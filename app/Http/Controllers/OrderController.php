<?php

namespace App\Http\Controllers;

use App\Exceptions\ZeroProductionLossException;
use App\Models\Order;
use App\Services\OrderReviseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $query = Order::with('orderStyles.orderShipments');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                  ->orWhere('buyer_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $orders = $query->orderByDesc('order_date')->paginate(10);

        return view('orders.index', compact('orders'));
    }

    public function show(Order $order): View
    {
        $order->load([
            'orderStyles.orderShipments.productionBoxes',
            'orderStyles.machineType',
            'revisions.items.shipment.orderStyle',
            'revisions.requester',
            'revisions.approver',
        ]);

        return view('orders.show', compact('order'));
    }

    public function revise(Request $request, Order $order, OrderReviseService $reviseService): RedirectResponse
    {
        $validated = $request->validate([
            'reason_notes' => 'required|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.shipment_id' => 'required|integer|exists:order_shipments,id',
            'items.*.new_qty' => 'required|integer|min:0',
            'items.*.new_delivery_date' => 'nullable|date',
        ]);

        $user = auth()->user() ?? \App\Models\User::first();

        try {
            $reviseService->applyRevision(
                $order,
                $validated['items'],
                $user,
                $user,
                $validated['reason_notes']
            );

            return redirect()
                ->route('orders.show', $order)
                ->with('success', 'Revisi order berhasil disimpan dan selisih kuantitas tercatat di log audit.');
        } catch (ZeroProductionLossException $e) {
            return redirect()
                ->route('orders.show', $order)
                ->withErrors(['revision' => $e->getMessage()]);
        }
    }
}
