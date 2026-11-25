@extends('layouts.app')

@section('title', 'Alur WIP & Mutasi Antar-Departemen')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white tracking-tight">Alur WIP & Mutasi Antar-Departemen</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Pelacakan perpindahan fisik box kaos kaki dari Knitting, Rosso, Setting, hingga Packing & Gudang.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('wip.buffer') }}" class="px-3.5 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-sm font-semibold hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                📊 Staging Buffer Kanban
            </a>
            <a href="{{ route('wip.flows.index') }}" class="px-3.5 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-sm font-semibold hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                ⚙️ Master Template Flow
            </a>
            <a href="{{ route('wip.transfer.create') }}" class="px-4 py-2 rounded-xl bg-brand-500 hover:bg-brand-600 text-white text-sm font-semibold shadow-xs transition">
                + Kirim Box (Transfer)
            </a>
        </div>
    </div>

    <!-- 4 KPI Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700/60 p-5 shadow-xs">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Total Mutasi Box</span>
            <span class="text-2xl font-black text-gray-900 dark:text-white mt-1 block">{{ number_format($stats['total_movements']) }}</span>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700/60 p-5 shadow-xs">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Sedang Dikirim (In Transit)</span>
            <span class="text-2xl font-black text-amber-500 dark:text-amber-400 mt-1 block">{{ number_format($stats['dispatched_count']) }}</span>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700/60 p-5 shadow-xs">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Diterima Utuh</span>
            <span class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1 block">{{ number_format($stats['received_count']) }}</span>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700/60 p-5 shadow-xs">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Selisih Hitung (Discrepancy)</span>
            <span class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1 block">{{ number_format($stats['discrepancy_count']) }}</span>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white dark:bg-gray-800 p-4 rounded-2xl border border-gray-200 dark:border-gray-700/60 shadow-xs">
        <form action="{{ route('wip.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <div>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="">Semua Status</option>
                    <option value="DISPATCHED" {{ request('status') == 'DISPATCHED' ? 'selected' : '' }}>DISPATCHED (Dikirim)</option>
                    <option value="RECEIVED" {{ request('status') == 'RECEIVED' ? 'selected' : '' }}>RECEIVED (Diterima)</option>
                    <option value="DISCREPANCY" {{ request('status') == 'DISCREPANCY' ? 'selected' : '' }}>DISCREPANCY (Selisih)</option>
                </select>
            </div>
            <div>
                <select name="from_area_id" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="">Dari Area Asal</option>
                    @foreach($areas as $area)
                        <option value="{{ $area->id }}" {{ request('from_area_id') == $area->id ? 'selected' : '' }}>{{ $area->area_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <select name="to_area_id" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="">Ke Area Tujuan</option>
                    @foreach($areas as $area)
                        <option value="{{ $area->id }}" {{ request('to_area_id') == $area->id ? 'selected' : '' }}>{{ $area->area_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <input type="date" name="date" value="{{ request('date') }}" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-gray-900 dark:bg-gray-700 hover:bg-black text-white text-sm font-semibold rounded-xl transition">Filter</button>
                <a href="{{ route('wip.index') }}" class="px-3 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-semibold rounded-xl transition">Reset</a>
            </div>
        </form>
    </div>

    <!-- Movements Table -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700/60 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                <thead class="bg-gray-50/75 dark:bg-gray-900/50 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="px-4 py-3.5">Tanggal Mutasi</th>
                        <th class="px-4 py-3.5">Box / Order SKU</th>
                        <th class="px-4 py-3.5">Rute Perpindahan</th>
                        <th class="px-4 py-3.5 text-right">Kirim (Pcs)</th>
                        <th class="px-4 py-3.5 text-right">Terima (Pcs)</th>
                        <th class="px-4 py-3.5 text-center">Status</th>
                        <th class="px-4 py-3.5 text-center">Tindakan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700/60">
                    @forelse($movements as $m)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition">
                            <td class="px-4 py-3.5 text-xs text-gray-500">
                                {{ $m->movement_date ? $m->movement_date->format('d M Y') : '-' }}
                            </td>
                            <td class="px-4 py-3.5 text-xs">
                                <span class="font-bold text-gray-900 dark:text-white block">Box #{{ $m->productionBox?->no_box ?? '-' }}</span>
                                <span class="text-gray-500 font-mono">{{ $m->productionBox?->shipment?->orderStyle?->order?->po_number ?? '-' }} | {{ $m->productionBox?->shipment?->orderStyle?->style_code ?? '-' }}</span>
                            </td>
                            <td class="px-4 py-3.5 text-xs">
                                <div class="flex items-center gap-1.5 font-medium">
                                    <span class="text-gray-800 dark:text-gray-200">{{ $m->fromArea?->area_name ?? 'Area Asal' }}</span>
                                    <span class="text-gray-400">➔</span>
                                    <span class="text-brand-600 dark:text-brand-400 font-semibold">{{ $m->toArea?->area_name ?? 'Area Tujuan' }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3.5 text-right font-bold text-gray-900 dark:text-white">
                                {{ number_format($m->sent_pcs) }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-bold {{ $m->received_pcs !== null ? ($m->received_pcs != $m->sent_pcs ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400') : 'text-gray-400' }}">
                                {{ $m->received_pcs !== null ? number_format($m->received_pcs) : '-' }}
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                @if($m->status->value === 'DISPATCHED')
                                    <span class="inline-block px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                                        DIKIRIM
                                    </span>
                                @elseif($m->status->value === 'RECEIVED')
                                    <span class="inline-block px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                                        DITERIMA
                                    </span>
                                @else
                                    <span class="inline-block px-2.5 py-1 rounded-full text-xs font-bold bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-800">
                                        SELISIH
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                @if($m->status->value === 'DISPATCHED')
                                    <!-- Form Konfirmasi Terima Cepat -->
                                    <form action="{{ route('wip.receive', $m) }}" method="POST" class="inline-flex items-center gap-1.5">
                                        @csrf
                                        <input type="number" name="received_pcs" value="{{ $m->sent_pcs }}" min="0" required class="w-18 px-2 py-1 text-xs border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-900 font-bold text-center">
                                        <button type="submit" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg transition" title="Konfirmasi Terima Box">
                                            Terima
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-gray-400 italic">Selesai</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-500">
                                Belum ada riwayat mutasi box WIP.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($movements->hasPages())
            <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                {{ $movements->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
