<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanningAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'factory_area_id',
        'allocated_pcs',
        'target_start_date',
        'target_end_date',
    ];

    protected function casts(): array
    {
        return [
            'allocated_pcs' => 'integer',
            'target_start_date' => 'date',
            'target_end_date' => 'date',
        ];
    }

    public function getAllocatedPairsAttribute(): int
    {
        return $this->allocated_pcs;
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function factoryArea(): BelongsTo
    {
        return $this->belongsTo(FactoryArea::class);
    }

    public function machineAssignments(): HasMany
    {
        return $this->hasMany(MachineAssignment::class);
    }

    public function dailyLoads(): HasMany
    {
        return $this->hasMany(DailyPlanningLoad::class);
    }
}
