<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\CapacityPlanningController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DowntimeController;
use App\Http\Controllers\MachineController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductionMonitoringController;
use App\Http\Controllers\QualityController;
use App\Http\Controllers\YarnController;
use Illuminate\Support\Facades\Route;

// 1. Executive Manufacturing Dashboard
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// 2. Order Management & PDK Revisions
Route::prefix('orders')->name('orders.')->group(function () {
    Route::get('/', [OrderController::class, 'index'])->name('index');
    Route::get('/{order}', [OrderController::class, 'show'])->name('show');
    Route::post('/{order}/revise', [OrderController::class, 'revise'])->name('revise');
});

// 3. Production Monitoring & Sisa Order
Route::prefix('production')->name('production.')->group(function () {
    Route::get('/monitoring', [ProductionMonitoringController::class, 'index'])->name('monitoring');
});

// 4. Machine Master & Telemetry (3.036 Mesin)
Route::prefix('machines')->name('machines.')->group(function () {
    Route::get('/', [MachineController::class, 'index'])->name('index');
});

// 5. Quality, Box Defects & Rework
Route::prefix('quality')->name('quality.')->group(function () {
    Route::get('/', [QualityController::class, 'index'])->name('index');
});

// 6. Downtime Analysis & Breakdown Logs
Route::prefix('downtimes')->name('downtimes.')->group(function () {
    Route::get('/', [DowntimeController::class, 'index'])->name('index');
});

// 7. Maintenance & Repair Tickets
Route::prefix('maintenance')->name('maintenance.')->group(function () {
    Route::get('/', [MaintenanceController::class, 'index'])->name('index');
    Route::post('/', [MaintenanceController::class, 'store'])->name('store');
    Route::patch('/{ticket}/status', [MaintenanceController::class, 'updateStatus'])->name('updateStatus');
});

// 8. Yarn Floor Stock & Logistics
Route::prefix('yarn')->name('yarn.')->group(function () {
    Route::get('/', [YarnController::class, 'index'])->name('index');
});

// 9. Plant Capacity & Workload Forecast
Route::prefix('capacity')->name('capacity.')->group(function () {
    Route::get('/', [CapacityPlanningController::class, 'index'])->name('index');
});

// 10. Enterprise Audit Trail
Route::prefix('audit')->name('audit.')->group(function () {
    Route::get('/', [AuditLogController::class, 'index'])->name('index');
});
                                    
// 4. Machine Master & Telemetry (3.036 Mesin)
Route::prefix('machines')->name('machines.')->group(function () {
    Route::get('/', [MachineController::class, 'index'])->name('index');
});

// 5. Quality, Box Defects & Rework
Route::prefix('quality')->name('quality.')->group(function () {
    Route::get('/', [QualityController::class, 'index'])->name('index');
});

// 6. Downtime Analysis & Breakdown Logs
Route::prefix('downtimes')->name('downtimes.')->group(function () {
    Route::get('/', [DowntimeController::class, 'index'])->name('index');
});

// 7. Maintenance & Repair Tickets
Route::prefix('maintenance')->name('maintenance.')->group(function () {
    Route::get('/', [MaintenanceController::class, 'index'])->name('index');
    Route::post('/', [MaintenanceController::class, 'store'])->name('store');
    Route::patch('/{ticket}/status', [MaintenanceController::class, 'updateStatus'])->name('updateStatus');
});

// 8. Yarn Floor Stock & Logistics
Route::prefix('yarn')->name('yarn.')->group(function () {
    Route::get('/', [YarnController::class, 'index'])->name('index');
});

// 9. Plant Capacity & Workload Forecast
Route::prefix('capacity')->name('capacity.')->group(function () {
    Route::get('/', [CapacityPlanningController::class, 'index'])->name('index');
});

// 10. Enterprise Audit Trail
Route::prefix('audit')->name('audit.')->group(function () {
    Route::get('/', [AuditLogController::class, 'index'])->name('index');
});
