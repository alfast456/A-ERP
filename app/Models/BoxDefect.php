<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoxDefect extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'order_shipment_id',
        'production_box_id',
        'defect_type_id',
        'factory_area_id',
        'no_box',
        'no_label',
        'defect_date',
        'defect_count_pcs',
        'notes',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'defect_date' => 'date',
            'defect_count_pcs' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function getDefectCountPairsAttribute(): int
    {
        return $this->defect_count_pcs;
    }

    public function orderShipment(): BelongsTo
    {
        return $this->belongsTo(OrderShipment::class);
    }

    public function productionBox(): BelongsTo
    {
        return $this->belongsTo(ProductionBox::class);
    }

    public function factoryArea(): BelongsTo
    {
        return $this->belongsTo(FactoryArea::class);
    }

    public function defectType(): BelongsTo
    {
        return $this->belongsTo(DefectType::class);
    }
}
