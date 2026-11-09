@extends('layouts.app')

@section('title', __('messages.pages.dashboard.title'))

@section('content')
@php
    $roleTitle = match ($role) {
        'finance' => __('messages.pages.dashboard.desk_finance'),
        'auditor' => __('messages.pages.dashboard.desk_auditor'),
        'hr' => __('messages.pages.dashboard.desk_hr'),
        default => __('messages.pages.dashboard.title'),
    };
    $roleHint = match ($role) {
        'finance' => __('messages.pages.dashboard.hint_finance'),
        'auditor' => __('messages.pages.dashboard.hint_auditor'),
        'hr' => __('messages.pages.dashboard.hint_hr'),
        default => '',
    };

    $doneCount = $hrMonth ? collect($hrMonth['steps'])->where('done', true)->count() : 0;
    $stepTotal = $hrMonth ? count($hrMonth['steps']) : 0;
    $progressPct = $stepTotal > 0 ? (int) round(($doneCount / $stepTotal) * 100) : 0;
    $currentStepIndex = $hrMonth
        ? collect($hrMonth['steps'])->search(fn ($step) => ! $step['done'])
        : false;
@endphp

<section class="relative overflow-hidden rounded-2xl border border-stone-200/80 bg-gradient-to-br from-[#faf8f5] via-white to-teal-50/40 px-5 py-6 sm:px-7">
    <div class="pointer-events-none absolute -right-16 -top-16 h-40 w-40 rounded-full bg-teal-700/5"></div>
    <div class="relative flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-teal-800">{{ $roleTitle }}</p>
            <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-stone-900">{{ __('messages.pages.dashboard.hi', ['name' => auth()->user()->name]) }}</h1>
            <p class="mt-2 max-w-xl text-sm text-stone-600">{{ $roleHint }}</p>
        </div>
        <span class="rounded-full bg-stone-900 px-3 py-1 text-xs font-medium uppercase tracking-wide text-white">{{ $role }}</span>
    </div>
</section>

<div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
    @foreach($stats as $stat)
        <div class="rounded-xl border border-stone-200 bg-white p-4 shadow-[0_1px_0_rgba(28,25,23,0.04)]">
            <p class="text-xs uppercase tracking-wide text-stone-500">{{ $stat['label'] }}</p>
            <p class="mt-2 font-display text-3xl font-semibold {{ (int) $stat['value'] > 0 && $role === 'hr' ? 'text-teal-900' : 'text-stone-900' }}">
                {{ $stat['value'] }}
            </p>
            @if(!empty($stat['hint']))
                <p class="mt-1 text-xs text-stone-600">{{ $stat['hint'] }}</p>
            @endif
        </div>
    @endforeach
</div>

@if($role === 'hr' && $hrMonth)
    <div class="mt-8 overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-[0_1px_0_rgba(28,25,23,0.04)]">
        <div class="border-b border-stone-100 bg-[#faf8f5] px-5 py-5 sm:px-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-teal-800">{{ __('messages.pages.dashboard.checklist') }}</p>
                    <h2 class="mt-1 font-display text-2xl font-semibold text-stone-900">{{ $hrMonth['label'] }}</h2>
                    <p class="mt-1 text-sm text-stone-600">{{ __('messages.pages.dashboard.checklist_hint', ['done' => $doneCount, 'total' => $stepTotal]) }}</p>
                </div>
                <a href="{{ $hrMonth['nextHref'] }}"
                   class="inline-flex items-center rounded-lg bg-teal-800 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-teal-900">
                    {{ $hrMonth['nextAction'] }}
                    <span class="ml-2">→</span>
                </a>
            </div>

            <div class="mt-5">
                <div class="mb-2 flex items-center justify-between text-xs text-stone-500">
                    <span>{{ __('messages.pages.dashboard.progress') }}</span>
                    <span class="font-medium text-stone-700">{{ $progressPct }}%</span>
                </div>
                <div class="h-2 overflow-hidden rounded-full bg-stone-200/80">
                    <div class="h-full rounded-full bg-teal-700 transition-all" style="width: {{ $progressPct }}%"></div>
                </div>
            </div>

            <div class="mt-5 hidden gap-2 md:grid md:grid-cols-5">
                @foreach($hrMonth['steps'] as $i => $step)
                    @php
                        $isCurrent = $currentStepIndex === $i;
                        $short = match ($i) {
                            0 => __('messages.pages.dashboard.step_attendance'),
                            1 => __('messages.pages.dashboard.step_run'),
                            2 => __('messages.pages.dashboard.step_calculate'),
                            3 => __('messages.pages.dashboard.step_validate'),
                            default => __('messages.pages.dashboard.step_submit'),
                        };
                    @endphp
                    <div class="rounded-lg px-2 py-2 text-center text-xs font-medium
                        {{ $step['done'] ? 'bg-teal-800 text-white' : ($isCurrent ? 'bg-white text-teal-900 ring-2 ring-teal-700' : 'bg-stone-200/70 text-stone-500') }}">
                        {{ $short }}
                    </div>
                @endforeach
            </div>
        </div>

        <ol class="divide-y divide-stone-100">
            @foreach($hrMonth['steps'] as $i => $step)
                @php $isCurrent = $currentStepIndex === $i; @endphp
                <li class="flex flex-wrap items-start justify-between gap-3 px-5 py-4 sm:px-6 {{ $isCurrent ? 'bg-teal-50/50' : '' }}">
                    <div class="flex gap-3">
                        <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold
                            {{ $step['done'] ? 'bg-teal-700 text-white' : ($isCurrent ? 'bg-teal-800 text-white' : 'bg-stone-200 text-stone-600') }}">
                            {{ $step['done'] ? '✓' : ($i + 1) }}
                        </span>
                        <div>
                            <p class="font-medium {{ $step['done'] ? 'text-stone-500' : 'text-stone-900' }}">
                                {{ preg_replace('/^\d+\.\s*/', '', $step['label']) }}
                                @if($isCurrent)
                                    <span class="ml-2 rounded-full bg-teal-800 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-white">{{ __('messages.pages.dashboard.now') }}</span>
                                @endif
                            </p>
                            <p class="mt-0.5 text-sm text-stone-500">{{ $step['detail'] }}</p>
                        </div>
                    </div>
                    <a href="{{ $step['href'] }}"
                       class="rounded-md px-3 py-1.5 text-sm {{ $isCurrent ? 'bg-teal-800 text-white hover:bg-teal-900' : 'text-teal-700 hover:bg-stone-50' }}">
                        {{ $isCurrent ? __('messages.common.continue') : __('messages.common.open') }}
                    </a>
                </li>
            @endforeach
        </ol>
    </div>
@endif

@if($role !== 'hr' && !empty($quickLinks))
    <div class="mt-6">
        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-stone-600">Shortcuts</p>
        <div class="flex flex-wrap gap-2">
            @foreach($quickLinks as $link)
                <a href="{{ $link['href'] }}"
                   class="rounded-full border border-stone-200 bg-white px-3.5 py-1.5 text-sm text-stone-700 shadow-sm hover:border-teal-700/30 hover:text-teal-900">
                    {{ $link['label'] }}
                </a>
            @endforeach
        </div>
    </div>
@endif

@if($role !== 'hr' && count($queues) > 0)
<div class="mt-8 overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-[0_1px_0_rgba(28,25,23,0.04)]">
    <div class="border-b border-stone-100 px-5 py-4 sm:px-6">
        <h2 class="font-display text-base font-semibold">
            @if($role === 'finance')
                {{ __('messages.pages.dashboard.queue_finance') }}
            @else
                {{ __('messages.pages.dashboard.queue_auditor') }}
            @endif
        </h2>
        <p class="text-sm text-stone-500">{{ __('messages.pages.dashboard.queue_hint') }}</p>
    </div>
    <ul class="divide-y divide-stone-100">
        @foreach($queues as $item)
            <li>
                <a href="{{ $item['href'] }}" class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 hover:bg-stone-50 sm:px-6">
                    <div>
                        <p class="font-medium text-stone-900">{{ $item['title'] }}</p>
                        <p class="text-sm text-stone-500">{{ $item['subtitle'] }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        @if(!empty($item['meta']))
                            <span class="rounded-full bg-stone-100 px-2.5 py-0.5 text-xs text-stone-600">{{ $item['meta'] }}</span>
                        @endif
                        <span class="text-sm font-medium text-teal-700">Open →</span>
                    </div>
                </a>
            </li>
        @endforeach
    </ul>
</div>
@endif
@endsection
