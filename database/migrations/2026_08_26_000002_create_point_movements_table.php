<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('point_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('point_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['reposicao', 'retirada', 'ajuste']);
            $table->decimal('quantity_kg', 8, 2);
            $table->enum('adjustment_direction', ['increase', 'decrease'])->nullable();
            $table->decimal('cost', 8, 2)->nullable();
            $table->decimal('revenue', 8, 2)->nullable();
            $table->date('occurred_at');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_movements');
    }
};
