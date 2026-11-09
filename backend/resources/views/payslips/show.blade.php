@extends('layouts.app')

@section('title', __('messages.pages.payslips.title_one'))

@section('content')
@php
    $earnings = $payslip->lines->where('line_type', \App\Enums\ComponentClass::Earning->value);
    $deductions = $payslip->lines->where('line_type', \App\Enums\ComponentClass::Deduction->value)->filter(fn ($l) => (float) $l->amount > 0);
    $employer = $payslip->lines->where('line_type', \App\Enums\ComponentClass::Employer->value);
    $employee = $payslip->employee;
    $run = $payslip->run;
@endphp

<div class="no-print flex flex-wrap items-center justify-between gap-3">
    <a href="{{ route('payslips.index') }}" class="text-sm text-teal-700 hover:underline">{{ __('messages.common.back_to', ['page' => __('messages.pages.payslips.title')]) }}</a>
    <button type="button" onclick="window.print()" class="rounded-md border border-stone-300 bg-white px-3 py-1.5 text-sm hover:bg-stone-50">
        {{ __('messages.pages.payslips.print') }}
    </button>
</div>

<div class="print-sheet mx-auto mt-4 max-w-3xl overflow-hidden rounded-xl border border-stone-200 bg-white shadow-sm">
    <div class="border-b border-stone-100 bg-stone-50 px-6 py-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="font-display text-lg font-semibold">{{ $companyName }}</p>
                <p class="text-xs text-stone-500">{{ __('messages.pages.payslips.company_line', ['state' => $companyState]) }}</p>
            </div>
            <div class="text-right text-sm">
                <p class="font-medium">{{ $run?->periodLabel() }}</p>
                <p class="text-xs text-stone-500">{{ __('messages.pages.payslips.run_label', ['state' => $run?->state?->value]) }}</p>
            </div>
        </div>
    </div>

    <div class="grid gap-4 border-b border-stone-100 px-6 py-5 sm:grid-cols-2">
        <div>
            <p class="text-xs uppercase tracking-wide text-stone-500">{{ __('messages.common.employee') }}</p>
            <p class="mt-1 font-medium">{{ $employee?->full_name }}</p>
            <p class="text-sm text-stone-500">{{ $employee?->employee_code }} · {{ $employee?->designation }}</p>
            <p class="text-sm text-stone-500">{{ $employee?->department }}</p>
        </div>
        <div>
            <p class="text-xs uppercase tracking-wide text-stone-500">{{ __('messages.pages.payslips.payment') }}</p>
            <p class="mt-1 text-sm text-stone-700">{{ $employee?->bank_name ?: __('messages.pages.payslips.bank_fallback') }}</p>
            <p class="text-sm text-stone-500">{{ __('messages.pages.payslips.account_line', ['account' => $employee?->bank_account, 'ifsc' => $employee?->bank_ifsc]) }}</p>
            <p class="text-sm text-stone-500">{{ __('messages.pages.payslips.ids_line', ['uan' => $employee?->uan ?: '—', 'pan' => $employee?->pan ?: 'N/A']) }}</p>
        </div>
    </div>

    <div class="grid gap-6 px-6 py-5 sm:grid-cols-2">
        <div>
            <h2 class="text-xs font-semibold uppercase tracking-wide text-stone-500">{{ __('messages.pages.payslips.earnings') }}</h2>
            <ul class="mt-2 space-y-1.5 text-sm">
                @forelse ($earnings as $l)
                    <li class="flex justify-between gap-4"><span>{{ $l->label }}</span><span>₹ {{ number_format($l->amount, 2) }}</span></li>
                @empty
                    <li class="text-stone-400">{{ __('messages.pages.payslips.no_earnings') }}</li>
                @endforelse
            </ul>
            <div class="mt-3 flex justify-between border-t border-stone-100 pt-2 text-sm font-medium">
                <span>{{ __('messages.pages.payroll.gross') }}</span><span>₹ {{ number_format($payslip->gross, 2) }}</span>
            </div>
        </div>
        <div>
            <h2 class="text-xs font-semibold uppercase tracking-wide text-stone-500">{{ __('messages.pages.payroll.deductions') }}</h2>
            <ul class="mt-2 space-y-1.5 text-sm">
                @forelse ($deductions as $l)
                    <li class="flex justify-between gap-4"><span>{{ $l->label }}</span><span>₹ {{ number_format($l->amount, 2) }}</span></li>
                @empty
                    <li class="text-stone-400">{{ __('messages.pages.payslips.no_deductions') }}</li>
                @endforelse
            </ul>
            <div class="mt-3 flex justify-between border-t border-stone-100 pt-2 text-sm font-medium">
                <span>{{ __('messages.pages.payslips.total_deductions') }}</span><span>₹ {{ number_format($payslip->total_deductions, 2) }}</span>
            </div>
        </div>
    </div>

    @if($employer->isNotEmpty())
        <div class="border-t border-stone-100 px-6 py-5">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-stone-500">{{ __('messages.pages.payslips.employer_contrib') }}</h2>
            <ul class="mt-2 space-y-1.5 text-sm">
                @foreach ($employer as $l)
                    <li class="flex justify-between gap-4"><span>{{ $l->label }}</span><span>₹ {{ number_format($l->amount, 2) }}</span></li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="flex items-center justify-between border-t border-stone-200 bg-teal-50/60 px-6 py-4 text-lg font-semibold text-teal-950">
        <span>{{ __('messages.pages.payslips.net_pay') }}</span>
        <span>₹ {{ number_format($payslip->net, 2) }}</span>
    </div>
</div>
@endsection
