<?php

use App\Enums\ComponentClass;
use App\Enums\EmployeeStatus;
use App\Enums\EmploymentCategory;
use App\Models\AttendanceSummary;
use App\Models\Configuration;
use App\Models\Employee;
use App\Models\SalaryComponent;
use App\Models\SalaryStructure;
use App\Models\StatutoryRuleVersion;
use App\Services\PayrollCalculator;
use App\Services\StatutoryEngine;
use App\Services\WageBaseCalculator;

beforeEach(function () {
    Configuration::create(['key' => 'proration_divisor', 'value_json' => 'calendar']);
    Configuration::create(['key' => 'overtime_multiplier', 'value_json' => 2]);
    Configuration::create(['key' => 'deduction_cap_percent', 'value_json' => 50]);

    StatutoryRuleVersion::create([
        'code' => 'default',
        'effective_from' => '2026-01-01',
        'payload_json' => [
            'epf_ceiling' => 25000,
            'epf_employee_rate' => 0.12,
            'epf_employer_rate' => 0.12,
            'eps_rate' => 0.0833,
            'esi_threshold' => 21000,
            'esi_employee_rate' => 0.0075,
            'esi_employer_rate' => 0.0325,
            'pt_slabs' => [
                ['upto' => 15000, 'amount' => 0],
                ['upto' => 20000, 'amount' => 150],
                ['upto' => null, 'amount' => 200],
            ],
        ],
    ]);
});

function makeEmployeeWithPay(): array
{
    $employee = Employee::create([
        'employee_code' => 'T001',
        'first_name' => 'Test',
        'last_name' => 'User',
        'dob' => '1990-01-01',
        'designation' => 'Dev',
        'work_location' => 'Hyderabad',
        'state_code' => 'TG',
        'employment_category' => EmploymentCategory::Permanent,
        'joining_date' => '2024-01-01',
        'status' => EmployeeStatus::Active,
        'pan' => 'ABCDE1234F',
        'bank_account' => '111122223333',
        'bank_ifsc' => 'HDFC0001111',
        'appointment_letter_path' => 'x.pdf',
    ]);

    $structure = SalaryStructure::create([
        'employee_id' => $employee->id,
        'effective_from' => '2026-01-01',
        'status' => 'active',
        'monthly_ctc' => 60000,
        'annual_ctc' => 720000,
    ]);

    SalaryComponent::create([
        'structure_id' => $structure->id,
        'code' => 'BASIC',
        'name' => 'Basic',
        'class' => ComponentClass::Earning,
        'amount_monthly' => 40000,
        'include_in_code_wages' => true,
        'include_in_epf' => true,
        'include_in_esi' => true,
        'include_in_pt' => true,
        'include_in_taxable' => true,
    ]);
    SalaryComponent::create([
        'structure_id' => $structure->id,
        'code' => 'HRA',
        'name' => 'HRA',
        'class' => ComponentClass::Earning,
        'amount_monthly' => 20000,
        'include_in_code_wages' => false,
        'include_in_epf' => false,
        'include_in_esi' => true,
        'include_in_pt' => true,
        'include_in_taxable' => true,
    ]);

    $structure->load('components');

    return [$employee, $structure];
}

it('prorates fixed earnings for LOP days', function () {
    [$employee, $structure] = makeEmployeeWithPay();

    $attendance = AttendanceSummary::create([
        'employee_id' => $employee->id,
        'period_year' => 2026,
        'period_month' => 10,
        'payable_days' => 30,
        'lop_days' => 1,
        'overtime_hours' => 0,
        'status' => 'approved',
    ]);

    $calc = new PayrollCalculator(new WageBaseCalculator, new StatutoryEngine);
    $result = $calc->calculate($employee, $attendance, $structure);

    // October 2026 has 31 days; payable 30 → factor 30/31
    expect($result['gross'])->toBeLessThan(60000)
        ->and($result['gross'])->toBeGreaterThan(58000);
});

it('applies overtime at double hourly rate', function () {
    [$employee, $structure] = makeEmployeeWithPay();

    $attendance = AttendanceSummary::create([
        'employee_id' => $employee->id,
        'period_year' => 2026,
        'period_month' => 10,
        'payable_days' => 31,
        'lop_days' => 0,
        'overtime_hours' => 2,
        'overtime_consent' => true,
        'status' => 'approved',
    ]);

    $calc = new PayrollCalculator(new WageBaseCalculator, new StatutoryEngine);
    $result = $calc->calculate($employee, $attendance, $structure);

    $ot = collect($result['lines'])->firstWhere('code', 'OT');
    expect($ot)->not->toBeNull()
        ->and($ot['amount'])->toBeGreaterThan(0);
});

it('computes professional tax for telangana slabs', function () {
    $engine = new StatutoryEngine;
    expect($engine->professionalTax(14000, []))->toBe(0.0)
        ->and($engine->professionalTax(18000, []))->toBe(150.0)
        ->and($engine->professionalTax(25000, []))->toBe(200.0);
});

it('recovers active loans and caps against deduction percent', function () {
    [$employee, $structure] = makeEmployeeWithPay();

    $loan = \App\Models\Loan::create([
        'employee_id' => $employee->id,
        'type' => 'advance',
        'principal' => 50000,
        'interest_rate' => 0,
        'tenure_months' => 5,
        'monthly_recovery' => 40000,
        'outstanding' => 50000,
        'status' => 'active',
        'started_on' => '2026-10-01',
    ]);

    $attendance = AttendanceSummary::create([
        'employee_id' => $employee->id,
        'period_year' => 2026,
        'period_month' => 10,
        'payable_days' => 31,
        'lop_days' => 0,
        'overtime_hours' => 0,
        'status' => 'approved',
    ]);

    $calc = new PayrollCalculator(new WageBaseCalculator, new StatutoryEngine);
    $result = $calc->calculate($employee, $attendance, $structure);

    $loanLine = collect($result['lines'])->firstWhere('code', 'LOAN_'.$loan->id);
    $cfLine = collect($result['lines'])->first(fn ($l) => $l['code'] === 'LOAN_CF_'.$loan->id);

    expect($loanLine)->not->toBeNull()
        ->and($loanLine['amount'])->toBeGreaterThan(0)
        ->and($loanLine['amount'])->toBeLessThan(40000)
        ->and($result['total_deductions'])->toBeLessThanOrEqual($result['gross'] * 0.5 + 0.01)
        ->and($cfLine)->not->toBeNull()
        ->and($cfLine['meta']['carried_forward'])->toBeGreaterThan(0);
});

it('includes structure deduction and employer component classes', function () {
    [$employee, $structure] = makeEmployeeWithPay();

    SalaryComponent::create([
        'structure_id' => $structure->id,
        'code' => 'CANTEEN',
        'name' => 'Canteen',
        'class' => ComponentClass::Deduction,
        'amount_monthly' => 500,
        'is_fixed' => true,
        'sort_order' => 10,
    ]);
    SalaryComponent::create([
        'structure_id' => $structure->id,
        'code' => 'GRAT_PROV',
        'name' => 'Gratuity provision',
        'class' => ComponentClass::Employer,
        'amount_monthly' => 1000,
        'is_fixed' => true,
        'sort_order' => 11,
    ]);
    $structure->load('components');

    $attendance = AttendanceSummary::create([
        'employee_id' => $employee->id,
        'period_year' => 2026,
        'period_month' => 10,
        'payable_days' => 31,
        'lop_days' => 0,
        'overtime_hours' => 0,
        'status' => 'approved',
    ]);

    $result = (new PayrollCalculator(new WageBaseCalculator, new StatutoryEngine))
        ->calculate($employee, $attendance, $structure);

    $canteen = collect($result['lines'])->firstWhere('code', 'CANTEEN');
    $grat = collect($result['lines'])->firstWhere('code', 'GRAT_PROV');

    expect($canteen['line_type'])->toBe(ComponentClass::Deduction->value)
        ->and($canteen['amount'])->toBe(500.0)
        ->and($grat['line_type'])->toBe(ComponentClass::Employer->value)
        ->and($grat['amount'])->toBe(1000.0);
});
