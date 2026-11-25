<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MachineType extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand',
        'model_name',
        'cylinder_inches',
        'needle_count',
        'standard_speed_rpm',
    ];

    protected function casts(): array
    {
        return [
            'cylinder_inches' => 'decimal:2',
            'needle_count' => 'integer',
            'standard_speed_rpm' => 'integer',
        ];
    }

    public function machines(): HasMany
    {
        return $this->hasMany(Machine::class);
    }

    public function orderStyles(): HasMany
    {
        return $this->hasMany(OrderStyle::class);
    }

    public function getNameAttribute(): string
    {
        return "{$this->brand} {$this->model_name}" . ($this->needle_count ? " ({$this->needle_count}N)" : '');
    }
}
