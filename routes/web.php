<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\CapacityPlanningController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DowntimeController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\MachineController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\FlowTemplateController;
use App\Http\Controllers\OeeController;
use App\Http\Controllers\OrderBookingController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PlanningController;
use App\Http\Controllers\ProductionBoxController;
use App\Http\Controllers\ProductionMonitoringController;
use App\Http\Controllers\QualityController;
use App\Http\Controllers\ReportExportController;
use App\Http\Controllers\SampleApprovalController;
use App\Http\Controllers\TimterController;
use App\Http\Controllers\WipMovementController;
use App\Http\Controllers\YarnController;
use Illuminate\Support\Facades\Route;

// 1. Executive Manufacturing Dashboard
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// 2. PPIC, Scheduling & Sample PPS
Route::prefix('planning')->name('planning.')->group(function () {
    Route::get('/', [PlanningController::class, 'index'])->name('index');
    Route::post('/allocations', [PlanningController::class, 'storeAllocation'])->name('allocations.store');
    Route::post('/daily-loads', [PlanningController::class, 'storeDailyLoad'])->name('dailyLoads.store');
    Route::post('/machine-assignments', [PlanningController::class, 'storeMachineAssignment'])->name('machineAssignments.store');

    Route::get('/pps', [SampleApprovalController::class, 'index'])->name('pps.index');
    Route::put('/pps/{sample}', [SampleApprovalController::class, 'update'])->name('pps.update');

    Route::get('/holidays', [HolidayController::class, 'index'])->name('holidays.index');
    Route::post('/holidays', [HolidayController::class, 'store'])->name('holidays.store');
    Route::delete('/holidays/{holiday}', [HolidayController::class, 'destroy'])->name('holidays.destroy');
});

// 3. Order Management & PDK Revisions
Route::prefix('orders')->name('orders.')->group(function () {
    Route::get('/', [OrderController::class, 'index'])->name('index');
    Route::get('/create', [OrderController::class, 'create'])->name('create');
    Route::post('/', [OrderController::class, 'store'])->name('store');
    Route::post('/import', [OrderController::class, 'import'])->name('import');
    Route::get('/{order}', [OrderController::class, 'show'])->name('show');
    Route::post('/{order}/revise', [OrderController::class, 'revise'])->name('revise');
    Route::post('/{order}/cancel', [OrderController::class, 'cancel'])->name('cancel');
});

// 3. Production Monitoring, Output Box & Timter
Route::prefix('production')->name('production.')->group(function () {
    Route::get('/monitoring', [ProductionMonitoringController::class, 'index'])->name('monitoring');
    Route::get('/boxes', [ProductionBoxController::class, 'index'])->name('boxes.index');
    Route::get('/boxes/create', [ProductionBoxController::class, 'create'])->name('boxes.create');
    Route::post('/boxes', [ProductionBoxController::class, 'store'])->name('boxes.store');
    Route::get('/boxes/{box}', [ProductionBoxController::class, 'show'])->name('boxes.show');
    Route::get('/boxes/{box}/print-label', [ProductionBoxController::class, 'printLabel'])->name('boxes.print-label');
    Route::get('/timter', [TimterController::class, 'index'])->name('timter');
});

// 4. WIP Flow Routing & Process Movement
Route::prefix('wip')->name('wip.')->group(function () {
    Route::get('/', [WipMovementController::class, 'index'])->name('index');
    Route::get('/transfer', [WipMovementController::class, 'create'])->name('transfer.create');
    Route::post('/transfer', [WipMovementController::class, 'store'])->name('transfer.store');
    Route::post('/{movement}/receive', [WipMovementController::class, 'receive'])->name('receive');
    Route::get('/buffer', [WipMovementController::class, 'buffer'])->name('buffer');

    Route::get('/flows', [FlowTemplateController::class, 'index'])->name('flows.index');
    Route::post('/flows', [FlowTemplateController::class, 'store'])->name('flows.store');
});

// 5. Machine Master & Telemetry (3.036 Mesin)
Route::prefix('machines')->name('machines.')->group(function () {
    Route::get('/', [MachineController::class, 'index'])->name('index');
    Route::get('/needles', [MachineController::class, 'needles'])->name('needles');
    Route::post('/needles', [MachineController::class, 'storeNeedle'])->name('needles.store');
    Route::get('/oee', [OeeController::class, 'index'])->name('oee');
});

// 6. Quality, Box Defects & Rework
Route::prefix('quality')->name('quality.')->group(function () {
    Route::get('/', [QualityController::class, 'index'])->name('index');
    Route::get('/defects/create', [QualityController::class, 'createDefect'])->name('defects.create');
    Route::post('/defects', [QualityController::class, 'storeDefect'])->name('defects.store');
    Route::post('/rework/{rework}/resolve', [QualityController::class, 'resolveRework'])->name('rework.resolve');

    Route::get('/waste', [QualityController::class, 'waste'])->name('waste');
    Route::post('/waste', [QualityController::class, 'storeWaste'])->name('waste.store');
});

// 7. Downtime Analysis & Breakdown Logs
Route::prefix('downtimes')->name('downtimes.')->group(function () {
    Route::get('/', [DowntimeController::class, 'index'])->name('index');
    Route::get('/create', [DowntimeController::class, 'create'])->name('create');
    Route::post('/', [DowntimeController::class, 'store'])->name('store');
});

// 8. Maintenance & Repair Tickets
Route::prefix('maintenance')->name('maintenance.')->group(function () {
    Route::get('/', [MaintenanceController::class, 'index'])->name('index');
    Route::post('/', [MaintenanceController::class, 'store'])->name('store');
    Route::patch('/{ticket}/status', [MaintenanceController::class, 'updateStatus'])->name('updateStatus');
    Route::post('/{ticket}/reply', [MaintenanceController::class, 'reply'])->name('reply');
    Route::get('/checklists', [MaintenanceController::class, 'checklists'])->name('checklists');
    Route::post('/checklists', [MaintenanceController::class, 'storeChecklist'])->name('checklists.store');
});

// 9. Yarn Floor Stock & Logistics
Route::prefix('yarn')->name('yarn.')->group(function () {
    Route::get('/', [YarnController::class, 'index'])->name('index');
    Route::get('/transactions', [YarnController::class, 'transactions'])->name('transactions');
    Route::post('/transactions', [YarnController::class, 'storeTransaction'])->name('transactions.store');
    Route::get('/requirements', [YarnController::class, 'requirements'])->name('requirements');
});

// 9. Plant Capacity & Workload Forecast
Route::prefix('capacity')->name('capacity.')->group(function () {
    Route::get('/', [CapacityPlanningController::class, 'index'])->name('index');
    Route::get('/targets', [CapacityPlanningController::class, 'targets'])->name('targets');
    Route::post('/targets', [CapacityPlanningController::class, 'storeTarget'])->name('targets.store');
});

// 10. Pre-Order Capacity Bookings
Route::prefix('bookings')->name('bookings.')->group(function () {
    Route::get('/', [OrderBookingController::class, 'index'])->name('index');
    Route::get('/create', [OrderBookingController::class, 'create'])->name('create');
    Route::post('/', [OrderBookingController::class, 'store'])->name('store');
    Route::post('/{booking}/cancel', [OrderBookingController::class, 'cancel'])->name('cancel');
});

// 11. Excel Data Exports (FastExcel)
Route::prefix('reports/export')->name('reports.export.')->group(function () {
    Route::get('/production', [ReportExportController::class, 'exportProductionMonitoring'])->name('production');
    Route::get('/defects', [ReportExportController::class, 'exportDefects'])->name('defects');
    Route::get('/downtimes', [ReportExportController::class, 'exportDowntimes'])->name('downtimes');
});

// 12. Enterprise Audit Trail
Route::prefix('audit')->name('audit.')->group(function () {
    Route::get('/', [AuditLogController::class, 'index'])->name('index');
});
