<?php

namespace App\Http\Controllers;

use App\Enums\MachineStatus;
use App\Enums\MaintenanceStatus;
use App\Enums\ReworkStatus;
use App\Models\BoxDefect;
use App\Models\Machine;
use App\Models\MaintenanceTicket;
use App\Models\Order;
use App\Models\OrderShipment;
use App\Models\ProductionBox;
use App\Models\ReworkTracking;
use App\Models\YarnStockBalance;
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
        $breakdownMachines = Machine::where('status', MachineStatus::BREAKDOWN)->count();

        $todayGoodPcs = (int) ProductionBox::where('production_date', $today)->sum('good_pcs');
        $todayDefectPcs = (int) ProductionBox::where('production_date', $today)->sum('defect_pcs');
        $todayGoodPairs = $todayGoodPcs;
        $todayDefectPairs = $todayDefectPcs;
        $totalOutput = $todayGoodPcs + $todayDefectPcs;
        $defectRate = $totalOutput > 0 ? round(($todayDefectPcs / $totalOutput) * 100, 2) : 0.0;

        // Domain Metrics from CapacityApps integration
        $totalDefectsCount = BoxDefect::count();
        $activeReworkCount = ReworkTracking::where('status', ReworkStatus::IN_REPAIR)->count();
        $openTicketsCount = MaintenanceTicket::where('status', MaintenanceStatus::OPEN)->count();
        $totalYarnStockKg = (float) YarnStockBalance::sum('weight_kg');

        $recentOrders = Order::with('orderStyles.orderShipments')
            ->latest('order_date')
            ->take(5)
            ->get();

        // Highly optimized index-driven query for earliest target delivery shipments
        $earliestShipmentIds = OrderShipment::orderBy('target_delivery_date')
            ->take(8)
            ->pluck('id');

        $liveProgress = DB::table('order_shipments', 's')
            ->join('order_styles as st', 'st.id', '=', 's.order_style_id')
            ->join('orders as o', 'o.id', '=', 'st.order_id')
            ->leftJoin('production_boxes as pb', 'pb.order_shipment_id', '=', 's.id')
            ->whereIn('s.id', $earliestShipmentIds)
            ->selectRaw('
                o.id AS order_id,
                o.po_number,
                o.buyer_name,
                s.id AS shipment_id,
                s.target_delivery_date,
                st.style_code,
                st.color_code,
                st.size,
                s.qty_pcs AS ordered_qty,
                COALESCE(SUM(pb.good_pcs), 0) AS produced_good_qty,
                COALESCE(SUM(pb.defect_pcs), 0) AS produced_defect_qty,
                GREATEST(0, CAST(s.qty_pcs AS SIGNED) - CAST(COALESCE(SUM(pb.good_pcs), 0) AS SIGNED)) AS sisa_pcs,
                GREATEST(0, CAST(s.qty_pcs AS SIGNED) - CAST(COALESCE(SUM(pb.good_pcs), 0) AS SIGNED)) AS sisa_pairs,
                ROUND((COALESCE(SUM(pb.good_pcs), 0) / NULLIF(s.qty_pcs, 0)) * 100, 2) AS completion_percentage
            ')
            ->groupBy(
                's.id',
                'o.id',
                'o.po_number',
                'o.buyer_name',
                's.target_delivery_date',
                'st.id',
                'st.style_code',
                'st.color_code',
                'st.size',
                's.qty_pcs'
            )
            ->orderBy('s.target_delivery_date')
            ->get();

        return view('dashboard', compact(
            'totalOrders',
            'runningMachines',
            'totalMachines',
            'breakdownMachines',
            'todayGoodPcs',
            'todayDefectPcs',
            'todayGoodPairs',
            'todayDefectPairs',
            'defectRate',
            'totalDefectsCount',
            'activeReworkCount',
            'openTicketsCount',
            'totalYarnStockKg',
            'recentOrders',
            'liveProgress'
        ));
    }
}
