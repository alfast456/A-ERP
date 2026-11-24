<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DowntimeReason extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'code',
        'name',
        'category',
        'created_at',
    ];

    public function downtimes(): HasMany
    {
        return $this->hasMany(MachineDowntime::class);
    }
}
