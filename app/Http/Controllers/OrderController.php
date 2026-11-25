<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Exceptions\ZeroProductionLossException;
use App\Models\AuditLog;
use App\Models\MachineType;
use App\Models\Order;
use App\Models\OrderShipment;
use App\Models\OrderStyle;
use App\Models\User;
use App\Services\OrderReviseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Rap2hpoutre\FastExcel\FastExcel;

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

    public function create(): View
    {
        $machineTypes = MachineType::orderBy('needle_count')->get();

        return view('orders.create', compact('machineTypes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'po_number' => 'required|string|max:100|unique:orders,po_number',
            'buyer_name' => 'required|string|max:100',
            'season' => 'nullable|string|max:50',
            'order_date' => 'required|date',
            'style_code' => 'required|string|max:50',
            'color_code' => 'nullable|string|max:50',
            'size' => 'nullable|string|max:20',
            'jarum' => 'nullable|string|max:50',
            'machine_type_id' => 'nullable|exists:machine_types,id',
            'qty_pcs' => 'required|integer|min:1',
            'target_delivery_date' => 'required|date',
        ]);

        $userId = auth()->id() ?? User::first()?->id ?? 1;

        $order = DB::transaction(function () use ($validated, $userId) {
            $order = Order::create([
                'po_number' => $validated['po_number'],
                'buyer_name' => $validated['buyer_name'],
                'season' => $validated['season'] ?? 'REGULAR',
                'order_type' => OrderType::BULK,
                'total_ordered_pcs' => $validated['qty_pcs'],
                'order_date' => $validated['order_date'],
                'status' => OrderStatus::CONFIRMED,
            ]);

            $machineTypeId = $validated['machine_type_id'] ?? MachineType::first()?->id ?? 1;

            $style = OrderStyle::create([
                'order_id' => $order->id,
                'machine_type_id' => $machineTypeId,
                'jarum' => $validated['jarum'] ?? '168N',
                'style_code' => $validated['style_code'],
                'color_code' => $validated['color_code'] ?? 'SOLID',
                'size' => $validated['size'] ?? 'M',
                'seam' => 'ROSSO',
                'factory' => 'PLANT-1',
                'smv_seconds' => 140.0,
            ]);

            OrderShipment::create([
                'order_style_id' => $style->id,
                'shipment_index' => 1,
                'target_delivery_date' => $validated['target_delivery_date'],
                'qty_pcs' => $validated['qty_pcs'],
            ]);

            AuditLog::create([
                'user_id' => $userId,
                'action' => 'CREATE_MANUAL_ORDER',
                'table_name' => 'orders',
                'record_id' => $order->id,
                'new_values' => [
                    'po_number' => $validated['po_number'],
                    'buyer' => $validated['buyer_name'],
                    'qty_pcs' => $validated['qty_pcs'],
                ],
                'ip_address' => request()->ip(),
                'created_at' => now(),
            ]);

            return $order;
        });

        return redirect()->route('orders.show', $order)
            ->with('success', "Order SPK {$order->po_number} berhasil dibuat.");
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $order->status = OrderStatus::CANCELLED;
        $order->save();

        $userId = auth()->id() ?? User::first()?->id ?? 1;

        AuditLog::create([
            'user_id' => $userId,
            'action' => 'CANCEL_ORDER',
            'table_name' => 'orders',
            'record_id' => $order->id,
            'new_values' => [
                'status' => 'CANCELLED',
                'reason' => $validated['reason'],
            ],
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);

        return redirect()->route('orders.show', $order)
            ->with('success', "Order {$order->po_number} berhasil dibatalkan.");
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $userId = auth()->id() ?? User::first()?->id ?? 1;
        $importedCount = 0;

        DB::transaction(function () use ($request, $userId, &$importedCount) {
            $rows = (new FastExcel)->import($request->file('file'));

            foreach ($rows as $row) {
                $poNumber = trim($row['po_number'] ?? $row['PO'] ?? $row['no_po'] ?? '');
                $buyerName = trim($row['buyer_name'] ?? $row['Buyer'] ?? $row['buyer'] ?? 'Buyer Umum');
                $styleCode = trim($row['style_code'] ?? $row['Style'] ?? $row['no_model'] ?? 'STYLE-DEFAULT');
                $qtyPcs = (int) ($row['qty_pcs'] ?? $row['Qty'] ?? $row['qty'] ?? 0);
                $deliveryDate = $row['delivery_date'] ?? $row['Delivery'] ?? date('Y-m-d', strtotime('+30 days'));

                if (!$poNumber || $qtyPcs <= 0) {
                    continue;
                }

                $order = Order::firstOrCreate(
                    ['po_number' => $poNumber],
                    [
                        'buyer_name' => $buyerName,
                        'season' => 'REGULAR',
                        'order_type' => OrderType::BULK,
                        'total_ordered_pcs' => $qtyPcs,
                        'order_date' => now()->toDateString(),
                        'status' => OrderStatus::CONFIRMED,
                    ]
                );

                $style = OrderStyle::firstOrCreate(
                    [
                        'order_id' => $order->id,
                        'style_code' => $styleCode,
                    ],
                    [
                        'machine_type_id' => MachineType::first()?->id ?? 1,
                        'jarum' => '168N',
                        'color_code' => 'SOLID',
                        'size' => 'M',
                        'seam' => 'ROSSO',
                        'factory' => 'PLANT-1',
                        'smv_seconds' => 140.0,
                    ]
                );

                OrderShipment::create([
                    'order_style_id' => $style->id,
                    'shipment_index' => $style->orderShipments()->count() + 1,
                    'target_delivery_date' => date('Y-m-d', strtotime($deliveryDate)),
                    'qty_pcs' => $qtyPcs,
                ]);

                $importedCount++;
            }

            AuditLog::create([
                'user_id' => $userId,
                'action' => 'IMPORT_EXCEL_ORDERS',
                'table_name' => 'orders',
                'record_id' => 0,
                'new_values' => ['imported_rows' => $importedCount],
                'ip_address' => request()->ip(),
                'created_at' => now(),
            ]);
        });

        return redirect()->route('orders.index')
            ->with('success', "Berhasil mengimpor {$importedCount} data order dari Excel.");
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

        $user = auth()->user() ?? User::first();

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
