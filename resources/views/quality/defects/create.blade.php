@extends('layouts.app')

@section('title', 'Lapor Temuan Cacat Box (BS)')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white tracking-tight">Lapor Temuan Cacat (BS)</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Pencatatan jenis reject / defect pada box produksi rajut dan disposisi perbaikan.</p>
        </div>
        <a href="{{ route('quality.index') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-sm font-semibold rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition">
            ← Kembali
        </a>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700/60 p-6 shadow-xs">
        <form action="{{ route('quality.defects.store') }}" method="POST" class="space-y-5">
            @csrf

            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Pilih Box Produksi *</label>
                <select name="production_box_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="">-- Pilih Box Produksi --</option>
                    @foreach($boxes as $box)
                        <option value="{{ $box->id }}">
                            Box #{{ $box->no_box }} | {{ $box->box_barcode ?? ('BX-' . $box->id) }} | PO: {{ $box->shipment?->orderStyle?->order?->po_number ?? '-' }} (Area: {{ $box->factoryArea?->area_name ?? '-' }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Jenis Cacat / Defect Type *</label>
                <select name="defect_type_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="">-- Pilih Jenis Cacat --</option>
                    @foreach($defectTypes as $dt)
                        <option value="{{ $dt->id }}">
                            [{{ $dt->defect_code }}] {{ $dt->defect_name }} (Kategori: {{ $dt->category }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Jumlah Cacat (Pcs) *</label>
                <input type="number" name="defect_pcs" value="1" min="1" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-900 text-rose-600 dark:text-rose-400 font-bold focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Keterangan / Temuan QC</label>
                <textarea name="notes" rows="3" placeholder="Contoh: Lubang di bagian tumit karena jarum patah..." class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500"></textarea>
            </div>

            <div class="flex items-center gap-2 p-3 bg-gray-50 dark:bg-gray-900/60 rounded-xl border border-gray-200 dark:border-gray-700/40">
                <input type="checkbox" id="create_rework" name="create_rework" value="1" checked class="w-4 h-4 text-brand-600 rounded border-gray-300 focus:ring-brand-500">
                <label for="create_rework" class="text-xs font-medium text-gray-700 dark:text-gray-300">
                    Otomatis kirim kaos kaki cacat ini ke jalur perbaikan (Rework Tracking)
                </label>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700/60">
                <a href="{{ route('quality.index') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">Batal</a>
                <button type="submit" class="px-6 py-2 bg-rose-600 hover:bg-rose-700 text-white text-sm font-semibold rounded-xl shadow-xs transition">Simpan Laporan Defect</button>
            </div>
        </form>
    </div>
</div>
@endsection
