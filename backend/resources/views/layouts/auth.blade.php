<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ __('messages.layout.meta_description') }}">
    <meta name="theme-color" content="#f3f1ec">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('messages.auth.sign_in') }} · {{ $companyName ?? 'Payroll' }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="flex min-h-screen items-center justify-center bg-gradient-to-br from-stone-100 via-teal-50 to-stone-200 px-4 font-sans text-ink antialiased">
@yield('content')
</body>
</html>
