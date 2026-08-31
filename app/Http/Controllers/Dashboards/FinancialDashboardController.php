<?php

namespace App\Http\Controllers\Dashboards;

use App\Http\Controllers\Controller;
use App\Services\OverviewKpiService;

class FinancialDashboardController extends Controller
{
    public function index(OverviewKpiService $kpiService)
    {
        $periodStart = now()->startOfMonth();
        $periodEnd = now()->endOfMonth();

        $series = collect($kpiService->last12MonthsSeries())->slice(-6)->values()->all();
        $ranking = $kpiService->fullRanking($periodStart, $periodEnd);
        $profitShare = $kpiService->profitShare($periodStart, $periodEnd);
        $projection = $kpiService->nextMonthProjection();
        $marginChange = $kpiService->marginChange();
        $comparison = $kpiService->monthOverMonthComparison();

        return view('dashboards.financial.index', [
            'series' => $series,
            'ranking' => $ranking,
            'profitShare' => $profitShare,
            'projection' => $projection,
            'marginChange' => $marginChange,
            'comparison' => $comparison,
            'currentMonthLabel' => now()->translatedFormat('F'),
            'nextMonthLabel' => now()->addMonthNoOverflow()->translatedFormat('F'),
        ]);
    }
}
