<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('hr') ?? false;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'category' => ['required', 'string'],
            'resignation_date' => ['nullable', 'date'],
            'last_working_date' => ['required', 'date'],
            'reason' => ['nullable', 'string'],
            'notice_days' => ['nullable', 'integer'],
            'notice_shortfall_days' => ['nullable', 'integer'],
        ];
    }
}
