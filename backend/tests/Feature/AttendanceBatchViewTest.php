<?php

use App\Models\AttendanceImportBatch;
use App\Models\User;
use App\Services\AttendanceImportService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['hr', 'finance', 'employee', 'auditor'] as $role) {
        Role::findOrCreate($role);
    }
    $this->seed(DatabaseSeeder::class);
});

it('shows uploaded batch details after import', function () {
    $hr = User::where('email', 'hr@enigma.test')->firstOrFail();
    $imports = app(AttendanceImportService::class);
    $file = UploadedFile::fake()->createWithContent('demo.csv', $imports->templateCsv(2026, 10));
    $batch = $imports->import($file, $hr, 2026, 10);

    $this->actingAs($hr)
        ->get(route('attendance.show', $batch))
        ->assertOk()
        ->assertSee($batch->batch_code)
        ->assertSee('Accepted rows')
        ->assertSee('EMP001')
        ->assertSee('Rahul Sharma');
});

it('lets hr remove an error batch from the database', function () {
    $hr = User::where('email', 'hr@enigma.test')->firstOrFail();

    $batch = AttendanceImportBatch::create([
        'batch_code' => 'ATT-ERROR-TEST',
        'source' => 'csv',
        'importer_id' => $hr->id,
        'period_year' => 2026,
        'period_month' => 10,
        'row_count' => 3,
        'accepted_count' => 0,
        'rejected_count' => 3,
        'status' => 'imported',
        'report_json' => [
            'rows' => [
                ['line' => 2, 'ok' => false, 'reason' => 'bad days'],
            ],
        ],
    ]);

    $this->actingAs($hr)
        ->delete(route('attendance.destroy', $batch))
        ->assertRedirect(route('attendance.index'));

    expect(AttendanceImportBatch::where('batch_code', 'ATT-ERROR-TEST')->exists())->toBeFalse();
});
