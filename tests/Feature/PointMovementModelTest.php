<?php

namespace Tests\Feature;

use App\Models\PointMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PointMovementModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_quantity_is_positive_for_reposicao(): void
    {
        $movement = PointMovement::factory()->make(['type' => 'reposicao', 'quantity_kg' => 30]);

        $this->assertSame(30.0, $movement->signedQuantity());
    }

    public function test_signed_quantity_is_negative_for_retirada(): void
    {
        $movement = PointMovement::factory()->make(['type' => 'retirada', 'quantity_kg' => 10]);

        $this->assertSame(-10.0, $movement->signedQuantity());
    }

    public function test_signed_quantity_respects_adjustment_direction(): void
    {
        $increase = PointMovement::factory()->make([
            'type' => 'ajuste', 'quantity_kg' => 5, 'adjustment_direction' => 'increase',
        ]);
        $decrease = PointMovement::factory()->make([
            'type' => 'ajuste', 'quantity_kg' => 5, 'adjustment_direction' => 'decrease',
        ]);

        $this->assertSame(5.0, $increase->signedQuantity());
        $this->assertSame(-5.0, $decrease->signedQuantity());
    }
}
