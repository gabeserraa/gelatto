<?php

namespace Tests\Feature;

use App\Models\Point;
use App\Models\PointMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('dashboards.reports.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_sees_report_cards(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('dashboards.reports.index'));

        $response->assertOk();
        $response->assertSee('Relatório Financeiro Mensal');
        $response->assertSee('Relatório de Consumo por Ponto');
        $response->assertSee('Relatório de Reposições');
        $response->assertSee('Relatório de Estoque Consolidado');
    }

    public function test_financial_export_returns_csv_with_point_rows(): void
    {
        $this->actingAs(User::factory()->create());

        $point = Point::factory()->create(['name' => 'Arena Events']);
        PointMovement::factory()->create([
            'point_id' => $point->id, 'type' => 'retirada', 'quantity_kg' => 10,
            'revenue' => 1000, 'cost' => 300, 'occurred_at' => now(),
        ]);

        $response = $this->get(route('dashboards.reports.financial'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Arena Events', $response->getContent());
    }

    public function test_consumption_export_returns_csv_with_point_rows(): void
    {
        $this->actingAs(User::factory()->create());

        $point = Point::factory()->create(['name' => 'Supermix Bar']);
        PointMovement::factory()->create([
            'point_id' => $point->id, 'type' => 'retirada', 'quantity_kg' => 15,
            'occurred_at' => now(),
        ]);

        $response = $this->get(route('dashboards.reports.consumption'));

        $response->assertOk();
        $this->assertStringContainsString('Supermix Bar', $response->getContent());
    }

    public function test_replenishments_export_returns_csv_within_period(): void
    {
        $this->actingAs(User::factory()->create());

        $point = Point::factory()->create(['name' => 'Festival Lapa']);
        PointMovement::factory()->create([
            'point_id' => $point->id, 'type' => 'reposicao', 'quantity_kg' => 60,
            'cost' => 108, 'occurred_at' => now(),
        ]);
        PointMovement::factory()->create([
            'point_id' => $point->id, 'type' => 'reposicao', 'quantity_kg' => 999,
            'occurred_at' => now()->subMonths(3),
        ]);

        $response = $this->get(route('dashboards.reports.replenishments', [
            'start' => now()->startOfMonth()->format('Y-m-d'),
            'end' => now()->endOfMonth()->format('Y-m-d'),
        ]));

        $response->assertOk();
        $content = $response->getContent();
        $this->assertStringContainsString('Festival Lapa', $content);
        $this->assertSame(1, substr_count($content, 'Festival Lapa'));
    }
}
