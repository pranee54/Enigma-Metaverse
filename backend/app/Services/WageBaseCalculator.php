<?php

namespace App\Services;

use App\Enums\ComponentClass;
use App\Models\SalaryStructure;
use Illuminate\Support\Collection;

class WageBaseCalculator
{
    /**
     * @return array{total_remuneration: float, code_wages: float, epf_wages: float, esi_wages: float, gratuity_wages: float, bonus_wages: float, pt_gross: float, taxable: float, excluded_total: float, fifty_percent_addback: float, warning: ?string}
     */
    public function summarize(SalaryStructure $structure): array
    {
        $components = $structure->relationLoaded('components')
            ? $structure->components
            : $structure->components()->get();

        $earnings = $components->where('class', ComponentClass::Earning);
        $totalRemuneration = (float) $earnings->sum('amount_monthly');

        $codeWages = (float) $earnings->where('include_in_code_wages', true)->sum('amount_monthly');
        $excluded = $totalRemuneration - $codeWages;

        // 50% test: excluded components cannot exceed 50% of total remuneration
        $addback = 0.0;
        $warning = null;
        if ($totalRemuneration > 0 && $excluded > ($totalRemuneration * 0.5)) {
            $addback = round($excluded - ($totalRemuneration * 0.5), 2);
            $codeWages += $addback;
            $warning = "Excluded components exceed 50% of remuneration. Add-back of {$addback} applied to Code wages.";
        }

        return [
            'total_remuneration' => round($totalRemuneration, 2),
            'code_wages' => round($codeWages, 2),
            'epf_wages' => round((float) $earnings->where('include_in_epf', true)->sum('amount_monthly'), 2),
            'esi_wages' => round((float) $earnings->where('include_in_esi', true)->sum('amount_monthly'), 2),
            'gratuity_wages' => round((float) $earnings->where('include_in_gratuity', true)->sum('amount_monthly'), 2),
            'bonus_wages' => round((float) $earnings->where('include_in_bonus', true)->sum('amount_monthly'), 2),
            'pt_gross' => round((float) $earnings->where('include_in_pt', true)->sum('amount_monthly'), 2),
            'taxable' => round((float) $earnings->where('include_in_taxable', true)->sum('amount_monthly'), 2),
            'excluded_total' => round($excluded, 2),
            'fifty_percent_addback' => $addback,
            'warning' => $warning,
        ];
    }

    public function earningComponents(SalaryStructure $structure): Collection
    {
        return $structure->components->where('class', ComponentClass::Earning);
    }
}
