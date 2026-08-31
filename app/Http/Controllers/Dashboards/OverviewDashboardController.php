<?php

namespace App\Http\Controllers\Dashboards;

use App\Http\Controllers\Controller;
use App\Models\Point;
use App\Services\OverviewKpiService;
use App\Services\PointStockService;

class OverviewDashboardController extends Controller
{
    public function index(OverviewKpiService $kpiService, PointStockService $stockService)
    {
        $comparison = $kpiService->monthOverMonthComparison();
        $series = $kpiService->last12MonthsSeries();
        $ranking = $kpiService->ranking(now()->startOfMonth(), now()->endOfMonth());
        $movementTypes = $kpiService->movementTypeBreakdown(now()->startOfMonth(), now()->endOfMonth());
        $stockByPoint = $kpiService->stockByPoint();
        $consumptionByPointType = $kpiService->consumptionByPointType(now()->startOfMonth(), now()->endOfMonth());
        $regionsCovered = $kpiService->regionsCovered();

        $ice = [
            'current' => $kpiService->iceDistributed(now()->startOfMonth(), now()->endOfMonth()),
            'previous' => $kpiService->iceDistributed(
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth()
            ),
        ];

        $current = $comparison['current'];
        $margin = $current['revenue'] > 0
            ? round(($current['profit'] / $current['revenue']) * 100, 1)
            : 0.0;

        $restockList = Point::with('movements')
            ->where('status', 'ativo')
            ->get()
            ->map(fn (Point $point) => [
                'point' => $point,
                'urgency' => $stockService->restockUrgency($point),
                'daysUntilStockout' => $stockService->daysUntilStockout($point),
                'currentStock' => $stockService->currentStock($point),
            ])
            ->filter(fn (array $row) => $row['urgency'] !== 'ok')
            ->sortBy('daysUntilStockout')
            ->take(5)
            ->values();

        return view('dashboards.overview.index', [
            'comparison' => $comparison,
            'series' => $series,
            'ranking' => $ranking,
            'margin' => $margin,
            'movementTypes' => $movementTypes,
            'stockByPoint' => $stockByPoint,
            'consumptionByPointType' => $consumptionByPointType,
            'regionsCovered' => $regionsCovered,
            'ice' => $ice,
            'restockList' => $restockList,
        ]);
    }
}
