@extends('layouts.spk')

@section('content')
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="fw-bold mb-1">Visualisasi OEE</h1>

            {{-- Filter --}}
            <div>
                <form action="{{ route('dashboard.index') }}" method="GET">
                    <div>
                        <label for="filterMonth" class="mb-0 small text-muted">Filter Bulan:</label>
                        <select name="bulan" id="filterMonth" class="form-select form-select-sm" onchange="this.form.submit()">
                            @foreach ($availableMonths as $index => $month)
                                <option value="{{ $index + 1 }}" {{ request('bulan') == $index + 1 ? 'selected' : '' }}>{{ $month }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="filterYear" class="mb-0 small text-muted">Filter Tahun:</label>
                        <select name="tahun" id="filterYear" class="form-select form-select-sm" onchange="this.form.submit()">
                            @for ($year = 2020; $year <= date('Y'); $year++)
                                <option value="{{ $year }}" {{ request('tahun') == $year ? 'selected' : '' }}>{{ $year }}</option>
                            @endfor
                        </select>
                    </div>
                </form>
            </div>
        </div>
    
        <canvas id="scatterChart" height="120"></canvas>

        <div class="mt-4 text-center d-flex justify-content-center gap-4 flex-wrap">
            <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 border border-success"><i class="fa-solid fa-circle"></i> Zona Optimal</span>
            <span class="badge bg-warning bg-opacity-10 text-warning px-3 py-2 border border-warning"><i class="fa-solid fa-circle"></i> Zona Waspada</span>
            <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-2 border border-danger"><i class="fa-solid fa-circle"></i> Zona Kritis</span>
            <span class="badge bg-dark px-3 py-2"><i class="fa-regular fa-circle text-danger fw-bold"></i> Deteksi Anomali</span>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body">
        <h2 class="fw-bold mb-3">Data Proses</h2>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col">No</th>
                        <th scope="col">Proses</th>
                        <th scope="col">OEE</th>
                        <th scope="col">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logReports as $index => $logReport)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $logReport->process ?? '-' }}</td>
                            <td>{{ number_format(($logReport->oee ?? 0) * 100, 2) }}%</td>
                            <td>
                                @if ($logReport->is_anomaly)
                                    <span class="badge bg-dark">Anomali</span>
                                @elseif ($logReport->status === 2)
                                    <span class="badge bg-success">Optimal</span>
                                @elseif ($logReport->status === 1)
                                    <span class="badge bg-warning text-dark">Waspada</span>
                                @elseif ($logReport->status === 0)
                                    <span class="badge bg-danger">Kritis</span>
                                @else
                                    <span class="badge bg-secondary">Tidak diketahui</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted">Tidak ada rekomendasi penanganan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="machineListModal" tabindex="-1" aria-labelledby="machineListModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-sm rounded-4">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold mb-1" id="machineListModalLabel">Daftar Mesin</h5>
                    <small class="text-muted" id="machineListModalSubtitle">Klik titik pada chart untuk melihat detail data.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-3">
                <div id="machineListMeta" class="mb-3 small text-muted"></div>
                <div class="list-group" id="machineListContent"></div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="pointChooserModal" tabindex="-1" aria-labelledby="pointChooserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-sm rounded-4">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold mb-1" id="pointChooserModalLabel">Pilih Titik Data</h5>
                    <small class="text-muted" id="pointChooserModalSubtitle">Terdapat beberapa titik yang berdekatan. Pilih data yang ingin dilihat.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-3">
                <div id="pointChooserMeta" class="mb-3 small text-muted"></div>
                <div class="list-group" id="pointChooserList"></div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const data = @json($logReports);
    const machineListModalEl = document.getElementById('machineListModal');
    const machineListModal = machineListModalEl ? new bootstrap.Modal(machineListModalEl) : null;
    const machineListModalLabel = document.getElementById('machineListModalLabel');
    const machineListModalSubtitle = document.getElementById('machineListModalSubtitle');
    const machineListMeta = document.getElementById('machineListMeta');
    const machineListContent = document.getElementById('machineListContent');

    const pointChooserModalEl = document.getElementById('pointChooserModal');
    const pointChooserModal = pointChooserModalEl ? new bootstrap.Modal(pointChooserModalEl) : null;
    const pointChooserMeta = document.getElementById('pointChooserMeta');
    const pointChooserList = document.getElementById('pointChooserList');
    let nearbyPointCandidates = [];

    function formatMachineName(item) {
        return item.machine && item.machine !== '-' ? item.machine : 'Tidak diketahui';
    }

    function escapeHtml(text) {
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function normalizeRecommendations(rawRecommendations) {
        if (Array.isArray(rawRecommendations)) {
            return rawRecommendations
                .map((item) => (typeof item === 'string' ? item.trim() : ''))
                .filter(Boolean);
        }

        if (typeof rawRecommendations === 'string') {
            const trimmed = rawRecommendations.trim();

            if (!trimmed) {
                return [];
            }

            try {
                const parsed = JSON.parse(trimmed);
                if (Array.isArray(parsed)) {
                    return parsed
                        .map((item) => (typeof item === 'string' ? item.trim() : ''))
                        .filter(Boolean);
                }
            } catch (error) {
                // Keep as plain text recommendation when not valid JSON.
            }

            return [trimmed];
        }

        return [];
    }

    function toMachineRows(point) {
        if (!point) {
            return [];
        }

        if (Array.isArray(point.machines)) {
            return point.machines;
        }

        return [point];
    }

    function getBadgeClass(status) {
        if (status === 'Optimal') {
            return 'bg-success';
        }

        if (status === 'Waspada') {
            return 'bg-warning text-dark';
        }

        if (status === 'Kritis') {
            return 'bg-danger';
        }

        return 'bg-dark';
    }

    function renderMachineList(datasetLabel, point) {
        if (!machineListModal || !machineListModalLabel || !machineListModalSubtitle || !machineListMeta || !machineListContent) {
            return;
        }

        const machineRows = toMachineRows(point);

        machineListModalLabel.textContent = `Daftar Mesin - ${datasetLabel}`;
        machineListModalSubtitle.textContent = 'Berikut detail dari titik scatter yang dipilih.';
        machineListMeta.textContent = point
            ? `Titik: (${point.x ?? '-'}, ${point.y ?? '-'}) | Total mesin: ${machineRows.length}`
            : '';

        if (!point) {
            machineListContent.innerHTML = '<div class="alert alert-light border mb-0">Tidak ada data pada titik yang dipilih.</div>';
            machineListModal.show();
            return;
        }

        machineListContent.innerHTML = machineRows.map((machineRow, idx) => {
            const rowStatus = machineRow.status ?? datasetLabel;
            const showRecommendation = rowStatus === 'Waspada' || rowStatus === 'Kritis';
            const recommendations = normalizeRecommendations(machineRow.recommendations);
            console.log(recommendations);
            
            const fallbackRecommendation = rowStatus === 'Kritis'
                ? 'Segera lakukan inspeksi menyeluruh pada mesin, evaluasi akar masalah, dan jadwalkan tindakan korektif prioritas tinggi.'
                : 'Lakukan pemantauan intensif, cek parameter proses, dan siapkan tindakan preventif sebelum kondisi memburuk.';

            const recommendationSection = showRecommendation
                ? `
                    <div class="mt-2 pt-2 border-top">
                        <div class="fw-semibold mb-2">Rekomendasi Penanganan</div>
                        <ul class="mb-0 ps-3 small">
                            ${(recommendations.length ? recommendations : [fallbackRecommendation])
                                .map((recommendation) => `<li>${escapeHtml(recommendation)}</li>`)
                                .join('')}
                        </ul>
                    </div>
                `
                : '';

            return `
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <div class="fw-semibold">${idx + 1}. ${formatMachineName(machineRow)}</div>
                            <div class="small text-muted">Bulan: ${machineRow.month ?? '-'} | Availability: ${machineRow.availability ?? '-'} | Performance: ${machineRow.performance ?? '-'} | Quality: ${machineRow.quality ?? '-'} | OEE: ${machineRow.oee ?? '-'}% | Status: ${rowStatus}</div>
                        </div>
                        <span class="badge ${getBadgeClass(rowStatus)}">${rowStatus}</span>
                    </div>
                    ${recommendationSection}
                </div>
            `;
        }).join('');

        machineListModal.show();
    }

    function findNearbyPoints(chart, element, thresholdPx = 14) {
        const { datasetIndex, index } = element;
        const anchorMeta = chart.getDatasetMeta(datasetIndex);
        const anchorPoint = anchorMeta?.data?.[index];

        if (!anchorPoint) {
            return [];
        }

        const anchor = anchorPoint.getProps(['x', 'y'], true);
        const candidates = [];

        chart.data.datasets.forEach((dataset, dsIndex) => {
            const meta = chart.getDatasetMeta(dsIndex);
            if (!meta?.data?.length) {
                return;
            }

            meta.data.forEach((pt, ptIndex) => {
                const position = pt.getProps(['x', 'y'], true);
                const distance = Math.hypot(position.x - anchor.x, position.y - anchor.y);

                if (distance <= thresholdPx) {
                    candidates.push({
                        datasetIndex: dsIndex,
                        index: ptIndex,
                        datasetLabel: dataset.label ?? 'Data',
                        point: dataset.data[ptIndex],
                        distance
                    });
                }
            });
        });

        return candidates.sort((a, b) => a.distance - b.distance);
    }

    function renderPointChooser(candidates, anchorPoint) {
        if (!pointChooserModal || !pointChooserMeta || !pointChooserList) {
            return;
        }

        nearbyPointCandidates = candidates;
        pointChooserMeta.textContent = `Titik sekitar (${anchorPoint.x ?? '-'}, ${anchorPoint.y ?? '-'}) | Kandidat: ${candidates.length}`;

        pointChooserList.innerHTML = candidates.map((candidate, idx) => {
            console.log(candidate);
            
            
            const machineName = formatMachineName(candidate.point);
            const status = candidate.point?.status ?? candidate.datasetLabel;
            const showRecommendation = status === 'Waspada' || status === 'Kritis';

            if(candidate.point.is_anomaly) {
                return `
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div>
                                <div class="fw-semibold">${idx + 1}. ${machineName}</div>
                                <div class="small text-muted">Bulan: ${candidate.point?.month ?? '-'} | OEE: ${candidate.point?.oee ?? '-'}% | Jarak: ${candidate.distance.toFixed(2)} px</div>
                            </div>
                            <span class="badge bg-dark">Anomali</span>
                        </div>
                        <div class="mt-2 pt-2 border-top">
                            <div class="fw-semibold mb-2">Catatan</div>
                            <ul class="mb-0 ps-3 small">
                                <li>Data mesin ${machineName} terdeteksi sebagai anomali. Harap cek kembali data mesin yang diinput.</li>
                            </ul>
                        </div>
                    </div>
                `;
            }
            
            const recommendations = candidate.point?.recommendations;
            const fallbackRecommendation = status === 'Kritis'
                ? 'Segera lakukan inspeksi menyeluruh pada mesin, evaluasi akar masalah, dan jadwalkan tindakan korektif prioritas tinggi.'
                : 'Lakukan pemantauan intensif, cek parameter proses, dan siapkan tindakan preventif sebelum kondisi memburuk.';

            const recommendationSection = showRecommendation
                ? `
                    <div class="mt-2 pt-2 border-top">
                        <div class="fw-semibold mb-2">Rekomendasi Penanganan</div>
                        <ul class="mb-0 ps-3 small">
                            ${(recommendations.length ? recommendations : [fallbackRecommendation])
                                .map((recommendation) => `<li>${recommendation.recommendation}</li>`)
                                .join('')}
                        </ul>
                    </div>
                `
                : '';

            return `
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <div class="fw-semibold">${idx + 1}. ${machineName}</div>
                            <div class="small text-muted">Bulan: ${candidate.point?.month ?? '-'} | Availability: ${candidate.point?.availability ?? '-'} | Performance: ${candidate.point?.performance ?? '-'} | Quality: ${candidate.point?.quality ?? '-'} | OEE: ${candidate.point?.oee ?? '-'}%</div>
                        </div>
                        <span class="badge ${getBadgeClass(status)}">${status}</span>
                    </div>
                    ${recommendationSection}
                </div>
            `;
        }).join('');

        pointChooserModal.show();
    }
    
    const labeledData = data.reduce((acc, item) => {
        if (item.is_anomaly) {
            acc.anomali.push({
                x: parseFloat(item.x_axis).toFixed(2),
                y: parseFloat(item.y_axis).toFixed(2),
                machine: item.process ?? '-',
                month: item.bulan,
                status: 'Anomali',
                performance: (parseFloat(item.performance) * 100).toFixed(2),
                availability: (parseFloat(item.availability) * 100).toFixed(2),
                quality: (parseFloat(item.quality) * 100).toFixed(2),
                oee: (parseFloat(item.oee) * 100).toFixed(2),
                is_anomaly: item.is_anomaly ?? false,
                recommendations: item.recommendations ?? [],
            });
        } else if (item.status === 2) {
            acc.optimal.push({
                x: parseFloat(item.x_axis).toFixed(2),
                y: parseFloat(item.y_axis).toFixed(2),
                machine: item.process ?? '-',
                month: item.bulan,
                status: 'Optimal',
                performance: (parseFloat(item.performance) * 100).toFixed(2),
                availability: (parseFloat(item.availability) * 100).toFixed(2),
                quality: (parseFloat(item.quality) * 100).toFixed(2),
                oee: (parseFloat(item.oee) * 100).toFixed(2),
                is_anomaly: item.is_anomaly ?? false,
                recommendations: item.recommendations ?? [],
            });
        } else if (item.status === 1) {
            acc.waspada.push({
                x: parseFloat(item.x_axis).toFixed(2),
                y: parseFloat(item.y_axis).toFixed(2),
                machine: item.process ?? '-',
                month: item.bulan,
                status: 'Waspada',
                performance: (parseFloat(item.performance) * 100).toFixed(2),
                availability: (parseFloat(item.availability) * 100).toFixed(2),
                quality: (parseFloat(item.quality) * 100).toFixed(2),
                oee: (parseFloat(item.oee) * 100).toFixed(2),
                is_anomaly: item.is_anomaly ?? false,
                recommendations: item.recommendations ?? [],
            });
        } else if (item.status === 0) {
            acc.kritis.push({
                x: parseFloat(item.x_axis).toFixed(2),
                y: parseFloat(item.y_axis).toFixed(2),
                machine: item.process ?? '-',
                month: item.bulan,
                status: 'Kritis',
                performance: (parseFloat(item.performance) * 100).toFixed(2),
                availability: (parseFloat(item.availability) * 100).toFixed(2),
                quality: (parseFloat(item.quality) * 100).toFixed(2),
                oee: (parseFloat(item.oee) * 100).toFixed(2),
                is_anomaly: item.is_anomaly ?? false,
                recommendations: item.recommendations ?? [],
            });
        }

        return acc;
    }, {
        optimal: [],
        waspada: [],
        kritis: [],
        anomali: []
    });

    console.log(labeledData.anomali);
    

    const ctx = document.getElementById('scatterChart').getContext('2d');
    new Chart(ctx, {
        type: 'scatter',
        data: {
            datasets: [
                { label: 'Optimal', data: labeledData.optimal, backgroundColor: '#198754', pointRadius: 8, pointHoverRadius: 10 },
                { label: 'Waspada', data: labeledData.waspada, backgroundColor: '#ffc107', pointRadius: 8, pointHoverRadius: 10 },
                { label: 'Kritis', data: labeledData.kritis, backgroundColor: '#dc3545', pointRadius: 8, pointHoverRadius: 10 },
                {
                    label: 'Anomali Terdeteksi',
                    data: labeledData.anomali,
                    backgroundColor: 'rgba(220, 53, 69, 0.5)',
                    borderColor: '#000',
                    borderWidth: 2,
                    borderDash: [5, 5],
                    pointRadius: 8,
                    pointHoverRadius: 10
                }
            ]
        },
        options: {
            responsive: true,
            onClick(event, elements, chart) {
                if (!elements.length) {
                    return;
                }

                const { datasetIndex, index } = elements[0];
                const dataset = chart.data.datasets[datasetIndex];

                const selectedPoint = dataset.data[index];
                const datasetLabel = dataset.label ?? 'Data';
                const nearbyPoints = findNearbyPoints(chart, elements[0]);

                if (nearbyPoints.length > 1) {
                    renderPointChooser(nearbyPoints, selectedPoint);
                    return;
                }

                renderMachineList(datasetLabel, selectedPoint);
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        title(context) {
                            const raw = context?.[0]?.raw;
                            if (!raw) {
                                return 'Detail Titik';
                            }

                            return `Titik (${raw.x ?? '-'}, ${raw.y ?? '-'})`;
                        },
                        label(context) {
                            const raw = context.raw;
                            const rows = Array.isArray(raw?.machines) ? raw.machines : (raw ? [raw] : []);

                            if (!rows.length) {
                                return 'Tidak ada data mesin';
                            }

                            const previewNames = rows
                                .slice(0, 3)
                                .map((row) => row.machine && row.machine !== '-' ? row.machine : 'Tidak diketahui')
                                .join(', ');
                            const hasMore = rows.length > 3 ? ` +${rows.length - 3} lainnya` : '';
                            const status = rows[0]?.status ?? raw?.status ?? context.dataset.label ?? '-';

                            return [
                                `Status: ${status}`,
                                `Total Mesin: ${rows.length}`,
                                `Mesin: ${previewNames}${hasMore}`
                            ];
                        }
                    }
                }
            },
            scales: {
                x: { title: { display: true, text: "Availability" } },
                y: { title: { display: true, text: "Quality & Performance" } }
            }
        }
    });
</script>
@endpush
