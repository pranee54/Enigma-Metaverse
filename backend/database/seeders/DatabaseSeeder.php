<?php

namespace Database\Seeders;

use App\Enums\ComponentClass;
use App\Enums\EmployeeStatus;
use App\Enums\EmploymentCategory;
use App\Models\AttendanceImportBatch;
use App\Models\AttendanceSummary;
use App\Models\Configuration;
use App\Models\Employee;
use App\Models\SalaryComponent;
use App\Models\SalaryStructure;
use App\Models\StatutoryRuleVersion;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['hr', 'finance', 'employee', 'auditor'] as $role) {
            Role::findOrCreate($role);
        }

        Configuration::query()->upsert([
            ['key' => 'company_name', 'value_json' => json_encode('Enigma Payroll'), 'description' => 'Company display name', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'company_state', 'value_json' => json_encode('Telangana'), 'description' => 'Default state for PT / compliance', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'currency', 'value_json' => json_encode('INR'), 'description' => 'Payroll currency', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'proration_divisor', 'value_json' => json_encode('calendar'), 'description' => 'calendar|30|26', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'overtime_multiplier', 'value_json' => json_encode(2), 'description' => 'OT multiplier', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'deduction_cap_percent', 'value_json' => json_encode(50), 'description' => 'Max wage deductions %', 'created_at' => now(), 'updated_at' => now()],
        ], ['key'], ['value_json', 'description', 'updated_at']);

        StatutoryRuleVersion::query()->updateOrCreate(
            ['code' => 'default', 'effective_from' => '2026-09-17'],
            [
                'notes' => 'Telangana defaults post EPF ceiling change',
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

        StatutoryRuleVersion::query()->updateOrCreate(
            ['code' => 'default', 'effective_from' => '2024-01-01'],
            [
                'notes' => 'Earlier EPF ceiling 15000',
                'payload_json' => [
                    'epf_ceiling' => 15000,
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

        $hr = User::query()->updateOrCreate(
            ['email' => 'hr@enigma.test'],
            ['name' => 'Priya HR', 'password' => Hash::make('password')]
        );
        $hr->syncRoles(['hr']);

        $finance = User::query()->updateOrCreate(
            ['email' => 'finance@enigma.test'],
            ['name' => 'Arjun Finance', 'password' => Hash::make('password')]
        );
        $finance->syncRoles(['finance']);

        $auditor = User::query()->updateOrCreate(
            ['email' => 'auditor@enigma.test'],
            ['name' => 'Meera Auditor', 'password' => Hash::make('password')]
        );
        $auditor->syncRoles(['auditor']);

        $period = Carbon::create(2026, 10, 1);
        $daysInMonth = $period->daysInMonth;

        $people = [
            ['EMP001', 'Rahul', 'Sharma', 'Engineering', 'Software Engineer', 45000, 15000, 5000],
            ['EMP002', 'Sneha', 'Reddy', 'HR', 'HR Executive', 35000, 10000, 4000],
            ['EMP003', 'Vikram', 'Patel', 'Finance', 'Accountant', 40000, 12000, 4000],
            ['EMP004', 'Ananya', 'Iyer', 'Engineering', 'QA Analyst', 32000, 8000, 3000],
            ['EMP005', 'Kiran', 'Nair', 'Operations', 'Ops Lead', 50000, 16000, 6000],
        ];

        $employeeUser = null;

        $seedBatch = AttendanceImportBatch::query()->updateOrCreate(
            ['batch_code' => 'ATT-SEED-2026-10'],
            [
                'source' => 'seed',
                'importer_id' => $hr->id,
                'period_year' => 2026,
                'period_month' => 10,
                'row_count' => count($people),
                'accepted_count' => count($people),
                'rejected_count' => 0,
                'status' => 'approved',
                'report_json' => [
                    'rows' => collect($people)->map(fn ($p, $i) => [
                        'line' => $i + 2,
                        'ok' => true,
                        'employee_code' => $p[0],
                    ])->all(),
                ],
            ]
        );

        foreach ($people as $i => [$code, $first, $last, $dept, $desig, $basic, $hra, $special]) {
            $employee = Employee::query()->updateOrCreate(
                ['employee_code' => $code],
                [
                    'first_name' => $first,
                    'last_name' => $last,
                    'dob' => '1992-05-15',
                    'email' => strtolower($first).'@enigma.test',
                    'phone' => '900000000'.($i + 1),
                    'department' => $dept,
                    'designation' => $desig,
                    'work_location' => 'Hyderabad',
                    'state_code' => 'TG',
                    'employment_category' => EmploymentCategory::Permanent,
                    'joining_date' => '2024-01-10',
                    'status' => EmployeeStatus::Active,
                    'pan' => 'ABCDE123'.($i + 1).'F',
                    'uan' => '10000000000'.($i + 1),
                    'esic_number' => 'ESIC00'.($i + 1),
                    'bank_account' => '1234567890'.($i + 1),
                    'bank_ifsc' => 'HDFC0001234',
                    'bank_name' => 'HDFC Bank',
                    'tax_regime' => 'new',
                    'appointment_letter_path' => 'seeded/appointment-'.$code.'.pdf',
                    'created_by' => $hr->id,
                ]
            );

            $structure = SalaryStructure::query()->updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'effective_from' => '2026-04-01',
                    'status' => 'active',
                ],
                [
                    'monthly_ctc' => $basic + $hra + $special,
                    'annual_ctc' => ($basic + $hra + $special) * 12,
                    'created_by' => $hr->id,
                    'approved_by' => $finance->id,
                    'approved_at' => now(),
                ]
            );

            $structure->components()->delete();
            foreach ([
                ['BASIC', 'Basic', $basic, true, true, true, true, true, true, true],
                ['HRA', 'House Rent Allowance', $hra, false, false, true, false, false, true, true],
                ['SPECIAL', 'Special Allowance', $special, true, true, true, false, false, true, true],
            ] as $j => [$c, $n, $amt, $codeW, $epf, $esi, $grat, $bonus, $pt, $tax]) {
                SalaryComponent::create([
                    'structure_id' => $structure->id,
                    'code' => $c,
                    'name' => $n,
                    'class' => ComponentClass::Earning,
                    'amount_monthly' => $amt,
                    'is_fixed' => true,
                    'include_in_code_wages' => $codeW,
                    'include_in_epf' => $epf,
                    'include_in_esi' => $esi,
                    'include_in_gratuity' => $grat,
                    'include_in_bonus' => $bonus,
                    'include_in_pt' => $pt,
                    'include_in_taxable' => $tax,
                    'sort_order' => $j,
                ]);
            }

            AttendanceSummary::query()->updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'period_year' => 2026,
                    'period_month' => 10,
                    'version' => 1,
                ],
                [
                    'batch_id' => $seedBatch->id,
                    'payable_days' => $daysInMonth,
                    'lop_days' => 0,
                    'overtime_hours' => $i === 0 ? 4 : 0,
                    'overtime_consent' => $i === 0,
                    'leave_encashment_days' => 0,
                    'maternity_benefit_days' => 0,
                    'remarks' => 'Seeded demo attendance',
                    'status' => 'approved',
                    'approved_by' => $hr->id,
                    'approved_at' => now(),
                ]
            );

            if ($i === 0) {
                $employeeUser = User::query()->updateOrCreate(
                    ['email' => 'emp@enigma.test'],
                    [
                        'name' => $employee->full_name,
                        'password' => Hash::make('password'),
                        'employee_id' => $employee->id,
                    ]
                );
                $employeeUser->syncRoles(['employee']);
            }
        }
    }
}
