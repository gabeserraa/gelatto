<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'point_id',
        'type',
        'quantity_kg',
        'adjustment_direction',
        'cost',
        'revenue',
        'occurred_at',
        'notes',
    ];

    protected $casts = [
        'quantity_kg' => 'decimal:2',
        'cost' => 'decimal:2',
        'revenue' => 'decimal:2',
        'occurred_at' => 'date',
    ];

    public function point(): BelongsTo
    {
        return $this->belongsTo(Point::class);
    }

    public function signedQuantity(): float
    {
        return match ($this->type) {
            'reposicao' => (float) $this->quantity_kg,
            'retirada' => -(float) $this->quantity_kg,
            'ajuste' => $this->adjustment_direction === 'decrease'
                ? -(float) $this->quantity_kg
                : (float) $this->quantity_kg,
        };
    }
}
