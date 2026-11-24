<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MachineServiceRecord extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'machine_id',
        'service_date',
        'jarum',
        'technician_name',
        'inspector_name',
        'area_head',
        'factory_area_id',
        'machine_year',
        'serial_number',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'service_date' => 'date',
            'machine_year' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function factoryArea(): BelongsTo
    {
        return $this->belongsTo(FactoryArea::class);
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(MachineServiceChecklist::class, 'machine_service_record_id');
    }
}
