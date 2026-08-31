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

    public function test_filters_by_region(): void
    {
        $this->actingAs(User::factory()->create());

        Point::factory()->create(['name' => 'Ponto Centro', 'region' => 'Centro']);
        Point::factory()->create(['name' => 'Ponto Sul', 'region' => 'Zona Sul']);

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
