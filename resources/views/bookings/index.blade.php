@extends('layouts.app')

@section('title', 'Pre-Order Capacity Bookings')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Pre-Order Capacity Bookings</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Pemesanan slot kapasitas rajut di awal (pre-order) sebelum terbit PO resmi dari buyer.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('bookings.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 shadow-sm transition">
                + Buat Booking Baru
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-lg bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <!-- Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700 shadow-sm">
            <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Slot Booking</div>
            <div class="mt-2 text-3xl font-extrabold text-brand-600 dark:text-brand-400">{{ number_format($totalBookings) }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700 shadow-sm">
            <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estimasi Pasangan (HOLD)</div>
            <div class="mt-2 text-3xl font-extrabold text-indigo-600 dark:text-indigo-400">{{ number_format($totalEstimatedPcs) }} <span class="text-sm font-normal text-gray-500">pcs</span></div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700 shadow-sm">
            <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Dibatalkan (Cancel)</div>
            <div class="mt-2 text-3xl font-extrabold text-red-600 dark:text-red-400">{{ number_format($totalCancelledPcs) }} <span class="text-sm font-normal text-gray-500">pcs</span></div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <form method="GET" action="{{ route('bookings.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Cari Booking / Buyer</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Contoh: BK-2026 atau PUMA"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Status Booking</label>
                <select name="status" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                    <option value="">Semua Status</option>
                    <option value="HOLD" {{ request('status') == 'HOLD' ? 'selected' : '' }}>HOLD (Aktif)</option>
                    <option value="CONFIRMED_TO_ORDER" {{ request('status') == 'CONFIRMED_TO_ORDER' ? 'selected' : '' }}>CONFIRMED (Menjadi PO)</option>
                    <option value="CANCELLED" {{ request('status') == 'CANCELLED' ? 'selected' : '' }}>CANCELLED (Batal)</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 rounded-lg text-sm font-medium hover:bg-gray-800">
                    Filter
                </button>
                <a href="{{ route('bookings.index') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-50 dark:hover:bg-gray-700">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Data Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-700/50 text-xs uppercase font-medium text-gray-500 dark:text-gray-400">
                        <th class="py-3.5 px-4">Kode Booking</th>
                        <th class="py-3.5 px-4">Buyer</th>
                        <th class="py-3.5 px-4">Bulan / Delivery</th>
                        <th class="py-3.5 px-4">Spesifikasi Rajut</th>
                        <th class="py-3.5 px-4 text-right">Estimasi (Pcs)</th>
                        <th class="py-3.5 px-4 text-center">Status</th>
                        <th class="py-3.5 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($bookings as $b)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-750/50 transition">
                            <td class="py-3.5 px-4 font-bold text-gray-900 dark:text-white whitespace-nowrap">
                                {{ $b->booking_code }}
                            </td>
                            <td class="py-3.5 px-4 font-medium text-gray-800 dark:text-gray-200">
                                {{ $b->buyer_name }}
                            </td>
                            <td class="py-3.5 px-4 text-gray-600 dark:text-gray-300">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $b->target_month }}</div>
                                <div class="text-xs text-gray-400">Deliv: {{ $b->delivery_date?->format('d M Y') ?? '-' }}</div>
                            </td>
                            <td class="py-3.5 px-4 text-xs text-gray-600 dark:text-gray-300">
                                <div>Jarum: {{ $b->needle ?? '-' }} | Seam: {{ $b->seam ?? '-' }}</div>
                                <div class="text-gray-400">{{ $b->machineType?->brand ?? '' }} {{ $b->machineType?->model_name ?? '' }}</div>
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono font-bold text-gray-900 dark:text-white">
                                {{ number_format($b->estimated_pcs) }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @if($b->status === 'HOLD')
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">HOLD</span>
                                @elseif($b->status === 'CONFIRMED_TO_ORDER')
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">CONFIRMED</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300">CANCELLED</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @if($b->status === 'HOLD')
                                    <form action="{{ route('bookings.cancel', $b) }}" method="POST" onsubmit="return confirm('Batalkan booking {{ $b->booking_code }}?')">
                                        @csrf
                                        <input type="hidden" name="reason" value="Dibatalkan oleh PPIC/Buyer">
                                        <button type="submit" class="px-2.5 py-1 text-xs font-medium text-red-700 bg-red-50 hover:bg-red-100 dark:bg-red-900/30 dark:text-red-300 rounded border border-red-200 dark:border-red-800 transition">
                                            Cancel
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-gray-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-gray-500 dark:text-gray-400">
                                Belum ada slot booking kapasitas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-gray-200 dark:border-gray-700">
            {{ $bookings->links() }}
        </div>
    </div>
</div>
@endsection
