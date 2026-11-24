<?php

namespace App\Services;

use App\Models\BoxDefect;
use App\Models\ProductionBox;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductionBoxService
{
    /**
     * Menghasilkan kode barcode unik untuk fisik box rajut.
     */
    public function generateBarcode(int $machineId, ?string $date = null): string
    {
        $dateStr = $date ? date('Ymd', strtotime($date)) : date('Ymd');
        $random = strtoupper(Str::random(6));

        return sprintf('BOX-%s-MC%03d-%s', $dateStr, $machineId, $random);
    }

    /**
     * Mencatat transaksi output box rajut dan rincian cacat secara atomik.
     *
     * @param  array  $payload  ['order_shipment_id', 'machine_id', 'imported_by'|'operator_id', 'production_date', 'shift', 'good_pcs'|'good_pairs', 'defect_pcs'|'defect_pairs', 'weight_kg', 'box_barcode'?]
     * @param  array  $defects  List of ['defect_type_id' => int, 'defect_count_pcs'|'defect_count_pairs' => int, 'notes' => ?string]
     */
    public function recordOutput(array $payload, array $defects = []): ProductionBox
    {
        return DB::transaction(function () use ($payload, $defects) {
            $barcode = $payload['box_barcode'] ?? $this->generateBarcode((int) $payload['machine_id'], $payload['production_date'] ?? null);

            $goodPcs = (int) ($payload['good_pcs'] ?? $payload['good_pairs'] ?? 0);
            $defectPcs = (int) ($payload['defect_pcs'] ?? $payload['defect_pairs'] ?? 0);
            $importedBy = (int) ($payload['imported_by'] ?? $payload['operator_id'] ?? 1);

            $box = ProductionBox::create([
                'box_barcode' => $barcode,
                'production_date' => $payload['production_date'] ?? now()->toDateString(),
                'shift' => (int) $payload['shift'],
                'order_shipment_id' => (int) $payload['order_shipment_id'],
                'machine_id' => (int) $payload['machine_id'],
                'imported_by' => $importedBy,
                'good_pcs' => $goodPcs,
                'defect_pcs' => $defectPcs,
                'weight_kg' => (float) ($payload['weight_kg'] ?? 0.0),
                'created_at' => now(),
            ]);

            foreach ($defects as $defect) {
                BoxDefect::create([
                    'production_box_id' => $box->id,
                    'defect_type_id' => (int) $defect['defect_type_id'],
                    'defect_count_pcs' => (int) ($defect['defect_count_pcs'] ?? $defect['defect_count_pairs'] ?? 1),
                    'notes' => $defect['notes'] ?? null,
                    'created_at' => now(),
                ]);
            }

            return $box->load('defects');
        });
    }
}
