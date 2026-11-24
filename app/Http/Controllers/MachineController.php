<?php

namespace App\Http\Controllers;

use App\Enums\MachineStatus;
use App\Models\FactoryArea;
use App\Models\Machine;
use App\Models\MachineType;
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
}
