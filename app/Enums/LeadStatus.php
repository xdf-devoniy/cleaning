<?php

namespace App\Enums;

enum LeadStatus: string
{
    case NewLead = 'new';
    case Qualified = 'qualified';
    case Quoted = 'quoted';
    case Won = 'won';
    case Lost = 'lost';
}
