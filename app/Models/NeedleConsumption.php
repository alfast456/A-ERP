<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NeedleConsumption extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'machine_id',
        'replacement_date',
        'shift',
        'needle_type',
        'broken_count',
        'replaced_count',
        'reason',
        'mechanic_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'replacement_date' => 'date',
            'shift' => 'integer',
            'broken_count' => 'integer',
            'replaced_count' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function mechanic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mechanic_id');
    }
}
