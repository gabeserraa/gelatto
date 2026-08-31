<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Point extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'address',
        'region',
        'latitude',
        'longitude',
        'contact_name',
        'contact_phone',
        'capacity_kg',
        'initial_estimate_kg',
        'status',
        'notes',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'capacity_kg' => 'decimal:2',
        'initial_estimate_kg' => 'decimal:2',
    ];

    public function movements(): HasMany
    {
        return $this->hasMany(PointMovement::class);
    }
}
