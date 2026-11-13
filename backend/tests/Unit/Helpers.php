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
use App\Models\User;
use Spatie\Permission\Models\Role;

function unitSeedConfigs(): void
{
    Configuration::query()->firstOrCreate(
        ['key' => 'proration_divisor'],
        ['value_json' => 'calendar']
    );
    Configuration::query()->firstOrCreate(
        ['key' => 'overtime_multiplier'],
        ['value_json' => 2]
    );
    Configuration::query()->firstOrCreate(
        ['key' => 'deduction_cap_percent'],
        ['value_json' => 50]
    );
}

function unitSeedStatutory(): StatutoryRuleVersion
{
    return StatutoryRuleVersion::query()->firstOrCreate(
        ['code' => 'default', 'effective_from' => '2026-01-01'],
        [
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
        ]
    );
}

function unitEmployee(string $code = 'U001', EmploymentCategory $category = EmploymentCategory::Permanent): Employee
{
    return Employee::create([
        'employee_code' => $code,
        'first_name' => 'Unit',
        'last_name' => 'Test',
        'dob' => '1990-01-01',
        'designation' => 'Dev',
        'work_location' => 'Hyderabad',
        'state_code' => 'TG',
        'employment_category' => $category,
        'joining_date' => '2024-01-01',
        'status' => EmployeeStatus::Active,
        'pan' => 'ABCDE1234F',
        'bank_account' => '111122223333',
        'bank_ifsc' => 'HDFC0001111',
        'appointment_letter_path' => 'x.pdf',
    ]);
}

function unitSalaryStructure(Employee $employee, float $basic = 40000, float $hra = 20000): SalaryStructure
{
    $structure = SalaryStructure::create([
        'employee_id' => $employee->id,
        'effective_from' => '2026-01-01',
        'status' => 'active',
        'monthly_ctc' => $basic + $hra,
        'annual_ctc' => ($basic + $hra) * 12,
    ]);

    SalaryComponent::create([
        'structure_id' => $structure->id,
        'code' => 'BASIC',
        'name' => 'Basic',
        'class' => ComponentClass::Earning,
        'amount_monthly' => $basic,
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
        'amount_monthly' => $hra,
        'include_in_code_wages' => false,
        'include_in_epf' => false,
        'include_in_esi' => true,
        'include_in_pt' => true,
        'include_in_taxable' => true,
    ]);

    return $structure->load('components');
}

function unitAttendance(Employee $employee, int $year = 2026, int $month = 10, float $payable = 31, float $lop = 0): AttendanceSummary
{
    return AttendanceSummary::create([
        'employee_id' => $employee->id,
        'period_year' => $year,
        'period_month' => $month,
        'payable_days' => $payable,
        'lop_days' => $lop,
        'overtime_hours' => 0,
        'status' => 'approved',
    ]);
}

function unitUserWithRole(string $role, ?string $email = null): User
{
    Role::findOrCreate($role);
    $user = User::factory()->create([
        'email' => $email ?? "{$role}-".uniqid('', true).'@enigma.test',
    ]);
    $user->assignRole($role);

    return $user;
}
