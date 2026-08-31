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
}
