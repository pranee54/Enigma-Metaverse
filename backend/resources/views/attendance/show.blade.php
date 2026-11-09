@extends('layouts.app')

@section('title', __('messages.pages.attendance.batch_title'))

@section('content')
@php
    $approved = $batch->status === 'approved';
    $period = $batch->period_year.'-'.str_pad((string) $batch->period_month, 2, '0', STR_PAD_LEFT);
@endphp

<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <a href="{{ route('attendance.index') }}" class="text-sm text-stone-500 hover:text-stone-700">{{ __('messages.common.back_to', ['page' => __('messages.pages.attendance.title')]) }}</a>
        <h1 class="mt-2 font-display text-2xl font-semibold">{{ $batch->batch_code }}</h1>
        <p class="text-sm text-stone-500">
            {{ __('messages.pages.attendance.batch_meta', [
                'period' => $period,
                'accepted' => $batch->accepted_count,
                'rejected' => $batch->rejected_count,
                'by' => $batch->importer?->name ?? '—',
            ]) }}
        </p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $approved ? 'bg-teal-50 text-teal-800' : 'bg-amber-50 text-amber-800' }}">
            {{ $batch->status }}
        </span>
        @if(auth()->user()->hasRole('hr') && ! $approved)
            <form method="POST" action="{{ route('attendance.approve', $batch) }}">
                @csrf
                <button class="rounded-md bg-teal-700 px-4 py-2 text-sm text-white hover:bg-teal-800">{{ __('messages.pages.attendance.approve_batch') }}</button>
            </form>
        @endif
        @if(auth()->user()->hasRole('hr'))
            <form method="POST" action="{{ route('attendance.destroy', $batch) }}"
                  onsubmit="return confirm(@js(__('messages.pages.attendance.remove_confirm')))">
                @csrf
                @method('DELETE')
                <button class="rounded-md border border-red-200 px-4 py-2 text-sm text-red-700 hover:bg-red-50">{{ __('messages.common.remove') }}</button>
            </form>
        @endif
    </div>
</div>

@if($batch->summaries->isNotEmpty())
<div class="mt-6 overflow-hidden rounded-lg border border-stone-200 bg-white">
    <div class="border-b border-stone-100 px-5 py-4">
        <h2 class="font-display text-base font-semibold">{{ __('messages.pages.attendance.accepted_rows') }}</h2>
        <p class="text-sm text-stone-500">{{ __('messages.pages.attendance.accepted_rows_hint') }}</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[720px] text-left text-sm">
            <thead class="bg-stone-50 text-xs uppercase text-stone-500">
                <tr>
                    <th class="px-4 py-3">{{ __('messages.common.code') }}</th>
                    <th class="px-4 py-3">{{ __('messages.common.name') }}</th>
                    <th class="px-4 py-3">{{ __('messages.common.payable') }}</th>
                    <th class="px-4 py-3">{{ __('messages.pages.attendance.lop') }}</th>
                    <th class="px-4 py-3">{{ __('messages.pages.attendance.ot_hrs') }}</th>
                    <th class="px-4 py-3">{{ __('messages.pages.attendance.consent') }}</th>
                    <th class="px-4 py-3">{{ __('messages.common.status') }}</th>
                    <th class="px-4 py-3">{{ __('messages.common.remarks') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($batch->summaries as $summary)
                    <tr class="border-t border-stone-100">
                        <td class="px-4 py-3 font-medium">{{ $summary->employee?->employee_code }}</td>
                        <td class="px-4 py-3">{{ $summary->employee?->full_name }}</td>
                        <td class="px-4 py-3">{{ $summary->payable_days }}</td>
                        <td class="px-4 py-3">{{ $summary->lop_days }}</td>
                        <td class="px-4 py-3">{{ $summary->overtime_hours }}</td>
                        <td class="px-4 py-3">{{ $summary->overtime_consent ? 'yes' : 'no' }}</td>
                        <td class="px-4 py-3">{{ $summary->status }}</td>
                        <td class="px-4 py-3 text-stone-500">{{ $summary->remarks ?: '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@if($rejectedRows->isNotEmpty())
<div class="mt-6 overflow-hidden rounded-lg border border-amber-200 bg-amber-50/40">
    <div class="border-b border-amber-100 px-5 py-4">
        <h2 class="font-display text-base font-semibold text-amber-950">{{ __('messages.pages.attendance.rejected_rows') }}</h2>
        <p class="text-sm text-amber-900/80">{{ __('messages.pages.attendance.rejected_rows_hint') }}</p>
    </div>
    <ul class="space-y-1 px-5 py-4 text-sm text-amber-950">
        @foreach($rejectedRows as $row)
            <li>{{ __('messages.pages.attendance.rejected_line', [
                'line' => $row['line'] ?? '?',
                'reason' => $row['reason'] ?? __('messages.pages.attendance.invalid_row'),
            ]) }}</li>
        @endforeach
    </ul>
</div>
@elseif($batch->summaries->isEmpty())
<div class="mt-6 rounded-lg border border-stone-200 bg-white px-5 py-8 text-center text-sm text-stone-500">
    {{ __('messages.pages.attendance.no_accepted') }}
</div>
@endif
@endsection
