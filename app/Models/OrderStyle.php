<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderStyle extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'machine_type_id',
        'jarum',
        'style_code',
        'inisial',
        'color_code',
        'size',
        'seam',
        'factory',
        'smv_seconds',
        'unit_weight_gram',
        'gw_dz',
    ];

    protected function casts(): array
    {
        return [
            'smv_seconds' => 'decimal:2',
            'unit_weight_gram' => 'decimal:2',
            'gw_dz' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function machineType(): BelongsTo
    {
        return $this->belongsTo(MachineType::class);
    }

    public function orderShipments(): HasMany
    {
        return $this->hasMany(OrderShipment::class);
    }

    public function sampleApprovals(): HasMany
    {
        return $this->hasMany(SampleApproval::class);
    }
}
