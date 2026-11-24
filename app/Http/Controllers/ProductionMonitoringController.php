<?php

namespace App\Http\Controllers;

use App\Models\OrderShipment;
use App\Models\ProductionBox;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductionMonitoringController extends Controller
{
    public function index(Request $request): View
    {
        $shipmentQuery = OrderShipment::with(['orderStyle.order']);

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $shipmentQuery->where(function ($q) use ($search) {
                $q->whereHas('orderStyle.order', function ($oq) use ($search) {
                    $oq->where('po_number', 'like', "%{$search}%")
                       ->orWhere('buyer_name', 'like', "%{$search}%");
                })->orWhereHas('orderStyle', function ($sq) use ($search) {
                    $sq->where('style_code', 'like', "%{$search}%");
                });
            });
        }

        $paginatedShipments = $shipmentQuery->orderBy('target_delivery_date')->paginate(15)->withQueryString();

        $shipmentIds = $paginatedShipments->pluck('id');

        // Aggregated production totals for paginated 15 shipments
        $productionStats = ProductionBox::whereIn('order_shipment_id', $shipmentIds)
            ->selectRaw('order_shipment_id, COALESCE(SUM(good_pcs), 0) as good_pcs, COALESCE(SUM(defect_pcs), 0) as defect_pcs')
            ->groupBy('order_shipment_id')
            ->get()
            ->keyBy('order_shipment_id');

        $items = $paginatedShipments->getCollection()->map(function ($shipment) use ($productionStats) {
            $stats = $productionStats->get($shipment->id);
            $goodPcs = (int) ($stats->good_pcs ?? 0);
            $defectPcs = (int) ($stats->defect_pcs ?? 0);
            $orderedQty = (int) $shipment->qty_pcs;
            $sisaPcs = max(0, $orderedQty - $goodPcs);
            $completionPercentage = $orderedQty > 0 ? round(($goodPcs / $orderedQty) * 100, 2) : 0.0;

            return (object) [
                'order_id' => $shipment->orderStyle?->order_id,
                'po_number' => $shipment->orderStyle?->order?->po_number ?? '-',
                'buyer_name' => $shipment->orderStyle?->order?->buyer_name ?? '-',
                'shipment_id' => $shipment->id,
                'target_delivery_date' => $shipment->target_delivery_date,
                'style_code' => $shipment->orderStyle?->style_code ?? '-',
                'inisial' => $shipment->orderStyle?->inisial ?? '-',
                'color_code' => $shipment->orderStyle?->color_code ?? '-',
                'size' => $shipment->orderStyle?->size ?? '-',
                'ordered_qty' => $orderedQty,
                'produced_good_qty' => $goodPcs,
                'produced_defect_qty' => $defectPcs,
                'sisa_pcs' => $sisaPcs,
                'sisa_pairs' => $sisaPcs,
                'completion_percentage' => $completionPercentage,
            ];
        });

        if ($request->filled('filter_status')) {
            if ($request->input('filter_status') === 'completed') {
                $items = $items->filter(fn ($item) => $item->sisa_pcs == 0)->values();
            } elseif ($request->input('filter_status') === 'ongoing') {
                $items = $items->filter(fn ($item) => $item->sisa_pcs > 0)->values();
            }
        }

        $paginatedShipments->setCollection($items);
        $progressList = $paginatedShipments;

        $totalOrdered = (int) OrderShipment::sum('qty_pcs');
        $totalGood = (int) ProductionBox::sum('good_pcs');
        $totalDefect = (int) ProductionBox::sum('defect_pcs');
        $totalSisa = max(0, $totalOrdered - $totalGood);

        return view('production.monitoring', compact(
            'progressList',
            'totalOrdered',
            'totalGood',
            'totalDefect',
            'totalSisa'
        ));
    }
}
