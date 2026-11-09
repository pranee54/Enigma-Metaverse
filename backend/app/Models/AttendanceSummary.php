<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceSummary extends Model
{
    protected $fillable = [
        'batch_id', 'employee_id', 'period_year', 'period_month', 'version',
        'payable_days', 'lop_days', 'overtime_hours', 'overtime_consent',
        'leave_encashment_days', 'maternity_benefit_days', 'remarks',
        'status', 'rejection_reason', 'approved_by', 'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'payable_days' => 'decimal:1',
            'lop_days' => 'decimal:1',
            'overtime_hours' => 'decimal:1',
            'overtime_consent' => 'boolean',
            'leave_encashment_days' => 'decimal:1',
            'maternity_benefit_days' => 'decimal:1',
            'approved_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(AttendanceImportBatch::class, 'batch_id');
    }
}
