<?php

use App\Enums\PayrollState;

it('allows the happy-path transitions', function () {
    expect(PayrollState::Draft->canTransitionTo(PayrollState::Calculated))->toBeTrue()
        ->and(PayrollState::Calculated->canTransitionTo(PayrollState::Validated))->toBeTrue()
        ->and(PayrollState::Validated->canTransitionTo(PayrollState::UnderReview))->toBeTrue()
        ->and(PayrollState::UnderReview->canTransitionTo(PayrollState::Approved))->toBeTrue()
        ->and(PayrollState::Approved->canTransitionTo(PayrollState::Finalised))->toBeTrue()
        ->and(PayrollState::Finalised->canTransitionTo(PayrollState::Paid))->toBeTrue()
        ->and(PayrollState::Paid->canTransitionTo(PayrollState::Reconciled))->toBeTrue();
});

it('allows documented rewind transitions', function () {
    expect(PayrollState::Calculated->canTransitionTo(PayrollState::Draft))->toBeTrue()
        ->and(PayrollState::Validated->canTransitionTo(PayrollState::Calculated))->toBeTrue()
        ->and(PayrollState::UnderReview->canTransitionTo(PayrollState::Validated))->toBeTrue()
        ->and(PayrollState::Approved->canTransitionTo(PayrollState::UnderReview))->toBeTrue();
});

it('blocks illegal and terminal moves', function () {
    expect(PayrollState::Draft->canTransitionTo(PayrollState::Approved))->toBeFalse()
        ->and(PayrollState::Finalised->canTransitionTo(PayrollState::Approved))->toBeFalse()
        ->and(PayrollState::Reconciled->canTransitionTo(PayrollState::Paid))->toBeFalse()
        ->and(PayrollState::Reconciled->canTransitionTo(PayrollState::Reconciled))->toBeFalse();
});
