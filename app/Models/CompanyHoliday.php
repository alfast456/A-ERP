<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyHoliday extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'plant_id',
        'holiday_date',
        'description',
        'is_working_day',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'holiday_date' => 'date',
            'is_working_day' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function plant(): BelongsTo
    {
        return $this->belongsTo(Plant::class);
    }
}
