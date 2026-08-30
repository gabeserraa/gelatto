<?php

namespace App\Services;

use App\Models\Point;
use Illuminate\Support\Carbon;

class OverviewKpiService
{
    public function __construct(private PointStockService $stockService)
    {
    }

    public function monthlyTotals(Carbon $start, Carbon $end): array
    {
        $points = Point::with('movements')->get();

        return $this->stockService->summary($points, $start, $end);
    }

    public function monthOverMonthComparison(): array
    {
        $current = $this->monthlyTotals(now()->startOfMonth(), now()->endOfMonth());
        $previous = $this->monthlyTotals(
            now()->subMonthNoOverflow()->startOfMonth(),
            now()->subMonthNoOverflow()->endOfMonth()
        );

        return ['current' => $current, 'previous' => $previous];
    }

    public function last12MonthsSeries(): array
    {
        $series = [];

        for ($i = 11; $i >= 0; $i--) {
            $start = now()->subMonths($i)->startOfMonth();
            $end = $start->copy()->endOfMonth();
            $totals = $this->monthlyTotals($start, $end);

            $series[] = [
                'label' => $start->translatedFormat('M/y'),
                'revenue' => $totals['revenue'],
                'cost' => $totals['cost'],
                'profit' => $totals['profit'],
            ];
        }

        return $series;
    }

    public function ranking(Carbon $start, Carbon $end, int $limit = 5): array
    {
        $points = Point::with('movements')->get();

        $ranked = $points->map(function (Point $point) use ($start, $end) {
            $financials = $this->stockService->financials($point, $start, $end);

            return ['point' => $point, 'profit' => $financials['profit']];
        })->sortByDesc('profit')->values();

        return [
            'top' => $ranked->take($limit)->all(),
            'bottom' => $ranked->reverse()->take($limit)->values()->all(),
        ];
    }
}
