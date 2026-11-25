<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\MachineType;
use App\Models\OrderBooking;
use App\Models\OrderBookingCancellation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrderBookingController extends Controller
{
    public function index(Request $request): View
    {
        $query = OrderBooking::with(['machineType', 'cancellations'])->latest('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('buyer')) {
            $query->where('buyer_name', 'like', '%' . $request->input('buyer') . '%');
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('booking_code', 'like', "%{$search}%")
                  ->orWhere('no_booking', 'like', "%{$search}%")
                  ->orWhere('no_order', 'like', "%{$search}%")
                  ->orWhere('buyer_name', 'like', "%{$search}%");
            });
        }

        $bookings = $query->paginate(20)->withQueryString();

        $totalBookings = OrderBooking::count();
        $totalEstimatedPcs = (int) OrderBooking::where('status', 'HOLD')->sum('estimated_pcs');
        $totalCancelledPcs = (int) OrderBookingCancellation::sum('qty_cancel_pcs');

        return view('bookings.index', compact(
            'bookings',
            'totalBookings',
            'totalEstimatedPcs',
            'totalCancelledPcs'
        ));
    }

    public function create(): View
    {
        $machineTypes = MachineType::orderBy('needle_count')->get();

        return view('bookings.create', compact('machineTypes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'buyer_name' => 'required|string|max:100',
            'estimated_pcs' => 'required|integer|min:1',
            'target_month' => 'required|string|max:7',
            'delivery_date' => 'nullable|date',
            'machine_type_id' => 'nullable|exists:machine_types,id',
            'needle' => 'nullable|string|max:50',
            'seam' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:500',
        ]);

        $bookingCount = OrderBooking::count() + 1;
        $bookingCode = sprintf('BK-%s-%04d', str_replace('-', '', $validated['target_month']), $bookingCount);

        $booking = OrderBooking::create([
            'buyer_name' => $validated['buyer_name'],
            'booking_code' => $bookingCode,
            'no_booking' => $bookingCode,
            'machine_type_id' => $validated['machine_type_id'] ?? null,
            'needle' => $validated['needle'] ?? '168N',
            'seam' => $validated['seam'] ?? 'ROSSO',
            'estimated_pcs' => $validated['estimated_pcs'],
            'sisa_pcs' => $validated['estimated_pcs'],
            'target_month' => $validated['target_month'],
            'delivery_date' => $validated['delivery_date'] ?? null,
            'lead_time_days' => 45,
            'notes' => $validated['notes'] ?? null,
            'status' => 'HOLD',
            'created_at' => now(),
        ]);

        $userId = auth()->id() ?? User::first()?->id ?? 1;

        AuditLog::create([
            'user_id' => $userId,
            'action' => 'CREATE_ORDER_BOOKING',
            'table_name' => 'order_bookings',
            'record_id' => $booking->id,
            'new_values' => [
                'booking_code' => $bookingCode,
                'buyer' => $validated['buyer_name'],
                'estimated_pcs' => $validated['estimated_pcs'],
            ],
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);

        return redirect()->route('bookings.index')
            ->with('success', "Slot booking pre-order {$bookingCode} ({$validated['buyer_name']}) berhasil didaftarkan.");
    }

    public function cancel(Request $request, OrderBooking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|min:3|max:255',
            'qty_cancel_pcs' => 'nullable|integer|min:1',
        ]);

        $cancelQty = $validated['qty_cancel_pcs'] ?? $booking->sisa_pcs;
        if ($cancelQty <= 0) {
            $cancelQty = $booking->estimated_pcs;
        }

        DB::transaction(function () use ($booking, $validated, $cancelQty) {
            OrderBookingCancellation::create([
                'order_booking_id' => $booking->id,
                'qty_cancel_pcs' => $cancelQty,
                'reason' => $validated['reason'],
                'created_at' => now(),
            ]);

            $booking->status = 'CANCELLED';
            $booking->sisa_pcs = max(0, $booking->sisa_pcs - $cancelQty);
            $booking->save();

            $userId = auth()->id() ?? User::first()?->id ?? 1;

            AuditLog::create([
                'user_id' => $userId,
                'action' => 'CANCEL_ORDER_BOOKING',
                'table_name' => 'order_bookings',
                'record_id' => $booking->id,
                'new_values' => [
                    'cancel_qty' => $cancelQty,
                    'reason' => $validated['reason'],
                ],
                'ip_address' => request()->ip(),
                'created_at' => now(),
            ]);
        });

        return redirect()->route('bookings.index')
            ->with('success', "Booking {$booking->booking_code} berhasil dibatalkan sebanyak {$cancelQty} pcs.");
    }
}
