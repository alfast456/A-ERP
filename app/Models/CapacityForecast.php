<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CapacityForecast extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'factory_area_id',
        'machine_type_id',
        'forecast_period',
        'active_machines_count',
        'available_hours',
        'required_hours',
        'load_percentage',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'active_machines_count' => 'integer',
            'available_hours' => 'decimal:2',
            'required_hours' => 'decimal:2',
            'load_percentage' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function factoryArea(): BelongsTo
    {
        return $this->belongsTo(FactoryArea::class);
    }

    public function machineType(): BelongsTo
    {
        return $this->belongsTo(MachineType::class);
    }
}
