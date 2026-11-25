@extends('layouts.app')

@section('title', 'Input Order / SPK Manual')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Input Kontrak Order / SPK Baru</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Pendaftaran PO buyer, spesifikasi SKU style, alokasi tipe mesin, dan target delivery.</p>
        </div>
        <a href="{{ route('orders.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50">
            &larr; Kembali ke Daftar Order
        </a>
    </div>

    @if($errors->any())
        <div class="p-4 rounded-lg bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 text-sm">
            <ul class="list-disc pl-5 space-y-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <form action="{{ route('orders.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Informasi PO & Buyer -->
            <div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white mb-4 pb-2 border-b border-gray-100 dark:border-gray-700">1. Data Kontrak PO Buyer</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nomor PO *</label>
                        <input type="text" name="po_number" required value="{{ old('po_number') }}" placeholder="Contoh: PO-2026-NK-001"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-brand-500 focus:ring-brand-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Buyer *</label>
                        <input type="text" name="buyer_name" required value="{{ old('buyer_name') }}" placeholder="Contoh: NIKE, UNIQLO, PUMA"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-brand-500 focus:ring-brand-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Season</label>
                        <input type="text" name="season" value="{{ old('season', 'FW26') }}" placeholder="Contoh: SS26, FW26"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-brand-500 focus:ring-brand-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal Terbit Order *</label>
                        <input type="date" name="order_date" required value="{{ old('order_date', date('Y-m-d')) }}"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-brand-500 focus:ring-brand-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Total Kuantitas (Pcs) *</label>
                        <input type="number" name="qty_pcs" min="1" required value="{{ old('qty_pcs', 12000) }}"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-brand-500 focus:ring-brand-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Target Delivery Date *</label>
                        <input type="date" name="target_delivery_date" required value="{{ old('target_delivery_date', date('Y-m-d', strtotime('+45 days'))) }}"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-brand-500 focus:ring-brand-500">
                    </div>
                </div>
            </div>

            <!-- Spesifikasi Style -->
            <div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white mb-4 pb-2 border-b border-gray-100 dark:border-gray-700">2. Spesifikasi SKU Style Kaos Kaki</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kode Style / Model *</label>
                        <input type="text" name="style_code" required value="{{ old('style_code') }}" placeholder="Contoh: SX-5544"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-brand-500 focus:ring-brand-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kode / Nama Warna</label>
                        <input type="text" name="color_code" value="{{ old('color_code', 'BLACK/WHITE') }}" placeholder="Contoh: BLACK/WHITE"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-brand-500 focus:ring-brand-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ukuran / Size</label>
                        <input type="text" name="size" value="{{ old('size', 'L (42-45)') }}" placeholder="Contoh: M, L, XL"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-brand-500 focus:ring-brand-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipe Mesin & Jarum</label>
                        <select name="machine_type_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-brand-500 focus:ring-brand-500">
                            <option value="">-- Pilih Tipe Mesin --</option>
                            @foreach($machineTypes as $mt)
                                <option value="{{ $mt->id }}" {{ old('machine_type_id') == $mt->id ? 'selected' : '' }}>
                                    {{ $mt->brand }} {{ $mt->model_name }} ({{ $mt->needle_count }}N)
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('orders.index') }}" class="px-5 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2.5 rounded-lg text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 shadow-sm transition">
                    Simpan Order SPK
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
