<?php

namespace KawayanMedia\LeaveForms;

use Illuminate\Support\ServiceProvider;

/**
 * Registers the leave form migrations with the host application.
 */
class LeaveFormsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
