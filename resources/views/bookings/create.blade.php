@extends('layouts.app')

@section('title', 'Buat Booking Kapasitas Baru')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Form Pre-Order Booking Kapasitas</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Pemesanan alokasi kuota produksi rajut per buyer dan estimasi bulan pengiriman.</p>
        </div>
        <a href="{{ route('bookings.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50">
            &larr; Kembali ke Booking
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
        <form action="{{ route('bookings.store') }}" method="POST" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Buyer *</label>
                    <input type="text" name="buyer_name" required value="{{ old('buyer_name') }}" placeholder="Contoh: UNIQLO, PUMA, DECATHLON"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-brand-500 focus:ring-brand-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Estimasi Kuota (Pcs) *</label>
                    <input type="number" name="estimated_pcs" min="1" required value="{{ old('estimated_pcs', 12000) }}"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-brand-500 focus:ring-brand-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Target Bulan Produksi (YYYY-MM) *</label>
                    <input type="month" name="target_month" required value="{{ old('target_month', now()->addMonth()->format('Y-m')) }}"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-brand-500 focus:ring-brand-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Perkiraan Delivery Date</label>
                    <input type="date" name="delivery_date" value="{{ old('delivery_date', now()->addMonths(2)->format('Y-m-d')) }}"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-brand-500 focus:ring-brand-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipe Mesin & Jarum</label>
                    <select name="machine_type_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-brand-500 focus:ring-brand-500">
                        <option value="">-- Bebas / Belum Ditentukan --</option>
                        @foreach($machineTypes as $mt)
                            <option value="{{ $mt->id }}" {{ old('machine_type_id') == $mt->id ? 'selected' : '' }}>
                                {{ $mt->brand }} {{ $mt->model_name }} ({{ $mt->needle_count }}N)
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Jenis Jahit / Seam</label>
                    <input type="text" name="seam" value="{{ old('seam', 'ROSSO') }}" placeholder="ROSSO, AUTOMATIC LINKING, HAND LINKING"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-brand-500 focus:ring-brand-500">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Catatan Tambahan / Spesifikasi Khusus</label>
                    <textarea name="notes" rows="3" placeholder="Contoh: Menunggu approval yarn sample lab dip..."
                              class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-brand-500 focus:ring-brand-500">{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('bookings.index') }}" class="px-5 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2.5 rounded-lg text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 shadow-sm transition">
                    Simpan Pre-Order Booking
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
