@extends('layouts.app')

@section('title', 'Catat Downtime Mesin')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Catat Downtime / Kerusakan Mesin</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Pencatatan waktu berhenti mesin rajut dan tindakan perbaikan teknisi.</p>
        </div>
        <a href="{{ route('downtimes.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50">
            &larr; Kembali ke Daftar
        </a>
    </div>

    @if($errors->any())
        <div class="p-4 rounded-lg bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300">
            <ul class="list-disc pl-5 space-y-1 text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <form action="{{ route('downtimes.store') }}" method="POST" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nomor Mesin *</label>
                    <select name="machine_id" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-brand-500 focus:ring-brand-500">
                        <option value="">-- Pilih Mesin --</option>
                        @foreach($machines as $machine)
                            <option value="{{ $machine->id }}" {{ old('machine_id') == $machine->id ? 'selected' : '' }}>
                                Mesin #{{ $machine->machine_number }} ({{ $machine->factoryArea->area_name ?? 'Area' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Penyebab Downtime *</label>
                    <select name="downtime_reason_id" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-brand-500 focus:ring-brand-500">
                        <option value="">-- Pilih Alasan Kerusakan --</option>
                        @foreach($reasons as $reason)
                            <option value="{{ $reason->id }}" {{ old('downtime_reason_id') == $reason->id ? 'selected' : '' }}>
                                [{{ $reason->code }}] {{ $reason->name }} ({{ $reason->category }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Waktu Mulai Berhenti *</label>
                    <input type="datetime-local" name="start_time" required value="{{ old('start_time', now()->subHours(1)->format('Y-m-d\TH:i')) }}"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-brand-500 focus:ring-brand-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Waktu Selesai / Mulai Nyala *</label>
                    <input type="datetime-local" name="end_time" required value="{{ old('end_time', now()->format('Y-m-d\TH:i')) }}"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-brand-500 focus:ring-brand-500">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tindakan Perbaikan / Catatan Teknisi</label>
                    <textarea name="action_taken" rows="3" placeholder="Contoh: Penggantian sinker plate & kalibrasi tension benang..."
                              class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-brand-500 focus:ring-brand-500">{{ old('action_taken') }}</textarea>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('downtimes.index') }}" class="px-5 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2.5 rounded-lg text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 shadow-sm transition">
                    Simpan Log Downtime
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
