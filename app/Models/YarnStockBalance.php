<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class YarnStockBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'factory_area_id',
        'yarn_code',
        'yarn_name',
        'color_name',
        'lot_number',
        'qty_cones',
        'weight_kg',
    ];

    protected function casts(): array
    {
        return [
            'qty_cones' => 'integer',
            'weight_kg' => 'decimal:3',
        ];
    }

    public function factoryArea(): BelongsTo
    {
        return $this->belongsTo(FactoryArea::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(YarnTransaction::class);
    }
}
