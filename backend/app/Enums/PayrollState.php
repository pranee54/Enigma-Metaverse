<?php

namespace App\Enums;

enum PayrollState: string
{
    case Draft = 'draft';
    case Calculated = 'calculated';
    case Validated = 'validated';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Finalised = 'finalised';
    case Paid = 'paid';
    case Reconciled = 'reconciled';

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Draft => $next === self::Calculated,
            self::Calculated => in_array($next, [self::Validated, self::Draft], true),
            self::Validated => in_array($next, [self::UnderReview, self::Calculated], true),
            self::UnderReview => in_array($next, [self::Approved, self::Validated], true),
            self::Approved => in_array($next, [self::Finalised, self::UnderReview], true),
            self::Finalised => $next === self::Paid,
            self::Paid => $next === self::Reconciled,
            self::Reconciled => false,
        };
    }
}
