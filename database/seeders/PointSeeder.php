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

            for ($i = $months; $i >= 0; $i--) {
                $monthStart = Carbon::now()->subMonths($i)->startOfMonth();

                PointMovement::factory()->reposicao()->create([
                    'point_id' => $point->id,
                    'occurred_at' => $monthStart->copy()->addDays(fake()->numberBetween(0, 3)),
                ]);

                foreach (range(1, fake()->numberBetween(2, 5)) as $ignored) {
                    PointMovement::factory()->create([
                        'point_id' => $point->id,
                        'type' => 'retirada',
                        'occurred_at' => $monthStart->copy()->addDays(fake()->numberBetween(4, 27)),
                    ]);
                }
            }
        });
    }
}
