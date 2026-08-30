<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class DashboardRegistryTest extends TestCase
{
    public function test_dashboard_items_have_required_keys(): void
    {
        $items = config('dashboards.items');

        $this->assertNotEmpty($items);

        foreach ($items as $item) {
            $this->assertArrayHasKey('key', $item);
            $this->assertArrayHasKey('name', $item);
            $this->assertArrayHasKey('icon', $item);
            $this->assertArrayHasKey('route', $item);
            $this->assertArrayHasKey('order', $item);
            $this->assertTrue(Route::has($item['route']), "Route {$item['route']} is not registered.");
        }
    }
}
