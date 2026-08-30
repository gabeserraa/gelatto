<?php

namespace Database\Factories;

use App\Models\Point;
use App\Models\PointMovement;
use Illuminate\Database\Eloquent\Factories\Factory;

class PointMovementFactory extends Factory
{
    protected $model = PointMovement::class;

    public function definition(): array
    {
        return [
            'point_id' => Point::factory(),
            'type' => 'retirada',
            'quantity_kg' => $this->faker->randomFloat(2, 5, 30),
            'adjustment_direction' => null,
            'cost' => null,
            'revenue' => $this->faker->randomFloat(2, 20, 150),
            'occurred_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'notes' => null,
        ];
    }
}
