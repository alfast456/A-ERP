<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderShipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_style_id',
        'shipment_index',
        'target_delivery_date',
        'qty_pcs',
    ];

    protected function casts(): array
    {
        return [
            'shipment_index' => 'integer',
            'target_delivery_date' => 'date',
            'qty_pcs' => 'integer',
        ];
    }

    public function orderStyle(): BelongsTo
    {
        return $this->belongsTo(OrderStyle::class);
    }

    public function productionBoxes(): HasMany
    {
        return $this->hasMany(ProductionBox::class);
    }

    public function machineAssignments(): HasMany
    {
        return $this->hasMany(MachineAssignment::class);
    }

    public function revisionItems(): HasMany
    {
        return $this->hasMany(OrderRevisionItem::class);
    }

    /**
     * Backward-compatibility accessor for qty_pairs
     */
    public function getQtyPairsAttribute(): int
    {
        return $this->qty_pcs;
    }

    /**
     * Dynamic calculation of produced good pcs
     */
    public function getProducedGoodPcsAttribute(): int
    {
        return (int) $this->productionBoxes()->sum('good_pcs');
    }

    public function getProducedGoodPairsAttribute(): int
    {
        return $this->produced_good_pcs;
    }

    /**
     * Dynamic calculation of produced defect pcs
     */
    public function getProducedDefectPcsAttribute(): int
    {
        return (int) $this->productionBoxes()->sum('defect_pcs');
    }

    public function getProducedDefectPairsAttribute(): int
    {
        return $this->produced_defect_pcs;
    }

    /**
     * Dynamic calculation of sisa order in pcs
     */
    public function getSisaPcsAttribute(): int
    {
        return max(0, $this->qty_pcs - $this->produced_good_pcs);
    }

    public function getSisaPairsAttribute(): int
    {
        return $this->sisa_pcs;
    }

    /**
     * Dynamic completion percentage
     */
    public function getCompletionPercentageAttribute(): float
    {
        if ($this->qty_pcs <= 0) {
            return 0.0;
        }

        return round(($this->produced_good_pcs / $this->qty_pcs) * 100, 2);
    }
}
