<?php

use App\Services\AttendanceImportService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('builds a CSV template with known columns and demo rows', function () {
    unitEmployee('EMP1');
    $csv = app(AttendanceImportService::class)->templateCsv(2026, 10);

    expect($csv)->toContain('employee_code')
        ->and($csv)->toContain('payable_days')
        ->and($csv)->toContain('overtime_consent');
});

it('accepts a valid attendance row', function () {
    Storage::fake('local');
    $user = unitUserWithRole('hr');
    unitEmployee('EMPA');

    $csv = "employee_code,period_year,period_month,payable_days,lop_days,overtime_hours,overtime_consent,leave_encashment_days,maternity_benefit_days,remarks\n";
    $csv .= "EMPA,2026,10,31,0,0,no,0,0,\n";

    $file = UploadedFile::fake()->createWithContent('ok.csv', $csv);
    $batch = app(AttendanceImportService::class)->import($file, $user, 2026, 10);

    expect($batch->accepted_count)->toBe(1)
        ->and($batch->rejected_count)->toBe(0)
        ->and($batch->summaries()->where('status', 'staged')->count())->toBe(1);
});

it('approves staged summaries on a batch', function () {
    Storage::fake('local');
    $user = unitUserWithRole('hr');
    unitEmployee('EMPB');

    $csv = "employee_code,period_year,period_month,payable_days,lop_days,overtime_hours,overtime_consent,leave_encashment_days,maternity_benefit_days,remarks\n";
    $csv .= "EMPB,2026,10,31,0,0,no,0,0,\n";
    $file = UploadedFile::fake()->createWithContent('ok.csv', $csv);

    $svc = app(AttendanceImportService::class);
    $batch = $svc->import($file, $user, 2026, 10);
    $batch = $svc->approveBatch($batch, $user);

    expect($batch->summaries()->where('status', 'approved')->count())->toBe(1)
        ->and($batch->summaries()->first()->approved_by)->toBe($user->id);
});

it('rejects unknown employee codes', function () {
    Storage::fake('local');
    $user = unitUserWithRole('hr');

    $csv = "employee_code,period_year,period_month,payable_days,lop_days,overtime_hours,overtime_consent,leave_encashment_days,maternity_benefit_days,remarks\n";
    $csv .= "NOPE,2026,10,31,0,0,no,0,0,\n";
    $file = UploadedFile::fake()->createWithContent('bad.csv', $csv);

    $batch = app(AttendanceImportService::class)->import($file, $user, 2026, 10);

    expect($batch->rejected_count)->toBe(1)
        ->and($batch->accepted_count)->toBe(0);
});
