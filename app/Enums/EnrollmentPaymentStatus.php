<?php

namespace App\Enums;

enum EnrollmentPaymentStatus: string
{
    case Unpaid = 'unpaid';
    case PartiallyPaid = 'partially_paid';
    case FullyPaid = 'fully_paid';
    case Overdue = 'overdue';
}
