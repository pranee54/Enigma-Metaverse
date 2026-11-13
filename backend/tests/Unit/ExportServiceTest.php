<?php

use App\Enums\PayrollState;
use App\Exceptions\DomainException;
use App\Models\Export;
use App\Models\PayrollRun;
use App\Services\ExportService;
use App\Services\PayrollRunService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    unitSeedConfigs();
    unitSeedStatutory();
    Storage::fake('local');
});

it('rejects exports before approval', function () {
    $hr = unitUserWithRole('hr');
    $run = app(PayrollRunService::class)->create(2026, 2, $hr);

    expect(fn () => app(ExportService::class)->journal($run, $hr))
        ->toThrow(DomainException::class);
});

it('creates idempotent journal and bank exports after approve', function () {
    $hr = unitUserWithRole('hr', 'hr-export@enigma.test');
    $finance = unitUserWithRole('finance', 'finance-export@enigma.test');
    $employee = unitEmployee('EX01');
    unitSalaryStructure($employee);
    unitAttendance($employee, 2026, 2, 28, 0);

    $svc = app(PayrollRunService::class);
    $run = $svc->create(2026, 2, $hr);
    $run = $svc->calculate($run);
    $run = $svc->validate($run);
    $run = $svc->submitReview($run);
    $run = $svc->approve($run, $finance);

    $exports = app(ExportService::class);
    $j1 = $exports->journal($run->fresh(), $finance, 'approved');
    $j2 = $exports->journal($run->fresh(), $finance, 'approved');

    expect($j2->id)->toBe($j1->id)
        ->and(Export::query()->where('type', 'journal')->where('payroll_run_id', $run->id)->count())->toBe(1)
        ->and(Storage::disk('local')->exists($j1->payload_path))->toBeTrue();

    $run = $svc->finalise($run->fresh(), $finance);
    $run = $svc->markPaid($run, $finance);
    expect($run->state)->toBe(PayrollState::Paid);

    $b1 = $exports->bank($run->fresh(), $finance);
    $b2 = $exports->bank($run->fresh(), $finance);

    expect($b2->id)->toBe($b1->id)
        ->and(Export::query()->where('type', 'bank')->where('payroll_run_id', $run->id)->count())->toBe(1)
        ->and(Storage::disk('local')->exists($b1->payload_path))->toBeTrue();
});
