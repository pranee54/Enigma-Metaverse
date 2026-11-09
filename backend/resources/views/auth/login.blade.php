@extends('layouts.auth')

@section('content')
<div class="w-full max-w-md rounded-xl border border-stone-200 bg-white p-8 shadow-sm">
    <h1 class="text-2xl font-semibold tracking-tight" style="font-family:'IBM Plex Sans',sans-serif">{{ $companyName ?? 'Payroll' }}</h1>
    <p class="mt-1 text-sm text-stone-500">{{ __('messages.auth.sign_in_continue') }}</p>

    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-4" autocomplete="username">
        @csrf
        <div>
            <label class="mb-1 block text-sm font-medium">{{ __('messages.auth.email') }}</label>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus
                   class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">{{ __('messages.auth.password') }}</label>
            <div class="relative">
                <input id="password" type="password" name="password" required autocomplete="current-password"
                       class="w-full rounded-md border border-stone-300 px-3 py-2 pr-10 text-sm focus:border-teal-500 focus:outline-none">
                <button type="button" id="toggle-password"
                        class="absolute inset-y-0 right-0 flex items-center px-3 text-stone-400 hover:text-stone-700"
                        aria-label="{{ __('messages.auth.show_password') }}"
                        data-show="{{ __('messages.auth.show_password') }}"
                        data-hide="{{ __('messages.auth.hide_password') }}">
                    <svg id="eye-open" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12s-3.75 6.75-9.75 6.75S2.25 12 2.25 12z"/>
                        <circle cx="12" cy="12" r="2.75"/>
                    </svg>
                    <svg id="eye-closed" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="hidden h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75l16.5 16.5M9.9 9.97A2.75 2.75 0 0012 14.75c.5 0 .97-.13 1.38-.36M6.53 6.7C4.4 8.1 2.9 10.2 2.25 12c0 0 3.75 6.75 9.75 6.75 1.6 0 3.05-.36 4.3-.94M14.12 9.88A2.74 2.74 0 0114.75 12"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 5.25c6 0 9.75 6.75 9.75 6.75a17.7 17.7 0 01-2.4 3.15"/>
                    </svg>
                </button>
            </div>
        </div>
        <script>
            (() => {
                const input = document.getElementById('password');
                const btn = document.getElementById('toggle-password');
                const open = document.getElementById('eye-open');
                const closed = document.getElementById('eye-closed');
                btn?.addEventListener('click', () => {
                    const show = input.type === 'password';
                    input.type = show ? 'text' : 'password';
                    open.classList.toggle('hidden', show);
                    closed.classList.toggle('hidden', !show);
                    btn.setAttribute('aria-label', show ? btn.dataset.hide : btn.dataset.show);
                });
            })();
        </script>

        @error('email')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
        <button type="submit" class="w-full rounded-md bg-teal-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-teal-800">
            {{ __('messages.auth.sign_in') }}
        </button>
    </form>
</div>
@endsection
