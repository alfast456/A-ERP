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
        'target_export_pairs',
        'target_local_pairs',
        'target_efficiency',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'target_export_pairs' => 'integer',
            'target_local_pairs' => 'integer',
            'target_efficiency' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function plant(): BelongsTo
    {
        return $this->belongsTo(Plant::class);
    }
}
