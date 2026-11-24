<?php

namespace App\Models;

use App\Enums\DepartmentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlowStep extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'flow_template_id',
        'step_order',
        'department_type',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'step_order' => 'integer',
            'department_type' => DepartmentType::class,
            'created_at' => 'datetime',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(FlowTemplate::class, 'flow_template_id');
    }
}
