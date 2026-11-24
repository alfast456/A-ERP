<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'po_number',
        'buyer_name',
        'season',
        'order_type',
        'total_ordered_pcs',
        'order_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'order_type' => OrderType::class,
            'status' => OrderStatus::class,
            'total_ordered_pcs' => 'integer',
        ];
    }

    public function getTotalOrderedPairsAttribute(): int
    {
        return $this->total_ordered_pcs;
    }

    public function orderStyles(): HasMany
    {
        return $this->hasMany(OrderStyle::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(OrderRevision::class);
    }

    public function planningAllocations(): HasMany
    {
        return $this->hasMany(PlanningAllocation::class);
    }
}
