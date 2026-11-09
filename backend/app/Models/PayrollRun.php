<?php

namespace App\Models;

use App\Enums\PayrollState;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends Model
{
    protected $fillable = [
        'period_year', 'period_month', 'state', 'prepared_by', 'approved_by',
        'calculated_at', 'approved_at', 'finalised_at', 'paid_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'state' => PayrollState::class,
            'calculated_at' => 'datetime',
            'approved_at' => 'datetime',
            'finalised_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class, 'run_id');
    }

    public function preparer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function periodLabel(): string
    {
        return Carbon::create($this->period_year, $this->period_month, 1)->format('M Y');
    }
}
