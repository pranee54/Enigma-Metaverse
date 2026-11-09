<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportAttendanceRequest;
use App\Models\AttendanceImportBatch;
use App\Models\AttendanceSummary;
use App\Services\AttendanceImportService;
use App\Services\AuditLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function __construct(
        private AttendanceImportService $imports,
        private AuditLogger $audit,
    ) {}

    public function index()
    {
        $batches = AttendanceImportBatch::query()->with('importer')->latest()->paginate(20);
        [$periodYear, $periodMonth] = $this->defaultPeriod();

        $readyPeriods = AttendanceSummary::query()
            ->select('period_year', 'period_month', DB::raw('count(*) as employee_count'))
            ->where('status', 'approved')
            ->groupBy('period_year', 'period_month')
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->get();

        return view('attendance.index', [
            'batches' => $batches,
            'periodYear' => $periodYear,
            'periodMonth' => $periodMonth,
            'readyPeriods' => $readyPeriods,
        ]);
    }

    public function guide(Request $request)
    {
        [$periodYear, $periodMonth] = $this->periodFromRequest($request);

        return view('attendance.guide', [
            'periodYear' => $periodYear,
            'periodMonth' => $periodMonth,
            'daysInPeriod' => Carbon::create($periodYear, $periodMonth, 1)->daysInMonth,
            'demoRows' => $this->imports->demoRows($periodYear, $periodMonth),
        ]);
    }

    public function template(Request $request)
    {
        [$year, $month] = $this->periodFromRequest($request);

        return response($this->imports->templateCsv($year, $month), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="attendance-template-'.$year.'-'.str_pad((string) $month, 2, '0', STR_PAD_LEFT).'.csv"',
        ]);
    }

    /** @return array{0: int, 1: int} */
    private function defaultPeriod(): array
    {
        $period = now()->startOfMonth()->subMonth();

        return [(int) $period->year, (int) $period->month];
    }

    /** @return array{0: int, 1: int} */
    private function periodFromRequest(Request $request): array
    {
        [$defaultYear, $defaultMonth] = $this->defaultPeriod();
        $year = (int) $request->integer('period_year', $defaultYear);
        $month = max(1, min(12, (int) $request->integer('period_month', $defaultMonth)));

        return [$year, $month];
    }

    public function show(AttendanceImportBatch $batch)
    {
        $batch->load(['importer', 'summaries.employee']);

        $reportRows = collect($batch->report_json['rows'] ?? []);

        return view('attendance.show', [
            'batch' => $batch,
            'acceptedRows' => $reportRows->where('ok', true)->values(),
            'rejectedRows' => $reportRows->where('ok', false)->values(),
        ]);
    }

    public function import(ImportAttendanceRequest $request)
    {
        $data = $request->validated();

        $batch = $this->imports->import(
            $request->file('file'),
            $request->user(),
            (int) $data['period_year'],
            (int) $data['period_month'],
        );
        $this->audit->log('attendance.imported', $batch);

        return redirect()
            ->route('attendance.show', $batch)
            ->with('success', __('messages.flash.attendance_imported', [
                'code' => $batch->batch_code,
                'accepted' => $batch->accepted_count,
                'rejected' => $batch->rejected_count,
            ]));
    }

    public function approve(AttendanceImportBatch $batch, Request $request)
    {
        $this->imports->approveBatch($batch, $request->user());
        $this->audit->log('attendance.approved', $batch);

        return redirect()
            ->route('attendance.show', $batch)
            ->with('success', __('messages.flash.batch_approved'));
    }

    public function destroy(AttendanceImportBatch $batch)
    {
        $code = $batch->batch_code;
        $this->imports->deleteBatch($batch);
        $this->audit->log('attendance.deleted', null, ['batch_code' => $code]);

        return redirect()
            ->route('attendance.index')
            ->with('success', __('messages.flash.batch_removed', ['code' => $code]));
    }
}
