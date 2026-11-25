<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use App\Models\SampleApproval;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SampleApprovalController extends Controller
{
    /**
     * Dashboard Approval Sampel Pre-Production (PPS Gatekeeper)
     */
    public function index(Request $request): View
    {
        $status = $request->query('status'); // approved, pending
        $search = $request->query('search');

        $query = SampleApproval::with(['orderStyle.order', 'orderShipment', 'machine', 'mechanic', 'qa']);

        if ($status === 'approved') {
            $query->where('is_approved_for_bulk', true);
        } elseif ($status === 'pending') {
            $query->where('is_approved_for_bulk', false);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('mechanic_name', 'like', "%{$search}%")
                  ->orWhere('remarks', 'like', "%{$search}%")
                  ->orWhereHas('orderStyle', function ($sq) use ($search) {
                      $sq->where('style_code', 'like', "%{$search}%")
                         ->orWhereHas('order', fn($oq) => $oq->where('po_number', 'like', "%{$search}%"));
                  });
            });
        }

        $samples = $query->orderBy('id', 'desc')->paginate(20);

        $stats = [
            'total_samples' => SampleApproval::count(),
            'approved_samples' => SampleApproval::where('is_approved_for_bulk', true)->count(),
            'pending_samples' => SampleApproval::where('is_approved_for_bulk', false)->count(),
        ];

        $availableMachines = Machine::orderBy('machine_number')->limit(100)->get();

        return view('planning.pps', compact('samples', 'stats', 'availableMachines', 'status', 'search'));
    }

    /**
     * Simpan / Perbarui Keputusan Approval PPS
     */
    public function update(Request $request, SampleApproval $sample): RedirectResponse
    {
        $validated = $request->validate([
            'is_approved_for_bulk' => 'required|boolean',
            'sample_stage' => 'required|in:PROTO,FIT,PRE_PRODUCTION,PILOT',
            'mechanic_name' => 'nullable|string|max:100',
            'machine_id' => 'nullable|exists:machines,id',
            'remarks' => 'nullable|string|max:500',
        ]);

        if ($validated['is_approved_for_bulk']) {
            $userId = auth()->id() ?? User::first()?->id ?? 1;
            $validated['approved_at'] = now();
            $validated['qa_approved_by'] = $userId;
            $validated['mechanic_approved_by'] = $userId;
        } else {
            $validated['approved_at'] = null;
        }

        $sample->update($validated);

        $statusMsg = $validated['is_approved_for_bulk'] ? 'DISETUJUI untuk Produksi Massal' : 'BELUM DISETUJUI / Dalam Review';

        return back()->with('success', "Status PPS #{$sample->id} berhasil diperbarui: {$statusMsg}.");
    }
}
