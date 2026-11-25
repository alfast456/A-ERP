<?php

namespace App\Http\Controllers;

use App\Enums\WipStatus;
use App\Models\AuditLog;
use App\Models\FactoryArea;
use App\Models\ProductionBox;
use App\Models\User;
use App\Models\WipMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WipMovementController extends Controller
{
    public function index(Request $request)
    {
        $query = WipMovement::with(['productionBox.shipment.orderStyle.order', 'fromArea', 'toArea'])
            ->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from_area_id')) {
            $query->where('from_area_id', $request->from_area_id);
        }

        if ($request->filled('to_area_id')) {
            $query->where('to_area_id', $request->to_area_id);
        }

        if ($request->filled('date')) {
            $query->whereDate('movement_date', $request->date);
        }

        $movements = $query->paginate(25)->withQueryString();
        $areas = FactoryArea::orderBy('area_name')->get();

        $stats = [
            'total_movements' => WipMovement::count(),
            'dispatched_count' => WipMovement::where('status', WipStatus::DISPATCHED)->count(),
            'received_count' => WipMovement::where('status', WipStatus::RECEIVED)->count(),
            'discrepancy_count' => WipMovement::where('status', WipStatus::DISCREPANCY)->count(),
        ];

        return view('wip.index', compact('movements', 'areas', 'stats'));
    }

    public function create()
    {
        $areas = FactoryArea::orderBy('area_name')->get();
        $boxes = ProductionBox::with('shipment.orderStyle.order')
            ->latest('id')
            ->take(100)
            ->get();

        return view('wip.transfer', compact('areas', 'boxes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'production_box_id' => 'required|exists:production_boxes,id',
            'from_area_id' => 'required|exists:factory_areas,id',
            'to_area_id' => 'required|different:from_area_id|exists:factory_areas,id',
            'movement_date' => 'required|date',
            'sent_pcs' => 'required|integer|min:1',
            'remarks' => 'nullable|string|max:500',
        ]);

        $movement = DB::transaction(function () use ($validated) {
            $userId = auth()->id() ?? User::first()?->id ?? 1;

            $record = WipMovement::create([
                'movement_date' => $validated['movement_date'],
                'production_box_id' => $validated['production_box_id'],
                'from_area_id' => $validated['from_area_id'],
                'to_area_id' => $validated['to_area_id'],
                'sent_pcs' => $validated['sent_pcs'],
                'received_pcs' => 0,
                'dispatched_by' => $userId,
                'status' => WipStatus::DISPATCHED,
                'remarks' => $validated['remarks'] ?? null,
                'created_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => $userId,
                'action' => 'DISPATCH_WIP_BOX',
                'table_name' => 'wip_movements',
                'record_id' => $record->id,
                'new_values' => [
                    'box_id' => $validated['production_box_id'],
                    'from' => $validated['from_area_id'],
                    'to' => $validated['to_area_id'],
                    'sent_pcs' => $validated['sent_pcs'],
                ],
                'ip_address' => request()->ip(),
                'created_at' => now(),
            ]);

            return $record;
        });

        return redirect()->route('wip.index')
            ->with('success', "Transfer WIP Box #{$movement->production_box_id} berhasil dikirim (Status: DISPATCHED).");
    }

    public function receive(Request $request, WipMovement $movement)
    {
        $validated = $request->validate([
            'received_pcs' => 'required|integer|min:0',
            'remarks' => 'nullable|string|max:500',
        ]);

        $status = ($validated['received_pcs'] == $movement->sent_pcs)
            ? WipStatus::RECEIVED
            : WipStatus::DISCREPANCY;

        DB::transaction(function () use ($movement, $validated, $status) {
            $userId = auth()->id() ?? User::first()?->id ?? 1;

            $movement->update([
                'received_pcs' => $validated['received_pcs'],
                'received_by' => $userId,
                'status' => $status,
                'remarks' => $validated['remarks'] ? ($movement->remarks . ' | Terima: ' . $validated['remarks']) : $movement->remarks,
            ]);

            AuditLog::create([
                'user_id' => $userId,
                'action' => 'RECEIVE_WIP_BOX',
                'table_name' => 'wip_movements',
                'record_id' => $movement->id,
                'new_values' => [
                    'received_pcs' => $validated['received_pcs'],
                    'status' => $status->value,
                ],
                'ip_address' => request()->ip(),
                'created_at' => now(),
            ]);
        });

        $msg = $status === WipStatus::RECEIVED
            ? "Box #{$movement->production_box_id} berhasil diterima utuh ({$validated['received_pcs']} pcs)."
            : "Box #{$movement->production_box_id} diterima dengan selisih! (Kirim: {$movement->sent_pcs}, Terima: {$validated['received_pcs']}).";

        return redirect()->back()->with('success', $msg);
    }

    public function buffer()
    {
        // Staging buffer grouped by department area
        $areas = FactoryArea::withCount([
            'machines',
        ])->get();

        $inTransitMovements = WipMovement::with(['productionBox.shipment.orderStyle.order', 'fromArea', 'toArea'])
            ->where('status', WipStatus::DISPATCHED)
            ->latest('id')
            ->get();

        $discrepancyMovements = WipMovement::with(['productionBox.shipment.orderStyle.order', 'fromArea', 'toArea'])
            ->where('status', WipStatus::DISCREPANCY)
            ->latest('id')
            ->take(20)
            ->get();

        return view('wip.buffer', compact('areas', 'inTransitMovements', 'discrepancyMovements'));
    }
}
