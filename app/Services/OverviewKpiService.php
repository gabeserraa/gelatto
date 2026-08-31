<?php

namespace App\Services;

use App\Models\Point;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class OverviewKpiService
{
    private ?Collection $points = null;

    public function __construct(private PointStockService $stockService) {}

    private function points(): Collection
    {
        return $this->points ??= Point::with('movements')->get();
    }

    public function monthlyTotals(Carbon $start, Carbon $end): array
    {
        $points = $this->points();

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
            $start = now()->subMonthsNoOverflow($i)->startOfMonth();
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

    public function movementTypeBreakdown(Carbon $start, Carbon $end): array
    {
        $totals = ['reposicao' => 0.0, 'retirada' => 0.0, 'ajuste' => 0.0];

        foreach ($this->points() as $point) {
            foreach ($point->movements as $movement) {
                if (! $movement->occurred_at->between($start, $end)) {
                    continue;
                }

                $totals[$movement->type] += (float) $movement->quantity_kg;
            }
        }

        return $totals;
    }

    public function stockByPoint(): array
    {
        $breakdown = [];

        foreach ($this->points() as $point) {
            $breakdown[$point->name] = $this->stockService->currentStock($point);
        }

        return $breakdown;
    }

    public function iceDistributed(Carbon $start, Carbon $end): array
    {
        $kg = 0.0;

        foreach ($this->points() as $point) {
            foreach ($point->movements as $movement) {
                if ($movement->type === 'retirada' && $movement->occurred_at->between($start, $end)) {
                    $kg += (float) $movement->quantity_kg;
                }
            }
        }

        return ['kg' => $kg, 'tons' => round($kg / 1000, 2)];
    }

    public function consumptionByPointType(Carbon $start, Carbon $end): array
    {
        $breakdown = [];

        foreach ($this->points() as $point) {
            foreach ($point->movements as $movement) {
                if ($movement->type === 'retirada' && $movement->occurred_at->between($start, $end)) {
                    $breakdown[$point->type] = ($breakdown[$point->type] ?? 0.0) + (float) $movement->quantity_kg;
                }
            }
        }

        return $breakdown;
    }

    public function regionsCovered(): int
    {
        return $this->points()
            ->where('status', 'ativo')
            ->pluck('region')
            ->filter()
            ->unique()
            ->count();
    }

    public function consumptionReport(Carbon $start, Carbon $end): array
    {
        $report = $this->points()->map(function (Point $point) use ($start, $end) {
            $movements = $point->movements->filter(
                fn ($m) => $m->type === 'retirada' && $m->occurred_at->between($start, $end)
            );

            return [
                'point' => $point,
                'kg' => (float) $movements->sum(fn ($m) => (float) $m->quantity_kg),
                'revenue' => (float) $movements->sum(fn ($m) => (float) ($m->revenue ?? 0)),
            ];
        })->sortByDesc('kg')->values();

        return $report->all();
    }

    public function fullRanking(Carbon $start, Carbon $end): array
    {
        $previousStart = $start->copy()->subMonthNoOverflow()->startOfMonth();
        $previousEnd = $previousStart->copy()->endOfMonth();

        $ranked = $this->points()->map(function (Point $point) use ($start, $end, $previousStart, $previousEnd) {
            $financials = $this->stockService->financials($point, $start, $end);
            $previousFinancials = $this->stockService->financials($point, $previousStart, $previousEnd);

            $margin = $financials['revenue'] > 0
                ? round(($financials['profit'] / $financials['revenue']) * 100, 1)
                : 0.0;

            $variationPercent = $previousFinancials['profit'] != 0
                ? round((($financials['profit'] - $previousFinancials['profit']) / abs($previousFinancials['profit'])) * 100, 1)
                : null;

            return [
                'point' => $point,
                'revenue' => $financials['revenue'],
                'cost' => $financials['cost'],
                'profit' => $financials['profit'],
                'margin' => $margin,
                'previousProfit' => $previousFinancials['profit'],
                'variationPercent' => $variationPercent,
            ];
        })->sortByDesc('profit')->values();

        return $ranked->all();
    }

    public function profitShare(Carbon $start, Carbon $end): array
    {
        $profits = $this->points()->mapWithKeys(
            fn (Point $point) => [$point->name => $this->stockService->financials($point, $start, $end)['profit']]
        );

        $total = $profits->sum();

        if ($total <= 0) {
            return $profits->map(fn () => 0.0)->all();
        }

        return $profits->map(fn ($profit) => round(($profit / $total) * 100, 1))->all();
    }

    public function nextMonthProjection(): array
    {
        $comparison = $this->monthOverMonthComparison();
        $current = $comparison['current']['profit'];
        $previous = $comparison['previous']['profit'];

        $growthRate = $previous != 0 ? ($current - $previous) / abs($previous) : 0.0;

        return [
            'projectedProfit' => round($current * (1 + $growthRate), 2),
            'growthRatePercent' => round($growthRate * 100, 1),
        ];
    }

    public function marginChange(): array
    {
        $comparison = $this->monthOverMonthComparison();

        $marginOf = fn (array $totals) => $totals['revenue'] > 0
            ? round(($totals['profit'] / $totals['revenue']) * 100, 1)
            : 0.0;

        $current = $marginOf($comparison['current']);
        $previous = $marginOf($comparison['previous']);

        return [
            'current' => $current,
            'previous' => $previous,
            'deltaPp' => round($current - $previous, 1),
        ];
    }

    public function ranking(Carbon $start, Carbon $end, int $limit = 5): array
    {
        $points = $this->points();

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
