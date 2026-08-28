<?php

namespace App\Http\Controllers;

use App\Models\LogReport;
use App\Models\Machine;
use App\Models\TreatmentRecommendation;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;
use SimpleXMLElement;
use ZipArchive;
use Symfony\Component\Process\Process;
use Throwable;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UploadDataController extends Controller
{
    public function index(): View
    {
        $currentYear = (int) now()->format('Y');
        $uploadYears = range($currentYear + 1, $currentYear - 10);
        $uploadMonths = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];

        return view('spk.upload', [
            'pageTitle' => 'Integrasi Data Produksi',
            'pageSubtitle' => 'Unggah data log mesin untuk proses OEE dan klasterisasi',
            'uploadYears' => $uploadYears,
            'uploadMonths' => $uploadMonths,
        ]);
    }

    public function downloadTemplate(): RedirectResponse|BinaryFileResponse
    {
        $templatePath = public_path('assets/templates/format_data.xlsx');

        if (!file_exists($templatePath)) {
            return redirect()
                ->route('upload.index')
                ->with('error', 'Template XLSX tidak ditemukan. Silahkan hubungi administrator.');
        }

        return response()->download($templatePath, 'oee-import-template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'upload_year' => ['required', 'integer', 'min:2000', 'max:' . ((int) now()->format('Y') + 1)],
            'upload_month' => ['required', 'integer', 'min:1', 'max:12'],
            'data_file' => ['required', 'file', 'mimes:xlsx', 'max:10240'],
        ]);

        $result = $this->importWorkbook($request->file('data_file'), (int) $validated['upload_month'], (int) $validated['upload_year']);

        return redirect()
            ->route('upload.index')
            ->with('success', "Import selesai: " . count($result) . " baris berhasil disimpan. Silahkan cek data di halaman dashboard.");
    }

    private function importWorkbook(UploadedFile $uploadedFile, int $uploadMonth, int $uploadYear): array
    {
        $storedPath = $uploadedFile->store('oee-imports', 'public');
        $absolutePath = storage_path('app/public/' . $storedPath);

        $zip = new ZipArchive();
        if ($zip->open($absolutePath) !== true) {
            throw new RuntimeException('File XLSX tidak dapat dibuka.');
        }

        $sharedStrings = $this->readSharedStrings($zip);
        $sheetTargets = $this->readSheetTargets($zip);
        $sheetDefinitions = $this->readWorkbookSheets($zip);

        $data = [];

        foreach ($sheetDefinitions as $sheetDefinition) {
            $relationId = $sheetDefinition['relation_id'];
            $sheetName = $sheetDefinition['name'];

            if (!isset($sheetTargets[$relationId])) {
                continue;
            }

            $sheetPath = 'xl/' . ltrim(str_replace('\\', '/', $sheetTargets[$relationId]), '/');
            $sheetXml = $zip->getFromName($sheetPath);
            if ($sheetXml === false) {
                continue;
            }

            $rows = $this->extractRowsFromSheet($sheetXml, $sharedStrings);
            if (count($rows) <= 1) {
                continue;
            }

            $headers = $rows[0]['values'];
            $sheetRowCount = 0;

            for ($i = 1; $i < count($rows); $i++) {
                $rowValues = $rows[$i]['values'];
                $metrics = [];

                foreach ($headers as $columnIndex => $headerName) {
                    $header = trim((string) $headerName);
                    if ($header === '') {
                        continue;
                    }

                    $metrics[$header] = $rowValues[$columnIndex] ?? null;
                }

                $process = $metrics['Proses'] ?? null;
                if ($process === null || $process === '') {
                    continue;
                }

                $data[] = [
                    'month_name' => $sheetName,
                    'row_number' => $rows[$i]['row_number'],
                    'process' => (string) $process,
                    'pot' => $this->toNullableFloat($metrics['POT'] ?? null),
                    'pot_shift_available' => $this->toNullableFloat($metrics['POT Shift Tersedia'] ?? null),
                    'unschedule_time' => $this->toNullableFloat($metrics['Unschedule Time'] ?? null),
                    'unschedule_time_shift_available' => $this->toNullableFloat($metrics['Unschedule Time Shift Tersedia'] ?? null),
                    'productive_time' => $this->toNullableFloat($metrics['Waktu Beproduksi'] ?? null),
                    'idle_time' => $this->toNullableFloat($metrics['Idle Time'] ?? null),
                    'ppt' => $this->toNullableFloat($metrics['PPT'] ?? null),
                    'running_time' => $this->toNullableFloat($metrics['R'] ?? null),
                    'total_output' => $this->toNullableFloat($metrics['Total Output'] ?? null),
                    'reject_output' => $this->toNullableFloat($metrics['Reject Output'] ?? null),
                    'good_output' => $this->toNullableFloat($metrics['Good Output'] ?? null),
                    'oee' => $this->toNullableFloat($metrics['OEE'] ?? null),
                    'metrics' => $metrics,
                ];
            }
        }

        $zip->close();

        // dd($data);

        $mapped = $this->mapRowsForAnalysis($data);

        $analiticData =  $this->runPythonAnalysis($mapped, $uploadYear);

        $machinesCode = collect($data)->map(function($item){
            return [
                'code' => $item['process'],
                'name' => $item['process'],
                'status' => 'aktif',
            ];
        })->unique('code')->values()->all();

        DB::table('machines')->insertOrIgnore($machinesCode);

        $machines = Machine::query()
            ->with(['average'])
            ->whereIn('code', collect($machinesCode)->pluck('code')->all())
            ->get()
            ->keyBy('code');

        // dd($machines);

        $savedData = [];
        $recommendations = [];

        foreach ($analiticData['analysis_results'] as $value) {
            $machine = $machines[$value['Proses']] ?? null;
            if($machine) {
                $machineData = collect($data)->where('process', $value['Proses'])->first();

                $status = '';
                if($value['Status'] === 'Kritis') {
                    $status = 0;
                } elseif($value['Status'] === 'Waspada') {
                    $status = 1;
                } else {
                    $status = 2;
                }
                
                $insertData = [
                    'machine_id' => $machine->id,
                    'bulan' => $uploadMonth,
                    'tahun' => $uploadYear,
                    'process' => $machineData['process'] ?? null,
                    'pot' => $machineData['pot'] ?? null,
                    'pot_shift_tersedia' => $machineData['pot_shift_available'] ?? null,
                    'unschedule_time' => $machineData['unschedule_time'] ?? null,
                    'unschedule_time_shift_tersedia' => $machineData['unschedule_time_shift_available'] ?? null,
                    'waktu_berproduksi' => $machineData['productive_time'] ?? null,
                    'idle_time' => $machineData['idle_time'] ?? null,
                    'l2' => $machineData['metrics']['L2'] ?? null,
                    'l21' => $machineData['metrics']['L21'] ?? null,
                    'l22' => $machineData['metrics']['L22'] ?? null,
                    'ig' => $machineData['metrics']['IG'] ?? null,
                    'ppt' => $machineData['metrics']['PPT'] ?? null,
                    'r' => $machineData['metrics']['R'] ?? null,
                    'dt' => $machineData['metrics']['DT'] ?? null,
                    'setup' => $machineData['metrics']['Setup'] ?? null,
                    'p6' => $machineData['metrics']['P6'] ?? null,
                    'p5' => $machineData['metrics']['P5'] ?? null,
                    'p8' => $machineData['metrics']['P8'] ?? null,
                    'p9' => $machineData['metrics']['P9'] ?? null,
                    'breakdown' => $machineData['metrics']['Breakdown'] ?? null,
                    'm1' => $machineData['metrics']['M1'] ?? null,
                    'm2' => $machineData['metrics']['M2'] ?? null,
                    'm4' => $machineData['metrics']['M4'] ?? null,
                    'm8' => $machineData['metrics']['M8'] ?? null,
                    'm9' => $machineData['metrics']['M9'] ?? null,
                    'clean' => $machineData['metrics']['Clean'] ?? null,
                    'p2' => $machineData['metrics']['P2'] ?? null,
                    'p4' => $machineData['metrics']['P4'] ?? null,
                    'p17' => $machineData['metrics']['P17'] ?? null,
                    'p19' => $machineData['metrics']['P19'] ?? null,
                    'p12' => $machineData['metrics']['P12'] ?? null,
                    'trial' => $machineData['metrics']['Trial'] ?? null,
                    'r1' => $machineData['metrics']['R1'] ?? null,
                    'r2' => $machineData['metrics']['R2'] ?? null,
                    'waiting' => $machineData['metrics']['Waiting'] ?? null,
                    'l1' => $machineData['metrics']['L1'] ?? null,
                    'l3' => $machineData['metrics']['L3'] ?? null,
                    'h1' => $machineData['metrics']['H1'] ?? null,
                    'h2' => $machineData['metrics']['H2'] ?? null,
                    'h4' => $machineData['metrics']['H4'] ?? null,
                    'h6' => $machineData['metrics']['H6'] ?? null,
                    'h7' => $machineData['metrics']['H7'] ?? null,
                    'h8' => $machineData['metrics']['H8'] ?? null,
                    'h10' => $machineData['metrics']['H10'] ?? null,
                    'h11' => $machineData['metrics']['H11'] ?? null,
                    'h13' => $machineData['metrics']['H13'] ?? null,
                    'h14' => $machineData['metrics']['H14'] ?? null,
                    'h16' => $machineData['metrics']['H16'] ?? null,
                    'm5' => $machineData['metrics']['M5'] ?? null,
                    'm6' => $machineData['metrics']['M6'] ?? null,
                    'm7' => $machineData['metrics']['M7'] ?? null,
                    'q1' => $machineData['metrics']['Q1'] ?? null,
                    'q2' => $machineData['metrics']['Q2'] ?? null,
                    'q3' => $machineData['metrics']['Q3'] ?? null,
                    'q4' => $machineData['metrics']['Q4'] ?? null,
                    'total_output' => $machineData['total_output'] ?? null,
                    'reject_output' => $machineData['reject_output'] ?? null,
                    'good_output' => $machineData['good_output'] ?? null,
                    'availability' => $value['Availability'] ?? null,
                    'performance' => $value['Performance'] ?? null,
                    'quality' => $value['Quality'] ?? null,
                    'oee' => $value['OEE_Score'] ?? null,
                    'cluster' => $value['Cluster'] ?? null,
                    'status' => $status,
                    'is_anomaly' => $value['is_anomali'] ? true : false,
                    'x_axis' => $value['PCA1'] ?? null,
                    'y_axis' => $value['PCA2'] ?? null,
                    'silhouette_score' => $analiticData['silhouette_score'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $pot = $insertData['pot'] ?? 1; // Hindari division by zero

                $zScoreParameter = $machine->average;

                $breakdownLosses = $this->extractLosses($insertData, $zScoreParameter, $this->kategoriBreakdown);
                $waitingLosses   = $this->extractLossesNew($insertData['waiting'] ?? 0, $zScoreParameter['waiting'] ?? 0, $zScoreParameter['waiting_sd'] ?? 1);
                $setupLosses   = $this->extractLossesNew($insertData['setup'] ?? 0, $zScoreParameter['setup'] ?? 0, $zScoreParameter['setup_sd'] ?? 1);
                $cleanLosses   = $this->extractLossesNew($insertData['clean'] ?? 0, $zScoreParameter['clean'] ?? 0, $zScoreParameter['clean_sd'] ?? 1);

                $rekomendasiTeks = "";
                $penyebabUtamaString = "";

                if ($insertData['breakdown'] > 0) {
                    $message = '';
                    if ($status == 1) {
                        $message = "Mesin menunjukkan gejala penurunan efisiensi akibat hambatan mekanis. Jadwalkan Preventive Maintenance (perawatan ringan) pada saat pergantian shift atau akhir pekan.";
                    } elseif ($status == 0) {
                        $message = "Mesin mengalami kerusakan mekanis yang signifikan. Segera lakukan perbaikan darurat dan evaluasi komponen yang rusak untuk mencegah kerusakan berulang.";
                    }
                    $recommendations[] = [
                        'process' => $value['Proses'] ?? null,
                        'metric' => 'breakdown',
                        'recommendation' => "Kerusakan Mekanis (Breakdown: {$insertData['breakdown']} menit): " . $message,
                    ];
                }

                if ($waitingLosses > 0) {
                    $message = '';
                    if ($status == 1) {
                        $message = "Terdeteksi pemborosan waktu tunggu. Cek kembali dengan operator shift atau tim logistik terkait percepatan persiapan material.";
                    } elseif ($status == 0) {
                        $message = "Waktu idle/tunggu (waiting) telah merugikan efisiensi utilitas pabrik secara signifikan. Jadwalkan audit lintas departemen (Supply Chain) untuk mengevaluasi alur distribusi dari gudang dan perombakan jadwal serah-terima shift operator.";
                    }
                    $recommendations[] = [
                        'process' => $value['Proses'] ?? null,
                        'metric' => 'waiting' ?? null,
                        'recommendation' => "Hambatan Logistik/SDM (waiting: {$waitingLosses} menit): " . $message,
                    ];
                }

                if ($setupLosses > 0) {
                    $message = '';
                    if ($status == 1) {
                        $message = "Waktu penyetelan mesin (setup) sedikit melampaui batas wajar. Lakukan observasi pada teknisi setup.";
                    } elseif ($status == 0) {
                        $message = "Waktu setup menggerus kapasitas produksi secara ekstrem. Lakukan revisi Jadwal Induk Produksi untuk mengelompokkan produk dengan ukuran/komponen mesin yang sama agar frekuensi ganti cetakan (changeover) drastis menurun.";
                    }
                    $recommendations[] = [
                        'process' => $value['Proses'] ?? null,
                        'metric' => 'setup' ?? null,
                        'recommendation' => "Hambatan Setup (setup: {$setupLosses} menit): " . $message,
                    ];
                }

                if ($cleanLosses > 0) {
                    $message = '';
                    if ($status == 1) {
                        $message = "Durasi transisi pembersihan antar-batch lebih lambat. Lakukan observasi pada teknisi cleaning service dan evaluasi prosedur pembersihan.";
                    } elseif ($status == 0) {
                        $message = "Terdeteksi inefisiensi tingkat tinggi akibat kesulitan pembersihan mesin (changeover). Disarankan mengatur ulang urutan produksi berdasarkan matriks pembersihan guna meminimalkan durasi dan tingkat kesulitan pencucian.";
                    }
                    $recommendations[] = [
                        'process' => $value['Proses'] ?? null,
                        'metric' => 'clean' ?? null,
                        'recommendation' => "Hambatan Cleaning (clean: {$cleanLosses} menit): " . $message,
                    ];
                }

                $savedData[] = $insertData;
            }
        }

        LogReport::query()->where('bulan', $uploadMonth)->where('tahun', $uploadYear)->delete();

        LogReport::query()->insert($savedData);

        // Setelah LogReport disimpan, kita perlu mengupdate log_report_id pada rekomendasi
        $logReports = LogReport::query()
            ->where('bulan', $uploadMonth)
            ->where('tahun', $uploadYear)
            ->get()
            ->keyBy('process');

        $insertedRecommendations = [];
            
        foreach ($recommendations as &$rec) {
            $process = $rec['process'] ?? null;
            if ($process && isset($logReports[$process])) {
                $rec['log_report_id'] = $logReports[$process]->id;
                $insertedRecommendations[] = [
                    'log_report_id' => $logReports[$process]->id,
                    'metric' => $rec['metric'] ?? null,
                    'recommendation' => $rec['recommendation'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        TreatmentRecommendation::query()->insert($insertedRecommendations);

        return $savedData;
    }

    /**
     * Kategori Downtime untuk Hierarki Rule-Based
     */
    private $kategoriBreakdown = ['m1', 'm2', 'm4'];
    private $kategoriWaiting   = ['l1', 'l3', 'h1', 'h2', 'h4', 'p10', 'p11', 'p13', 'p14', 'p16'];
    private $kategoriSetupClean = ['p6', 'p5', 'p8', 'p9', 'p2', 'p4', 'p17', 'p19', 'p12'];

    /**
     * Helper function untuk mengekstrak dan mengurutkan losses berdasarkan kategori
     */
    private function extractLosses($dataLog, $zScoreParameter, $kategori)
    {
        $losses = [];
        foreach ($kategori as $kode) {
            // Asumsi field di database menggunakan lowercase atau uppercase sesuai model
            $nilai = $dataLog[$kode] ?? 0;
            if ($nilai > 0) {
                $zScrore = ($nilai - ($zScoreParameter[$kode] ?? 0)) / (($zScoreParameter[$kode . '_sd'] ?? 0) == 0 ? 1 : ($zScoreParameter[$kode . '_sd'] ?? 1)); // Hitung selisih dengan rata-rata (z-score)
                if ($zScrore > 5) {
                    $losses[$kode] = $nilai;
                }
            }
        }
        
        return $losses;
    }

    /**
     * Helper function untuk mengekstrak dan mengurutkan losses berdasarkan kategori
     */
    private function extractLossesNew($nilai, $mean, $sd)
    {
        $losses = 0;
        if ($nilai > 0) {
            $zScrore = ($nilai - $mean) / ($sd == 0 ? 1 : $sd); // Hitung selisih dengan rata-rata (z-score)
            if ($zScrore > 5) {
                $losses = $nilai;
            }
        }

        return $losses;
    }

    private function mapRowsForAnalysis($rows): array
    {
        $mapped = [];
        foreach ($rows as $row) {
            $mapped[] = [
                'Bulan' => $row['month_name'],
                'Proses' => $row['process'],
                'POT' => $row['pot'],
                'Waktu_Beproduksi' => $row['productive_time'],
                'Idle_Time' => $row['idle_time'],
                'PPT' => $row['ppt'],
                'Running_Time' => $row['running_time'],
                'Total_Output' => $row['total_output'],
                'Good_Output' => $row['good_output'],
                'OEE' => $row['oee'],
            ];
        }

        return $mapped;
    }

    private function runPythonAnalysis(array $rows, ?int $year): array
    {
        $scriptPath = base_path('python/oee_analysis.py');
        if (!file_exists($scriptPath)) {
            throw new \RuntimeException('Script python/oee_analysis.py tidak ditemukan.');
        }

        $payload = json_encode([
            'year' => $year,
            'rows' => $rows,
        ], JSON_THROW_ON_ERROR);

        $script = base_path('/python/venv/bin/python3');

        $commands = [
            [$script, $scriptPath],
            // ['py', '-3', $scriptPath],
        ];

        $lastError = 'Python process gagal dijalankan.';

        foreach ($commands as $command) {
            $process = new Process($command, base_path());
            $process->setInput($payload);
            $process->setTimeout(120);
            $process->run();

            if (!$process->isSuccessful()) {
                $lastError = trim($process->getErrorOutput() ?: $process->getOutput());
                continue;
            }

            $decoded = json_decode($process->getOutput(), true);
            if (!is_array($decoded)) {
                $lastError = 'Output python bukan JSON yang valid.';
                continue;
            }

            if (($decoded['ok'] ?? false) !== true) {
                $lastError = (string) ($decoded['message'] ?? 'Python mengembalikan status gagal.');
                continue;
            }

            return $decoded;
        }

        throw new \RuntimeException($lastError);
    }

    private function readSharedStrings(ZipArchive $zip): array
    {
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsXml === false) {
            return [];
        }

        $xml = simplexml_load_string($sharedStringsXml);
        if (!$xml instanceof SimpleXMLElement) {
            return [];
        }

        $items = [];
        $nodes = $xml->xpath('//*[local-name()="si"]');
        if ($nodes === false) {
            return [];
        }

        foreach ($nodes as $node) {
            $texts = $node->xpath('.//*[local-name()="t"]');
            if ($texts === false || count($texts) === 0) {
                $items[] = '';
                continue;
            }

            $value = '';
            foreach ($texts as $textNode) {
                $value .= (string) $textNode;
            }

            $items[] = $value;
        }

        return $items;
    }

    private function readSheetTargets(ZipArchive $zip): array
    {
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($relsXml === false) {
            throw new RuntimeException('Relasi workbook tidak ditemukan di file XLSX.');
        }

        $xml = simplexml_load_string($relsXml);
        if (!$xml instanceof SimpleXMLElement) {
            throw new RuntimeException('Relasi workbook tidak valid.');
        }

        $targets = [];
        $relationships = $xml->xpath('//*[local-name()="Relationship"]');
        if ($relationships === false) {
            return [];
        }

        foreach ($relationships as $relationship) {
            $id = (string) $relationship['Id'];
            $target = (string) $relationship['Target'];
            if ($id !== '' && $target !== '') {
                $targets[$id] = $target;
            }
        }

        return $targets;
    }

    private function readWorkbookSheets(ZipArchive $zip): array
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        if ($workbookXml === false) {
            throw new RuntimeException('Workbook XML tidak ditemukan di file XLSX.');
        }

        $xml = simplexml_load_string($workbookXml);
        if (!$xml instanceof SimpleXMLElement) {
            throw new RuntimeException('Workbook XML tidak valid.');
        }

        $sheets = [];
        $nodes = $xml->xpath('//*[local-name()="sheet"]');
        if ($nodes === false) {
            return [];
        }

        foreach ($nodes as $node) {
            $attrs = $node->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
            $sheets[] = [
                'name' => (string) $node['name'],
                'relation_id' => (string) $attrs['id'],
            ];
        }

        return $sheets;
    }

    private function extractRowsFromSheet(string $sheetXml, array $sharedStrings): array
    {
        $xml = simplexml_load_string($sheetXml);
        if (!$xml instanceof SimpleXMLElement) {
            return [];
        }

        $rows = [];
        $rowNodes = $xml->xpath('//*[local-name()="sheetData"]/*[local-name()="row"]');
        if ($rowNodes === false) {
            return [];
        }

        foreach ($rowNodes as $rowNode) {
            $values = [];
            $cellNodes = $rowNode->xpath('./*[local-name()="c"]');
            if ($cellNodes === false) {
                continue;
            }

            foreach ($cellNodes as $cell) {
                $reference = (string) $cell['r'];
                $columnIndex = $this->columnIndexFromReference($reference);
                if ($columnIndex <= 0) {
                    continue;
                }

                $values[$columnIndex] = $this->extractCellValue($cell, $sharedStrings);
            }

            if (count($values) === 0) {
                continue;
            }

            $rows[] = [
                'row_number' => (int) $rowNode['r'],
                'values' => $values,
            ];
        }

        return $rows;
    }

    private function extractCellValue(SimpleXMLElement $cell, array $sharedStrings): string|float|null
    {
        $type = (string) $cell['t'];
        $vNode = $cell->xpath('./*[local-name()="v"]');
        $value = ($vNode !== false && isset($vNode[0])) ? (string) $vNode[0] : null;

        if ($value === null || $value === '') {
            return null;
        }

        if ($type === 's') {
            $index = (int) $value;
            return $sharedStrings[$index] ?? null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        return $value;
    }

    private function columnIndexFromReference(string $reference): int
    {
        if ($reference === '') {
            return 0;
        }

        preg_match('/^[A-Z]+/', strtoupper($reference), $matches);
        if (!isset($matches[0])) {
            return 0;
        }

        $letters = $matches[0];
        $index = 0;

        for ($i = 0; $i < strlen($letters); $i++) {
            $index = $index * 26 + (ord($letters[$i]) - 64);
        }

        return $index;
    }

    private function toNullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        return null;
    }
}
