<?php

use App\Enums\PayrollState;
use App\Models\Employee;
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

it('blocks employee from approving payroll via HTTP', function () {
    $hr = User::where('email', 'hr@enigma.test')->firstOrFail();
    $employee = User::where('email', 'emp@enigma.test')->firstOrFail();

    $this->actingAs($hr)->post('/payroll', [
        'period_year' => 2026,
        'period_month' => 10,
    ]);

    $run = PayrollRun::query()->where('period_year', 2026)->where('period_month', 10)->firstOrFail();
    $run->update(['state' => PayrollState::UnderReview]);

    $this->actingAs($employee)
        ->post(route('payroll.approve', $run))
        ->assertForbidden();
});

it('blocks hr from approving payroll via HTTP (finance only)', function () {
    $hr = User::where('email', 'hr@enigma.test')->firstOrFail();
    $otherHr = User::factory()->create(['email' => 'hr2@enigma.test']);
    $otherHr->syncRoles(['hr']);

    $this->actingAs($hr)->post('/payroll', [
        'period_year' => 2026,
        'period_month' => 11,
    ]);

    $run = PayrollRun::query()->where('period_year', 2026)->where('period_month', 11)->firstOrFail();
    $run->update([
        'state' => PayrollState::UnderReview,
        'prepared_by' => $hr->id,
    ]);

    $this->actingAs($otherHr)
        ->post(route('payroll.approve', $run))
        ->assertForbidden();
});

it('lets hr create and update an employee', function () {
    $hr = User::where('email', 'hr@enigma.test')->firstOrFail();

    $this->actingAs($hr)
        ->post(route('employees.store'), [
            'employee_code' => 'EMP999',
            'first_name' => 'Test',
            'last_name' => 'Worker',
            'dob' => '1995-01-01',
            'email' => 'test.worker@example.com',
            'phone' => '9000000099',
            'department' => 'Engineering',
            'designation' => 'Developer',
            'work_location' => 'Hyderabad',
            'state_code' => 'TG',
            'employment_category' => 'permanent',
            'joining_date' => now()->toDateString(),
            'status' => 'active',
            'pan' => 'ABCDE9999F',
            'uan' => '10000000999',
            'bank_account' => '9999999999',
            'bank_ifsc' => 'HDFC0001234',
            'bank_name' => 'HDFC Bank',
            'tax_regime' => 'new',
            'appointment_letter_path' => 'letters/emp999.pdf',
            'basic' => 30000,
            'hra' => 10000,
            'special' => 5000,
            'salary_effective_from' => now()->toDateString(),
        ])
        ->assertRedirect();

    $employee = Employee::where('employee_code', 'EMP999')->firstOrFail();
    expect($employee->activeSalaryStructure)->not->toBeNull();
    expect((float) $employee->activeSalaryStructure->monthly_ctc)->toBe(45000.0);

    $this->actingAs($hr)
        ->put(route('employees.update', $employee), [
            'employee_code' => 'EMP999',
            'first_name' => 'Updated',
            'last_name' => 'Worker',
            'dob' => '1995-01-01',
            'designation' => 'Senior Developer',
            'work_location' => 'Hyderabad',
            'state_code' => 'TG',
            'employment_category' => 'permanent',
            'joining_date' => now()->toDateString(),
            'status' => 'active',
            'tax_regime' => 'new',
            'pan' => 'ABCDE9999F',
            'bank_account' => '9999999999',
            'bank_ifsc' => 'HDFC0001234',
            'bank_name' => 'HDFC Bank',
            'appointment_letter_path' => 'letters/emp999.pdf',
        ])
        ->assertRedirect(route('employees.show', $employee));

    expect($employee->fresh()->first_name)->toBe('Updated');
    expect($employee->fresh()->designation)->toBe('Senior Developer');
});

it('blocks employee from creating employees', function () {
    $employee = User::where('email', 'emp@enigma.test')->firstOrFail();

    $this->actingAs($employee)
        ->get(route('employees.create'))
        ->assertForbidden();
});
