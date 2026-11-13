<?php

use App\Enums\EmploymentCategory;
use App\Services\StatutoryEngine;
use Carbon\Carbon;

beforeEach(function () {
    unitSeedConfigs();
    unitSeedStatutory();
});

it('picks the latest statutory rule on or before as-of date', function () {
    $engine = new StatutoryEngine;
    $rule = $engine->rulesFor(Carbon::parse('2026-10-01'));

    expect($rule->code)->toBe('default')
        ->and($rule->effective_from->toDateString())->toBe('2026-01-01');
});

it('throws when no statutory rule exists', function () {
    \App\Models\StatutoryRuleVersion::query()->delete();

    expect(fn () => (new StatutoryEngine)->rulesFor(Carbon::parse('2026-10-01')))
        ->toThrow(RuntimeException::class);
});

it('skips statutes for apprentices', function () {
    $employee = unitEmployee('AP01', EmploymentCategory::Apprentice);
    $rule = unitSeedStatutory();
    $engine = new StatutoryEngine;

    expect($engine->appliesStatutes($employee))->toBeFalse();

    $lines = $engine->contributions($employee, [
        'epf_wages' => 20000,
        'esi_wages' => 20000,
        'pt_gross' => 20000,
        'code_wages' => 20000,
    ], $rule);

    expect($lines['employee'])->toBe([])
        ->and($lines['employer'])->toBe([]);
});

it('builds EPF ESI and PT contribution lines for permanent staff', function () {
    $employee = unitEmployee('ST01');
    $rule = unitSeedStatutory();
    $engine = new StatutoryEngine;

    $lines = $engine->contributions($employee, [
        'epf_wages' => 20000,
        'esi_wages' => 20000,
        'pt_gross' => 18000,
        'code_wages' => 20000,
    ], $rule);

    $codes = collect($lines['employee'])->pluck('code')->all();
    expect($codes)->toContain('EPF')
        ->and($codes)->toContain('ESI')
        ->and($codes)->toContain('PT')
        ->and(collect($lines['employer'])->pluck('code')->all())->toContain('EPF_ER')
        ->and(collect($lines['employer'])->pluck('code')->all())->toContain('EPS');
});

it('skips ESI when wages are above threshold', function () {
    $employee = unitEmployee('ST02');
    $rule = unitSeedStatutory();

    $lines = (new StatutoryEngine)->contributions($employee, [
        'epf_wages' => 25000,
        'esi_wages' => 25000,
        'pt_gross' => 25000,
        'code_wages' => 25000,
    ], $rule);

    expect(collect($lines['employee'])->pluck('code')->all())->not->toContain('ESI');
});

it('uses payload slabs for professional tax', function () {
    $engine = new StatutoryEngine;
    $slabs = [
        ['upto' => 10000, 'amount' => 0],
        ['upto' => null, 'amount' => 99],
    ];

    expect($engine->professionalTax(9000, $slabs))->toBe(0.0)
        ->and($engine->professionalTax(12000, $slabs))->toBe(99.0);
});
