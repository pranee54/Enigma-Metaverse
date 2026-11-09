<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryRevision extends Model
{
    protected $fillable = [
        'employee_id', 'structure_id', 'effective_from', 'reason', 'status',
        'initiated_by', 'approved_by', 'arrears_json',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'arrears_json' => 'array',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function structure(): BelongsTo
    {
        return $this->belongsTo(SalaryStructure::class, 'structure_id');
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }
}
