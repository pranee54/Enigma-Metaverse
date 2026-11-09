@extends('layouts.app')

@section('title', __('messages.pages.settings.title'))

@section('content')
@php
    $val = fn (string $key, mixed $default = '') => old($key, optional($configs->get($key))->displayValue() ?? $default);
@endphp

<div class="mx-auto max-w-5xl space-y-8">
    <div>
        <h1 class="font-display text-2xl font-semibold tracking-tight">{{ __('messages.pages.settings.title') }}</h1>
        <p class="mt-1 text-sm text-stone-500">{{ __('messages.pages.settings.subtitle') }}</p>
    </div>

    <form method="POST" action="{{ route('settings.configs') }}" class="overflow-hidden rounded-xl border border-stone-200 bg-white shadow-sm">
        @csrf
        @method('PUT')

        <div class="border-b border-stone-100 px-5 py-4">
            <h2 class="font-medium">{{ __('messages.pages.settings.company_policy') }}</h2>
            <p class="mt-0.5 text-xs text-stone-500">{{ __('messages.pages.settings.company_policy_hint') }}</p>
        </div>

        <div class="grid gap-6 px-5 py-5 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <label class="mb-1.5 block text-xs font-medium text-stone-600">{{ __('messages.pages.settings.company_name') }}</label>
                <input name="company_name" value="{{ $val('company_name', 'Enigma Payroll') }}" required
                       class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-teal-600 focus:outline-none focus:ring-1 focus:ring-teal-600">
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-medium text-stone-600">{{ __('messages.pages.settings.state') }}</label>
                <input name="company_state" value="{{ $val('company_state', 'Telangana') }}" required
                       class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-teal-600 focus:outline-none focus:ring-1 focus:ring-teal-600">
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-medium text-stone-600">{{ __('messages.pages.settings.currency') }}</label>
                <input name="currency" value="{{ $val('currency', 'INR') }}" required
                       class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-teal-600 focus:outline-none focus:ring-1 focus:ring-teal-600">
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-medium text-stone-600">{{ __('messages.pages.settings.proration') }}</label>
                <select name="proration_divisor" class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-teal-600 focus:outline-none focus:ring-1 focus:ring-teal-600">
                    @foreach(['calendar' => __('messages.pages.settings.proration_calendar'), '30' => __('messages.pages.settings.proration_30'), '26' => __('messages.pages.settings.proration_26')] as $option => $label)
                        <option value="{{ $option }}" @selected($val('proration_divisor', 'calendar') == $option)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-medium text-stone-600">{{ __('messages.pages.settings.ot_multiplier') }}</label>
                <input type="number" step="0.1" min="1" max="5" name="overtime_multiplier"
                       value="{{ $val('overtime_multiplier', 2) }}" required
                       class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-teal-600 focus:outline-none focus:ring-1 focus:ring-teal-600">
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-medium text-stone-600">{{ __('messages.pages.settings.deduction_cap') }}</label>
                <input type="number" step="1" min="1" max="100" name="deduction_cap_percent"
                       value="{{ $val('deduction_cap_percent', 50) }}" required
                       class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-teal-600 focus:outline-none focus:ring-1 focus:ring-teal-600">
            </div>
        </div>

        <div class="border-t border-stone-100 bg-stone-50 px-5 py-3">
            <button class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">{{ __('messages.pages.settings.save_settings') }}</button>
        </div>
    </form>

    <section class="overflow-hidden rounded-xl border border-stone-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-end justify-between gap-3 border-b border-stone-100 px-5 py-4">
            <div>
                <h2 class="font-medium">{{ __('messages.pages.settings.statutory_versions') }}</h2>
                <p class="mt-0.5 text-xs text-stone-500">{{ __('messages.pages.settings.statutory_hint') }}</p>
            </div>
            <a href="{{ route('settings.statutory.create') }}"
               class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">
                {{ __('messages.pages.settings.add_version') }}
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-stone-50 text-xs uppercase tracking-wide text-stone-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">{{ __('messages.pages.settings.version') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('messages.pages.settings.epf_eps') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('messages.pages.settings.esi') }}</th>
                        <th class="px-5 py-3 font-medium">PT</th>
                        <th class="px-5 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rules as $r)
                        @php $p = $r->payload_json ?? []; @endphp
                        <tr class="border-t border-stone-100 align-top">
                            <td class="px-5 py-4">
                                <p class="font-medium text-stone-800">{{ $r->code }}</p>
                                <p class="text-xs text-stone-500">{{ __('messages.pages.settings.from', ['date' => $r->effective_from?->format('d M Y')]) }}</p>
                                @if($r->notes)
                                    <p class="mt-1 text-xs text-stone-500">{{ $r->notes }}</p>
                                @endif
                                @if($r->payroll_items_count > 0)
                                    <p class="mt-1 text-xs text-amber-700">{{ __('messages.pages.settings.in_use', ['count' => $r->payroll_items_count]) }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-stone-700">
                                <p>{{ __('messages.pages.settings.ceiling', ['amount' => number_format((float) ($p['epf_ceiling'] ?? 0), 0)]) }}</p>
                                <p class="text-xs text-stone-500">{{ number_format(((float) ($p['epf_employee_rate'] ?? 0)) * 100, 2) }}% / {{ number_format(((float) ($p['epf_employer_rate'] ?? 0)) * 100, 2) }}%</p>
                            </td>
                            <td class="px-5 py-4 text-stone-700">
                                <p>{{ __('messages.pages.settings.upto', ['amount' => number_format((float) ($p['esi_threshold'] ?? 0), 0)]) }}</p>
                                <p class="text-xs text-stone-500">{{ number_format(((float) ($p['esi_employee_rate'] ?? 0)) * 100, 2) }}% / {{ number_format(((float) ($p['esi_employer_rate'] ?? 0)) * 100, 2) }}%</p>
                            </td>
                            <td class="px-5 py-4 text-xs text-stone-600">
                                @forelse(($p['pt_slabs'] ?? []) as $slab)
                                    <p>
                                        @if($slab['upto'] === null)
                                            {{ __('messages.pages.settings.pt_above', ['amount' => number_format((float) $slab['amount'], 0)]) }}
                                        @else
                                            {{ __('messages.pages.settings.pt_lte', ['upto' => number_format((float) $slab['upto'], 0), 'amount' => number_format((float) $slab['amount'], 0)]) }}
                                        @endif
                                    </p>
                                @empty
                                    <p class="text-stone-400">—</p>
                                @endforelse
                            </td>
                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                <a href="{{ route('settings.statutory.edit', $r) }}" class="text-teal-700 hover:underline">{{ __('messages.common.edit') }}</a>
                                @if($r->payroll_items_count === 0 && $rules->count() > 1)
                                    <form method="POST" action="{{ route('settings.statutory.destroy', $r) }}" class="mt-2 inline"
                                          onsubmit="return confirm(@js(__('messages.pages.settings.delete_confirm')))">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-red-600 hover:underline">{{ __('messages.common.delete') }}</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-stone-500">{{ __('messages.pages.settings.empty_statutory') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
