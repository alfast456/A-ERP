<?php

namespace App\Models;

use App\Enums\ReworkStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReworkTracking extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'rework_tracking';

    protected $fillable = [
        'production_box_id',
        'defect_type_id',
        'sent_pcs',
        'repaired_pcs',
        'scrapped_pcs',
        'status',
        'repair_area_id',
        'technician_id',
        'repaired_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_pcs' => 'integer',
            'repaired_pcs' => 'integer',
            'scrapped_pcs' => 'integer',
            'status' => ReworkStatus::class,
            'repaired_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function getSentPairsAttribute(): int
    {
        return $this->sent_pcs;
    }

    public function getRepairedPairsAttribute(): int
    {
        return $this->repaired_pcs;
    }

    public function getScrappedPairsAttribute(): int
    {
        return $this->scrapped_pcs;
    }

    public function productionBox(): BelongsTo
    {
        return $this->belongsTo(ProductionBox::class);
    }

    public function defectType(): BelongsTo
    {
        return $this->belongsTo(DefectType::class);
    }

    public function repairArea(): BelongsTo
    {
        return $this->belongsTo(FactoryArea::class, 'repair_area_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }
}
