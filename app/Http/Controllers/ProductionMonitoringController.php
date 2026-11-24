<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductionMonitoringController extends Controller
{
    public function index(Request $request): View
    {
        $query = DB::table('view_order_shipment_progress');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                  ->orWhere('buyer_name', 'like', "%{$search}%")
                  ->orWhere('style_code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('filter_status')) {
            if ($request->input('filter_status') === 'completed') {
                $query->where('sisa_pairs', '=', 0);
            } elseif ($request->input('filter_status') === 'ongoing') {
                $query->where('sisa_pairs', '>', 0);
            }
        }

        $progressList = $query->orderBy('target_delivery_date')->paginate(15);

        $totalOrdered = DB::table('view_order_shipment_progress')->sum('ordered_qty');
        $totalGood = DB::table('view_order_shipment_progress')->sum('produced_good_qty');
        $totalDefect = DB::table('view_order_shipment_progress')->sum('produced_defect_qty');
        $totalSisa = DB::table('view_order_shipment_progress')->sum('sisa_pairs');

        return view('production.monitoring', compact(
            'progressList',
            'totalOrdered',
            'totalGood',
            'totalDefect',
            'totalSisa'
        ));
    }
}
