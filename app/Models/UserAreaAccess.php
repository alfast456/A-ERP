<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAreaAccess extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'user_area_access';

    protected $fillable = [
        'user_id',
        'factory_area_id',
        'can_write',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'can_write' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function factoryArea(): BelongsTo
    {
        return $this->belongsTo(FactoryArea::class);
    }
}
