<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderRevisionItem extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'order_revision_id',
        'order_shipment_id',
        'old_qty',
        'new_qty',
        'delta_qty',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_qty' => 'integer',
            'new_qty' => 'integer',
            'delta_qty' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function revision(): BelongsTo
    {
        return $this->belongsTo(OrderRevision::class, 'order_revision_id');
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(OrderShipment::class, 'order_shipment_id');
    }
}
