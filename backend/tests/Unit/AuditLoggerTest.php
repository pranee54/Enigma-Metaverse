<?php

use App\Models\AuditLog;
use App\Models\Employee;
use App\Services\AuditLogger;

it('writes an audit log row with action and model', function () {
    $user = unitUserWithRole('hr');
    $this->actingAs($user);

    $employee = unitEmployee('AL01');
    (new AuditLogger)->log('employee.touched', $employee, ['a' => 1], ['a' => 2]);

    $row = AuditLog::query()->latest('id')->first();

    expect($row)->not->toBeNull()
        ->and($row->action)->toBe('employee.touched')
        ->and($row->user_id)->toBe($user->id)
        ->and($row->auditable_type)->toBe(Employee::class)
        ->and($row->auditable_id)->toBe($employee->id)
        ->and($row->before_json)->toMatchArray(['a' => 1])
        ->and($row->after_json)->toMatchArray(['a' => 2]);
});

it('allows logging without a model', function () {
    (new AuditLogger)->log('system.ping');

    expect(AuditLog::query()->where('action', 'system.ping')->exists())->toBeTrue();
});
