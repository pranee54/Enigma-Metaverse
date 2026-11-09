<?php

namespace App\Http\Controllers\Web;

use App\Enums\EmployeeStatus;
use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRevisionRequest;
use App\Models\Employee;
use App\Models\SalaryRevision;
use App\Models\SalaryStructure;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class RevisionController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function index()
    {
        $this->authorize('viewAny', SalaryRevision::class);

        return view('revisions.index', [
            'revisions' => SalaryRevision::query()->with(['employee', 'structure', 'initiator'])->latest()->paginate(20),
            'employees' => Employee::query()
                ->whereIn('status', [EmployeeStatus::Active, EmployeeStatus::Exiting])
                ->orderBy('employee_code')
                ->get(),
        ]);
    }

    public function store(StoreRevisionRequest $request)
    {
        $data = $request->validated();

        $structure = SalaryStructure::query()
            ->where('employee_id', $data['employee_id'])
            ->where('status', 'active')
            ->orderByDesc('effective_from')
            ->first();

        if (! $structure) {
            throw new DomainException(__('messages.errors.no_active_structure'), 422);
        }

        $revision = SalaryRevision::create([
            'employee_id' => $data['employee_id'],
            'structure_id' => $structure->id,
            'effective_from' => $data['effective_from'],
            'reason' => $data['reason'] ?? null,
            'status' => 'pending',
            'initiated_by' => $request->user()->id,
        ]);

        $this->audit->log('revision.created', $revision);

        return back()->with('success', __('messages.flash.revision_submitted'));
    }

    public function approve(Request $request, SalaryRevision $revision)
    {
        $this->authorize('approve', $revision);

        if ($revision->status !== 'pending') {
            throw new DomainException(__('messages.errors.revision_not_pending'));
        }

        $structure = SalaryStructure::findOrFail($revision->structure_id);

        SalaryStructure::query()
            ->where('employee_id', $revision->employee_id)
            ->where('status', 'active')
            ->where('id', '!=', $structure->id)
            ->update([
                'status' => 'superseded',
                'effective_to' => $revision->effective_from,
            ]);

        $structure->update([
            'status' => 'active',
            'effective_from' => $revision->effective_from,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        $revision->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'arrears_json' => [
                'note' => 'Retrospective arrears would be calculated into the next open payroll.',
            ],
        ]);

        $this->audit->log('revision.approved', $revision);

        return back()->with('success', __('messages.flash.revision_approved'));
    }
}
