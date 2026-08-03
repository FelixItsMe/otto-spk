<?php

namespace App\Http\Controllers;

use App\Models\LogReport;
use App\Models\Machine;
use App\Models\TreatmentRecommendation;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $bulanBerjalan = $request->query('bulan', 1);
        $tahunBerjalan = $request->query('tahun', 2025);

        $logReports = LogReport::query()
            ->with(['recommendations'])
            ->where('bulan', (int) $bulanBerjalan)
            ->where('tahun', (int) $tahunBerjalan)
            ->get();

        $machines = Machine::get(['id', 'code']);

        $availableMonths = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];

        return view('spk.dashboard', compact('logReports', 'bulanBerjalan', 'tahunBerjalan', 'machines', 'availableMonths'));
    }

    public function getRecommendation($id): \Illuminate\Http\JsonResponse
    {
        $recommendations = TreatmentRecommendation::query()
            ->where('log_report_id', $id)
            ->get();

        return response()->json($recommendations);
    }
}
