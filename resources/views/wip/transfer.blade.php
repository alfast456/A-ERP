@extends('layouts.app')

@section('title', 'Kirim Transfer Box WIP')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white tracking-tight">Kirim Transfer Box WIP</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Pindahkan box rajut ke tahapan proses berikutnya (Rosso, Setting, Packing, Gudang).</p>
        </div>
        <a href="{{ route('wip.index') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-sm font-semibold rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition">
            ← Kembali
        </a>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700/60 p-6 shadow-xs">
        <form action="{{ route('wip.transfer.store') }}" method="POST" class="space-y-6">
            @csrf

            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Pilih Box Produksi Rajut *</label>
                <select name="production_box_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="">-- Pilih Nomor Box / Barcode --</option>
                    @foreach($boxes as $box)
                        <option value="{{ $box->id }}" {{ old('production_box_id') == $box->id ? 'selected' : '' }}>
                            Box #{{ $box->no_box }} | {{ $box->box_barcode ?? ('BX-' . $box->id) }} | PO: {{ $box->shipment?->orderStyle?->order?->po_number ?? '-' }} | Output: {{ number_format($box->good_pcs) }} pcs
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Departemen / Area Asal (Pengirim) *</label>
                    <select name="from_area_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <option value="">-- Pilih Area Asal --</option>
                        @foreach($areas as $area)
                            <option value="{{ $area->id }}" {{ old('from_area_id') == $area->id ? 'selected' : '' }}>{{ $area->area_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Departemen / Area Tujuan *</label>
                    <select name="to_area_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <option value="">-- Pilih Area Tujuan --</option>
                        @foreach($areas as $area)
                            <option value="{{ $area->id }}" {{ old('to_area_id') == $area->id ? 'selected' : '' }}>{{ $area->area_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Tanggal Pengiriman *</label>
                    <input type="date" name="movement_date" value="{{ old('movement_date', today()->toDateString()) }}" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Kuantitas Dikirim (Pcs) *</label>
                    <input type="number" name="sent_pcs" value="{{ old('sent_pcs', 240) }}" required min="1" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-900 text-brand-600 dark:text-brand-400 font-bold focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Catatan Tambahan (Remarks)</label>
                <textarea name="remarks" rows="3" placeholder="Contoh: Kirim ke Rosso meja 4..." class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">{{ old('remarks') }}</textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700/60">
                <a href="{{ route('wip.index') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">Batal</a>
                <button type="submit" class="px-6 py-2 bg-brand-500 hover:bg-brand-600 text-white text-sm font-semibold rounded-xl shadow-xs transition">Kirim Mutasi Box</button>
            </div>
        </form>
    </div>
</div>
@endsection
