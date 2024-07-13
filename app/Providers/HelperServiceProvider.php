<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class HelperServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $file = app_path() . "/helpers.php";
        require_once $file;
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
