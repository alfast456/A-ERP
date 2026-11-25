@extends('layouts.app')

@section('title', 'Rekap Timter (Timbang Terus)')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white tracking-tight">Timter: Timbang Terus & Akurasi Output</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Verifikasi bobot timbangan box harian per nomor mesin rajut dan shift kerja.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('production.boxes.index') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-sm font-semibold rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                📦 Daftar Box Rajut
            </a>
            <a href="{{ route('production.boxes.create') }}" class="px-4 py-2 rounded-xl bg-brand-500 hover:bg-brand-600 text-white text-sm font-semibold shadow-xs transition">
                + Entri Box Baru
            </a>
        </div>
    </div>

    <!-- 3 Summary KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700/60 p-5 shadow-xs">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Tanggal Rekap</span>
            <span class="text-xl font-black text-gray-900 dark:text-white mt-1 block">{{ \Carbon\Carbon::parse($stats['date'])->format('d F Y') }}</span>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700/60 p-5 shadow-xs">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Total Output Terverifikasi</span>
            <span class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1 block">{{ number_format($stats['total_pcs']) }} <span class="text-xs font-normal text-gray-400">Pcs</span></span>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700/60 p-5 shadow-xs">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Total Bobot Timbangan</span>
            <span class="text-2xl font-black text-brand-600 dark:text-brand-400 mt-1 block">{{ number_format($stats['total_weight_kg'], 2) }} <span class="text-xs font-normal text-gray-400">Kg</span></span>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white dark:bg-gray-800 p-4 rounded-2xl border border-gray-200 dark:border-gray-700/60 shadow-xs">
        <form action="{{ route('production.timter') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Tanggal Produksi</label>
                <input type="date" name="date" value="{{ $date }}" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Area Pabrik</label>
                <select name="area_id" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="">Semua Area</option>
                    @foreach($areas as $area)
                        <option value="{{ $area->id }}" {{ $areaId == $area->id ? 'selected' : '' }}>{{ $area->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Tipe Mesin (Jarum)</label>
                <select name="machine_type_id" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="">Semua Tipe Mesin</option>
                    @foreach($machineTypes as $mt)
                        <option value="{{ $mt->id }}" {{ $machineTypeId == $mt->id ? 'selected' : '' }}>{{ $mt->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-gray-900 dark:bg-gray-700 hover:bg-black text-white text-sm font-semibold rounded-xl transition">Tampilkan</button>
                <a href="{{ route('production.timter') }}" class="px-3 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-semibold rounded-xl transition">Reset</a>
            </div>
        </form>
    </div>

    <!-- Timter Summary Matrix Table -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700/60 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                <thead class="bg-gray-50/75 dark:bg-gray-900/50 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="px-4 py-3.5">No Mesin</th>
                        <th class="px-4 py-3.5">Tipe Mesin</th>
                        <th class="px-4 py-3.5">Nomor PO & Style</th>
                        <th class="px-4 py-3.5 text-right">Shift A</th>
                        <th class="px-4 py-3.5 text-right">Shift B</th>
                        <th class="px-4 py-3.5 text-right">Shift C</th>
                        <th class="px-4 py-3.5 text-right">Total Pcs</th>
                        <th class="px-4 py-3.5 text-right">Total Dz</th>
                        <th class="px-4 py-3.5 text-right">Total Kg</th>
                        <th class="px-4 py-3.5 text-right">Gram / Pcs</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700/60">
                    @forelse($timterSummary as $row)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition">
                            <td class="px-4 py-3.5 font-mono font-bold text-gray-900 dark:text-white">
                                Mc #{{ $row->machine_number }}
                            </td>
                            <td class="px-4 py-3.5 text-xs">
                                {{ $row->machine_type }}
                            </td>
                            <td class="px-4 py-3.5 text-xs">
                                <span class="font-bold text-gray-900 dark:text-white block">{{ $row->po_number }}</span>
                                <span class="text-gray-500">{{ $row->style_code }}</span>
                            </td>
                            <td class="px-4 py-3.5 text-right font-mono text-xs">
                                {{ number_format($row->shift_a) }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-mono text-xs">
                                {{ number_format($row->shift_b) }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-mono text-xs">
                                {{ number_format($row->shift_c) }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-bold text-emerald-600 dark:text-emerald-400">
                                {{ number_format($row->total_pcs) }}
                            </td>
                            <td class="px-4 py-3.5 text-right text-xs">
                                {{ number_format($row->total_dz, 1) }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-mono text-xs font-semibold text-gray-900 dark:text-white">
                                {{ number_format($row->total_weight_kg, 2) }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-mono text-xs text-brand-600 dark:text-brand-400">
                                {{ $row->avg_gram_per_pair > 0 ? $row->avg_gram_per_pair . ' g' : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-10 text-center text-sm text-gray-500">
                                Tidak ada data output box yang tercatat pada tanggal {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
