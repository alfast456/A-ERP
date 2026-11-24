<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderBooking extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'buyer_name',
        'booking_code',
        'machine_type_id',
        'estimated_pcs',
        'target_month',
        'notes',
        'status',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'estimated_pcs' => 'integer',
            'status' => BookingStatus::class,
            'created_at' => 'datetime',
        ];
    }

    public function getEstimatedPairsAttribute(): int
    {
        return $this->estimated_pcs;
    }

    public function machineType(): BelongsTo
    {
        return $this->belongsTo(MachineType::class);
    }
}
