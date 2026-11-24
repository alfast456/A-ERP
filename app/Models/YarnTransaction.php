<?php

namespace App\Models;

use App\Enums\YarnTransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YarnTransaction extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'yarn_stock_balance_id',
        'transaction_type',
        'cones_delta',
        'weight_kg_delta',
        'reference_number',
        'performed_by',
        'transaction_time',
    ];

    protected function casts(): array
    {
        return [
            'transaction_type' => YarnTransactionType::class,
            'cones_delta' => 'integer',
            'weight_kg_delta' => 'decimal:3',
            'transaction_time' => 'datetime',
        ];
    }

    public function balance(): BelongsTo
    {
        return $this->belongsTo(YarnStockBalance::class, 'yarn_stock_balance_id');
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
