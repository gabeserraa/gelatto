<?php

namespace App\Http\Controllers\Dashboards;

use App\Http\Controllers\Controller;
use App\Models\Point;
use App\Services\PointStockService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class InventoryDashboardController extends Controller
{
    public function index(Request $request, PointStockService $stockService)
    {
        [$rows, $totals, $periodStart, $periodEnd] = $this->buildRows($request, $stockService);

        $perPage = 5;
        $page = (int) $request->input('page', 1);

        $paginator = new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('dashboards.inventory.index', [
            'rows' => $paginator,
            'totals' => $totals,
            'status' => $request->input('status'),
            'region' => $request->input('region'),
            'regions' => Point::query()->whereNotNull('region')->distinct()->orderBy('region')->pluck('region'),
            'month' => (int) $request->input('month', now()->month),
            'year' => (int) $request->input('year', now()->year),
        ]);
    }

    public function export(Request $request, PointStockService $stockService): Response
    {
        [$rows] = $this->buildRows($request, $stockService);

        $csv = "Ponto,Regiao,Entrada (kg),Saida (kg),Estoque atual (kg),Custo unit. (R$/kg),Valor total (R$),Margem (%),Situacao\n";

        foreach ($rows as $row) {
            $csv .= implode(',', [
                '"'.str_replace('"', '""', $row['point']->name).'"',
                '"'.str_replace('"', '""', $row['point']->region ?? '').'"',
                number_format($row['entrada'], 1, '.', ''),
                number_format($row['saida'], 1, '.', ''),
                number_format($row['currentStock'], 1, '.', ''),
                number_format($row['costPerKg'], 2, '.', ''),
                number_format($row['stockValue'], 2, '.', ''),
                number_format($row['margin'], 1, '.', ''),
                $row['urgency'],
            ])."\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="estoque.csv"',
        ]);
    }

    private function buildRows(Request $request, PointStockService $stockService): array
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
            $entrada = (float) $point->movements
                ->filter(fn ($m) => $m->type === 'reposicao' && $m->occurred_at->between($periodStart, $periodEnd))
                ->sum(fn ($m) => (float) $m->quantity_kg);

            $saida = (float) $point->movements
                ->filter(fn ($m) => $m->type === 'retirada' && $m->occurred_at->between($periodStart, $periodEnd))
                ->sum(fn ($m) => (float) $m->quantity_kg);

            $financials = $stockService->financials($point, $periodStart, $periodEnd);
            $margin = $financials['revenue'] > 0
                ? round(($financials['profit'] / $financials['revenue']) * 100, 1)
                : 0.0;

            return [
                'point' => $point,
                'entrada' => $entrada,
                'saida' => $saida,
                'currentStock' => $stockService->currentStock($point),
                'costPerKg' => $stockService->costPerKg($point, $periodStart, $periodEnd),
                'stockValue' => $stockService->stockValue($point, $periodStart, $periodEnd),
                'margin' => $margin,
                'urgency' => $stockService->restockUrgency($point),
            ];
        });

        $totals = [
            'stockTotal' => $rows->sum('currentStock'),
            'stockValueTotal' => $rows->sum('stockValue'),
            'turnoverRate' => $stockService->turnoverRate($points, $periodStart, $periodEnd),
        ];

        return [$rows, $totals, $periodStart, $periodEnd];
    }
}
