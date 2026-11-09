<?php

namespace App\Http\Requests;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('employee')) ?? false;
    }

    public function rules(): array
    {
        $employee = $this->route('employee');

        return [
            'employee_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('employees', 'employee_code')->ignore($employee?->id),
            ],
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
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'pan_not_available' => $this->boolean('pan_not_available'),
        ]);
    }
}
