<?php

namespace App\Http\Controllers;

use App\Enums\ReworkStatus;
use App\Models\AuditLog;
use App\Models\BoxDefect;
use App\Models\DefectType;
use App\Models\FactoryArea;
use App\Models\Machine;
use App\Models\MachineWasteLog;
use App\Models\ProductionBox;
use App\Models\ReworkTracking;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class QualityController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->input('tab', 'defects');

        $defectTypes = DefectType::orderBy('defect_code')->get();

        if ($tab === 'rework') {
            $query = ReworkTracking::with(['defectType', 'productionBox', 'repairArea'])
                ->latest('id');

            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }

            if ($request->filled('defect_type_id')) {
                $query->where('defect_type_id', $request->input('defect_type_id'));
            }

            $reworkList = $query->paginate(20)->withQueryString();
            $defectList = null;
        } else {
            $query = BoxDefect::with(['defectType', 'productionBox.shipment.orderStyle.order'])
                ->latest('id');

            if ($request->filled('defect_type_id')) {
                $query->where('defect_type_id', $request->input('defect_type_id'));
            }

            if ($request->filled('search')) {
                $search = trim($request->input('search'));
                $query->where(function ($q) use ($search) {
                    $q->where('notes', 'like', "%{$search}%")
                      ->orWhereHas('productionBox', function ($bq) use ($search) {
                          $bq->where('no_box', 'like', "%{$search}%")
                             ->orWhere('box_barcode', 'like', "%{$search}%");
                      });
                });
            }

            $defectList = $query->paginate(20)->withQueryString();
            $reworkList = null;
        }

        $totalDefectsCount = BoxDefect::count();
        $totalReworkCount = ReworkTracking::count();
        $totalRepaired = (int) ReworkTracking::where('status', ReworkStatus::RESOLVED)->sum('repaired_pcs');
        $totalScrapped = (int) ReworkTracking::where('status', ReworkStatus::SCRAPPED)->sum('scrapped_pcs');
        $inRepairCount = ReworkTracking::where('status', ReworkStatus::IN_REPAIR)->count();

        $repairResolutionRate = ($totalRepaired + $totalScrapped) > 0
            ? round(($totalRepaired / ($totalRepaired + $totalScrapped)) * 100, 1)
            : 0;

        return view('quality.index', compact(
            'tab',
            'defectList',
            'reworkList',
            'defectTypes',
            'totalDefectsCount',
            'totalReworkCount',
            'totalRepaired',
            'totalScrapped',
            'inRepairCount',
            'repairResolutionRate'
        ));
    }

    public function createDefect()
    {
        $defectTypes = DefectType::orderBy('defect_code')->get();
        $boxes = ProductionBox::latest('id')->take(100)->get();

        return view('quality.defects.create', compact('defectTypes', 'boxes'));
    }

    public function storeDefect(Request $request)
    {
        $validated = $request->validate([
            'production_box_id' => 'required|exists:production_boxes,id',
            'defect_type_id' => 'required|exists:defect_types,id',
            'defect_pcs' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:500',
            'create_rework' => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $box = ProductionBox::find($validated['production_box_id']);

            $defect = BoxDefect::create([
                'order_shipment_id' => $box?->order_shipment_id,
                'production_box_id' => $validated['production_box_id'],
                'defect_type_id' => $validated['defect_type_id'],
                'factory_area_id' => $box?->factory_area_id,
                'no_box' => $box?->no_box,
                'no_label' => $box?->no_label,
                'defect_date' => now()->toDateString(),
                'defect_count_pcs' => $validated['defect_pcs'],
                'notes' => $validated['notes'] ?? null,
                'created_at' => now(),
            ]);

            // Tambahkan kuantitas cacat ke box
            if ($box) {
                $box->increment('defect_pcs', $validated['defect_pcs']);
            }

            // Jika user memilih untuk otomatis mengirim ke jalur perbaikan (Rework)
            if ($request->boolean('create_rework') && $box) {
                ReworkTracking::create([
                    'order_shipment_id' => $box->order_shipment_id,
                    'production_box_id' => $box->id,
                    'defect_type_id' => $validated['defect_type_id'],
                    'sent_pcs' => $validated['defect_pcs'],
                    'repaired_pcs' => 0,
                    'scrapped_pcs' => 0,
                    'status' => ReworkStatus::IN_REPAIR,
                    'repair_area_id' => $box->factory_area_id,
                    'machine_id' => $box->machine_id,
                    'no_box' => $box->no_box,
                    'no_label' => $box->no_label,
                    'rework_date' => now()->toDateString(),
                    'created_at' => now(),
                ]);
            }

            $userId = auth()->id() ?? User::first()?->id ?? 1;

            AuditLog::create([
                'user_id' => $userId,
                'action' => 'RECORD_BOX_DEFECT',
                'table_name' => 'box_defects',
                'record_id' => $defect->id,
                'new_values' => [
                    'box_id' => $validated['production_box_id'],
                    'defect_pcs' => $validated['defect_pcs'],
                ],
                'ip_address' => request()->ip(),
                'created_at' => now(),
            ]);
        });

        return redirect()->route('quality.index', ['tab' => 'defects'])
            ->with('success', "Temuan cacat {$validated['defect_pcs']} pcs berhasil dicatat.");
    }

    public function resolveRework(Request $request, ReworkTracking $rework)
    {
        $validated = $request->validate([
            'action_type' => 'required|in:RESOLVE,SCRAP',
            'qty' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:500',
        ]);

        $userId = auth()->id() ?? User::first()?->id ?? 1;

        DB::transaction(function () use ($rework, $validated, $userId) {
            if ($validated['action_type'] === 'RESOLVE') {
                $rework->update([
                    'repaired_pcs' => $validated['qty'],
                    'status' => ReworkStatus::RESOLVED,
                    'repaired_at' => now(),
                    'technician_id' => $userId,
                ]);

                // Tambahkan kembali ke good_pcs jika ada box
                if ($rework->production_box_id) {
                    $box = ProductionBox::find($rework->production_box_id);
                    if ($box) {
                        $box->increment('good_pcs', $validated['qty']);
                        $box->decrement('defect_pcs', min($box->defect_pcs, $validated['qty']));
                    }
                }
            } else {
                $rework->update([
                    'scrapped_pcs' => $validated['qty'],
                    'status' => ReworkStatus::SCRAPPED,
                    'repaired_at' => now(),
                    'technician_id' => $userId,
                ]);
            }

            AuditLog::create([
                'user_id' => $userId,
                'action' => 'RESOLVE_REWORK',
                'table_name' => 'rework_tracking',
                'record_id' => $rework->id,
                'new_values' => [
                    'status' => $rework->status->value,
                    'qty' => $validated['qty'],
                ],
                'ip_address' => request()->ip(),
                'created_at' => now(),
            ]);
        });

        $msg = $validated['action_type'] === 'RESOLVE'
            ? "Perbaikan berhasil! {$validated['qty']} pcs kaos kaki dikembalikan ke Good Output."
            : "Kaos kaki {$validated['qty']} pcs dinyatakan BS murni (Scrapped).";

        return redirect()->route('quality.index', ['tab' => 'rework'])->with('success', $msg);
    }

    public function waste(Request $request)
    {
        $query = MachineWasteLog::with(['machine', 'factoryArea'])
            ->latest('production_date');

        if ($request->filled('date')) {
            $query->whereDate('production_date', $request->date);
        }

        if ($request->filled('area_id')) {
            $query->where('factory_area_id', $request->area_id);
        }

        $wasteLogs = $query->paginate(25)->withQueryString();
        $areas = FactoryArea::orderBy('area_name')->get();
        $machines = Machine::take(100)->get();

        $stats = [
            'total_waste_pcs' => (int) MachineWasteLog::sum('qty_pcs'),
            'total_waste_gram' => (int) MachineWasteLog::sum('weight_gram'),
            'total_waste_kg' => round(MachineWasteLog::sum('weight_gram') / 1000, 2),
        ];

        return view('quality.waste.index', compact('wasteLogs', 'areas', 'machines', 'stats'));
    }

    public function storeWaste(Request $request)
    {
        $validated = $request->validate([
            'production_date' => 'required|date',
            'machine_id' => 'required|exists:machines,id',
            'factory_area_id' => 'required|exists:factory_areas,id',
            'shift' => 'required|in:1,2,3',
            'qty_pcs' => 'nullable|numeric|min:0',
            'weight_gram' => 'required|numeric|min:0',
            'employee_name' => 'nullable|string|max:100',
            'style_code' => 'nullable|string|max:100',
        ]);

        MachineWasteLog::create([
            'production_date' => $validated['production_date'],
            'machine_id' => $validated['machine_id'],
            'factory_area_id' => $validated['factory_area_id'],
            'shift' => (int) $validated['shift'],
            'qty_pcs' => $validated['qty_pcs'] ?? 0,
            'weight_gram' => $validated['weight_gram'],
            'employee_name' => $validated['employee_name'] ?? 'Operator Rajut',
            'style_code' => $validated['style_code'] ?? null,
            'created_at' => now(),
        ]);

        return redirect()->route('quality.waste')
            ->with('success', "Log limbah benang / waste mesin #{$validated['machine_id']} ({$validated['weight_gram']} g) berhasil dicatat.");
    }
}
