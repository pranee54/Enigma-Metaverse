@extends('layouts.app')

@section('title', __('messages.pages.loans.title'))

@section('content')
<h1 class="font-display text-2xl font-semibold">{{ __('messages.pages.loans.title') }}</h1>
<p class="text-sm text-stone-500">{{ __('messages.pages.loans.subtitle') }}</p>

@if(auth()->user()->hasAnyRole(['hr','finance']))
<form method="POST" action="{{ route('loans.store') }}" class="mt-6 grid gap-3 rounded-lg border border-stone-200 bg-white p-5 sm:grid-cols-3">
    @csrf
    <div>
        <label class="mb-1 block text-xs text-stone-500">{{ __('messages.common.employee') }}</label>
        <select name="employee_id" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm">
            @foreach($employees as $e)
                <option value="{{ $e->id }}">{{ $e->employee_code }} {{ $e->first_name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="mb-1 block text-xs text-stone-500">{{ __('messages.common.type') }}</label>
        <select name="type" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm">
            <option value="advance" @selected(old('type') === 'advance')>{{ __('messages.pages.loans.advance') }}</option>
            <option value="loan" @selected(old('type', 'loan') === 'loan')>{{ __('messages.pages.loans.loan') }}</option>
        </select>
    </div>
    <div>
        <label class="mb-1 block text-xs text-stone-500">{{ __('messages.pages.loans.principal') }}</label>
        <input type="number" name="principal" value="{{ old('principal') }}" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm" required>
    </div>
    <div>
        <label class="mb-1 block text-xs text-stone-500">{{ __('messages.pages.loans.monthly_recovery') }}</label>
        <input type="number" name="monthly_recovery" value="{{ old('monthly_recovery') }}" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm" required>
    </div>
    <div>
        <label class="mb-1 block text-xs text-stone-500">{{ __('messages.pages.loans.tenure') }}</label>
        <input type="number" name="tenure_months" value="{{ old('tenure_months') }}" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm" required>
    </div>
    <div>
        <label class="mb-1 block text-xs text-stone-500">{{ __('messages.pages.loans.interest') }}</label>
        <input type="number" step="0.01" name="interest_rate" value="{{ old('interest_rate', 0) }}" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm" required>
    </div>
    <div>
        <label class="mb-1 block text-xs text-stone-500">{{ __('messages.common.start') }}</label>
        <input type="date" name="started_on" value="{{ old('started_on', now()->toDateString()) }}" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm" required>
    </div>
    <div class="flex items-end"><button class="rounded-md bg-teal-700 px-4 py-2 text-sm text-white">{{ __('messages.common.create') }}</button></div>
</form>
@endif

<div class="mt-6 overflow-hidden rounded-lg border border-stone-200 bg-white">
    <table class="w-full text-sm">
        <thead class="bg-stone-50 text-xs uppercase text-stone-500">
            <tr>
                <th class="px-4 py-3 text-left">{{ __('messages.common.employee') }}</th>
                <th class="px-4 py-3 text-left">{{ __('messages.common.type') }}</th>
                <th class="px-4 py-3 text-right">{{ __('messages.pages.loans.principal') }}</th>
                <th class="px-4 py-3 text-right">{{ __('messages.pages.loans.outstanding') }}</th>
                <th class="px-4 py-3 text-left">{{ __('messages.pages.loans.schedule') }}</th>
                <th class="px-4 py-3 text-left">{{ __('messages.common.status') }}</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($loans as $l)
                <tr class="border-t border-stone-100 align-top">
                    <td class="px-4 py-3">{{ $l->employee?->employee_code }}</td>
                    <td class="px-4 py-3">{{ $l->type }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($l->principal, 2) }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($l->outstanding, 2) }}</td>
                    <td class="px-4 py-3 text-xs text-stone-600">
                        @forelse(($l->schedule ?? []) as $row)
                            <div>{{ __('messages.pages.loans.instalment', ['n' => $row['instalment']]) }} · ₹ {{ number_format($row['amount'], 2) }}</div>
                        @empty
                            —
                        @endforelse
                    </td>
                    <td class="px-4 py-3">{{ $l->status }}</td>
                    <td class="px-4 py-3 text-right">
                        @if(auth()->user()->hasRole('finance') && $l->status === 'pending')
                            <form method="POST" action="{{ route('loans.approve', $l) }}" class="inline">
                                @csrf
                                <button class="text-teal-700 hover:underline">{{ __('messages.common.approve') }}</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-10 text-center">
                        <p class="font-medium text-stone-800">{{ __('messages.pages.loans.empty') }}</p>
                        <p class="mt-1 text-sm text-stone-500">{{ __('messages.pages.loans.empty_hint') }}</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $loans->links() }}</div>
@endsection
