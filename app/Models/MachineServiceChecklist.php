<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MachineServiceChecklist extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'machine_service_record_id',
        'master_maintenance_id',
        'item_name',
        'status',
        'sparepart_name',
        'sparepart_qty',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'sparepart_qty' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function serviceRecord(): BelongsTo
    {
        return $this->belongsTo(MachineServiceRecord::class, 'machine_service_record_id');
    }
}
