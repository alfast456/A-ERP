<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\FactoryArea;
use App\Models\Machine;
use App\Models\OrderShipment;
use App\Models\ProductionBox;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductionBoxController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductionBox::with(['shipment.orderStyle.order', 'machine', 'factoryArea', 'defects.defectType'])
            ->latest('id');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('box_barcode', 'like', "%{$search}%")
                  ->orWhere('no_box', 'like', "%{$search}%")
                  ->orWhere('no_label', 'like', "%{$search}%")
                  ->orWhereHas('shipment.orderStyle.order', function ($oq) use ($search) {
                      $oq->where('po_number', 'like', "%{$search}%")
                         ->orWhere('buyer_name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('area_id')) {
            $query->where('factory_area_id', $request->area_id);
        }

        if ($request->filled('shift')) {
            $query->where('shift', $request->shift);
        }

        if ($request->filled('date')) {
            $query->whereDate('production_date', $request->date);
        }

        $boxes = $query->paginate(25)->withQueryString();
        $areas = FactoryArea::orderBy('area_name')->get();

        $stats = [
            'total_boxes' => ProductionBox::count(),
            'total_good_pcs' => (int) ProductionBox::sum('good_pcs'),
            'total_defect_pcs' => (int) ProductionBox::sum('defect_pcs'),
            'today_output_pcs' => (int) ProductionBox::whereDate('production_date', today())->sum('good_pcs'),
        ];

        return view('production.boxes.index', compact('boxes', 'areas', 'stats'));
    }

    public function create()
    {
        $shipments = OrderShipment::with('orderStyle.order')
            ->latest('id')
            ->take(100)
            ->get();

        $machines = Machine::where('status', 'RUNNING')
            ->orWhereNull('status')
            ->orderBy('machine_number')
            ->take(200)
            ->get();

        $areas = FactoryArea::orderBy('area_name')->get();

        return view('production.boxes.create', compact('shipments', 'machines', 'areas'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_shipment_id' => 'required|exists:order_shipments,id',
            'machine_id' => 'required|exists:machines,id',
            'factory_area_id' => 'required|exists:factory_areas,id',
            'production_date' => 'required|date',
            'shift' => 'required|in:1,2,3',
            'no_box' => 'required|string|max:50',
            'no_label' => 'nullable|integer',
            'good_pcs' => 'required|integer|min:0',
            'defect_pcs' => 'nullable|integer|min:0',
            'weight_kg' => 'nullable|numeric|min:0',
            'shift_a' => 'nullable|integer|min:0',
            'shift_b' => 'nullable|integer|min:0',
            'shift_c' => 'nullable|integer|min:0',
        ]);

        $boxBarcode = 'BX-' . date('Ymd') . '-' . strtoupper(Str::random(5));

        $box = DB::transaction(function () use ($validated, $boxBarcode) {
            $shiftMap = [1 => 'shift_a', 2 => 'shift_b', 3 => 'shift_c'];
            $shiftCol = $shiftMap[$validated['shift']] ?? 'shift_a';

            $userId = auth()->id() ?? User::first()?->id ?? 1;

            $data = [
                'box_barcode' => $boxBarcode,
                'no_box' => $validated['no_box'],
                'no_label' => $validated['no_label'] ?? null,
                'production_date' => $validated['production_date'],
                'shift' => (int) $validated['shift'],
                'order_shipment_id' => $validated['order_shipment_id'],
                'machine_id' => $validated['machine_id'],
                'factory_area_id' => $validated['factory_area_id'],
                'imported_by' => $userId,
                'good_pcs' => (int) $validated['good_pcs'],
                'defect_pcs' => (int) ($validated['defect_pcs'] ?? 0),
                'weight_kg' => $validated['weight_kg'] ?? 0,
                'shift_a' => (int) ($validated['shift_a'] ?? ($validated['shift'] == 1 ? $validated['good_pcs'] : 0)),
                'shift_b' => (int) ($validated['shift_b'] ?? ($validated['shift'] == 2 ? $validated['good_pcs'] : 0)),
                'shift_c' => (int) ($validated['shift_c'] ?? ($validated['shift'] == 3 ? $validated['good_pcs'] : 0)),
                'created_at' => now(),
            ];

            $createdBox = ProductionBox::create($data);

            AuditLog::create([
                'user_id' => $userId,
                'action' => 'CREATE_PRODUCTION_BOX',
                'table_name' => 'production_boxes',
                'record_id' => $createdBox->id,
                'auditable_type' => ProductionBox::class,
                'auditable_id' => $createdBox->id,
                'new_values' => [
                    'box_barcode' => $boxBarcode,
                    'no_box' => $validated['no_box'],
                    'good_pcs' => $validated['good_pcs'],
                ],
                'ip_address' => request()->ip(),
                'created_at' => now(),
            ]);

            return $createdBox;
        });

        return redirect()->route('production.boxes.index')
            ->with('success', "Box produksi {$box->box_barcode} (No. Box {$box->no_box}) berhasil disimpan.");
    }

    public function show(ProductionBox $box)
    {
        $box->load(['shipment.orderStyle.order', 'machine', 'factoryArea', 'defects.defectType', 'importedBy']);
        return view('production.boxes.show', compact('box'));
    }

    public function printLabel(ProductionBox $box)
    {
        $box->load(['shipment.orderStyle.order', 'machine', 'factoryArea']);
        return view('production.boxes.label', compact('box'));
    }
}
