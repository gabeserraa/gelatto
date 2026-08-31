<?php

namespace Tests\Feature;

use App\Models\Point;
use App\Models\PointMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('dashboards.inventory.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_sees_inventory_table(): void
    {
        $this->actingAs(User::factory()->create());

        $point = Point::factory()->create(['name' => 'Club Black']);
        PointMovement::factory()->create([
            'point_id' => $point->id, 'type' => 'reposicao', 'quantity_kg' => 40,
            'cost' => 72, 'occurred_at' => now(),
        ]);

        $response = $this->get(route('dashboards.inventory.index'));

        $response->assertOk();
        $response->assertSee('Club Black');
        $response->assertSee('Giro médio');
    }

    public function test_orders_points_by_most_recent_movement_first(): void
    {
        $this->actingAs(User::factory()->create());

        $stale = Point::factory()->create(['name' => 'Ponto Antigo Alfa']);
        PointMovement::factory()->create([
            'point_id' => $stale->id, 'type' => 'reposicao', 'occurred_at' => now()->subMonths(3),
        ]);

        $fresh = Point::factory()->create(['name' => 'Ponto Recente Zulu']);
        PointMovement::factory()->create([
            'point_id' => $fresh->id, 'type' => 'retirada', 'occurred_at' => now(),
        ]);

        $response = $this->get(route('dashboards.inventory.index'));

        $content = $response->getContent();
        $freshPos = strpos($content, 'Ponto Recente Zulu');
        $stalePos = strpos($content, 'Ponto Antigo Alfa');

        $this->assertNotFalse($freshPos);
        $this->assertNotFalse($stalePos);
        $this->assertTrue($freshPos < $stalePos, 'Ponto com movimentação mais recente deve vir primeiro');
    }

    public function test_filters_by_region(): void
    {
        $this->actingAs(User::factory()->create());

        Point::factory()->create(['name' => 'Ponto Centro', 'region' => 'Centro']);
        $sul = Point::factory()->create(['name' => 'Ponto Sul', 'region' => 'Zona Sul', 'capacity_kg' => 100]);
        // Estoque saudável para não aparecer no sino de alertas globais do header
        // (que lista pontos críticos em qualquer página, sem respeitar o filtro de região).
        PointMovement::factory()->create(['point_id' => $sul->id, 'type' => 'reposicao', 'quantity_kg' => 90]);

        $response = $this->get(route('dashboards.inventory.index', ['region' => 'Centro']));

        $response->assertSee('Ponto Centro');
        $response->assertDontSee('Ponto Sul');
    }

    public function test_export_returns_csv_with_point_rows(): void
    {
        $this->actingAs(User::factory()->create());

        Point::factory()->create(['name' => 'Club Black']);

        $response = $this->get(route('dashboards.inventory.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Club Black', $response->getContent());
    }
}
