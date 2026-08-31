<?php

namespace Tests\Feature;

use App\Models\Point;
use App\Models\PointMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PointsDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('dashboards.points.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_sees_points_summary(): void
    {
        $this->actingAs(User::factory()->create());

        $point = Point::factory()->create(['name' => 'Balada Teste', 'capacity_kg' => 100]);
        PointMovement::factory()->create([
            'point_id' => $point->id, 'type' => 'reposicao', 'quantity_kg' => 60, 'occurred_at' => now(),
        ]);

        $response = $this->get(route('dashboards.points.index'));

        $response->assertOk();
        $response->assertSee('Balada Teste');
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

        $noHistory = Point::factory()->create(['name' => 'Ponto Sem Historico']);

        $response = $this->get(route('dashboards.points.index'));

        $content = $response->getContent();
        $freshPos = strpos($content, 'Ponto Recente Zulu');
        $stalePos = strpos($content, 'Ponto Antigo Alfa');
        $noHistoryPos = strpos($content, 'Ponto Sem Historico');

        $this->assertNotFalse($freshPos);
        $this->assertNotFalse($stalePos);
        $this->assertNotFalse($noHistoryPos);
        $this->assertTrue($freshPos < $stalePos, 'Ponto com movimentação mais recente deve vir primeiro');
        $this->assertTrue($stalePos < $noHistoryPos, 'Ponto sem histórico deve vir por último');
    }

    public function test_filters_by_status(): void
    {
        $this->actingAs(User::factory()->create());

        Point::factory()->create(['name' => 'Ponto Ativo', 'status' => 'ativo']);
        Point::factory()->create(['name' => 'Ponto Inativo', 'status' => 'inativo']);

        $response = $this->get(route('dashboards.points.index', ['status' => 'ativo']));

        $response->assertSee('Ponto Ativo');
        $response->assertDontSee('Ponto Inativo');
    }
}
