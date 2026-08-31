<?php

namespace Database\Seeders;

use App\Models\Point;
use App\Models\PointMovement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class PointSeeder extends Seeder
{
    public function run(): void
    {
        Point::factory()->count(10)->create()->each(function (Point $point) {
            $months = fake()->numberBetween(3, 6);
            $balance = 0.0;

            for ($i = $months; $i >= 0; $i--) {
                $monthStart = Carbon::now()->subMonthsNoOverflow($i)->startOfMonth();

                $reposicaoQuantity = fake()->randomFloat(2, 20, 60);
                $balance += $reposicaoQuantity;

                PointMovement::factory()->reposicao()->create([
                    'point_id' => $point->id,
                    'quantity_kg' => $reposicaoQuantity,
                    'occurred_at' => $monthStart->copy()->addDays(fake()->numberBetween(0, 3)),
                ]);

                foreach (range(1, fake()->numberBetween(2, 5)) as $ignored) {
                    if ($balance <= 0) {
                        break;
                    }

                    $retiradaQuantity = min(fake()->randomFloat(2, 5, 30), $balance);
                    $balance -= $retiradaQuantity;

                    PointMovement::factory()->create([
                        'point_id' => $point->id,
                        'type' => 'retirada',
                        'quantity_kg' => $retiradaQuantity,
                        'occurred_at' => $monthStart->copy()->addDays(fake()->numberBetween(4, 27)),
                    ]);
                }
            }
        });
    }
}
