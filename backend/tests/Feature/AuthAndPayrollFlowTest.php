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

it('logs in via blade session and reaches dashboard', function () {
    $response = $this->post('/login', [
        'email' => 'hr@enigma.test',
        'password' => 'password',
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticated();
});

it('blocks maker from approving their own payroll run', function () {
    $hr = User::where('email', 'hr@enigma.test')->first();

    $this->actingAs($hr)
        ->post('/payroll', [
            'period_year' => 2026,
            'period_month' => 10,
        ])
        ->assertRedirect();

    $run = PayrollRun::query()->where('period_year', 2026)->where('period_month', 10)->firstOrFail();

    $this->actingAs($hr)->post(route('payroll.calculate', $run))->assertRedirect();
    $this->actingAs($hr)->post(route('payroll.validate', $run))->assertRedirect();
    $this->actingAs($hr)->post(route('payroll.submit', $run))->assertRedirect();

    // HR cannot hit approve at all — Finance-only route middleware
    $this->actingAs($hr)
        ->post(route('payroll.approve', $run))
        ->assertForbidden();
});

it('allows finance to approve after hr prepares', function () {
    $hr = User::where('email', 'hr@enigma.test')->first();
    $finance = User::where('email', 'finance@enigma.test')->first();

    expect($hr->id)->not->toBe($finance->id);

    $this->actingAs($hr)->post('/payroll', [
        'period_year' => 2026,
        'period_month' => 10,
    ]);

    $run = PayrollRun::query()->where('period_year', 2026)->where('period_month', 10)->firstOrFail();

    $this->actingAs($hr)->post(route('payroll.calculate', $run));
    $this->actingAs($hr)->post(route('payroll.validate', $run));
    $this->actingAs($hr)->post(route('payroll.submit', $run));

    $this->actingAs($finance)
        ->post(route('payroll.approve', $run))
        ->assertRedirect();

    expect($run->fresh()->state)->toBe(PayrollState::Approved);
});
