<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ __('messages.layout.meta_description') }}">
    <meta name="theme-color" content="#f3f1ec">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('messages.layout.default_title')) · {{ $companyName ?? 'Payroll' }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('head')
</head>
<body class="h-screen overflow-hidden bg-[#f3f1ec] font-sans text-ink antialiased">
@php
    $user = auth()->user();
    $role = $user->getRoleNames()->first() ?? 'user';

    $nav = match ($role) {
        'hr' => [
            ['section' => __('messages.sections.daily_work'), 'items' => [
                [__('messages.nav.dashboard'), route('dashboard'), ['/']],
                [__('messages.nav.employees'), route('employees.index'), ['employees*']],
                [__('messages.nav.attendance'), route('attendance.index'), ['attendance*']],
                [__('messages.nav.payroll'), route('payroll.index'), ['payroll*']],
            ]],
            ['section' => __('messages.sections.people_changes'), 'items' => [
                [__('messages.nav.loans'), route('loans.index'), ['loans*']],
                [__('messages.nav.revisions'), route('revisions.index'), ['revisions*']],
                [__('messages.nav.exits'), route('exits.index'), ['exits*']],
            ]],
            ['section' => __('messages.sections.outputs'), 'items' => [
                [__('messages.nav.payslips'), route('payslips.index'), ['payslips*']],
                [__('messages.nav.settings'), route('settings.index'), ['settings*']],
            ]],
        ],
        'finance' => [
            ['section' => __('messages.sections.review_pay'), 'items' => [
                [__('messages.nav.dashboard'), route('dashboard'), ['/']],
                [__('messages.nav.payroll'), route('payroll.index'), ['payroll*']],
                [__('messages.nav.revisions'), route('revisions.index'), ['revisions*']],
                [__('messages.nav.exports'), route('exports.index'), ['exports*']],
            ]],
            ['section' => __('messages.sections.reference'), 'items' => [
                [__('messages.nav.employees'), route('employees.index'), ['employees*']],
                [__('messages.nav.attendance'), route('attendance.index'), ['attendance*']],
                [__('messages.nav.payslips'), route('payslips.index'), ['payslips*']],
                [__('messages.nav.loans'), route('loans.index'), ['loans*']],
                [__('messages.nav.settings'), route('settings.index'), ['settings*']],
            ]],
        ],
        'auditor' => [
            ['section' => __('messages.sections.audit_view'), 'items' => [
                [__('messages.nav.dashboard'), route('dashboard'), ['/']],
                [__('messages.nav.payroll'), route('payroll.index'), ['payroll*']],
                [__('messages.nav.exports'), route('exports.index'), ['exports*']],
                [__('messages.nav.payslips'), route('payslips.index'), ['payslips*']],
            ]],
            ['section' => __('messages.sections.master_data'), 'items' => [
                [__('messages.nav.employees'), route('employees.index'), ['employees*']],
                [__('messages.nav.attendance'), route('attendance.index'), ['attendance*']],
                [__('messages.nav.loans'), route('loans.index'), ['loans*']],
                [__('messages.nav.revisions'), route('revisions.index'), ['revisions*']],
                [__('messages.nav.exits'), route('exits.index'), ['exits*']],
            ]],
        ],
        default => [
            ['section' => __('messages.sections.my_pay'), 'items' => [
                [__('messages.nav.payslips'), route('payslips.index'), ['payslips*']],
            ]],
        ],
    };

    $roleLabel = match ($role) {
        'hr', 'finance', 'auditor', 'employee' => __('messages.roles.'.$role),
        default => ucfirst($role),
    };
@endphp

<div class="app-shell flex h-screen overflow-hidden">
    <div id="nav-backdrop" class="nav-backdrop" hidden></div>

    <aside id="app-nav"
           class="mobile-drawer flex h-screen w-[min(18rem,88vw)] shrink-0 flex-col border-r border-stone-200/80 bg-[#faf8f5] lg:w-64"
           aria-label="{{ __('messages.roles.menu', ['role' => $roleLabel]) }}">
        <div class="flex shrink-0 items-start justify-between gap-2 border-b border-stone-200/80 px-5 py-5">
            <div class="min-w-0">
                <p class="ui-heading text-lg tracking-tight">{{ $companyName ?? 'Payroll' }}</p>
                <p class="ui-sub mt-1 text-xs">{{ $companyState ?? '' }} · {{ $companyCurrency ?? 'INR' }}</p>
                <span class="mt-3 inline-flex rounded-full bg-teal-800 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-white">
                    {{ __('messages.roles.menu', ['role' => $roleLabel]) }}
                </span>
            </div>
            <button type="button"
                    class="nav-close inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-stone-300 bg-white text-stone-700 lg:hidden"
                    aria-label="{{ __('messages.layout.close_menu') }}">
                <span class="text-xl leading-none" aria-hidden="true">&times;</span>
            </button>
        </div>
        <nav class="flex min-h-0 flex-1 flex-col gap-5 overflow-y-auto p-3 text-sm">
            @foreach ($nav as $group)
                <div>
                    <p class="ui-label px-3 pb-1.5">{{ $group['section'] }}</p>
                    <div class="flex flex-col gap-0.5">
                        @foreach ($group['items'] as [$label, $url, $patterns])
                            @php
                                $active = collect($patterns)->contains(fn ($pattern) => request()->is($pattern));
                            @endphp
                            <a href="{{ $url }}"
                               @if($active) aria-current="page" @endif
                               class="rounded-lg px-3 py-2.5 {{ $active ? 'bg-teal-800/10 font-medium text-teal-900' : 'ui-sub hover:bg-stone-200/50 hover:text-ink' }}">
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </nav>
        <div class="shrink-0 border-t border-stone-200/80 p-4">
            <p class="ui-heading truncate text-sm">{{ $user->name }}</p>
            <p class="ui-sub mt-0.5 truncate text-xs">{{ __('messages.roles.signed_in_as', ['role' => $roleLabel]) }}</p>
            <form method="POST" action="{{ route('logout') }}" class="mt-3">
                @csrf
                <button class="w-full rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-xs text-ink hover:bg-stone-50">{{ __('messages.layout.sign_out') }}</button>
            </form>
        </div>
    </aside>

    <div class="flex min-h-0 min-w-0 flex-1 flex-col overflow-hidden">
        <header class="flex h-14 shrink-0 items-center border-b border-stone-200/80 bg-[#faf8f5] px-3 sm:px-4 lg:px-8">
            <div class="flex w-full items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-2 lg:hidden">
                    <button type="button"
                            class="nav-open inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-stone-300 bg-white text-stone-800"
                            aria-controls="app-nav"
                            aria-expanded="false"
                            aria-label="{{ __('messages.layout.open_menu') }}">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M3 5h14a1 1 0 1 1 0 2H3a1 1 0 0 1 0-2Zm0 4h14a1 1 0 1 1 0 2H3a1 1 0 0 1 0-2Zm0 4h14a1 1 0 1 1 0 2H3a1 1 0 0 1 0-2Z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                    <div class="min-w-0">
                        <p class="ui-heading truncate text-sm">{{ $companyName ?? 'Payroll' }}</p>
                        <p class="truncate text-[11px] text-stone-500">@yield('title', __('messages.layout.default_title'))</p>
                    </div>
                </div>
                <div class="hidden min-w-0 lg:block">
                    <p class="ui-label">{{ __('messages.layout.page') }}</p>
                    <p class="ui-heading truncate text-base leading-tight">@yield('title', __('messages.layout.default_title'))</p>
                </div>
                <div class="text-right">
                    <p class="ui-label">{{ __('messages.layout.today') }}</p>
                    <p class="ui-sub text-sm leading-tight">{{ now()->format('d M Y') }}</p>
                </div>
            </div>
        </header>
        <main class="min-h-0 flex-1 overflow-y-auto px-4 py-6 lg:px-8">
            @session('success')
                <div class="mb-4 rounded-lg border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-900" role="status">{{ $value }}</div>
            @endsession
            @session('error')
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">{{ $value }}</div>
            @endsession
            @if ($errors->any())
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
                    <ul class="list-disc pl-4">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @yield('content')
        </main>
    </div>
</div>
<script>
(() => {
    const desktop = () => window.matchMedia('(min-width: 1024px)').matches;
    const nav = document.getElementById('app-nav');
    const backdrop = document.getElementById('nav-backdrop');
    const openBtns = document.querySelectorAll('.nav-open');
    const closeBtns = document.querySelectorAll('.nav-close');

    const setOpen = (open) => {
        document.body.classList.toggle('nav-open', open);
        if (backdrop) backdrop.hidden = !open;
        if (nav && !desktop()) nav.inert = !open;
        openBtns.forEach((btn) => btn.setAttribute('aria-expanded', open ? 'true' : 'false'));
    };

    const sync = () => setOpen(desktop() ? false : document.body.classList.contains('nav-open'));

    openBtns.forEach((btn) => btn.addEventListener('click', () => setOpen(true)));
    closeBtns.forEach((btn) => btn.addEventListener('click', () => setOpen(false)));
    backdrop?.addEventListener('click', () => setOpen(false));
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') setOpen(false);
    });
    window.addEventListener('resize', sync);
    sync();
})();
</script>
</body>
</html>
