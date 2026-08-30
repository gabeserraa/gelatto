<?php

namespace App\Http\Controllers\Dashboards;

use App\Http\Controllers\Controller;
use App\Services\OverviewKpiService;

class OverviewDashboardController extends Controller
{
    public function index(OverviewKpiService $kpiService)
    {
        $comparison = $kpiService->monthOverMonthComparison();
        $series = $kpiService->last12MonthsSeries();
        $ranking = $kpiService->ranking(now()->startOfMonth(), now()->endOfMonth());

        $current = $comparison['current'];
        $margin = $current['revenue'] > 0
            ? round(($current['profit'] / $current['revenue']) * 100, 1)
            : 0.0;

        return view('dashboards.overview.index', [
            'comparison' => $comparison,
            'series' => $series,
            'ranking' => $ranking,
            'margin' => $margin,
        ]);
    }
}
