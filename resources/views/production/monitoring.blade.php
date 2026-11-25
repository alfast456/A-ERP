@extends('layouts.app')

@section('title', 'Live Sisa Order & Monitoring Produksi')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Live Sisa Order & Monitoring Produksi</h1>
            <p class="text-sm text-slate-500">Kalkulasi sisa order realtime dari view `view_order_shipment_progress` tanpa lag dan bebas dari risiko race-condition.</p>
        </div>
        <div>
            <a href="{{ route('reports.export.production') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium shadow-sm transition">
                📥 Export Excel (FastExcel)
            </a>
        </div>
    </div>

    <!-- 4 Summary KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Total Kuantitas Order</span>
            <span class="text-2xl font-black text-slate-900 mt-2 block">{{ number_format($totalOrdered) }} <span class="text-xs font-normal text-slate-500">Pcs</span></span>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Total Good Pcs (Lolos)</span>
            <span class="text-2xl font-black text-emerald-600 mt-2 block">{{ number_format($totalGood) }} <span class="text-xs font-normal text-slate-500">Pcs</span></span>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Total Cacat (BS/Reject)</span>
            <span class="text-2xl font-black text-rose-600 mt-2 block">{{ number_format($totalDefect) }} <span class="text-xs font-normal text-slate-500">Pcs</span></span>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Total Sisa Harus Dirajut</span>
            <span class="text-2xl font-black text-amber-600 mt-2 block">{{ number_format($totalSisa) }} <span class="text-xs font-normal text-slate-500">Pcs</span></span>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200">
        <form action="{{ route('production.monitoring') }}" method="GET" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari PO, Buyer, atau Style..." class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
            </div>
            <div class="w-full sm:w-48">
                <select name="filter_status" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <option value="">Semua Status</option>
                    <option value="ongoing" {{ request('filter_status') == 'ongoing' ? 'selected' : '' }}>Masih Ada Sisa</option>
                    <option value="completed" {{ request('filter_status') == 'completed' ? 'selected' : '' }}>Selesai (Sisa 0)</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white text-sm font-semibold rounded-lg transition">Filter</button>
            @if(request()->hasAny(['search', 'filter_status']))
                <a href="{{ route('production.monitoring') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold rounded-lg transition text-center">Reset</a>
            @endif
        </form>
    </div>

    <!-- Data Table -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase font-semibold">
                    <tr>
                        <th class="px-6 py-3">PO Number</th>
                        <th class="px-6 py-3">Buyer</th>
                        <th class="px-6 py-3">Style SKU</th>
                        <th class="px-6 py-3">Tgl Kirim</th>
                        <th class="px-6 py-3 text-right">Order Qty</th>
                        <th class="px-6 py-3 text-right">Good Qty</th>
                        <th class="px-6 py-3 text-right">BS Qty</th>
                        <th class="px-6 py-3 text-right">Sisa Pcs</th>
                        <th class="px-6 py-3">Progres Produksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($progressList as $row)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 font-bold text-slate-900">{{ $row->po_number }}</td>
                            <td class="px-6 py-4">{{ $row->buyer_name }}</td>
                            <td class="px-6 py-4">
                                <span class="font-semibold text-slate-800">{{ $row->style_code }}</span>
                                <span class="text-xs text-slate-400 block">{{ $row->color_code }} / {{ $row->size }}</span>
                            </td>
                            <td class="px-6 py-4 text-xs">{{ date('d M Y', strtotime($row->target_delivery_date)) }}</td>
                            <td class="px-6 py-4 text-right font-semibold">{{ number_format($row->ordered_qty) }}</td>
                            <td class="px-6 py-4 text-right font-semibold text-emerald-600">{{ number_format($row->produced_good_qty) }}</td>
                            <td class="px-6 py-4 text-right text-rose-600 text-xs">{{ number_format($row->produced_defect_qty) }}</td>
                            <td class="px-6 py-4 text-right font-black {{ $row->sisa_pcs == 0 ? 'text-slate-400' : 'text-amber-600' }}">
                                {{ number_format($row->sisa_pcs) }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="w-40">
                                    <div class="flex justify-between text-xs font-semibold mb-1">
                                        <span class="{{ $row->completion_percentage >= 100 ? 'text-emerald-600' : 'text-slate-700' }}">{{ $row->completion_percentage }}%</span>
                                        @if($row->sisa_pcs == 0)
                                            <span class="text-emerald-600 text-[10px]">LENGKAP</span>
                                        @endif
                                    </div>
                                    <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                                        <div class="h-2.5 rounded-full transition-all {{ $row->completion_percentage >= 100 ? 'bg-emerald-500' : 'bg-blue-600' }}" style="width: {{ min(100, $row->completion_percentage) }}%"></div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-8 text-center text-slate-400">
                                Tidak ada data progres pengiriman ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($progressList->hasPages())
            <div class="px-6 py-3 border-t border-slate-200">
                {{ $progressList->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
