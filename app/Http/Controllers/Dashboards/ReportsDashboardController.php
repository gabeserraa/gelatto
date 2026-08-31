<?php

namespace App\Http\Controllers\Dashboards;

use App\Http\Controllers\Controller;
use App\Models\PointMovement;
use App\Services\OverviewKpiService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class ReportsDashboardController extends Controller
{
    public function index(Request $request)
    {
        return view('dashboards.reports.index', [
            'start' => $request->input('start', now()->startOfMonth()->format('Y-m-d')),
            'end' => $request->input('end', now()->endOfMonth()->format('Y-m-d')),
        ]);
    }

    public function exportFinancial(Request $request, OverviewKpiService $kpiService): Response
    {
        [$start, $end] = $this->period($request);

        $csv = "Ponto,Receita,Custo,Lucro,Margem (%),Variacao vs mes anterior (%)\n";

        foreach ($kpiService->fullRanking($start, $end) as $row) {
            $csv .= implode(',', [
                '"'.str_replace('"', '""', $row['point']->name).'"',
                number_format($row['revenue'], 2, '.', ''),
                number_format($row['cost'], 2, '.', ''),
                number_format($row['profit'], 2, '.', ''),
                number_format($row['margin'], 1, '.', ''),
                $row['variationPercent'] === null ? '' : number_format($row['variationPercent'], 1, '.', ''),
            ])."\n";
        }

        return $this->csvResponse($csv, 'relatorio-financeiro.csv');
    }

    public function exportConsumption(Request $request, OverviewKpiService $kpiService): Response
    {
        [$start, $end] = $this->period($request);

        $csv = "Ponto,Consumo (kg),Receita (R$)\n";

        foreach ($kpiService->consumptionReport($start, $end) as $row) {
            $csv .= implode(',', [
                '"'.str_replace('"', '""', $row['point']->name).'"',
                number_format($row['kg'], 1, '.', ''),
                number_format($row['revenue'], 2, '.', ''),
            ])."\n";
        }

        return $this->csvResponse($csv, 'relatorio-consumo-por-ponto.csv');
    }

    public function exportReplenishments(Request $request): Response
    {
        [$start, $end] = $this->period($request);

        $movements = PointMovement::with('point')
            ->where('type', 'reposicao')
            ->whereBetween('occurred_at', [$start, $end])
            ->orderBy('occurred_at')
            ->get();

        $csv = "Data,Ponto,Regiao,Quantidade (kg),Custo (R$)\n";

        foreach ($movements as $movement) {
            $csv .= implode(',', [
                $movement->occurred_at->format('d/m/Y'),
                '"'.str_replace('"', '""', $movement->point->name).'"',
                '"'.str_replace('"', '""', $movement->point->region ?? '').'"',
                number_format((float) $movement->quantity_kg, 1, '.', ''),
                $movement->cost === null ? '' : number_format((float) $movement->cost, 2, '.', ''),
            ])."\n";
        }

        return $this->csvResponse($csv, 'relatorio-reposicoes.csv');
    }

    private function period(Request $request): array
    {
        $start = Carbon::parse($request->input('start', now()->startOfMonth()->format('Y-m-d')))->startOfDay();
        $end = Carbon::parse($request->input('end', now()->endOfMonth()->format('Y-m-d')))->endOfDay();

        return [$start, $end];
    }

    private function csvResponse(string $csv, string $filename): Response
    {
        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
