<?php

namespace App\Models;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{
    protected $fillable = [
        'employee_code', 'first_name', 'last_name', 'dob', 'email', 'phone',
        'department', 'designation', 'manager_id', 'work_location', 'state_code',
        'employment_category', 'joining_date', 'probation_end', 'confirmation_date',
        'status', 'standard_daily_hours', 'weekly_rest_day', 'shift_name',
        'pan', 'pan_not_available', 'uan', 'esic_number',
        'bank_account', 'bank_ifsc', 'bank_name', 'tax_regime',
        'aadhaar_consent_at', 'appointment_letter_path', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'dob' => 'date',
            'joining_date' => 'date',
            'probation_end' => 'date',
            'confirmation_date' => 'date',
            'status' => EmployeeStatus::class,
            'employment_category' => EmploymentCategory::class,
            'pan_not_available' => 'boolean',
            'aadhaar_consent_at' => 'datetime',
            'standard_daily_hours' => 'decimal:1',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'manager_id');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(EmployeeStatusHistory::class);
    }

    public function salaryStructures(): HasMany
    {
        return $this->hasMany(SalaryStructure::class);
    }

    public function activeSalaryStructure(): HasOne
    {
        return $this->hasOne(SalaryStructure::class)->where('status', 'active')->latestOfMany('effective_from');
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function exits(): HasMany
    {
        return $this->hasMany(EmployeeExit::class);
    }

    public function missingActivationFields(): array
    {
        $missing = [];
        foreach (['employee_code', 'first_name', 'dob', 'employment_category', 'joining_date', 'work_location', 'state_code', 'designation'] as $field) {
            if (blank($this->{$field})) {
                $missing[] = $field;
            }
        }
        if (blank($this->bank_account) || blank($this->bank_ifsc)) {
            $missing[] = 'bank_details';
        }
        if (blank($this->pan) && ! $this->pan_not_available) {
            $missing[] = 'pan';
        }
        if (! $this->activeSalaryStructure()->exists()) {
            $missing[] = 'active_salary_structure';
        }
        if (blank($this->appointment_letter_path)) {
            $missing[] = 'appointment_letter';
        }

        return $missing;
    }
}
