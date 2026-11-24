<?php

namespace App\Http\Controllers;

use App\Enums\MachineStatus;
use App\Models\FactoryArea;
use App\Models\Machine;
use App\Models\Order;
use App\Models\OrderShipment;
use App\Services\CapacityPlanningService;
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
}
