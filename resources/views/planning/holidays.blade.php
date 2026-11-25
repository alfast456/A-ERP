@extends('layouts.app')

@section('title', 'Kalender Libur Pabrik')

@section('content')
<div class="space-y-6">
    <!-- Header Page -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('planning.index') }}" class="hover:underline">Planning PPIC</a>
                <span>/</span>
                <span>Kalender Libur Pabrik</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Master Kalender Hari Libur Pabrik</h1>
            <p class="text-sm text-slate-500">Penetapan hari libur nasional dan operasional pabrik yang digunakan oleh algoritma estimasi kapasitas dan jadwal mesin.</p>
        </div>
        <div class="flex items-center space-x-2">
            <button onclick="document.getElementById('modalAddHoliday').classList.remove('hidden')" class="inline-flex items-center px-3.5 py-2 text-xs font-bold rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition shadow-sm">
                + Tambah Hari Libur
            </button>
            <a href="{{ route('planning.index') }}" class="inline-flex items-center px-3.5 py-2 text-xs font-semibold rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                &larr; Kembali ke Planning
            </a>
        </div>
    </div>

    <!-- Summary Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Total Libur Tahun {{ $year }}</span>
            <div class="mt-1 text-2xl font-black text-slate-800">{{ number_format($stats['total_holidays']) }} Hari</div>
            <span class="text-xs text-slate-500 mt-1 block">Tercatat di sistem</span>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold text-rose-500 uppercase tracking-wider block">Libur Penuh (Off Produksi)</span>
            <div class="mt-1 text-2xl font-black text-rose-600">{{ number_format($stats['non_working_holidays']) }} Hari</div>
            <span class="text-xs text-rose-500 mt-1 block font-medium">Non-working days</span>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold text-amber-600 uppercase tracking-wider block">Libur Masuk / Lembur</span>
            <div class="mt-1 text-2xl font-black text-amber-600">{{ number_format($stats['working_holidays']) }} Hari</div>
            <span class="text-xs text-slate-500 mt-1 block">Working days override</span>
        </div>
    </div>

    <!-- Table Section -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
            <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Daftar Hari Libur Pabrik</h2>
            <form action="{{ route('planning.holidays.index') }}" method="GET" class="flex items-center space-x-2">
                <select name="year" onchange="this.form.submit()" class="text-xs rounded-lg border-slate-200 py-1.5 focus:ring-emerald-500 focus:border-emerald-500">
                    @for ($y = date('Y') - 2; $y <= date('Y') + 2; $y++)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>Tahun {{ $y }}</option>
                    @endfor
                </select>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-500 uppercase font-semibold text-[11px] border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4">Tanggal Libur</th>
                        <th class="py-3 px-4">Keterangan / Hari Libur</th>
                        <th class="py-3 px-4">Plant / Lokasi</th>
                        <th class="py-3 px-4 text-center">Status Hari Kerja</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($holidays as $h)
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="py-3 px-4 font-bold text-slate-800">
                            {{ $h->holiday_date->format('d M Y (l)') }}
                        </td>
                        <td class="py-3 px-4 font-medium text-slate-900">
                            {{ $h->description }}
                        </td>
                        <td class="py-3 px-4 text-slate-500">
                            {{ $h->plant->name ?? 'Semua Plant' }}
                        </td>
                        <td class="py-3 px-4 text-center">
                            @if ($h->is_working_day)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800">
                                    Lembur / Masuk
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800">
                                    Off Produksi
                                </span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-center">
                            <form action="{{ route('planning.holidays.destroy', $h) }}" method="POST" onsubmit="return confirm('Hapus hari libur {{ $h->description }}?');" class="inline-block">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-rose-600 hover:text-rose-800 font-bold text-xs p-1">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-8 text-center text-slate-400">Belum ada hari libur yang didaftarkan pada tahun {{ $year }}.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100">
            {{ $holidays->appends(['year' => $year])->links() }}
        </div>
    </div>
</div>

<!-- MODAL TAMBAH HARI LIBUR -->
<div id="modalAddHoliday" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900">Tambah Hari Libur Pabrik</h3>
            <button onclick="document.getElementById('modalAddHoliday').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">&times;</button>
        </div>
        <form action="{{ route('planning.holidays.store') }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Libur</label>
                <input type="date" name="holiday_date" required class="w-full text-xs rounded-lg border-slate-300 py-2">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Keterangan Hari Libur</label>
                <input type="text" name="description" required placeholder="Contoh: Tahun Baru Imlek, Cuti Bersama Idul Fitri" class="w-full text-xs rounded-lg border-slate-300 py-2">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Plant Terkait</label>
                <select name="plant_id" class="w-full text-xs rounded-lg border-slate-300 py-2">
                    @foreach ($plants as $p)
                        <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center space-x-2 pt-2">
                <input type="checkbox" name="is_working_day" id="is_working_day" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                <label for="is_working_day" class="text-xs font-medium text-slate-700">Hari ini tetap ada jadwal produksi lembur (Working Day Override)</label>
            </div>
            <div class="flex justify-end space-x-2 pt-4 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('modalAddHoliday').classList.add('hidden')" class="px-4 py-2 text-xs font-semibold rounded-lg text-slate-600 hover:bg-slate-100">Batal</button>
                <button type="submit" class="px-4 py-2 text-xs font-bold rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm">Simpan Hari Libur</button>
            </div>
        </form>
    </div>
</div>
@endsection
