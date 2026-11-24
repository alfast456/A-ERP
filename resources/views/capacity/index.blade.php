@extends('layouts.app')

@section('title', 'Perencanaan Kapasitas Pabrik')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Perencanaan Kapasitas & Beban Mesin</h1>
            <p class="text-sm text-slate-500 mt-1">Simulasi throughput harian pabrik, utilisasi mesin per area, dan proyeksi pemenuhan order.</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                OEE Standar: 85% (3 Shift 24 Jam)
            </span>
        </div>
    </div>

    <!-- Capacity Forecasting Highlights -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white border border-slate-200 rounded-lg p-5 shadow-sm">
            <span class="text-xs font-medium text-slate-500 uppercase tracking-wider block">Mesin Beroperasi</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-slate-900">{{ number_format($totalActiveMachines) }}</span>
                <span class="text-xs text-slate-400">dari {{ number_format($totalMachines) }} unit</span>
            </div>
            <div class="w-full bg-slate-100 rounded-full h-1.5 mt-3">
                <div class="bg-emerald-500 h-1.5 rounded-full" style="width: {{ $totalMachines > 0 ? ($totalActiveMachines / $totalMachines) * 100 : 0 }}%"></div>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-lg p-5 shadow-sm">
            <span class="text-xs font-medium text-slate-500 uppercase tracking-wider block">Kapasitas Harian / Mesin</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-emerald-600">{{ number_format($dailyCapacityPerMachine, 0) }}</span>
                <span class="text-xs text-slate-400">pcs/hari</span>
            </div>
            <span class="text-2xs text-slate-400 block mt-2">Asumsi SMV rata-rata: 140 detik</span>
        </div>

        <div class="bg-white border border-slate-200 rounded-lg p-5 shadow-sm">
            <span class="text-xs font-medium text-slate-500 uppercase tracking-wider block">Throughput Total Pabrik</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-indigo-600">{{ number_format($totalDailyPlantOutput, 0) }}</span>
                <span class="text-xs text-slate-400">pcs/hari</span>
            </div>
            <span class="text-2xs text-slate-400 block mt-2">Total potensi output serentak</span>
        </div>

        <div class="bg-white border border-slate-200 rounded-lg p-5 shadow-sm">
            <span class="text-xs font-medium text-slate-500 uppercase tracking-wider block">Estimasi Waktu Selesai</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-amber-600">{{ $estimatedDaysToComplete }}</span>
                <span class="text-xs text-slate-400">hari kerja</span>
            </div>
            <span class="text-2xs text-slate-400 block mt-2">Untuk {{ number_format($totalOrderedPcs) }} total pcs order</span>
        </div>
    </div>

    <!-- Factory Areas Capacity Grid -->
    <div class="bg-white border border-slate-200 rounded-lg p-6 shadow-sm">
        <h2 class="text-base font-bold text-slate-900 mb-4">Utilisasi & Sebaran Mesin Rajut per Area Pabrik</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($factoryAreas as $area)
                @php
                    $areaTotal = $area->machines_count;
                    $areaRunning = $area->running_machines_count;
                    $utilization = $areaTotal > 0 ? round(($areaRunning / $areaTotal) * 100, 1) : 0;
                @endphp
                <div class="border border-slate-200 rounded-lg p-4 bg-slate-50/50 hover:bg-slate-50 transition">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-slate-900 text-sm">{{ $area->area_name }}</span>
                        <span class="text-xs font-mono font-semibold px-2 py-0.5 rounded {{ $utilization >= 75 ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' }}">
                            {{ $utilization }}%
                        </span>
                    </div>
                    <div class="mt-3 flex items-baseline justify-between text-xs text-slate-600">
                        <span>Running: <strong class="text-slate-900">{{ number_format($areaRunning) }}</strong></span>
                        <span>Total: {{ number_format($areaTotal) }} mc</span>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-1.5 mt-2">
                        <div class="bg-emerald-600 h-1.5 rounded-full" style="width: {{ $utilization }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Active Orders Load -->
    <div class="bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-200 flex items-center justify-between">
            <h2 class="text-base font-bold text-slate-900">Antrean Beban Order Aktif (In Production)</h2>
            <a href="{{ route('orders.index') }}" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700">Lihat Semua Order →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-slate-600 font-semibold text-xs uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-4">Nomor PO</th>
                        <th class="py-3 px-4">Buyer</th>
                        <th class="py-3 px-4">Tanggal Order</th>
                        <th class="py-3 px-4 text-right">Total Pcs</th>
                        <th class="py-3 px-4 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @forelse($activeOrders as $ord)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3 px-4 font-mono font-bold text-slate-900">
                                <a href="{{ route('orders.show', $ord) }}" class="text-emerald-600 hover:underline">
                                    {{ $ord->po_number }}
                                </a>
                            </td>
                            <td class="py-3 px-4 text-slate-700 font-medium">
                                {{ $ord->buyer_name }}
                            </td>
                            <td class="py-3 px-4 font-mono text-slate-600">
                                {{ $ord->order_date?->format('d M Y') ?? '-' }}
                            </td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-slate-900">
                                {{ number_format($ord->total_ordered_pcs) }}
                            </td>
                            <td class="py-3 px-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-sky-100 text-sky-800">
                                    ● In Production
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-400">
                                Tidak ada order aktif saat ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
