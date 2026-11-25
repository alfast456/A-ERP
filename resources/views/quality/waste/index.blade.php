@extends('layouts.app')

@section('title', 'Log Limbah Mesin & Waste Rajut')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white tracking-tight">Log Limbah Mesin & Waste Rajut</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Pencatatan gramasi limbah benang, BS awal rajut, dan sisa waste per nomor mesin rajut.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('quality.index') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-sm font-semibold rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                ← Kembali ke QC Defect
            </a>
        </div>
    </div>

    <!-- 3 Summary KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700/60 p-5 shadow-xs">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Total Limbah (Kg)</span>
            <span class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1 block">{{ number_format($stats['total_waste_kg'], 2) }} <span class="text-xs font-normal">Kg</span></span>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700/60 p-5 shadow-xs">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Total Gramasi Waste</span>
            <span class="text-2xl font-black text-gray-900 dark:text-white mt-1 block">{{ number_format($stats['total_waste_gram']) }} <span class="text-xs font-normal">Gram</span></span>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700/60 p-5 shadow-xs">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Total Potongan BS</span>
            <span class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1 block">{{ number_format($stats['total_waste_pcs']) }} <span class="text-xs font-normal">Pcs</span></span>
        </div>
    </div>

    <!-- Layout Grid: Form Tambah + Tabel Log -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Form Tambah Waste -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700/60 p-5 shadow-xs h-fit space-y-4">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider">+ Catat Limbah Mesin</h3>
            <form action="{{ route('quality.waste.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Tanggal Produksi *</label>
                    <input type="date" name="production_date" value="{{ today()->toDateString() }}" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Area Pabrik *</label>
                    <select name="factory_area_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
                        <option value="">-- Pilih Area --</option>
                        @foreach($areas as $area)
                            <option value="{{ $area->id }}">{{ $area->area_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Nomor Mesin *</label>
                    <select name="machine_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
                        <option value="">-- Pilih Mesin --</option>
                        @foreach($machines as $mc)
                            <option value="{{ $mc->id }}">Mc #{{ $mc->machine_number }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Shift *</label>
                        <select name="shift" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
                            <option value="1">Shift A</option>
                            <option value="2">Shift B</option>
                            <option value="3">Shift C</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Berat (Gram) *</label>
                        <input type="number" step="0.1" name="weight_gram" required placeholder="0.0" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-900 text-rose-600 dark:text-rose-400 font-bold">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Jumlah Pcs BS</label>
                        <input type="number" name="qty_pcs" value="0" min="0" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Operator</label>
                        <input type="text" name="employee_name" placeholder="Nama Operator" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
                    </div>
                </div>

                <button type="submit" class="w-full py-2 bg-rose-600 hover:bg-rose-700 text-white text-sm font-semibold rounded-xl shadow-xs transition">
                    Simpan Catatan Limbah
                </button>
            </form>
        </div>

        <!-- Tabel Log Waste -->
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700/60 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                    <thead class="bg-gray-50/75 dark:bg-gray-900/50 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700">
                        <tr>
                            <th class="px-4 py-3.5">Tanggal / Shift</th>
                            <th class="px-4 py-3.5">Mesin & Area</th>
                            <th class="px-4 py-3.5">Operator</th>
                            <th class="px-4 py-3.5 text-right">Potongan Pcs</th>
                            <th class="px-4 py-3.5 text-right">Berat Limbah</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700/60">
                        @forelse($wasteLogs as $w)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition">
                                <td class="px-4 py-3.5 text-xs">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $w->production_date ? $w->production_date->format('d M Y') : '-' }}</div>
                                    <span class="text-gray-400">Shift {{ $w->shift == 1 ? 'A' : ($w->shift == 2 ? 'B' : 'C') }}</span>
                                </td>
                                <td class="px-4 py-3.5 text-xs">
                                    <span class="font-bold text-gray-900 dark:text-white">Mc #{{ $w->machine?->machine_number ?? '-' }}</span>
                                    <span class="text-gray-400 block">{{ $w->factoryArea?->area_name ?? '-' }}</span>
                                </td>
                                <td class="px-4 py-3.5 text-xs text-gray-700 dark:text-gray-300">
                                    {{ $w->employee_name ?? '-' }}
                                </td>
                                <td class="px-4 py-3.5 text-right font-mono text-xs">
                                    {{ number_format($w->qty_pcs) }} pcs
                                </td>
                                <td class="px-4 py-3.5 text-right font-mono text-xs font-bold text-rose-600 dark:text-rose-400">
                                    {{ number_format($w->weight_gram, 1) }} g
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-500">
                                    Belum ada log limbah mesin yang tercatat.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($wasteLogs->hasPages())
                <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                    {{ $wasteLogs->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
