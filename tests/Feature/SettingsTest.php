<?php

namespace Tests\Feature;

use App\Models\CompanySetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('dashboards.settings.index'))->assertRedirect(route('login'));
    }

    public function test_index_redirects_to_company_tab(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('dashboards.settings.index'))
            ->assertRedirect(route('dashboards.settings.company'));
    }

    public function test_company_page_shows_current_settings(): void
    {
        $this->actingAs(User::factory()->create());

        CompanySetting::current()->update(['legal_name' => 'Gelatto ICE CO. Ltda.']);

        $response = $this->get(route('dashboards.settings.company'));

        $response->assertOk();
        $response->assertSee('Gelatto ICE CO. Ltda.');
    }

    public function test_company_settings_can_be_updated(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->put(route('dashboards.settings.company.update'), [
            'legal_name' => 'Gelatto ICE CO. Ltda.',
            'trade_name' => 'Gelatto ICE CO.',
            'cnpj' => '12.345.678/0001-99',
            'phone' => '(11) 3000-1234',
            'email' => 'contato@gelatto.com.br',
            'website' => 'www.gelatto.com.br',
            'address' => 'Av. Paulista, 1500',
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame('Gelatto ICE CO. Ltda.', CompanySetting::current()->legal_name);
        $this->assertSame('contato@gelatto.com.br', CompanySetting::current()->email);
    }

    public function test_preferences_can_be_updated(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->put(route('dashboards.settings.preferences.update'), [
            'dark_mode' => '1',
            'currency' => 'BRL',
            'timezone' => 'America/Sao_Paulo',
            'notify_critical_stock' => '1',
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect();

        $user->refresh();
        $this->assertTrue($user->dark_mode);
        $this->assertTrue($user->notify_critical_stock);
        $this->assertFalse($user->notify_low_stock);
    }

    public function test_integrations_page_is_displayed(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('dashboards.settings.integrations'));

        $response->assertOk();
        $response->assertSee('WhatsApp Business');
    }
}
