<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);
    }
}
