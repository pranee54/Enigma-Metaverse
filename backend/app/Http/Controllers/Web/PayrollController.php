<?php

namespace App\Http\Controllers\Web;

use App\Enums\EmployeeStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePayrollRunRequest;
use App\Models\AttendanceSummary;
use App\Models\Employee;
use App\Models\PayrollRun;
use App\Services\PayrollRunService;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    public function __construct(private PayrollRunService $payroll) {}

    public function index()
    {
        $this->authorize('viewAny', PayrollRun::class);

        $runs = PayrollRun::query()->with(['preparer', 'approver'])->latest()->paginate(20);

        return view('payroll.index', compact('runs'));
    }

    public function store(StorePayrollRunRequest $request)
    {
        $data = $request->validated();

        $run = $this->payroll->create((int) $data['period_year'], (int) $data['period_month'], $request->user());

        return redirect()->route('payroll.show', $run)->with('success', __('messages.flash.payroll_created'));
    }

    public function show(PayrollRun $payroll)
    {
        $this->authorize('view', $payroll);

        // ponytail: show page never reads lines — payslip show does
        $payroll->load(['items.employee', 'preparer', 'approver']);

        $activeEmployees = Employee::query()
            ->whereIn('status', [EmployeeStatus::Active, EmployeeStatus::Exiting])
            ->count();

        $approvedAttendance = AttendanceSummary::query()
            ->where('period_year', $payroll->period_year)
            ->where('period_month', $payroll->period_month)
            ->where('status', 'approved')
            ->distinct()
            ->count('employee_id');

        return view('payroll.show', [
            'run' => $payroll,
            'activeEmployees' => $activeEmployees,
            'approvedAttendance' => $approvedAttendance,
        ]);
    }

    public function calculate(PayrollRun $payroll)
    {
        $this->authorize('calculate', $payroll);
        $this->payroll->calculate($payroll);

        return back()->with('success', __('messages.flash.payroll_calculated'));
    }

    public function validateRun(PayrollRun $payroll)
    {
        $this->authorize('validate', $payroll);
        $this->payroll->validate($payroll);

        return back()->with('success', __('messages.flash.payroll_validated'));
    }

    public function submitReview(PayrollRun $payroll)
    {
        $this->authorize('submit', $payroll);
        $this->payroll->submitReview($payroll);

        return back()->with('success', __('messages.flash.payroll_submitted'));
    }

    public function approve(Request $request, PayrollRun $payroll)
    {
        $this->authorize('approve', $payroll);
        $this->payroll->approve($payroll, $request->user());

        return back()->with('success', __('messages.flash.payroll_approved'));
    }

    public function finalise(Request $request, PayrollRun $payroll)
    {
        $this->authorize('finalise', $payroll);
        $this->payroll->finalise($payroll, $request->user());

        return back()->with('success', __('messages.flash.payroll_finalised'));
    }

    public function markPaid(Request $request, PayrollRun $payroll)
    {
        $this->authorize('markPaid', $payroll);
        $this->payroll->markPaid($payroll, $request->user());

        return back()->with('success', __('messages.flash.payroll_paid'));
    }

    public function reconcile(Request $request, PayrollRun $payroll)
    {
        $this->authorize('reconcile', $payroll);
        $this->payroll->markReconciled($payroll, $request->user());

        return back()->with('success', __('messages.flash.payroll_reconciled'));
    }
}
