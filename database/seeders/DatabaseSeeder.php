<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Administrador',
            'email' => 'admin@gelatto.com.br',
            'password' => bcrypt('password'),
        ]);

        // PointSeeder desativado: sistema em uso com dados reais desde 2026-08-31.
        // Reative só em ambiente de desenvolvimento/teste, nunca em produção.
        // $this->call(PointSeeder::class);
    }
}
