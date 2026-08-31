<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    protected $fillable = [
        'legal_name',
        'trade_name',
        'cnpj',
        'phone',
        'email',
        'website',
        'address',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate([]);
    }
}
