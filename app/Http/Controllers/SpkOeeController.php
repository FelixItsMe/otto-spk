<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;
use RuntimeException;
use SimpleXMLElement;
use ZipArchive;
use Illuminate\Support\Facades\DB;

class SpkOeeController extends Controller
{
    private const INDONESIAN_MONTHS = [
        'januari' => 1,
        'februari' => 2,
        'maret' => 3,
        'april' => 4,
        'mei' => 5,
        'juni' => 6,
        'juli' => 7,
        'agustus' => 8,
        'september' => 9,
        'oktober' => 10,
        'november' => 11,
        'desember' => 12,
    ];

    public function index(): View
    {
        return view('spk-oee');
    }

    public function extractExcelByMonthSheet(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'data_file' => ['required', 'file', 'mimes:xlsx', 'max:10240'],
        ]);

        $grouped = $this->extractDataFromExcelGroupedBySheet($validated['data_file']);
        $averages = $this->calculateAveragesByProcessAllMonths($grouped);

        $listMachines = collect($averages['processes'])->keys();

        $machines = Machine::query()
            ->whereIn('code', $listMachines)
            ->get(['id', 'code'])
            ->keyBy('code');

        $insertAverages = [];
        foreach ($averages['processes'] as $processName => $processData) {
            $machine = $machines->get($processName);
            if ($machine === null) {
                continue;
            }

            $insertAverages[] = [
                'machine_id' => $machine->id,
                'process' => $processName,
                'pot' => $processData['metrics']['POT']['mean'] ?? null,
                'pot_sd' => $processData['metrics']['POT']['standard_deviation'] ?? null,
                'pot_shift_tersedia' => $processData['metrics']['POT Shift Tersedia']['mean'] ?? null,
                'pot_shift_tersedia_sd' => $processData['metrics']['POT Shift Tersedia']['standard_deviation'] ?? null,
                'unschedule_time' => $processData['metrics']['Unschedule Time']['mean'] ?? null,
                'unschedule_time_sd' => $processData['metrics']['Unschedule Time']['standard_deviation'] ?? null,
                'unschedule_time_shift_tersedia' => $processData['metrics']['Unschedule Time Shift Tersedia']['mean'] ?? null,
                'unschedule_time_shift_tersedia_sd' => $processData['metrics']['Unschedule Time Shift Tersedia']['standard_deviation'] ?? null,
                'waktu_berproduksi' => $processData['metrics']['Waktu Berproduksi']['mean'] ?? null,
                'waktu_berproduksi_sd' => $processData['metrics']['Waktu Berproduksi']['standard_deviation'] ?? null,
                'idle_time' => $processData['metrics']['Idle Time']['mean'] ?? null,
                'idle_time_sd' => $processData['metrics']['Idle Time']['standard_deviation'] ?? null,
                'l2' => $processData['metrics']['L2']['mean'] ?? null,
                'l2_sd' => $processData['metrics']['L2']['standard_deviation'] ?? null,
                'l21' => $processData['metrics']['L21']['mean'] ?? null,
                'l21_sd' => $processData['metrics']['L21']['standard_deviation'] ?? null,
                'l22' => $processData['metrics']['L22']['mean'] ?? null,
                'l22_sd' => $processData['metrics']['L22']['standard_deviation'] ?? null,
                'ig' => $processData['metrics']['IG']['mean'] ?? null,
                'ig_sd' => $processData['metrics']['IG']['standard_deviation'] ?? null,
                'ppt' => $processData['metrics']['PPT']['mean'] ?? null,
                'ppt_sd' => $processData['metrics']['PPT']['standard_deviation'] ?? null,
                'r' => $processData['metrics']['R']['mean'] ?? null,
                'r_sd' => $processData['metrics']['R']['standard_deviation'] ?? null,
                'dt' => $processData['metrics']['DT']['mean'] ?? null,
                'dt_sd' => $processData['metrics']['DT']['standard_deviation'] ?? null,
                'setup' => $processData['metrics']['Setup']['mean'] ?? null,
                'setup_sd' => $processData['metrics']['Setup']['standard_deviation'] ?? null,
                'p6' => $processData['metrics']['P6']['mean'] ?? null,
                'p6_sd' => $processData['metrics']['P6']['standard_deviation'] ?? null,
                'p5' => $processData['metrics']['P5']['mean'] ?? null,
                'p5_sd' => $processData['metrics']['P5']['standard_deviation'] ?? null,
                'p8' => $processData['metrics']['P8']['mean'] ?? null,
                'p8_sd' => $processData['metrics']['P8']['standard_deviation'] ?? null,
                'p9' => $processData['metrics']['P9']['mean'] ?? null,
                'p9_sd' => $processData['metrics']['P9']['standard_deviation'] ?? null,
                'breakdown' => $processData['metrics']['Breakdown']['mean'] ?? null,
                'breakdown_sd' => $processData['metrics']['Breakdown']['standard_deviation'] ?? null,
                'm1' => $processData['metrics']['M1']['mean'] ?? null,
                'm1_sd' => $processData['metrics']['M1']['standard_deviation'] ?? null,
                'm2' => $processData['metrics']['M2']['mean'] ?? null,
                'm2_sd' => $processData['metrics']['M2']['standard_deviation'] ?? null,
                'm4' => $processData['metrics']['M4']['mean'] ?? null,
                'm4_sd' => $processData['metrics']['M4']['standard_deviation'] ?? null,
                'm8' => $processData['metrics']['M8']['mean'] ?? null,
                'm8_sd' => $processData['metrics']['M8']['standard_deviation'] ?? null,
                'm9' => $processData['metrics']['M9']['mean'] ?? null,
                'm9_sd' => $processData['metrics']['M9']['standard_deviation'] ?? null,
                'clean' => $processData['metrics']['Clean']['mean'] ?? null,
                'clean_sd' => $processData['metrics']['Clean']['standard_deviation'] ?? null,
                'p2' => $processData['metrics']['P2']['mean'] ?? null,
                'p2_sd' => $processData['metrics']['P2']['standard_deviation'] ?? null,
                'p4' => $processData['metrics']['P4']['mean'] ?? null,
                'p4_sd' => $processData['metrics']['P4']['standard_deviation'] ?? null,
                'p17' => $processData['metrics']['P17']['mean'] ?? null,
                'p17_sd' => $processData['metrics']['P17']['standard_deviation'] ?? null,
                'p19' => $processData['metrics']['P19']['mean'] ?? null,
                'p19_sd' => $processData['metrics']['P19']['standard_deviation'] ?? null,
                'p12' => $processData['metrics']['P12']['mean'] ?? null,
                'p12_sd' => $processData['metrics']['P12']['standard_deviation'] ?? null,
                'trial' => $processData['metrics']['Trial']['mean'] ?? null,
                'trial_sd' => $processData['metrics']['Trial']['standard_deviation'] ?? null,
                'r1' => $processData['metrics']['R1']['mean'] ?? null,
                'r1_sd' => $processData['metrics']['R1']['standard_deviation'] ?? null,
                'r2' => $processData['metrics']['R2']['mean'] ?? null,
                'r2_sd' => $processData['metrics']['R2']['standard_deviation'] ?? null,
                'waiting' => $processData['metrics']['Waiting']['mean'] ?? null,
                'waiting_sd' => $processData['metrics']['Waiting']['standard_deviation'] ?? null,
                'l1' => $processData['metrics']['L1']['mean'] ?? null,
                'l1_sd' => $processData['metrics']['L1']['standard_deviation'] ?? null,
                'l3' => $processData['metrics']['L3']['mean'] ?? null,
                'l3_sd' => $processData['metrics']['L3']['standard_deviation'] ?? null,
                'h1' => $processData['metrics']['H1']['mean'] ?? null,
                'h1_sd' => $processData['metrics']['H1']['standard_deviation'] ?? null,
                'h2' => $processData['metrics']['H2']['mean'] ?? null,
                'h2_sd' => $processData['metrics']['H2']['standard_deviation'] ?? null,
                'h4' => $processData['metrics']['H4']['mean'] ?? null,
                'h4_sd' => $processData['metrics']['H4']['standard_deviation'] ?? null,
                'h6' => $processData['metrics']['H6']['mean'] ?? null,
                'h6_sd' => $processData['metrics']['H6']['standard_deviation'] ?? null,
                'h7' => $processData['metrics']['H7']['mean'] ?? null,
                'h7_sd' => $processData['metrics']['H7']['standard_deviation'] ?? null,
                'h8' => $processData['metrics']['H8']['mean'] ?? null,
                'h8_sd' => $processData['metrics']['H8']['standard_deviation'] ?? null,
                'h10' => $processData['metrics']['H10']['mean'] ?? null,
                'h10_sd' => $processData['metrics']['H10']['standard_deviation'] ?? null,
                'h11' => $processData['metrics']['H11']['mean'] ?? null,
                'h11_sd' => $processData['metrics']['H11']['standard_deviation'] ?? null,
                'h13' => $processData['metrics']['H13']['mean'] ?? null,
                'h13_sd' => $processData['metrics']['H13']['standard_deviation'] ?? null,
                'h14' => $processData['metrics']['H14']['mean'] ?? null,
                'h14_sd' => $processData['metrics']['H14']['standard_deviation'] ?? null,
                'h16' => $processData['metrics']['H16']['mean'] ?? null,
                'h16_sd' => $processData['metrics']['H16']['standard_deviation'] ?? null,
                'm5' => $processData['metrics']['M5']['mean'] ?? null,
                'm5_sd' => $processData['metrics']['M5']['standard_deviation'] ?? null,
                'm6' => $processData['metrics']['M6']['mean'] ?? null,
                'm6_sd' => $processData['metrics']['M6']['standard_deviation'] ?? null,
                'm7' => $processData['metrics']['M7']['mean'] ?? null,
                'm7_sd' => $processData['metrics']['M7']['standard_deviation'] ?? null,
                'q1' => $processData['metrics']['Q1']['mean'] ?? null,
                'q1_sd' => $processData['metrics']['Q1']['standard_deviation'] ?? null,
                'q2' => $processData['metrics']['Q2']['mean'] ?? null,
                'q2_sd' => $processData['metrics']['Q2']['standard_deviation'] ?? null,
                'q3' => $processData['metrics']['Q3']['mean'] ?? null,
                'q3_sd' => $processData['metrics']['Q3']['standard_deviation'] ?? null,
                'q4' => $processData['metrics']['Q4']['mean'] ?? null,
                'q4_sd' => $processData['metrics']['Q4']['standard_deviation'] ?? null,
                'total_output' => $processData['metrics']['Total Output']['mean'] ?? null,
                'total_output_sd' => $processData['metrics']['Total Output']['standard_deviation'] ?? null,
                'reject_output' => $processData['metrics']['Reject Output']['mean'] ?? null,
                'reject_output_sd' => $processData['metrics']['Reject Output']['standard_deviation'] ?? null,
                'good_output' => $processData['metrics']['Good Output']['mean'] ?? null,
                'good_output_sd' => $processData['metrics']['Good Output']['standard_deviation'] ?? null,
            ];
        }

        DB::table('machine_averages')->insert($insertAverages);

        return response()->json([
            'ok' => true,
            'message' => 'Data berhasil diekstrak dan dikelompokkan per sheet bulan.',
            'data' => DB::table('machine_averages')->get(),
        ]);
    }

    private function extractDataFromExcelGroupedBySheet(UploadedFile $uploadedFile): array
    {
        $absolutePath = $uploadedFile->getRealPath();
        if ($absolutePath === false) {
            throw new RuntimeException('File XLSX tidak dapat diakses.');
        }

        $zip = new ZipArchive();
        if ($zip->open($absolutePath) !== true) {
            throw new RuntimeException('File XLSX tidak dapat dibuka.');
        }

        try {
            $sharedStrings = $this->readSharedStrings($zip);
            $sheetTargets = $this->readSheetTargets($zip);
            $sheetDefinitions = $this->readWorkbookSheets($zip);

            $grouped = [];

            foreach ($sheetDefinitions as $sheetDefinition) {
                $relationId = $sheetDefinition['relation_id'];
                $sheetName = trim((string) $sheetDefinition['name']);
                $normalized = $this->normalizeMonthSheetName($sheetName);

                // Hanya proses sheet yang namanya bulan Indonesia.
                if (!isset(self::INDONESIAN_MONTHS[$normalized])) {
                    continue;
                }

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

                $headers = $this->normalizeHeaderRow($rows[0]['values']);
                if ($headers === []) {
                    continue;
                }

                $groupedRows = [];

                for ($i = 1; $i < count($rows); $i++) {
                    $rowValues = $rows[$i]['values'];
                    $record = [];

                    foreach ($headers as $columnIndex => $headerName) {
                        if ($headerName === '') {
                            continue;
                        }

                        if ($headerName === 'Proses') {
                            $record[$headerName] = isset($rowValues[$columnIndex]) ? (string) $rowValues[$columnIndex] : '';
                            continue;
                        }

                        $record[$headerName] = $this->toNullableFloat($rowValues[$columnIndex] ?? null);
                    }

                    if ($this->isRowEmpty($record)) {
                        continue;
                    }

                    $groupedRows[] = [
                        'row_number' => $rows[$i]['row_number'],
                        'data' => $record,
                    ];
                }

                $grouped[$sheetName] = [
                    'month_number' => self::INDONESIAN_MONTHS[$normalized],
                    'rows' => $groupedRows,
                ];
            }

            return $grouped;
        } finally {
            $zip->close();
        }
    }

    private function calculateAveragesByProcessAllMonths(array $groupedData): array
    {
        $processBuckets = [];
        $monthsIncluded = [];

        foreach ($groupedData as $sheetName => $sheetData) {
            $monthsIncluded[$sheetName] = true;

            foreach (($sheetData['rows'] ?? []) as $row) {
                $rowData = $row['data'] ?? [];
                $processName = trim((string) ($rowData['Proses'] ?? ''));

                if ($processName === '') {
                    continue;
                }

                if (!isset($processBuckets[$processName])) {
                    $processBuckets[$processName] = [
                        'row_count' => 0,
                        'totals' => [],
                        'counts' => [],
                        'months' => [],
                    ];
                }

                $processBuckets[$processName]['row_count']++;
                $processBuckets[$processName]['months'][$sheetName] = true;

                foreach ($rowData as $key => $value) {
                    if ($key === 'Proses') {
                        continue;
                    }

                    if (!is_numeric($value)) {
                        continue;
                    }

                    $processBuckets[$processName]['totals'][$key] = ($processBuckets[$processName]['totals'][$key] ?? 0.0) + (float) $value;
                    $processBuckets[$processName]['counts'][$key] = ($processBuckets[$processName]['counts'][$key] ?? 0) + 1;
                    $processBuckets[$processName]['value'][$key][] = (float) $value;
                }
            }

        }

        $processAverages = [];

        foreach ($processBuckets as $processName => $bucket) {
            $metrics = [];

            foreach ($bucket['totals'] as $key => $total) {
                $divider = $bucket['counts'][$key] ?? 0;
                if ($divider <= 0) {
                    continue;
                }

                $mean = round($total / $divider, 4);

                $jumlahKuadratJarak = 0.0;
                foreach ($bucket['value'][$key] as $value) {
                    $jumlahKuadratJarak += pow($value - $mean, 2);
                }

                $metrics[$key]['mean'] = $mean;
                $metrics[$key]['standard_deviation'] = round(sqrt($jumlahKuadratJarak / $divider), 4);
            }

            $processAverages[$processName] = [
                'row_count' => $bucket['row_count'],
                'month_count' => count($bucket['months']),
                'metrics' => $metrics,
            ];
        }

        return [
            'month_count' => count($monthsIncluded),
            'processes' => $processAverages,
        ];
    }

    private function normalizeHeaderRow(array $headers): array
    {
        $normalized = [];

        foreach ($headers as $columnIndex => $header) {
            $normalized[$columnIndex] = trim((string) ($header ?? ''));
        }

        return $normalized;
    }

    private function isRowEmpty(array $record): bool
    {
        foreach ($record as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function normalizeMonthSheetName(string $sheetName): string
    {
        return mb_strtolower(trim($sheetName));
    }

    private function toNullableFloat(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $normalized = trim((string) $value);
        if ($normalized === '') {
            return null;
        }

        $normalized = str_replace(' ', '', $normalized);

        if (preg_match('/^-?\d{1,3}(\.\d{3})*(,\d+)?$/', $normalized) === 1) {
            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);
        } elseif (preg_match('/^-?\d{1,3}(,\d{3})*(\.\d+)?$/', $normalized) === 1) {
            $normalized = str_replace(',', '', $normalized);
        } else {
            $normalized = str_replace(',', '.', $normalized);
        }

        return is_numeric($normalized) ? (float) $normalized : null;
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

}
