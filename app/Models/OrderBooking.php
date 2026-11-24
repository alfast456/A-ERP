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
        'no_booking',
        'no_order',
        'machine_type_id',
        'needle',
        'seam',
        'estimated_pcs',
        'sisa_pcs',
        'target_month',
        'delivery_date',
        'lead_time_days',
        'notes',
        'status',
        'legacy_status',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'estimated_pcs' => 'integer',
            'sisa_pcs' => 'integer',
            'lead_time_days' => 'integer',
            'delivery_date' => 'date',
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

    public function cancellations()
    {
        return $this->hasMany(OrderBookingCancellation::class);
    }
}
