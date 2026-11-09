<?php

namespace App\Http\Controllers\Web;

use App\Enums\PayrollState;
use App\Http\Controllers\Controller;
use App\Models\Configuration;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PayslipController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', PayrollItem::class);

        $user = $request->user();
        $visibleStates = [
            PayrollState::Approved->value,
            PayrollState::Finalised->value,
            PayrollState::Paid->value,
            PayrollState::Reconciled->value,
        ];

        $periods = $this->availablePeriods($user, $visibleStates);
        $selected = trim((string) $request->input('period', ''));

        if ($selected === '' && $periods->isNotEmpty()) {
            $selected = $periods->first()['value'];
        }

        if ($selected !== '' && $selected !== 'all' && ! $periods->contains(fn (array $p) => $p['value'] === $selected)) {
            $selected = $periods->first()['value'] ?? 'all';
        }

        // ponytail: join beats whereHas for list filters; stays readable
        $q = PayrollItem::query()
            ->select('payroll_items.*')
            ->with(['employee', 'run'])
            ->join('payroll_runs', 'payroll_runs.id', '=', 'payroll_items.run_id')
            ->where('payroll_items.status', '!=', 'hold')
            ->whereIn('payroll_runs.state', $visibleStates);

        if ($selected !== '' && $selected !== 'all') {
            [$year, $month] = array_map('intval', explode('-', $selected));
            $q->where('payroll_runs.period_year', $year)->where('payroll_runs.period_month', $month);
        }

        $this->scopeEmployeePayslips($q, $user, 'payroll_items.employee_id');

        return view('payslips.index', [
            'payslips' => $q->latest('payroll_items.id')->paginate(20)->withQueryString(),
            'periods' => $periods,
            'selectedPeriod' => $selected === '' ? 'all' : $selected,
        ]);
    }

    public function show(Request $request, PayrollItem $payslip)
    {
        $this->authorize('view', $payslip);

        $payslip->load(['employee', 'run', 'lines']);

        $visibleStates = [
            PayrollState::Approved,
            PayrollState::Finalised,
            PayrollState::Paid,
            PayrollState::Reconciled,
        ];

        if (! $payslip->run || ! in_array($payslip->run->state, $visibleStates, true)) {
            abort(404, __('messages.errors.payslip_after_approval'));
        }

        if ($payslip->status === 'hold') {
            abort(404, __('messages.errors.payslip_on_hold'));
        }

        return view('payslips.show', [
            'payslip' => $payslip,
            'companyName' => Configuration::getValue('company_name', 'Enigma Payroll'),
            'companyState' => Configuration::getValue('company_state', 'Telangana'),
        ]);
    }

    /**
     * @param  list<string>  $visibleStates
     * @return Collection<int, array{value: string, label: string}>
     */
    private function availablePeriods($user, array $visibleStates): Collection
    {
        $runs = PayrollRun::query()
            ->whereIn('state', $visibleStates)
            ->whereHas('items', function ($q) use ($user) {
                $q->where('status', '!=', 'hold');
                $this->scopeEmployeePayslips($q, $user, 'employee_id');
            })
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->get(['period_year', 'period_month']);

        return $runs
            ->unique(fn (PayrollRun $run) => sprintf('%04d-%02d', $run->period_year, $run->period_month))
            ->values()
            ->map(fn (PayrollRun $run) => [
                'value' => sprintf('%04d-%02d', $run->period_year, $run->period_month),
                'label' => Carbon::create($run->period_year, $run->period_month, 1)->format('M Y'),
            ]);
    }

    private function scopeEmployeePayslips($query, $user, string $column): void
    {
        if (! $user->hasRole('employee')) {
            return;
        }

        // Harden: employee role without linked employee_id must see nothing (not all payslips)
        if (! $user->employee_id) {
            $query->whereRaw('0 = 1');

            return;
        }

        $query->where($column, $user->employee_id);
    }
}
