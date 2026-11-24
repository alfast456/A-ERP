<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionBox extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'box_barcode',
        'no_box',
        'no_label',
        'production_date',
        'shift',
        'shift_a',
        'shift_b',
        'shift_c',
        'order_shipment_id',
        'machine_id',
        'factory_area_id',
        'operator_id',
        'good_pcs',
        'defect_pcs',
        'weight_kg',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'production_date' => 'date',
            'shift' => 'integer',
            'shift_a' => 'integer',
            'shift_b' => 'integer',
            'shift_c' => 'integer',
            'no_label' => 'integer',
            'good_pcs' => 'integer',
            'defect_pcs' => 'integer',
            'weight_kg' => 'decimal:3',
            'created_at' => 'datetime',
        ];
    }

    public function getGoodPairsAttribute(): int
    {
        return $this->good_pcs;
    }

    public function getDefectPairsAttribute(): int
    {
        return $this->defect_pcs;
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(OrderShipment::class, 'order_shipment_id');
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function factoryArea(): BelongsTo
    {
        return $this->belongsTo(FactoryArea::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function defects(): HasMany
    {
        return $this->hasMany(BoxDefect::class);
    }

    public function reworks(): HasMany
    {
        return $this->hasMany(ReworkTracking::class);
    }

    public function wipMovements(): HasMany
    {
        return $this->hasMany(WipMovement::class);
    }
}
