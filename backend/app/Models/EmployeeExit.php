<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeExit extends Model
{
    protected $fillable = [
        'employee_id', 'category', 'resignation_date', 'last_working_date',
        'reason', 'notice_days', 'notice_shortfall_days', 'status',
    ];

    protected function casts(): array
    {
        return [
            'resignation_date' => 'date',
            'last_working_date' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(Settlement::class, 'exit_id');
    }
}
