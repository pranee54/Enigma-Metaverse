<?php

namespace App\Services;

use App\Enums\EmploymentCategory;
use App\Models\Employee;
use App\Models\StatutoryRuleVersion;
use Carbon\CarbonInterface;

class StatutoryEngine
{
    public function rulesFor(CarbonInterface $asOf): StatutoryRuleVersion
    {
        $rule = StatutoryRuleVersion::query()
            ->where('code', 'default')
            ->where('effective_from', '<=', $asOf->toDateString())
            ->orderByDesc('effective_from')
            ->first();

        if (! $rule) {
            throw new \RuntimeException('No statutory rule version found for period.');
        }

        return $rule;
    }

    public function appliesStatutes(Employee $employee): bool
    {
        return $employee->employment_category !== EmploymentCategory::Apprentice;
    }

    /**
     * @param  array{epf_wages: float, esi_wages: float, pt_gross: float, code_wages: float}  $bases
     * @return array{employee: list<array{code: string, label: string, amount: float}>, employer: list<array{code: string, label: string, amount: float}>}
     */
    public function contributions(Employee $employee, array $bases, StatutoryRuleVersion $rule): array
    {
        $p = $rule->payload_json;
        $employeeLines = [];
        $employerLines = [];

        if (! $this->appliesStatutes($employee)) {
            return ['employee' => $employeeLines, 'employer' => $employerLines];
        }

        $epfCeiling = (float) ($p['epf_ceiling'] ?? 25000);
        $epfRateEmp = (float) ($p['epf_employee_rate'] ?? 0.12);
        $epfRateEr = (float) ($p['epf_employer_rate'] ?? 0.12);
        $epsRate = (float) ($p['eps_rate'] ?? 0.0833);

        $epfWages = min($bases['epf_wages'], $epfCeiling);
        if ($epfWages > 0) {
            $empPf = round($epfWages * $epfRateEmp, 2);
            $erPfTotal = round($epfWages * $epfRateEr, 2);
            $eps = round(min($epfWages, $epfCeiling) * $epsRate, 2);
            $erPf = round($erPfTotal - $eps, 2);

            $employeeLines[] = ['code' => 'EPF', 'label' => 'Employee Provident Fund', 'amount' => $empPf];
            $employerLines[] = ['code' => 'EPF_ER', 'label' => 'Employer PF', 'amount' => $erPf];
            $employerLines[] = ['code' => 'EPS', 'label' => 'Employer Pension (EPS)', 'amount' => $eps];
        }

        $esiThreshold = (float) ($p['esi_threshold'] ?? 21000);
        $esiEmpRate = (float) ($p['esi_employee_rate'] ?? 0.0075);
        $esiErRate = (float) ($p['esi_employer_rate'] ?? 0.0325);
        if ($bases['esi_wages'] > 0 && $bases['esi_wages'] <= $esiThreshold) {
            $employeeLines[] = [
                'code' => 'ESI',
                'label' => 'Employee State Insurance',
                'amount' => round($bases['esi_wages'] * $esiEmpRate, 2),
            ];
            $employerLines[] = [
                'code' => 'ESI_ER',
                'label' => 'Employer ESI',
                'amount' => round($bases['esi_wages'] * $esiErRate, 2),
            ];
        }

        $pt = $this->professionalTax($bases['pt_gross'], $p['pt_slabs'] ?? []);
        if ($pt > 0) {
            $employeeLines[] = ['code' => 'PT', 'label' => 'Professional Tax (Telangana)', 'amount' => $pt];
        }

        return ['employee' => $employeeLines, 'employer' => $employerLines];
    }

    /**
     * @param  list<array{upto: float|null, amount: float}>  $slabs
     */
    public function professionalTax(float $ptGross, array $slabs): float
    {
        if ($slabs === []) {
            // Telangana defaults from assignment
            if ($ptGross <= 15000) {
                return 0;
            }
            if ($ptGross <= 20000) {
                return 150;
            }

            return 200;
        }

        foreach ($slabs as $slab) {
            $upto = $slab['upto'] ?? null;
            if ($upto === null || $ptGross <= (float) $upto) {
                return (float) $slab['amount'];
            }
        }

        return 0;
    }
}
