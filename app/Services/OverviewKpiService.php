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
