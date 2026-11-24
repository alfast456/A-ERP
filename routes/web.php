<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductionMonitoringController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

Route::prefix('orders')->name('orders.')->group(function () {
    Route::get('/', [OrderController::class, 'index'])->name('index');
    Route::get('/{order}', [OrderController::class, 'show'])->name('show');
    Route::post('/{order}/revise', [OrderController::class, 'revise'])->name('revise');
});

Route::prefix('production')->name('production.')->group(function () {
    Route::get('/monitoring', [ProductionMonitoringController::class, 'index'])->name('monitoring');
});
