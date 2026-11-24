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
        'qty_pairs',
    ];

    protected function casts(): array
    {
        return [
            'shipment_index' => 'integer',
            'target_delivery_date' => 'date',
            'qty_pairs' => 'integer',
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
     * Dynamic calculation of produced good pairs
     */
    public function getProducedGoodPairsAttribute(): int
    {
        return (int) $this->productionBoxes()->sum('good_pairs');
    }

    /**
     * Dynamic calculation of produced defect pairs
     */
    public function getProducedDefectPairsAttribute(): int
    {
        return (int) $this->productionBoxes()->sum('defect_pairs');
    }

    /**
     * Dynamic calculation of sisa order (eliminating god-mode static update)
     */
    public function getSisaPairsAttribute(): int
    {
        return max(0, $this->qty_pairs - $this->produced_good_pairs);
    }

    /**
     * Dynamic completion percentage
     */
    public function getCompletionPercentageAttribute(): float
    {
        if ($this->qty_pairs <= 0) {
            return 0.0;
        }

        return round(($this->produced_good_pairs / $this->qty_pairs) * 100, 2);
    }
}
