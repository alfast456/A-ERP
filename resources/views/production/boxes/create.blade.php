@extends('layouts.app')

@section('title', 'Entri Output Box Rajut Baru')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white tracking-tight">Entri Box Produksi Rajut</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Pencatatan nomor box fisik, assign nomor mesin, kuantitas output, dan berat timter.</p>
        </div>
        <a href="{{ route('production.boxes.index') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-sm font-semibold rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition">
            ← Kembali
        </a>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700/60 p-6 shadow-xs">
        <form action="{{ route('production.boxes.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Section 1: Identitas Order & Mesin -->
            <div class="border-b border-gray-200 dark:border-gray-700/60 pb-5">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4">1. Identitas Order & Area Mesin</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Pilih Order Shipment / SKU Style *</label>
                        <select name="order_shipment_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                            <option value="">-- Pilih Kontrak PO / Style / Size --</option>
                            @foreach($shipments as $sh)
                                <option value="{{ $sh->id }}" {{ old('order_shipment_id') == $sh->id ? 'selected' : '' }}>
                                    {{ $sh->orderStyle?->order?->po_number ?? 'PO-' }} | Style: {{ $sh->orderStyle?->style_code ?? '-' }} | Size: {{ $sh->size }} | Qty: {{ number_format($sh->qty_pcs) }} pcs
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Area Lantai Pabrik *</label>
                        <select name="factory_area_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                            <option value="">-- Pilih Area --</option>
                            @foreach($areas as $area)
                                <option value="{{ $area->id }}" {{ old('factory_area_id') == $area->id ? 'selected' : '' }}>{{ $area->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Nomor Mesin Rajut *</label>
                        <select name="machine_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                            <option value="">-- Pilih Mesin --</option>
                            @foreach($machines as $mc)
                                <option value="{{ $mc->id }}" {{ old('machine_id') == $mc->id ? 'selected' : '' }}>Mesin #{{ $mc->machine_number }} ({{ $mc->machineType?->name ?? 'Knitting' }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Tanggal Produksi *</label>
                        <input type="date" name="production_date" value="{{ old('production_date', today()->toDateString()) }}" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Shift Kerja *</label>
                        <select name="shift" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                            <option value="1" {{ old('shift', '1') == '1' ? 'selected' : '' }}>Shift A (Pagi)</option>
                            <option value="2" {{ old('shift') == '2' ? 'selected' : '' }}>Shift B (Siang)</option>
                            <option value="3" {{ old('shift') == '3' ? 'selected' : '' }}>Shift C (Malam)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Section 2: Fisik Box & Output -->
            <div class="border-b border-gray-200 dark:border-gray-700/60 pb-5">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4">2. Detail Nomor Box Fisik & Kuantitas</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Nomor Box *</label>
                        <input type="text" name="no_box" value="{{ old('no_box') }}" required placeholder="Contoh: 101" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Nomor Label</label>
                        <input type="number" name="no_label" value="{{ old('no_label') }}" placeholder="Contoh: 1" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Good Output (Pcs) *</label>
                        <input type="number" name="good_pcs" value="{{ old('good_pcs', 240) }}" required min="0" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-900 text-emerald-600 dark:text-emerald-400 font-bold focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Cacat BS (Pcs)</label>
                        <input type="number" name="defect_pcs" value="{{ old('defect_pcs', 0) }}" min="0" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-900 text-rose-600 dark:text-rose-400 font-bold focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                </div>
            </div>

            <!-- Section 3: Verifikasi Berat Timter -->
            <div>
                <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4">3. Verifikasi Timbang Terus (Timter)</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Berat Fisik Box (Kg)</label>
                        <input type="number" step="0.001" name="weight_kg" value="{{ old('weight_kg') }}" placeholder="0.000" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <span class="text-[11px] text-gray-400 mt-1 block">Digunakan oleh QC Timter untuk kalkulasi gram per pasang kaos kaki.</span>
                    </div>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700/60">
                <a href="{{ route('production.boxes.index') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">Batal</a>
                <button type="submit" class="px-6 py-2 bg-brand-500 hover:bg-brand-600 text-white text-sm font-semibold rounded-xl shadow-xs transition">Simpan Box Produksi</button>
            </div>
        </form>
    </div>
</div>
@endsection
