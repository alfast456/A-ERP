@extends('layouts.app')

@section('title', 'Executive Manufacturing Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Header Page -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Executive Manufacturing Dashboard</h1>
            <p class="text-sm text-slate-500">Pemantauan menyeluruh seluruh lini manufaktur rajut, status 3.036 mesin, mutu, sisa order, dan logistik bahan baku.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('production.monitoring') }}" class="inline-flex items-center px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg shadow-sm transition">
                ⚡ Live Sisa Order
            </a>
            <a href="{{ route('capacity.index') }}" class="inline-flex items-center px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold rounded-lg shadow-sm transition">
                📈 Simulasi Kapasitas
            </a>
        </div>
    </div>

    <!-- 4 Key Primary Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Metric 1: Total Orders -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Kontrak Order (PO)</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-3xl font-extrabold text-slate-900">{{ number_format($totalOrders) }}</span>
                <span class="text-xs font-medium text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">Aktif</span>
            </div>
            <p class="text-xs text-slate-500 mt-2">284.018 jadwal shipment terdaftar</p>
        </div>

        <!-- Metric 2: Mesin Beroperasi -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Mesin Beroperasi</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-3xl font-extrabold text-emerald-600">{{ number_format($runningMachines) }}</span>
                <span class="text-xs font-medium text-slate-500">dari {{ number_format($totalMachines) }} unit</span>
            </div>
            <p class="text-xs text-slate-500 mt-2">Lonati 144N, 168N, 200N</p>
        </div>

        <!-- Metric 3: Output Bagus Hari Ini -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Output Bagus (Hari Ini)</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-3xl font-extrabold text-blue-600">{{ number_format($todayGoodPairs) }}</span>
                <span class="text-xs font-medium text-slate-500">Pasang</span>
            </div>
            <p class="text-xs text-slate-500 mt-2">1,895 juta total box tercatat</p>
        </div>

        <!-- Metric 4: Defect Rate -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Defect Rate (BS)</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-3xl font-extrabold {{ $defectRate > 3.0 ? 'text-rose-600' : 'text-slate-900' }}">{{ $defectRate }}%</span>
                <span class="text-xs font-medium text-rose-600 bg-rose-50 px-2 py-0.5 rounded-full">{{ number_format($todayDefectPairs) }} pasang BS</span>
            </div>
            <p class="text-xs text-slate-500 mt-2">Target standar &lt; 3.0%</p>
        </div>
    </div>

    <!-- Secondary Operational Domain Strip (Integrated from CapacityApps) -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <a href="{{ route('quality.index') }}" class="bg-white hover:bg-slate-50 transition border border-slate-200 rounded-lg p-3.5 shadow-sm block">
            <div class="flex items-center justify-between text-xs text-slate-500 font-medium">
                <span>Cacat BS Terdata</span>
                <span class="text-rose-500 font-bold">●</span>
            </div>
            <div class="mt-1 flex items-baseline justify-between">
                <span class="text-xl font-bold text-slate-900">{{ number_format($totalDefectsCount) }}</span>
                <span class="text-2xs text-slate-400">log cacat</span>
            </div>
        </a>

        <a href="{{ route('quality.index', ['tab' => 'rework']) }}" class="bg-white hover:bg-slate-50 transition border border-slate-200 rounded-lg p-3.5 shadow-sm block">
            <div class="flex items-center justify-between text-xs text-slate-500 font-medium">
                <span>Rework Aktif</span>
                <span class="text-amber-500 font-bold">●</span>
            </div>
            <div class="mt-1 flex items-baseline justify-between">
                <span class="text-xl font-bold text-amber-600">{{ number_format($activeReworkCount) }}</span>
                <span class="text-2xs text-slate-400">in repair</span>
            </div>
        </a>

        <a href="{{ route('maintenance.index') }}" class="bg-white hover:bg-slate-50 transition border border-slate-200 rounded-lg p-3.5 shadow-sm block">
            <div class="flex items-center justify-between text-xs text-slate-500 font-medium">
                <span>Tiket Servis Open</span>
                <span class="text-sky-500 font-bold">●</span>
            </div>
            <div class="mt-1 flex items-baseline justify-between">
                <span class="text-xl font-bold text-sky-600">{{ number_format($openTicketsCount) }}</span>
                <span class="text-2xs text-slate-400">tiket</span>
            </div>
        </a>

        <a href="{{ route('yarn.index') }}" class="bg-white hover:bg-slate-50 transition border border-slate-200 rounded-lg p-3.5 shadow-sm block">
            <div class="flex items-center justify-between text-xs text-slate-500 font-medium">
                <span>Saldo Benang Lantai</span>
                <span class="text-emerald-500 font-bold">●</span>
            </div>
            <div class="mt-1 flex items-baseline justify-between">
                <span class="text-xl font-bold text-emerald-600">{{ number_format($totalYarnStockKg, 1) }}</span>
                <span class="text-2xs text-slate-400">kg benang</span>
            </div>
        </a>
    </div>

    <!-- Live Sisa Order (Urgent Deliveries) -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-5 border-b border-slate-200 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-900">Jadwal Pengiriman Paling Mendesak (*Earliest Deliveries*)</h2>
                <p class="text-xs text-slate-500 mt-0.5">Prioritas jadwal kirim multi-delivery order beserta realisasi output rajut.</p>
            </div>
            <a href="{{ route('production.monitoring') }}" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700">Lihat Semua →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 text-xs font-semibold uppercase tracking-wider border-b border-slate-200">
                        <th class="py-3 px-4">PO / Buyer</th>
                        <th class="py-3 px-4">Style / Varian</th>
                        <th class="py-3 px-4">Target Kirim</th>
                        <th class="py-3 px-4 text-right">Order (Psg)</th>
                        <th class="py-3 px-4 text-right">Bagus (Psg)</th>
                        <th class="py-3 px-4 text-right">Sisa (Psg)</th>
                        <th class="py-3 px-4">Progres</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($liveProgress as $item)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="py-3 px-4">
                                <a href="{{ route('orders.show', $item->order_id) }}" class="font-bold text-slate-900 hover:text-emerald-600 font-mono">
                                    {{ $item->po_number }}
                                </a>
                                <span class="text-xs text-slate-500 block">{{ $item->buyer_name }}</span>
                            </td>
                            <td class="py-3 px-4">
                                <span class="font-medium text-slate-800">{{ $item->style_code }}</span>
                                <span class="text-xs text-slate-400 block">{{ $item->color_code }} | {{ $item->size }}</span>
                            </td>
                            <td class="py-3 px-4 text-xs font-medium text-slate-600 font-mono">
                                {{ \Carbon\Carbon::parse($item->target_delivery_date)->format('d M Y') }}
                            </td>
                            <td class="py-3 px-4 text-right font-mono font-medium text-slate-800">
                                {{ number_format($item->ordered_qty) }}
                            </td>
                            <td class="py-3 px-4 text-right font-mono font-semibold text-blue-600">
                                {{ number_format($item->produced_good_qty) }}
                            </td>
                            <td class="py-3 px-4 text-right font-mono font-bold {{ $item->sisa_pairs > 0 ? 'text-amber-600' : 'text-emerald-600' }}">
                                {{ number_format($item->sisa_pairs) }}
                            </td>
                            <td class="py-3 px-4">
                                @php
                                    $pct = $item->ordered_qty > 0 ? min(100, round(($item->produced_good_qty / $item->ordered_qty) * 100)) : 0;
                                @endphp
                                <div class="w-full bg-slate-200 rounded-full h-2">
                                    <div class="h-2 rounded-full {{ $pct >= 100 ? 'bg-emerald-500' : 'bg-blue-600' }}" style="width: {{ $pct }}%"></div>
                                </div>
                                <span class="text-2xs text-slate-400 mt-1 block text-right font-mono">{{ $pct }}%</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400">
                                Tidak ada data progres pengiriman aktif.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Orders Section -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-5 border-b border-slate-200 flex items-center justify-between">
            <h2 class="text-base font-bold text-slate-900">Kontrak Order Terbaru Terdaftar</h2>
            <a href="{{ route('orders.index') }}" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700">Lihat Semua Order →</a>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse ($recentOrders as $order)
                <div class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-slate-50/50 transition">
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="font-bold text-slate-900 font-mono text-base">{{ $order->po_number }}</span>
                            <span class="px-2 py-0.5 rounded text-2xs font-semibold bg-slate-100 text-slate-700">{{ $order->buyer_name }}</span>
                            <span class="px-2 py-0.5 rounded text-2xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">{{ $order->season ?? 'REGULAR' }}</span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">
                            Tanggal Order: {{ $order->order_date->format('d M Y') }} | Varian Style: {{ $order->orderStyles->count() }} SKU
                        </p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="text-right">
                            <span class="text-xs text-slate-400 block">Total Pasang</span>
                            <span class="font-bold font-mono text-slate-900">{{ number_format($order->total_ordered_pairs) }}</span>
                        </div>
                        <a href="{{ route('orders.show', $order) }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-md transition">
                            Detail PDK →
                        </a>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-slate-400">
                    Belum ada order terdaftar.
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
