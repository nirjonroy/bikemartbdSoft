<?php

namespace App\Http\Controllers;

use App\Models\BusinessSetting;
use App\Models\Location;
use App\Support\LocationManager;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Collection;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected function getBusinessSetting(): BusinessSetting
    {
        return BusinessSetting::first() ?? BusinessSetting::create([
            'business_name' => config('app.name', 'BikeMart POS'),
            'email' => 'admin@bikemartbd.com',
            'currency_code' => 'BDT',
            'timezone' => config('app.timezone', 'UTC'),
        ]);
    }

    protected function getLocationManager(): LocationManager
    {
        return app(LocationManager::class);
    }

    protected function getAccessibleLocations(): Collection
    {
        return $this->getLocationManager()->accessibleLocations();
    }

    protected function getActiveLocation(): ?Location
    {
        return $this->getLocationManager()->activeLocation();
    }

    protected function setActiveLocation(int $locationId): bool
    {
        return $this->getLocationManager()->setActiveLocation($locationId);
    }

    protected function missingLocationResponse()
    {
        if (auth()->check() && auth()->user()->can('manage locations')) {
            return redirect()
                ->route('locations.index')
                ->withErrors(['location' => 'Create or select an active location before working with location-based modules.']);
        }

        abort(403, 'No active location is assigned to your account.');
    }

    protected function abortIfRecordNotInActiveLocation(?int $locationId): void
    {
        $activeLocation = $this->getActiveLocation();

        abort_unless(
            $activeLocation && $locationId && $activeLocation->id === $locationId,
            404
        );
    }
}
