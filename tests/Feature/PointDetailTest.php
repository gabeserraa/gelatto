<?php

namespace Tests\Feature;

use App\Livewire\PointDetail;
use App\Models\Point;
use App\Models\PointMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PointDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_shows_current_stock_and_movement_history(): void
    {
        $this->actingAs(User::factory()->create());
        $point = Point::factory()->create(['name' => 'Ponto Detalhe', 'capacity_kg' => 100]);

        PointMovement::factory()->create([
            'point_id' => $point->id, 'type' => 'reposicao', 'quantity_kg' => 40,
        ]);

        Livewire::test(PointDetail::class)
            ->call('open', $point->id)
            ->assertSee('Ponto Detalhe')
            ->assertSee('40');
    }

    public function test_refreshes_after_point_saved_event(): void
    {
        $this->actingAs(User::factory()->create());
        $point = Point::factory()->create();

        Livewire::test(PointDetail::class)
            ->call('open', $point->id)
            ->dispatch('point-saved', pointId: $point->id)
            ->assertOk();
    }
}
