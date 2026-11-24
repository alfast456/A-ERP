<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderBookingCancellation extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'order_booking_id',
        'qty_cancel_pcs',
        'reason',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'qty_cancel_pcs' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function orderBooking(): BelongsTo
    {
        return $this->belongsTo(OrderBooking::class);
    }
}
