@extends('layouts.app')

@section('title', 'Executive Manufacturing Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Header Page -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Executive Manufacturing Dashboard</h1>
            <p class="text-sm text-slate-500">Pemantauan performa harian lini rajut (*Knitting Floor*), ketersediaan mesin, dan sisa order real-time.</p>
        </div>
        <div>
            <a href="{{ route('production.monitoring') }}" class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm transition">
                ⚡ Lihat Live Sisa Order
            </a>
        </div>
    </div>

    <!-- 4 Key Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Metric 1: Total Orders -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Kontrak Order (PO)</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-3xl font-extrabold text-slate-900">{{ number_format($totalOrders) }}</span>
                <span class="text-xs font-medium text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">Aktif</span>
            </div>
            <p class="text-xs text-slate-500 mt-2">Total PO buyer di database</p>
        </div>

        <!-- Metric 2: Mesin Beroperasi -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Mesin Beroperasi</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-3xl font-extrabold text-emerald-600">{{ number_format($runningMachines) }}</span>
                <span class="text-xs font-medium text-slate-500">dari {{ number_format($totalMachines) }} unit</span>
            </div>
            <p class="text-xs text-slate-500 mt-2">Lonati, Santoni, Dakong</p>
        </div>

        <!-- Metric 3: Output Bagus Hari Ini -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Output Bagus (Hari Ini)</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-3xl font-extrabold text-blue-600">{{ number_format($todayGoodPairs) }}</span>
                <span class="text-xs font-medium text-slate-500">Pasang</span>
            </div>
            <p class="text-xs text-slate-500 mt-2">Box lolos QC knitting</p>
        </div>

        <!-- Metric 4: Defect Rate -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Defect Rate (BS)</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-3xl font-extrabold {{ $defectRate > 3.0 ? 'text-rose-600' : 'text-slate-900' }}">{{ $defectRate }}%</span>
                <span class="text-xs font-medium text-rose-600 bg-rose-50 px-2 py-0.5 rounded-full">{{ number_format($todayDefectPairs) }} pasang BS</span>
            </div>
            <p class="text-xs text-slate-500 mt-2">Target standar < 3.0%</p>
        </div>
    </div>

    <!-- Live Shipment Progress Snapshot -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-900">Snapshot Progres & Sisa Order Terkini</h2>
                <p class="text-xs text-slate-500">Dihitung otomatis secara dinamis melalui Database View tanpa risiko korup data.</p>
            </div>
            <a href="{{ route('production.monitoring') }}" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700">Lihat Semua →</a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase font-semibold">
                    <tr>
                        <th class="px-6 py-3">PO Number</th>
                        <th class="px-6 py-3">Buyer</th>
                        <th class="px-6 py-3">Style / SKU</th>
                        <th class="px-6 py-3">Target Kirim</th>
                        <th class="px-6 py-3 text-right">Order Qty</th>
                        <th class="px-6 py-3 text-right">Good Qty</th>
                        <th class="px-6 py-3 text-right">Sisa Qty</th>
                        <th class="px-6 py-3">Progres</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($liveProgress as $row)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-3 font-semibold text-slate-900">{{ $row->po_number }}</td>
                            <td class="px-6 py-3">{{ $row->buyer_name }}</td>
                            <td class="px-6 py-3">
                                <span class="font-medium text-slate-800">{{ $row->style_code }}</span>
                                <span class="text-xs text-slate-400 block">{{ $row->color_code }} - {{ $row->size }}</span>
                            </td>
                            <td class="px-6 py-3 text-xs">{{ date('d M Y', strtotime($row->target_delivery_date)) }}</td>
                            <td class="px-6 py-3 text-right font-medium">{{ number_format($row->ordered_qty) }}</td>
                            <td class="px-6 py-3 text-right text-emerald-600 font-semibold">{{ number_format($row->produced_good_qty) }}</td>
                            <td class="px-6 py-3 text-right font-bold {{ $row->sisa_pairs == 0 ? 'text-slate-400' : 'text-amber-600' }}">
                                {{ number_format($row->sisa_pairs) }}
                            </td>
                            <td class="px-6 py-3">
                                <div class="w-36">
                                    <div class="flex justify-between text-xs font-medium mb-1">
                                        <span>{{ $row->completion_percentage }}%</span>
                                    </div>
                                    <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                        <div class="bg-emerald-500 h-2 rounded-full transition-all" style="width: {{ min(100, $row->completion_percentage) }}%"></div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-slate-400">
                                Belum ada data transaksi output box produksi. Buat PO atau jalankan transaksi rajut.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
