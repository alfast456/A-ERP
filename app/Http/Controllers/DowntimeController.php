<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\DowntimeReason;
use App\Models\Machine;
use App\Models\MachineDowntime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DowntimeController extends Controller
{
    public function index(Request $request): View
    {
        $query = MachineDowntime::with(['machine.factoryArea', 'reason']);

        if ($request->filled('reason_id')) {
            $query->where('downtime_reason_id', $request->input('reason_id'));
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('action_taken', 'like', "%{$search}%")
                  ->orWhereHas('machine', fn ($m) => $m->where('machine_number', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('date')) {
            $query->whereDate('start_time', $request->input('date'));
        }

        $downtimes = $query->orderByDesc('id')->paginate(20)->withQueryString();

        $totalDowntimes = MachineDowntime::count();
        $reasons = DowntimeReason::orderBy('name')->get();

        $topReasons = DowntimeReason::withCount('downtimes')
            ->orderByDesc('downtimes_count')
            ->take(6)
            ->get();

        return view('downtimes.index', compact(
            'downtimes',
            'totalDowntimes',
            'reasons',
            'topReasons'
        ));
    }

    public function create()
    {
        $machines = Machine::take(200)->get();
        $reasons = DowntimeReason::orderBy('name')->get();

        return view('downtimes.create', compact('machines', 'reasons'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'machine_id' => 'required|exists:machines,id',
            'downtime_reason_id' => 'required|exists:downtime_reasons,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after_or_equal:start_time',
            'action_taken' => 'nullable|string|max:1000',
            'technician_name' => 'nullable|string|max:100',
        ]);

        $start = Carbon::parse($validated['start_time']);
        $end = Carbon::parse($validated['end_time']);
        $duration = max(1, $start->diffInMinutes($end));

        $defaultUserId = auth()->id() ?? User::first()?->id ?? 1;

        $downtime = MachineDowntime::create([
            'machine_id' => $validated['machine_id'],
            'downtime_reason_id' => $validated['downtime_reason_id'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'duration_minutes' => $duration,
            'reported_by' => $defaultUserId,
            'action_taken' => $validated['action_taken'] ?? 'Perbaikan teknis',
            'created_at' => now(),
        ]);

        AuditLog::create([
            'user_id' => $defaultUserId,
            'action' => 'RECORD_DOWNTIME',
            'table_name' => 'machine_downtimes',
            'record_id' => $downtime->id,
            'new_values' => [
                'machine_id' => $validated['machine_id'],
                'duration_minutes' => $duration,
            ],
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);

        return redirect()->route('downtimes.index')
            ->with('success', "Log downtime mesin #{$validated['machine_id']} ({$duration} menit) berhasil dicatat.");
    }
}
