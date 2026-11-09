@extends('layouts.app')

@section('title', __('messages.pages.revisions.title'))

@section('content')
<h1 class="font-display text-2xl font-semibold">{{ __('messages.pages.revisions.title') }}</h1>
<p class="text-sm text-stone-500">{{ __('messages.pages.revisions.subtitle') }}</p>

@if(auth()->user()->hasRole('hr'))
<form method="POST" action="{{ route('revisions.store') }}" class="mt-6 flex flex-wrap items-end gap-3 rounded-lg border border-stone-200 bg-white p-5">
    @csrf
    <div>
        <label class="mb-1 block text-xs text-stone-500">{{ __('messages.common.employee') }}</label>
        <select name="employee_id" class="rounded-md border border-stone-300 px-3 py-2 text-sm">
            @foreach($employees as $e)
                <option value="{{ $e->id }}">{{ $e->employee_code }} {{ $e->first_name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="mb-1 block text-xs text-stone-500">{{ __('messages.common.effective') }}</label>
        <input type="date" name="effective_from" value="{{ old('effective_from', now()->toDateString()) }}" class="rounded-md border border-stone-300 px-3 py-2 text-sm">
    </div>
    <div>
        <label class="mb-1 block text-xs text-stone-500">{{ __('messages.common.reason') }}</label>
        <input type="text" name="reason" value="{{ old('reason') }}" class="rounded-md border border-stone-300 px-3 py-2 text-sm">
    </div>
    <button class="rounded-md bg-teal-700 px-4 py-2 text-sm text-white">{{ __('messages.common.submit') }}</button>
</form>
@endif

<div class="mt-6 overflow-hidden rounded-lg border border-stone-200 bg-white">
    <table class="w-full text-sm">
        <thead class="bg-stone-50 text-xs uppercase text-stone-500">
            <tr>
                <th class="px-4 py-3 text-left">{{ __('messages.common.employee') }}</th>
                <th class="px-4 py-3 text-left">{{ __('messages.common.effective') }}</th>
                <th class="px-4 py-3 text-left">{{ __('messages.common.status') }}</th>
                <th class="px-4 py-3 text-left">{{ __('messages.pages.revisions.initiated_by') }}</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($revisions as $r)
                <tr class="border-t border-stone-100">
                    <td class="px-4 py-3">{{ $r->employee?->employee_code }}</td>
                    <td class="px-4 py-3">{{ $r->effective_from?->toDateString() }}</td>
                    <td class="px-4 py-3">{{ $r->status }}</td>
                    <td class="px-4 py-3">{{ $r->initiator?->name }}</td>
                    <td class="px-4 py-3 text-right">
                        @if(auth()->user()->hasRole('finance') && $r->status === 'pending')
                            <form method="POST" action="{{ route('revisions.approve', $r) }}" class="inline">
                                @csrf
                                <button class="text-teal-700 hover:underline">{{ __('messages.common.approve') }}</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-10 text-center">
                        <p class="font-medium text-stone-800">{{ __('messages.pages.revisions.empty') }}</p>
                        <p class="mt-1 text-sm text-stone-500">{{ __('messages.pages.revisions.empty_hint') }}</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $revisions->links() }}</div>
@endsection
