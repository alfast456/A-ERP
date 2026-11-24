<?php

namespace App\Http\Controllers;

use App\Enums\MachineStatus;
use App\Models\Machine;
use App\Models\Order;
use App\Models\ProductionBox;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $today = now()->toDateString();

        $totalOrders = Order::count();
        $runningMachines = Machine::where('status', MachineStatus::RUNNING)->count();
        $totalMachines = Machine::count();

        $todayGoodPairs = (int) ProductionBox::where('production_date', $today)->sum('good_pairs');
        $todayDefectPairs = (int) ProductionBox::where('production_date', $today)->sum('defect_pairs');
        $totalOutput = $todayGoodPairs + $todayDefectPairs;
        $defectRate = $totalOutput > 0 ? round(($todayDefectPairs / $totalOutput) * 100, 2) : 0.0;

        $recentOrders = Order::with('orderStyles.orderShipments')
            ->latest('order_date')
            ->take(5)
            ->get();

        $liveProgress = DB::table('view_order_shipment_progress')
            ->orderBy('target_delivery_date')
            ->take(8)
            ->get();

        return view('dashboard', compact(
            'totalOrders',
            'runningMachines',
            'totalMachines',
            'todayGoodPairs',
            'todayDefectPairs',
            'defectRate',
            'recentOrders',
            'liveProgress'
        ));
    }
}
