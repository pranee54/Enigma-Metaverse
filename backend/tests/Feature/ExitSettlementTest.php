<?php

use App\Enums\EmployeeStatus;
use App\Models\AttendanceSummary;
use App\Models\Employee;
use App\Models\EmployeeExit;
use App\Models\Settlement;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['hr', 'finance', 'employee', 'auditor'] as $role) {
        Role::findOrCreate($role);
    }
    $this->seed(DatabaseSeeder::class);
});

it('records exit and generates step-one settlement from approved attendance', function () {
    $hr = User::where('email', 'hr@enigma.test')->first();
    $employee = Employee::query()
        ->where('status', EmployeeStatus::Active)
        ->whereHas('salaryStructures', fn ($q) => $q->where('status', 'active'))
        ->firstOrFail();

    AttendanceSummary::query()->updateOrCreate(
        [
            'employee_id' => $employee->id,
            'period_year' => 2026,
            'period_month' => 11,
            'version' => 1,
        ],
        [
            'payable_days' => 20,
            'lop_days' => 0,
            'overtime_hours' => 0,
            'status' => 'approved',
        ]
    );

    $this->actingAs($hr)->post(route('exits.store'), [
        'employee_id' => $employee->id,
        'category' => 'resignation',
        'last_working_date' => '2026-11-20',
        'resignation_date' => '2026-11-01',
    ])->assertRedirect();

    $exit = EmployeeExit::query()->where('employee_id', $employee->id)->latest('id')->firstOrFail();
    expect($employee->fresh()->status)->toBe(EmployeeStatus::Exiting);

    $this->actingAs($hr)->post(route('exits.settlement', $exit))->assertRedirect();

    $settlement = Settlement::query()->where('exit_id', $exit->id)->where('step', 1)->first();
    expect($settlement)->not->toBeNull()
        ->and((float) $settlement->net_payable)->toBeGreaterThan(0)
        ->and($exit->fresh()->status)->toBe('settlement_step1');
});
