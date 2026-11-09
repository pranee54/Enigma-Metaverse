<?php

namespace App\Services;

use App\Enums\EmployeeStatus;
use App\Exceptions\DomainException;
use App\Models\AttendanceSummary;
use App\Models\EmployeeExit;
use App\Models\Settlement;
use App\Models\User;
use Carbon\Carbon;

class SettlementService
{
    public function __construct(
        private PayrollCalculator $calculator,
        private AuditLogger $audit,
    ) {}

    public function createExit(array $data): EmployeeExit
    {
        $exit = EmployeeExit::create($data);
        $exit->employee->update(['status' => EmployeeStatus::Exiting]);
        $this->audit->log('exit.created', $exit);

        return $exit->load('employee');
    }

    public function generateStepOne(EmployeeExit $exit, User $user): Settlement
    {
        $employee = $exit->employee;
        $lwd = $exit->last_working_date;
        $year = (int) $lwd->format('Y');
        $month = (int) $lwd->format('n');

        $attendance = AttendanceSummary::query()
            ->where('employee_id', $employee->id)
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->where('status', 'approved')
            ->orderByDesc('version')
            ->first();

        if (! $attendance) {
            throw new DomainException(__('messages.errors.settlement_needs_attendance'));
        }

        $result = $this->calculator->calculate($employee, $attendance);

        $settlement = Settlement::create([
            'exit_id' => $exit->id,
            'step' => 1,
            'undisputed_wages' => $result['net'],
            'other_components_json' => [
                'note' => 'Step-2 components (gratuity, bond, assets) require separate approval — not auto-applied.',
            ],
            'tax_amount' => 0,
            'net_payable' => $result['net'],
            'due_by' => Carbon::parse($lwd)->addWeekdays(2)->toDateString(),
        ]);

        $exit->update(['status' => 'settlement_step1']);
        $this->audit->log('settlement.step1', $settlement, null, ['by' => $user->id]);

        return $settlement;
    }
}
