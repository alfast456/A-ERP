<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plant extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'address',
        'phone',
    ];

    public function factoryAreas(): HasMany
    {
        return $this->hasMany(FactoryArea::class);
    }

    public function monthlyTargets(): HasMany
    {
        return $this->hasMany(MonthlyTarget::class);
    }
}
