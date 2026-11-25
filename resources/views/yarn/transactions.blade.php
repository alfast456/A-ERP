@extends('layouts.app')

@section('title', 'Transaksi & Mutasi Benang')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Riwayat Transaksi & Mutasi Benang</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Pencatatan penerimaan dari gudang (receipt), pengembalian (return), pemakaian rajut, dan adjustment.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('yarn.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50">
                &larr; Saldo Benang
            </a>
            <button onclick="document.getElementById('txModal').classList.remove('hidden')"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 shadow-sm transition">
                + Catat Transaksi Baru
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-lg bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <form method="GET" action="{{ route('yarn.transactions') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Cari No. Referensi / Lot / Kode</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Contoh: REF-2026 atau PE 30s"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Tipe Transaksi</label>
                <select name="type" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                    <option value="">Semua Tipe Transaksi</option>
                    <option value="RECEIPT_FROM_WH" {{ request('type') == 'RECEIPT_FROM_WH' ? 'selected' : '' }}>Penerimaan Gudang (RECEIPT)</option>
                    <option value="RETURN_TO_WH" {{ request('type') == 'RETURN_TO_WH' ? 'selected' : '' }}>Retur ke Gudang (RETURN)</option>
                    <option value="USAGE_KNITTING" {{ request('type') == 'USAGE_KNITTING' ? 'selected' : '' }}>Pemakaian Rajut (USAGE)</option>
                    <option value="ADJUSTMENT" {{ request('type') == 'ADJUSTMENT' ? 'selected' : '' }}>Penyesuaian (ADJUSTMENT)</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 rounded-lg text-sm font-medium hover:bg-gray-800">
                    Filter
                </button>
                <a href="{{ route('yarn.transactions') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-50 dark:hover:bg-gray-700">
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
                        <th class="py-3.5 px-4">Waktu & Ref</th>
                        <th class="py-3.5 px-4">Benang / Item</th>
                        <th class="py-3.5 px-4">Area Lantai</th>
                        <th class="py-3.5 px-4">Tipe Mutasi</th>
                        <th class="py-3.5 px-4 text-right">Delta Cones</th>
                        <th class="py-3.5 px-4 text-right">Delta Berat (Kg)</th>
                        <th class="py-3.5 px-4">Petugas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($transactions as $tx)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-750/50 transition">
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $tx->reference_number }}</div>
                                <div class="text-xs text-gray-400">{{ $tx->transaction_time?->format('d M Y H:i') ?? '-' }}</div>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-semibold text-gray-900 dark:text-white">{{ $tx->balance->yarn_name ?? $tx->balance->yarn_code }}</div>
                                <div class="text-xs text-gray-500">Lot: {{ $tx->balance->lot_number ?? '-' }} | Warna: {{ $tx->balance->color_name ?? '-' }}</div>
                            </td>
                            <td class="py-3.5 px-4 text-gray-600 dark:text-gray-300">
                                {{ $tx->balance->factoryArea->area_name ?? '-' }}
                            </td>
                            <td class="py-3.5 px-4">
                                @if($tx->transaction_type->value === 'RECEIPT_FROM_WH')
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">MASUK (RECEIPT)</span>
                                @elseif($tx->transaction_type->value === 'RETURN_TO_WH')
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">RETUR GUDANG</span>
                                @elseif($tx->transaction_type->value === 'USAGE_KNITTING')
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">PAKAI RAJUT</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300">ADJUSTMENT</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono font-bold {{ $tx->cones_delta >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $tx->cones_delta > 0 ? '+' : '' }}{{ number_format($tx->cones_delta) }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono font-bold {{ $tx->weight_kg_delta >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $tx->weight_kg_delta > 0 ? '+' : '' }}{{ number_format($tx->weight_kg_delta, 2) }}
                            </td>
                            <td class="py-3.5 px-4 text-xs text-gray-500">
                                {{ $tx->operator->name ?? 'Sistem' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-gray-500 dark:text-gray-400">
                                Belum ada transaksi mutasi benang tercatat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-gray-200 dark:border-gray-700">
            {{ $transactions->links() }}
        </div>
    </div>
</div>

<!-- Modal Transaksi Baru -->
<div id="txModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full p-6 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between pb-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Form Mutasi Stok Benang</h3>
            <button onclick="document.getElementById('txModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <form action="{{ route('yarn.transactions.store') }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Pilih Item / Lot Benang *</label>
                <select name="yarn_stock_balance_id" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                    @foreach($balances as $bal)
                        <option value="{{ $bal->id }}">
                            [{{ $bal->yarn_code }}] {{ $bal->yarn_name }} (Lot: {{ $bal->lot_number }} | Stok: {{ $bal->qty_cones }} cns / {{ $bal->weight_kg }} kg)
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Tipe Mutasi *</label>
                <select name="transaction_type" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                    <option value="RECEIPT_FROM_WH">Penerimaan dari Gudang (Tambah Stok)</option>
                    <option value="RETURN_TO_WH">Pengembalian / Retur ke Gudang (Kurang Stok)</option>
                    <option value="USAGE_KNITTING">Pemakaian Lantai Rajut (Kurang Stok)</option>
                    <option value="ADJUSTMENT">Penyesuaian Opname (Adjustment)</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">No. Referensi / Surat Jalan *</label>
                <input type="text" name="reference_number" required placeholder="SJ-2026-001 atau OPNAME-NOV" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Jumlah Cones *</label>
                    <input type="number" name="cones_delta" required value="10" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Berat Benang (Kg) *</label>
                    <input type="number" step="0.01" name="weight_kg_delta" required value="25.50" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button type="button" onclick="document.getElementById('txModal').classList.add('hidden')" class="px-4 py-2 border rounded-lg text-sm text-gray-700 dark:text-gray-300">Batal</button>
                <button type="submit" class="px-4 py-2 bg-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700">Simpan Transaksi</button>
            </div>
        </form>
    </div>
</div>
@endsection
