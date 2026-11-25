<?php

namespace App\Http\Controllers;

use App\Models\DailyPlanningLoad;
use App\Models\FactoryArea;
use App\Models\Machine;
use App\Models\MachineAssignment;
use App\Models\Order;
use App\Models\OrderShipment;
use App\Models\PlanningAllocation;
use App\Models\SampleApproval;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlanningController extends Controller
{
    /**
     * Dashboard & Manajemen PPIC / Jadwal Kapasitas Mesin
     */
    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'allocations');
        $search = $request->query('search');
        $areaId = $request->query('factory_area_id');

        // Statistik Ringkasan
        $stats = [
            'total_allocations' => PlanningAllocation::count(),
            'total_allocated_pcs' => (int) PlanningAllocation::sum('allocated_pcs'),
            'total_daily_loads' => DailyPlanningLoad::count(),
            'total_machine_assignments' => MachineAssignment::count(),
            'total_approved_pps' => SampleApproval::where('is_approved_for_bulk', true)->count(),
        ];

        // 1. Data Alokasi Planning
        $allocationsQuery = PlanningAllocation::with(['order.orderStyles', 'factoryArea', 'dailyLoads'])
            ->withCount('machineAssignments');

        if ($areaId) {
            $allocationsQuery->where('factory_area_id', $areaId);
        }

        if ($search) {
            $allocationsQuery->whereHas('order', function ($q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                  ->orWhereHas('orderStyles', function ($sq) use ($search) {
                      $sq->where('style_code', 'like', "%{$search}%");
                  });
            });
        }

        $allocations = $allocationsQuery->orderBy('id', 'desc')->paginate(15, ['*'], 'alloc_page');

        // 2. Data Beban Kapasitas Harian (Daily Planning Loads)
        $dailyLoads = DailyPlanningLoad::with(['planningAllocation.order', 'planningAllocation.factoryArea'])
            ->when($search, function ($q) use ($search) {
                $q->whereHas('planningAllocation.order', fn($oq) => $oq->where('po_number', 'like', "%{$search}%"));
            })
            ->orderBy('plan_date', 'desc')
            ->paginate(15, ['*'], 'load_page');

        // 3. Data Penugasan Mesin Fisik (Machine Assignments)
        $machineAssignments = MachineAssignment::with(['planningAllocation.order', 'machine.factoryArea', 'orderShipment.orderStyle'])
            ->when($search, function ($q) use ($search) {
                $q->whereHas('machine', fn($mq) => $mq->where('machine_number', 'like', "%{$search}%"))
                  ->orWhereHas('planningAllocation.order', fn($oq) => $oq->where('po_number', 'like', "%{$search}%"));
            })
            ->orderBy('plan_date', 'desc')
            ->paginate(15, ['*'], 'assign_page');

        $factoryAreas = FactoryArea::orderBy('area_name')->get();
        $recentOrders = Order::with('orderStyles')->orderBy('id', 'desc')->limit(30)->get();
        $activeMachines = Machine::orderBy('machine_number')->limit(50)->get();

        return view('planning.index', compact(
            'tab',
            'stats',
            'allocations',
            'dailyLoads',
            'machineAssignments',
            'factoryAreas',
            'recentOrders',
            'activeMachines'
        ));
    }

    /**
     * Tambah Alokasi Planning Baru
     */
    public function storeAllocation(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'factory_area_id' => 'required|exists:factory_areas,id',
            'allocated_pcs' => 'required|integer|min:1',
            'target_start_date' => 'required|date',
            'target_end_date' => 'required|date|after_or_equal:target_start_date',
        ]);

        PlanningAllocation::create($validated);

        return redirect()->route('planning.index', ['tab' => 'allocations'])
            ->with('success', "Alokasi planning berhasil ditambahkan ({$validated['allocated_pcs']} pcs).");
    }

    /**
     * Tambah Jadwal Beban Mesin Harian
     */
    public function storeDailyLoad(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'planning_allocation_id' => 'required|exists:planning_allocations,id',
            'plan_date' => 'required|date',
            'planned_machine_count' => 'required|integer|min:0',
            'daily_target_pcs' => 'nullable|integer|min:0',
            'start_date' => 'nullable|date',
            'stop_date' => 'nullable|date',
        ]);

        if (empty($validated['daily_target_pcs'])) {
            $validated['daily_target_pcs'] = $validated['planned_machine_count'] * 100;
        }

        DailyPlanningLoad::create($validated);

        return redirect()->route('planning.index', ['tab' => 'daily_loads'])
            ->with('success', "Beban kapasitas harian tanggal {$validated['plan_date']} berhasil disimpan.");
    }

    /**
     * Penugasan Unit Mesin Fisik
     */
    public function storeMachineAssignment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'planning_allocation_id' => 'required|exists:planning_allocations,id',
            'machine_id' => 'required|exists:machines,id',
            'order_shipment_id' => 'required|exists:order_shipments,id',
            'start_time' => 'required|date',
            'stop_time' => 'required|date|after_or_equal:start_time',
            'target_pcs' => 'required|integer|min:0',
        ]);

        // Cek bentrok penugasan mesin di rentang waktu yang sama
        $overlap = MachineAssignment::where('machine_id', $validated['machine_id'])
            ->whereNotNull('start_time')
            ->whereNotNull('stop_time')
            ->where(function ($query) use ($validated) {
                $query->whereBetween('start_time', [$validated['start_time'], $validated['stop_time']])
                      ->orWhereBetween('stop_time', [$validated['start_time'], $validated['stop_time']])
                      ->orWhere(function ($q) use ($validated) {
                          $q->where('start_time', '<=', $validated['start_time'])
                            ->where('stop_time', '>=', $validated['stop_time']);
                      });
            })
            ->exists();

        if ($overlap) {
            return back()->withInput()->with('error', 'Mesin tersebut sudah memiliki penugasan lain pada rentang waktu yang dipilih!');
        }

        $validated['plan_date'] = date('Y-m-d', strtotime($validated['start_time']));
        $validated['target_dz'] = round($validated['target_pcs'] / 12, 2);

        MachineAssignment::create($validated);

        return redirect()->route('planning.index', ['tab' => 'assignments'])
            ->with('success', 'Penugasan fisik mesin berhasil dijadwalkan tanpa bentrok.');
    }
}
