<?php

namespace Tests\Feature;

use App\Models\Point;
use App\Models\PointMovement;
use App\Services\OverviewKpiService;
use App\Services\PointStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OverviewKpiServiceTest extends TestCase
{
    use RefreshDatabase;

    private OverviewKpiService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OverviewKpiService(new PointStockService);
    }

    public function test_last_12_months_series_has_12_entries(): void
    {
        $series = $this->service->last12MonthsSeries();

        $this->assertCount(12, $series);
        $this->assertArrayHasKey('revenue', $series[0]);
        $this->assertArrayHasKey('cost', $series[0]);
        $this->assertArrayHasKey('profit', $series[0]);
    }

    public function test_last_12_months_series_has_no_duplicate_or_skipped_months_across_a_day_31_boundary(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 8, 31));

        $series = $this->service->last12MonthsSeries();

        $labels = collect($series)->pluck('label')->all();

        $this->assertSame([
            'set/25', 'out/25', 'nov/25', 'dez/25', 'jan/26', 'fev/26',
            'mar/26', 'abr/26', 'mai/26', 'jun/26', 'jul/26', 'ago/26',
        ], $labels);

        Carbon::setTestNow();
    }

    public function test_ranking_orders_points_by_profit_descending(): void
    {
        $high = Point::factory()->create(['name' => 'Ponto Lucrativo']);
        $low = Point::factory()->create(['name' => 'Ponto Fraco']);

        PointMovement::factory()->create([
            'point_id' => $high->id, 'type' => 'retirada', 'quantity_kg' => 10,
            'revenue' => 500, 'cost' => null, 'occurred_at' => now(),
        ]);
        PointMovement::factory()->create([
            'point_id' => $low->id, 'type' => 'retirada', 'quantity_kg' => 10,
            'revenue' => 10, 'cost' => null, 'occurred_at' => now(),
        ]);

        $ranking = $this->service->ranking(now()->startOfMonth(), now()->endOfMonth());

        $this->assertSame('Ponto Lucrativo', $ranking['top'][0]['point']->name);
    }

    public function test_movement_type_breakdown_sums_quantity_kg_by_type_within_period(): void
    {
        $point = Point::factory()->create();

        PointMovement::factory()->create([
            'point_id' => $point->id, 'type' => 'reposicao', 'quantity_kg' => 40, 'occurred_at' => now(),
        ]);
        PointMovement::factory()->create([
            'point_id' => $point->id, 'type' => 'retirada', 'quantity_kg' => 15, 'occurred_at' => now(),
        ]);
        PointMovement::factory()->create([
            'point_id' => $point->id, 'type' => 'retirada', 'quantity_kg' => 5, 'occurred_at' => now(),
        ]);
        PointMovement::factory()->create([
            'point_id' => $point->id, 'type' => 'ajuste', 'quantity_kg' => 3,
            'adjustment_direction' => 'decrease', 'occurred_at' => now(),
        ]);
        PointMovement::factory()->create([
            'point_id' => $point->id, 'type' => 'reposicao', 'quantity_kg' => 999, 'occurred_at' => now()->subMonths(2),
        ]);

        $breakdown = $this->service->movementTypeBreakdown(now()->startOfMonth(), now()->endOfMonth());

        $this->assertSame(40.0, $breakdown['reposicao']);
        $this->assertSame(20.0, $breakdown['retirada']);
        $this->assertSame(3.0, $breakdown['ajuste']);
    }

    public function test_stock_by_point_returns_current_stock_for_every_point(): void
    {
        $a = Point::factory()->create(['name' => 'Ponto A']);
        $b = Point::factory()->create(['name' => 'Ponto B']);

        PointMovement::factory()->create(['point_id' => $a->id, 'type' => 'reposicao', 'quantity_kg' => 30]);
        PointMovement::factory()->create(['point_id' => $b->id, 'type' => 'reposicao', 'quantity_kg' => 12]);

        $breakdown = $this->service->stockByPoint();

        $this->assertSame(30.0, $breakdown['Ponto A']);
        $this->assertSame(12.0, $breakdown['Ponto B']);
    }

    public function test_ice_distributed_sums_retirada_quantity_across_points_within_period(): void
    {
        $a = Point::factory()->create();
        $b = Point::factory()->create();

        PointMovement::factory()->create([
            'point_id' => $a->id, 'type' => 'retirada', 'quantity_kg' => 600, 'occurred_at' => Carbon::create(2026, 3, 10),
        ]);
        PointMovement::factory()->create([
            'point_id' => $b->id, 'type' => 'retirada', 'quantity_kg' => 400, 'occurred_at' => Carbon::create(2026, 3, 15),
        ]);
        PointMovement::factory()->create([
            'point_id' => $a->id, 'type' => 'retirada', 'quantity_kg' => 999, 'occurred_at' => Carbon::create(2026, 4, 1),
        ]);
        PointMovement::factory()->create([
            'point_id' => $a->id, 'type' => 'reposicao', 'quantity_kg' => 500, 'occurred_at' => Carbon::create(2026, 3, 10),
        ]);

        $result = $this->service->iceDistributed(Carbon::create(2026, 3, 1), Carbon::create(2026, 3, 31));

        $this->assertSame(1000.0, $result['kg']);
        $this->assertSame(1.0, $result['tons']);
    }

    public function test_consumption_by_point_type_sums_retirada_quantity_grouped_by_point_type(): void
    {
        $balada = Point::factory()->create(['type' => 'Balada']);
        $mercado = Point::factory()->create(['type' => 'Mercado']);

        PointMovement::factory()->create([
            'point_id' => $balada->id, 'type' => 'retirada', 'quantity_kg' => 70, 'occurred_at' => now(),
        ]);
        PointMovement::factory()->create([
            'point_id' => $mercado->id, 'type' => 'retirada', 'quantity_kg' => 30, 'occurred_at' => now(),
        ]);

        $breakdown = $this->service->consumptionByPointType(now()->startOfMonth(), now()->endOfMonth());

        $this->assertSame(70.0, $breakdown['Balada']);
        $this->assertSame(30.0, $breakdown['Mercado']);
    }

    public function test_regions_covered_counts_distinct_regions_among_active_points(): void
    {
        Point::factory()->create(['status' => 'ativo', 'region' => 'Centro']);
        Point::factory()->create(['status' => 'ativo', 'region' => 'Centro']);
        Point::factory()->create(['status' => 'ativo', 'region' => 'Zona Sul']);
        Point::factory()->create(['status' => 'inativo', 'region' => 'Zona Norte']);

        $this->assertSame(2, $this->service->regionsCovered());
    }

    public function test_full_ranking_includes_every_point_with_margin_and_variation(): void
    {
        $high = Point::factory()->create(['name' => 'Arena Events']);
        $low = Point::factory()->create(['name' => 'Bar da Esquina']);

        PointMovement::factory()->create([
            'point_id' => $high->id, 'type' => 'retirada', 'quantity_kg' => 100,
            'revenue' => 1000, 'cost' => 300, 'occurred_at' => now(),
        ]);
        PointMovement::factory()->create([
            'point_id' => $high->id, 'type' => 'retirada', 'quantity_kg' => 50,
            'revenue' => 500, 'cost' => 100, 'occurred_at' => now()->subMonthNoOverflow(),
        ]);
        PointMovement::factory()->create([
            'point_id' => $low->id, 'type' => 'retirada', 'quantity_kg' => 10,
            'revenue' => 100, 'cost' => 80, 'occurred_at' => now(),
        ]);

        $ranking = $this->service->fullRanking(now()->startOfMonth(), now()->endOfMonth());

        $this->assertCount(2, $ranking);
        $this->assertSame('Arena Events', $ranking[0]['point']->name);
        $this->assertSame(700.0, $ranking[0]['profit']);
        $this->assertSame(70.0, $ranking[0]['margin']);
        $this->assertSame(400.0, $ranking[0]['previousProfit']);
        $this->assertSame(75.0, $ranking[0]['variationPercent']);
        $this->assertSame('Bar da Esquina', $ranking[1]['point']->name);
        $this->assertNull($ranking[1]['variationPercent']);
    }

    public function test_profit_share_returns_percentage_of_total_profit_per_point(): void
    {
        $a = Point::factory()->create(['name' => 'Ponto A']);
        $b = Point::factory()->create(['name' => 'Ponto B']);

        PointMovement::factory()->create([
            'point_id' => $a->id, 'type' => 'retirada', 'quantity_kg' => 10,
            'revenue' => 750, 'cost' => 0, 'occurred_at' => now(),
        ]);
        PointMovement::factory()->create([
            'point_id' => $b->id, 'type' => 'retirada', 'quantity_kg' => 10,
            'revenue' => 250, 'cost' => 0, 'occurred_at' => now(),
        ]);

        $share = $this->service->profitShare(now()->startOfMonth(), now()->endOfMonth());

        $this->assertSame(75.0, $share['Ponto A']);
        $this->assertSame(25.0, $share['Ponto B']);
    }

    public function test_next_month_projection_applies_current_growth_rate(): void
    {
        $point = Point::factory()->create();

        PointMovement::factory()->create([
            'point_id' => $point->id, 'type' => 'retirada', 'quantity_kg' => 10,
            'revenue' => 1000, 'cost' => 0, 'occurred_at' => now(),
        ]);
        PointMovement::factory()->create([
            'point_id' => $point->id, 'type' => 'retirada', 'quantity_kg' => 10,
            'revenue' => 500, 'cost' => 0, 'occurred_at' => now()->subMonthNoOverflow(),
        ]);

        $projection = $this->service->nextMonthProjection();

        // lucro atual 1000, anterior 500 -> crescimento 100%, projecao = 1000*2 = 2000
        $this->assertSame(100.0, $projection['growthRatePercent']);
        $this->assertSame(2000.0, $projection['projectedProfit']);
    }

    public function test_consumption_report_sums_kg_and_revenue_per_point_sorted_by_kg_descending(): void
    {
        $high = Point::factory()->create(['name' => 'Ponto Alto']);
        $low = Point::factory()->create(['name' => 'Ponto Baixo']);

        PointMovement::factory()->create([
            'point_id' => $high->id, 'type' => 'retirada', 'quantity_kg' => 80,
            'revenue' => 400, 'occurred_at' => now(),
        ]);
        PointMovement::factory()->create([
            'point_id' => $low->id, 'type' => 'retirada', 'quantity_kg' => 20,
            'revenue' => 100, 'occurred_at' => now(),
        ]);
        PointMovement::factory()->create([
            'point_id' => $low->id, 'type' => 'retirada', 'quantity_kg' => 999,
            'revenue' => 999, 'occurred_at' => now()->subMonths(2),
        ]);

        $report = $this->service->consumptionReport(now()->startOfMonth(), now()->endOfMonth());

        $this->assertCount(2, $report);
        $this->assertSame('Ponto Alto', $report[0]['point']->name);
        $this->assertSame(80.0, $report[0]['kg']);
        $this->assertSame(400.0, $report[0]['revenue']);
        $this->assertSame('Ponto Baixo', $report[1]['point']->name);
        $this->assertSame(20.0, $report[1]['kg']);
    }

    public function test_margin_change_returns_delta_in_percentage_points(): void
    {
        $point = Point::factory()->create();

        PointMovement::factory()->create([
            'point_id' => $point->id, 'type' => 'retirada', 'quantity_kg' => 10,
            'revenue' => 1000, 'cost' => 300, 'occurred_at' => now(),
        ]);
        PointMovement::factory()->create([
            'point_id' => $point->id, 'type' => 'retirada', 'quantity_kg' => 10,
            'revenue' => 1000, 'cost' => 500, 'occurred_at' => now()->subMonthNoOverflow(),
        ]);

        $margin = $this->service->marginChange();

        // atual: (1000-300)/1000=70%, anterior: (1000-500)/1000=50% -> +20pp
        $this->assertSame(70.0, $margin['current']);
        $this->assertSame(50.0, $margin['previous']);
        $this->assertSame(20.0, $margin['deltaPp']);
    }
}
