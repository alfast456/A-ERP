@extends('layouts.app')

@section('title', 'Pencatatan Jarum Patah & Penggantian')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Konsumsi & Penggantian Jarum Mesin</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Monitoring jarum patah, frekuensi penggantian per mesin & shift kerja.</p>
        </div>
        <button onclick="document.getElementById('needleModal').classList.remove('hidden')"
                class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 shadow-sm transition">
            + Catat Jarum Patah / Ganti
        </button>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-lg bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <!-- Metrics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700 shadow-sm">
            <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Jarum Diganti</div>
            <div class="mt-2 text-3xl font-extrabold text-brand-600 dark:text-brand-400">{{ number_format($stats['total_replaced']) }} <span class="text-sm font-normal text-gray-500">pcs</span></div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700 shadow-sm">
            <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Jarum Patah</div>
            <div class="mt-2 text-3xl font-extrabold text-red-600 dark:text-red-400">{{ number_format($stats['total_broken']) }} <span class="text-sm font-normal text-gray-500">pcs</span></div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <form method="GET" action="{{ route('machines.needles') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Tanggal</label>
                <input type="date" name="date" value="{{ request('date') }}"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Pilih Mesin</label>
                <select name="machine_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                    <option value="">Semua Mesin</option>
                    @foreach($machines as $m)
                        <option value="{{ $m->id }}" {{ request('machine_id') == $m->id ? 'selected' : '' }}>
                            Mesin #{{ $m->machine_number }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 rounded-lg text-sm font-medium hover:bg-gray-800">
                    Filter
                </button>
                <a href="{{ route('machines.needles') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-50 dark:hover:bg-gray-700">
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
                        <th class="py-3.5 px-4">Tanggal & Shift</th>
                        <th class="py-3.5 px-4">Mesin</th>
                        <th class="py-3.5 px-4">Tipe Jarum</th>
                        <th class="py-3.5 px-4 text-center">Patah</th>
                        <th class="py-3.5 px-4 text-center">Ganti</th>
                        <th class="py-3.5 px-4">Alasan & Mekanik</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($records as $rec)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-750/50 transition">
                            <td class="py-3.5 px-4 font-medium text-gray-900 dark:text-white whitespace-nowrap">
                                {{ $rec->replacement_date?->format('d M Y') ?? '-' }}
                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                    Shift {{ $rec->shift }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-gray-900 dark:text-white font-semibold">
                                Mesin #{{ $rec->machine->machine_number ?? $rec->machine_id }}
                                <span class="text-xs font-normal text-gray-400 block">{{ $rec->machine->factoryArea->area_name ?? '' }}</span>
                            </td>
                            <td class="py-3.5 px-4 font-mono text-xs text-gray-800 dark:text-gray-200">
                                {{ $rec->needle_type }}
                            </td>
                            <td class="py-3.5 px-4 text-center font-bold text-red-600 dark:text-red-400">
                                {{ $rec->broken_count }}
                            </td>
                            <td class="py-3.5 px-4 text-center font-bold text-brand-600 dark:text-brand-400">
                                {{ $rec->replaced_count }}
                            </td>
                            <td class="py-3.5 px-4 text-gray-600 dark:text-gray-300 text-xs">
                                <div>{{ $rec->reason ?? '-' }}</div>
                                <div class="text-gray-400 mt-0.5">Oleh: {{ $rec->mechanic->name ?? 'Mekanik' }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-gray-500 dark:text-gray-400">
                                Belum ada catatan konsumsi jarum.
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

<!-- Modal Jarum -->
<div id="needleModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full p-6 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between pb-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Form Penggantian Jarum</h3>
            <button onclick="document.getElementById('needleModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <form action="{{ route('machines.needles.store') }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Mesin *</label>
                <select name="machine_id" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                    @foreach($machines as $m)
                        <option value="{{ $m->id }}">Mesin #{{ $m->machine_number }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal *</label>
                    <input type="date" name="replacement_date" required value="{{ date('Y-m-d') }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Shift *</label>
                    <select name="shift" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                        <option value="1">Shift 1 (Pagi)</option>
                        <option value="2">Shift 2 (Siang)</option>
                        <option value="3">Shift 3 (Malam)</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Tipe Jarum *</label>
                <input type="text" name="needle_type" required value="VO 71.85 G01" placeholder="Contoh: VO 71.85 G01" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Jumlah Patah (pcs) *</label>
                    <input type="number" name="broken_count" min="0" required value="1" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Jumlah Ganti (pcs) *</label>
                    <input type="number" name="replaced_count" min="1" required value="1" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Keterangan / Alasan *</label>
                <select name="reason" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                    <option value="BROKEN_HOOK">Kait Patah (BROKEN_HOOK)</option>
                    <option value="BENT">Jarum Bengkok (BENT)</option>
                    <option value="WEAR_TEAR">Aus Terpakai (WEAR_TEAR)</option>
                    <option value="CRASH">Tabrakan / Crash (CRASH)</option>
                </select>
            </div>
            <div class="flex justify-end gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button type="button" onclick="document.getElementById('needleModal').classList.add('hidden')" class="px-4 py-2 border rounded-lg text-sm text-gray-700 dark:text-gray-300">Batal</button>
                <button type="submit" class="px-4 py-2 bg-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700">Simpan Log Jarum</button>
            </div>
        </form>
    </div>
</div>
@endsection
