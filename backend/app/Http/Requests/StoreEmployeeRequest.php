<?php

namespace App\Http\Requests;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Employee::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'employee_code' => ['required', 'string', 'max:50', Rule::unique('employees', 'employee_code')],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'dob' => ['required', 'date'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:20'],
            'department' => ['nullable', 'string', 'max:100'],
            'designation' => ['required', 'string', 'max:100'],
            'work_location' => ['required', 'string', 'max:100'],
            'state_code' => ['required', 'string', 'max:10'],
            'employment_category' => ['required', Rule::enum(EmploymentCategory::class)],
            'joining_date' => ['required', 'date'],
            'status' => ['required', Rule::enum(EmployeeStatus::class)],
            'pan' => ['nullable', 'string', 'max:20'],
            'pan_not_available' => ['sometimes', 'boolean'],
            'uan' => ['nullable', 'string', 'max:30'],
            'esic_number' => ['nullable', 'string', 'max:30'],
            'bank_account' => ['nullable', 'string', 'max:30'],
            'bank_ifsc' => ['nullable', 'string', 'max:20'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'tax_regime' => ['required', Rule::in(['new', 'old'])],
            'appointment_letter_path' => ['nullable', 'string', 'max:255'],
            'basic' => ['nullable', 'numeric', 'min:0'],
            'hra' => ['nullable', 'numeric', 'min:0'],
            'special' => ['nullable', 'numeric', 'min:0'],
            'salary_effective_from' => ['nullable', 'date', 'required_with:basic'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'pan_not_available' => $this->boolean('pan_not_available'),
        ]);
    }
}
