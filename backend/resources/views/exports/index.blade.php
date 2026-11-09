@extends('layouts.app')

@section('title', __('messages.pages.exports.title'))

@section('content')
<h1 class="font-display text-2xl font-semibold">{{ __('messages.pages.exports.title') }}</h1>
<p class="text-sm text-stone-500">{{ __('messages.pages.exports.subtitle') }}</p>

<form method="POST" class="mt-6 flex flex-wrap items-end gap-3 rounded-lg border border-stone-200 bg-white p-5">
    @csrf
    <div>
        <label class="mb-1 block text-xs text-stone-500">{{ __('messages.pages.exports.payroll_run') }}</label>
        <select name="payroll_run_id" class="min-w-56 rounded-md border border-stone-300 px-3 py-2 text-sm" required>
            @forelse($runs as $r)
                <option value="{{ $r->id }}">{{ $r->periodLabel() }} ({{ $r->state->value }})</option>
            @empty
                <option value="" disabled selected>{{ __('messages.pages.exports.no_runs') }}</option>
            @endforelse
        </select>
    </div>
    <button formaction="{{ route('exports.journal') }}" class="rounded-md bg-teal-700 px-4 py-2 text-sm text-white" @disabled($runs->isEmpty())>{{ __('messages.pages.exports.journal') }}</button>
    <button formaction="{{ route('exports.bank') }}" class="rounded-md border border-stone-300 px-4 py-2 text-sm" @disabled($runs->isEmpty())>{{ __('messages.pages.exports.bank') }}</button>
</form>

<div class="mt-6 overflow-hidden rounded-lg border border-stone-200 bg-white">
    <ul class="divide-y divide-stone-100 text-sm">
        @forelse($exports as $e)
            <li class="flex justify-between px-4 py-3">
                <span>{{ $e->type }} · {{ $e->idempotency_key }}</span>
                <a href="{{ route('exports.download', $e) }}" class="text-teal-700 hover:underline">{{ __('messages.common.download') }}</a>
            </li>
        @empty
            <li class="px-4 py-10 text-center">
                <p class="font-medium text-stone-800">{{ __('messages.pages.exports.empty') }}</p>
                <p class="mt-1 text-sm text-stone-500">{{ __('messages.pages.exports.empty_hint') }}</p>
            </li>
        @endforelse
    </ul>
</div>
<div class="mt-4">{{ $exports->links() }}</div>
@endsection
