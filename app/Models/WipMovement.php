<?php

namespace App\Models;

use App\Enums\WipStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WipMovement extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'movement_date',
        'production_box_id',
        'from_area_id',
        'to_area_id',
        'sent_pairs',
        'received_pairs',
        'dispatched_by',
        'received_by',
        'status',
        'remarks',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'movement_date' => 'date',
            'sent_pairs' => 'integer',
            'received_pairs' => 'integer',
            'status' => WipStatus::class,
            'created_at' => 'datetime',
        ];
    }

    public function productionBox(): BelongsTo
    {
        return $this->belongsTo(ProductionBox::class);
    }

    public function fromArea(): BelongsTo
    {
        return $this->belongsTo(FactoryArea::class, 'from_area_id');
    }

    public function toArea(): BelongsTo
    {
        return $this->belongsTo(FactoryArea::class, 'to_area_id');
    }

    public function dispatcher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatched_by');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
