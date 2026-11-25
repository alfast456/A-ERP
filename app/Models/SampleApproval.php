<?php

namespace App\Models;

use App\Enums\SampleStage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SampleApproval extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'order_style_id',
        'order_shipment_id',
        'machine_id',
        'sample_stage',
        'mechanic_name',
        'mechanic_approved_by',
        'qa_approved_by',
        'merchandiser_approved_by',
        'is_approved_for_bulk',
        'remarks',
        'approved_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'sample_stage' => SampleStage::class,
            'is_approved_for_bulk' => 'boolean',
            'approved_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function orderStyle(): BelongsTo
    {
        return $this->belongsTo(OrderStyle::class);
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function mechanic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mechanic_approved_by');
    }

    public function qa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'qa_approved_by');
    }

    public function merchandiser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'merchandiser_approved_by');
    }

    public function orderShipment(): BelongsTo
    {
        return $this->belongsTo(OrderShipment::class);
    }
}
