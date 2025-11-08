<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class PayrollSettings extends Settings
{
    /**
     * Pay cycle used when no custom cadence is defined for a team.
     */
    public string $default_pay_cycle = 'monthly';

    /**
     * Multiplier applied to hours worked beyond the standard schedule.
     */
    public float $overtime_multiplier = 1.5;

    /**
     * Default percentage bonus applied to eligible payouts.
     */
    public float $bonus_percentage = 0.1;

    public static function group(): string
    {
        return 'payroll';
    }
}
