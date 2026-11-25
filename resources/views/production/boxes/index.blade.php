@extends('layouts.app')

@section('title', 'Daftar Box Produksi Rajut')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white tracking-tight">Lantai Produksi: Output Box Rajut</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Pencatatan output box per mesin, shift kerja, verifikasi barcode, dan integrasi sisa rajut.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('production.timter') }}" class="px-3.5 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-sm font-semibold hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                ⚖️ Timter (Timbang Terus)
            </a>
            <a href="{{ route('production.boxes.create') }}" class="px-4 py-2 rounded-xl bg-brand-500 hover:bg-brand-600 text-white text-sm font-semibold shadow-xs transition">
                + Entri Box Baru
            </a>
        </div>
    </div>

    <!-- 4 Summary KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700/60 p-5 shadow-xs">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Total Box Terdaftar</span>
            <span class="text-2xl font-black text-gray-900 dark:text-white mt-1 block">{{ number_format($stats['total_boxes']) }} <span class="text-xs font-normal text-gray-400">Box</span></span>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700/60 p-5 shadow-xs">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Total Good Output</span>
            <span class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1 block">{{ number_format($stats['total_good_pcs']) }} <span class="text-xs font-normal text-gray-400">Pcs</span></span>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700/60 p-5 shadow-xs">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Total Cacat (BS)</span>
            <span class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1 block">{{ number_format($stats['total_defect_pcs']) }} <span class="text-xs font-normal text-gray-400">Pcs</span></span>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700/60 p-5 shadow-xs">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Output Hari Ini</span>
            <span class="text-2xl font-black text-brand-600 dark:text-brand-400 mt-1 block">{{ number_format($stats['today_output_pcs']) }} <span class="text-xs font-normal text-gray-400">Pcs</span></span>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white dark:bg-gray-800 p-4 rounded-2xl border border-gray-200 dark:border-gray-700/60 shadow-xs">
        <form action="{{ route('production.boxes.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <div class="lg:col-span-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari barcode box, no box, no label, nomor PO..." class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>
            <div>
                <select name="area_id" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="">Semua Area</option>
                    @foreach($areas as $area)
                        <option value="{{ $area->id }}" {{ request('area_id') == $area->id ? 'selected' : '' }}>{{ $area->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <select name="shift" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="">Semua Shift</option>
                    <option value="1" {{ request('shift') == '1' ? 'selected' : '' }}>Shift A (1)</option>
                    <option value="2" {{ request('shift') == '2' ? 'selected' : '' }}>Shift B (2)</option>
                    <option value="3" {{ request('shift') == '3' ? 'selected' : '' }}>Shift C (3)</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-gray-900 dark:bg-gray-700 hover:bg-black text-white text-sm font-semibold rounded-xl transition">Filter</button>
                @if(request()->hasAny(['search', 'area_id', 'shift', 'date']))
                    <a href="{{ route('production.boxes.index') }}" class="px-3 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-semibold rounded-xl transition">Reset</a>
                @endif
            </div>
        </form>
    </div>

    <!-- Boxes Table -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700/60 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                <thead class="bg-gray-50/75 dark:bg-gray-900/50 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="px-4 py-3.5">Barcode / No Box</th>
                        <th class="px-4 py-3.5">Tanggal / Shift</th>
                        <th class="px-4 py-3.5">Area & Mesin</th>
                        <th class="px-4 py-3.5">Order / PO & Style</th>
                        <th class="px-4 py-3.5 text-right">Good Pcs</th>
                        <th class="px-4 py-3.5 text-right">Cacat BS</th>
                        <th class="px-4 py-3.5 text-right">Berat (Kg)</th>
                        <th class="px-4 py-3.5 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700/60">
                    @forelse($boxes as $box)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition">
                            <td class="px-4 py-3.5 font-medium text-gray-900 dark:text-white">
                                <span class="font-mono text-xs block text-brand-600 dark:text-brand-400">{{ $box->box_barcode ?? ('BX-' . str_pad($box->id, 6, '0', STR_PAD_LEFT)) }}</span>
                                <span class="text-xs text-gray-500">Box #{{ $box->no_box }} @if($box->no_label) | Lbl #{{ $box->no_label }} @endif</span>
                            </td>
                            <td class="px-4 py-3.5 text-xs">
                                <div>{{ $box->production_date ? $box->production_date->format('d M Y') : '-' }}</div>
                                <span class="inline-block mt-0.5 px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                    Shift {{ $box->shift == 1 ? 'A' : ($box->shift == 2 ? 'B' : ($box->shift == 3 ? 'C' : $box->shift)) }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-xs">
                                <div class="font-semibold text-gray-800 dark:text-gray-200">{{ $box->factoryArea?->name ?? 'Area -' }}</div>
                                <div class="text-gray-500">Mc #{{ $box->machine?->machine_number ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3.5 text-xs">
                                <div class="font-bold text-gray-900 dark:text-white">{{ $box->shipment?->orderStyle?->order?->po_number ?? '-' }}</div>
                                <div class="text-gray-500 truncate max-w-xs">{{ $box->shipment?->orderStyle?->style_code ?? '-' }} | Size {{ $box->shipment?->size ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3.5 text-right font-bold text-emerald-600 dark:text-emerald-400">
                                {{ number_format($box->good_pcs) }}
                            </td>
                            <td class="px-4 py-3.5 text-right text-xs {{ $box->defect_pcs > 0 ? 'font-bold text-rose-600 dark:text-rose-400' : 'text-gray-400' }}">
                                {{ number_format($box->defect_pcs) }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-mono text-xs">
                                {{ $box->weight_kg ? number_format($box->weight_kg, 2) . ' kg' : '-' }}
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="{{ route('production.boxes.show', $box) }}" class="p-1.5 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition" title="Lihat Detail Box">
                                        👁️
                                    </a>
                                    <a href="{{ route('production.boxes.print-label', $box) }}" target="_blank" class="p-1.5 rounded-lg text-brand-600 dark:text-brand-400 hover:bg-brand-50 dark:hover:bg-brand-950/40 transition" title="Cetak Label Barcode">
                                        🏷️
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-sm text-gray-500">
                                Tidak ada data box produksi yang sesuai filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($boxes->hasPages())
            <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                {{ $boxes->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
