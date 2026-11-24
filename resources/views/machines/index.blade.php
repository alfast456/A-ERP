@extends('layouts.app')

@section('title', 'Inventaris & Status Mesin Rajut')

@section('content')
<div class="space-y-6">
    <!-- Header & Quick Stats -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Inventaris & Telemetri Mesin Rajut</h1>
            <p class="text-sm text-slate-500 mt-1">Monitoring status operasional, lokasi area lantai pabrik, dan spesifikasi 3.036 unit mesin rajut.</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                <span class="w-2 h-2 rounded-full bg-emerald-500 mr-1.5"></span>
                Active Rate: {{ $activeRate }}%
            </span>
            <span class="text-xs text-slate-400">Total: <strong>{{ number_format($totalMachines) }}</strong> unit</span>
        </div>
    </div>

    <!-- Stat Metric Strip -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-sm">
            <span class="text-xs font-medium text-slate-500 uppercase tracking-wider block">Running / Beroperasi</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-emerald-600 tracking-tight">{{ number_format($runningCount) }}</span>
                <span class="text-xs text-slate-400">unit</span>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-sm">
            <span class="text-xs font-medium text-slate-500 uppercase tracking-wider block">Breakdown / Kerusakan</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-rose-600 tracking-tight">{{ number_format($breakdownCount) }}</span>
                <span class="text-xs text-slate-400">unit</span>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-sm">
            <span class="text-xs font-medium text-slate-500 uppercase tracking-wider block">Idle / Siap Jalan</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-amber-600 tracking-tight">{{ number_format($idleCount) }}</span>
                <span class="text-xs text-slate-400">unit</span>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-sm">
            <span class="text-xs font-medium text-slate-500 uppercase tracking-wider block">Maintenance / Servis</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-sky-600 tracking-tight">{{ number_format($maintenanceCount) }}</span>
                <span class="text-xs text-slate-400">unit</span>
            </div>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-sm">
        <form method="GET" action="{{ route('machines.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Cari No Mesin / Seri</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Contoh: KK9D-MC001" class="w-full text-sm border-slate-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Area Lantai Pabrik</label>
                <select name="area_id" class="w-full text-sm border-slate-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">Semua Area</option>
                    @foreach($factoryAreas as $area)
                        <option value="{{ $area->id }}" {{ request('area_id') == $area->id ? 'selected' : '' }}>{{ $area->area_name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Status Mesin</label>
                <select name="status" class="w-full text-sm border-slate-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">Semua Status</option>
                    <option value="RUNNING" {{ request('status') === 'RUNNING' ? 'selected' : '' }}>Running</option>
                    <option value="BREAKDOWN" {{ request('status') === 'BREAKDOWN' ? 'selected' : '' }}>Breakdown</option>
                    <option value="IDLE" {{ request('status') === 'IDLE' ? 'selected' : '' }}>Idle</option>
                    <option value="MAINTENANCE" {{ request('status') === 'MAINTENANCE' ? 'selected' : '' }}>Maintenance</option>
                    <option value="OFFLINE" {{ request('status') === 'OFFLINE' ? 'selected' : '' }}>Offline</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Ukuran Jarum</label>
                <select name="needle_count" class="w-full text-sm border-slate-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">Semua Jarum</option>
                    @foreach($needleCounts as $needle)
                        <option value="{{ $needle }}" {{ request('needle_count') == $needle ? 'selected' : '' }}>{{ $needle }} N</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 bg-slate-800 hover:bg-slate-900 text-white text-sm font-medium py-2 px-3 rounded-md transition shadow-sm">Filter</button>
                <a href="{{ route('machines.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm font-medium py-2 px-3 rounded-md transition">Reset</a>
            </div>
        </form>
    </div>

    <!-- Machine Data Grid -->
    <div class="bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-slate-600 font-semibold text-xs uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-4">Nomor Mesin</th>
                        <th class="py-3 px-4">Area Pabrik</th>
                        <th class="py-3 px-4">Merk & Tipe</th>
                        <th class="py-3 px-4">Spesifikasi (Cyl / Jarum)</th>
                        <th class="py-3 px-4">No. Seri Fisik</th>
                        <th class="py-3 px-4 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($machines as $mc)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3 px-4 font-mono font-semibold text-slate-900">
                                {{ $mc->machine_number }}
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700">
                                    {{ $mc->factoryArea?->area_name ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-slate-700 font-medium">
                                {{ $mc->machineType?->brand ?? 'LONATI' }}
                                <span class="text-xs text-slate-400 block">{{ $mc->machineType?->model_name ?? '-' }}</span>
                            </td>
                            <td class="py-3 px-4 font-mono text-slate-600">
                                {{ $mc->machineType?->cylinder_inches ?? '3.75' }}" / <strong>{{ $mc->machineType?->needle_count ?? 144 }}N</strong>
                            </td>
                            <td class="py-3 px-4 font-mono text-xs text-slate-500">
                                {{ $mc->serial_number ?: '-' }}
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if($mc->status->value === 'RUNNING')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                        ● Running
                                    </span>
                                @elseif($mc->status->value === 'BREAKDOWN')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-800">
                                        ● Breakdown
                                    </span>
                                @elseif($mc->status->value === 'IDLE')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                        ○ Idle
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-sky-100 text-sky-800">
                                        ◌ Sample
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400">
                                Tidak ada data mesin rajut yang sesuai dengan filter pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-200">
            {{ $machines->links() }}
        </div>
    </div>
</div>
@endsection
