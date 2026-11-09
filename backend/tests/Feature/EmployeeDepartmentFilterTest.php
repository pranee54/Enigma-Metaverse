<?php

use App\Models\Employee;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['hr', 'finance', 'employee', 'auditor'] as $role) {
        Role::findOrCreate($role);
    }
    $this->seed(DatabaseSeeder::class);
});

it('shows department chips with counts on employees index', function () {
    $hr = User::where('email', 'hr@enigma.test')->firstOrFail();

    $this->actingAs($hr)
        ->get(route('employees.index'))
        ->assertOk()
        ->assertSee('All departments')
        ->assertSee('Engineering · 2')
        ->assertSee('Finance · 1')
        ->assertSee('HR · 1')
        ->assertSee('Operations · 1');
});

it('filters employees by department query', function () {
    $hr = User::where('email', 'hr@enigma.test')->firstOrFail();

    $this->actingAs($hr)
        ->get(route('employees.index', ['department' => 'Engineering']))
        ->assertOk()
        ->assertSee('Showing')
        ->assertSee('Engineering')
        ->assertSee('Rahul Sharma')
        ->assertSee('Ananya Iyer')
        ->assertDontSee('Sneha Reddy')
        ->assertDontSee('Vikram Patel');

    expect(
        Employee::where('department', 'Engineering')->count()
    )->toBe(2);
});
