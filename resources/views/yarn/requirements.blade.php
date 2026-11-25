@extends('layouts.app')

@section('title', 'Kebutuhan Benang Order & Alokasi PO')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Estimasi Kebutuhan Benang per PO</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Analisa perbandingan kebutuhan bahan baku benang order vs ketersediaan saldo lantai saat ini.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('yarn.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50">
                &larr; Saldo Benang
            </a>
            <a href="{{ route('yarn.transactions') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 shadow-sm transition">
                + Mutasi Benang
            </a>
        </div>
    </div>

    <!-- Available Stock Summary -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
        <h3 class="text-base font-bold text-gray-900 dark:text-white mb-3">Ringkasan Stok Benang Ready di Lantai</h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            @forelse($stockSummary->take(6) as $stk)
                <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-100 dark:border-gray-700">
                    <div class="text-xs font-bold text-gray-900 dark:text-white truncate">{{ $stk->yarn_code }}</div>
                    <div class="text-[11px] text-gray-400 truncate">{{ $stk->yarn_name }}</div>
                    <div class="mt-2 text-sm font-extrabold text-brand-600 dark:text-brand-400">{{ number_format($stk->total_weight, 1) }} kg</div>
                    <div class="text-[10px] text-gray-400">{{ number_format($stk->total_cones) }} cones</div>
                </div>
            @empty
                <div class="col-span-6 text-gray-400 text-sm">Tidak ada ringkasan stok.</div>
            @endforelse
        </div>
    </div>

    <!-- Order Demand Requirements Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-base font-bold text-gray-900 dark:text-white">Daftar PO & Estimasi Kebutuhan Rajut</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-700/50 text-xs uppercase font-medium text-gray-500 dark:text-gray-400">
                        <th class="py-3.5 px-4">PO / Buyer</th>
                        <th class="py-3.5 px-4">Style & Delivery</th>
                        <th class="py-3.5 px-4 text-center">Qty Order (Dzn)</th>
                        <th class="py-3.5 px-4 text-right">Est. Kebutuhan Benang</th>
                        <th class="py-3.5 px-4 text-center">Status Material</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($shipments as $shipment)
                        @php
                            // Benchmark: 1 dz roughly 0.45 kg yarn
                            $orderDzn = $shipment->order_qty_dz ?? round(($shipment->order_qty_pcs ?? 0) / 12, 1);
                            $estYarnKg = round($orderDzn * 0.45, 2);
                        @endphp
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-750/50 transition">
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-gray-900 dark:text-white">{{ $shipment->orderStyle->order->po_number ?? '-' }}</div>
                                <div class="text-xs text-gray-400">{{ $shipment->orderStyle->order->buyer_name ?? '-' }}</div>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $shipment->orderStyle->style_number ?? '-' }}</div>
                                <div class="text-xs text-gray-500">Deliv: {{ $shipment->delivery_date?->format('d M Y') ?? '-' }}</div>
                            </td>
                            <td class="py-3.5 px-4 text-center font-semibold text-gray-800 dark:text-gray-200">
                                {{ number_format($orderDzn) }} dzn
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono font-bold text-brand-600 dark:text-brand-400">
                                ~{{ number_format($estYarnKg, 2) }} kg
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                    STOK SIAP
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-gray-500 dark:text-gray-400">
                                Belum ada order aktif yang membutuhkan alokasi benang.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
