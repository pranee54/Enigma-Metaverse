@php
    $effectiveDefault = $effectiveDefault ?? now()->toDateString();
    $notesDefault = $notesDefault ?? '';
@endphp

<div class="grid gap-4 sm:grid-cols-3">
    <div>
        <label class="mb-1 block text-xs text-stone-500">{{ __('messages.pages.settings.rule_code') }}</label>
        <input name="code" value="{{ old('code', $defaults['code']) }}" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm">
    </div>
    <div>
        <label class="mb-1 block text-xs text-stone-500">{{ __('messages.pages.employees.effective_from') }}</label>
        <input type="date" name="effective_from" value="{{ old('effective_from', $effectiveDefault) }}"
               class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm">
    </div>
    <div>
        <label class="mb-1 block text-xs text-stone-500">{{ __('messages.common.notes') }}</label>
        <input name="notes" value="{{ old('notes', $notesDefault) }}" placeholder="{{ __('messages.pages.settings.notes_placeholder') }}"
               class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm">
    </div>
</div>

<div>
    <h3 class="text-sm font-medium text-stone-700">{{ __('messages.pages.settings.epf_eps') }}</h3>
    <div class="mt-2 grid gap-4 sm:grid-cols-4">
        <div>
            <label class="mb-1 block text-xs text-stone-500">{{ __('messages.pages.settings.epf_ceiling') }}</label>
            <input type="number" step="1" name="epf_ceiling" value="{{ old('epf_ceiling', $defaults['epf_ceiling']) }}"
                   class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-xs text-stone-500">{{ __('messages.pages.settings.employee_rate') }}</label>
            <input type="number" step="0.01" name="epf_employee_rate_pct" value="{{ old('epf_employee_rate_pct', $defaults['epf_employee_rate_pct']) }}"
                   class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-xs text-stone-500">{{ __('messages.pages.settings.employer_rate') }}</label>
            <input type="number" step="0.01" name="epf_employer_rate_pct" value="{{ old('epf_employer_rate_pct', $defaults['epf_employer_rate_pct']) }}"
                   class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-xs text-stone-500">{{ __('messages.pages.settings.eps_rate') }}</label>
            <input type="number" step="0.01" name="eps_rate_pct" value="{{ old('eps_rate_pct', $defaults['eps_rate_pct']) }}"
                   class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm">
        </div>
    </div>
</div>

<div>
    <h3 class="text-sm font-medium text-stone-700">{{ __('messages.pages.settings.esi') }}</h3>
    <div class="mt-2 grid gap-4 sm:grid-cols-3">
        <div>
            <label class="mb-1 block text-xs text-stone-500">{{ __('messages.pages.settings.gross_threshold') }}</label>
            <input type="number" step="1" name="esi_threshold" value="{{ old('esi_threshold', $defaults['esi_threshold']) }}"
                   class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-xs text-stone-500">{{ __('messages.pages.settings.employee_rate') }}</label>
            <input type="number" step="0.01" name="esi_employee_rate_pct" value="{{ old('esi_employee_rate_pct', $defaults['esi_employee_rate_pct']) }}"
                   class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-xs text-stone-500">{{ __('messages.pages.settings.employer_rate') }}</label>
            <input type="number" step="0.01" name="esi_employer_rate_pct" value="{{ old('esi_employer_rate_pct', $defaults['esi_employer_rate_pct']) }}"
                   class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm">
        </div>
    </div>
</div>

<div>
    <h3 class="text-sm font-medium text-stone-700">{{ __('messages.pages.settings.pt') }}</h3>
    <div class="mt-2 overflow-hidden rounded-md border border-stone-200">
        <table class="w-full text-sm">
            <thead class="bg-stone-50 text-xs uppercase text-stone-500">
                <tr>
                    <th class="px-3 py-2 text-left">{{ __('messages.pages.settings.slab') }}</th>
                    <th class="px-3 py-2 text-left">{{ __('messages.pages.settings.gross_upto') }}</th>
                    <th class="px-3 py-2 text-left">{{ __('messages.pages.settings.pt_amount') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr class="border-t border-stone-100">
                    <td class="px-3 py-2">{{ __('messages.pages.settings.slab_n', ['n' => 1]) }}</td>
                    <td class="px-3 py-2">
                        <input type="number" name="pt_slab_1_upto" value="{{ old('pt_slab_1_upto', $defaults['pt_slab_1_upto']) }}"
                               class="w-full rounded-md border border-stone-300 px-3 py-1.5 text-sm">
                    </td>
                    <td class="px-3 py-2">
                        <input type="number" name="pt_slab_1_amount" value="{{ old('pt_slab_1_amount', $defaults['pt_slab_1_amount']) }}"
                               class="w-full rounded-md border border-stone-300 px-3 py-1.5 text-sm">
                    </td>
                </tr>
                <tr class="border-t border-stone-100">
                    <td class="px-3 py-2">{{ __('messages.pages.settings.slab_n', ['n' => 2]) }}</td>
                    <td class="px-3 py-2">
                        <input type="number" name="pt_slab_2_upto" value="{{ old('pt_slab_2_upto', $defaults['pt_slab_2_upto']) }}"
                               class="w-full rounded-md border border-stone-300 px-3 py-1.5 text-sm">
                    </td>
                    <td class="px-3 py-2">
                        <input type="number" name="pt_slab_2_amount" value="{{ old('pt_slab_2_amount', $defaults['pt_slab_2_amount']) }}"
                               class="w-full rounded-md border border-stone-300 px-3 py-1.5 text-sm">
                    </td>
                </tr>
                <tr class="border-t border-stone-100">
                    <td class="px-3 py-2">{{ __('messages.pages.settings.above_slab') }}</td>
                    <td class="px-3 py-2 text-stone-400">{{ __('messages.pages.settings.no_upper') }}</td>
                    <td class="px-3 py-2">
                        <input type="number" name="pt_slab_3_amount" value="{{ old('pt_slab_3_amount', $defaults['pt_slab_3_amount']) }}"
                               class="w-full rounded-md border border-stone-300 px-3 py-1.5 text-sm">
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
