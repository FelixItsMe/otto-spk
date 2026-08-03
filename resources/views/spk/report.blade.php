@extends('layouts.spk')

@section('content')
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold">Tabel Laporan Keputusan SPK</h5>
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-custom">
                <thead>
                <tr>
                    <th>Kode Mesin</th>
                    <th>Availability</th>
                    <th>Performance</th>
                    <th>Quality</th>
                    <th>Skor OEE</th>
                    <th>Status Klaster</th>
                    <th>Anomali Algoritma</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($reports as $report)
                    <tr class="{{ $report->is_anomaly ? 'table-danger' : '' }}">
                        <td class="fw-bold">{{ $report->machine?->code ?? '-' }}</td>
                        <td>{{ number_format($report->availability, 2) }}%</td>
                        <td>{{ number_format($report->performance, 2) }}%</td>
                        <td>{{ number_format($report->quality, 2) }}%</td>
                        <td>{{ number_format($report->oee_score, 2) }}%</td>
                        <td>
                            <span class="badge {{ $report->cluster_status === 'Optimal' ? 'bg-success' : ($report->cluster_status === 'Waspada' ? 'bg-warning text-dark' : 'bg-danger') }}">
                                {{ $report->cluster_status }}
                            </span>
                        </td>
                        <td>
                            @if ($report->is_anomaly)
                                <span class="badge-anomali"><i class="fa-solid fa-triangle-exclamation"></i> Ya (Outlier)</span>
                            @else
                                <span class="text-success fw-bold"><i class="fa-solid fa-check"></i> Normal</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">Belum ada data laporan.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $reports->links() }}
        </div>
    </div>
</div>
@endsection
