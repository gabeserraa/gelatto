<?php

namespace Tests\Feature;

use App\Livewire\PointFormModal;
use App\Models\Point;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PointFormModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejects_missing_required_fields(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(PointFormModal::class)
            ->call('open')
            ->set('name', '')
            ->call('save')
            ->assertHasErrors(['name']);
    }

    public function test_creates_a_point_with_valid_data(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(PointFormModal::class)
            ->call('open')
            ->set('name', 'Balada Nova')
            ->set('type', 'Balada')
            ->set('capacity_kg', 100)
            ->set('status', 'ativo')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('points', ['name' => 'Balada Nova']);
    }

    public function test_loads_existing_point_data_on_open(): void
    {
        $this->actingAs(User::factory()->create());
        $point = Point::factory()->create(['name' => 'Ponto Existente']);

        Livewire::test(PointFormModal::class)
            ->call('open', $point->id)
            ->assertSet('name', 'Ponto Existente');
    }
}
