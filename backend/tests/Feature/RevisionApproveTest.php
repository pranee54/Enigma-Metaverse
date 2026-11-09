<?php

use App\Models\Employee;
use App\Models\SalaryRevision;
use App\Models\SalaryStructure;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['hr', 'finance', 'employee', 'auditor'] as $role) {
        Role::findOrCreate($role);
    }
    $this->seed(DatabaseSeeder::class);
});

it('lets finance approve a pending revision and blocks the initiator', function () {
    $hr = User::where('email', 'hr@enigma.test')->first();
    $finance = User::where('email', 'finance@enigma.test')->first();
    $employee = Employee::query()->whereHas('salaryStructures', fn ($q) => $q->where('status', 'active'))->firstOrFail();

    $this->actingAs($hr)->post(route('revisions.store'), [
        'employee_id' => $employee->id,
        'effective_from' => now()->toDateString(),
        'reason' => 'annual hike',
    ])->assertRedirect();

    $revision = SalaryRevision::query()->latest('id')->firstOrFail();
    expect($revision->status)->toBe('pending');

    $this->actingAs($hr)
        ->post(route('revisions.approve', $revision))
        ->assertForbidden();

    $this->actingAs($finance)
        ->post(route('revisions.approve', $revision))
        ->assertRedirect();

    expect($revision->fresh()->status)->toBe('approved')
        ->and(SalaryStructure::query()->where('employee_id', $employee->id)->where('status', 'active')->exists())->toBeTrue();
});
