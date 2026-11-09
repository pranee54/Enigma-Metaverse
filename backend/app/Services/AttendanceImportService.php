<?php

namespace App\Services;

use App\Enums\EmployeeStatus;
use App\Models\AttendanceImportBatch;
use App\Models\AttendanceSummary;
use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AttendanceImportService
{
    public function templateCsv(?int $year = null, ?int $month = null): string
    {
        $year ??= (int) now()->year;
        $month ??= (int) now()->month;
        $columns = $this->knownColumns();
        $lines = [implode(',', $columns)];

        foreach ($this->demoRows($year, $month) as $row) {
            $lines[] = implode(',', array_map(
                fn (string $column) => $this->csvCell((string) ($row[$column] ?? '')),
                $columns,
            ));
        }

        return implode("\n", $lines)."\n";
    }

    /**
     * Sample rows so HR can see a working CSV shape (uses real employee codes when present).
     *
     * @return list<array<string, string|int|float>>
     */
    public function demoRows(int $year, int $month): array
    {
        $days = (float) Carbon::create($year, $month, 1)->daysInMonth;
        $codes = Employee::query()
            ->whereIn('status', [EmployeeStatus::Active, EmployeeStatus::Exiting])
            ->orderBy('employee_code')
            ->limit(3)
            ->pluck('employee_code')
            ->all();

        if ($codes === []) {
            $codes = ['EMP001', 'EMP002', 'EMP003'];
        }

        $samples = [
            [
                'payable_days' => $days,
                'lop_days' => 0,
                'overtime_hours' => 0,
                'overtime_consent' => 'no',
                'leave_encashment_days' => 0,
                'maternity_benefit_days' => 0,
                'remarks' => 'Full month present',
            ],
            [
                'payable_days' => max(0, $days - 1),
                'lop_days' => min(1, $days),
                'overtime_hours' => 0,
                'overtime_consent' => 'no',
                'leave_encashment_days' => 0,
                'maternity_benefit_days' => 0,
                'remarks' => 'One LOP day',
            ],
            [
                'payable_days' => $days,
                'lop_days' => 0,
                'overtime_hours' => 2,
                'overtime_consent' => 'yes',
                'leave_encashment_days' => 0,
                'maternity_benefit_days' => 0,
                'remarks' => 'OT needs consent yes',
            ],
        ];

        $rows = [];
        foreach (array_values($codes) as $i => $code) {
            $sample = $samples[$i] ?? $samples[0];
            $rows[] = [
                'employee_code' => $code,
                'period_year' => $year,
                'period_month' => $month,
                ...$sample,
            ];
        }

        return $rows;
    }

    private function csvCell(string $value): string
    {
        if (str_contains($value, ',') || str_contains($value, '"') || str_contains($value, "\n")) {
            return '"'.str_replace('"', '""', $value).'"';
        }

        return $value;
    }

    public function import(UploadedFile $file, User $importer, int $year, int $month): AttendanceImportBatch
    {
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle, escape: '\\');
        $header = array_map(fn ($h) => Str::of((string) $h)->trim()->lower()->toString(), $header ?: []);

        $rows = [];
        while (($data = fgetcsv($handle, escape: '\\')) !== false) {
            if (count(array_filter($data, fn ($v) => $v !== null && $v !== '')) === 0) {
                continue;
            }
            $rows[] = array_combine($header, array_pad($data, count($header), null));
        }
        fclose($handle);

        return DB::transaction(function () use ($rows, $importer, $year, $month, $header) {
            $batch = AttendanceImportBatch::create([
                'batch_code' => 'ATT-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4)),
                'source' => 'csv',
                'importer_id' => $importer->id,
                'period_year' => $year,
                'period_month' => $month,
                'row_count' => count($rows),
                'status' => 'imported',
            ]);

            $accepted = 0;
            $rejected = 0;
            $report = ['extra_columns' => array_values(array_diff($header, $this->knownColumns())), 'rows' => []];

            foreach ($rows as $index => $row) {
                $result = $this->validateAndStage($batch, $row, $year, $month);
                $report['rows'][] = ['line' => $index + 2, ...$result];
                if ($result['ok']) {
                    $accepted++;
                } else {
                    $rejected++;
                }
            }

            $batch->update([
                'accepted_count' => $accepted,
                'rejected_count' => $rejected,
                'report_json' => $report,
            ]);

            return $batch->fresh('summaries');
        });
    }

    public function approveBatch(AttendanceImportBatch $batch, User $approver): AttendanceImportBatch
    {
        $batch->summaries()->where('status', 'staged')->each(function (AttendanceSummary $summary) use ($approver) {
            $summary->update([
                'status' => 'approved',
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);
        });

        $batch->update(['status' => 'approved']);

        return $batch->fresh('summaries');
    }

    public function deleteBatch(AttendanceImportBatch $batch): void
    {
        DB::transaction(function () use ($batch) {
            $batch->summaries()->delete();
            $batch->delete();
        });
    }

    public function employmentDays(Employee $employee, int $year, int $month): float
    {
        $periodStart = Carbon::create($year, $month, 1)->startOfDay();
        // ponytail: endOfMonth() keeps 23:59:59 → Carbon float diffInDays drifts (31.999…); calendar days only
        $periodEnd = $periodStart->copy()->endOfMonth()->startOfDay();

        $from = $employee->joining_date->greaterThan($periodStart)
            ? $employee->joining_date->copy()->startOfDay()
            : $periodStart;
        $to = $periodEnd;

        $exit = $employee->exits()->where('status', '!=', 'cancelled')->latest('last_working_date')->first();
        if ($exit && $exit->last_working_date->copy()->startOfDay()->lessThan($to)) {
            $to = $exit->last_working_date->copy()->startOfDay();
        }

        if ($from->greaterThan($to)) {
            return 0;
        }

        return (float) ((int) $from->diffInDays($to) + 1);
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{ok: bool, reason?: string, summary_id?: int}
     */
    private function validateAndStage(AttendanceImportBatch $batch, array $row, int $year, int $month): array
    {
        $code = trim((string) ($row['employee_code'] ?? ''));
        $employee = Employee::query()->where('employee_code', $code)->first();

        if (! $employee) {
            return ['ok' => false, 'reason' => 'Employee not found'];
        }

        if (! in_array($employee->status, [EmployeeStatus::Active, EmployeeStatus::Exiting], true)) {
            return ['ok' => false, 'reason' => 'Employee is not active or exiting'];
        }

        $payable = (float) ($row['payable_days'] ?? -1);
        $lop = (float) ($row['lop_days'] ?? -1);
        $ot = (float) ($row['overtime_hours'] ?? 0);
        $maternity = (float) ($row['maternity_benefit_days'] ?? 0);
        $encash = (float) ($row['leave_encashment_days'] ?? 0);
        $consentRaw = strtolower(trim((string) ($row['overtime_consent'] ?? 'no')));
        $consent = in_array($consentRaw, ['1', 'yes', 'y', 'true'], true);

        if ($payable < 0 || $lop < 0 || $ot < 0 || $maternity < 0 || $encash < 0) {
            return ['ok' => false, 'reason' => 'Numeric fields must be non-negative'];
        }

        if ($ot > 0 && ! $consent) {
            return ['ok' => false, 'reason' => 'Overtime hours require overtime_consent = yes'];
        }

        $employmentDays = $this->employmentDays($employee, $year, $month);
        $sum = round($payable + $lop + $maternity, 1);
        if ($sum !== round($employmentDays, 1)) {
            return [
                'ok' => false,
                'reason' => "payable+lop+maternity ({$sum}) must equal employment days ({$employmentDays})",
            ];
        }

        $version = (int) AttendanceSummary::query()
            ->where('employee_id', $employee->id)
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->max('version') + 1;

        $summary = AttendanceSummary::create([
            'batch_id' => $batch->id,
            'employee_id' => $employee->id,
            'period_year' => $year,
            'period_month' => $month,
            'version' => $version,
            'payable_days' => $payable,
            'lop_days' => $lop,
            'overtime_hours' => $ot,
            'overtime_consent' => $consent,
            'leave_encashment_days' => $encash,
            'maternity_benefit_days' => $maternity,
            'remarks' => $row['remarks'] ?? null,
            'status' => 'staged',
        ]);

        return ['ok' => true, 'summary_id' => $summary->id];
    }

    /** @return list<string> */
    private function knownColumns(): array
    {
        return [
            'employee_code', 'period_year', 'period_month', 'payable_days', 'lop_days',
            'overtime_hours', 'overtime_consent', 'leave_encashment_days',
            'maternity_benefit_days', 'remarks',
        ];
    }
}
