<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Route::has('dashboards.points.index')
        ? redirect()->route('dashboards.points.index')
        : redirect()->route('login');
});

Route::middleware('auth')->prefix('dashboards')->name('dashboards.')->group(function () {
    // As rotas dos dashboards individuais são adicionadas nas Tasks 10 e 12.
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
