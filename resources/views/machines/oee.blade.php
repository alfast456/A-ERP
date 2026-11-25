@extends('layouts.app')

@section('title', 'OEE Live Dashboard')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Overall Equipment Effectiveness (OEE)</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Metrik efektivitas manufaktur: Ketersediaan (Availability), Kinerja (Performance), dan Mutu (Quality).</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('machines.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50">
                &larr; Master Mesin
            </a>
            <a href="{{ route('downtimes.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 shadow-sm transition">
                + Input Downtime
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <form method="GET" action="{{ route('machines.oee') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Tanggal Analisa</label>
                <input type="date" name="date" value="{{ $date }}"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Filter Area</label>
                <select name="area_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                    <option value="">Semua Area (Pabrik Keseluruhan)</option>
                    @foreach($areas as $area)
                        <option value="{{ $area->id }}" {{ $areaId == $area->id ? 'selected' : '' }}>
                            {{ $area->area_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="px-5 py-2 bg-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700">
                    Hitung OEE
                </button>
                <a href="{{ route('machines.oee') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-50 dark:hover:bg-gray-700">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- OEE 4 Main Pillars Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- OEE Score -->
        <div class="bg-gradient-to-br from-indigo-600 to-brand-700 rounded-xl p-5 text-white shadow-md">
            <div class="text-xs font-semibold uppercase tracking-wider text-indigo-100">Overall OEE Rate</div>
            <div class="mt-2 text-4xl font-black">{{ $oeeRate }}%</div>
            <div class="mt-2 text-xs text-indigo-100">Target Standard Kelas Dunia: &ge; 85%</div>
        </div>

        <!-- Availability -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700 shadow-sm">
            <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Availability (A)</div>
            <div class="mt-2 text-3xl font-extrabold text-blue-600 dark:text-blue-400">{{ $availabilityRate }}%</div>
            <div class="mt-2 text-xs text-gray-500">Downtime: {{ number_format($totalDowntimeMinutes) }} menit</div>
        </div>

        <!-- Performance -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700 shadow-sm">
            <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Performance (P)</div>
            <div class="mt-2 text-3xl font-extrabold text-amber-600 dark:text-amber-400">{{ $performanceRate }}%</div>
            <div class="mt-2 text-xs text-gray-500">Output: {{ number_format($totalProductionPcs) }} pcs</div>
        </div>

        <!-- Quality -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700 shadow-sm">
            <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Quality (Q)</div>
            <div class="mt-2 text-3xl font-extrabold text-green-600 dark:text-green-400">{{ $qualityRate }}%</div>
            <div class="mt-2 text-xs text-gray-500">Defect: {{ number_format($totalDefectPcs) }} pcs</div>
        </div>
    </div>

    <!-- 2 Column Breakdown -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Area Summary -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
            <h3 class="text-base font-bold text-gray-900 dark:text-white mb-4">Availability per Area Pabrik</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700 text-xs font-semibold text-gray-500 uppercase">
                            <th class="pb-2">Area</th>
                            <th class="pb-2 text-center">Mesin</th>
                            <th class="pb-2 text-center">Downtime</th>
                            <th class="pb-2 text-right">Availability</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($areaSummaries as $asum)
                            <tr>
                                <td class="py-3 font-medium text-gray-900 dark:text-white">{{ $asum['area_name'] }}</td>
                                <td class="py-3 text-center text-gray-600 dark:text-gray-300">{{ $asum['machines'] }}</td>
                                <td class="py-3 text-center text-red-600 font-semibold">{{ $asum['downtime_minutes'] }} m</td>
                                <td class="py-3 text-right font-bold text-brand-600 dark:text-brand-400">{{ $asum['availability'] }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Downtime Reasons -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
            <h3 class="text-base font-bold text-gray-900 dark:text-white mb-4">Top 5 Kerusakan Terbesar (Pareto)</h3>
            <div class="space-y-3">
                @forelse($topReasons as $reason)
                    <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white text-sm">[{{ $reason->code }}] {{ $reason->name }}</div>
                            <div class="text-xs text-gray-400">{{ $reason->category }}</div>
                        </div>
                        <div class="text-right">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300">
                                {{ $reason->downtimes_count }} kali
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-gray-400 text-sm">Tidak ada catatan downtime pada tanggal ini.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
