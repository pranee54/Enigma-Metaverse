@extends('layouts.app')

@section('title', __('messages.pages.employees.title'))

@section('content')
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="font-display text-2xl font-semibold">{{ __('messages.pages.employees.title') }}</h1>
        <p class="text-sm text-stone-500">{{ __('messages.pages.employees.subtitle') }}</p>
    </div>
    @if(auth()->user()->hasRole('hr'))
        <a href="{{ route('employees.create') }}" class="rounded-md bg-teal-700 px-4 py-2 text-sm text-white hover:bg-teal-800">{{ __('messages.pages.employees.add') }}</a>
    @endif
</div>

@if($departmentCounts->isNotEmpty())
    <div class="mt-5 flex flex-wrap gap-2">
        <a href="{{ route('employees.index', array_filter(['q' => $filters['q'] ?: null, 'status' => $filters['status'] ?: null])) }}"
           class="rounded-full px-3 py-1.5 text-xs font-medium {{ $filters['department'] === '' ? 'bg-teal-800 text-white' : 'bg-white text-stone-600 ring-1 ring-stone-200 hover:bg-stone-50' }}">
            {{ __('messages.common.all') }} · {{ $departmentCounts->sum() }}
        </a>
        @foreach($departmentCounts as $dept => $total)
            <a href="{{ route('employees.index', array_filter(['department' => $dept, 'q' => $filters['q'] ?: null, 'status' => $filters['status'] ?: null])) }}"
               class="rounded-full px-3 py-1.5 text-xs font-medium {{ $filters['department'] === $dept ? 'bg-teal-800 text-white' : 'bg-white text-stone-600 ring-1 ring-stone-200 hover:bg-stone-50' }}">
                {{ $dept }} · {{ $total }}
            </a>
        @endforeach
    </div>
@endif

<form method="GET" action="{{ route('employees.index') }}" class="mt-4 flex flex-wrap items-end gap-3 rounded-lg border border-stone-200 bg-white p-4">
    <div class="min-w-48 flex-1">
        <label class="mb-1 block text-xs text-stone-500">{{ __('messages.common.search') }}</label>
        <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="{{ __('messages.pages.employees.search_placeholder') }}"
               class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm">
    </div>
    <div>
        <label class="mb-1 block text-xs text-stone-500">{{ __('messages.common.department') }}</label>
        <select name="department" class="min-w-40 rounded-md border border-stone-300 px-3 py-2 text-sm">
            <option value="">{{ __('messages.pages.employees.all_departments') }}</option>
            @foreach($departmentCounts as $dept => $total)
                <option value="{{ $dept }}" @selected($filters['department'] === $dept)>{{ $dept }} ({{ $total }})</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="mb-1 block text-xs text-stone-500">{{ __('messages.common.status') }}</label>
        <select name="status" class="rounded-md border border-stone-300 px-3 py-2 text-sm">
            <option value="">{{ __('messages.common.all') }}</option>
            @foreach($statuses as $status)
                <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>{{ $status->value }}</option>
            @endforeach
        </select>
    </div>
    <button class="rounded-md bg-stone-800 px-4 py-2 text-sm text-white">{{ __('messages.common.filter') }}</button>
    @if($filters['q'] !== '' || $filters['department'] !== '' || $filters['status'] !== '')
        <a href="{{ route('employees.index') }}" class="rounded-md border border-stone-300 px-4 py-2 text-sm hover:bg-stone-50">{{ __('messages.common.clear') }}</a>
    @endif
</form>

@if($filters['department'] !== '')
    <p class="mt-4 text-sm text-stone-600">{{ __('messages.pages.employees.showing_department', ['department' => $filters['department']]) }}</p>
@endif

<div class="mt-3 overflow-hidden rounded-lg border border-stone-200 bg-white">
    <table class="w-full text-left text-sm">
        <thead class="border-b border-stone-200 bg-stone-50 text-xs uppercase text-stone-500">
            <tr>
                <th class="px-4 py-3">{{ __('messages.common.code') }}</th>
                <th class="px-4 py-3">{{ __('messages.common.name') }}</th>
                <th class="px-4 py-3">{{ __('messages.common.department') }}</th>
                <th class="px-4 py-3">{{ __('messages.common.status') }}</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($employees as $emp)
                <tr class="border-b border-stone-100">
                    <td class="px-4 py-3 font-medium">{{ $emp->employee_code }}</td>
                    <td class="px-4 py-3">{{ $emp->full_name }}</td>
                    <td class="px-4 py-3">{{ $emp->department }}</td>
                    <td class="px-4 py-3"><span class="rounded bg-stone-100 px-2 py-0.5 text-xs">{{ $emp->status->value }}</span></td>
                    <td class="px-4 py-3 text-right space-x-3">
                        <a href="{{ route('employees.show', $emp) }}" class="text-teal-700 hover:underline">{{ __('messages.common.open') }}</a>
                        @if(auth()->user()->hasRole('hr'))
                            <a href="{{ route('employees.edit', $emp) }}" class="text-stone-600 hover:underline">{{ __('messages.common.edit') }}</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-10 text-center">
                        <p class="font-medium text-stone-800">{{ __('messages.pages.employees.empty') }}</p>
                        <p class="mt-1 text-sm text-stone-500">{{ __('messages.pages.employees.empty_hint') }}</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $employees->links() }}</div>
@endsection
