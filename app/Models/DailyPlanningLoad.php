<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyPlanningLoad extends Model
{
    use HasFactory;

    protected $fillable = [
        'planning_allocation_id',
        'plan_date',
        'planned_machine_count',
        'daily_target_pcs',
        'start_date',
        'stop_date',
    ];

    protected function casts(): array
    {
        return [
            'plan_date' => 'date',
            'planned_machine_count' => 'integer',
            'daily_target_pcs' => 'integer',
            'start_date' => 'date',
            'stop_date' => 'date',
        ];
    }

    public function planningAllocation(): BelongsTo
    {
        return $this->belongsTo(PlanningAllocation::class);
    }
}
