@extends('layouts.spk')

@section('content')
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body d-flex justify-content-between align-items-center bg-light">
        <div>
            <h5 class="fw-bold mb-1">Analisis K-Means dari Data Import</h5>
            <small class="text-muted">Feature engineering: Availability, Performance, Quality dari file import OEE.</small>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <form action="{{ route('analysis.index') }}" method="GET" class="d-flex gap-2">
                <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach ($availableYears as $year)
                        <option value="{{ $year }}" {{ (int) $selectedYear === (int) $year ? 'selected' : '' }}>{{ $year }}</option>
                    @endforeach
                </select>
            </form>
            <span class="badge bg-success p-2">Silhouette: {{ number_format((float) ($silhouetteScore ?? 0), 2) }}</span>
            <span class="badge bg-secondary p-2">Source: {{ $analysisSource ?? 'unknown' }}</span>
        </div>
    </div>
</div>

@if (!empty($analysisError))
    <div class="alert alert-warning border-0 shadow-sm">Python gagal dipakai, fallback PHP aktif: {{ $analysisError }}</div>
@endif

@if (!$activeBatch)
    <div class="alert alert-warning border-0 shadow-sm">Belum ada data import OEE untuk dianalisis.</div>
@else
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body">
        <h5 class="fw-bold mb-4">Visualisasi Sebaran Klaster</h5>
        <canvas id="scatterChart" height="120"></canvas>

        <div class="mt-4 text-center d-flex justify-content-center gap-4 flex-wrap">
            <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 border border-success"><i class="fa-solid fa-circle"></i> Zona Optimal</span>
            <span class="badge bg-warning bg-opacity-10 text-warning px-3 py-2 border border-warning"><i class="fa-solid fa-circle"></i> Zona Waspada</span>
            <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-2 border border-danger"><i class="fa-solid fa-circle"></i> Zona Kritis</span>
            <span class="badge bg-dark px-3 py-2"><i class="fa-regular fa-circle text-danger fw-bold"></i> Deteksi Anomali</span>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body">
        <h5 class="fw-bold mb-3">Detail Hasil Analisis</h5>
        <div class="table-responsive">
            <table class="table table-hover table-custom">
                <thead>
                <tr>
                    <th>Proses</th>
                    <th>Bulan</th>
                    <th>Cluster</th>
                    <th>Availability</th>
                    <th>Performance</th>
                    <th>Quality</th>
                    <th>Skor OEE</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($analysisResults as $result)
                    <tr>
                        <td class="fw-bold">{{ $result['Proses'] }}</td>
                        <td>{{ $result['Bulan'] }}</td>
                        <td>
                            <span class="badge {{ $result['Status'] === 'Optimal' ? 'bg-success' : ($result['Status'] === 'Waspada' ? 'bg-warning text-dark' : 'bg-danger') }}">
                                {{ $result['Status'] }}
                            </span>
                        </td>
                        <td>{{ number_format((float) $result['Availability'] * 100, 2) }}%</td>
                        <td>{{ number_format((float) $result['Performance'] * 100, 2) }}%</td>
                        <td>{{ number_format((float) $result['Quality'] * 100, 2) }}%</td>
                        <td>{{ number_format((float) $result['OEE_Score'] * 100, 2) }}%</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">Belum ada hasil analisis.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
@if ($activeBatch)
<script>
    const results = @json($analysisResults);

    const optimalData = [];
    const waspadaData = [];
    const kritisData = [];
    const anomaliData = [];

    const hasPca = results.some((row) => row.PCA1 !== undefined && row.PCA2 !== undefined);
    const xLabel = hasPca ? 'Komponen Utama 1 (PCA1)' : 'Availability';
    const yLabel = hasPca ? 'Komponen Utama 2 (PCA2)' : 'Quality';

    results.forEach((row) => {
        const point = {
            x: hasPca
                ? Number(row.PCA1 ?? 0)
                : Number(row.Availability ?? 0),
            y: hasPca
                ? Number(row.PCA2 ?? 0)
                : Number(row.Quality ?? 0),
            machine: row.Proses ?? '-',
            month: row.Bulan ?? '-',
            performance: Number(row.Performance ?? 0),
            oee: Number(row.OEE_Score ?? 0),
            status: row.Status ?? 'Unknown',
        };

        if (point.status === 'Optimal') {
            optimalData.push(point);
        } else if (point.status === 'Waspada') {
            waspadaData.push(point);
        } else if (point.status === 'Kritis') {
            kritisData.push(point);
        } else {
            anomaliData.push(point);
        }
    });

    const ctx = document.getElementById('scatterChart').getContext('2d');
    new Chart(ctx, {
        type: 'scatter',
        data: {
            datasets: [
                { label: 'Optimal', data: optimalData, backgroundColor: '#198754', pointRadius: 8, pointHoverRadius: 10 },
                { label: 'Waspada', data: waspadaData, backgroundColor: '#ffc107', pointRadius: 8, pointHoverRadius: 10 },
                { label: 'Kritis', data: kritisData, backgroundColor: '#dc3545', pointRadius: 8, pointHoverRadius: 10 },
                {
                    label: 'Anomali Terdeteksi',
                    data: anomaliData,
                    backgroundColor: 'rgba(220, 53, 69, 0.5)',
                    borderColor: '#000',
                    borderWidth: 2,
                    borderDash: [5, 5],
                    pointRadius: 12,
                    pointHoverRadius: 15
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label(context) {
                            const raw = context.raw;
                            return `${raw.machine} (${raw.month}) | Status: ${raw.status} | P: ${raw.performance} | OEE: ${raw.oee}`;
                        }
                    }
                }
            },
            scales: {
                x: { title: { display: true, text: xLabel } },
                y: { title: { display: true, text: yLabel } }
            }
        }
    });
</script>
@endif
@endpush
