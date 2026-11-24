@extends('layouts.app')

@section('title', 'Manajemen Order & Revisi')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Manajemen Order & Revisi</h1>
            <p class="text-sm text-slate-500">Kelola kontrak PO pembeli, varian SKU style, jadwal pengiriman, dan riwayat revisi.</p>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200">
        <form action="{{ route('orders.index') }}" method="GET" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nomor PO atau nama buyer..." class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
            </div>
            <div class="w-full sm:w-48">
                <select name="status" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <option value="">Semua Status</option>
                    <option value="DRAFT" {{ request('status') == 'DRAFT' ? 'selected' : '' }}>DRAFT</option>
                    <option value="CONFIRMED" {{ request('status') == 'CONFIRMED' ? 'selected' : '' }}>CONFIRMED</option>
                    <option value="IN_PLANNING" {{ request('status') == 'IN_PLANNING' ? 'selected' : '' }}>IN PLANNING</option>
                    <option value="IN_PRODUCTION" {{ request('status') == 'IN_PRODUCTION' ? 'selected' : '' }}>IN PRODUCTION</option>
                    <option value="COMPLETED" {{ request('status') == 'COMPLETED' ? 'selected' : '' }}>COMPLETED</option>
                    <option value="CANCELLED" {{ request('status') == 'CANCELLED' ? 'selected' : '' }}>CANCELLED</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white text-sm font-semibold rounded-lg transition">Filter</button>
            @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('orders.index') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold rounded-lg transition text-center">Reset</a>
            @endif
        </form>
    </div>

    <!-- Orders Table -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase font-semibold">
                    <tr>
                        <th class="px-6 py-3">PO Number</th>
                        <th class="px-6 py-3">Buyer</th>
                        <th class="px-6 py-3">Season</th>
                        <th class="px-6 py-3">Tipe</th>
                        <th class="px-6 py-3 text-right">Total Pasang</th>
                        <th class="px-6 py-3">Tgl Order</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($orders as $order)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 font-bold text-slate-900">{{ $order->po_number }}</td>
                            <td class="px-6 py-4 font-medium">{{ $order->buyer_name }}</td>
                            <td class="px-6 py-4 text-xs text-slate-500">{{ $order->season ?? '-' }}</td>
                            <td class="px-6 py-4">
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-700">
                                    {{ $order->order_type->value ?? $order->order_type }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right font-extrabold text-slate-900">{{ number_format($order->total_ordered_pairs) }}</td>
                            <td class="px-6 py-4 text-xs">{{ $order->order_date?->format('d M Y') ?? '-' }}</td>
                            <td class="px-6 py-4">
                                @php
                                    $statusClass = match($order->status->value ?? $order->status) {
                                        'CONFIRMED' => 'bg-blue-50 text-blue-700',
                                        'IN_PRODUCTION' => 'bg-emerald-50 text-emerald-700',
                                        'COMPLETED' => 'bg-slate-100 text-slate-700',
                                        'CANCELLED' => 'bg-rose-50 text-rose-700',
                                        default => 'bg-amber-50 text-amber-700',
                                    };
                                @endphp
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $statusClass }}">
                                    {{ $order->status->value ?? $order->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <a href="{{ route('orders.show', $order) }}" class="inline-flex items-center px-3 py-1 bg-slate-100 hover:bg-emerald-600 hover:text-white text-slate-700 text-xs font-semibold rounded transition">
                                    Detail & Revisi →
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-slate-400">
                                Tidak ada data order ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
            <div class="px-6 py-3 border-t border-slate-200">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
