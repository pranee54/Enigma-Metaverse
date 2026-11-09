<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSalaryStructureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('updateSalary', $this->route('employee')) ?? false;
    }

    public function rules(): array
    {
        return [
            'basic' => ['required', 'numeric', 'min:0'],
            'hra' => ['nullable', 'numeric', 'min:0'],
            'special' => ['nullable', 'numeric', 'min:0'],
            'effective_from' => ['required', 'date'],
        ];
    }
}
