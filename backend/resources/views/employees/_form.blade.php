@php
    /** @var \App\Models\Employee|null $employee */
    $employee = $employee ?? null;
@endphp

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="mb-1 block text-xs text-stone-500">{{ __('messages.pages.employees.fields.employee_code') }}</label>
        <input name="employee_code" value="{{ old('employee_code', $employee?->employee_code) }}" required
               class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm">
    </div>
    <div>
        <label class="mb-1 block text-xs text-stone-500">{{ __('messages.common.status') }}</label>
        <select name="status" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm">
            @foreach($statuses as $status)
                <option value="{{ $status->value }}" @selected(old('status', $employee?->status?->value ?? 'draft') === $status->value)>
                    {{ $status->value }}
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="mb-1 block text-xs text-stone-500">{{ __('messages.pages.employees.fields.first_name') }}</label>
        <input name="first_name" value="{{ old('first_name', $employee?->first_name) }}" required
               class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm">
    </div>
    <div>
        <label class="mb-1 block text-xs text-stone-500">{{ __('messages.pages.employees.fields.last_name') }}</label>
        <input name="last_name" value="{{ old('last_name', $employee?->last_name) }}" required
               class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm">
    </div>
    <div>
        <label class="mb-1 block text-xs text-stone-500">{{ __('messages.pages.employees.fields.dob') }}</label>
        <input type="date" name="dob" value="{{ old('dob', $employee?->dob?->toDateString()) }}" required
               class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm">
    </div>
    <div>
        <label class="mb-1 block text-xs text-stone-500">{{ __('messages.pages.employees.fields.joining_date') }}</label>
        <input type="date" name="joining_date" value="{{ old('joining_date', $employee?->joining_date?->toDateString() ?? now()->toDateString()) }}" required
               class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm">
    </div>
    <div>
        <label class="mb-1 block text-xs text-stone-500">{{ __('messages.auth.email') }}</label>
        <input type="email" name="email" value="{{ old('email', $employee?->email) }}"
               class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm">
    </div>
    <div>
        <label class="mb-1 block text-xs text-stone-500">{{ __('messages.common.phone') }}</label>
        <input name="phone" value="{{ old('phone', $employee?->phone) }}"
               class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm">
    </div>
    <div>
        <label class="mb-1 block text-xs text-stone-500">{{ __('messages.common.department') }}</label>
        <input name="department" value="{{ old('department', $employee?->department) }}"
               class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm">
    </div>
    <div>
        <label class="mb-1 block text-xs text-stone-500">{{ __('messages.pages.employees.fields.designation') }}</label>
        <input name="designation" value="{{ old('designation', $employee?->designation) }}" required
               class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm">
    </div>
    <div>
        <label class="mb-1 block text-xs text-stone-500">{{ __('messages.pages.employees.fields.work_location') }}</label>
        <input name="work_location" value="{{ old('work_location', $employee?->work_location ?? 'Hyderabad') }}" required
               class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm">
    </div>
    <div>
        <label class="mb-1 block text-xs text-stone-500">{{ __('messages.pages.employees.fields.state_code') }}</label>
        <input name="state_code" value="{{ old('state_code', $employee?->state_code ?? 'TG') }}" required
               class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm">
    </div>
    <div>
        <label class="mb-1 block text-xs text-stone-500">{{ __('messages.pages.employees.fields.employment_category') }}</label>
        <select name="employment_category" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm">
            @foreach($categories as $category)
                <option value="{{ $category->value }}" @selected(old('employment_category', $employee?->employment_category?->value) === $category->value)>
                    {{ $category->value }}
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="mb-1 block text-xs text-stone-500">{{ __('messages.pages.employees.fields.tax_regime') }}</label>
        <select name="tax_regime" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm">
            <option value="new" @selected(old('tax_regime', $employee?->tax_regime ?? 'new') === 'new')>new</option>
            <option value="old" @selected(old('tax_regime', $employee?->tax_regime) === 'old')>old</option>
        </select>
    </div>
</div>

<h2 class="mt-8 font-medium">{{ __('messages.pages.employees.bank_ids') }}</h2>
<div class="mt-3 grid gap-4 sm:grid-cols-2">
    <div>
        <label class="mb-1 block text-xs text-stone-500">{{ __('messages.pages.employees.fields.pan') }}</label>
        <input name="pan" value="{{ old('pan', $employee?->pan) }}" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm">
    </div>
    <div class="flex items-end pb-2">
        <label class="flex items-center gap-2 text-sm text-stone-600">
            <input type="checkbox" name="pan_not_available" value="1" @checked(old('pan_not_available', $employee?->pan_not_available))>
            {{ __('messages.pages.employees.fields.pan_na') }}
        </label>
    </div>
    <div>
        <label class="mb-1 block text-xs text-stone-500">{{ __('messages.pages.employees.fields.uan') }}</label>
        <input name="uan" value="{{ old('uan', $employee?->uan) }}" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm">
    </div>
    <div>
        <label class="mb-1 block text-xs text-stone-500">{{ __('messages.pages.employees.fields.esic') }}</label>
        <input name="esic_number" value="{{ old('esic_number', $employee?->esic_number) }}" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm">
    </div>
    <div>
        <label class="mb-1 block text-xs text-stone-500">{{ __('messages.pages.employees.fields.bank_account') }}</label>
        <input name="bank_account" value="{{ old('bank_account', $employee?->bank_account) }}" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm">
    </div>
    <div>
        <label class="mb-1 block text-xs text-stone-500">{{ __('messages.pages.employees.fields.ifsc') }}</label>
        <input name="bank_ifsc" value="{{ old('bank_ifsc', $employee?->bank_ifsc) }}" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm">
    </div>
    <div>
        <label class="mb-1 block text-xs text-stone-500">{{ __('messages.pages.employees.fields.bank_name') }}</label>
        <input name="bank_name" value="{{ old('bank_name', $employee?->bank_name) }}" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm">
    </div>
    <div>
        <label class="mb-1 block text-xs text-stone-500">{{ __('messages.pages.employees.fields.appointment_path') }}</label>
        <input name="appointment_letter_path" value="{{ old('appointment_letter_path', $employee?->appointment_letter_path) }}"
               class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm" placeholder="{{ __('messages.pages.employees.fields.appointment_placeholder') }}">
    </div>
</div>
