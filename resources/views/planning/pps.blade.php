@extends('layouts.app')

@section('title', 'Pre-Production Sample (PPS) Approval')

@section('content')
<div class="space-y-6">
    <!-- Header Page -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('planning.index') }}" class="hover:underline">Planning PPIC</a>
                <span>/</span>
                <span>Pre-Production Sample (PPS)</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Gatekeeper Approval Sampel Pre-Production (PPS)</h1>
            <p class="text-sm text-slate-500">Persetujuan kelayakan sampel kaos kaki sebelum alokasi mesin diizinkan jalan produksi massal.</p>
        </div>
        <div>
            <a href="{{ route('planning.index') }}" class="inline-flex items-center px-3.5 py-2 text-xs font-semibold rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                &larr; Kembali ke Planning
            </a>
        </div>
    </div>

    <!-- Summary Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Total PPS Sampel</span>
                <div class="mt-1 text-2xl font-black text-slate-800">{{ number_format($stats['total_samples']) }}</div>
            </div>
            <div class="w-10 h-10 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center font-bold">PPS</div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-emerald-600 uppercase tracking-wider block">Disetujui (Bulk Ready)</span>
                <div class="mt-1 text-2xl font-black text-emerald-600">{{ number_format($stats['approved_samples']) }}</div>
            </div>
            <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">✓</div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-amber-600 uppercase tracking-wider block">Dalam Review / Hold</span>
                <div class="mt-1 text-2xl font-black text-amber-600">{{ number_format($stats['pending_samples']) }}</div>
            </div>
            <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center font-bold">⏳</div>
        </div>
    </div>

    <!-- Table Section -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <!-- Filter Bar -->
        <div class="p-4 border-b border-slate-100 bg-slate-50/50 flex flex-wrap items-center justify-between gap-3">
            <form action="{{ route('planning.pps.index') }}" method="GET" class="flex flex-wrap items-center gap-3 flex-1">
                <div class="relative flex-1 min-w-[220px]">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari Style Code, PO, atau Nama Mekanik..." class="w-full text-xs rounded-lg border-slate-200 pl-8 pr-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                    <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0118 0z"/></svg>
                </div>
                <div class="flex items-center space-x-1">
                    <a href="{{ route('planning.pps.index', ['search' => request('search')]) }}" class="px-3 py-2 text-xs font-semibold rounded-lg {{ !request('status') ? 'bg-slate-800 text-white' : 'text-slate-600 hover:bg-slate-200' }}">Semua</a>
                    <a href="{{ route('planning.pps.index', ['status' => 'approved', 'search' => request('search')]) }}" class="px-3 py-2 text-xs font-semibold rounded-lg {{ request('status') === 'approved' ? 'bg-emerald-600 text-white' : 'text-slate-600 hover:bg-slate-200' }}">Disetujui</a>
                    <a href="{{ route('planning.pps.index', ['status' => 'pending', 'search' => request('search')]) }}" class="px-3 py-2 text-xs font-semibold rounded-lg {{ request('status') === 'pending' ? 'bg-amber-600 text-white' : 'text-slate-600 hover:bg-slate-200' }}">Pending</a>
                </div>
                <button type="submit" class="px-3.5 py-2 text-xs font-semibold rounded-lg bg-slate-800 text-white hover:bg-slate-900 transition">Filter</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-500 uppercase font-semibold text-[11px] border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4">ID PPS</th>
                        <th class="py-3 px-4">PO & Model Style</th>
                        <th class="py-3 px-4">Tahapan Sampel</th>
                        <th class="py-3 px-4">Mekanik Penanggung Jawab</th>
                        <th class="py-3 px-4">Mesin Sampel</th>
                        <th class="py-3 px-4">Status Produksi Massal</th>
                        <th class="py-3 px-4">Catatan / History</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($samples as $sample)
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="py-3 px-4 font-mono font-bold text-slate-700">#{{ $sample->id }}</td>
                        <td class="py-3 px-4">
                            <span class="font-bold text-slate-900">{{ $sample->orderStyle->order->po_number ?? 'PO-N/A' }}</span>
                            <span class="text-xs text-indigo-700 block font-mono font-semibold">{{ $sample->orderStyle->style_code ?? 'STYLE-N/A' }}</span>
                        </td>
                        <td class="py-3 px-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-indigo-50 text-indigo-700">
                                {{ $sample->sample_stage ? $sample->sample_stage->value : 'PRE_PRODUCTION' }}
                            </span>
                        </td>
                        <td class="py-3 px-4 font-medium text-slate-800">
                            {{ $sample->mechanic_name ?: ($sample->mechanic->name ?? '-') }}
                        </td>
                        <td class="py-3 px-4 font-mono text-slate-700">
                            {{ $sample->machine->machine_number ?? '-' }}
                        </td>
                        <td class="py-3 px-4">
                            @if ($sample->is_approved_for_bulk)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800">
                                    ✓ Disetujui Massal
                                </span>
                                <span class="text-[10px] text-slate-400 block mt-0.5">{{ $sample->approved_at ? $sample->approved_at->format('d/m/Y H:i') : '' }}</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800">
                                    ⏳ Dalam Review
                                </span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-slate-500 max-w-xs truncate text-[11px]">
                            {{ $sample->remarks ?: '-' }}
                        </td>
                        <td class="py-3 px-4 text-center">
                            <button onclick="openApprovalModal({{ $sample->id }}, {{ $sample->is_approved_for_bulk ? 1 : 0 }}, '{{ $sample->sample_stage ? $sample->sample_stage->value : 'PRE_PRODUCTION' }}', '{{ addslashes($sample->mechanic_name ?? '') }}', '{{ addslashes($sample->remarks ?? '') }}', {{ $sample->machine_id ?? 'null' }})" 
                                    class="px-2.5 py-1 text-xs font-semibold rounded bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                                Update
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-8 text-center text-slate-400">Tidak ada data PPS yang sesuai kriteria pencarian.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100">
            {{ $samples->appends(['status' => request('status'), 'search' => request('search')])->links() }}
        </div>
    </div>
</div>

<!-- MODAL UPDATE STATUS PPS -->
<div id="modalApproval" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl border border-slate-200">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900">Update Keputusan Approval PPS <span id="modalPpsId" class="font-mono text-emerald-600"></span></h3>
            <button onclick="document.getElementById('modalApproval').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">&times;</button>
        </div>
        <form id="formApproval" method="POST" class="mt-4 space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Keputusan Persetujuan Produksi Massal</label>
                <select name="is_approved_for_bulk" id="modalApproved" class="w-full text-xs rounded-lg border-slate-300 py-2">
                    <option value="1">✓ SETUJUI (Boleh Jalan Bulk)</option>
                    <option value="0">⏳ TAHAN / REVISI / PENDING</option>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tahap Sampel</label>
                    <select name="sample_stage" id="modalStage" class="w-full text-xs rounded-lg border-slate-300 py-2">
                        <option value="PROTO">PROTO</option>
                        <option value="FIT">FIT</option>
                        <option value="PRE_PRODUCTION">PRE PRODUCTION</option>
                        <option value="PILOT">PILOT</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Mesin Sampel</label>
                    <select name="machine_id" id="modalMachine" class="w-full text-xs rounded-lg border-slate-300 py-2">
                        <option value="">-- Tanpa Mesin Khusus --</option>
                        @foreach ($availableMachines as $mc)
                            <option value="{{ $mc->id }}">{{ $mc->machine_number }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Mekanik / Penanggung Jawab</label>
                <input type="text" name="mechanic_name" id="modalMechanic" placeholder="Nama teknisi / mekanik" class="w-full text-xs rounded-lg border-slate-300 py-2">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Evaluasi / Catatan QA</label>
                <textarea name="remarks" id="modalRemarks" rows="3" placeholder="Catatan hasil fitting, jarum, kerapatan benang, dll..." class="w-full text-xs rounded-lg border-slate-300 py-2"></textarea>
            </div>
            <div class="flex justify-end space-x-2 pt-4 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('modalApproval').classList.add('hidden')" class="px-4 py-2 text-xs font-semibold rounded-lg text-slate-600 hover:bg-slate-100">Batal</button>
                <button type="submit" class="px-4 py-2 text-xs font-bold rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm">Simpan Keputusan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openApprovalModal(id, approved, stage, mechanic, remarks, machineId) {
    document.getElementById('modalPpsId').innerText = '#' + id;
    document.getElementById('formApproval').action = '/planning/pps/' + id;
    document.getElementById('modalApproved').value = approved ? "1" : "0";
    document.getElementById('modalStage').value = stage || "PRE_PRODUCTION";
    document.getElementById('modalMechanic').value = mechanic || "";
    document.getElementById('modalRemarks').value = remarks || "";
    document.getElementById('modalMachine').value = machineId || "";
    document.getElementById('modalApproval').classList.remove('hidden');
}
</script>
@endsection
