@extends('layouts.app')

@section('title', __('messages.pages.employees.new'))

@section('content')
<a href="{{ route('employees.index') }}" class="text-sm text-teal-700 hover:underline">{{ __('messages.common.back_to', ['page' => __('messages.pages.employees.title')]) }}</a>
<h1 class="mt-3 font-display text-2xl font-semibold">{{ __('messages.pages.employees.add') }}</h1>
<p class="text-sm text-stone-500">{{ __('messages.pages.employees.add_subtitle') }}</p>

<form method="POST" action="{{ route('employees.store') }}" class="mt-6 space-y-6 rounded-lg border border-stone-200 bg-white p-5">
    @csrf
    @include('employees._form')

    <div>
        <h2 class="font-medium">{{ __('messages.pages.employees.starting_salary') }}</h2>
        <p class="mt-1 text-xs text-stone-500">{{ __('messages.pages.employees.starting_salary_hint') }}</p>
        <div class="mt-3 grid gap-4 sm:grid-cols-4">
            <div>
                <label class="mb-1 block text-xs text-stone-500">{{ __('messages.pages.employees.basic') }}</label>
                <input type="number" step="0.01" name="basic" value="{{ old('basic') }}" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs text-stone-500">{{ __('messages.pages.employees.hra') }}</label>
                <input type="number" step="0.01" name="hra" value="{{ old('hra') }}" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs text-stone-500">{{ __('messages.pages.employees.special') }}</label>
                <input type="number" step="0.01" name="special" value="{{ old('special') }}" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs text-stone-500">{{ __('messages.pages.employees.effective_from') }}</label>
                <input type="date" name="salary_effective_from" value="{{ old('salary_effective_from', now()->toDateString()) }}" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm">
            </div>
        </div>
    </div>

    <button class="rounded-md bg-teal-700 px-4 py-2 text-sm text-white hover:bg-teal-800">{{ __('messages.pages.employees.create_btn') }}</button>
</form>
@endsection
