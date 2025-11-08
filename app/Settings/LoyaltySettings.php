<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class LoyaltySettings extends Settings
{
    /**
     * Percentage earned on every eligible invoice amount.
     */
    public string $base_earn_rate = '0.05';

    /**
     * Percentage cost when redeeming points.
     */
    public string $base_redeem_rate = '0.01';

    /**
     * Points required to reach the Silver tier.
     */
    public int $tier_upgrade_threshold_silver = 1_000;

    /**
     * Points required to reach the Gold tier.
     */
    public int $tier_upgrade_threshold_gold = 5_000;

    /**
     * Points required to reach the Platinum tier.
     */
    public int $tier_upgrade_threshold_platinum = 15_000;

    /**
     * Number of days before unused points expire.
     */
    public int $points_expiry_days = 365;

    public static function group(): string
    {
        return 'loyalty';
    }
}
