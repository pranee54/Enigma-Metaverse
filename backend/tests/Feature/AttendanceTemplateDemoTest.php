<?php

use App\Models\User;
use App\Services\AttendanceImportService;
use Database\Seeders\DatabaseSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['hr', 'finance', 'employee', 'auditor'] as $role) {
        Role::findOrCreate($role);
    }
    $this->seed(DatabaseSeeder::class);
});

it('builds a template csv with demo employee rows', function () {
    $csv = app(AttendanceImportService::class)->templateCsv(2026, 10);

    expect($csv)->toContain('employee_code,period_year,period_month,payable_days')
        ->and($csv)->toContain('EMP001')
        ->and($csv)->toContain('Full month present')
        ->and($csv)->toContain('OT needs consent yes')
        ->and($csv)->toContain(',31,');
});

it('keeps attendance index clean without demo preview', function () {
    $hr = User::where('email', 'hr@enigma.test')->firstOrFail();

    $this->actingAs($hr)
        ->get(route('attendance.index'))
        ->assertOk()
        ->assertSee('Template & demo')
        ->assertDontSee('Demo rows')
        ->assertDontSee('Full month present');
});

it('shows all demo info on the guide page', function () {
    $hr = User::where('email', 'hr@enigma.test')->firstOrFail();

    $this->actingAs($hr)
        ->get(route('attendance.guide', ['period_year' => 2026, 'period_month' => 10]))
        ->assertOk()
        ->assertSee('Template & demo')
        ->assertSee('Important rules')
        ->assertSee('Full month present')
        ->assertSee('One LOP day')
        ->assertSee('Download demo CSV');
});

it('downloads template with query period', function () {
    $hr = User::where('email', 'hr@enigma.test')->firstOrFail();

    $this->actingAs($hr)
        ->get(route('attendance.template', ['period_year' => 2026, 'period_month' => 10]))
        ->assertOk()
        ->assertHeader('content-disposition')
        ->assertSee('EMP001')
        ->assertSee('2026,10,');
});

it('imports the demo csv with all rows accepted', function () {
    $hr = User::where('email', 'hr@enigma.test')->firstOrFail();
    $imports = app(AttendanceImportService::class);
    $csv = $imports->templateCsv(2026, 10);
    $file = Illuminate\Http\UploadedFile::fake()->createWithContent('demo.csv', $csv);

    $batch = $imports->import($file, $hr, 2026, 10);

    expect($batch->accepted_count)->toBe(3)
        ->and($batch->rejected_count)->toBe(0)
        ->and($imports->employmentDays(
            App\Models\Employee::where('employee_code', 'EMP001')->firstOrFail(),
            2026,
            10,
        ))->toBe(31.0);
});
