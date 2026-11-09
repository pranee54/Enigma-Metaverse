<?php

namespace App\Services;

use App\Enums\ComponentClass;
use App\Enums\PayrollState;
use App\Exceptions\DomainException;
use App\Models\Export;
use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class ExportService
{
    public function __construct(private AuditLogger $audit) {}

    public function journal(PayrollRun $run, User $user, string $event = 'approved'): Export
    {
        $this->assertExportable($run);

        $key = "journal:{$run->id}:{$event}";
        if ($existing = Export::query()->where('idempotency_key', $key)->first()) {
            return $existing;
        }

        $totals = [
            'gross_expense' => 0,
            'employer_statutory' => 0,
            'employee_statutory' => 0,
            'net_salary_payable' => 0,
            'loans_advances' => 0,
        ];

        foreach ($run->items()->with('lines')->get() as $item) {
            $totals['gross_expense'] += (float) $item->gross;
            $totals['net_salary_payable'] += (float) $item->net;
            foreach ($item->lines as $line) {
                if ($line->line_type === ComponentClass::Employer->value) {
                    $totals['employer_statutory'] += (float) $line->amount;
                }
                if (in_array($line->code, ['EPF', 'ESI', 'PT'], true)) {
                    $totals['employee_statutory'] += (float) $line->amount;
                }
                if (str_starts_with($line->code, 'LOAN_') && ! str_starts_with($line->code, 'LOAN_CF_')) {
                    $totals['loans_advances'] += (float) $line->amount;
                }
            }
        }

        foreach ($totals as $k => $v) {
            $totals[$k] = round($v, 2);
        }

        $payload = [
            'run_id' => $run->id,
            'period' => $run->periodLabel(),
            'event' => $event,
            'lines' => $totals,
        ];

        $path = "exports/journal-{$run->id}-{$event}.json";
        Storage::disk('local')->put($path, json_encode($payload, JSON_PRETTY_PRINT));

        $export = Export::create([
            'type' => 'journal',
            'payroll_run_id' => $run->id,
            'idempotency_key' => $key,
            'schema_version' => '1.0',
            'payload_path' => $path,
            'meta_json' => $totals,
            'created_by' => $user->id,
        ]);

        $this->audit->log('export.journal', $export);

        return $export;
    }

    public function bank(PayrollRun $run, User $user): Export
    {
        $this->assertExportable($run);

        $key = "bank:{$run->id}:payment";
        if ($existing = Export::query()->where('idempotency_key', $key)->first()) {
            return $existing;
        }

        $rows = ["employee_code,account_number,ifsc,amount,narration"];
        foreach ($run->items()->with('employee')->get() as $item) {
            if ($item->net <= 0 || $item->status === 'hold') {
                continue;
            }
            $e = $item->employee;
            $rows[] = implode(',', [
                $e->employee_code,
                $e->bank_account,
                $e->bank_ifsc,
                number_format((float) $item->net, 2, '.', ''),
                "Salary {$run->periodLabel()}",
            ]);
        }

        $path = "exports/bank-{$run->id}.csv";
        Storage::disk('local')->put($path, implode("\n", $rows)."\n");

        $export = Export::create([
            'type' => 'bank',
            'payroll_run_id' => $run->id,
            'idempotency_key' => $key,
            'schema_version' => '1.0',
            'payload_path' => $path,
            'meta_json' => ['rows' => count($rows) - 1],
            'created_by' => $user->id,
        ]);

        $this->audit->log('export.bank', $export);

        return $export;
    }

    private function assertExportable(PayrollRun $run): void
    {
        $ok = in_array($run->state, [
            PayrollState::Approved,
            PayrollState::Finalised,
            PayrollState::Paid,
            PayrollState::Reconciled,
        ], true);

        if (! $ok) {
            throw new DomainException(__('messages.errors.export_needs_approval'));
        }
    }
}
