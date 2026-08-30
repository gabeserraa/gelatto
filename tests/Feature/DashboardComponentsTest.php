<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class DashboardComponentsTest extends TestCase
{
    public function test_kpi_card_renders_label_and_value(): void
    {
        $html = Blade::render('<x-dashboard.kpi-card label="Estoque total" value="120 kg" />');

        $this->assertStringContainsString('Estoque total', $html);
        $this->assertStringContainsString('120 kg', $html);
    }

    public function test_status_badge_renders_portuguese_label(): void
    {
        $html = Blade::render('<x-dashboard.status-badge status="manutencao" />');

        $this->assertStringContainsString('Em manutenção', $html);
    }
}
