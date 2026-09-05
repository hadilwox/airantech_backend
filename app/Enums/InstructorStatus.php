<?php

namespace App\Enums;

enum InstructorStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Active = 'active';
    case Inactive = 'inactive';
}
