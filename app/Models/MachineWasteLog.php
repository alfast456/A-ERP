<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MachineWasteLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'production_date',
        'machine_id',
        'factory_area_id',
        'employee_id',
        'employee_name',
        'shift',
        'style_code',
        'size',
        'inisial',
        'qty_pcs',
        'weight_gram',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'production_date' => 'date',
            'qty_pcs' => 'float',
            'weight_gram' => 'float',
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
}
