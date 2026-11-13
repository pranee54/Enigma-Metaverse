<?php

use App\Enums\PayrollState;
use App\Exceptions\DomainException;
use App\Models\PayrollRun;
use App\Services\PayrollRunService;

beforeEach(function () {
    unitSeedConfigs();
    unitSeedStatutory();
});

it('creates a draft payroll run for a period', function () {
    $hr = unitUserWithRole('hr');
    $run = app(PayrollRunService::class)->create(2026, 8, $hr);

    expect($run->state)->toBe(PayrollState::Draft)
        ->and($run->period_year)->toBe(2026)
        ->and($run->period_month)->toBe(8)
        ->and($run->prepared_by)->toBe($hr->id);
});

it('rejects duplicate period create', function () {
    $hr = unitUserWithRole('hr');
    app(PayrollRunService::class)->create(2026, 8, $hr);

    expect(fn () => app(PayrollRunService::class)->create(2026, 8, $hr))
        ->toThrow(DomainException::class);
});

it('calculates items for employees with approved attendance', function () {
    $hr = unitUserWithRole('hr');
    $employee = unitEmployee('PR01');
    unitSalaryStructure($employee);
    unitAttendance($employee, 2026, 8, 31, 0);

    $svc = app(PayrollRunService::class);
    $run = $svc->create(2026, 8, $hr);
    $run = $svc->calculate($run);

    expect($run->state)->toBe(PayrollState::Calculated)
        ->and($run->items)->toHaveCount(1)
        ->and($run->items->first()->status)->not->toBe('hold')
        ->and((float) $run->items->first()->net)->toBeGreaterThan(0);
});

it('holds employees missing approved attendance', function () {
    $hr = unitUserWithRole('hr');
    unitEmployee('PR02');
    $svc = app(PayrollRunService::class);
    $run = $svc->create(2026, 7, $hr);
    $run = $svc->calculate($run);

    expect($run->items)->toHaveCount(1)
        ->and($run->items->first()->status)->toBe('hold')
        ->and($run->items->first()->hold_reason)->toContain('attendance');
});

it('walks validate submit approve finalise paid reconcile with maker-checker', function () {
    $hr = unitUserWithRole('hr', 'hr-unit@enigma.test');
    $finance = unitUserWithRole('finance', 'finance-unit@enigma.test');
    $employee = unitEmployee('PR03');
    unitSalaryStructure($employee);
    unitAttendance($employee, 2026, 6, 30, 0);

    $svc = app(PayrollRunService::class);
    $run = $svc->create(2026, 6, $hr);
    $run = $svc->calculate($run);
    $run = $svc->validate($run);
    $run = $svc->submitReview($run);
    $run = $svc->approve($run, $finance);
    $run = $svc->finalise($run, $finance);
    $run = $svc->markPaid($run, $finance);
    $run = $svc->markReconciled($run, $finance);

    expect($run->state)->toBe(PayrollState::Reconciled)
        ->and($run->approved_by)->toBe($finance->id);
});

it('blocks preparer from approving the same run', function () {
    $hr = unitUserWithRole('hr');
    \Spatie\Permission\Models\Role::findOrCreate('finance');
    $hr->assignRole('finance');
    $employee = unitEmployee('PR04');
    unitSalaryStructure($employee);
    unitAttendance($employee, 2026, 5, 31, 0);

    $svc = app(PayrollRunService::class);
    $run = $svc->create(2026, 5, $hr);
    $run = $svc->calculate($run);
    $run = $svc->validate($run);
    $run = $svc->submitReview($run);

    expect(fn () => $svc->approve($run->fresh(), $hr))->toThrow(DomainException::class);
});

it('blocks calculate after review has started', function () {
    $hr = unitUserWithRole('hr');
    $employee = unitEmployee('PR05');
    unitSalaryStructure($employee);
    unitAttendance($employee, 2026, 4, 30, 0);

    $svc = app(PayrollRunService::class);
    $run = $svc->create(2026, 4, $hr);
    $run = $svc->calculate($run);
    $run = $svc->validate($run);
    $run = $svc->submitReview($run);

    expect(fn () => $svc->calculate($run->fresh()))->toThrow(DomainException::class);
});

it('rejects illegal state jumps', function () {
    $hr = unitUserWithRole('hr');
    $finance = unitUserWithRole('finance');
    $run = app(PayrollRunService::class)->create(2026, 3, $hr);

    expect(fn () => app(PayrollRunService::class)->approve($run, $finance))
        ->toThrow(DomainException::class)
        ->and(PayrollRun::query()->find($run->id)->state)->toBe(PayrollState::Draft);
});
