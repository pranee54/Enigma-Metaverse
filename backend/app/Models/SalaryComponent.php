<?php

namespace App\Models;

use App\Enums\ComponentClass;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryComponent extends Model
{
    protected $fillable = [
        'structure_id', 'code', 'name', 'class', 'amount_monthly', 'is_fixed',
        'include_in_code_wages', 'include_in_epf', 'include_in_esi',
        'include_in_gratuity', 'include_in_bonus', 'include_in_pt',
        'include_in_taxable', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'class' => ComponentClass::class,
            'amount_monthly' => 'decimal:2',
            'is_fixed' => 'boolean',
            'include_in_code_wages' => 'boolean',
            'include_in_epf' => 'boolean',
            'include_in_esi' => 'boolean',
            'include_in_gratuity' => 'boolean',
            'include_in_bonus' => 'boolean',
            'include_in_pt' => 'boolean',
            'include_in_taxable' => 'boolean',
        ];
    }

    public function structure(): BelongsTo
    {
        return $this->belongsTo(SalaryStructure::class, 'structure_id');
    }
}
