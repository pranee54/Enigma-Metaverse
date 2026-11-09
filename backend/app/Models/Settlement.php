<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Settlement extends Model
{
    protected $fillable = [
        'exit_id', 'step', 'undisputed_wages', 'other_components_json',
        'tax_amount', 'net_payable', 'due_by', 'paid_at', 'statement_path',
    ];

    protected function casts(): array
    {
        return [
            'undisputed_wages' => 'decimal:2',
            'other_components_json' => 'array',
            'tax_amount' => 'decimal:2',
            'net_payable' => 'decimal:2',
            'due_by' => 'date',
            'paid_at' => 'datetime',
        ];
    }

    public function exit(): BelongsTo
    {
        return $this->belongsTo(EmployeeExit::class, 'exit_id');
    }
}
