<?php

use App\Enums\ComponentClass;
use App\Models\SalaryComponent;
use App\Services\WageBaseCalculator;

beforeEach(function () {
    unitSeedConfigs();
});

it('sums wage bases from earning flags', function () {
    $employee = unitEmployee('WB01');
    $structure = unitSalaryStructure($employee, 40000, 20000);

    $bases = (new WageBaseCalculator)->summarize($structure);

    expect($bases['total_remuneration'])->toBe(60000.0)
        ->and($bases['code_wages'])->toBe(40000.0)
        ->and($bases['epf_wages'])->toBe(40000.0)
        ->and($bases['esi_wages'])->toBe(60000.0)
        ->and($bases['pt_gross'])->toBe(60000.0)
        ->and($bases['excluded_total'])->toBe(20000.0)
        ->and($bases['fifty_percent_addback'])->toBe(0.0)
        ->and($bases['warning'])->toBeNull();
});

it('applies 50 percent add-back when excluded earnings exceed half', function () {
    $employee = unitEmployee('WB02');
    $structure = unitSalaryStructure($employee, 20000, 0);

    SalaryComponent::query()->where('structure_id', $structure->id)->where('code', 'HRA')->delete();
    SalaryComponent::create([
        'structure_id' => $structure->id,
        'code' => 'ALLOW',
        'name' => 'Special',
        'class' => ComponentClass::Earning,
        'amount_monthly' => 30000,
        'include_in_code_wages' => false,
        'include_in_epf' => false,
        'include_in_esi' => false,
        'include_in_pt' => false,
        'include_in_taxable' => true,
    ]);
    $structure->load('components');

    $bases = (new WageBaseCalculator)->summarize($structure);

    expect($bases['total_remuneration'])->toBe(50000.0)
        ->and($bases['excluded_total'])->toBe(30000.0)
        ->and($bases['fifty_percent_addback'])->toBe(5000.0)
        ->and($bases['code_wages'])->toBe(25000.0)
        ->and($bases['warning'])->not->toBeNull();
});
