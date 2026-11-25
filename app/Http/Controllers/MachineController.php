<?php

namespace App\Http\Controllers;

use App\Enums\MachineStatus;
use App\Models\AuditLog;
use App\Models\FactoryArea;
use App\Models\Machine;
use App\Models\MachineType;
use App\Models\NeedleConsumption;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MachineController extends Controller
{
    public function index(Request $request): View
    {
        $query = Machine::with(['factoryArea', 'machineType']);

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('machine_number', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('area_id')) {
            $query->where('factory_area_id', $request->input('area_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('needle_count')) {
            $needle = (int) $request->input('needle_count');
            $query->whereHas('machineType', fn ($q) => $q->where('needle_count', $needle));
        }

        $machines = $query->orderBy('machine_number')->paginate(24)->withQueryString();

        $totalMachines = Machine::count();
        $runningCount = Machine::where('status', MachineStatus::RUNNING)->count();
        $breakdownCount = Machine::where('status', MachineStatus::BREAKDOWN)->count();
        $idleCount = Machine::where('status', MachineStatus::IDLE)->count();
        $maintenanceCount = Machine::where('status', MachineStatus::MAINTENANCE)->count();
        $activeRate = $totalMachines > 0 ? round(($runningCount / $totalMachines) * 100, 1) : 0;

        $factoryAreas = FactoryArea::orderBy('area_name')->get();
        $needleCounts = MachineType::distinct()->orderBy('needle_count')->pluck('needle_count');

        return view('machines.index', compact(
            'machines',
            'totalMachines',
            'runningCount',
            'breakdownCount',
            'idleCount',
            'maintenanceCount',
            'activeRate',
            'factoryAreas',
            'needleCounts'
        ));
    }

    public function needles(Request $request)
    {
        $query = NeedleConsumption::with(['machine.factoryArea', 'mechanic'])
            ->latest('replacement_date');

        if ($request->filled('date')) {
            $query->whereDate('replacement_date', $request->date);
        }

        if ($request->filled('machine_id')) {
            $query->where('machine_id', $request->machine_id);
        }

        $records = $query->paginate(20)->withQueryString();
        $machines = Machine::take(150)->get();

        $stats = [
            'total_replaced' => (int) NeedleConsumption::sum('replaced_count'),
            'total_broken' => (int) NeedleConsumption::sum('broken_count'),
        ];

        return view('machines.needles', compact('records', 'machines', 'stats'));
    }

    public function storeNeedle(Request $request)
    {
        $validated = $request->validate([
            'machine_id' => 'required|exists:machines,id',
            'replacement_date' => 'required|date',
            'shift' => 'required|in:1,2,3',
            'needle_type' => 'required|string|max:50',
            'broken_count' => 'required|integer|min:0',
            'replaced_count' => 'required|integer|min:1',
            'reason' => 'nullable|in:BENT,BROKEN_HOOK,WEAR_TEAR,CRASH',
        ]);

        $userId = auth()->id() ?? User::first()?->id ?? 1;

        $needle = NeedleConsumption::create([
            'machine_id' => $validated['machine_id'],
            'replacement_date' => $validated['replacement_date'],
            'shift' => (int) $validated['shift'],
            'needle_type' => $validated['needle_type'],
            'broken_count' => $validated['broken_count'],
            'replaced_count' => $validated['replaced_count'],
            'reason' => $validated['reason'] ?? 'Patah saat rajut normal',
            'mechanic_id' => $userId,
            'created_at' => now(),
        ]);

        AuditLog::create([
            'user_id' => $userId,
            'action' => 'RECORD_NEEDLE_REPLACEMENT',
            'table_name' => 'needle_consumptions',
            'record_id' => $needle->id,
            'new_values' => [
                'machine_id' => $validated['machine_id'],
                'replaced' => $validated['replaced_count'],
            ],
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);

        return redirect()->route('machines.needles')
            ->with('success', "Konsumsi {$validated['replaced_count']} jarum ({$validated['needle_type']}) pada mesin #{$validated['machine_id']} berhasil dicatat.");
    }
}
