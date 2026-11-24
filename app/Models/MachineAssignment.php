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
        'target_dz',
        'target_pairs',
    ];

    protected function casts(): array
    {
        return [
            'plan_date' => 'date',
            'target_dz' => 'decimal:2',
            'target_pairs' => 'integer',
        ];
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
