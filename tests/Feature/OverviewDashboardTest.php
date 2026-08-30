<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OverviewDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('dashboards.overview.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_sees_kpis(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('dashboards.overview.index'));

        $response->assertOk();
        $response->assertSee('Lucro');
        $response->assertSee('Margem');
    }
}
