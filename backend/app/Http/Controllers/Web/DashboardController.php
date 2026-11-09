<?php

namespace App\Http\Controllers\Web;

use App\Enums\EmployeeStatus;
use App\Enums\PayrollState;
use App\Http\Controllers\Controller;
use App\Models\AttendanceImportBatch;
use App\Models\AttendanceSummary;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Export;
use App\Models\Loan;
use App\Models\PayrollRun;
use App\Models\SalaryRevision;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = auth()->user();
        $role = $user->getRoleNames()->first() ?? 'user';
        $hrMonth = $role === 'hr' ? $this->hrMonthGuide() : null;

        return view('dashboard', [
            'role' => $role,
            'stats' => $this->statsFor($role, $hrMonth),
            'queues' => $role === 'hr' ? [] : $this->queuesFor($role),
            'hrMonth' => $hrMonth,
            'quickLinks' => $role === 'hr' ? [] : $this->quickLinksFor($role),
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $hrMonth
     * @return array<int, array{label: string, value: string|int, hint?: string}>
     */
    private function statsFor(string $role, ?array $hrMonth = null): array
    {
        return match ($role) {
            'finance' => [
                ['label' => __('messages.pages.dashboard.stat_awaiting'), 'value' => PayrollRun::query()->where('state', PayrollState::UnderReview)->count(), 'hint' => __('messages.pages.dashboard.stat_awaiting_hint')],
                ['label' => __('messages.pages.dashboard.stat_approved'), 'value' => PayrollRun::query()->where('state', PayrollState::Approved)->count(), 'hint' => __('messages.pages.dashboard.stat_approved_hint')],
                ['label' => __('messages.pages.dashboard.stat_revisions'), 'value' => SalaryRevision::query()->where('status', 'pending')->count(), 'hint' => __('messages.pages.dashboard.stat_revisions_hint')],
                ['label' => __('messages.pages.dashboard.stat_loans'), 'value' => Loan::query()->where('status', 'active')->count(), 'hint' => __('messages.pages.dashboard.stat_loans_hint')],
            ],
            'auditor' => [
                ['label' => __('messages.nav.employees'), 'value' => Employee::count()],
                ['label' => __('messages.pages.payroll.runs_title'), 'value' => PayrollRun::count()],
                ['label' => __('messages.pages.dashboard.stat_exports'), 'value' => Export::count()],
                ['label' => __('messages.pages.dashboard.stat_audit'), 'value' => AuditLog::count()],
            ],
            default => $this->hrStats($hrMonth),
        };
    }

    /**
     * @param  array<string, mixed>|null  $hrMonth
     * @return array<int, array{label: string, value: string|int, hint?: string}>
     */
    private function hrStats(?array $hrMonth): array
    {
        $activeEmployees = (int) ($hrMonth['activeEmployees'] ?? Employee::query()
            ->whereIn('status', [EmployeeStatus::Active, EmployeeStatus::Exiting])
            ->count());

        $batchesWaiting = AttendanceImportBatch::query()->where('status', 'imported')->count();
        $attendanceReady = (int) ($hrMonth['approvedAttendance'] ?? 0);
        $monthLabel = $hrMonth['label'] ?? 'this month';

        $openPayroll = PayrollRun::query()->whereIn('state', [
            PayrollState::Draft,
            PayrollState::Calculated,
            PayrollState::Validated,
        ])->count();

        $needsPayrollCreate = $hrMonth
            && $attendanceReady > 0
            && $hrMonth['run'] === null;

        $hrActionCount = $openPayroll + ($needsPayrollCreate ? 1 : 0) + $batchesWaiting;

        return [
            [
                'label' => __('messages.pages.dashboard.stat_active'),
                'value' => $activeEmployees,
                'hint' => __('messages.pages.dashboard.stat_active_hint'),
            ],
            [
                'label' => __('messages.pages.dashboard.stat_attendance', ['month' => $monthLabel]),
                'value' => $attendanceReady.'/'.$activeEmployees,
                'hint' => $batchesWaiting > 0
                    ? __('messages.pages.dashboard.stat_batch_waiting', ['count' => $batchesWaiting])
                    : ($attendanceReady >= $activeEmployees && $activeEmployees > 0
                        ? __('messages.pages.dashboard.stat_attendance_ready')
                        : __('messages.pages.dashboard.stat_attendance_more')),
            ],
            [
                'label' => __('messages.pages.dashboard.stat_actions'),
                'value' => $hrActionCount,
                'hint' => $needsPayrollCreate
                    ? __('messages.pages.dashboard.stat_create_run', ['month' => $monthLabel])
                    : ($openPayroll > 0
                        ? __('messages.pages.dashboard.stat_calc_validate')
                        : ($batchesWaiting > 0
                            ? __('messages.pages.dashboard.stat_approve_batches')
                            : __('messages.pages.dashboard.stat_nothing'))),
            ],
            [
                'label' => __('messages.pages.dashboard.stat_finance'),
                'value' => PayrollRun::query()->where('state', PayrollState::UnderReview)->count(),
                'hint' => __('messages.pages.dashboard.stat_finance_hint'),
            ],
        ];
    }

    /** @return array<int, array{label: string, href: string}> */
    private function quickLinksFor(string $role): array
    {
        return match ($role) {
            'finance' => [
                ['label' => __('messages.pages.dashboard.link_payroll_approve'), 'href' => route('payroll.index')],
                ['label' => __('messages.nav.revisions'), 'href' => route('revisions.index')],
                ['label' => __('messages.nav.exports'), 'href' => route('exports.index')],
                ['label' => __('messages.nav.payslips'), 'href' => route('payslips.index')],
            ],
            'auditor' => [
                ['label' => __('messages.pages.payroll.runs_title'), 'href' => route('payroll.index')],
                ['label' => __('messages.nav.exports'), 'href' => route('exports.index')],
                ['label' => __('messages.nav.employees'), 'href' => route('employees.index')],
            ],
            default => [
                ['label' => __('messages.nav.employees'), 'href' => route('employees.index')],
                ['label' => __('messages.pages.dashboard.link_import_attendance'), 'href' => route('attendance.index')],
                ['label' => __('messages.pages.dashboard.link_csv_demo'), 'href' => route('attendance.guide')],
                ['label' => __('messages.pages.payroll.runs_title'), 'href' => route('payroll.index')],
                ['label' => __('messages.nav.payslips'), 'href' => route('payslips.index')],
            ],
        };
    }

    /**
     * Focus month for HR = latest open payroll, else previous calendar month.
     *
     * @return array{
     *   label: string,
     *   year: int,
     *   month: int,
     *   run: ?PayrollRun,
     *   nextAction: string,
     *   nextHref: string,
     *   activeEmployees: int,
     *   approvedAttendance: int,
     *   steps: list<array{label: string, detail: string, done: bool, href: string}>
     * }
     */
    private function hrMonthGuide(): array
    {
        $run = PayrollRun::query()
            ->withCount('items')
            ->whereIn('state', [
                PayrollState::Draft,
                PayrollState::Calculated,
                PayrollState::Validated,
                PayrollState::UnderReview,
            ])
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->first();

        if (! $run) {
            $focus = now()->startOfMonth()->subMonth();
            $year = (int) $focus->year;
            $month = (int) $focus->month;
            $run = PayrollRun::query()
                ->withCount('items')
                ->where('period_year', $year)
                ->where('period_month', $month)
                ->first();
        } else {
            $year = (int) $run->period_year;
            $month = (int) $run->period_month;
        }

        $label = Carbon::create($year, $month, 1)->format('M Y');
        $activeEmployees = Employee::query()
            ->whereIn('status', [EmployeeStatus::Active, EmployeeStatus::Exiting])
            ->count();

        $approvedAttendance = AttendanceSummary::query()
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->where('status', 'approved')
            ->distinct()
            ->count('employee_id');

        $pendingBatch = AttendanceImportBatch::query()
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->where('status', 'imported')
            ->latest()
            ->first();

        $attendanceDone = $approvedAttendance >= $activeEmployees && $activeEmployees > 0;
        $hasRun = $run !== null;
        $itemCount = (int) ($run?->items_count ?? 0);
        $calculated = $run && $run->state !== PayrollState::Draft && $itemCount > 0;
        $validated = $run && in_array($run->state, [
            PayrollState::Validated,
            PayrollState::UnderReview,
            PayrollState::Approved,
            PayrollState::Finalised,
            PayrollState::Paid,
            PayrollState::Reconciled,
        ], true);
        $submitted = $run && in_array($run->state, [
            PayrollState::UnderReview,
            PayrollState::Approved,
            PayrollState::Finalised,
            PayrollState::Paid,
            PayrollState::Reconciled,
        ], true);

        $steps = [
            [
                'label' => __('messages.pages.dashboard.step1'),
                'detail' => $pendingBatch
                    ? __('messages.pages.dashboard.step1_batch', [
                        'code' => $pendingBatch->batch_code,
                        'ready' => $approvedAttendance,
                        'active' => $activeEmployees,
                    ])
                    : __('messages.pages.dashboard.step1_detail', [
                        'ready' => $approvedAttendance,
                        'active' => $activeEmployees,
                        'month' => $label,
                    ]),
                'done' => $attendanceDone,
                'href' => $pendingBatch
                    ? route('attendance.show', $pendingBatch)
                    : route('attendance.index'),
            ],
            [
                'label' => __('messages.pages.dashboard.step2'),
                'detail' => $hasRun
                    ? __('messages.pages.dashboard.step2_open', ['state' => $run->state->value])
                    : __('messages.pages.dashboard.step2_missing', ['month' => $label]),
                'done' => $hasRun,
                'href' => $hasRun ? route('payroll.show', $run) : route('payroll.index'),
            ],
            [
                'label' => __('messages.pages.dashboard.step3'),
                'detail' => $calculated
                    ? __('messages.pages.dashboard.step3_done', ['count' => $itemCount])
                    : __('messages.pages.dashboard.step3_todo'),
                'done' => (bool) $calculated,
                'href' => $hasRun ? route('payroll.show', $run) : route('payroll.index'),
            ],
            [
                'label' => __('messages.pages.dashboard.step4'),
                'detail' => $validated ? __('messages.pages.dashboard.step4_done') : __('messages.pages.dashboard.step4_todo'),
                'done' => (bool) $validated,
                'href' => $hasRun ? route('payroll.show', $run) : route('payroll.index'),
            ],
            [
                'label' => __('messages.pages.dashboard.step5'),
                'detail' => $submitted ? __('messages.pages.dashboard.step5_done') : __('messages.pages.dashboard.step5_todo'),
                'done' => (bool) $submitted,
                'href' => $hasRun ? route('payroll.show', $run) : route('payroll.index'),
            ],
        ];

        $nextAction = match (true) {
            ! $attendanceDone && $pendingBatch => __('messages.pages.dashboard.next_approve_batch'),
            ! $attendanceDone => __('messages.pages.dashboard.next_import', ['month' => $label]),
            ! $hasRun => __('messages.pages.dashboard.next_create', ['month' => $label]),
            $run->state === PayrollState::Draft => __('messages.pages.dashboard.next_calculate', ['month' => $label]),
            $run->state === PayrollState::Calculated => __('messages.pages.dashboard.next_validate', ['month' => $label]),
            $run->state === PayrollState::Validated => __('messages.pages.dashboard.next_submit', ['month' => $label]),
            $run->state === PayrollState::UnderReview => __('messages.pages.dashboard.next_waiting', ['month' => $label]),
            default => __('messages.pages.dashboard.next_done', ['month' => $label]),
        };

        $nextHref = match (true) {
            ! $attendanceDone && $pendingBatch => route('attendance.show', $pendingBatch),
            ! $attendanceDone => route('attendance.index'),
            ! $hasRun => route('payroll.index'),
            default => route('payroll.show', $run),
        };

        return [
            'label' => $label,
            'year' => $year,
            'month' => $month,
            'run' => $run,
            'nextAction' => $nextAction,
            'nextHref' => $nextHref,
            'activeEmployees' => $activeEmployees,
            'approvedAttendance' => $approvedAttendance,
            'steps' => $steps,
        ];
    }

    /** @return array<int, array{title: string, subtitle: string, href: string, meta?: string}> */
    private function queuesFor(string $role): array
    {
        return match ($role) {
            'finance' => $this->financeQueues(),
            'auditor' => $this->auditorQueues(),
            default => [],
        };
    }

    /** @return array<int, array{title: string, subtitle: string, href: string, meta?: string}> */
    private function financeQueues(): array
    {
        $items = [];

        foreach (
            PayrollRun::query()
                ->where('state', PayrollState::UnderReview)
                ->withSum(['items as payable_net' => fn ($q) => $q->where('status', '!=', 'hold')], 'net')
                ->latest()
                ->limit(5)
                ->get() as $run
        ) {
            $items[] = [
                'title' => __('messages.pages.dashboard.queue_approve', ['period' => $run->periodLabel()]),
                'subtitle' => __('messages.pages.dashboard.queue_under_review', ['net' => number_format((float) $run->payable_net, 2)]),
                'href' => route('payroll.show', $run),
                'meta' => __('messages.pages.dashboard.meta_maker'),
            ];
        }

        foreach (PayrollRun::query()->where('state', PayrollState::Approved)->latest()->limit(3)->get() as $run) {
            $items[] = [
                'title' => __('messages.pages.dashboard.queue_finalise', ['period' => $run->periodLabel()]),
                'subtitle' => __('messages.pages.dashboard.queue_finalise_sub'),
                'href' => route('payroll.show', $run),
            ];
        }

        foreach (SalaryRevision::query()->with('employee')->where('status', 'pending')->latest()->limit(5)->get() as $revision) {
            $items[] = [
                'title' => __('messages.pages.dashboard.queue_revision', ['code' => $revision->employee?->employee_code]),
                'subtitle' => __('messages.pages.dashboard.queue_revision_sub', [
                    'name' => $revision->employee?->full_name ?? __('messages.common.employee'),
                ]),
                'href' => route('revisions.index'),
                'meta' => __('messages.pages.dashboard.meta_revision'),
            ];
        }

        $items[] = [
            'title' => __('messages.nav.exports'),
            'subtitle' => __('messages.pages.dashboard.queue_exports_sub'),
            'href' => route('exports.index'),
        ];

        return $items;
    }

    /** @return array<int, array{title: string, subtitle: string, href: string, meta?: string}> */
    private function auditorQueues(): array
    {
        $items = [];

        foreach (PayrollRun::query()->latest()->limit(5)->get() as $run) {
            $items[] = [
                'title' => __('messages.pages.payroll.run_title', ['period' => $run->periodLabel()]),
                'subtitle' => __('messages.pages.dashboard.queue_payroll_sub', ['state' => $run->state->value]),
                'href' => route('payroll.show', $run),
                'meta' => __('messages.pages.dashboard.meta_readonly'),
            ];
        }

        foreach (Export::query()->latest()->limit(5)->get() as $export) {
            $items[] = [
                'title' => strtoupper($export->type).' export',
                'subtitle' => $export->idempotency_key,
                'href' => route('exports.index'),
                'meta' => __('messages.pages.dashboard.meta_export'),
            ];
        }

        foreach (AuditLog::query()->latest()->limit(8)->get() as $log) {
            $items[] = [
                'title' => $log->action,
                'subtitle' => ($log->created_at?->format('d M Y H:i') ?? '').' · user #'.($log->user_id ?? '—'),
                'href' => route('dashboard'),
                'meta' => __('messages.pages.dashboard.meta_audit'),
            ];
        }

        return $items;
    }
}
