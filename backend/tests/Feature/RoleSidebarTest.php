<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['hr', 'finance', 'employee', 'auditor'] as $role) {
        Role::findOrCreate($role);
    }
    $this->seed(DatabaseSeeder::class);
});

it('shows hr menu without exports', function () {
    $hr = User::where('email', 'hr@enigma.test')->firstOrFail();

    $this->actingAs($hr)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('HR menu')
        ->assertSee('Daily work')
        ->assertSee('Attendance')
        ->assertSee('Settings')
        ->assertDontSee('Exports');
});

it('shows finance menu with exports and without exits', function () {
    $finance = User::where('email', 'finance@enigma.test')->firstOrFail();

    $this->actingAs($finance)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Finance menu')
        ->assertSee('Review & pay')
        ->assertSee('Exports')
        ->assertDontSee('Exits');
});

it('shows employee only payslips menu', function () {
    $employee = User::where('email', 'emp@enigma.test')->firstOrFail();

    $this->actingAs($employee)
        ->get(route('payslips.index'))
        ->assertOk()
        ->assertSee('Employee menu')
        ->assertSee('My pay')
        ->assertSee('Payslips')
        ->assertDontSee('Daily work')
        ->assertDontSee('Review & pay')
        ->assertDontSee('Exports');
});
