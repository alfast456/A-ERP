<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyTarget extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'plant_id',
        'target_month',
        'target_export_pcs',
        'target_local_pcs',
        'target_efficiency',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'target_export_pcs' => 'integer',
            'target_local_pcs' => 'integer',
            'target_efficiency' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function getTargetExportPairsAttribute(): int
    {
        return $this->target_export_pcs;
    }

    public function getTargetLocalPairsAttribute(): int
    {
        return $this->target_local_pcs;
    }

    public function plant(): BelongsTo
    {
        return $this->belongsTo(Plant::class);
    }
}
