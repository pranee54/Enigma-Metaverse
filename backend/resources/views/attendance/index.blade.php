@extends('layouts.app')

@section('title', __('messages.pages.attendance.title'))

@section('content')
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="font-display text-2xl font-semibold">{{ __('messages.pages.attendance.title') }}</h1>
        <p class="text-sm text-stone-500">{{ __('messages.pages.attendance.subtitle') }}</p>
    </div>
    @if(auth()->user()->hasRole('hr'))
        <a href="{{ route('attendance.guide', ['period_year' => $periodYear, 'period_month' => $periodMonth]) }}"
           class="rounded-md border border-stone-300 bg-white px-4 py-2 text-sm hover:bg-stone-50">
            {{ __('messages.pages.attendance.template_demo') }}
        </a>
    @endif
</div>

@if($readyPeriods->isNotEmpty())
<div class="mt-6 overflow-hidden rounded-lg border border-teal-200 bg-teal-50/40">
    <div class="border-b border-teal-100 px-5 py-4">
        <h2 class="font-display text-base font-semibold text-teal-950">{{ __('messages.pages.attendance.ready_title') }}</h2>
        <p class="text-sm text-teal-900/80">{{ __('messages.pages.attendance.ready_subtitle') }}</p>
    </div>
    <ul class="divide-y divide-teal-100 bg-white">
        @foreach($readyPeriods as $period)
            <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-3 text-sm">
                <div>
                    <p class="font-medium text-stone-900">
                        {{ \Carbon\Carbon::create($period->period_year, $period->period_month, 1)->format('M Y') }}
                    </p>
                    <p class="text-stone-500">{{ __('messages.pages.attendance.employees_approved', ['count' => $period->employee_count]) }}</p>
                </div>
                <a href="{{ route('payroll.index') }}" class="text-teal-700 hover:underline">{{ __('messages.pages.attendance.go_payroll') }}</a>
            </li>
        @endforeach
    </ul>
</div>
@endif

@if(auth()->user()->hasRole('hr'))
<div class="mt-6 rounded-lg border border-stone-200 bg-white p-5">
    <form method="POST" action="{{ route('attendance.import') }}" enctype="multipart/form-data" class="flex flex-wrap items-end gap-3">
        @csrf
        <div>
            <label class="mb-1 block text-xs text-stone-500">{{ __('messages.common.year') }}</label>
            <input type="number" name="period_year" value="{{ $periodYear }}" class="w-28 rounded-md border border-stone-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-xs text-stone-500">{{ __('messages.common.month') }}</label>
            <input type="number" name="period_month" value="{{ $periodMonth }}" min="1" max="12" class="w-24 rounded-md border border-stone-300 px-3 py-2 text-sm">
        </div>
        <div class="min-w-56 flex-1">
            <label class="mb-1 block text-xs text-stone-500">{{ __('messages.pages.attendance.csv_file') }}</label>
            <input type="file" name="file" accept=".csv,text/csv" required
                   class="block w-full text-sm text-stone-600 file:mr-3 file:rounded-md file:border-0 file:bg-stone-800 file:px-3 file:py-2 file:text-sm file:text-white">
        </div>
        <button class="rounded-md bg-teal-700 px-4 py-2 text-sm text-white hover:bg-teal-800">{{ __('messages.common.import') }}</button>
    </form>
</div>
@endif

<div class="mt-6 overflow-hidden rounded-lg border border-stone-200 bg-white">
    <div class="border-b border-stone-100 px-5 py-4">
        <h2 class="font-display text-base font-semibold">{{ __('messages.pages.attendance.batches_title') }}</h2>
        <p class="text-sm text-stone-500">{{ __('messages.pages.attendance.batches_subtitle') }}</p>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-stone-50 text-xs uppercase text-stone-500">
            <tr>
                <th class="px-4 py-3 text-left">{{ __('messages.pages.attendance.batch') }}</th>
                <th class="px-4 py-3 text-left">{{ __('messages.common.period') }}</th>
                <th class="px-4 py-3 text-left">{{ __('messages.pages.attendance.accepted') }}</th>
                <th class="px-4 py-3 text-left">{{ __('messages.pages.attendance.rejected') }}</th>
                <th class="px-4 py-3 text-left">{{ __('messages.common.status') }}</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($batches as $b)
                @php $approved = $b->status === 'approved'; @endphp
                <tr class="border-t border-stone-100">
                    <td class="px-4 py-3 font-medium">{{ $b->batch_code }}</td>
                    <td class="px-4 py-3">{{ \Carbon\Carbon::create($b->period_year, $b->period_month, 1)->format('M Y') }}</td>
                    <td class="px-4 py-3">{{ $b->accepted_count }}</td>
                    <td class="px-4 py-3 {{ $b->rejected_count > 0 ? 'text-amber-700' : '' }}">{{ $b->rejected_count }}</td>
                    <td class="px-4 py-3">
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $approved ? 'bg-teal-50 text-teal-800' : 'bg-amber-50 text-amber-800' }}">
                            {{ $b->status }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right space-x-3">
                        <a href="{{ route('attendance.show', $b) }}" class="text-teal-700 hover:underline">{{ __('messages.common.view') }}</a>
                        @if(auth()->user()->hasRole('hr') && ! $approved)
                            <form method="POST" action="{{ route('attendance.approve', $b) }}" class="inline">
                                @csrf
                                <button class="text-teal-700 hover:underline">{{ __('messages.common.approve') }}</button>
                            </form>
                        @endif
                        @if(auth()->user()->hasRole('hr'))
                            <form method="POST" action="{{ route('attendance.destroy', $b) }}" class="inline"
                                  onsubmit="return confirm(@js(__('messages.pages.attendance.remove_confirm')))">
                                @csrf
                                @method('DELETE')
                                <button class="text-red-600 hover:underline">{{ __('messages.common.remove') }}</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-stone-500">
                        {{ __('messages.pages.attendance.empty_batches') }}
                        @if(auth()->user()->hasRole('hr'))
                            <a href="{{ route('attendance.guide', ['period_year' => $periodYear, 'period_month' => $periodMonth]) }}" class="text-teal-700 hover:underline">{{ __('messages.pages.attendance.open_template') }}</a>
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $batches->links() }}</div>
@endsection
