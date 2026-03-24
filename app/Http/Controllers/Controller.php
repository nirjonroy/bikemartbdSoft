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
            'show_stock_information' => true,
            'show_quantity_fields' => true,
            'show_stock_management_module' => true,
        ]);
    }

    protected function showStockInformation(): bool
    {
        return (bool) $this->getBusinessSetting()->show_stock_information;
    }

    protected function showQuantityFields(): bool
    {
        return (bool) $this->getBusinessSetting()->show_quantity_fields;
    }

    protected function showStockManagementModule(): bool
    {
        return (bool) $this->getBusinessSetting()->show_stock_management_module;
    }

    protected function hiddenStockModuleResponse()
    {
        return redirect()
            ->route('dashboard')
            ->withErrors(['stock' => 'Stock management is currently hidden from business settings.']);
    }

    protected function getLocationManager(): LocationManager
    {
        return app(LocationManager::class);
    }

    protected function getAccessibleLocations(): Collection
    {
        return $this->getLocationManager()->accessibleLocations();
    }

    protected function getSelectedLocationIds(): Collection
    {
        return $this->getLocationManager()->selectedLocationIds();
    }

    protected function getLocationScopeLabel(): string
    {
        return $this->getLocationManager()->selectionLabel();
    }

    protected function isAllLocationsMode(): bool
    {
        return $this->getLocationManager()->isAllLocationsMode();
    }

    protected function getActiveLocation(): ?Location
    {
        return $this->getLocationManager()->activeLocation();
    }

    protected function setActiveLocation(int|string $locationId): bool
    {
        return $this->getLocationManager()->setActiveLocation($locationId);
    }

    protected function missingLocationResponse()
    {
        if ($this->isAllLocationsMode()) {
            return redirect()
                ->route('dashboard')
                ->withErrors(['location' => 'Select a specific branch instead of All Branches before creating or editing branch-specific records.']);
        }

        if (auth()->check() && auth()->user()->can('manage locations')) {
            return redirect()
                ->route('locations.index')
                ->withErrors(['location' => 'Create or select an active location before working with location-based modules.']);
        }

        abort(403, 'No active location is assigned to your account.');
    }

    protected function abortIfRecordNotInActiveLocation(?int $locationId): void
    {
        if ($this->isAllLocationsMode()) {
            abort_unless(
                $locationId && $this->getSelectedLocationIds()->contains($locationId),
                404
            );

            return;
        }

        $activeLocation = $this->getActiveLocation();

        abort_unless(
            $activeLocation && $locationId && $activeLocation->id === $locationId,
            404
        );
    }
}
