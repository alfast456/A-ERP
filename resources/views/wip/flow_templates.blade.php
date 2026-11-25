@extends('layouts.app')

@section('title', 'Master Template Alur Proses WIP')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white tracking-tight">Master Template Alur Rute WIP</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Konfigurasi sekuens tahapan produksi standar kaos kaki (Knitting ➔ Linking ➔ Setting ➔ Packing ➔ Warehouse).</p>
        </div>
        <a href="{{ route('wip.index') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-sm font-semibold rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition">
            ← Kembali ke Mutasi WIP
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- List Templates -->
        <div class="lg:col-span-2 space-y-4">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider">Template Rute Aktif</h3>
            @forelse($templates as $tmpl)
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700/60 p-5 shadow-xs space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="font-bold text-gray-900 dark:text-white text-base">{{ $tmpl->name }}</h4>
                            <p class="text-xs text-gray-500">{{ $tmpl->description ?? 'Tanpa keterangan' }}</p>
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $tmpl->is_active ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400' : 'bg-gray-100 text-gray-600' }}">
                            {{ $tmpl->is_active ? 'AKTIF' : 'NONAKTIF' }}
                        </span>
                    </div>

                    <!-- Flow Diagram -->
                    <div class="flex flex-wrap items-center gap-2 pt-2">
                        @foreach($tmpl->steps as $index => $step)
                            <div class="flex items-center gap-2">
                                <span class="px-3 py-1.5 rounded-xl bg-gray-50 dark:bg-gray-900/80 border border-gray-200 dark:border-gray-700 text-xs font-bold text-gray-800 dark:text-gray-200 shadow-2xs">
                                    {{ $step->step_order }}. {{ $step->department_type->value }}
                                </span>
                                @if(!$loop->last)
                                    <span class="text-gray-400 text-sm font-bold">➔</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700/60 p-8 text-center text-sm text-gray-500">
                    Belum ada template alur rute proses. Buat template pertama di samping.
                </div>
            @endforelse
        </div>

        <!-- Form Tambah Template Baru -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700/60 p-5 shadow-xs h-fit space-y-4">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider">+ Buat Template Baru</h3>
            <form action="{{ route('wip.flows.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Nama Alur Rute *</label>
                    <input type="text" name="name" required placeholder="Contoh: Standar Ankle (5 Langkah)" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Keterangan Singkat</label>
                    <input type="text" name="description" placeholder="Contoh: Untuk kaos kaki tanpa bordir" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Urutan Departemen / Langkah *</label>
                    <div class="space-y-2">
                        <select name="steps[]" required class="w-full px-3 py-1.5 border border-gray-300 dark:border-gray-700 rounded-xl text-xs bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
                            <option value="KNITTING">Langkah 1: KNITTING (Rajut)</option>
                        </select>
                        <select name="steps[]" required class="w-full px-3 py-1.5 border border-gray-300 dark:border-gray-700 rounded-xl text-xs bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
                            <option value="LINKING">Langkah 2: LINKING (Rosso)</option>
                        </select>
                        <select name="steps[]" required class="w-full px-3 py-1.5 border border-gray-300 dark:border-gray-700 rounded-xl text-xs bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
                            <option value="SETTING">Langkah 3: SETTING (Boarding/Steam)</option>
                        </select>
                        <select name="steps[]" required class="w-full px-3 py-1.5 border border-gray-300 dark:border-gray-700 rounded-xl text-xs bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
                            <option value="PACKING">Langkah 4: PACKING (Kemas)</option>
                        </select>
                        <select name="steps[]" required class="w-full px-3 py-1.5 border border-gray-300 dark:border-gray-700 rounded-xl text-xs bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
                            <option value="WAREHOUSE">Langkah 5: WAREHOUSE (Gudang Jadi)</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="w-full py-2 bg-brand-500 hover:bg-brand-600 text-white text-sm font-semibold rounded-xl shadow-xs transition">
                    Simpan Template Flow
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
