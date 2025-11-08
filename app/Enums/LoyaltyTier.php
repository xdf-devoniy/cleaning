<?php

namespace App\Enums;

enum LoyaltyTier: string
{
    case Base = 'base';
    case Silver = 'silver';
    case Gold = 'gold';
    case Platinum = 'platinum';
}
