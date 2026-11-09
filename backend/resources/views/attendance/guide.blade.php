@extends('layouts.app')

@section('title', __('messages.pages.attendance.guide_title'))

@section('content')
@php
    $monthLabel = \Carbon\Carbon::create($periodYear, $periodMonth, 1)->format('M Y');
@endphp

<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <a href="{{ route('attendance.index') }}" class="text-sm text-stone-500 hover:text-stone-700">{{ __('messages.common.back_to', ['page' => __('messages.pages.attendance.title')]) }}</a>
        <h1 class="mt-2 font-display text-2xl font-semibold">{{ __('messages.pages.attendance.guide_title') }}</h1>
        <p class="text-sm text-stone-500">{{ __('messages.pages.attendance.guide_subtitle', ['month' => $monthLabel]) }}</p>
    </div>
    <a href="{{ route('attendance.template', ['period_year' => $periodYear, 'period_month' => $periodMonth]) }}"
       class="rounded-md bg-teal-700 px-4 py-2 text-sm text-white hover:bg-teal-800">
        {{ __('messages.pages.attendance.download_demo') }}
    </a>
</div>

<div class="mt-6 grid gap-3 sm:grid-cols-3">
    <div class="rounded-xl border border-stone-200 bg-white p-4">
        <p class="text-xs font-semibold uppercase tracking-wide text-teal-800">{{ __('messages.pages.attendance.guide_download') }}</p>
        <p class="mt-1 text-sm text-stone-600">{{ __('messages.pages.attendance.guide_download_body') }}</p>
    </div>
    <div class="rounded-xl border border-stone-200 bg-white p-4">
        <p class="text-xs font-semibold uppercase tracking-wide text-teal-800">{{ __('messages.pages.attendance.guide_edit') }}</p>
        <p class="mt-1 text-sm text-stone-600">{{ __('messages.pages.attendance.guide_edit_body') }}</p>
    </div>
    <div class="rounded-xl border border-stone-200 bg-white p-4">
        <p class="text-xs font-semibold uppercase tracking-wide text-teal-800">{{ __('messages.pages.attendance.guide_import') }}</p>
        <p class="mt-1 text-sm text-stone-600">{{ __('messages.pages.attendance.guide_import_body') }}</p>
    </div>
</div>

<div class="mt-6 rounded-xl border border-stone-200 bg-white p-5">
    <h2 class="font-display text-base font-semibold">{{ __('messages.pages.attendance.rules_title') }}</h2>
    <ul class="mt-3 space-y-2 text-sm text-stone-600">
        <li>{{ __('messages.pages.attendance.rule_days', ['days' => $daysInPeriod]) }}</li>
        <li>{{ __('messages.pages.attendance.rule_ot') }}</li>
        <li>{{ __('messages.pages.attendance.rule_period') }}</li>
        <li>{{ __('messages.pages.attendance.rule_code') }}</li>
    </ul>
</div>

<div class="mt-6 overflow-hidden rounded-xl border border-stone-200 bg-white">
    <div class="border-b border-stone-100 px-5 py-4">
        <h2 class="font-display text-base font-semibold">{{ __('messages.pages.attendance.demo_rows', ['month' => $monthLabel, 'days' => $daysInPeriod]) }}</h2>
        <p class="mt-1 text-sm text-stone-500">{{ __('messages.pages.attendance.demo_rows_hint') }}</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[720px] text-left text-sm">
            <thead class="bg-stone-50 text-xs uppercase tracking-wide text-stone-500">
                <tr>
                    <th class="px-4 py-3">{{ __('messages.common.code') }}</th>
                    <th class="px-4 py-3">{{ __('messages.common.year') }}</th>
                    <th class="px-4 py-3">{{ __('messages.common.month') }}</th>
                    <th class="px-4 py-3">{{ __('messages.common.payable') }}</th>
                    <th class="px-4 py-3">{{ __('messages.pages.attendance.lop') }}</th>
                    <th class="px-4 py-3">{{ __('messages.pages.attendance.ot_hrs') }}</th>
                    <th class="px-4 py-3">{{ __('messages.pages.attendance.ot_consent') }}</th>
                    <th class="px-4 py-3">{{ __('messages.common.remarks') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($demoRows as $row)
                    <tr class="border-t border-stone-100">
                        <td class="px-4 py-3 font-medium">{{ $row['employee_code'] }}</td>
                        <td class="px-4 py-3">{{ $row['period_year'] }}</td>
                        <td class="px-4 py-3">{{ $row['period_month'] }}</td>
                        <td class="px-4 py-3">{{ $row['payable_days'] }}</td>
                        <td class="px-4 py-3">{{ $row['lop_days'] }}</td>
                        <td class="px-4 py-3">{{ $row['overtime_hours'] }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs {{ $row['overtime_consent'] === 'yes' ? 'bg-teal-50 text-teal-800' : 'bg-stone-100 text-stone-600' }}">
                                {{ $row['overtime_consent'] }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-stone-500">{{ $row['remarks'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="border-t border-stone-100 bg-[#faf8f5] px-5 py-3 text-xs text-stone-500">
        {{ __('messages.pages.attendance.csv_columns') }}
    </div>
</div>

<div class="mt-6 flex flex-wrap gap-3">
    <a href="{{ route('attendance.template', ['period_year' => $periodYear, 'period_month' => $periodMonth]) }}"
       class="rounded-md bg-teal-700 px-4 py-2 text-sm text-white hover:bg-teal-800">{{ __('messages.pages.attendance.download_demo') }}</a>
    <a href="{{ route('attendance.index') }}" class="rounded-md border border-stone-300 px-4 py-2 text-sm hover:bg-stone-50">{{ __('messages.pages.attendance.back_import') }}</a>
</div>
@endsection
