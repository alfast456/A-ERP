@extends('layouts.app')

@section('title', 'Audit Trail & Rekaman Sistem')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Enterprise Audit Trail & Log Sistem</h1>
            <p class="text-sm text-slate-500 mt-1">Penelusuran jejak aktivitas mutasi data dan aksi pengguna (1,27 juta rekaman).</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-xs text-slate-400">Total Log: <strong>{{ number_format($totalLogsCount) }}</strong></span>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-sm">
        <form method="GET" action="{{ route('audit.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Cari IP / ID / Modul</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Contoh: 192.168 atau ID order" class="w-full text-sm border-slate-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Modul / Tabel</label>
                <select name="table_name" class="w-full text-sm border-slate-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">Semua Modul</option>
                    @foreach($distinctModules as $mod)
                        <option value="{{ $mod }}" {{ request('table_name') === $mod ? 'selected' : '' }}>{{ $mod }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Jenis Aksi (Action)</label>
                <select name="action" class="w-full text-sm border-slate-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">Semua Aksi</option>
                    @foreach($distinctActions as $act)
                        <option value="{{ $act }}" {{ request('action') === $act ? 'selected' : '' }}>{{ $act }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 bg-slate-800 hover:bg-slate-900 text-white text-sm font-medium py-2 px-3 rounded-md transition shadow-sm">Filter</button>
                <a href="{{ route('audit.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm font-medium py-2 px-3 rounded-md transition">Reset</a>
            </div>
        </form>
    </div>

    <!-- Audit Log Table -->
    <div class="bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-slate-600 font-semibold text-xs uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-4">ID Log</th>
                        <th class="py-3 px-4">Waktu Kejadian</th>
                        <th class="py-3 px-4">Aksi</th>
                        <th class="py-3 px-4">Tabel / Modul</th>
                        <th class="py-3 px-4 font-mono">Record ID</th>
                        <th class="py-3 px-4">IP Address</th>
                        <th class="py-3 px-4">Payload Nilai Baru</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @forelse($logs as $l)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3 px-4 font-mono font-semibold text-slate-900">
                                #{{ $l->id }}
                            </td>
                            <td class="py-3 px-4 font-mono text-slate-500">
                                {{ $l->created_at?->format('d M Y H:i:s') ?? '-' }}
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold {{ in_array($l->action, ['CREATE', 'INSERT']) ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : (in_array($l->action, ['DELETE']) ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-slate-100 text-slate-700') }}">
                                    {{ $l->action }}
                                </span>
                            </td>
                            <td class="py-3 px-4 font-semibold text-slate-800">
                                {{ $l->table_name }}
                            </td>
                            <td class="py-3 px-4 font-mono text-slate-600">
                                {{ $l->record_id ?: '-' }}
                            </td>
                            <td class="py-3 px-4 font-mono text-slate-500">
                                {{ $l->ip_address ?: '-' }}
                            </td>
                            <td class="py-3 px-4 max-w-xs truncate font-mono text-slate-600" title="{{ is_array($l->new_values) ? json_encode($l->new_values) : $l->new_values }}">
                                {{ is_array($l->new_values) ? json_encode($l->new_values) : ($l->new_values ?: '-') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400">
                                Tidak ada log aktivitas yang cocok dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-200">
            {{ $logs->links() }}
        </div>
    </div>
</div>
@endsection
