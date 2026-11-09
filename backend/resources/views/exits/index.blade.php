@extends('layouts.app')

@section('title', __('messages.pages.exits.title'))

@section('content')
<h1 class="font-display text-2xl font-semibold">{{ __('messages.pages.exits.title') }}</h1>
<p class="text-sm text-stone-500">{{ __('messages.pages.exits.subtitle') }}</p>

@if(auth()->user()->hasRole('hr'))
<form method="POST" action="{{ route('exits.store') }}" class="mt-6 grid gap-3 rounded-lg border border-stone-200 bg-white p-5 sm:grid-cols-3">
    @csrf
    <div>
        <label class="mb-1 block text-xs text-stone-500">{{ __('messages.common.employee') }}</label>
        <select name="employee_id" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm">
            @foreach($employees as $e)
                <option value="{{ $e->id }}">{{ $e->employee_code }} {{ $e->first_name }}</option>
            @endforeach
        </select>
    </div>
    <input type="hidden" name="category" value="voluntary_resignation">
    <div>
        <label class="mb-1 block text-xs text-stone-500">{{ __('messages.pages.exits.last_working_day') }}</label>
        <input type="date" name="last_working_date" value="{{ old('last_working_date', now()->toDateString()) }}" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm">
    </div>
    <div>
        <label class="mb-1 block text-xs text-stone-500">{{ __('messages.pages.exits.resignation_date') }}</label>
        <input type="date" name="resignation_date" value="{{ old('resignation_date', now()->toDateString()) }}" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm">
    </div>
    <div>
        <label class="mb-1 block text-xs text-stone-500">{{ __('messages.common.reason') }}</label>
        <input type="text" name="reason" value="{{ old('reason') }}" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm">
    </div>
    <input type="hidden" name="notice_days" value="30">
    <input type="hidden" name="notice_shortfall_days" value="0">
    <div class="flex items-end"><button class="rounded-md bg-teal-700 px-4 py-2 text-sm text-white">{{ __('messages.pages.exits.record') }}</button></div>
</form>
@endif

<div class="mt-6 space-y-3">
    @forelse($exits as $x)
        <div class="rounded-lg border border-stone-200 bg-white p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="font-medium">{{ $x->employee?->employee_code }} — {{ $x->category }}</p>
                    <p class="text-sm text-stone-500">LWD {{ $x->last_working_date?->toDateString() }} · {{ $x->status }}</p>
                    @if($x->settlements->isNotEmpty())
                        <p class="mt-1 text-sm">Step-1 net: ₹ {{ number_format($x->settlements->first()->net_payable, 2) }}
                            (due {{ $x->settlements->first()->due_by?->toDateString() }})</p>
                    @endif
                </div>
                @if(auth()->user()->hasRole('hr') && $x->settlements->isEmpty())
                    <form method="POST" action="{{ route('exits.settlement', $x) }}">
                        @csrf
                        <button class="rounded-md bg-teal-700 px-3 py-1.5 text-sm text-white">{{ __('messages.pages.exits.generate_step1') }}</button>
                    </form>
                @endif
            </div>
        </div>
    @empty
        <div class="rounded-lg border border-dashed border-stone-300 bg-white px-4 py-10 text-center">
            <p class="font-medium text-stone-800">{{ __('messages.pages.exits.empty') }}</p>
            <p class="mt-1 text-sm text-stone-500">{{ __('messages.pages.exits.empty_hint') }}</p>
        </div>
    @endforelse
</div>
<div class="mt-4">{{ $exits->links() }}</div>
@endsection
