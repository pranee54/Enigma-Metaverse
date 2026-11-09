<?php

namespace App\Enums;

enum EmployeeStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Exiting = 'exiting';
    case Exited = 'exited';
}
