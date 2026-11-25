<?php

namespace App\Http\Controllers;

use App\Enums\MachineStatus;
use App\Models\AuditLog;
use App\Models\FactoryArea;
use App\Models\Machine;
use App\Models\MonthlyTarget;
use App\Models\Order;
use App\Models\OrderShipment;
use App\Models\Plant;
use App\Models\User;
use App\Services\CapacityPlanningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CapacityPlanningController extends Controller
{
    public function index(Request $request, CapacityPlanningService $planningService): View
    {
        $factoryAreas = FactoryArea::withCount([
            'machines',
            'machines as running_machines_count' => fn ($q) => $q->where('status', MachineStatus::RUNNING),
        ])->orderBy('area_name')->get();

        $totalActiveMachines = Machine::where('status', MachineStatus::RUNNING)->count();
        $totalMachines = Machine::count();

        // Calculate sample capacity forecast based on active orders
        $totalOrderedPcs = (int) OrderShipment::sum('qty_pcs');
        $totalOrderedPairs = $totalOrderedPcs; // for backward view compatibility
        $sampleSmvSeconds = 140.0; // standard average knitting SMV
        $workingHours = 24.0; // 3 shifts
        $efficiency = 0.85; // 85% plant standard OEE

        $dailyCapacityPerMachine = ($workingHours * 3600 * $efficiency) / $sampleSmvSeconds;
        $totalDailyPlantOutput = $totalActiveMachines * $dailyCapacityPerMachine;

        $estimatedDaysToComplete = $totalDailyPlantOutput > 0
            ? round($totalOrderedPcs / $totalDailyPlantOutput, 1)
            : 0;

        $activeOrders = Order::where('status', 'IN_PRODUCTION')
            ->orderByDesc('order_date')
            ->take(8)
            ->get();

        return view('capacity.index', compact(
            'factoryAreas',
            'totalActiveMachines',
            'totalMachines',
            'totalOrderedPcs',
            'totalOrderedPairs',
            'dailyCapacityPerMachine',
            'totalDailyPlantOutput',
            'estimatedDaysToComplete',
            'activeOrders'
        ));
    }

    public function targets(Request $request): View
    {
        $targets = MonthlyTarget::with('plant')->orderByDesc('target_month')->paginate(15);
        $plants = Plant::all();

        return view('capacity.targets', compact('targets', 'plants'));
    }

    public function storeTarget(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'plant_id' => 'required|exists:plants,id',
            'target_month' => 'required|string|max:7',
            'target_export_pcs' => 'required|integer|min:0',
            'target_local_pcs' => 'required|integer|min:0',
            'target_efficiency' => 'required|numeric|between:0,100',
        ]);

        $target = MonthlyTarget::updateOrCreate(
            [
                'plant_id' => $validated['plant_id'],
                'target_month' => $validated['target_month'],
            ],
            [
                'target_export_pcs' => $validated['target_export_pcs'],
                'target_local_pcs' => $validated['target_local_pcs'],
                'target_efficiency' => $validated['target_efficiency'],
                'created_at' => now(),
            ]
        );

        $userId = auth()->id() ?? User::first()?->id ?? 1;

        AuditLog::create([
            'user_id' => $userId,
            'action' => 'SET_MONTHLY_TARGET',
            'table_name' => 'monthly_targets',
            'record_id' => $target->id,
            'new_values' => [
                'target_month' => $validated['target_month'],
                'export_pcs' => $validated['target_export_pcs'],
                'efficiency' => $validated['target_efficiency'],
            ],
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);

        return redirect()->route('capacity.targets')
            ->with('success', "Target produksi bulan {$validated['target_month']} berhasil diperbarui.");
    }
}
