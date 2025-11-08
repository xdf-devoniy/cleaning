<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class AutomationSettings extends Settings
{
    public array $triggers = [];
    public array $channels = [];

    public static function group(): string
    {
        return 'automation';
    }
}
