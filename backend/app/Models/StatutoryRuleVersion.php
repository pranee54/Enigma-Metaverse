<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StatutoryRuleVersion extends Model
{
    protected $fillable = ['code', 'effective_from', 'payload_json', 'notes'];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'payload_json' => 'array',
        ];
    }

    public function payrollItems(): HasMany
    {
        return $this->hasMany(PayrollItem::class, 'rule_version_id');
    }
}
