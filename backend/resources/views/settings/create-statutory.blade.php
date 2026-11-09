@extends('layouts.app')

@section('title', __('messages.pages.settings.add_statutory'))

@section('content')
<div class="mx-auto max-w-4xl">
    <a href="{{ route('settings.index') }}" class="text-sm text-teal-700 hover:underline">{{ __('messages.common.back_to', ['page' => __('messages.pages.settings.title')]) }}</a>
    <h1 class="mt-3 font-display text-2xl font-semibold tracking-tight">{{ __('messages.pages.settings.add_statutory') }}</h1>
    <p class="mt-1 text-sm text-stone-500">{{ __('messages.pages.settings.add_subtitle') }}</p>

    <form method="POST" action="{{ route('settings.statutory') }}" class="mt-6 space-y-5 overflow-hidden rounded-xl border border-stone-200 bg-white p-5 shadow-sm">
        @csrf
        @include('settings._statutory_fields', [
            'defaults' => $defaults,
            'effectiveDefault' => now()->toDateString(),
            'notesDefault' => '',
        ])
        <div class="flex flex-wrap gap-3 border-t border-stone-100 pt-4">
            <button class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">{{ __('messages.pages.settings.save_version') }}</button>
            <a href="{{ route('settings.index') }}" class="rounded-lg border border-stone-300 px-4 py-2 text-sm hover:bg-stone-50">{{ __('messages.common.cancel') }}</a>
        </div>
    </form>
</div>
@endsection
