@extends('layouts.app')

@section('title', __('messages.pages.employees.edit', ['name' => $employee->full_name]))

@section('content')
<a href="{{ route('employees.show', $employee) }}" class="text-sm text-teal-700 hover:underline">{{ __('messages.common.back_to', ['page' => $employee->full_name]) }}</a>
<h1 class="mt-3 font-display text-2xl font-semibold">{{ __('messages.pages.employees.edit_heading') }}</h1>
<p class="text-sm text-stone-500">{{ $employee->employee_code }}</p>

<form method="POST" action="{{ route('employees.update', $employee) }}" class="mt-6 space-y-6 rounded-lg border border-stone-200 bg-white p-5">
    @csrf
    @method('PUT')
    @include('employees._form', ['employee' => $employee])
    <button class="rounded-md bg-teal-700 px-4 py-2 text-sm text-white hover:bg-teal-800">{{ __('messages.pages.employees.save_btn') }}</button>
</form>
@endsection
