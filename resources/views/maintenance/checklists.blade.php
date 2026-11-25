@extends('layouts.app')

@section('title', 'Preventive Maintenance Checklists')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Preventive Maintenance (PM) Checklists</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Lembar cek fisik berkala, servis preventif & penggantian suku cadang mesin.</p>
        </div>
        <button onclick="document.getElementById('checklistModal').classList.remove('hidden')"
                class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 shadow-sm transition">
            + Catat Servis PM Baru
        </button>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-lg bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <!-- Filter Card -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <form method="GET" action="{{ route('maintenance.checklists') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Tanggal Servis</label>
                <input type="date" name="date" value="{{ request('date') }}"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Area Pabrik</label>
                <select name="area_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                    <option value="">Semua Area</option>
                    @foreach($areas as $area)
                        <option value="{{ $area->id }}" {{ request('area_id') == $area->id ? 'selected' : '' }}>
                            {{ $area->area_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 rounded-lg text-sm font-medium hover:bg-gray-800">
                    Filter
                </button>
                <a href="{{ route('maintenance.checklists') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-50 dark:hover:bg-gray-700">
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
                        <th class="py-3.5 px-4">Tanggal</th>
                        <th class="py-3.5 px-4">Mesin</th>
                        <th class="py-3.5 px-4">Area</th>
                        <th class="py-3.5 px-4">Teknisi / QC</th>
                        <th class="py-3.5 px-4">Item Checklist & Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($records as $rec)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-750/50 transition">
                            <td class="py-3.5 px-4 font-medium text-gray-900 dark:text-white whitespace-nowrap">
                                {{ $rec->service_date?->format('d M Y') ?? '-' }}
                            </td>
                            <td class="py-3.5 px-4 text-gray-900 dark:text-white font-semibold">
                                Mesin #{{ $rec->machine->machine_number ?? $rec->machine_id }}
                            </td>
                            <td class="py-3.5 px-4 text-gray-600 dark:text-gray-300">
                                {{ $rec->factoryArea->area_name ?? '-' }}
                            </td>
                            <td class="py-3.5 px-4 text-gray-600 dark:text-gray-300">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $rec->technician_name }}</div>
                                <div class="text-xs text-gray-400">Insp: {{ $rec->inspector_name ?? '-' }}</div>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="space-y-1">
                                    @foreach($rec->checklists as $item)
                                        <div class="flex items-center gap-2 text-xs">
                                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ $item->item_name }}:</span>
                                            @if($item->status === 'OK')
                                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">OK</span>
                                            @elseif($item->status === 'NOT_OK')
                                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                                    NOT OK @if($item->sparepart_name)({{ $item->sparepart_qty }}x {{ $item->sparepart_name }})@endif
                                                </span>
                                            @else
                                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">N/A</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-gray-500 dark:text-gray-400">
                                Belum ada catatan checklist servis PM.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-gray-200 dark:border-gray-700">
            {{ $records->links() }}
        </div>
    </div>
</div>

<!-- Modal Catat PM -->
<div id="checklistModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-2xl w-full p-6 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between pb-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Form Checklist Preventive Maintenance</h3>
            <button onclick="document.getElementById('checklistModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <form action="{{ route('maintenance.checklists.store') }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Mesin *</label>
                    <select name="machine_id" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                        @foreach($machines as $m)
                            <option value="{{ $m->id }}">Mesin #{{ $m->machine_number }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Area *</label>
                    <select name="factory_area_id" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                        @foreach($areas as $a)
                            <option value="{{ $a->id }}">{{ $a->area_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal Servis *</label>
                    <input type="date" name="service_date" required value="{{ date('Y-m-d') }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Teknisi *</label>
                    <input type="text" name="technician_name" required value="{{ auth()->user()->name ?? 'Teknisi Utama' }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                </div>
            </div>

            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">Item Pemeriksaan Standar:</h4>
                <div class="space-y-3">
                    <div class="grid grid-cols-12 gap-2 items-center text-xs">
                        <input type="hidden" name="items[0][name]" value="Kebersihan Silinder & Dial">
                        <div class="col-span-5 font-medium text-gray-700 dark:text-gray-300">1. Kebersihan Silinder & Dial</div>
                        <div class="col-span-3">
                            <select name="items[0][status]" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs">
                                <option value="OK">OK</option>
                                <option value="NOT_OK">NOT OK</option>
                                <option value="NA">N/A</option>
                            </select>
                        </div>
                        <div class="col-span-4">
                            <input type="text" name="items[0][sparepart_name]" placeholder="Nama part (opsional)" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs">
                        </div>
                    </div>

                    <div class="grid grid-cols-12 gap-2 items-center text-xs">
                        <input type="hidden" name="items[1][name]" value="Pelumasan & Oli Sentral">
                        <div class="col-span-5 font-medium text-gray-700 dark:text-gray-300">2. Pelumasan & Oli Sentral</div>
                        <div class="col-span-3">
                            <select name="items[1][status]" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs">
                                <option value="OK">OK</option>
                                <option value="NOT_OK">NOT OK</option>
                                <option value="NA">N/A</option>
                            </select>
                        </div>
                        <div class="col-span-4">
                            <input type="text" name="items[1][sparepart_name]" placeholder="Nama part (opsional)" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs">
                        </div>
                    </div>

                    <div class="grid grid-cols-12 gap-2 items-center text-xs">
                        <input type="hidden" name="items[2][name]" value="Kondisi Timing Belt & Motor">
                        <div class="col-span-5 font-medium text-gray-700 dark:text-gray-300">3. Kondisi Timing Belt & Motor</div>
                        <div class="col-span-3">
                            <select name="items[2][status]" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs">
                                <option value="OK">OK</option>
                                <option value="NOT_OK">NOT OK</option>
                                <option value="NA">N/A</option>
                            </select>
                        </div>
                        <div class="col-span-4">
                            <input type="text" name="items[2][sparepart_name]" placeholder="Timing Belt 140XL" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs">
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button type="button" onclick="document.getElementById('checklistModal').classList.add('hidden')" class="px-4 py-2 border rounded-lg text-sm text-gray-700 dark:text-gray-300">Batal</button>
                <button type="submit" class="px-4 py-2 bg-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700">Simpan Checklist PM</button>
            </div>
        </form>
    </div>
</div>
@endsection
