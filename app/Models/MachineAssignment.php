<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MachineAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'planning_allocation_id',
        'machine_id',
        'order_shipment_id',
        'plan_date',
        'start_time',
        'stop_time',
        'target_dz',
        'target_pcs',
    ];

    protected function casts(): array
    {
        return [
            'plan_date' => 'date',
            'start_time' => 'datetime',
            'stop_time' => 'datetime',
            'target_dz' => 'decimal:2',
            'target_pcs' => 'integer',
        ];
    }

    public function getTargetPairsAttribute(): int
    {
        return $this->target_pcs;
    }

    public function planningAllocation(): BelongsTo
    {
        return $this->belongsTo(PlanningAllocation::class);
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function orderShipment(): BelongsTo
    {
        return $this->belongsTo(OrderShipment::class);
    }
}
