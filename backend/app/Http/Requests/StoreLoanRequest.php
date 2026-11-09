<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Loan::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'type' => ['required', 'in:advance,loan'],
            'principal' => ['required', 'numeric', 'min:1'],
            'tenure_months' => ['required', 'integer', 'min:1'],
            'monthly_recovery' => ['required', 'numeric', 'min:1'],
            'interest_rate' => ['required', 'numeric', 'min:0'],
            'started_on' => ['required', 'date'],
        ];
    }
}
