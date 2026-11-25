<?php

namespace App\Models;

use App\Enums\DepartmentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FactoryArea extends Model
{
    use HasFactory;

    protected $fillable = [
        'plant_id',
        'area_code',
        'area_name',
        'department_type',
    ];

    protected function casts(): array
    {
        return [
            'department_type' => DepartmentType::class,
        ];
    }

    public function plant(): BelongsTo
    {
        return $this->belongsTo(Plant::class);
    }

    public function machines(): HasMany
    {
        return $this->hasMany(Machine::class);
    }

    public function userAccesses(): HasMany
    {
        return $this->hasMany(UserAreaAccess::class);
    }

    public function planningAllocations(): HasMany
    {
        return $this->hasMany(PlanningAllocation::class);
    }

    public function yarnStockBalances(): HasMany
    {
        return $this->hasMany(YarnStockBalance::class);
    }

    public function getNameAttribute(): string
    {
        return $this->area_name ?? $this->area_code ?? '';
    }
}
