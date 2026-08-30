<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboards.points.index');
});

Route::middleware('auth')->prefix('dashboards')->name('dashboards.')->group(function () {
    // As rotas dos dashboards individuais são adicionadas nas Tasks 10 e 12.
});

require __DIR__.'/auth.php';
