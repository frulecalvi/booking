<?php

namespace App\Providers;

use App\Models\Booking;
use App\Observers\BookingObserver;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Events\DatabaseRefreshed;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\PermissionRegistrar;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Booking::observe(BookingObserver::class);

        Event::listen(DatabaseRefreshed::class, function () {
            Artisan::call('db:seed', ['--class' => RolesAndPermissionsSeeder::class]);
            $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
        });
        
        Gate::before(function ($user, $ability) {
            return $user->hasRole('Super Admin') ? true : null;
        });
    }
}
