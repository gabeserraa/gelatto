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

    public function test_daily_average_withdrawal_is_monthly_average_divided_by_thirty(): void
    {
        $point = Point::factory()->create();

        PointMovement::factory()->create([
            'point_id' => $point->id, 'type' => 'retirada', 'quantity_kg' => 60, 'occurred_at' => now()->subDays(5),
        ]);

        $point->load('movements');

        $this->assertSame(2.0, $this->service->dailyAverageWithdrawal($point));
    }

    public function test_days_until_stockout_divides_current_stock_by_daily_average(): void
    {
        $point = Point::factory()->create();

        PointMovement::factory()->create(['point_id' => $point->id, 'type' => 'reposicao', 'quantity_kg' => 320]);
        PointMovement::factory()->create([
            'point_id' => $point->id, 'type' => 'retirada', 'quantity_kg' => 300, 'occurred_at' => now(),
        ]);

        $point->load('movements');

        // currentStock = 20kg, dailyAverage = 300/30 = 10kg/dia -> 2 dias
        $this->assertSame(2.0, $this->service->daysUntilStockout($point));
    }

    public function test_days_until_stockout_is_null_when_no_consumption_history(): void
    {
        $point = Point::factory()->create(['initial_estimate_kg' => null]);
        PointMovement::factory()->create(['point_id' => $point->id, 'type' => 'reposicao', 'quantity_kg' => 40]);

        $point->load('movements');

        $this->assertNull($this->service->daysUntilStockout($point));
    }

    public function test_restock_urgency_is_critico_below_critical_days_threshold(): void
    {
        $point = Point::factory()->create();

        PointMovement::factory()->create(['point_id' => $point->id, 'type' => 'reposicao', 'quantity_kg' => 305]);
        PointMovement::factory()->create([
            'point_id' => $point->id, 'type' => 'retirada', 'quantity_kg' => 300, 'occurred_at' => now(),
        ]);

        $point->load('movements');

        // currentStock = 5kg, dailyAverage = 10kg/dia -> 0.5 dias (< limiar critico de 1 dia)
        $this->assertSame('critico', $this->service->restockUrgency($point));
    }

    public function test_restock_urgency_is_repor_em_breve_below_low_stock_days_threshold(): void
    {
        $point = Point::factory()->create();

        PointMovement::factory()->create(['point_id' => $point->id, 'type' => 'reposicao', 'quantity_kg' => 325]);
        PointMovement::factory()->create([
            'point_id' => $point->id, 'type' => 'retirada', 'quantity_kg' => 300, 'occurred_at' => now(),
        ]);

        $point->load('movements');

        // currentStock = 25kg, dailyAverage = 10kg/dia -> 2.5 dias (entre 1 e 3)
        $this->assertSame('repor_em_breve', $this->service->restockUrgency($point));
    }

    public function test_cost_per_kg_averages_reposicao_cost_within_period(): void
    {
        $point = Point::factory()->create();

        PointMovement::factory()->create([
            'point_id' => $point->id, 'type' => 'reposicao', 'quantity_kg' => 40,
            'cost' => 72, 'occurred_at' => Carbon::create(2026, 3, 10),
        ]);
        PointMovement::factory()->create([
            'point_id' => $point->id, 'type' => 'reposicao', 'quantity_kg' => 10,
            'cost' => 18, 'occurred_at' => Carbon::create(2026, 3, 20),
        ]);
        PointMovement::factory()->create([
            'point_id' => $point->id, 'type' => 'reposicao', 'quantity_kg' => 999,
            'cost' => 999, 'occurred_at' => Carbon::create(2026, 4, 1),
        ]);

        $point->load('movements');

        // (72+18) / (40+10) = 1.80
        $this->assertSame(1.8, $this->service->costPerKg($point, Carbon::create(2026, 3, 1), Carbon::create(2026, 3, 31)));
    }

    public function test_cost_per_kg_is_zero_when_no_reposicao_cost_in_period(): void
    {
        $point = Point::factory()->create();
        $point->load('movements');

        $this->assertSame(0.0, $this->service->costPerKg($point, now()->startOfMonth(), now()->endOfMonth()));
    }

    public function test_stock_value_multiplies_current_stock_by_cost_per_kg(): void
    {
        $point = Point::factory()->create();

        PointMovement::factory()->create([
            'point_id' => $point->id, 'type' => 'reposicao', 'quantity_kg' => 100,
            'cost' => 180, 'occurred_at' => now(),
        ]);
        PointMovement::factory()->create([
            'point_id' => $point->id, 'type' => 'retirada', 'quantity_kg' => 30, 'occurred_at' => now(),
        ]);

        $point->load('movements');

        // currentStock = 70kg, custo/kg = 180/100 = 1.80 -> 126.0
        $this->assertSame(126.0, $this->service->stockValue($point, now()->startOfMonth(), now()->endOfMonth()));
    }

    public function test_turnover_rate_is_the_simple_average_of_saida_over_entrada_per_point(): void
    {
        $a = Point::factory()->create();
        $b = Point::factory()->create();

        PointMovement::factory()->create([
            'point_id' => $a->id, 'type' => 'reposicao', 'quantity_kg' => 400, 'occurred_at' => now(),
        ]);
        PointMovement::factory()->create([
            'point_id' => $a->id, 'type' => 'retirada', 'quantity_kg' => 358, 'occurred_at' => now(),
        ]);
        PointMovement::factory()->create([
            'point_id' => $b->id, 'type' => 'reposicao', 'quantity_kg' => 200, 'occurred_at' => now(),
        ]);
        PointMovement::factory()->create([
            'point_id' => $b->id, 'type' => 'retirada', 'quantity_kg' => 44, 'occurred_at' => now(),
        ]);

        $points = collect([$a, $b])->each->load('movements');

        // (358/400 + 44/200) / 2 = (0.895 + 0.22) / 2 = 0.5575 -> 55.75%... rounded
        $rate = $this->service->turnoverRate($points, now()->startOfMonth(), now()->endOfMonth());

        $this->assertSame(55.8, $rate);
    }

    public function test_turnover_rate_ignores_points_with_no_entrada_in_period(): void
    {
        $a = Point::factory()->create();
        $b = Point::factory()->create();

        PointMovement::factory()->create([
            'point_id' => $a->id, 'type' => 'reposicao', 'quantity_kg' => 100, 'occurred_at' => now(),
        ]);
        PointMovement::factory()->create([
            'point_id' => $a->id, 'type' => 'retirada', 'quantity_kg' => 50, 'occurred_at' => now(),
        ]);

        $points = collect([$a, $b])->each->load('movements');

        $this->assertSame(50.0, $this->service->turnoverRate($points, now()->startOfMonth(), now()->endOfMonth()));
    }

    public function test_restock_urgency_is_ok_when_plenty_of_days_remain(): void
    {
        $point = Point::factory()->create();

        PointMovement::factory()->create(['point_id' => $point->id, 'type' => 'reposicao', 'quantity_kg' => 340]);
        PointMovement::factory()->create([
            'point_id' => $point->id, 'type' => 'retirada', 'quantity_kg' => 300, 'occurred_at' => now(),
        ]);

        $point->load('movements');

        // currentStock = 40kg, dailyAverage = 10kg/dia -> 4 dias (>= limiar baixo de 3 dias)
        $this->assertSame('ok', $this->service->restockUrgency($point));
    }
}
