@extends('layouts.app')

@section('title', 'Log Downtime & Keandalan Mesin')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Analisis Downtime & Keandalan Mesin</h1>
            <p class="text-sm text-slate-500 mt-1">Audit log kerusakan mesin rajut dan tindakan perbaikan teknisi (1,38 juta rekaman).</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('reports.export.downtimes') }}" class="inline-flex items-center px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-xs font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50">
                📥 Export Downtime Excel
            </a>
            <a href="{{ route('downtimes.create') }}" class="inline-flex items-center px-3.5 py-1.5 rounded-lg bg-brand-600 text-white text-xs font-semibold hover:bg-brand-700 shadow-sm">
                + Catat Downtime
            </a>
        </div>
    </div>

    <!-- Top 6 Downtime Reasons Card Strip -->
    <div class="bg-white border border-slate-200 rounded-lg p-5 shadow-sm">
        <h2 class="text-xs font-bold text-slate-600 uppercase tracking-wider mb-3">Top 6 Penyebab Downtime Terbanyak</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            @foreach($topReasons as $tr)
                <div class="bg-slate-50 border border-slate-200/80 rounded-md p-3">
                    <span class="text-xs font-mono font-bold text-slate-800 block truncate" title="{{ $tr->name }}">{{ $tr->name }}</span>
                    <div class="mt-2 flex items-baseline justify-between">
                        <span class="text-lg font-extrabold text-rose-600">{{ number_format($tr->downtimes_count) }}</span>
                        <span class="text-2xs text-slate-400">insiden</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-sm">
        <form method="GET" action="{{ route('downtimes.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Cari No Mesin / Tindakan</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Contoh: KK1A-MC001 atau jarum patah" class="w-full text-sm border-slate-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Kategori Alasan Kerusakan</label>
                <select name="reason_id" class="w-full text-sm border-slate-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">Semua Kategori</option>
                    @foreach($reasons as $r)
                        <option value="{{ $r->id }}" {{ request('reason_id') == $r->id ? 'selected' : '' }}>{{ $r->code }} - {{ $r->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Tanggal Kejadian</label>
                <input type="date" name="date" value="{{ request('date') }}" class="w-full text-sm border-slate-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 bg-slate-800 hover:bg-slate-900 text-white text-sm font-medium py-2 px-3 rounded-md transition shadow-sm">Filter</button>
                <a href="{{ route('downtimes.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm font-medium py-2 px-3 rounded-md transition">Reset</a>
            </div>
        </form>
    </div>

    <!-- Downtime Log Table -->
    <div class="bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-slate-600 font-semibold text-xs uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-4">ID Insiden</th>
                        <th class="py-3 px-4">Mesin & Area</th>
                        <th class="py-3 px-4">Alasan Breakdown</th>
                        <th class="py-3 px-4">Mulai Downtime</th>
                        <th class="py-3 px-4">Selesai</th>
                        <th class="py-3 px-4">Tindakan / Detail Perbaikan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @forelse($downtimes as $dt)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3 px-4 font-mono font-semibold text-slate-900">
                                #{{ $dt->id }}
                            </td>
                            <td class="py-3 px-4">
                                <span class="font-mono font-bold text-slate-800">{{ $dt->machine?->machine_number ?? 'MC-N/A' }}</span>
                                <span class="text-slate-400 block">{{ $dt->machine?->factoryArea?->area_name ?? '-' }}</span>
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                    {{ $dt->reason?->name ?? 'Kerusakan Mesin' }}
                                </span>
                            </td>
                            <td class="py-3 px-4 font-mono text-slate-600">
                                {{ $dt->start_time?->format('d M Y H:i') ?? '-' }}
                            </td>
                            <td class="py-3 px-4 font-mono text-slate-600">
                                {{ $dt->end_time?->format('d M Y H:i') ?? '-' }}
                            </td>
                            <td class="py-3 px-4 text-slate-600 max-w-md truncate" title="{{ $dt->action_taken }}">
                                {{ $dt->action_taken ?: '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400">
                                Tidak ada log downtime yang sesuai dengan kriteria filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-200">
            {{ $downtimes->links() }}
        </div>
    </div>
</div>
@endsection
