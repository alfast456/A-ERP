<?php

namespace App\Http\Controllers;

use App\Models\BoxDefect;
use App\Models\MachineDowntime;
use App\Models\OrderShipment;
use Illuminate\Http\Request;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    public function exportProductionMonitoring(): StreamedResponse|BinaryFileResponse
    {
        $shipments = OrderShipment::with(['orderStyle.order', 'productionBoxes'])
            ->orderByDesc('target_delivery_date')
            ->take(500)
            ->get();

        $rows = $shipments->map(function ($s) {
            $orderDz = $s->order_qty_dz ?? round(($s->qty_pcs ?? 0) / 12, 1);
            $totalGood = $s->productionBoxes->sum('good_pcs');
            $totalDefect = $s->productionBoxes->sum('defect_pcs');

            return [
                'PO Number' => $s->orderStyle->order->po_number ?? '-',
                'Buyer' => $s->orderStyle->order->buyer_name ?? '-',
                'Style SKU' => $s->orderStyle->style_number ?? '-',
                'Delivery Date' => $s->target_delivery_date?->format('Y-m-d') ?? '-',
                'Order Qty (Dz)' => $orderDz,
                'Output Good (Pcs)' => $totalGood,
                'Defect BS (Pcs)' => $totalDefect,
                'Balance Sisa (Pcs)' => max(0, ($s->qty_pcs ?? ($orderDz * 12)) - $totalGood),
            ];
        });

        return (new FastExcel($rows))->download('monitoring-produksi-' . date('Ymd-His') . '.xlsx');
    }

    public function exportDefects(): StreamedResponse|BinaryFileResponse
    {
        $defects = BoxDefect::with(['defectType', 'productionBox', 'orderShipment.orderStyle.order'])
            ->orderByDesc('defect_date')
            ->take(500)
            ->get();

        $rows = $defects->map(function ($d) {
            return [
                'Tanggal Defect' => $d->defect_date?->format('Y-m-d') ?? '-',
                'PO Number' => $d->orderShipment->orderStyle->order->po_number ?? '-',
                'No Box' => $d->productionBox->no_box ?? '-',
                'Kode Cacat' => $d->defectType->defect_code ?? '-',
                'Keterangan Cacat' => $d->defectType->description ?? '-',
                'Kategori' => $d->defectType->category ?? '-',
                'Jumlah Cacat (Pcs)' => $d->defect_count_pcs,
            ];
        });

        return (new FastExcel($rows))->download('laporan-cacat-bs-' . date('Ymd-His') . '.xlsx');
    }

    public function exportDowntimes(): StreamedResponse|BinaryFileResponse
    {
        $downtimes = MachineDowntime::with(['machine.factoryArea', 'reason', 'reporter'])
            ->orderByDesc('start_time')
            ->take(500)
            ->get();

        $rows = $downtimes->map(function ($dt) {
            return [
                'Mulai' => $dt->start_time?->format('Y-m-d H:i') ?? '-',
                'Selesai' => $dt->end_time?->format('Y-m-d H:i') ?? '-',
                'Durasi (Menit)' => $dt->duration_minutes,
                'No Mesin' => $dt->machine->machine_number ?? '-',
                'Area' => $dt->machine->factoryArea->area_name ?? '-',
                'Kode Kerusakan' => $dt->reason->code ?? '-',
                'Penyebab Kerusakan' => $dt->reason->name ?? '-',
                'Kategori' => $dt->reason->category ?? '-',
                'Tindakan Perbaikan' => $dt->action_taken ?? '-',
                'Pelapor' => $dt->reporter->name ?? 'Teknisi',
            ];
        });

        return (new FastExcel($rows))->download('laporan-downtime-mesin-' . date('Ymd-His') . '.xlsx');
    }
}
