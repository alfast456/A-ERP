@extends('layouts.app')

@section('title', 'Papan Buffer Staging Area WIP')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white tracking-tight">Papan Buffer & Staging Area WIP</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Monitoring antrean box kaos kaki dalam pengiriman (In-Transit) dan penanganan selisih hitung.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('wip.index') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-sm font-semibold rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                ← Daftar Mutasi WIP
            </a>
            <a href="{{ route('wip.transfer.create') }}" class="px-4 py-2 rounded-xl bg-brand-500 hover:bg-brand-600 text-white text-sm font-semibold shadow-xs transition">
                + Kirim Box Baru
            </a>
        </div>
    </div>

    <!-- Active In-Transit Queue Cards -->
    <div class="space-y-3">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider">Antrean Box Dalam Perjalanan (In-Transit)</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($inTransitMovements as $item)
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-amber-200 dark:border-amber-800/40 p-4 shadow-xs space-y-3 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-2 h-full bg-amber-400"></div>
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="font-extrabold text-gray-900 dark:text-white text-base">Box #{{ $item->productionBox?->no_box ?? '-' }}</span>
                            <span class="text-xs text-gray-500 block font-mono">{{ $item->productionBox?->shipment?->orderStyle?->order?->po_number ?? '-' }}</span>
                        </div>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300">
                            IN-TRANSIT
                        </span>
                    </div>

                    <div class="p-3 bg-gray-50 dark:bg-gray-900/60 rounded-xl text-xs space-y-1">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Dari:</span>
                            <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $item->fromArea?->area_name ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Tujuan:</span>
                            <span class="font-semibold text-brand-600 dark:text-brand-400">{{ $item->toArea?->area_name ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Jumlah Kirim:</span>
                            <span class="font-bold text-gray-900 dark:text-white">{{ number_format($item->sent_pcs) }} pcs</span>
                        </div>
                    </div>

                    <form action="{{ route('wip.receive', $item) }}" method="POST" class="flex gap-2">
                        @csrf
                        <input type="number" name="received_pcs" value="{{ $item->sent_pcs }}" min="0" required class="w-20 px-2 py-1.5 text-xs border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-900 font-bold text-center">
                        <button type="submit" class="flex-1 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg transition">
                            Terima Box
                        </button>
                    </form>
                </div>
            @empty
                <div class="col-span-3 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700/60 p-8 text-center text-sm text-gray-500">
                    Tidak ada box yang sedang dalam perjalanan antrean (Semua box telah diterima di departemen tujuan).
                </div>
            @endforelse
        </div>
    </div>

    <!-- Discrepancy Warnings -->
    @if($discrepancyMovements->count() > 0)
        <div class="space-y-3 pt-4 border-t border-gray-200 dark:border-gray-700/60">
            <h3 class="text-sm font-bold text-rose-600 dark:text-rose-400 uppercase tracking-wider flex items-center gap-1.5">
                <span>⚠️</span> Box dengan Selisih Hitung (Perlu Investigasi)
            </h3>
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-rose-200 dark:border-rose-800/40 divide-y divide-gray-100 dark:divide-gray-700/40 shadow-xs overflow-hidden">
                @foreach($discrepancyMovements as $disc)
                    <div class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-sm">
                        <div>
                            <span class="font-bold text-gray-900 dark:text-white">Box #{{ $disc->productionBox?->no_box }}</span>
                            <span class="text-xs text-gray-500 ml-2">({{ $disc->fromArea?->area_name }} ➔ {{ $disc->toArea?->area_name }})</span>
                            <span class="text-xs text-gray-400 block mt-0.5">Tgl: {{ $disc->movement_date?->format('d M Y') }} | Ket: {{ $disc->remarks ?? '-' }}</span>
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="text-xs text-gray-500">Kirim: <strong class="text-gray-900 dark:text-white">{{ number_format($disc->sent_pcs) }}</strong></span>
                            <span class="text-xs text-gray-500">Terima: <strong class="text-rose-600 dark:text-rose-400">{{ number_format($disc->received_pcs) }}</strong></span>
                            <span class="text-xs font-bold text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/60 px-2.5 py-1 rounded-lg">
                                Selisih: {{ $disc->received_pcs - $disc->sent_pcs }} pcs
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
