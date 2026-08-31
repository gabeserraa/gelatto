<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'job_title',
        'phone',
        'dark_mode',
        'currency',
        'timezone',
        'notify_critical_stock',
        'notify_low_stock',
        'notify_daily_financial_report',
        'notify_report_generated',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'dark_mode' => 'boolean',
        'notify_critical_stock' => 'boolean',
        'notify_low_stock' => 'boolean',
        'notify_daily_financial_report' => 'boolean',
        'notify_report_generated' => 'boolean',
    ];
}
