<?php

namespace App\Models;

use App\Enums\MachineStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Machine extends Model
{
    use HasFactory;

    protected $fillable = [
        'machine_type_id',
        'jarum',
        'factory_area_id',
        'machine_number',
        'brand',
        'year_manufactured',
        'serial_number',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => MachineStatus::class,
            'year_manufactured' => 'integer',
        ];
    }

    public function machineType(): BelongsTo
    {
        return $this->belongsTo(MachineType::class);
    }

    public function factoryArea(): BelongsTo
    {
        return $this->belongsTo(FactoryArea::class);
    }

    public function productionBoxes(): HasMany
    {
        return $this->hasMany(ProductionBox::class);
    }

    public function downtimes(): HasMany
    {
        return $this->hasMany(MachineDowntime::class);
    }

    public function maintenanceTickets(): HasMany
    {
        return $this->hasMany(MaintenanceTicket::class);
    }

    public function needleConsumptions(): HasMany
    {
        return $this->hasMany(NeedleConsumption::class);
    }
}
