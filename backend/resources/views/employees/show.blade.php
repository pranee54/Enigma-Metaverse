@extends('layouts.app')

@section('title', $employee->full_name)

@section('content')
@php
    $structure = $employee->activeSalaryStructure;
    $amounts = [
        'BASIC' => (float) ($structure?->components->firstWhere('code', 'BASIC')?->amount_monthly ?? 0),
        'HRA' => (float) ($structure?->components->firstWhere('code', 'HRA')?->amount_monthly ?? 0),
        'SPECIAL' => (float) ($structure?->components->firstWhere('code', 'SPECIAL')?->amount_monthly ?? 0),
    ];
@endphp
<div class="flex flex-wrap items-start justify-between gap-4">
    <div>
        <a href="{{ route('employees.index') }}" class="text-sm text-teal-700 hover:underline">{{ __('messages.common.back_to', ['page' => __('messages.pages.employees.title')]) }}</a>
        <h1 class="mt-3 font-display text-2xl font-semibold">{{ $employee->full_name }}</h1>
        <p class="text-stone-500">{{ $employee->employee_code }} · {{ $employee->designation }} · {{ $employee->status->value }}</p>
    </div>
    @if(auth()->user()->hasRole('hr'))
        <a href="{{ route('employees.edit', $employee) }}" class="rounded-md border border-stone-300 px-4 py-2 text-sm hover:bg-stone-50">{{ __('messages.common.edit') }}</a>
    @endif
</div>

@if(count($missing))
    <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        {{ __('messages.pages.employees.incomplete', ['fields' => implode(', ', $missing)]) }}
    </div>
@endif

<div class="mt-6 grid gap-4 lg:grid-cols-2">
    <div class="rounded-lg border border-stone-200 bg-white p-5">
        <h2 class="font-medium">{{ __('messages.pages.employees.profile') }}</h2>
        <dl class="mt-3 space-y-2 text-sm">
            <div class="flex justify-between gap-4"><dt class="text-stone-500">{{ __('messages.pages.employees.joined') }}</dt><dd>{{ $employee->joining_date?->toDateString() }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-stone-500">{{ __('messages.pages.employees.location') }}</dt><dd>{{ $employee->work_location }} ({{ $employee->state_code }})</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-stone-500">{{ __('messages.pages.employees.fields.pan') }}</dt><dd>{{ $employee->pan ?: 'N/A' }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-stone-500">{{ __('messages.pages.employees.fields.bank_name') }}</dt><dd>{{ $employee->bank_account }} / {{ $employee->bank_ifsc }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-stone-500">{{ __('messages.pages.employees.fields.uan') }}</dt><dd>{{ $employee->uan }}</dd></div>
        </dl>
    </div>
    <div class="rounded-lg border border-stone-200 bg-white p-5">
        <h2 class="font-medium">{{ __('messages.pages.employees.wage_bases') }}</h2>
        @if ($wageBases)
            <dl class="mt-3 space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-stone-500">{{ __('messages.pages.employees.code_wages') }}</dt><dd>₹ {{ number_format($wageBases['code_wages'], 2) }}</dd></div>
                <div class="flex justify-between"><dt class="text-stone-500">{{ __('messages.pages.employees.epf_wages') }}</dt><dd>₹ {{ number_format($wageBases['epf_wages'], 2) }}</dd></div>
                <div class="flex justify-between"><dt class="text-stone-500">{{ __('messages.pages.employees.esi_wages') }}</dt><dd>₹ {{ number_format($wageBases['esi_wages'], 2) }}</dd></div>
                <div class="flex justify-between"><dt class="text-stone-500">{{ __('messages.pages.employees.pt_gross') }}</dt><dd>₹ {{ number_format($wageBases['pt_gross'], 2) }}</dd></div>
            </dl>
            @if ($wageBases['warning'])
                <p class="mt-2 text-sm text-amber-700">{{ $wageBases['warning'] }}</p>
            @endif
        @else
            <p class="mt-3 text-sm text-stone-500">{{ __('messages.pages.employees.no_structure') }}</p>
        @endif
    </div>
</div>

@if ($structure)
<div class="mt-6 rounded-lg border border-stone-200 bg-white p-5">
    <h2 class="font-medium">{{ __('messages.pages.employees.salary_components') }}</h2>
    <table class="mt-3 w-full text-sm">
        <thead>
            <tr class="text-left text-xs uppercase text-stone-500">
                <th class="py-2">{{ __('messages.common.code') }}</th><th>{{ __('messages.common.name') }}</th><th class="text-right">{{ __('messages.pages.employees.monthly') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($structure->components as $c)
                <tr class="border-t border-stone-100">
                    <td class="py-2">{{ $c->code }}</td>
                    <td>{{ $c->name }}</td>
                    <td class="text-right">₹ {{ number_format($c->amount_monthly, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@if(auth()->user()->hasRole('hr'))
<form method="POST" action="{{ route('employees.salary', $employee) }}" class="mt-6 grid gap-3 rounded-lg border border-stone-200 bg-white p-5 sm:grid-cols-4">
    @csrf
    @method('PUT')
    <div class="sm:col-span-4">
        <h2 class="font-medium">{{ $structure ? __('messages.pages.employees.edit_salary') : __('messages.pages.employees.add_structure') }}</h2>
        <p class="mt-1 text-xs text-stone-500">{{ __('messages.pages.employees.starting_salary_hint') }}</p>
    </div>
    <div>
        <label class="mb-1 block text-xs text-stone-500">{{ __('messages.pages.employees.basic') }}</label>
        <input type="number" step="0.01" name="basic" value="{{ old('basic', $amounts['BASIC']) }}" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm" required>
    </div>
    <div>
        <label class="mb-1 block text-xs text-stone-500">{{ __('messages.pages.employees.hra') }}</label>
        <input type="number" step="0.01" name="hra" value="{{ old('hra', $amounts['HRA']) }}" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm">
    </div>
    <div>
        <label class="mb-1 block text-xs text-stone-500">{{ __('messages.pages.employees.special') }}</label>
        <input type="number" step="0.01" name="special" value="{{ old('special', $amounts['SPECIAL']) }}" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm">
    </div>
    <div>
        <label class="mb-1 block text-xs text-stone-500">{{ __('messages.pages.employees.effective_from') }}</label>
        <input type="date" name="effective_from" value="{{ old('effective_from', now()->toDateString()) }}" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm" required>
    </div>
    <div class="sm:col-span-4">
        <button class="rounded-md bg-teal-700 px-4 py-2 text-sm text-white">{{ __('messages.pages.employees.save_salary') }}</button>
    </div>
</form>
@endif
@endsection
