<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Spatie\LaravelSettings\SettingsContainer;

class SettingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->afterResolving(SettingsContainer::class, function (SettingsContainer $settings) {
            $settings->register(
                \App\Settings\LoyaltySettings::class,
                \App\Settings\AutomationSettings::class,
                \App\Settings\PayrollSettings::class,
                \App\Settings\SecuritySettings::class,
            );
        });
    }
}
