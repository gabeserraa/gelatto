<?php

namespace App\Http\Controllers\Dashboards;

use App\Http\Controllers\Controller;
use App\Models\Point;
use App\Services\PointStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PointsDashboardController extends Controller
{
    public function index(Request $request, PointStockService $stockService)
    {
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);
        $status = $request->input('status');
        $region = $request->input('region');

        $periodStart = Carbon::create($year, $month, 1)->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();

        $points = Point::with('movements')
            ->withMax('movements', 'occurred_at')
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($region, fn ($query) => $query->where('region', $region))
            ->orderByDesc('movements_max_occurred_at')
            ->get();

        $rows = $points->map(function (Point $point) use ($stockService, $periodStart, $periodEnd) {
            $financials = $stockService->financials($point, $periodStart, $periodEnd);

            $lastRestock = $point->movements
                ->where('type', 'reposicao')
                ->sortByDesc('occurred_at')
                ->first();

            return [
                'point' => $point,
                'currentStock' => $stockService->currentStock($point),
                'stockPercentage' => $stockService->stockPercentage($point),
                'monthlyAverage' => $stockService->monthlyAverageWithdrawal($point),
                'dailyAverage' => $stockService->dailyAverageWithdrawal($point),
                'lastRestockAt' => $lastRestock?->occurred_at,
                'daysUntilStockout' => $stockService->daysUntilStockout($point),
                'profit' => $financials['profit'],
                'urgency' => $stockService->restockUrgency($point),
            ];
        });

        $summary = $stockService->summary($points, $periodStart, $periodEnd);
        $urgencyCounts = [
            'ok' => $rows->where('urgency', 'ok')->count(),
            'repor_em_breve' => $rows->where('urgency', 'repor_em_breve')->count(),
            'critico' => $rows->where('urgency', 'critico')->count(),
        ];

        $regions = Point::query()->whereNotNull('region')->distinct()->orderBy('region')->pluck('region');

        return view('dashboards.points.index', [
            'rows' => $rows,
            'summary' => $summary,
            'urgencyCounts' => $urgencyCounts,
            'status' => $status,
            'region' => $region,
            'regions' => $regions,
            'month' => $month,
            'year' => $year,
        ]);
    }
}
