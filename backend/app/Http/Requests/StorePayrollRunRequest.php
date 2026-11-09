<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePayrollRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\PayrollRun::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'period_year' => ['required', 'integer'],
            'period_month' => ['required', 'integer', 'min:1', 'max:12'],
        ];
    }
}
