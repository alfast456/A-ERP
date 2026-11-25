<?php

namespace App\Http\Controllers;

use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceStatus;
use App\Models\AuditLog;
use App\Models\FactoryArea;
use App\Models\Machine;
use App\Models\MachineServiceChecklist;
use App\Models\MachineServiceRecord;
use App\Models\MaintenanceTicket;
use App\Models\MaintenanceTicketReply;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MaintenanceController extends Controller
{
    public function index(Request $request): View
    {
        $query = MaintenanceTicket::with(['machine.factoryArea', 'reporter', 'technician', 'replies.user']);

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

        $ticket = MaintenanceTicket::create([
            'ticket_number' => $ticketNumber,
            'machine_id' => $validated['machine_id'],
            'priority' => $validated['priority'],
            'issue_description' => $validated['issue_description'],
            'reported_by' => $defaultUser?->id ?? 1,
            'status' => MaintenanceStatus::OPEN,
            'created_at' => now(),
        ]);

        $userId = auth()->id() ?? User::first()?->id ?? 1;

        AuditLog::create([
            'user_id' => $userId,
            'action' => 'CREATE_MAINTENANCE_TICKET',
            'table_name' => 'maintenance_tickets',
            'record_id' => $ticket->id,
            'new_values' => ['ticket_number' => $ticketNumber, 'machine_id' => $validated['machine_id']],
            'ip_address' => request()->ip(),
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

    public function reply(Request $request, MaintenanceTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|min:2|max:1000',
        ]);

        $userId = auth()->id() ?? User::first()?->id ?? 1;

        MaintenanceTicketReply::create([
            'maintenance_ticket_id' => $ticket->id,
            'user_id' => $userId,
            'message' => $validated['message'],
            'created_at' => now(),
        ]);

        return redirect()->route('maintenance.index')
            ->with('success', "Pesan balasan pada tiket {$ticket->ticket_number} berhasil ditambahkan.");
    }

    public function checklists(Request $request)
    {
        $query = MachineServiceRecord::with(['machine.factoryArea', 'factoryArea', 'checklists'])
            ->latest('service_date');

        if ($request->filled('date')) {
            $query->whereDate('service_date', $request->date);
        }

        if ($request->filled('area_id')) {
            $query->where('factory_area_id', $request->area_id);
        }

        $records = $query->paginate(20)->withQueryString();
        $areas = FactoryArea::orderBy('area_name')->get();
        $machines = Machine::take(100)->get();

        return view('maintenance.checklists', compact('records', 'areas', 'machines'));
    }

    public function storeChecklist(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'machine_id' => 'required|exists:machines,id',
            'factory_area_id' => 'required|exists:factory_areas,id',
            'service_date' => 'required|date',
            'technician_name' => 'required|string|max:100',
            'inspector_name' => 'nullable|string|max:100',
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string|max:100',
            'items.*.status' => 'required|in:OK,NOT_OK,NA',
            'items.*.sparepart_name' => 'nullable|string|max:100',
            'items.*.sparepart_qty' => 'nullable|integer|min:0',
        ]);

        DB::transaction(function () use ($validated) {
            $record = MachineServiceRecord::create([
                'machine_id' => $validated['machine_id'],
                'factory_area_id' => $validated['factory_area_id'],
                'service_date' => $validated['service_date'],
                'technician_name' => $validated['technician_name'],
                'inspector_name' => $validated['inspector_name'] ?? 'Supervisor QC',
                'created_at' => now(),
            ]);

            foreach ($validated['items'] as $item) {
                MachineServiceChecklist::create([
                    'machine_service_record_id' => $record->id,
                    'item_name' => $item['name'],
                    'status' => $item['status'],
                    'sparepart_name' => $item['sparepart_name'] ?? null,
                    'sparepart_qty' => $item['sparepart_qty'] ?? 0,
                    'created_at' => now(),
                ]);
            }
        });

        return redirect()->route('maintenance.checklists')
            ->with('success', "Checklist Preventive Maintenance mesin #{$validated['machine_id']} berhasil disimpan.");
    }
}
