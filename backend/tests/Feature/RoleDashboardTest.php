<?php

use App\Enums\PayrollState;
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

it('shows finance queue with under-review payroll', function () {
    $hr = User::where('email', 'hr@enigma.test')->firstOrFail();
    $finance = User::where('email', 'finance@enigma.test')->firstOrFail();

    $run = PayrollRun::create([
        'period_year' => 2026,
        'period_month' => 9,
        'state' => PayrollState::UnderReview,
        'prepared_by' => $hr->id,
    ]);

    $this->actingAs($finance)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Finance desk')
        ->assertSee('Awaiting your approval')
        ->assertSee('Payroll '.$run->periodLabel().' — approve')
        ->assertSee('Maker-checker');
});

it('shows hr month checklist and next action for draft payroll', function () {
    $hr = User::where('email', 'hr@enigma.test')->firstOrFail();

    PayrollRun::create([
        'period_year' => 2026,
        'period_month' => 8,
        'state' => PayrollState::Draft,
        'prepared_by' => $hr->id,
    ]);

    $this->actingAs($hr)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('HR desk')
        ->assertSee('Payroll checklist')
        ->assertSee('Aug 2026')
        ->assertSee('Attendance approved')
        ->assertSee('Progress')
        ->assertSee('Import / approve attendance for Aug 2026')
        ->assertSee('HR actions left')
        ->assertSee('Aug 2026 attendance')
        ->assertDontSee('Other open items')
        ->assertDontSee('Shortcuts');
});

it('shows auditor desk stats', function () {
    $auditor = User::where('email', 'auditor@enigma.test')->firstOrFail();

    $this->actingAs($auditor)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Auditor view')
        ->assertSee('Employees')
        ->assertSee('Payroll runs')
        ->assertSee('Audit events');
});
