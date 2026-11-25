@extends('layouts.app')

@section('title', 'Kualitas, BS & Perbaikan Kaos Kaki')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Kendali Mutu, Data Cacat (BS) & Perbaikan</h1>
            <p class="text-sm text-slate-500 mt-1">Audit terperinci rekaman cacat rajut dan riwayat perbaikan kaos kaki (1,90 juta rekaman).</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('reports.export.defects') }}" class="px-3 py-1.5 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-xs font-semibold hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                📥 Export Defect Excel
            </a>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                Success Rate: {{ $repairResolutionRate }}%
            </span>
            <a href="{{ route('quality.waste') }}" class="px-3.5 py-1.5 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-xs font-semibold hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                🗑️ Limbah Mesin (Waste)
            </a>
            <a href="{{ route('quality.defects.create') }}" class="px-3.5 py-1.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold shadow-xs transition">
                + Lapor Cacat (BS)
            </a>
        </div>
    </div>

    <!-- Stat Metric Strip -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-sm">
            <span class="text-xs font-medium text-slate-500 uppercase tracking-wider block">Total Kejadian Cacat (BS)</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-rose-600 tracking-tight">{{ number_format($totalDefectsCount) }}</span>
                <span class="text-xs text-slate-400">log</span>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-sm">
            <span class="text-xs font-medium text-slate-500 uppercase tracking-wider block">Kaos Kaki Masuk Perbaikan</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-amber-600 tracking-tight">{{ number_format($totalReworkCount) }}</span>
                <span class="text-xs text-slate-400">lot</span>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-sm">
            <span class="text-xs font-medium text-slate-500 uppercase tracking-wider block">Sukses Diperbaiki (Repaired)</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-emerald-600 tracking-tight">{{ number_format($totalRepaired) }}</span>
                <span class="text-xs text-slate-400">pcs</span>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-sm">
            <span class="text-xs font-medium text-slate-500 uppercase tracking-wider block">BS Murni (Scrapped)</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-slate-600 tracking-tight">{{ number_format($totalScrapped) }}</span>
                <span class="text-xs text-slate-400">pcs</span>
            </div>
        </div>
    </div>

    <!-- Tab Switching -->
    <div class="border-b border-slate-200">
        <nav class="-mb-px flex space-x-6">
            <a href="{{ route('quality.index', ['tab' => 'defects']) }}" class="py-3 px-1 border-b-2 text-sm font-semibold transition {{ $tab === 'defects' ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
                🔍 Detail Data Cacat Box (1.06M)
            </a>
            <a href="{{ route('quality.index', ['tab' => 'rework']) }}" class="py-3 px-1 border-b-2 text-sm font-semibold transition {{ $tab === 'rework' ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
                🛠️ Siklus Perbaikan & Rework (841K)
            </a>
        </nav>
    </div>

    @if($tab === 'defects')
        <!-- Filter Bar Defects -->
        <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-sm">
            <form method="GET" action="{{ route('quality.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <input type="hidden" name="tab" value="defects">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Cari Keterangan / Box</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Contoh: Area KK9D atau nomor box" class="w-full text-sm border-slate-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Jenis Cacat (Defect Type)</label>
                    <select name="defect_type_id" class="w-full text-sm border-slate-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">Semua Jenis Cacat</option>
                        @foreach($defectTypes as $dt)
                            <option value="{{ $dt->id }}" {{ request('defect_type_id') == $dt->id ? 'selected' : '' }}>{{ $dt->defect_code }} - {{ $dt->description }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 bg-slate-800 hover:bg-slate-900 text-white text-sm font-medium py-2 px-3 rounded-md transition shadow-sm">Filter</button>
                    <a href="{{ route('quality.index', ['tab' => 'defects']) }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm font-medium py-2 px-3 rounded-md transition">Reset</a>
                </div>
            </form>
        </div>

        <!-- Defect Table -->
        <div class="bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600 font-semibold text-xs uppercase tracking-wider">
                        <tr>
                            <th class="py-3 px-4">ID Cacat</th>
                            <th class="py-3 px-4">Ref Box Produksi</th>
                            <th class="py-3 px-4">Kode & Deskripsi Cacat</th>
                            <th class="py-3 px-4 text-right">Jumlah (Pcs)</th>
                            <th class="py-3 px-4">Catatan Audit</th>
                            <th class="py-3 px-4">Waktu Terdata</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-mono text-xs">
                        @forelse($defectList as $df)
                            <tr class="hover:bg-slate-50 transition font-sans">
                                <td class="py-3 px-4 font-mono font-semibold text-slate-900 text-xs">
                                    #{{ $df->id }}
                                </td>
                                <td class="py-3 px-4 font-mono text-slate-600 text-xs">
                                    BOX-{{ $df->production_box_id }}
                                </td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                        {{ $df->defectType?->defect_code ?? 'BS-N/A' }}
                                    </span>
                                    <span class="text-xs text-slate-500 block mt-0.5">{{ $df->defectType?->description ?? '-' }}</span>
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-rose-600">
                                    {{ number_format($df->defect_count_pcs) }}
                                </td>
                                <td class="py-3 px-4 text-slate-600 text-xs">
                                    {{ $df->notes ?: '-' }}
                                </td>
                                <td class="py-3 px-4 text-slate-400 text-xs">
                                    {{ $df->created_at?->format('d M Y H:i') ?? '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-slate-400 font-sans">
                                    Tidak ada data cacat yang sesuai dengan filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-200">
                {{ $defectList->links() }}
            </div>
        </div>
    @else
        <!-- Filter Bar Rework -->
        <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-sm">
            <form method="GET" action="{{ route('quality.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <input type="hidden" name="tab" value="rework">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Status Perbaikan</label>
                    <select name="status" class="w-full text-sm border-slate-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">Semua Status</option>
                        <option value="IN_REPAIR" {{ request('status') === 'IN_REPAIR' ? 'selected' : '' }}>In Repair (Sedang Diperbaiki)</option>
                        <option value="RESOLVED" {{ request('status') === 'RESOLVED' ? 'selected' : '' }}>Resolved (Lolos Perbaikan)</option>
                        <option value="SCRAPPED" {{ request('status') === 'SCRAPPED' ? 'selected' : '' }}>Scrapped (BS Murni)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Jenis Cacat Asal</label>
                    <select name="defect_type_id" class="w-full text-sm border-slate-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">Semua Jenis Cacat</option>
                        @foreach($defectTypes as $dt)
                            <option value="{{ $dt->id }}" {{ request('defect_type_id') == $dt->id ? 'selected' : '' }}>{{ $dt->defect_code }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 bg-slate-800 hover:bg-slate-900 text-white text-sm font-medium py-2 px-3 rounded-md transition shadow-sm">Filter</button>
                    <a href="{{ route('quality.index', ['tab' => 'rework']) }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm font-medium py-2 px-3 rounded-md transition">Reset</a>
                </div>
            </form>
        </div>

        <!-- Rework Table -->
        <div class="bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600 font-semibold text-xs uppercase tracking-wider">
                        <tr>
                            <th class="py-3 px-4">ID Rework</th>
                            <th class="py-3 px-4">Ref Box Produksi</th>
                            <th class="py-3 px-4">Jenis Cacat</th>
                            <th class="py-3 px-4 text-right">Terkirim (Sent)</th>
                            <th class="py-3 px-4 text-right">Sukses Diperbaiki</th>
                            <th class="py-3 px-4 text-right">Scrapped</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4">Tanggal Masuk</th>
                            <th class="py-3 px-4 text-center">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($reworkList as $rw)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="py-3 px-4 font-mono font-semibold text-slate-900 text-xs">
                                    #{{ $rw->id }}
                                </td>
                                <td class="py-3 px-4 font-mono text-slate-600 text-xs">
                                    BOX-{{ $rw->production_box_id }}
                                </td>
                                <td class="py-3 px-4 text-xs font-semibold text-slate-700">
                                    {{ $rw->defectType?->defect_code ?? '-' }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono text-slate-900">
                                    {{ number_format($rw->sent_pcs) }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-emerald-600">
                                    {{ number_format($rw->repaired_pcs) }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-rose-600">
                                    {{ number_format($rw->scrapped_pcs) }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @if($rw->status->value === 'RESOLVED')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                            ✓ Resolved
                                        </span>
                                    @elseif($rw->status->value === 'IN_REPAIR')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                            ⏳ In Repair
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-800">
                                            ✕ Scrapped
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-slate-400 text-xs font-mono">
                                    {{ $rw->created_at?->format('d M Y') ?? '-' }}
                                </td>
                                <td class="py-3 px-4 text-center text-xs">
                                    @if($rw->status->value === 'IN_REPAIR')
                                        <div class="flex items-center justify-center gap-1.5">
                                            <form action="{{ route('quality.rework.resolve', $rw) }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="action_type" value="RESOLVE">
                                                <input type="hidden" name="qty" value="{{ $rw->sent_pcs }}">
                                                <button type="submit" class="px-2 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded text-[11px] font-semibold transition" title="Kaos kaki berhasil diperbaiki (Good)">
                                                    ✓ Sukses
                                                </button>
                                            </form>
                                            <form action="{{ route('quality.rework.resolve', $rw) }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="action_type" value="SCRAP">
                                                <input type="hidden" name="qty" value="{{ $rw->sent_pcs }}">
                                                <button type="submit" class="px-2 py-1 bg-rose-600 hover:bg-rose-700 text-white rounded text-[11px] font-semibold transition" title="Tidak dapat diperbaiki (BS Murni)">
                                                    ✕ Scrap
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="text-slate-400 italic">Selesai</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-8 text-center text-slate-400">
                                    Tidak ada data perbaikan yang sesuai dengan filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-200">
                {{ $reworkList->links() }}
            </div>
        </div>
    @endif
</div>
@endsection
