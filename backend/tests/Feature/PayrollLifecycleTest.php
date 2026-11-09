<?php

use App\Enums\PayrollState;
use App\Models\PayrollRun;
use App\Models\User;
use App\Services\PayrollRunService;
use Database\Seeders\DatabaseSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['hr', 'finance', 'employee', 'auditor'] as $role) {
        Role::findOrCreate($role);
    }
    $this->seed(DatabaseSeeder::class);
});

it('runs full calculate to paid to reconciled', function () {
    $hr = User::where('email', 'hr@enigma.test')->first();
    $finance = User::where('email', 'finance@enigma.test')->first();

    $this->actingAs($hr)->post('/payroll', [
        'period_year' => 2026,
        'period_month' => 10,
    ])->assertRedirect();

    $run = PayrollRun::query()->where('period_year', 2026)->where('period_month', 10)->firstOrFail();

    $this->actingAs($hr)->post(route('payroll.calculate', $run))->assertRedirect();
    expect($run->fresh()->state)->toBe(PayrollState::Calculated);

    $this->actingAs($hr)->post(route('payroll.validate', $run))->assertRedirect();
    $this->actingAs($hr)->post(route('payroll.submit', $run))->assertRedirect();
    $this->actingAs($finance)->post(route('payroll.approve', $run))->assertRedirect();
    $this->actingAs($finance)->post(route('payroll.finalise', $run))->assertRedirect();
    $this->actingAs($finance)->post(route('payroll.paid', $run))->assertRedirect();
    expect($run->fresh()->state)->toBe(PayrollState::Paid);

    $this->actingAs($finance)->post(route('payroll.reconcile', $run))->assertRedirect();
    expect($run->fresh()->state)->toBe(PayrollState::Reconciled);
});

it('rejects illegal payroll state transitions', function () {
    $hr = User::where('email', 'hr@enigma.test')->first();
    $finance = User::where('email', 'finance@enigma.test')->first();

    $this->actingAs($hr)->post('/payroll', [
        'period_year' => 2026,
        'period_month' => 9,
    ]);

    $run = PayrollRun::query()->where('period_year', 2026)->where('period_month', 9)->firstOrFail();
    expect($run->state)->toBe(PayrollState::Draft);

    $this->actingAs($finance)
        ->from(route('payroll.show', $run))
        ->post(route('payroll.approve', $run))
        ->assertRedirect();

    expect(session('error'))->toContain('Cannot move payroll');
    expect($run->fresh()->state)->toBe(PayrollState::Draft);

    app(PayrollRunService::class)->calculate($run);
    expect(fn () => app(PayrollRunService::class)->markPaid($run->fresh(), $finance))
        ->toThrow(\App\Exceptions\DomainException::class);
});
