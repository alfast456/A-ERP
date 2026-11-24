@extends('layouts.app')

@section('title', 'Tiket Servis & Pemeliharaan Mesin')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Tiket Servis & Pengaduan Mesin</h1>
            <p class="text-sm text-slate-500 mt-1">Manajemen eskalasi perbaikan mekanik dan riwayat pengaduan kerusakan mesin.</p>
        </div>
        <div>
            <button onclick="document.getElementById('modal-new-ticket').classList.remove('hidden')" class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-sm py-2 px-4 rounded-md shadow-sm transition flex items-center gap-1.5">
                <span>+ Buat Tiket Servis</span>
            </button>
        </div>
    </div>

    <!-- Status Strip -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-sm">
            <span class="text-xs font-medium text-slate-500 uppercase tracking-wider block">Tiket Open (Baru)</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-amber-600">{{ number_format($openCount) }}</span>
                <span class="text-xs text-slate-400">tiket</span>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-sm">
            <span class="text-xs font-medium text-slate-500 uppercase tracking-wider block">Dalam Penanganan</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-sky-600">{{ number_format($inProgressCount) }}</span>
                <span class="text-xs text-slate-400">tiket</span>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-sm">
            <span class="text-xs font-medium text-slate-500 uppercase tracking-wider block">Selesai (Resolved)</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-emerald-600">{{ number_format($resolvedCount) }}</span>
                <span class="text-xs text-slate-400">tiket</span>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-sm">
            <span class="text-xs font-medium text-slate-500 uppercase tracking-wider block">Ditutup (Closed)</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-slate-600">{{ number_format($closedCount) }}</span>
                <span class="text-xs text-slate-400">tiket</span>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-sm">
        <form method="GET" action="{{ route('maintenance.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Cari Tiket / Masalah</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="No tiket atau deskripsi" class="w-full text-sm border-slate-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Status Tiket</label>
                <select name="status" class="w-full text-sm border-slate-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">Semua Status</option>
                    <option value="OPEN" {{ request('status') === 'OPEN' ? 'selected' : '' }}>Open</option>
                    <option value="IN_PROGRESS" {{ request('status') === 'IN_PROGRESS' ? 'selected' : '' }}>In Progress</option>
                    <option value="RESOLVED" {{ request('status') === 'RESOLVED' ? 'selected' : '' }}>Resolved</option>
                    <option value="CLOSED" {{ request('status') === 'CLOSED' ? 'selected' : '' }}>Closed</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Tingkat Prioritas</label>
                <select name="priority" class="w-full text-sm border-slate-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">Semua Prioritas</option>
                    <option value="LOW" {{ request('priority') === 'LOW' ? 'selected' : '' }}>Low</option>
                    <option value="MEDIUM" {{ request('priority') === 'MEDIUM' ? 'selected' : '' }}>Medium</option>
                    <option value="HIGH" {{ request('priority') === 'HIGH' ? 'selected' : '' }}>High</option>
                    <option value="URGENT" {{ request('priority') === 'URGENT' ? 'selected' : '' }}>Urgent</option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 bg-slate-800 hover:bg-slate-900 text-white text-sm font-medium py-2 px-3 rounded-md transition shadow-sm">Filter</button>
                <a href="{{ route('maintenance.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm font-medium py-2 px-3 rounded-md transition">Reset</a>
            </div>
        </form>
    </div>

    <!-- Ticket Data Grid -->
    <div class="bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-slate-600 font-semibold text-xs uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-4">No. Tiket</th>
                        <th class="py-3 px-4">Mesin Target</th>
                        <th class="py-3 px-4">Prioritas</th>
                        <th class="py-3 px-4">Uraian Kendala Mesin</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4">Pelapor</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($tickets as $tkt)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3 px-4 font-mono font-bold text-slate-900 text-xs">
                                {{ $tkt->ticket_number }}
                            </td>
                            <td class="py-3 px-4">
                                <span class="font-mono font-semibold text-slate-800 text-xs">{{ $tkt->machine?->machine_number ?? 'MC-N/A' }}</span>
                                <span class="text-xs text-slate-400 block">{{ $tkt->machine?->factoryArea?->area_name ?? '-' }}</span>
                            </td>
                            <td class="py-3 px-4">
                                @if($tkt->priority->value === 'URGENT')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-rose-100 text-rose-800">
                                        ⚡ URGENT
                                    </span>
                                @elseif($tkt->priority->value === 'HIGH')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-amber-100 text-amber-800">
                                        HIGH
                                    </span>
                                @elseif($tkt->priority->value === 'MEDIUM')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700">
                                        MEDIUM
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-50 text-slate-500 border border-slate-200">
                                        LOW
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-slate-700 max-w-sm">
                                <p class="line-clamp-2 text-xs">{{ $tkt->issue_description }}</p>
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if($tkt->status->value === 'RESOLVED')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                        ✓ Resolved
                                    </span>
                                @elseif($tkt->status->value === 'OPEN')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                        ● Open
                                    </span>
                                @elseif($tkt->status->value === 'IN_PROGRESS')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-sky-100 text-sky-800">
                                        ⏳ In Progress
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                        Closed
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-xs text-slate-500">
                                {{ $tkt->reporter?->name ?? 'Operator' }}
                            </td>
                            <td class="py-3 px-4 text-right">
                                @if($tkt->status->value === 'OPEN' || $tkt->status->value === 'IN_PROGRESS')
                                    <form method="POST" action="{{ route('maintenance.updateStatus', $tkt) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="RESOLVED">
                                        <button type="submit" class="bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-300 text-xs font-semibold py-1 px-2.5 rounded transition">
                                            Tandai Selesai
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-slate-400">Tuntas</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400">
                                Tidak ada tiket servis yang sesuai dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-200">
            {{ $tickets->links() }}
        </div>
    </div>
</div>

<!-- Modal Buat Tiket -->
<div id="modal-new-ticket" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-xl shadow-xl max-w-lg w-full p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-200 pb-3">
            <h3 class="text-lg font-bold text-slate-900">Terbitkan Tiket Servis Mesin</h3>
            <button onclick="document.getElementById('modal-new-ticket').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">✕</button>
        </div>
        <form method="POST" action="{{ route('maintenance.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Mesin Mengalami Kendala</label>
                <select name="machine_id" required class="w-full text-sm border-slate-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">-- Pilih Mesin --</option>
                    @foreach($machines as $m)
                        <option value="{{ $m->id }}">{{ $m->machine_number }} ({{ $m->factoryArea?->area_name }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Tingkat Prioritas</label>
                <select name="priority" required class="w-full text-sm border-slate-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="MEDIUM">Medium (Standar)</option>
                    <option value="HIGH">High (Penting - Menghambat Target)</option>
                    <option value="URGENT">Urgent (Mesin Berhenti Total)</option>
                    <option value="LOW">Low (Perawatan Ringan)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Uraian Kendala / Permasalahan</label>
                <textarea name="issue_description" rows="3" required placeholder="Jelaskan kendala mesin secara spesifik..." class="w-full text-sm border-slate-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('modal-new-ticket').classList.add('hidden')" class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium py-2 px-4 rounded-md">Batal</button>
                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold py-2 px-4 rounded-md shadow-sm">Kirim Tiket</button>
            </div>
        </form>
    </div>
</div>
@endsection
