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

it('shows empty payslips when employee role has null employee_id', function () {
    $user = User::factory()->create([
        'email' => 'orphan-employee@enigma.test',
        'employee_id' => null,
    ]);
    $user->assignRole('employee');

    $this->actingAs($user)
        ->get(route('payslips.index'))
        ->assertOk()
        ->assertSee(__('messages.pages.payslips.empty'));
});

it('redirects authenticated users away from guest login', function () {
    $hr = User::where('email', 'hr@enigma.test')->first();

    $this->actingAs($hr)
        ->get('/login')
        ->assertRedirect(route('dashboard'));
});
