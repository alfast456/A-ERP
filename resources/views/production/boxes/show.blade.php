@extends('layouts.app')

@section('title', 'Detail Box Produksi #' . $box->no_box)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white tracking-tight">Box Produksi Rajut #{{ $box->no_box }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Barcode: <span class="font-mono font-bold text-brand-600 dark:text-brand-400">{{ $box->box_barcode ?? ('BX-' . str_pad($box->id, 6, '0', STR_PAD_LEFT)) }}</span></p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('production.boxes.print-label', $box) }}" target="_blank" class="px-4 py-2 bg-brand-500 hover:bg-brand-600 text-white text-sm font-semibold rounded-xl shadow-xs transition">
                🏷️ Cetak Label Barcode
            </a>
            <a href="{{ route('production.boxes.index') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-sm font-semibold rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                ← Kembali
            </a>
        </div>
    </div>

    <!-- Info Box Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Card 1: Identitas Order -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700/60 p-5 shadow-xs space-y-3">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Identitas Kontrak & SKU</h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-700/40">
                    <span class="text-gray-500">Nomor PO Pembeli:</span>
                    <span class="font-bold text-gray-900 dark:text-white">{{ $box->shipment?->orderStyle?->order?->po_number ?? '-' }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-700/40">
                    <span class="text-gray-500">Buyer / Brand:</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $box->shipment?->orderStyle?->order?->buyer_name ?? '-' }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-700/40">
                    <span class="text-gray-500">Kode Style / Model:</span>
                    <span class="font-mono font-medium text-gray-900 dark:text-white">{{ $box->shipment?->orderStyle?->style_code ?? '-' }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-700/40">
                    <span class="text-gray-500">Ukuran (Size) & Warna:</span>
                    <span class="font-medium text-gray-900 dark:text-white">Size {{ $box->shipment?->size ?? '-' }} | {{ $box->shipment?->color ?? '-' }}</span>
                </div>
            </div>
        </div>

        <!-- Card 2: Mesin & Shift -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700/60 p-5 shadow-xs space-y-3">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Lantai Rajut & Mesin</h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-700/40">
                    <span class="text-gray-500">Area Pabrik:</span>
                    <span class="font-bold text-gray-900 dark:text-white">{{ $box->factoryArea?->name ?? '-' }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-700/40">
                    <span class="text-gray-500">Nomor Mesin:</span>
                    <span class="font-mono font-bold text-brand-600 dark:text-brand-400">Mc #{{ $box->machine?->machine_number ?? '-' }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-700/40">
                    <span class="text-gray-500">Tanggal Produksi:</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $box->production_date ? $box->production_date->format('d M Y') : '-' }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-700/40">
                    <span class="text-gray-500">Shift Kerja:</span>
                    <span class="font-bold text-gray-900 dark:text-white">Shift {{ $box->shift == 1 ? 'A' : ($box->shift == 2 ? 'B' : ($box->shift == 3 ? 'C' : $box->shift)) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 3: Rekap Kuantitas & Timbangan -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700/60 p-5 shadow-xs">
        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Hasil Output & Timbang Terus (Timter)</h3>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-center">
            <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-900/60 border border-gray-200 dark:border-gray-700/40">
                <span class="text-xs text-gray-400 block mb-1">Good Output</span>
                <span class="text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ number_format($box->good_pcs) }} <span class="text-xs font-normal">Pcs</span></span>
                <span class="text-[11px] text-gray-500 block mt-1">({{ round($box->good_pcs / 24, 2) }} Dz)</span>
            </div>
            <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-900/60 border border-gray-200 dark:border-gray-700/40">
                <span class="text-xs text-gray-400 block mb-1">Cacat BS</span>
                <span class="text-2xl font-black text-rose-600 dark:text-rose-400">{{ number_format($box->defect_pcs) }} <span class="text-xs font-normal">Pcs</span></span>
                <span class="text-[11px] text-gray-500 block mt-1">({{ round($box->defect_pcs / 24, 2) }} Dz)</span>
            </div>
            <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-900/60 border border-gray-200 dark:border-gray-700/40">
                <span class="text-xs text-gray-400 block mb-1">Berat Fisik Timbangan</span>
                <span class="text-2xl font-black text-gray-900 dark:text-white">{{ $box->weight_kg ? number_format($box->weight_kg, 2) : '0.00' }} <span class="text-xs font-normal">Kg</span></span>
            </div>
            <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-900/60 border border-gray-200 dark:border-gray-700/40">
                <span class="text-xs text-gray-400 block mb-1">Rata-rata Gram / Pasang</span>
                <span class="text-2xl font-black text-brand-600 dark:text-brand-400">
                    {{ ($box->good_pcs > 0 && $box->weight_kg > 0) ? round(($box->weight_kg * 1000) / $box->good_pcs, 1) : '-' }}
                    <span class="text-xs font-normal">Gr</span>
                </span>
            </div>
        </div>
    </div>
</div>
@endsection
