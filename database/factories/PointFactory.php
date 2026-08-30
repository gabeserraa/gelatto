<?php

namespace Database\Factories;

use App\Models\Point;
use Illuminate\Database\Eloquent\Factories\Factory;

class PointFactory extends Factory
{
    protected $model = Point::class;

    public function definition(): array
    {
        $capacity = $this->faker->randomElement([50, 80, 100, 150, 200]);

        return [
            'name' => $this->faker->company(),
            'type' => $this->faker->randomElement(['Balada', 'Casa de eventos', 'Mercado', 'Outro']),
            'address' => $this->faker->address(),
            'latitude' => $this->faker->latitude(-27.5, -26.8),
            'longitude' => $this->faker->longitude(-49.1, -48.5),
            'contact_name' => $this->faker->name(),
            'contact_phone' => $this->faker->numerify('(##) #####-####'),
            'capacity_kg' => $capacity,
            'initial_estimate_kg' => $capacity * 0.3,
            'status' => $this->faker->randomElement(['ativo', 'ativo', 'ativo', 'inativo', 'manutencao']),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
