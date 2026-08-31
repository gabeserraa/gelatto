<?php

namespace App\Services;

use App\Models\Point;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PointStockService
{
    public function currentStock(Point $point): float
    {
        return (float) $point->movements->sum(fn ($movement) => $movement->signedQuantity());
    }

    public function stockPercentage(Point $point): float
    {
        $capacity = (float) $point->capacity_kg;

        if ($capacity <= 0) {
            return 0.0;
        }

        return round(($this->currentStock($point) / $capacity) * 100, 1);
    }

    public function monthlyAverageWithdrawal(Point $point, ?int $months = null): float
    {
        $months ??= (int) config('dashboards.stock_window_months', 3);
        $since = Carbon::now()->subMonths($months)->startOfDay();

        $withdrawals = $point->movements
            ->where('type', 'retirada')
            ->filter(fn ($movement) => $movement->occurred_at->greaterThanOrEqualTo($since));

        if ($withdrawals->isEmpty()) {
            return (float) ($point->initial_estimate_kg ?? 0);
        }

        $monthsWithData = max(1, $withdrawals
            ->map(fn ($movement) => $movement->occurred_at->format('Y-m'))
            ->unique()
            ->count());

        return round($withdrawals->sum(fn ($movement) => (float) $movement->quantity_kg) / $monthsWithData, 2);
    }

    public function dailyAverageWithdrawal(Point $point, ?int $months = null): float
    {
        return round($this->monthlyAverageWithdrawal($point, $months) / 30, 2);
    }

    public function daysUntilStockout(Point $point): ?float
    {
        $daily = $this->dailyAverageWithdrawal($point);

        if ($daily <= 0) {
            return null;
        }

        return round($this->currentStock($point) / $daily, 1);
    }

    public function restockUrgency(Point $point): string
    {
        $days = $this->daysUntilStockout($point);

        if ($days === null) {
            return 'ok';
        }

        if ($days < (float) config('dashboards.critical_stockout_days', 1)) {
            return 'critico';
        }

        if ($days < (float) config('dashboards.low_stock_stockout_days', 3)) {
            return 'repor_em_breve';
        }

        return 'ok';
    }

    public function needsRestockSoon(Point $point): bool
    {
        $threshold = (float) config('dashboards.low_stock_threshold_percent', 20);

        if ($this->stockPercentage($point) < $threshold) {
            return true;
        }

        return $this->currentStock($point) < $this->monthlyAverageWithdrawal($point);
    }

    public function financials(Point $point, Carbon $start, Carbon $end): array
    {
        $movements = $point->movements->filter(
            fn ($movement) => $movement->occurred_at->between($start, $end)
        );

        $revenue = (float) $movements->sum(fn ($m) => (float) ($m->revenue ?? 0));
        $cost = (float) $movements->sum(fn ($m) => (float) ($m->cost ?? 0));

        return [
            'revenue' => $revenue,
            'cost' => $cost,
            'profit' => $revenue - $cost,
        ];
    }

    public function summary(Collection $points, Carbon $start, Carbon $end): array
    {
        $totals = ['active_points' => 0, 'total_stock' => 0.0, 'revenue' => 0.0, 'cost' => 0.0, 'profit' => 0.0];

        foreach ($points as $point) {
            if ($point->status === 'ativo') {
                $totals['active_points']++;
            }

            $totals['total_stock'] += $this->currentStock($point);

            $financials = $this->financials($point, $start, $end);
            $totals['revenue'] += $financials['revenue'];
            $totals['cost'] += $financials['cost'];
            $totals['profit'] += $financials['profit'];
        }

        return $totals;
    }
}
