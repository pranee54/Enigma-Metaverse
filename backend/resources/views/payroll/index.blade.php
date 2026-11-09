@extends('layouts.app')

@section('title', __('messages.pages.payroll.title'))

@section('content')
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="font-display text-2xl font-semibold">{{ __('messages.pages.payroll.runs_title') }}</h1>
        <p class="text-sm text-stone-500">{{ __('messages.pages.payroll.subtitle') }}</p>
    </div>
    @if(auth()->user()->hasRole('hr'))
    <form method="POST" action="{{ route('payroll.store') }}" class="flex items-end gap-2">
        @csrf
        <div>
            <label class="mb-1 block text-xs text-stone-500">{{ __('messages.common.year') }}</label>
            <input type="number" name="period_year" value="{{ now()->year }}" class="w-28 rounded-md border border-stone-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-xs text-stone-500">{{ __('messages.common.month') }}</label>
            <input type="number" name="period_month" value="{{ now()->month }}" class="w-24 rounded-md border border-stone-300 px-3 py-2 text-sm">
        </div>
        <button class="rounded-md bg-teal-700 px-4 py-2 text-sm text-white hover:bg-teal-800">{{ __('messages.pages.payroll.new_run') }}</button>
    </form>
    @endif
</div>

<div class="mt-6 overflow-hidden rounded-lg border border-stone-200 bg-white">
    <table class="w-full text-sm">
        <thead class="bg-stone-50 text-xs uppercase text-stone-500">
            <tr>
                <th class="px-4 py-3 text-left">{{ __('messages.common.period') }}</th>
                <th class="px-4 py-3 text-left">{{ __('messages.common.status') }}</th>
                <th class="px-4 py-3 text-left">{{ __('messages.pages.payroll.prepared_by') }}</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($runs as $r)
                <tr class="border-t border-stone-100">
                    <td class="px-4 py-3">{{ $r->periodLabel() }}</td>
                    <td class="px-4 py-3"><span class="rounded bg-stone-100 px-2 py-0.5 text-xs">{{ $r->state->value }}</span></td>
                    <td class="px-4 py-3">{{ $r->preparer?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('payroll.show', $r) }}" class="text-teal-700 hover:underline">{{ __('messages.common.open') }}</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-4 py-10 text-center">
                        <p class="font-medium text-stone-800">{{ __('messages.pages.payroll.empty') }}</p>
                        <p class="mt-1 text-sm text-stone-500">{{ __('messages.pages.payroll.empty_hint') }}</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $runs->links() }}</div>
@endsection
