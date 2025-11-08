<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Partial = 'partial';
    case Paid = 'paid';
    case Overdue = 'overdue';
}
