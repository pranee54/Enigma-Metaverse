<?php

namespace App\Http\Controllers\Web;

use App\Enums\EmployeeStatus;
use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLoanRequest;
use App\Models\Employee;
use App\Models\Loan;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Loan::class);

        $loans = Loan::query()->with('employee')->latest()->paginate(20);

        // ponytail: schedule = equal monthly recovery preview, not a separate amortisation table
        $loans->getCollection()->transform(function (Loan $loan) {
            $loan->setAttribute('schedule', $this->schedulePreview($loan));

            return $loan;
        });

        return view('loans.index', [
            'loans' => $loans,
            'employees' => Employee::query()
                ->whereIn('status', [EmployeeStatus::Active, EmployeeStatus::Exiting])
                ->orderBy('employee_code')
                ->get(),
        ]);
    }

    public function store(StoreLoanRequest $request)
    {
        $data = $request->validated();

        Loan::create([
            ...$data,
            'outstanding' => $data['principal'],
            'status' => 'pending',
            'approved_by' => null,
        ]);

        return back()->with('success', __('messages.flash.loan_created'));
    }

    public function approve(Request $request, Loan $loan)
    {
        $this->authorize('approve', $loan);

        if ($loan->status !== 'pending') {
            throw new DomainException(__('messages.errors.loan_not_pending'));
        }

        $loan->update([
            'status' => 'active',
            'approved_by' => $request->user()->id,
        ]);

        return back()->with('success', __('messages.flash.loan_approved'));
    }

    /** @return list<array{instalment: int, amount: float}> */
    private function schedulePreview(Loan $loan): array
    {
        $remaining = (float) $loan->outstanding;
        $recovery = (float) $loan->monthly_recovery;
        if ($remaining <= 0 || $recovery <= 0) {
            return [];
        }

        $rows = [];
        $n = 1;
        while ($remaining > 0.009 && $n <= (int) $loan->tenure_months + 12) {
            $take = min($recovery, $remaining);
            $rows[] = ['instalment' => $n, 'amount' => round($take, 2)];
            $remaining = round($remaining - $take, 2);
            $n++;
        }

        return $rows;
    }
}
