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
        'sent_pairs',
        'repaired_pairs',
        'scrapped_pairs',
        'status',
        'repair_area_id',
        'technician_id',
        'repaired_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_pairs' => 'integer',
            'repaired_pairs' => 'integer',
            'scrapped_pairs' => 'integer',
            'status' => ReworkStatus::class,
            'repaired_at' => 'datetime',
            'created_at' => 'datetime',
        ];
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
