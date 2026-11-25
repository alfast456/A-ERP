@extends('layouts.app')

@section('title', 'Target Produksi & Ekspor Bulanan')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Target Produksi & Ekspor Bulanan</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Penyusunan target kuota ekspor, pasar domestik, dan efisiensi pabrik bulanan.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('capacity.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50">
                &larr; Forecast Kapasitas
            </a>
            <button onclick="document.getElementById('targetModal').classList.remove('hidden')"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 shadow-sm transition">
                + Set Target Baru
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-lg bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <!-- Target Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-700/50 text-xs uppercase font-medium text-gray-500 dark:text-gray-400">
                        <th class="py-3.5 px-4">Bulan Target</th>
                        <th class="py-3.5 px-4">Plant Pabrik</th>
                        <th class="py-3.5 px-4 text-right">Target Ekspor (Pcs)</th>
                        <th class="py-3.5 px-4 text-right">Target Lokal (Pcs)</th>
                        <th class="py-3.5 px-4 text-right">Total Pasang</th>
                        <th class="py-3.5 px-4 text-center">Target OEE / Efisiensi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($targets as $t)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-750/50 transition">
                            <td class="py-3.5 px-4 font-bold text-gray-900 dark:text-white">
                                {{ $t->target_month }}
                            </td>
                            <td class="py-3.5 px-4 text-gray-600 dark:text-gray-300">
                                {{ $t->plant->name ?? 'Plant Utama' }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono font-bold text-blue-600 dark:text-blue-400">
                                {{ number_format($t->target_export_pcs) }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono text-gray-700 dark:text-gray-300">
                                {{ number_format($t->target_local_pcs) }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono font-extrabold text-brand-600 dark:text-brand-400">
                                {{ number_format($t->target_export_pcs + $t->target_local_pcs) }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">
                                    {{ $t->target_efficiency }}%
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-gray-500 dark:text-gray-400">
                                Belum ada target bulanan yang diset.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-gray-200 dark:border-gray-700">
            {{ $targets->links() }}
        </div>
    </div>
</div>

<!-- Modal Set Target -->
<div id="targetModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full p-6 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between pb-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Set Target Produksi Bulanan</h3>
            <button onclick="document.getElementById('targetModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <form action="{{ route('capacity.targets.store') }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Pilih Plant *</label>
                <select name="plant_id" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                    @foreach($plants as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Bulan Target (YYYY-MM) *</label>
                <input type="month" name="target_month" required value="{{ date('Y-m') }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Target Ekspor (Pcs) *</label>
                <input type="number" name="target_export_pcs" required value="500000" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Target Pasar Lokal (Pcs) *</label>
                <input type="number" name="target_local_pcs" required value="50000" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Target Efisiensi / OEE (%) *</label>
                <input type="number" step="0.1" name="target_efficiency" required value="85.0" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
            </div>
            <div class="flex justify-end gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button type="button" onclick="document.getElementById('targetModal').classList.add('hidden')" class="px-4 py-2 border rounded-lg text-sm text-gray-700 dark:text-gray-300">Batal</button>
                <button type="submit" class="px-4 py-2 bg-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700">Simpan Target</button>
            </div>
        </form>
    </div>
</div>
@endsection
