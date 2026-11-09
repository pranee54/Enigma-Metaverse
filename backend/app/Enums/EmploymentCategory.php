<?php

namespace App\Enums;

enum EmploymentCategory: string
{
    case Permanent = 'permanent';
    case Probationary = 'probationary';
    case FixedTerm = 'fixed_term';
    case PartTime = 'part_time';
    case Contractual = 'contractual';
    case Apprentice = 'apprentice';
}
