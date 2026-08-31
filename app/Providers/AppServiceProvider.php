<?php

namespace App\Providers;

use App\Models\Point;
use App\Services\PointStockService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.app', function ($view) {
            if (! auth()->check()) {
                $view->with('headerAlerts', collect());

                return;
            }

            $stockService = app(PointStockService::class);

            $alerts = Point::where('status', 'ativo')
                ->get()
                ->map(fn (Point $point) => [
                    'point' => $point,
                    'urgency' => $stockService->restockUrgency($point),
                ])
                ->filter(fn ($row) => $row['urgency'] !== 'ok')
                ->sortBy(fn ($row) => $row['urgency'] === 'critico' ? 0 : 1)
                ->values();

            $view->with('headerAlerts', $alerts);
        });
    }
}
