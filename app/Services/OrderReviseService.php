<?php

namespace App\Services;

use App\Enums\RevisionStatus;
use App\Exceptions\ZeroProductionLossException;
use App\Models\Order;
use App\Models\OrderRevision;
use App\Models\OrderRevisionItem;
use App\Models\OrderShipment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderReviseService
{
    /**
     * Terapkan revisi order dengan proteksi ACID & Zero Production Loss.
     *
     * @param  array  $revisionItems  List item revisi: [['shipment_id' => int, 'new_qty' => int, 'new_delivery_date' => ?string]]
     *
     * @throws ZeroProductionLossException
     */
    public function applyRevision(
        Order $order,
        array $revisionItems,
        User $requestedBy,
        ?User $approvedBy = null,
        string $reasonNotes = ''
    ): OrderRevision {
        return DB::transaction(function () use ($order, $revisionItems, $requestedBy, $approvedBy, $reasonNotes) {
            // Hitung nomor revisi berikutnya
            $nextRevisionNumber = ($order->revisions()->max('revision_number') ?? 0) + 1;

            $revision = OrderRevision::create([
                'order_id' => $order->id,
                'revision_number' => $nextRevisionNumber,
                'revision_date' => now(),
                'reason_notes' => $reasonNotes ?: 'Revisi kuantitas / jadwal order',
                'requested_by' => $requestedBy->id,
                'approved_by' => $approvedBy?->id ?? $requestedBy->id,
                'status' => RevisionStatus::APPROVED,
            ]);

            foreach ($revisionItems as $item) {
                /** @var OrderShipment $shipment */
                $shipment = OrderShipment::where('id', $item['shipment_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $oldQty = $shipment->qty_pairs;
                $newQty = (int) $item['new_qty'];

                // Proteksi Mutlak: Zero Production Loss
                $producedGood = $shipment->produced_good_pairs;
                if ($newQty < $producedGood) {
                    throw new ZeroProductionLossException($shipment->id, $producedGood, $newQty);
                }

                $deltaQty = $newQty - $oldQty;

                if ($deltaQty !== 0 || ! empty($item['new_delivery_date'])) {
                    OrderRevisionItem::create([
                        'order_revision_id' => $revision->id,
                        'order_shipment_id' => $shipment->id,
                        'old_qty' => $oldQty,
                        'new_qty' => $newQty,
                        'delta_qty' => $deltaQty,
                        'created_at' => now(),
                    ]);

                    $updatePayload = ['qty_pairs' => $newQty];
                    if (! empty($item['new_delivery_date'])) {
                        $updatePayload['target_delivery_date'] = $item['new_delivery_date'];
                    }

                    $shipment->update($updatePayload);
                }
            }

            // Rekalkulasi total order pairs
            $totalPairs = (int) OrderShipment::whereHas('orderStyle', function ($query) use ($order) {
                $query->where('order_id', $order->id);
            })->sum('qty_pairs');

            $order->update(['total_ordered_pairs' => $totalPairs]);

            return $revision->load('items');
        });
    }
}
