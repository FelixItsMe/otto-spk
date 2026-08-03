<?php

namespace App\Http\Controllers;

use App\Models\OeeReport;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        $reports = OeeReport::query()
            ->with('machine')
            ->latest('report_date')
            ->paginate(20)
            ->withQueryString();

        return view('spk.report', [
            'pageTitle' => 'Laporan Keputusan Manajemen',
            'pageSubtitle' => 'Rekap nilai OEE, status klaster, dan anomali',
            'reports' => $reports,
        ]);
    }
}
