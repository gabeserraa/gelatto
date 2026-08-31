<?php

namespace Tests\Feature;

use App\Livewire\MovementFormModal;
use App\Models\Point;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MovementFormModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejects_negative_quantity(): void
    {
        $this->actingAs(User::factory()->create());
        $point = Point::factory()->create();

        Livewire::test(MovementFormModal::class)
            ->call('open', $point->id)
            ->set('quantity_kg', -5)
            ->set('occurred_at', now()->format('Y-m-d'))
            ->call('save')
            ->assertHasErrors(['quantity_kg']);
    }

    public function test_requires_adjustment_direction_when_type_is_ajuste(): void
    {
        $this->actingAs(User::factory()->create());
        $point = Point::factory()->create();

        Livewire::test(MovementFormModal::class)
            ->call('open', $point->id)
            ->set('type', 'ajuste')
            ->set('quantity_kg', 10)
            ->set('occurred_at', now()->format('Y-m-d'))
            ->call('save')
            ->assertHasErrors(['adjustment_direction']);
    }

    public function test_creates_movement_with_valid_data(): void
    {
        $this->actingAs(User::factory()->create());
        $point = Point::factory()->create();

        Livewire::test(MovementFormModal::class)
            ->call('open', $point->id)
            ->set('type', 'retirada')
            ->set('quantity_kg', 12.5)
            ->set('revenue', 60)
            ->set('occurred_at', now()->format('Y-m-d'))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('point_movements', [
            'point_id' => $point->id, 'type' => 'retirada', 'quantity_kg' => 12.5,
        ]);
    }

    public function test_converts_cost_per_kg_input_into_total_cost_stored_on_the_movement(): void
    {
        $this->actingAs(User::factory()->create());
        $point = Point::factory()->create();

        Livewire::test(MovementFormModal::class)
            ->call('open', $point->id)
            ->set('type', 'reposicao')
            ->set('quantity_kg', 40)
            ->set('cost_per_kg', 1.80)
            ->set('occurred_at', now()->format('Y-m-d'))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('point_movements', [
            'point_id' => $point->id, 'type' => 'reposicao', 'quantity_kg' => 40, 'cost' => 72,
        ]);
    }

    public function test_cost_per_kg_is_optional(): void
    {
        $this->actingAs(User::factory()->create());
        $point = Point::factory()->create();

        Livewire::test(MovementFormModal::class)
            ->call('open', $point->id)
            ->set('type', 'retirada')
            ->set('quantity_kg', 10)
            ->set('occurred_at', now()->format('Y-m-d'))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('point_movements', [
            'point_id' => $point->id, 'type' => 'retirada', 'quantity_kg' => 10, 'cost' => null,
        ]);
    }
}
