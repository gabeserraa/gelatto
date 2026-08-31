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
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($region, fn ($query) => $query->where('region', $region))
            ->orderBy('name')
            ->get();

        $rows = $points->map(function (Point $point) use ($stockService, $periodStart, $periodEnd) {
            $financials = $stockService->financials($point, $periodStart, $periodEnd);

            return [
                'point' => $point,
                'currentStock' => $stockService->currentStock($point),
                'stockPercentage' => $stockService->stockPercentage($point),
                'monthlyAverage' => $stockService->monthlyAverageWithdrawal($point),
                'daysUntilStockout' => $stockService->daysUntilStockout($point),
                'profit' => $financials['profit'],
                'urgency' => $stockService->restockUrgency($point),
            ];
        });

        $summary = $stockService->summary($points, $periodStart, $periodEnd);

        $regions = Point::query()->whereNotNull('region')->distinct()->orderBy('region')->pluck('region');

        return view('dashboards.points.index', [
            'rows' => $rows,
            'summary' => $summary,
            'status' => $status,
            'region' => $region,
            'regions' => $regions,
            'month' => $month,
            'year' => $year,
        ]);
    }
}
