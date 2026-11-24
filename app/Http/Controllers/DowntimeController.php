<?php

namespace App\Http\Controllers;

use App\Models\DowntimeReason;
use App\Models\MachineDowntime;
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
}
