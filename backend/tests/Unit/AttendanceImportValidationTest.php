<?php

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentCategory;
use App\Models\Employee;
use App\Services\AttendanceImportService;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('rejects rows when payable+lop does not match employment days', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $employee = Employee::create([
        'employee_code' => 'EMPX',
        'first_name' => 'A',
        'last_name' => 'B',
        'dob' => '1991-01-01',
        'designation' => 'Dev',
        'work_location' => 'Hyd',
        'state_code' => 'TG',
        'employment_category' => EmploymentCategory::Permanent,
        'joining_date' => '2020-01-01',
        'status' => EmployeeStatus::Active,
        'pan' => 'ABCDE1234F',
        'bank_account' => '1',
        'bank_ifsc' => 'HDFC0001',
        'appointment_letter_path' => 'a.pdf',
    ]);

    // October 2026 = 31 days; send wrong sum
    $csv = "employee_code,period_year,period_month,payable_days,lop_days,overtime_hours,overtime_consent,leave_encashment_days,maternity_benefit_days,remarks\n";
    $csv .= "EMPX,2026,10,20,0,0,no,0,0,\n";

    $file = UploadedFile::fake()->createWithContent('att.csv', $csv);
    $batch = app(AttendanceImportService::class)->import($file, $user, 2026, 10);

    expect($batch->rejected_count)->toBe(1)
        ->and($batch->accepted_count)->toBe(0)
        ->and($batch->report_json['rows'][0]['reason'])->toContain('employment days');
});

it('rejects overtime without consent', function () {
    $user = User::factory()->create();
    Employee::create([
        'employee_code' => 'EMPY',
        'first_name' => 'C',
        'last_name' => 'D',
        'dob' => '1991-01-01',
        'designation' => 'Dev',
        'work_location' => 'Hyd',
        'state_code' => 'TG',
        'employment_category' => EmploymentCategory::Permanent,
        'joining_date' => '2020-01-01',
        'status' => EmployeeStatus::Active,
        'pan' => 'ABCDE1234F',
        'bank_account' => '1',
        'bank_ifsc' => 'HDFC0001',
        'appointment_letter_path' => 'a.pdf',
    ]);

    $csv = "employee_code,period_year,period_month,payable_days,lop_days,overtime_hours,overtime_consent,leave_encashment_days,maternity_benefit_days,remarks\n";
    $csv .= "EMPY,2026,10,31,0,2,no,0,0,\n";

    $file = UploadedFile::fake()->createWithContent('att.csv', $csv);
    $batch = app(AttendanceImportService::class)->import($file, $user, 2026, 10);

    expect($batch->rejected_count)->toBe(1)
        ->and($batch->report_json['rows'][0]['reason'])->toContain('overtime_consent');
});
