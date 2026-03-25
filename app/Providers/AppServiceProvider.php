<?php

namespace App\Providers;

use App\Models\BusinessSetting;
use App\Support\LocationManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
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
            static $sharedBusinessSetting;

            if ($sharedBusinessSetting === null) {
                $sharedBusinessSetting = new BusinessSetting([
                    'business_name' => config('app.name', 'BikeMart POS'),
                    'email' => 'admin@bikemartbd.com',
                    'currency_code' => 'BDT',
                    'timezone' => config('app.timezone', 'UTC'),
                    'show_stock_information' => true,
                    'show_quantity_fields' => true,
                    'show_stock_management_module' => true,
                ]);

                if (Schema::hasTable('business_settings')) {
                    $sharedBusinessSetting = BusinessSetting::query()->first() ?? $sharedBusinessSetting;
                }
            }

            $view->with('businessSetting', $sharedBusinessSetting);

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
