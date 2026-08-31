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
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
