<?php

use App\Enums\EmployeeStatus;
use App\Exceptions\DomainException;
use App\Models\Settlement;
use App\Services\SettlementService;

beforeEach(function () {
    unitSeedConfigs();
    unitSeedStatutory();
});

it('creates an exit and marks employee exiting', function () {
    $hr = unitUserWithRole('hr');
    $employee = unitEmployee('SE01');

    $exit = app(SettlementService::class)->createExit([
        'employee_id' => $employee->id,
        'category' => 'resignation',
        'last_working_date' => '2026-11-20',
        'resignation_date' => '2026-11-01',
        'status' => 'open',
    ]);

    expect($exit->employee_id)->toBe($employee->id)
        ->and($employee->fresh()->status)->toBe(EmployeeStatus::Exiting);
});

it('generates step-one settlement from approved attendance', function () {
    $hr = unitUserWithRole('hr');
    $employee = unitEmployee('SE02');
    unitSalaryStructure($employee);
    unitAttendance($employee, 2026, 11, 20, 0);

    $svc = app(SettlementService::class);
    $exit = $svc->createExit([
        'employee_id' => $employee->id,
        'category' => 'resignation',
        'last_working_date' => '2026-11-20',
        'resignation_date' => '2026-11-01',
        'status' => 'open',
    ]);

    $settlement = $svc->generateStepOne($exit, $hr);

    expect($settlement->step)->toBe(1)
        ->and((float) $settlement->net_payable)->toBeGreaterThan(0)
        ->and($exit->fresh()->status)->toBe('settlement_step1')
        ->and(Settlement::query()->where('exit_id', $exit->id)->count())->toBe(1);
});

it('requires approved attendance for step-one settlement', function () {
    $hr = unitUserWithRole('hr');
    $employee = unitEmployee('SE03');
    unitSalaryStructure($employee);

    $svc = app(SettlementService::class);
    $exit = $svc->createExit([
        'employee_id' => $employee->id,
        'category' => 'resignation',
        'last_working_date' => '2026-11-20',
        'status' => 'open',
    ]);

    expect(fn () => $svc->generateStepOne($exit, $hr))->toThrow(DomainException::class);
});
