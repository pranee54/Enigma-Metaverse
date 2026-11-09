<?php

namespace App\Services;

use App\Enums\ComponentClass;
use App\Models\AttendanceSummary;
use App\Models\Configuration;
use App\Models\Employee;
use App\Models\Loan;
use App\Models\SalaryStructure;
use App\Models\StatutoryRuleVersion;
use Carbon\Carbon;

class PayrollCalculator
{
    public function __construct(
        private WageBaseCalculator $wageBases,
        private StatutoryEngine $statutory,
    ) {}

    /**
     * @return array{
     *   gross: float,
     *   total_deductions: float,
     *   net: float,
     *   employer_cost: float,
     *   carried_forward_deduction: float,
     *   lines: list<array{line_type: string, code: string, label: string, amount: float, meta?: array}>,
     *   status: string,
     *   hold_reason: ?string,
     *   rule_version_id: int,
     *   structure_id: int
     * }
     */
    public function calculate(Employee $employee, AttendanceSummary $attendance, ?SalaryStructure $structure = null): array
    {
        $structure ??= $employee->salaryStructures()
            ->where('status', 'active')
            ->where('effective_from', '<=', Carbon::create($attendance->period_year, $attendance->period_month, 1)->endOfMonth())
            ->orderByDesc('effective_from')
            ->with('components')
            ->first();

        if (! $structure) {
            return $this->holdResult('No active salary structure');
        }

        $asOf = Carbon::create($attendance->period_year, $attendance->period_month, 1);
        $rule = $this->statutory->rulesFor($asOf);
        $divisor = $this->prorationDivisor($attendance->period_year, $attendance->period_month);
        $payable = (float) $attendance->payable_days;

        $lines = [];
        $gross = 0.0;

        foreach ($structure->components->where('class', ComponentClass::Earning) as $component) {
            $amount = (float) $component->amount_monthly;
            if ($component->is_fixed) {
                $amount = round(min($amount, $amount * $payable / $divisor), 2);
            }
            $lines[] = [
                'line_type' => ComponentClass::Earning->value,
                'code' => $component->code,
                'label' => $component->name,
                'amount' => $amount,
            ];
            $gross += $amount;
        }

        // Overtime: hours × (code_wages / (divisor × daily_hours)) × multiplier
        $basesMonthly = $this->wageBases->summarize($structure);
        $dailyHours = (float) $employee->standard_daily_hours;
        $otHours = (float) $attendance->overtime_hours;
        if ($otHours > 0) {
            $multiplier = (float) (Configuration::getValue('overtime_multiplier', 2));
            $hourly = $basesMonthly['code_wages'] / ($divisor * max($dailyHours, 1));
            $otPay = round($otHours * $hourly * $multiplier, 2);
            $lines[] = [
                'line_type' => ComponentClass::Earning->value,
                'code' => 'OT',
                'label' => 'Overtime',
                'amount' => $otPay,
                'meta' => ['hours' => $otHours, 'hourly' => round($hourly, 4), 'multiplier' => $multiplier],
            ];
            $gross += $otPay;
        }

        if ((float) $attendance->leave_encashment_days > 0) {
            $daily = $basesMonthly['code_wages'] / $divisor;
            $encash = round((float) $attendance->leave_encashment_days * $daily, 2);
            $lines[] = [
                'line_type' => ComponentClass::Earning->value,
                'code' => 'LEAVE_ENCASH',
                'label' => 'Leave encashment',
                'amount' => $encash,
            ];
            $gross += $encash;
        }

        // Prorate wage bases for statutory
        $factor = $payable / $divisor;
        $periodBases = [
            'epf_wages' => round($basesMonthly['epf_wages'] * $factor, 2),
            'esi_wages' => round($basesMonthly['esi_wages'] * $factor, 2),
            'pt_gross' => round($basesMonthly['pt_gross'] * $factor, 2),
            'code_wages' => round($basesMonthly['code_wages'] * $factor, 2),
        ];

        $stat = $this->statutory->contributions($employee, $periodBases, $rule);
        $deductions = 0.0;
        $employerCost = $gross;

        foreach ($stat['employee'] as $line) {
            $lines[] = [
                'line_type' => ComponentClass::Deduction->value,
                'code' => $line['code'],
                'label' => $line['label'],
                'amount' => $line['amount'],
            ];
            $deductions += $line['amount'];
        }

        foreach ($stat['employer'] as $line) {
            $lines[] = [
                'line_type' => ComponentClass::Employer->value,
                'code' => $line['code'],
                'label' => $line['label'],
                'amount' => $line['amount'],
            ];
            $employerCost += $line['amount'];
        }

        // Structure-level deduction / employer components (enum cases used beyond earnings)
        foreach ($structure->components->where('class', ComponentClass::Deduction) as $component) {
            $amount = (float) $component->amount_monthly;
            if ($component->is_fixed) {
                $amount = round($amount * $payable / $divisor, 2);
            }
            $lines[] = [
                'line_type' => ComponentClass::Deduction->value,
                'code' => $component->code,
                'label' => $component->name,
                'amount' => $amount,
            ];
            $deductions += $amount;
        }

        foreach ($structure->components->where('class', ComponentClass::Employer) as $component) {
            $amount = (float) $component->amount_monthly;
            if ($component->is_fixed) {
                $amount = round($amount * $payable / $divisor, 2);
            }
            $lines[] = [
                'line_type' => ComponentClass::Employer->value,
                'code' => $component->code,
                'label' => $component->name,
                'amount' => $amount,
            ];
            $employerCost += $amount;
        }

        // Loan recoveries with wage deduction cap
        $capPct = (float) (Configuration::getValue('deduction_cap_percent', 50)) / 100;
        $cap = round($gross * $capPct, 2);
        $room = max(0, $cap - $deductions);
        $carried = 0.0;

        $loans = Loan::query()
            ->where('employee_id', $employee->id)
            ->where('status', 'active')
            ->where('outstanding', '>', 0)
            ->get();

        foreach ($loans as $loan) {
            $wanted = min((float) $loan->monthly_recovery, (float) $loan->outstanding);
            $take = min($wanted, $room);
            $carry = round($wanted - $take, 2);
            $carried += $carry;
            $room = round($room - $take, 2);

            if ($take > 0) {
                $lines[] = [
                    'line_type' => ComponentClass::Deduction->value,
                    'code' => 'LOAN_'.$loan->id,
                    'label' => 'Loan / advance recovery',
                    'amount' => $take,
                    'meta' => ['loan_id' => $loan->id],
                ];
                $deductions += $take;
            }

            if ($carry > 0) {
                $lines[] = [
                    'line_type' => ComponentClass::Deduction->value,
                    'code' => 'LOAN_CF_'.$loan->id,
                    'label' => 'Deduction carry-forward (not recovered)',
                    'amount' => 0,
                    'meta' => ['loan_id' => $loan->id, 'carried_forward' => $carry],
                ];
            }
        }

        $net = round($gross - $deductions, 2);
        $status = 'ok';
        $hold = null;

        if ($net < 0) {
            $status = 'hold';
            $hold = 'Negative net pay';
        }
        if (blank($employee->bank_account) || blank($employee->bank_ifsc)) {
            $status = 'hold';
            $hold = 'Missing bank details';
        }

        return [
            'gross' => round($gross, 2),
            'total_deductions' => round($deductions, 2),
            'net' => $net,
            'employer_cost' => round($employerCost, 2),
            'carried_forward_deduction' => round($carried, 2),
            'lines' => $lines,
            'status' => $status,
            'hold_reason' => $hold,
            'rule_version_id' => $rule->id,
            'structure_id' => $structure->id,
        ];
    }

    public function prorationDivisor(int $year, int $month): float
    {
        $mode = Configuration::getValue('proration_divisor', 'calendar');

        return match ($mode) {
            30, '30' => 30.0,
            26, '26' => 26.0,
            default => (float) Carbon::create($year, $month, 1)->daysInMonth,
        };
    }

    private function holdResult(string $reason): array
    {
        return [
            'gross' => 0,
            'total_deductions' => 0,
            'net' => 0,
            'employer_cost' => 0,
            'carried_forward_deduction' => 0,
            'lines' => [],
            'status' => 'hold',
            'hold_reason' => $reason,
            'rule_version_id' => StatutoryRuleVersion::query()->latest('id')->value('id') ?? 0,
            'structure_id' => 0,
        ];
    }
}
