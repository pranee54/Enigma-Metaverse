@extends('layouts.app')

@section('title', __('messages.pages.payslips.title'))

@section('content')
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="font-display text-2xl font-semibold">{{ __('messages.pages.payslips.title') }}</h1>
        <p class="text-sm text-stone-500">{{ __('messages.pages.payslips.subtitle') }}</p>
    </div>
</div>

<form method="GET" action="{{ route('payslips.index') }}" class="mt-4 flex flex-wrap items-end gap-3 rounded-lg border border-stone-200 bg-white p-4">
    <div>
        <label class="mb-1 block text-xs text-stone-500">{{ __('messages.common.month') }}</label>
        <select name="period" class="min-w-44 rounded-md border border-stone-300 px-3 py-2 text-sm" onchange="this.form.submit()">
            <option value="all" @selected($selectedPeriod === 'all')>{{ __('messages.pages.payslips.all_months') }}</option>
            @foreach($periods as $period)
                <option value="{{ $period['value'] }}" @selected($selectedPeriod === $period['value'])>
                    {{ $period['label'] }}
                </option>
            @endforeach
        </select>
    </div>
    <noscript>
        <button class="rounded-md bg-stone-800 px-4 py-2 text-sm text-white">{{ __('messages.common.filter') }}</button>
    </noscript>
</form>

@if($selectedPeriod !== 'all')
    @php
        $label = collect($periods)->firstWhere('value', $selectedPeriod)['label'] ?? $selectedPeriod;
    @endphp
    <p class="mt-4 text-sm text-stone-600">{{ __('messages.pages.payslips.showing_month', ['month' => $label]) }}</p>
@endif

<div class="mt-3 overflow-hidden rounded-lg border border-stone-200 bg-white">
    <table class="w-full text-sm">
        <thead class="bg-stone-50 text-xs uppercase text-stone-500">
            <tr>
                <th class="px-4 py-3 text-left">{{ __('messages.common.employee') }}</th>
                <th class="px-4 py-3 text-left">{{ __('messages.common.period') }}</th>
                <th class="px-4 py-3 text-left">{{ __('messages.pages.payslips.run_state') }}</th>
                <th class="px-4 py-3 text-right">{{ __('messages.pages.payslips.net_pay') }}</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($payslips as $s)
                <tr class="border-t border-stone-100">
                    <td class="px-4 py-3">
                        <p class="font-medium">{{ $s->employee?->full_name }}</p>
                        <p class="text-xs text-stone-500">{{ $s->employee?->employee_code }}</p>
                    </td>
                    <td class="px-4 py-3">{{ $s->run?->periodLabel() }}</td>
                    <td class="px-4 py-3"><span class="rounded bg-stone-100 px-2 py-0.5 text-xs">{{ $s->run?->state?->value }}</span></td>
                    <td class="px-4 py-3 text-right font-semibold">₹ {{ number_format($s->net, 2) }}</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('payslips.show', $s) }}" class="text-teal-700 hover:underline">{{ __('messages.common.open') }}</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-10 text-center">
                        <p class="font-medium text-stone-800">{{ __('messages.pages.payslips.empty') }}</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $payslips->links() }}</div>
@endsection
