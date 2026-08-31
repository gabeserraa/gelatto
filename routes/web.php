<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboards.overview.index');
});

Route::middleware('auth')->prefix('dashboards')->name('dashboards.')->group(function () {
    Route::get('/pontos', [\App\Http\Controllers\Dashboards\PointsDashboardController::class, 'index'])->name('points.index');
    Route::get('/geral', [\App\Http\Controllers\Dashboards\OverviewDashboardController::class, 'index'])->name('overview.index');
    Route::get('/estoque', [\App\Http\Controllers\Dashboards\InventoryDashboardController::class, 'index'])->name('inventory.index');
    Route::get('/estoque/exportar', [\App\Http\Controllers\Dashboards\InventoryDashboardController::class, 'export'])->name('inventory.export');
    Route::get('/financeiro', [\App\Http\Controllers\Dashboards\FinancialDashboardController::class, 'index'])->name('financial.index');

    Route::prefix('relatorios')->name('reports.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Dashboards\ReportsDashboardController::class, 'index'])->name('index');
        Route::get('/financeiro', [\App\Http\Controllers\Dashboards\ReportsDashboardController::class, 'exportFinancial'])->name('financial');
        Route::get('/consumo', [\App\Http\Controllers\Dashboards\ReportsDashboardController::class, 'exportConsumption'])->name('consumption');
        Route::get('/reposicoes', [\App\Http\Controllers\Dashboards\ReportsDashboardController::class, 'exportReplenishments'])->name('replenishments');
    });

    Route::prefix('configuracoes')->name('settings.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Dashboards\SettingsController::class, 'index'])->name('index');
        Route::get('/empresa', [\App\Http\Controllers\Dashboards\SettingsController::class, 'company'])->name('company');
        Route::put('/empresa', [\App\Http\Controllers\Dashboards\SettingsController::class, 'updateCompany'])->name('company.update');
        Route::get('/preferencias', [\App\Http\Controllers\Dashboards\SettingsController::class, 'preferences'])->name('preferences');
        Route::put('/preferencias', [\App\Http\Controllers\Dashboards\SettingsController::class, 'updatePreferences'])->name('preferences.update');
        Route::get('/integracoes', [\App\Http\Controllers\Dashboards\SettingsController::class, 'integrations'])->name('integrations');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
