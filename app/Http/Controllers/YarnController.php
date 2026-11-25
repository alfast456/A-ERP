<?php

namespace App\Http\Controllers;

use App\Enums\YarnTransactionType;
use App\Models\AuditLog;
use App\Models\FactoryArea;
use App\Models\OrderShipment;
use App\Models\User;
use App\Models\YarnStockBalance;
use App\Models\YarnTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class YarnController extends Controller
{
    public function index(Request $request): View
    {
        $query = YarnStockBalance::with('factoryArea');

        if ($request->filled('area_id')) {
            $query->where('factory_area_id', $request->input('area_id'));
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('yarn_code', 'like', "%{$search}%")
                  ->orWhere('yarn_name', 'like', "%{$search}%")
                  ->orWhere('color_name', 'like', "%{$search}%")
                  ->orWhere('lot_number', 'like', "%{$search}%");
            });
        }

        $yarnStocks = $query->orderBy('yarn_code')->paginate(20)->withQueryString();

        $totalLots = YarnStockBalance::count();
        $totalCones = (int) YarnStockBalance::sum('qty_cones');
        $totalWeightKg = (float) YarnStockBalance::sum('weight_kg');

        $factoryAreas = FactoryArea::orderBy('area_name')->get();

        return view('yarn.index', compact(
            'yarnStocks',
            'totalLots',
            'totalCones',
            'totalWeightKg',
            'factoryAreas'
        ));
    }

    public function transactions(Request $request): View
    {
        $query = YarnTransaction::with(['balance.factoryArea', 'operator'])->latest('transaction_time');

        if ($request->filled('type')) {
            $query->where('transaction_type', $request->input('type'));
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                  ->orWhereHas('balance', fn ($b) => $b->where('yarn_code', 'like', "%{$search}%")
                                                      ->orWhere('lot_number', 'like', "%{$search}%"));
            });
        }

        $transactions = $query->paginate(20)->withQueryString();
        $balances = YarnStockBalance::orderBy('yarn_code')->take(100)->get();

        return view('yarn.transactions', compact('transactions', 'balances'));
    }

    public function storeTransaction(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'yarn_stock_balance_id' => 'required|exists:yarn_stock_balances,id',
            'transaction_type' => 'required|in:RECEIPT_FROM_WH,RETURN_TO_WH,USAGE_KNITTING,ADJUSTMENT',
            'cones_delta' => 'required|integer',
            'weight_kg_delta' => 'required|numeric',
            'reference_number' => 'required|string|max:100',
        ]);

        $userId = auth()->id() ?? User::first()?->id ?? 1;

        DB::transaction(function () use ($validated, $userId) {
            $balance = YarnStockBalance::lockForUpdate()->findOrFail($validated['yarn_stock_balance_id']);

            $conesDelta = (int) $validated['cones_delta'];
            $weightDelta = (float) $validated['weight_kg_delta'];

            // Adjust sign based on transaction type if positive submitted for deduction
            if (in_array($validated['transaction_type'], ['RETURN_TO_WH', 'USAGE_KNITTING'])) {
                $conesDelta = -abs($conesDelta);
                $weightDelta = -abs($weightDelta);
            } elseif ($validated['transaction_type'] === 'RECEIPT_FROM_WH') {
                $conesDelta = abs($conesDelta);
                $weightDelta = abs($weightDelta);
            }

            $tx = YarnTransaction::create([
                'yarn_stock_balance_id' => $balance->id,
                'transaction_type' => $validated['transaction_type'],
                'cones_delta' => $conesDelta,
                'weight_kg_delta' => $weightDelta,
                'reference_number' => $validated['reference_number'],
                'performed_by' => $userId,
                'transaction_time' => now(),
            ]);

            // Update balance
            $balance->qty_cones = max(0, $balance->qty_cones + $conesDelta);
            $balance->weight_kg = max(0, (float) $balance->weight_kg + $weightDelta);
            $balance->save();

            AuditLog::create([
                'user_id' => $userId,
                'action' => 'RECORD_YARN_TRANSACTION',
                'table_name' => 'yarn_transactions',
                'record_id' => $tx->id,
                'new_values' => [
                    'yarn_stock_balance_id' => $balance->id,
                    'type' => $validated['transaction_type'],
                    'cones_delta' => $conesDelta,
                    'weight_delta' => $weightDelta,
                    'reference' => $validated['reference_number'],
                ],
                'ip_address' => request()->ip(),
                'created_at' => now(),
            ]);
        });

        return redirect()->route('yarn.transactions')
            ->with('success', "Transaksi mutasi benang {$validated['reference_number']} berhasil dibukukan.");
    }

    public function requirements(Request $request): View
    {
        $shipments = OrderShipment::with(['orderStyle.order'])
            ->whereHas('orderStyle.order', fn ($q) => $q->where('status', 'ACTIVE'))
            ->take(50)
            ->get();

        $stockSummary = YarnStockBalance::selectRaw('yarn_code, yarn_name, SUM(qty_cones) as total_cones, SUM(weight_kg) as total_weight')
            ->groupBy('yarn_code', 'yarn_name')
            ->get();

        return view('yarn.requirements', compact('shipments', 'stockSummary'));
    }
}
