@extends('layouts.app')

@section('title', 'PPIC & Alokasi Kapasitas Mesin')

@section('content')
<div class="space-y-6">
    <!-- Header Page -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Perencanaan Produksi & Penjadwalan Mesin (PPIC)</h1>
            <p class="text-sm text-slate-500">Alokasi beban order, kapasitas harian (daily load), dan penugasan fisik 3.036 unit mesin rajut.</p>
        </div>
        <div class="flex items-center space-x-2">
            <a href="{{ route('planning.pps.index') }}" class="inline-flex items-center px-3.5 py-2 text-xs font-semibold rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition shadow-sm border border-indigo-200">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Approval PPS Sampel
            </a>
            <a href="{{ route('planning.holidays.index') }}" class="inline-flex items-center px-3.5 py-2 text-xs font-semibold rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 transition shadow-sm border border-slate-300">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Kalender Libur Pabrik
            </a>
        </div>
    </div>

    <!-- Summary Metrics -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Alokasi Planning</span>
            <div class="mt-1 text-2xl font-black text-slate-800">{{ number_format($stats['total_allocations']) }}</div>
            <span class="text-xs text-slate-500 mt-1 block">Batch alokasi style</span>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Total Beban Order</span>
            <div class="mt-1 text-2xl font-black text-emerald-600">{{ number_format($stats['total_allocated_pcs']) }}</div>
            <span class="text-xs text-emerald-600 mt-1 block font-medium">Pieces (Pcs)</span>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Jadwal Harian (Daily Load)</span>
            <div class="mt-1 text-2xl font-black text-blue-600">{{ number_format($stats['total_daily_loads']) }}</div>
            <span class="text-xs text-slate-500 mt-1 block">Record beban tanggal</span>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Penugasan Mesin</span>
            <div class="mt-1 text-2xl font-black text-indigo-600">{{ number_format($stats['total_machine_assignments']) }}</div>
            <span class="text-xs text-slate-500 mt-1 block">Alokasi fisik per unit</span>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm col-span-2 md:col-span-1">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">PPS Disetujui</span>
            <div class="mt-1 text-2xl font-black text-amber-600">{{ number_format($stats['total_approved_pps']) }}</div>
            <span class="text-xs text-amber-600 mt-1 block font-medium">Bulk Ready</span>
        </div>
    </div>

    <!-- Navigation Tabs & Actions -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="border-b border-slate-200 bg-slate-50/70 px-4 py-3 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="flex items-center space-x-2 text-xs font-semibold">
                <a href="{{ route('planning.index', ['tab' => 'allocations']) }}" 
                   class="px-3.5 py-2 rounded-lg transition {{ $tab === 'allocations' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-200/60' }}">
                    1. Alokasi Planning ({{ number_format($stats['total_allocations']) }})
                </a>
                <a href="{{ route('planning.index', ['tab' => 'daily_loads']) }}" 
                   class="px-3.5 py-2 rounded-lg transition {{ $tab === 'daily_loads' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-200/60' }}">
                    2. Beban Mesin Harian ({{ number_format($stats['total_daily_loads']) }})
                </a>
                <a href="{{ route('planning.index', ['tab' => 'assignments']) }}" 
                   class="px-3.5 py-2 rounded-lg transition {{ $tab === 'assignments' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-200/60' }}">
                    3. Penugasan Mesin Fisik ({{ number_format($stats['total_machine_assignments']) }})
                </a>
            </div>

            <div class="flex items-center space-x-2">
                @if ($tab === 'allocations')
                    <button onclick="document.getElementById('modalAddAllocation').classList.remove('hidden')" class="inline-flex items-center px-3 py-1.5 text-xs font-bold rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition shadow-sm">
                        + Tambah Alokasi Order
                    </button>
                @elseif ($tab === 'daily_loads')
                    <button onclick="document.getElementById('modalAddDailyLoad').classList.remove('hidden')" class="inline-flex items-center px-3 py-1.5 text-xs font-bold rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition shadow-sm">
                        + Entri Beban Tanggal
                    </button>
                @else
                    <button onclick="document.getElementById('modalAddAssignment').classList.remove('hidden')" class="inline-flex items-center px-3 py-1.5 text-xs font-bold rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition shadow-sm">
                        + Assign Mesin Fisik
                    </button>
                @endif
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="p-4 border-b border-slate-100 bg-white">
            <form action="{{ route('planning.index') }}" method="GET" class="flex flex-wrap items-center gap-3">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <div class="relative flex-1 min-w-[200px]">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari PO, Style Code, atau No Mesin..." class="w-full text-xs rounded-lg border-slate-200 pl-8 pr-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                    <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0118 0z"/></svg>
                </div>
                @if ($tab === 'allocations')
                <select name="factory_area_id" class="text-xs rounded-lg border-slate-200 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">Semua Area Pabrik</option>
                    @foreach ($factoryAreas as $fa)
                        <option value="{{ $fa->id }}" {{ request('factory_area_id') == $fa->id ? 'selected' : '' }}>{{ $fa->area_code }} - {{ $fa->area_name }}</option>
                    @endforeach
                </select>
                @endif
                <button type="submit" class="px-4 py-2 text-xs font-semibold rounded-lg bg-slate-800 text-white hover:bg-slate-900 transition">Filter</button>
                @if (request()->hasAny(['search', 'factory_area_id']))
                    <a href="{{ route('planning.index', ['tab' => $tab]) }}" class="text-xs text-slate-500 hover:underline">Reset</a>
                @endif
            </form>
        </div>

        <!-- TAB CONTENT 1: ALOKASI PLANNING -->
        @if ($tab === 'allocations')
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-500 uppercase font-semibold text-[11px] border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4">ID Plan</th>
                        <th class="py-3 px-4">PO & Model Style</th>
                        <th class="py-3 px-4">Area Pabrik</th>
                        <th class="py-3 px-4 text-right">Alokasi (Pcs)</th>
                        <th class="py-3 px-4">Target Mulai - Selesai</th>
                        <th class="py-3 px-4 text-center">Mesin Fisik Assigned</th>
                        <th class="py-3 px-4 text-center">Beban Harian Terjadwal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($allocations as $alloc)
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="py-3 px-4 font-mono font-bold text-slate-700">#{{ $alloc->id }}</td>
                        <td class="py-3 px-4">
                            <div class="font-bold text-slate-900">{{ $alloc->order->po_number ?? 'PO-N/A' }}</div>
                            <div class="text-[11px] text-slate-500">
                                @foreach ($alloc->order->orderStyles ?? [] as $st)
                                    <span class="inline-block bg-slate-100 px-1.5 py-0.5 rounded text-slate-600 font-mono mr-1">{{ $st->style_code }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td class="py-3 px-4">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800">
                                {{ $alloc->factoryArea->area_code ?? '-' }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-right font-bold text-slate-900">
                            {{ number_format($alloc->allocated_pcs) }} pcs
                        </td>
                        <td class="py-3 px-4">
                            <span class="text-slate-700 font-medium">{{ $alloc->target_start_date->format('d M Y') }}</span>
                            <span class="text-slate-400 mx-1">s/d</span>
                            <span class="text-slate-700 font-medium">{{ $alloc->target_end_date->format('d M Y') }}</span>
                        </td>
                        <td class="py-3 px-4 text-center">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ $alloc->machine_assignments_count > 0 ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-400' }}">
                                {{ $alloc->machine_assignments_count }} Mesin
                            </span>
                        </td>
                        <td class="py-3 px-4 text-center">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700">
                                {{ $alloc->dailyLoads->count() }} Hari
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-400">Tidak ada data alokasi planning yang sesuai.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100">
            {{ $allocations->appends(['tab' => 'allocations', 'search' => request('search'), 'factory_area_id' => request('factory_area_id')])->links() }}
        </div>

        <!-- TAB CONTENT 2: BEBAN KAPASITAS HARIAN -->
        @elseif ($tab === 'daily_loads')
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-500 uppercase font-semibold text-[11px] border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4">Tanggal Plan</th>
                        <th class="py-3 px-4">PO & Alokasi ID</th>
                        <th class="py-3 px-4">Area Pabrik</th>
                        <th class="py-3 px-4 text-center">Kapasitas Mesin (Unit)</th>
                        <th class="py-3 px-4 text-right">Target Produksi Harian (Pcs)</th>
                        <th class="py-3 px-4">Rentang Start - Stop Mesin</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($dailyLoads as $load)
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="py-3 px-4 font-bold text-slate-800">
                            {{ $load->plan_date ? $load->plan_date->format('d M Y (D)') : '-' }}
                        </td>
                        <td class="py-3 px-4">
                            <span class="font-bold text-slate-900">{{ $load->planningAllocation->order->po_number ?? 'PO-N/A' }}</span>
                            <span class="text-xs text-slate-400 block font-mono">Plan #{{ $load->planning_allocation_id }}</span>
                        </td>
                        <td class="py-3 px-4">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                {{ $load->planningAllocation->factoryArea->area_code ?? 'KK' }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-center">
                            <span class="inline-flex items-center px-3 py-1 rounded-full font-bold text-xs bg-slate-100 text-slate-800">
                                {{ $load->planned_machine_count }} Unit Mesin
                            </span>
                        </td>
                        <td class="py-3 px-4 text-right font-bold text-emerald-600">
                            {{ number_format($load->daily_target_pcs) }} pcs
                        </td>
                        <td class="py-3 px-4 text-slate-500 text-[11px]">
                            {{ $load->start_date ? $load->start_date->format('d/m/Y') : '-' }} s/d {{ $load->stop_date ? $load->stop_date->format('d/m/Y') : '-' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-400">Tidak ada jadwal beban mesin harian.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100">
            {{ $dailyLoads->appends(['tab' => 'daily_loads', 'search' => request('search')])->links() }}
        </div>

        <!-- TAB CONTENT 3: PENUGASAN MESIN FISIK -->
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-500 uppercase font-semibold text-[11px] border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4">Nomor Mesin Fisik</th>
                        <th class="py-3 px-4">Area & Tipe Jarum</th>
                        <th class="py-3 px-4">PO & SKU Shipment</th>
                        <th class="py-3 px-4">Mulai Operasi</th>
                        <th class="py-3 px-4">Selesai Operasi</th>
                        <th class="py-3 px-4 text-right">Target Produksi (Pcs)</th>
                        <th class="py-3 px-4 text-center">Status Jadwal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($machineAssignments as $assign)
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="py-3 px-4 font-mono font-bold text-indigo-700">
                            {{ $assign->machine->machine_number ?? "MC-{$assign->machine_id}" }}
                        </td>
                        <td class="py-3 px-4">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-800 mr-1">
                                {{ $assign->machine->factoryArea->area_code ?? '-' }}
                            </span>
                            <span class="text-slate-500 text-[11px] font-mono">{{ $assign->machine->jarum ?? '144N' }}</span>
                        </td>
                        <td class="py-3 px-4">
                            <span class="font-bold text-slate-900">{{ $assign->planningAllocation->order->po_number ?? 'PO-N/A' }}</span>
                            <span class="text-[11px] text-slate-500 block font-mono">SKU #{{ $assign->order_shipment_id }}</span>
                        </td>
                        <td class="py-3 px-4 text-slate-700 font-medium">
                            {{ $assign->start_time ? $assign->start_time->format('d M Y H:i') : ($assign->plan_date ? $assign->plan_date->format('d M Y') : '-') }}
                        </td>
                        <td class="py-3 px-4 text-slate-700 font-medium">
                            {{ $assign->stop_time ? $assign->stop_time->format('d M Y H:i') : '-' }}
                        </td>
                        <td class="py-3 px-4 text-right font-bold text-slate-900">
                            {{ number_format($assign->target_pcs) }} pcs
                            <span class="text-xs text-slate-400 block font-normal">({{ $assign->target_dz }} dz)</span>
                        </td>
                        <td class="py-3 px-4 text-center">
                            @if ($assign->stop_time && $assign->stop_time->isPast())
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600">Selesai</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-800">Aktif Berjalan</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-400">Tidak ada penugasan fisik mesin rajut yang terdaftar.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100">
            {{ $machineAssignments->appends(['tab' => 'assignments', 'search' => request('search')])->links() }}
        </div>
        @endif
    </div>
</div>

<!-- MODAL 1: TAMBAH ALOKASI PLANNING -->
<div id="modalAddAllocation" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl border border-slate-200">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900">Tambah Alokasi Planning Baru</h3>
            <button onclick="document.getElementById('modalAddAllocation').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">&times;</button>
        </div>
        <form action="{{ route('planning.allocations.store') }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Order (PO Number)</label>
                <select name="order_id" required class="w-full text-xs rounded-lg border-slate-300 py-2">
                    <option value="">-- Pilih Order / PO --</option>
                    @foreach ($recentOrders as $ro)
                        <option value="{{ $ro->id }}">{{ $ro->po_number }} (Order #{{ $ro->id }})</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Area Pabrik Rajut</label>
                    <select name="factory_area_id" required class="w-full text-xs rounded-lg border-slate-300 py-2">
                        @foreach ($factoryAreas as $fa)
                            <option value="{{ $fa->id }}">{{ $fa->area_code }} - {{ $fa->area_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Alokasi Kuantitas (Pcs)</label>
                    <input type="number" name="allocated_pcs" required min="1" placeholder="Contoh: 12000" class="w-full text-xs rounded-lg border-slate-300 py-2">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Target Tanggal Mulai</label>
                    <input type="date" name="target_start_date" required value="{{ date('Y-m-d') }}" class="w-full text-xs rounded-lg border-slate-300 py-2">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Target Tanggal Selesai</label>
                    <input type="date" name="target_end_date" required value="{{ date('Y-m-d', strtotime('+14 days')) }}" class="w-full text-xs rounded-lg border-slate-300 py-2">
                </div>
            </div>
            <div class="flex justify-end space-x-2 pt-4 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('modalAddAllocation').classList.add('hidden')" class="px-4 py-2 text-xs font-semibold rounded-lg text-slate-600 hover:bg-slate-100">Batal</button>
                <button type="submit" class="px-4 py-2 text-xs font-bold rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm">Simpan Alokasi</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 2: TAMBAH BEBAN HARIAN -->
<div id="modalAddDailyLoad" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl border border-slate-200">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900">Tambah Beban Mesin Harian (Daily Load)</h3>
            <button onclick="document.getElementById('modalAddDailyLoad').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">&times;</button>
        </div>
        <form action="{{ route('planning.dailyLoads.store') }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">ID Alokasi Planning Terkait</label>
                <input type="number" name="planning_allocation_id" required placeholder="Masukkan ID Alokasi Planning (contoh: 1)" class="w-full text-xs rounded-lg border-slate-300 py-2">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Rencana</label>
                    <input type="date" name="plan_date" required value="{{ date('Y-m-d') }}" class="w-full text-xs rounded-lg border-slate-300 py-2">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Jumlah Mesin yang Jalan</label>
                    <input type="number" name="planned_machine_count" required min="1" placeholder="Contoh: 5 unit" class="w-full text-xs rounded-lg border-slate-300 py-2">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Target Harian (Pcs - Kosongkan untuk auto x100)</label>
                <input type="number" name="daily_target_pcs" placeholder="Contoh: 500" class="w-full text-xs rounded-lg border-slate-300 py-2">
            </div>
            <div class="flex justify-end space-x-2 pt-4 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('modalAddDailyLoad').classList.add('hidden')" class="px-4 py-2 text-xs font-semibold rounded-lg text-slate-600 hover:bg-slate-100">Batal</button>
                <button type="submit" class="px-4 py-2 text-xs font-bold rounded-lg bg-blue-600 text-white hover:bg-blue-700 shadow-sm">Simpan Beban Tanggal</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 3: PENUGASAN MESIN FISIK -->
<div id="modalAddAssignment" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl border border-slate-200">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900">Penugasan Unit Mesin Fisik (Assign Machine)</h3>
            <button onclick="document.getElementById('modalAddAssignment').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">&times;</button>
        </div>
        <form action="{{ route('planning.machineAssignments.store') }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Mesin Fisik</label>
                <select name="machine_id" required class="w-full text-xs rounded-lg border-slate-300 py-2">
                    <option value="">-- Pilih Nomor Mesin --</option>
                    @foreach ($activeMachines as $mc)
                        <option value="{{ $mc->id }}">{{ $mc->machine_number }} ({{ $mc->brand }} - {{ $mc->jarum }})</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">ID Alokasi Planning</label>
                    <input type="number" name="planning_allocation_id" required placeholder="Contoh: 1" class="w-full text-xs rounded-lg border-slate-300 py-2">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">ID Shipment SKU</label>
                    <input type="number" name="order_shipment_id" required placeholder="Contoh: 1" class="w-full text-xs rounded-lg border-slate-300 py-2">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Waktu Mulai Jalan</label>
                    <input type="datetime-local" name="start_time" required value="{{ date('Y-m-d\T08:00') }}" class="w-full text-xs rounded-lg border-slate-300 py-2">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Waktu Selesai</label>
                    <input type="datetime-local" name="stop_time" required value="{{ date('Y-m-d\T16:00', strtotime('+7 days')) }}" class="w-full text-xs rounded-lg border-slate-300 py-2">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Target Produksi Mesin Ini (Pcs)</label>
                <input type="number" name="target_pcs" required min="1" placeholder="Contoh: 2400" class="w-full text-xs rounded-lg border-slate-300 py-2">
            </div>
            <div class="flex justify-end space-x-2 pt-4 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('modalAddAssignment').classList.add('hidden')" class="px-4 py-2 text-xs font-semibold rounded-lg text-slate-600 hover:bg-slate-100">Batal</button>
                <button type="submit" class="px-4 py-2 text-xs font-bold rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 shadow-sm">Assign Mesin</button>
            </div>
        </form>
    </div>
</div>
@endsection
