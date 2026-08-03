<?php

namespace App\Http\Controllers;

use App\Models\OeeImportBatch;
use App\Models\OeeImportRow;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Phpml\Clustering\KMeans;
use Phpml\Preprocessing\Normalizer;
use Symfony\Component\Process\Process;
use Throwable;

class AnalysisController extends Controller
{
    public function index(Request $request): View
    {
        $availableYears = OeeImportBatch::query()
            ->select('upload_year')
            ->distinct()
            ->orderBy('upload_year', 'desc')
            ->pluck('upload_year')
            ->values();

        $selectedYear = $request->integer('year');
        if (!$selectedYear && $availableYears->isNotEmpty()) {
            $selectedYear = (int) $availableYears->first();
        }

        $batch = OeeImportBatch::query()
            ->when($selectedYear, fn ($q) => $q->where('upload_year', $selectedYear))
            ->latest()
            ->first();

        $rows = collect();
        if ($batch) {
            $rows = OeeImportRow::query()
                ->where('oee_import_batch_id', $batch->id)
                ->orderBy('month_name', 'asc')
                ->orderBy('row_number', 'asc')
                ->get();
        }

        $dfAll = $this->mapRowsForAnalysis($rows);

        $analysisResults = [];
        $silhouetteScore = 0.0;
        $analysisSource = 'python';
        $analysisError = null;

        if (!empty($dfAll)) {
            try {
                $pythonResult = $this->runPythonAnalysis($dfAll, $selectedYear);
                $analysisResults = $pythonResult['analysis_results'] ?? [];
                $silhouetteScore = (float) ($pythonResult['silhouette_score'] ?? 0.0);
            } catch (Throwable $exception) {
                $analysisSource = 'php-fallback';
                $analysisError = $exception->getMessage();

                [$analysisResults, $silhouetteScore] = $this->runPhpFallbackAnalysis($dfAll);
            }
        }

        return view('spk.analysis', [
            'pageTitle' => 'Analisis K-Means & Anomali',
            'pageSubtitle' => 'Hasil klasterisasi kondisi mesin berdasarkan data impor OEE',
            'selectedYear' => $selectedYear,
            'availableYears' => $availableYears,
            'activeBatch' => $batch,
            'analysisResults' => $analysisResults,
            'silhouetteScore' => $silhouetteScore,
            'analysisSource' => $analysisSource,
            'analysisError' => $analysisError,
        ]);
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
                'Total_Output' => $row['total_output'],
                'Good_Output' => $row['good_output'],
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

    private function runPhpFallbackAnalysis(array $dfAll): array
    {
        $maxRatePerMachine = [];

        foreach ($dfAll as &$data) {
            $waktu = $data['Waktu_Beproduksi'] > 0 ? $data['Waktu_Beproduksi'] : 1;
            $currentRate = $data['Total_Output'] / $waktu;

            $mesin = $data['Proses'];
            if (!isset($maxRatePerMachine[$mesin]) || $currentRate > $maxRatePerMachine[$mesin]) {
                $maxRatePerMachine[$mesin] = $currentRate;
            }
        }
        unset($data);

        $samples = [];
        foreach ($dfAll as $key => &$data) {
            $pot = $data['POT'] > 0 ? $data['POT'] : 1;
            $waktu = $data['Waktu_Beproduksi'] > 0 ? $data['Waktu_Beproduksi'] : 1;
            $totalOutput = $data['Total_Output'] > 0 ? $data['Total_Output'] : 1;

            $a = $data['Waktu_Beproduksi'] / $pot;
            $q = $data['Good_Output'] / $totalOutput;

            $currentRate = $data['Total_Output'] / $waktu;
            $maxRate = $maxRatePerMachine[$data['Proses']] > 0 ? $maxRatePerMachine[$data['Proses']] : 1;
            $p = $currentRate / $maxRate;

            $data['Availability'] = min($a, 1.0);
            $data['Performance'] = min($p, 1.0);
            $data['Quality'] = min($q, 1.0);
            $data['OEE_Score'] = $data['Availability'] * $data['Performance'] * $data['Quality'];

            $samples[$key] = [$data['Availability'], $data['Performance'], $data['Quality']];
        }
        unset($data);

        if (count($samples) >= 1) {
            $scaler = new Normalizer();
            $scaler->fit($samples);
            $scaler->transform($samples);

            $kmeans = new KMeans(min(3, count($samples)));
            $clusters = $kmeans->cluster($samples);

            $clusterAvgOEE = [];
            foreach ($clusters as $clusterIndex => $clusterSamples) {
                $totalOee = 0.0;
                $count = 0;
                foreach ($clusterSamples as $sample) {
                    $originalKey = array_search($sample, $samples);
                    if ($originalKey === false) {
                        continue;
                    }

                    $totalOee += $dfAll[$originalKey]['OEE_Score'];
                    $dfAll[$originalKey]['Cluster'] = $clusterIndex;
                    $count++;
                }

                $clusterAvgOEE[$clusterIndex] = $count > 0 ? ($totalOee / $count) : 0.0;
            }

            asort($clusterAvgOEE);
            $sortedClusterKeys = array_values(array_keys($clusterAvgOEE));

            if (count($sortedClusterKeys) >= 3) {
                $mappingStatus = [
                    $sortedClusterKeys[0] => 'Kritis',
                    $sortedClusterKeys[1] => 'Waspada',
                    $sortedClusterKeys[2] => 'Optimal',
                ];

                foreach ($dfAll as &$data) {
                    if (!isset($data['Cluster'])) {
                        $data['Status'] = 'Anomali';
                    } else {
                        $clusterIndex = $data['Cluster'];
                        $data['Status'] = $mappingStatus[$clusterIndex] ?? 'Unknown';
                    }
                }
                unset($data);
            } else {
                foreach ($dfAll as &$data) {
                    $data['Status'] = $data['Status'] ?? 'Waspada';
                }
                unset($data);
            }
        } else {
            foreach ($dfAll as &$data) {
                $data['Status'] = 'Waspada';
            }
            unset($data);
        }

        return [$dfAll, 0.0];
    }

    private function clip01(float $value): float
    {
        return max(0.0, min(1.0, $value));
    }

    private function minMaxScale(array $rows): array
    {
        if (count($rows) === 0) {
            return [];
        }

        $columns = count($rows[0]);
        $mins = array_fill(0, $columns, INF);
        $maxs = array_fill(0, $columns, -INF);

        foreach ($rows as $row) {
            for ($i = 0; $i < $columns; $i++) {
                $mins[$i] = min($mins[$i], (float) $row[$i]);
                $maxs[$i] = max($maxs[$i], (float) $row[$i]);
            }
        }

        $scaled = [];
        foreach ($rows as $row) {
            $newRow = [];
            for ($i = 0; $i < $columns; $i++) {
                $range = $maxs[$i] - $mins[$i];
                $newRow[] = $range <= 0 ? 0.0 : (((float) $row[$i] - $mins[$i]) / $range);
            }
            $scaled[] = $newRow;
        }

        return $scaled;
    }

    private function kmeans(array $rows, int $k = 3, int $maxIter = 20): array
    {
        $n = count($rows);
        if ($n === 0) {
            return [[], []];
        }

        $k = max(1, min($k, $n));
        usort($rows, fn ($a, $b) => array_sum($a) <=> array_sum($b));

        $centroids = [];
        if ($k === 1) {
            $centroids[] = $rows[(int) floor(($n - 1) / 2)];
        } elseif ($k === 2) {
            $centroids[] = $rows[0];
            $centroids[] = $rows[$n - 1];
        } else {
            $centroids[] = $rows[0];
            $centroids[] = $rows[(int) floor(($n - 1) / 2)];
            $centroids[] = $rows[$n - 1];
        }

        $labels = array_fill(0, $n, 0);

        for ($iter = 0; $iter < $maxIter; $iter++) {
            $changed = false;

            for ($i = 0; $i < $n; $i++) {
                $bestCluster = 0;
                $bestDist = INF;

                for ($c = 0; $c < $k; $c++) {
                    $dist = $this->euclideanDistance($rows[$i], $centroids[$c]);
                    if ($dist < $bestDist) {
                        $bestDist = $dist;
                        $bestCluster = $c;
                    }
                }

                if ($labels[$i] !== $bestCluster) {
                    $labels[$i] = $bestCluster;
                    $changed = true;
                }
            }

            $newCentroids = array_fill(0, $k, [0.0, 0.0, 0.0]);
            $counts = array_fill(0, $k, 0);

            for ($i = 0; $i < $n; $i++) {
                $label = $labels[$i];
                $counts[$label]++;
                for ($d = 0; $d < count($rows[$i]); $d++) {
                    $newCentroids[$label][$d] += $rows[$i][$d];
                }
            }

            for ($c = 0; $c < $k; $c++) {
                if ($counts[$c] > 0) {
                    for ($d = 0; $d < count($newCentroids[$c]); $d++) {
                        $newCentroids[$c][$d] /= $counts[$c];
                    }
                } else {
                    $newCentroids[$c] = $centroids[$c];
                }
            }

            $centroids = $newCentroids;

            if (!$changed) {
                break;
            }
        }

        return [$labels, $centroids];
    }

    private function euclideanDistance(array $a, array $b): float
    {
        $sum = 0.0;
        for ($i = 0; $i < count($a); $i++) {
            $sum += ($a[$i] - $b[$i]) ** 2;
        }
        return sqrt($sum);
    }

    private function pca2(array $rows): array
    {
        $n = count($rows);
        if ($n === 0) {
            return [];
        }

        $means = [0.0, 0.0, 0.0];
        foreach ($rows as $row) {
            $means[0] += $row[0];
            $means[1] += $row[1];
            $means[2] += $row[2];
        }
        $means[0] /= $n;
        $means[1] /= $n;
        $means[2] /= $n;

        $centered = [];
        foreach ($rows as $row) {
            $centered[] = [
                $row[0] - $means[0],
                $row[1] - $means[1],
                $row[2] - $means[2],
            ];
        }

        $cov = [[0.0, 0.0, 0.0], [0.0, 0.0, 0.0], [0.0, 0.0, 0.0]];
        foreach ($centered as $row) {
            for ($i = 0; $i < 3; $i++) {
                for ($j = 0; $j < 3; $j++) {
                    $cov[$i][$j] += $row[$i] * $row[$j];
                }
            }
        }

        $den = max(1, $n - 1);
        for ($i = 0; $i < 3; $i++) {
            for ($j = 0; $j < 3; $j++) {
                $cov[$i][$j] /= $den;
            }
        }

        $v1 = $this->powerIteration($cov, [1.0, 1.0, 1.0], 40);
        $eig1 = $this->rayleighQuotient($cov, $v1);

        $cov2 = $cov;
        for ($i = 0; $i < 3; $i++) {
            for ($j = 0; $j < 3; $j++) {
                $cov2[$i][$j] -= $eig1 * $v1[$i] * $v1[$j];
            }
        }

        $v2 = $this->powerIteration($cov2, [1.0, -1.0, 0.5], 40);

        $points = [];
        foreach ($centered as $row) {
            $points[] = [
                $this->dot($row, $v1),
                $this->dot($row, $v2),
            ];
        }

        return $points;
    }

    private function powerIteration(array $matrix, array $start, int $iter): array
    {
        $v = $this->normalize($start);
        for ($k = 0; $k < $iter; $k++) {
            $w = [
                $matrix[0][0] * $v[0] + $matrix[0][1] * $v[1] + $matrix[0][2] * $v[2],
                $matrix[1][0] * $v[0] + $matrix[1][1] * $v[1] + $matrix[1][2] * $v[2],
                $matrix[2][0] * $v[0] + $matrix[2][1] * $v[1] + $matrix[2][2] * $v[2],
            ];
            $v = $this->normalize($w);
        }

        return $v;
    }

    private function rayleighQuotient(array $matrix, array $v): float
    {
        $mv = [
            $matrix[0][0] * $v[0] + $matrix[0][1] * $v[1] + $matrix[0][2] * $v[2],
            $matrix[1][0] * $v[0] + $matrix[1][1] * $v[1] + $matrix[1][2] * $v[2],
            $matrix[2][0] * $v[0] + $matrix[2][1] * $v[1] + $matrix[2][2] * $v[2],
        ];
        return $this->dot($v, $mv);
    }

    private function normalize(array $vector): array
    {
        $norm = sqrt(($vector[0] ** 2) + ($vector[1] ** 2) + ($vector[2] ** 2));
        if ($norm == 0.0) {
            return [1.0, 0.0, 0.0];
        }
        return [$vector[0] / $norm, $vector[1] / $norm, $vector[2] / $norm];
    }

    private function dot(array $a, array $b): float
    {
        return ($a[0] * $b[0]) + ($a[1] * $b[1]) + ($a[2] * $b[2]);
    }

    private function approxSilhouette(array $rows, array $labels): float
    {
        $n = count($rows);
        if ($n <= 2 || count(array_unique($labels)) <= 1) {
            return 0.0;
        }

        $scoreSum = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $same = [];
            $otherByCluster = [];

            for ($j = 0; $j < $n; $j++) {
                if ($i === $j) {
                    continue;
                }

                $dist = $this->euclideanDistance($rows[$i], $rows[$j]);
                if ($labels[$i] === $labels[$j]) {
                    $same[] = $dist;
                } else {
                    $otherByCluster[$labels[$j]][] = $dist;
                }
            }

            $a = count($same) > 0 ? array_sum($same) / count($same) : 0.0;
            $b = INF;
            foreach ($otherByCluster as $distances) {
                $avg = array_sum($distances) / count($distances);
                if ($avg < $b) {
                    $b = $avg;
                }
            }

            if (!is_finite($b) || max($a, $b) == 0.0) {
                continue;
            }

            $scoreSum += ($b - $a) / max($a, $b);
        }

        return round($scoreSum / $n, 2);
    }
}
