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
