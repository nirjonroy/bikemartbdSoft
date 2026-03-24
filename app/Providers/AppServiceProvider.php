<?php

namespace App\Providers;

use App\Support\LocationManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        View::composer('*', function ($view) {
            if (! Auth::check()) {
                $view->with('activeLocation', null);
                $view->with('accessibleLocations', collect());
                $view->with('allLocationsMode', false);
                $view->with('locationScopeLabel', 'No location selected');

                return;
            }

            $locationManager = app(LocationManager::class);

            $view->with('activeLocation', $locationManager->activeLocation());
            $view->with('accessibleLocations', $locationManager->accessibleLocations());
            $view->with('allLocationsMode', $locationManager->isAllLocationsMode());
            $view->with('locationScopeLabel', $locationManager->selectionLabel());
        });
    }
}
