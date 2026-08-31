<?php

namespace Tests\Feature;

use App\Models\Point;
use App\Models\PointMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('dashboards.financial.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_sees_ranking_and_projection(): void
    {
        $this->actingAs(User::factory()->create());

        $point = Point::factory()->create(['name' => 'Arena Events']);
        PointMovement::factory()->create([
            'point_id' => $point->id, 'type' => 'retirada', 'quantity_kg' => 10,
            'revenue' => 1000, 'cost' => 300, 'occurred_at' => now(),
        ]);

        $response = $this->get(route('dashboards.financial.index'));

        $response->assertOk();
        $response->assertSee('Arena Events');
        $response->assertSee('Ranking de lucro por ponto');
        $response->assertSee('Projeção de');
    }
}
