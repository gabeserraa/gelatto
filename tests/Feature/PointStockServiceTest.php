<?php

namespace Tests\Feature;

use App\Models\Point;
use App\Models\PointMovement;
use App\Services\PointStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PointStockServiceTest extends TestCase
{
    use RefreshDatabase;

    private PointStockService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PointStockService;
    }

    public function test_current_stock_sums_reposicao_and_subtracts_retirada(): void
    {
        $point = Point::factory()->create(['capacity_kg' => 100]);

        PointMovement::factory()->create(['point_id' => $point->id, 'type' => 'reposicao', 'quantity_kg' => 80]);
        PointMovement::factory()->create(['point_id' => $point->id, 'type' => 'retirada', 'quantity_kg' => 30]);

        $point->load('movements');

        $this->assertSame(50.0, $this->service->currentStock($point));
    }

    public function test_current_stock_applies_adjustment_direction(): void
    {
        $point = Point::factory()->create(['capacity_kg' => 100]);

        PointMovement::factory()->create(['point_id' => $point->id, 'type' => 'reposicao', 'quantity_kg' => 50]);
        PointMovement::factory()->create([
            'point_id' => $point->id, 'type' => 'ajuste', 'quantity_kg' => 10, 'adjustment_direction' => 'decrease',
        ]);

        $point->load('movements');

        $this->assertSame(40.0, $this->service->currentStock($point));
    }

    public function test_stock_percentage_relative_to_capacity(): void
    {
        $point = Point::factory()->create(['capacity_kg' => 100]);
        PointMovement::factory()->create(['point_id' => $point->id, 'type' => 'reposicao', 'quantity_kg' => 25]);

        $point->load('movements');

        $this->assertSame(25.0, $this->service->stockPercentage($point));
    }

    public function test_monthly_average_withdrawal_uses_history_when_available(): void
    {
        $point = Point::factory()->create(['initial_estimate_kg' => 999]);

        PointMovement::factory()->create([
            'point_id' => $point->id, 'type' => 'retirada', 'quantity_kg' => 20, 'occurred_at' => now()->subDays(5),
        ]);
        PointMovement::factory()->create([
            'point_id' => $point->id, 'type' => 'retirada', 'quantity_kg' => 10, 'occurred_at' => now()->subMonth()->subDays(5),
        ]);

        $point->load('movements');

        $this->assertSame(15.0, $this->service->monthlyAverageWithdrawal($point, 3));
    }

    public function test_monthly_average_withdrawal_falls_back_to_initial_estimate(): void
    {
        $point = Point::factory()->create(['initial_estimate_kg' => 42]);
        $point->load('movements');

        $this->assertSame(42.0, $this->service->monthlyAverageWithdrawal($point, 3));
    }

    public function test_needs_restock_soon_when_below_threshold_percentage(): void
    {
        $point = Point::factory()->create(['capacity_kg' => 100, 'initial_estimate_kg' => 5]);
        PointMovement::factory()->create(['point_id' => $point->id, 'type' => 'reposicao', 'quantity_kg' => 10]);

        $point->load('movements');

        $this->assertTrue($this->service->needsRestockSoon($point));
    }

    public function test_financials_sum_cost_and_revenue_within_period(): void
    {
        $point = Point::factory()->create();

        PointMovement::factory()->create([
            'point_id' => $point->id, 'type' => 'reposicao', 'quantity_kg' => 20,
            'cost' => 50, 'revenue' => null, 'occurred_at' => Carbon::create(2026, 3, 10),
        ]);
        PointMovement::factory()->create([
            'point_id' => $point->id, 'type' => 'retirada', 'quantity_kg' => 15,
            'cost' => null, 'revenue' => 90, 'occurred_at' => Carbon::create(2026, 3, 20),
        ]);
        PointMovement::factory()->create([
            'point_id' => $point->id, 'type' => 'retirada', 'quantity_kg' => 5,
            'cost' => null, 'revenue' => 30, 'occurred_at' => Carbon::create(2026, 4, 1),
        ]);

        $point->load('movements');

        $result = $this->service->financials($point, Carbon::create(2026, 3, 1), Carbon::create(2026, 3, 31));

        $this->assertSame(50.0, $result['cost']);
        $this->assertSame(90.0, $result['revenue']);
        $this->assertSame(40.0, $result['profit']);
    }

    public function test_summary_aggregates_across_points(): void
    {
        $active = Point::factory()->create(['status' => 'ativo', 'capacity_kg' => 100]);
        $inactive = Point::factory()->create(['status' => 'inativo', 'capacity_kg' => 50]);

        PointMovement::factory()->create([
            'point_id' => $active->id, 'type' => 'reposicao', 'quantity_kg' => 60,
            'cost' => 40, 'revenue' => null, 'occurred_at' => Carbon::create(2026, 3, 5),
        ]);
        PointMovement::factory()->create([
            'point_id' => $inactive->id, 'type' => 'reposicao', 'quantity_kg' => 20,
            'cost' => null, 'revenue' => 80, 'occurred_at' => Carbon::create(2026, 3, 6),
        ]);

        $points = Point::with('movements')->get();

        $summary = $this->service->summary($points, Carbon::create(2026, 3, 1), Carbon::create(2026, 3, 31));

        $this->assertSame(1, $summary['active_points']);
        $this->assertSame(80.0, $summary['total_stock']);
        $this->assertSame(80.0, $summary['revenue']);
        $this->assertSame(40.0, $summary['cost']);
        $this->assertSame(40.0, $summary['profit']);
    }
}
