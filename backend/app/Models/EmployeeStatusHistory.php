<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeStatusHistory extends Model
{
    protected $fillable = [
        'employee_id', 'from_status', 'to_status', 'effective_date',
        'recorded_at', 'author_id', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'recorded_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
