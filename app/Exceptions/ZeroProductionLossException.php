<?php

namespace App\Exceptions;

use Exception;

class ZeroProductionLossException extends Exception
{
    protected int $shipmentId;
    protected int $producedQty;
    protected int $requestedQty;

    public function __construct(int $shipmentId, int $producedQty, int $requestedQty)
    {
        $this->shipmentId = $shipmentId;
        $this->producedQty = $producedQty;
        $this->requestedQty = $requestedQty;

        parent::__construct(
            "Revisi ditolak (Zero Production Loss): Kuantitas order pada Shipment #{$shipmentId} tidak boleh dipotong menjadi {$requestedQty} pasang karena fisik produksi telah berjalan sebanyak {$producedQty} pasang."
        );
    }

    public function getShipmentId(): int
    {
        return $this->shipmentId;
    }

    public function getProducedQty(): int
    {
        return $this->producedQty;
    }

    public function getRequestedQty(): int
    {
        return $this->requestedQty;
    }
}
