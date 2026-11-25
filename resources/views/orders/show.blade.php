@extends('layouts.app')

@section('title', 'Detail Order #' . $order->po_number)

@section('content')
<div class="space-y-6">
    <!-- Header Back Button -->
    <div>
        <a href="{{ route('orders.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800">
            ← Kembali ke Daftar Order
        </a>
    </div>

    <!-- PO Summary Card -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <span class="text-xs font-bold text-emerald-600 uppercase tracking-wider">Kontrak PDK / PO Buyer</span>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">{{ $order->po_number }}</h1>
                <p class="text-sm text-slate-500">Buyer: <strong class="text-slate-800">{{ $order->buyer_name }}</strong> | Season: {{ $order->season ?? '-' }} | Tanggal Order: {{ $order->order_date?->format('d M Y') }}</p>
            </div>
            <div class="flex flex-col sm:flex-row items-end sm:items-center gap-3">
                <div class="text-right">
                    <span class="text-xs text-slate-500 font-medium block">Total Kuantitas:</span>
                    <span class="text-2xl font-black text-slate-900">{{ number_format($order->total_ordered_pcs) }} <span class="text-sm font-normal text-slate-500">Pcs</span></span>
                </div>
                @if($order->status->value !== 'CANCELLED')
                    <form action="{{ route('orders.cancel', $order) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan Order {{ $order->po_number }}?')">
                        @csrf
                        <input type="hidden" name="reason" value="Dibatalkan via sistem manajemen order">
                        <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 rounded-lg border border-rose-200 transition">
                            Batalkan Order
                        </button>
                    </form>
                @else
                    <span class="px-3 py-1 text-xs font-bold bg-rose-100 text-rose-800 rounded-full">CANCELLED</span>
                @endif
            </div>
        </div>
    </div>

    <!-- Form Pengajuan Revisi Aman -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h2 class="text-lg font-bold text-slate-900 mb-1">Pengajuan Revisi Kuantitas Order (ACID Protected)</h2>
        <p class="text-xs text-slate-500 mb-4">Setiap perubahan kuantitas tercatat otomatis ke audit log. Sistem menolak pemotongan jika kuantitas baru di bawah barang riil yang telah dirajut (*Zero Production Loss*).</p>

        <form action="{{ route('orders.revise', $order) }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Alasan Revisi / Catatan Buyer</label>
                <input type="text" name="reason_notes" required placeholder="Contoh: Revisi kuantitas PO resmi dari buyer per email..." class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
            </div>

            <div class="overflow-x-auto border border-slate-200 rounded-lg mb-4">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50 text-slate-500 text-xs uppercase font-semibold">
                        <tr>
                            <th class="px-4 py-2">Style Code</th>
                            <th class="px-4 py-2">Color & Size</th>
                            <th class="px-4 py-2">Delivery Index</th>
                            <th class="px-4 py-2">Tgl Kirim Saat Ini</th>
                            <th class="px-4 py-2 text-right">Qty Saat Ini</th>
                            <th class="px-4 py-2 text-right">Sudah Dirajut (Bagus)</th>
                            <th class="px-4 py-2 text-center w-36">Qty Baru</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @php $itemIndex = 0; @endphp
                        @foreach ($order->orderStyles as $style)
                            @foreach ($style->orderShipments as $shipment)
                                <tr>
                                    <td class="px-4 py-2 font-bold text-slate-900">{{ $style->style_code }}</td>
                                    <td class="px-4 py-2 text-xs">{{ $style->color_code }} / {{ $style->size }}</td>
                                    <td class="px-4 py-2 text-xs">Delivery #{{ $shipment->shipment_index }}</td>
                                    <td class="px-4 py-2 text-xs">{{ $shipment->target_delivery_date?->format('d M Y') }}</td>
                                    <td class="px-4 py-2 text-right font-medium">{{ number_format($shipment->qty_pcs) }}</td>
                                    <td class="px-4 py-2 text-right font-semibold text-emerald-600">
                                        {{ number_format($shipment->produced_good_pcs) }}
                                    </td>
                                    <td class="px-4 py-2 text-center">
                                        <input type="hidden" name="items[{{ $itemIndex }}][shipment_id]" value="{{ $shipment->id }}">
                                        <input type="number" name="items[{{ $itemIndex }}][new_qty]" value="{{ $shipment->qty_pcs }}" min="{{ $shipment->produced_good_pcs }}" required class="w-32 px-2 py-1 text-right border border-slate-300 rounded font-bold text-slate-900 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                                    </td>
                                </tr>
                                @php $itemIndex++; @endphp
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm rounded-lg shadow-sm transition">
                    Simpan Revisi Order
                </button>
            </div>
        </form>
    </div>

    <!-- Riwayat Revisi -->
    @if ($order->revisions->isNotEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-base font-bold text-slate-900 mb-3">Audit Riwayat Revisi Order</h2>
            <div class="space-y-3">
                @foreach ($order->revisions as $rev)
                    <div class="border border-slate-200 rounded-lg p-4 bg-slate-50">
                        <div class="flex items-center justify-between text-xs text-slate-500 mb-2">
                            <span class="font-bold text-slate-800">Revisi #{{ $rev->revision_number }}</span>
                            <span>{{ $rev->revision_date?->format('d M Y H:i') }} | Diajukan oleh: {{ $rev->requester?->name ?? 'System' }}</span>
                        </div>
                        <p class="text-sm text-slate-700 italic mb-2">"{{ $rev->reason_notes }}"</p>
                        
                        <div class="bg-white border border-slate-200 rounded p-2 text-xs">
                            <ul class="divide-y divide-slate-100">
                                @foreach ($rev->items as $item)
                                    <li class="py-1 flex justify-between">
                                        <span>Shipment #{{ $item->order_shipment_id }} ({{ $item->shipment?->orderStyle?->style_code }} {{ $item->shipment?->orderStyle?->size }})</span>
                                        <span>
                                            {{ number_format($item->old_qty) }} → {{ number_format($item->new_qty) }} 
                                            <strong class="{{ $item->delta_qty >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                                ({{ $item->delta_qty >= 0 ? '+' : '' }}{{ number_format($item->delta_qty) }})
                                            </strong>
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
