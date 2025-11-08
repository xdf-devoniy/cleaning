<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class SecuritySettings extends Settings
{
    /**
     * Forces all staff-facing accounts to complete MFA at sign-in.
     */
    public bool $enforce_two_factor = false;

    /**
     * List of IP addresses that cannot access the back office.
     */
    public array $restricted_ips = [];

    /**
     * Device fingerprints explicitly allowed to bypass IP restrictions.
     */
    public array $allowed_devices = [];

    public static function group(): string
    {
        return 'security';
    }
}
