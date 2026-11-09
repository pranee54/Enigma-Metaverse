<?php

namespace App\Services;

use App\Enums\EmployeeStatus;
use App\Enums\PayrollState;
use App\Exceptions\DomainException;
use App\Models\AttendanceSummary;
use App\Models\Employee;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\PayrollItem;
use App\Models\PayrollItemLine;
use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PayrollRunService
{
    public function __construct(
        private PayrollCalculator $calculator,
        private AuditLogger $audit,
    ) {}

    public function create(int $year, int $month, User $user): PayrollRun
    {
        if (PayrollRun::query()->where('period_year', $year)->where('period_month', $month)->exists()) {
            throw new DomainException(__('messages.errors.payroll_exists'));
        }

        $run = PayrollRun::create([
            'period_year' => $year,
            'period_month' => $month,
            'state' => PayrollState::Draft,
            'prepared_by' => $user->id,
        ]);

        $this->audit->log('payroll.created', $run);

        return $run;
    }

    public function calculate(PayrollRun $run): PayrollRun
    {
        if (! in_array($run->state, [PayrollState::Draft, PayrollState::Calculated, PayrollState::Validated], true)) {
            throw new DomainException(__('messages.errors.payroll_calc_before_review'));
        }

        DB::transaction(function () use ($run) {
            $run->items()->each(function (PayrollItem $item) {
                $item->lines()->delete();
                $item->delete();
            });

            $employees = Employee::query()
                ->whereIn('status', [EmployeeStatus::Active, EmployeeStatus::Exiting])
                ->get();

            foreach ($employees as $employee) {
                $attendance = AttendanceSummary::query()
                    ->where('employee_id', $employee->id)
                    ->where('period_year', $run->period_year)
                    ->where('period_month', $run->period_month)
                    ->where('status', 'approved')
                    ->orderByDesc('version')
                    ->first();

                if (! $attendance) {
                    PayrollItem::create([
                        'run_id' => $run->id,
                        'employee_id' => $employee->id,
                        'status' => 'hold',
                        'hold_reason' => 'Missing approved attendance summary',
                    ]);
                    continue;
                }

                $result = $this->calculator->calculate($employee, $attendance);

                $item = PayrollItem::create([
                    'run_id' => $run->id,
                    'employee_id' => $employee->id,
                    'attendance_summary_id' => $attendance->id,
                    'structure_id' => $result['structure_id'] ?: null,
                    'rule_version_id' => $result['rule_version_id'] ?: null,
                    'gross' => $result['gross'],
                    'total_deductions' => $result['total_deductions'],
                    'net' => $result['net'],
                    'employer_cost' => $result['employer_cost'],
                    'carried_forward_deduction' => $result['carried_forward_deduction'],
                    'status' => $result['status'],
                    'hold_reason' => $result['hold_reason'],
                ]);

                foreach ($result['lines'] as $line) {
                    PayrollItemLine::create([
                        'item_id' => $item->id,
                        'line_type' => $line['line_type'],
                        'code' => $line['code'],
                        'label' => $line['label'],
                        'amount' => $line['amount'],
                        'meta_json' => $line['meta'] ?? null,
                    ]);
                }
            }

            $run->update([
                'state' => PayrollState::Calculated,
                'calculated_at' => now(),
            ]);
        });

        $this->audit->log('payroll.calculated', $run);

        return $run->fresh('items');
    }

    public function validate(PayrollRun $run): PayrollRun
    {
        $this->assertTransition($run, PayrollState::Validated);

        if ($run->items()->count() === 0) {
            throw new DomainException(__('messages.errors.payroll_calc_before_validate'));
        }

        $holds = $run->items()->where('status', 'hold')->count();
        $payable = $run->items()->where('status', '!=', 'hold')->count();

        if ($payable === 0) {
            throw new DomainException(__('messages.errors.payroll_all_hold'));
        }

        $run->update([
            'state' => PayrollState::Validated,
            'notes' => $holds > 0 ? "{$holds} employee(s) on hold; {$payable} payable." : null,
        ]);
        $this->audit->log('payroll.validated', $run, null, ['holds' => $holds, 'payable' => $payable]);

        return $run;
    }

    public function submitReview(PayrollRun $run): PayrollRun
    {
        $this->assertTransition($run, PayrollState::UnderReview);

        if ($run->items()->where('status', '!=', 'hold')->count() === 0) {
            throw new DomainException(__('messages.errors.payroll_no_payable'));
        }

        $run->update(['state' => PayrollState::UnderReview]);
        $this->audit->log('payroll.submitted', $run);

        return $run;
    }

    public function approve(PayrollRun $run, User $approver): PayrollRun
    {
        $this->assertTransition($run, PayrollState::Approved);

        if (! $approver->hasRole('finance')) {
            throw new DomainException(__('messages.errors.payroll_finance_approve'));
        }

        if ($run->prepared_by && $run->prepared_by === $approver->id) {
            throw new DomainException(__('messages.errors.payroll_maker_checker'));
        }

        $run->update([
            'state' => PayrollState::Approved,
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        $this->audit->log('payroll.approved', $run);

        return $run;
    }

    public function finalise(PayrollRun $run, ?User $actor = null): PayrollRun
    {
        if ($actor && ! $actor->hasRole('finance')) {
            throw new DomainException(__('messages.errors.payroll_finance_finalise'));
        }

        $this->assertTransition($run, PayrollState::Finalised);
        $run->update([
            'state' => PayrollState::Finalised,
            'finalised_at' => now(),
        ]);
        $this->audit->log('payroll.finalised', $run);

        return $run;
    }

    public function markPaid(PayrollRun $run, ?User $actor = null): PayrollRun
    {
        if ($actor && ! $actor->hasRole('finance')) {
            throw new DomainException(__('messages.errors.payroll_finance_paid'));
        }

        $this->assertTransition($run, PayrollState::Paid);

        DB::transaction(function () use ($run) {
            foreach ($run->items()->with('lines')->get() as $item) {
                foreach ($item->lines as $line) {
                    if (! str_starts_with($line->code, 'LOAN_') || str_starts_with($line->code, 'LOAN_CF_')) {
                        continue;
                    }
                    $loanId = $line->meta_json['loan_id'] ?? null;
                    if (! $loanId || $line->amount <= 0) {
                        continue;
                    }
                    $loan = Loan::query()->find($loanId);
                    if (! $loan) {
                        continue;
                    }
                    $loan->outstanding = max(0, (float) $loan->outstanding - (float) $line->amount);
                    if ($loan->outstanding <= 0) {
                        $loan->status = 'closed';
                        $loan->outstanding = 0;
                    }
                    $loan->save();

                    LoanRepayment::create([
                        'loan_id' => $loan->id,
                        'payroll_item_id' => $item->id,
                        'amount' => $line->amount,
                        'period_year' => $run->period_year,
                        'period_month' => $run->period_month,
                    ]);
                }
            }

            $run->update([
                'state' => PayrollState::Paid,
                'paid_at' => now(),
            ]);
        });

        $this->audit->log('payroll.paid', $run);

        return $run->fresh();
    }

    public function markReconciled(PayrollRun $run, User $user): PayrollRun
    {
        if (! $user->hasRole('finance')) {
            throw new DomainException(__('messages.errors.payroll_finance_reconcile'));
        }

        $this->assertTransition($run, PayrollState::Reconciled);

        $run->update(['state' => PayrollState::Reconciled]);
        $this->audit->log('payroll.reconciled', $run, null, ['by' => $user->id]);

        return $run->fresh();
    }

    private function assertTransition(PayrollRun $run, PayrollState $next): void
    {
        if (! $run->state->canTransitionTo($next)) {
            throw new DomainException(__('messages.errors.payroll_bad_transition', ['from' => $run->state->value, 'to' => $next->value]));
        }
    }
}
