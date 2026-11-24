<?php

namespace App\Models;

use App\Enums\DefectCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DefectType extends Model
{
    use HasFactory;

    protected $fillable = [
        'defect_code',
        'description',
        'category',
        'is_reworkable',
    ];

    protected function casts(): array
    {
        return [
            'category' => DefectCategory::class,
            'is_reworkable' => 'boolean',
        ];
    }

    public function boxDefects(): HasMany
    {
        return $this->hasMany(BoxDefect::class);
    }

    public function reworkTrackings(): HasMany
    {
        return $this->hasMany(ReworkTracking::class);
    }
}
