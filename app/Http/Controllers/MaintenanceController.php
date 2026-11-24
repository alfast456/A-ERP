<?php

namespace App\Http\Controllers;

use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceStatus;
use App\Models\Machine;
use App\Models\MaintenanceTicket;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MaintenanceController extends Controller
{
    public function index(Request $request): View
    {
        $query = MaintenanceTicket::with(['machine.factoryArea', 'reporter', 'technician']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('issue_description', 'like', "%{$search}%")
                  ->orWhereHas('machine', fn ($m) => $m->where('machine_number', 'like', "%{$search}%"));
            });
        }

        $tickets = $query->orderByDesc('id')->paginate(15)->withQueryString();

        $openCount = MaintenanceTicket::where('status', MaintenanceStatus::OPEN)->count();
        $inProgressCount = MaintenanceTicket::where('status', MaintenanceStatus::IN_PROGRESS)->count();
        $resolvedCount = MaintenanceTicket::where('status', MaintenanceStatus::RESOLVED)->count();
        $closedCount = MaintenanceTicket::where('status', MaintenanceStatus::CLOSED)->count();

        $machines = Machine::orderBy('machine_number')->take(100)->get();

        return view('maintenance.index', compact(
            'tickets',
            'openCount',
            'inProgressCount',
            'resolvedCount',
            'closedCount',
            'machines'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'machine_id' => 'required|exists:machines,id',
            'priority' => 'required|in:LOW,MEDIUM,HIGH,URGENT',
            'issue_description' => 'required|string|min:5|max:1000',
        ]);

        $ticketCount = MaintenanceTicket::count() + 1;
        $ticketNumber = sprintf('TKT-%s-%04d', now()->format('ym'), $ticketCount);

        $defaultUser = User::first();

        MaintenanceTicket::create([
            'ticket_number' => $ticketNumber,
            'machine_id' => $validated['machine_id'],
            'priority' => $validated['priority'],
            'issue_description' => $validated['issue_description'],
            'reported_by' => $defaultUser?->id ?? 1,
            'status' => MaintenanceStatus::OPEN,
            'created_at' => now(),
        ]);

        return redirect()->route('maintenance.index')
            ->with('success', "Tiket servis {$ticketNumber} berhasil diterbitkan.");
    }

    public function updateStatus(Request $request, MaintenanceTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:OPEN,IN_PROGRESS,RESOLVED,CLOSED',
        ]);

        $ticket->status = MaintenanceStatus::from($validated['status']);
        if ($ticket->status === MaintenanceStatus::RESOLVED || $ticket->status === MaintenanceStatus::CLOSED) {
            $ticket->resolved_at = now();
        }
        $ticket->save();

        return redirect()->route('maintenance.index')
            ->with('success', "Status tiket {$ticket->ticket_number} diperbarui menjadi {$validated['status']}.");
    }
}
