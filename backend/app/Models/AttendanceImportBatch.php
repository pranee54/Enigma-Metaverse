<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceImportBatch extends Model
{
    protected $fillable = [
        'batch_code', 'source', 'importer_id', 'period_year', 'period_month',
        'row_count', 'accepted_count', 'rejected_count', 'status', 'report_json',
    ];

    protected function casts(): array
    {
        return [
            'report_json' => 'array',
        ];
    }

    public function importer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'importer_id');
    }

    public function summaries(): HasMany
    {
        return $this->hasMany(AttendanceSummary::class, 'batch_id');
    }
}
