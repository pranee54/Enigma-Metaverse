<?php

use App\Enums\PayrollState;
use App\Models\Export;
use App\Models\PayrollRun;
use App\Models\User;
use App\Services\ExportService;
use Database\Seeders\DatabaseSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['hr', 'finance', 'employee', 'auditor'] as $role) {
        Role::findOrCreate($role);
    }
    $this->seed(DatabaseSeeder::class);
});

it('returns the same journal and bank export on repeat calls', function () {
    $hr = User::where('email', 'hr@enigma.test')->first();
    $finance = User::where('email', 'finance@enigma.test')->first();

    $this->actingAs($hr)->post('/payroll', [
        'period_year' => 2026,
        'period_month' => 10,
    ]);
    $run = PayrollRun::query()->where('period_year', 2026)->where('period_month', 10)->firstOrFail();

    $this->actingAs($hr)->post(route('payroll.calculate', $run));
    $this->actingAs($hr)->post(route('payroll.validate', $run));
    $this->actingAs($hr)->post(route('payroll.submit', $run));
    $this->actingAs($finance)->post(route('payroll.approve', $run));

    expect($run->fresh()->state)->toBe(PayrollState::Approved);

    $exports = app(ExportService::class);
    $journal1 = $exports->journal($run->fresh(), $finance, 'approved');
    $journal2 = $exports->journal($run->fresh(), $finance, 'approved');
    expect($journal2->id)->toBe($journal1->id)
        ->and(Export::query()->where('type', 'journal')->where('payroll_run_id', $run->id)->count())->toBe(1);

    $this->actingAs($finance)->post(route('payroll.finalise', $run));
    $this->actingAs($finance)->post(route('payroll.paid', $run));

    $bank1 = $exports->bank($run->fresh(), $finance);
    $bank2 = $exports->bank($run->fresh(), $finance);
    expect($bank2->id)->toBe($bank1->id)
        ->and(Export::query()->where('type', 'bank')->where('payroll_run_id', $run->id)->count())->toBe(1);
});
