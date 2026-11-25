@extends('layouts.app')

@section('title', 'Logistik & Saldo Benang Lantai')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Saldo & Logistik Benang Lantai Rajut</h1>
            <p class="text-sm text-slate-500 mt-1">Monitoring stok benang aktif (cones & kilogram) per area lantai rajut.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('yarn.requirements') }}" class="inline-flex items-center px-3.5 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50">
                Kebutuhan PO (Demand)
            </a>
            <a href="{{ route('yarn.transactions') }}" class="inline-flex items-center px-3.5 py-2 rounded-lg bg-brand-600 text-white text-sm font-medium hover:bg-brand-700 shadow-sm">
                + Mutasi & Transaksi Benang
            </a>
        </div>
    </div>

    <!-- Metric Strip -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-sm">
            <span class="text-xs font-medium text-slate-500 uppercase tracking-wider block">Total Gulungan (Cones)</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-slate-900">{{ number_format($totalCones) }}</span>
                <span class="text-xs text-slate-400">cones</span>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-sm">
            <span class="text-xs font-medium text-slate-500 uppercase tracking-wider block">Total Berat Benang</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-emerald-600">{{ number_format($totalWeightKg, 2) }}</span>
                <span class="text-xs text-slate-400">kg</span>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-sm">
            <span class="text-xs font-medium text-slate-500 uppercase tracking-wider block">Varian Item / Lot Terdaftar</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-indigo-600">{{ number_format($totalLots) }}</span>
                <span class="text-xs text-slate-400">lot unik</span>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-sm">
        <form method="GET" action="{{ route('yarn.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Cari Kode / Warna / Lot</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Contoh: PE 30s atau LOT-01" class="w-full text-sm border-slate-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Area Lantai Rajut</label>
                <select name="area_id" class="w-full text-sm border-slate-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">Semua Area</option>
                    @foreach($factoryAreas as $area)
                        <option value="{{ $area->id }}" {{ request('area_id') == $area->id ? 'selected' : '' }}>{{ $area->area_name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 bg-slate-800 hover:bg-slate-900 text-white text-sm font-medium py-2 px-3 rounded-md transition shadow-sm">Filter</button>
                <a href="{{ route('yarn.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm font-medium py-2 px-3 rounded-md transition">Reset</a>
            </div>
        </form>
    </div>

    <!-- Yarn Table -->
    <div class="bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-slate-600 font-semibold text-xs uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-4">Area Rajut</th>
                        <th class="py-3 px-4">Kode Spesifikasi Benang</th>
                        <th class="py-3 px-4">Nama Item Benang</th>
                        <th class="py-3 px-4">Warna</th>
                        <th class="py-3 px-4">Nomor Lot</th>
                        <th class="py-3 px-4 text-right">Saldo (Cones)</th>
                        <th class="py-3 px-4 text-right">Berat Bersih (Kg)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @forelse($yarnStocks as $st)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3 px-4 font-semibold text-slate-800">
                                {{ $st->factoryArea?->area_name ?? 'Lantai Utama' }}
                            </td>
                            <td class="py-3 px-4 font-mono font-bold text-slate-900">
                                {{ $st->yarn_code }}
                            </td>
                            <td class="py-3 px-4 text-slate-700">
                                {{ $st->yarn_name }}
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700">
                                    {{ $st->color_name }}
                                </span>
                            </td>
                            <td class="py-3 px-4 font-mono text-slate-600">
                                {{ $st->lot_number }}
                            </td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-slate-900">
                                {{ number_format($st->qty_cones) }}
                            </td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-emerald-600">
                                {{ number_format($st->weight_kg, 3) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400">
                                Tidak ada data saldo benang yang cocok dengan kriteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-200">
            {{ $yarnStocks->links() }}
        </div>
    </div>
</div>
@endsection
