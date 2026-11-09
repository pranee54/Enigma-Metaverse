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

it('loads admin chrome text from the messages file', function () {
    expect(__('messages.nav.employees'))->toBe('Employees')
        ->and(__('messages.layout.sign_out'))->toBe('Sign out')
        ->and(__('messages.pages.dashboard.desk_hr'))->toBe('HR desk')
        ->and(__('messages.pages.loans.empty_hint'))->toContain('recovery')
        ->and(__('messages.flash.payroll_created'))->toBe('Payroll run created.');

    $hr = User::where('email', 'hr@enigma.test')->firstOrFail();

    $this->actingAs($hr)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('HR menu')
        ->assertSee('Daily work')
        ->assertSee('Sign out');
});

it('shows helpful empty states on people-change pages', function () {
    $hr = User::where('email', 'hr@enigma.test')->firstOrFail();

    $this->actingAs($hr)
        ->get(route('loans.index'))
        ->assertOk()
        ->assertSee('No loans yet')
        ->assertSee('Create an advance or loan above');

    $this->actingAs($hr)
        ->get(route('revisions.index'))
        ->assertOk()
        ->assertSee('No revisions yet')
        ->assertSee('finance approves');

    $this->actingAs($hr)
        ->get(route('exits.index'))
        ->assertOk()
        ->assertSee('No exits recorded')
        ->assertSee('generate Step-1 settlement');
});
