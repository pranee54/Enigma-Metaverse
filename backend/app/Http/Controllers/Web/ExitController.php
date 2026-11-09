<?php

namespace App\Http\Controllers\Web;

use App\Enums\EmployeeStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExitRequest;
use App\Models\Employee;
use App\Models\EmployeeExit;
use App\Services\SettlementService;
use Illuminate\Http\Request;

class ExitController extends Controller
{
    public function __construct(private SettlementService $settlements) {}

    public function index()
    {
        return view('exits.index', [
            'exits' => EmployeeExit::query()->with(['employee', 'settlements'])->latest()->paginate(20),
            'employees' => Employee::query()
                ->whereIn('status', [EmployeeStatus::Active, EmployeeStatus::Exiting])
                ->orderBy('employee_code')
                ->get(),
        ]);
    }

    public function store(StoreExitRequest $request)
    {
        $this->settlements->createExit($request->validated());

        return back()->with('success', __('messages.flash.exit_recorded'));
    }

    public function settlement(Request $request, EmployeeExit $exit)
    {
        $this->settlements->generateStepOne($exit, $request->user());

        return back()->with('success', __('messages.flash.settlement_generated'));
    }
}
