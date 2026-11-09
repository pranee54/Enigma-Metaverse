<?php

use App\Enums\PayrollState;
use App\Models\Employee;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['hr', 'finance', 'employee', 'auditor'] as $role) {
        Role::findOrCreate($role);
    }
    $this->seed(DatabaseSeeder::class);
});

it('defaults payslips to the latest month and can filter by period', function () {
    $hr = User::where('email', 'hr@enigma.test')->firstOrFail();
    $employee = Employee::where('employee_code', 'EMP001')->firstOrFail();

    $oct = PayrollRun::create([
        'period_year' => 2026,
        'period_month' => 10,
        'state' => PayrollState::Approved,
        'prepared_by' => $hr->id,
        'approved_by' => $hr->id,
    ]);
    PayrollItem::create([
        'run_id' => $oct->id,
        'employee_id' => $employee->id,
        'gross' => 1000,
        'total_deductions' => 100,
        'net' => 900,
        'employer_cost' => 1100,
        'status' => 'ok',
    ]);

    $nov = PayrollRun::create([
        'period_year' => 2026,
        'period_month' => 11,
        'state' => PayrollState::Approved,
        'prepared_by' => $hr->id,
        'approved_by' => $hr->id,
    ]);
    PayrollItem::create([
        'run_id' => $nov->id,
        'employee_id' => $employee->id,
        'gross' => 2000,
        'total_deductions' => 200,
        'net' => 1800,
        'employer_cost' => 2200,
        'status' => 'ok',
    ]);

    $this->actingAs($hr)
        ->get(route('payslips.index'))
        ->assertOk()
        ->assertSee('Month')
        ->assertSee('Nov 2026')
        ->assertSee('1,800.00')
        ->assertDontSee('900.00');

    $this->actingAs($hr)
        ->get(route('payslips.index', ['period' => '2026-10']))
        ->assertOk()
        ->assertSee('Showing')
        ->assertSee('Oct 2026')
        ->assertSee('900.00')
        ->assertDontSee('1,800.00');
});
