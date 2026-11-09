<?php

namespace App\Enums;

enum ComponentClass: string
{
    case Earning = 'earning';
    case Deduction = 'deduction';
    case Employer = 'employer';
}
