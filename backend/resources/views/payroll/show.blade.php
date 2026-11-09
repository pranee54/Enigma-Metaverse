@extends('layouts.app')

@section('title', __('messages.pages.payroll.run_title', ['period' => $run->periodLabel()]))

@section('content')
@php
    $state = $run->state;
    $items = $run->items;
    $holds = $items->where('status', 'hold');
    $payable = $items->where('status', '!=', 'hold');
    $canPayslip = in_array($state, [
        \App\Enums\PayrollState::Approved,
        \App\Enums\PayrollState::Finalised,
        \App\Enums\PayrollState::Paid,
        \App\Enums\PayrollState::Reconciled,
    ], true);
@endphp

<a href="{{ route('payroll.index') }}" class="text-sm text-teal-700 hover:underline">← {{ __('messages.pages.payroll.title') }}</a>

<div class="mt-3 flex flex-wrap items-start justify-between gap-4">
    <div>
        <h1 class="font-display text-2xl font-semibold">{{ __('messages.pages.payroll.run_title', ['period' => $run->periodLabel()]) }}</h1>
        <p class="mt-1 text-sm text-stone-500">
            {{ __('messages.common.status') }}: <span class="rounded bg-stone-100 px-2 py-0.5 text-xs font-medium uppercase tracking-wide">{{ $state->value }}</span>
            @if($run->preparer) · {{ __('messages.pages.payroll.prepared_by') }} {{ $run->preparer->name }} @endif
            @if($run->approver) · {{ __('messages.pages.payroll.approved_by') }} {{ $run->approver->name }} @endif
        </p>
        @if($run->notes)
            <p class="mt-2 text-sm text-amber-700">{{ $run->notes }}</p>
        @endif
    </div>
</div>

<div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
    <div class="rounded-lg border border-stone-200 bg-white p-4">
        <p class="text-xs uppercase tracking-wide text-stone-500">{{ __('messages.pages.payroll.employees') }}</p>
        <p class="mt-1 text-2xl font-semibold">{{ $items->count() }}</p>
    </div>
    <div class="rounded-lg border border-stone-200 bg-white p-4">
        <p class="text-xs uppercase tracking-wide text-stone-500">{{ __('messages.pages.payroll.gross') }}</p>
        <p class="mt-1 text-2xl font-semibold">₹ {{ number_format($payable->sum('gross'), 2) }}</p>
    </div>
    <div class="rounded-lg border border-stone-200 bg-white p-4">
        <p class="text-xs uppercase tracking-wide text-stone-500">{{ __('messages.pages.payroll.deductions') }}</p>
        <p class="mt-1 text-2xl font-semibold">₹ {{ number_format($payable->sum('total_deductions'), 2) }}</p>
    </div>
    <div class="rounded-lg border border-stone-200 bg-white p-4">
        <p class="text-xs uppercase tracking-wide text-stone-500">{{ __('messages.pages.payroll.net_payable') }}</p>
        <p class="mt-1 text-2xl font-semibold">₹ {{ number_format($payable->sum('net'), 2) }}</p>
    </div>
    <div class="rounded-lg border border-stone-200 bg-white p-4">
        <p class="text-xs uppercase tracking-wide text-stone-500">{{ __('messages.pages.payroll.on_hold') }}</p>
        <p class="mt-1 text-2xl font-semibold {{ $holds->count() ? 'text-amber-700' : '' }}">{{ $holds->count() }}</p>
        <p class="mt-1 text-xs text-stone-500">{{ __('messages.pages.payroll.employer_cost', ['amount' => number_format($payable->sum('employer_cost'), 2)]) }}</p>
    </div>
</div>

@if(auth()->user()->hasRole('finance') && $state === \App\Enums\PayrollState::UnderReview)
    <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
        {{ __('messages.pages.payroll.finance_review', [
            'net' => number_format($payable->sum('net'), 2),
            'holds' => $holds->count(),
            'preparer' => $run->preparer?->name ?? '—',
        ]) }}
    </div>
@endif

@if(auth()->user()->hasAnyRole(['finance', 'auditor']) && in_array($state, [
    \App\Enums\PayrollState::Approved,
    \App\Enums\PayrollState::Finalised,
    \App\Enums\PayrollState::Paid,
], true))
    <div class="mt-4 rounded-lg border border-stone-200 bg-white px-4 py-3 text-sm text-stone-600">
        {{ __('messages.pages.payroll.finance_next') }}
        <a href="{{ route('exports.index') }}" class="font-medium text-teal-700 hover:underline">{{ __('messages.pages.payroll.finance_exports_link') }}</a>.
    </div>
@endif

<div class="mt-4 flex flex-wrap gap-2">
    @if(auth()->user()->hasRole('hr'))
        @if(in_array($state, [\App\Enums\PayrollState::Draft, \App\Enums\PayrollState::Calculated, \App\Enums\PayrollState::Validated], true))
            <form method="POST" action="{{ route('payroll.calculate', $run) }}">@csrf
                <button class="rounded-md bg-teal-700 px-3 py-1.5 text-sm text-white">
                    {{ $state === \App\Enums\PayrollState::Draft ? __('messages.pages.payroll.calculate') : __('messages.pages.payroll.recalculate') }}
                </button>
            </form>
        @endif
        @if($state->canTransitionTo(\App\Enums\PayrollState::Validated))
            <form method="POST" action="{{ route('payroll.validate', $run) }}">@csrf
                <button class="rounded-md border border-stone-300 px-3 py-1.5 text-sm">{{ __('messages.pages.payroll.validate') }}</button>
            </form>
        @endif
        @if($state->canTransitionTo(\App\Enums\PayrollState::UnderReview))
            <form method="POST" action="{{ route('payroll.submit', $run) }}">@csrf
                <button class="rounded-md border border-stone-300 px-3 py-1.5 text-sm">{{ __('messages.pages.payroll.submit_review') }}</button>
            </form>
        @endif
    @endif

    @if(auth()->user()->hasRole('finance'))
        @if($state->canTransitionTo(\App\Enums\PayrollState::Approved))
            <form method="POST" action="{{ route('payroll.approve', $run) }}">@csrf
                <button class="rounded-md bg-teal-700 px-3 py-1.5 text-sm text-white">{{ __('messages.pages.payroll.approve') }}</button>
            </form>
        @endif
        @if($state->canTransitionTo(\App\Enums\PayrollState::Finalised))
            <form method="POST" action="{{ route('payroll.finalise', $run) }}">@csrf
                <button class="rounded-md border border-stone-300 px-3 py-1.5 text-sm">{{ __('messages.pages.payroll.finalise') }}</button>
            </form>
        @endif
        @if($state->canTransitionTo(\App\Enums\PayrollState::Paid))
            <form method="POST" action="{{ route('payroll.paid', $run) }}">@csrf
                <button class="rounded-md border border-stone-300 px-3 py-1.5 text-sm">{{ __('messages.pages.payroll.mark_paid') }}</button>
            </form>
        @endif
        @if($state->canTransitionTo(\App\Enums\PayrollState::Reconciled))
            <form method="POST" action="{{ route('payroll.reconcile', $run) }}">@csrf
                <button class="rounded-md border border-stone-300 px-3 py-1.5 text-sm">{{ __('messages.pages.payroll.reconcile') }}</button>
            </form>
        @endif
    @endif
</div>

@if($items->isEmpty() && auth()->user()->hasRole('hr'))
    <div class="mt-6 rounded-lg border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-900">
        {{ __('messages.pages.payroll.hint_hr_calc', ['ready' => $approvedAttendance, 'active' => $activeEmployees]) }}
    </div>
@elseif($items->isEmpty())
    <div class="mt-6 rounded-lg border border-stone-200 bg-white px-4 py-3 text-sm text-stone-600">
        {{ __('messages.pages.payroll.hint_finance_wait', ['ready' => $approvedAttendance, 'active' => $activeEmployees]) }}
    </div>
@endif

<p class="mt-6 text-xs text-stone-500">{{ __('messages.pages.payroll.flow') }}</p>

<div class="mt-4 overflow-x-auto rounded-lg border border-stone-200 bg-white">
    <table class="w-full text-sm">
        <thead class="bg-stone-50 text-xs uppercase text-stone-500">
            <tr>
                <th class="px-3 py-3 text-left">{{ __('messages.common.employee') }}</th>
                <th class="px-3 py-3 text-right">{{ __('messages.pages.payroll.gross') }}</th>
                <th class="px-3 py-3 text-right">{{ __('messages.pages.payroll.deductions') }}</th>
                <th class="px-3 py-3 text-right">{{ __('messages.pages.payroll.net') }}</th>
                <th class="px-3 py-3 text-left">{{ __('messages.common.status') }}</th>
                <th class="px-3 py-3"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $item)
                <tr class="border-t border-stone-100">
                    <td class="px-3 py-2">{{ $item->employee?->employee_code }} — {{ $item->employee?->full_name }}</td>
                    <td class="px-3 py-2 text-right">{{ number_format($item->gross, 2) }}</td>
                    <td class="px-3 py-2 text-right">{{ number_format($item->total_deductions, 2) }}</td>
                    <td class="px-3 py-2 text-right font-medium">{{ number_format($item->net, 2) }}</td>
                    <td class="px-3 py-2">
                        <span class="rounded px-2 py-0.5 text-xs {{ $item->status === 'hold' ? 'bg-amber-100 text-amber-800' : 'bg-teal-50 text-teal-800' }}">
                            {{ $item->status === 'ok' ? 'payable' : $item->status }}
                        </span>
                        @if($item->hold_reason)
                            <span class="mt-1 block text-xs text-amber-700">{{ $item->hold_reason }}</span>
                        @endif
                    </td>
                    <td class="px-3 py-2 text-right">
                        @if($canPayslip && $item->status !== 'hold')
                            <a href="{{ route('payslips.show', $item) }}" class="text-teal-700 hover:underline">{{ __('messages.pages.payslips.title_one') }}</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-3 py-10 text-center">
                        <p class="font-medium text-stone-800">{{ __('messages.pages.payroll.not_calculated') }}</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
