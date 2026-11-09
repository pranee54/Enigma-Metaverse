<?php

namespace App\Http\Controllers\Web;

use App\Enums\ComponentClass;
use App\Enums\EmployeeStatus;
use App\Enums\EmploymentCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Http\Requests\UpdateSalaryStructureRequest;
use App\Models\Employee;
use App\Models\EmployeeStatusHistory;
use App\Models\SalaryComponent;
use App\Models\SalaryStructure;
use App\Services\AuditLogger;
use App\Services\WageBaseCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Employee::class);

        $departmentCounts = Employee::query()
            ->select('department', DB::raw('count(*) as total'))
            ->whereNotNull('department')
            ->where('department', '!=', '')
            ->groupBy('department')
            ->orderBy('department')
            ->pluck('total', 'department');

        $employees = Employee::query()
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = trim((string) $request->input('q'));
                $query->where(function ($inner) use ($term) {
                    $inner->where('employee_code', 'like', "%{$term}%")
                        ->orWhere('first_name', 'like', "%{$term}%")
                        ->orWhere('last_name', 'like', "%{$term}%")
                        ->orWhere('department', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('department'), fn ($query) => $query->where('department', $request->input('department')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->orderBy('department')
            ->orderBy('employee_code')
            ->paginate(20)
            ->withQueryString();

        return view('employees.index', [
            'employees' => $employees,
            'statuses' => EmployeeStatus::cases(),
            'departmentCounts' => $departmentCounts,
            'filters' => [
                'q' => $request->input('q', ''),
                'department' => $request->input('department', ''),
                'status' => $request->input('status', ''),
            ],
        ]);
    }

    public function create()
    {
        $this->authorize('create', Employee::class);

        return view('employees.create', [
            'categories' => EmploymentCategory::cases(),
            'statuses' => EmployeeStatus::cases(),
        ]);
    }

    public function store(StoreEmployeeRequest $request)
    {
        $data = $request->safe()->except(['basic', 'hra', 'special', 'salary_effective_from']);
        $salary = $request->only(['basic', 'hra', 'special', 'salary_effective_from']);

        $employee = DB::transaction(function () use ($request, $data, $salary) {
            $employee = Employee::create([
                ...$data,
                'created_by' => $request->user()->id,
            ]);

            EmployeeStatusHistory::create([
                'employee_id' => $employee->id,
                'from_status' => null,
                'to_status' => $employee->status->value,
                'effective_date' => $employee->joining_date,
                'recorded_at' => now(),
                'author_id' => $request->user()->id,
                'notes' => 'Employee created',
            ]);

            if (($salary['basic'] ?? null) !== null && $salary['basic'] !== '') {
                $this->createActiveStructure(
                    $employee,
                    $request->user()->id,
                    (float) $salary['basic'],
                    (float) ($salary['hra'] ?? 0),
                    (float) ($salary['special'] ?? 0),
                    $salary['salary_effective_from'] ?? $employee->joining_date->toDateString(),
                );
            }

            $this->audit->log('employee.created', $employee);

            return $employee;
        });

        return redirect()->route('employees.show', $employee)->with('success', __('messages.flash.employee_created'));
    }

    public function show(Employee $employee, WageBaseCalculator $wageBases)
    {
        $this->authorize('view', $employee);

        $employee->load(['activeSalaryStructure.components', 'statusHistories']);
        $bases = null;
        if ($employee->activeSalaryStructure) {
            $bases = $wageBases->summarize($employee->activeSalaryStructure);
        }

        return view('employees.show', [
            'employee' => $employee,
            'wageBases' => $bases,
            'missing' => $employee->missingActivationFields(),
        ]);
    }

    public function edit(Employee $employee)
    {
        $this->authorize('update', $employee);

        return view('employees.edit', [
            'employee' => $employee,
            'categories' => EmploymentCategory::cases(),
            'statuses' => EmployeeStatus::cases(),
        ]);
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee)
    {
        $data = $request->validated();
        $fromStatus = $employee->status->value;

        DB::transaction(function () use ($request, $employee, $data, $fromStatus) {
            $employee->update($data);

            if ($fromStatus !== $employee->status->value) {
                EmployeeStatusHistory::create([
                    'employee_id' => $employee->id,
                    'from_status' => $fromStatus,
                    'to_status' => $employee->status->value,
                    'effective_date' => now()->toDateString(),
                    'recorded_at' => now(),
                    'author_id' => $request->user()->id,
                    'notes' => 'Status updated',
                ]);
            }

            $this->audit->log('employee.updated', $employee);
        });

        return redirect()->route('employees.show', $employee)->with('success', __('messages.flash.employee_updated'));
    }

    public function updateSalaryStructure(UpdateSalaryStructureRequest $request, Employee $employee)
    {
        $data = $request->validated();

        DB::transaction(function () use ($request, $employee, $data) {
            $structure = $employee->activeSalaryStructure;
            if ($structure) {
                $structure->update([
                    'status' => 'superseded',
                    'effective_to' => $data['effective_from'],
                ]);
            }

            $this->createActiveStructure(
                $employee,
                $request->user()->id,
                (float) $data['basic'],
                (float) ($data['hra'] ?? 0),
                (float) ($data['special'] ?? 0),
                $data['effective_from'],
            );

            $this->audit->log('employee.salary_updated', $employee->fresh('activeSalaryStructure'));
        });

        return redirect()->route('employees.show', $employee)->with('success', __('messages.flash.salary_updated'));
    }

    private function createActiveStructure(
        Employee $employee,
        int $userId,
        float $basic,
        float $hra,
        float $special,
        string $effectiveFrom,
    ): void {
        $monthly = $basic + $hra + $special;

        $structure = SalaryStructure::create([
            'employee_id' => $employee->id,
            'effective_from' => $effectiveFrom,
            'monthly_ctc' => $monthly,
            'annual_ctc' => $monthly * 12,
            'status' => 'active',
            'created_by' => $userId,
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);

        foreach ([
            ['BASIC', 'Basic', $basic, true, true, true, true, true, true, true],
            ['HRA', 'House Rent Allowance', $hra, false, false, true, false, false, true, true],
            ['SPECIAL', 'Special Allowance', $special, true, true, true, false, false, true, true],
        ] as $j => [$code, $name, $amount, $codeW, $epf, $esi, $grat, $bonus, $pt, $tax]) {
            if ($amount <= 0 && $code !== 'BASIC') {
                continue;
            }

            SalaryComponent::create([
                'structure_id' => $structure->id,
                'code' => $code,
                'name' => $name,
                'class' => ComponentClass::Earning,
                'amount_monthly' => $amount,
                'is_fixed' => true,
                'include_in_code_wages' => $codeW,
                'include_in_epf' => $epf,
                'include_in_esi' => $esi,
                'include_in_gratuity' => $grat,
                'include_in_bonus' => $bonus,
                'include_in_pt' => $pt,
                'include_in_taxable' => $tax,
                'sort_order' => $j,
            ]);
        }
    }
}
