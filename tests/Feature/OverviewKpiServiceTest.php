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
        $this->service = new OverviewKpiService(new PointStockService());
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
}
