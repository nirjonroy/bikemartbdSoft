<?php

namespace App\Support;

use App\Models\Location;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class LocationManager
{
    private ?Collection $accessibleLocationsCache = null;

    private ?Location $activeLocationCache = null;

    private ?int $cachedUserId = null;

    public function accessibleLocations(?User $user = null): Collection
    {
        $user ??= Auth::user();

        if (! $user) {
            return collect();
        }

        if ($this->cachedUserId === $user->id && $this->accessibleLocationsCache !== null) {
            return $this->accessibleLocationsCache;
        }

        $locations = $user->hasRole('super-admin')
            ? Location::query()->where('is_active', true)->orderBy('name')->get()
            : $user->locations()->where('is_active', true)->orderBy('name')->get();

        $this->cachedUserId = $user->id;
        $this->accessibleLocationsCache = $locations->values();
        $this->activeLocationCache = null;

        return $this->accessibleLocationsCache;
    }

    public function activeLocation(?User $user = null): ?Location
    {
        $user ??= Auth::user();

        if (! $user) {
            return null;
        }

        if ($this->cachedUserId === $user->id && $this->activeLocationCache !== null) {
            return $this->activeLocationCache;
        }

        $locations = $this->accessibleLocations($user);

        if ($locations->isEmpty()) {
            return null;
        }

        $sessionLocationId = (int) Session::get('active_location_id');

        $activeLocation = $locations->firstWhere('id', $sessionLocationId);

        if (! $activeLocation) {
            $activeLocation = $locations->firstWhere('id', (int) $user->default_location_id)
                ?: $locations->first();

            Session::put('active_location_id', $activeLocation->id);
        }

        $this->activeLocationCache = $activeLocation;

        return $activeLocation;
    }

    public function setActiveLocation(int $locationId, ?User $user = null): bool
    {
        $user ??= Auth::user();

        if (! $user) {
            return false;
        }

        $location = $this->accessibleLocations($user)->firstWhere('id', $locationId);

        if (! $location) {
            return false;
        }

        Session::put('active_location_id', $location->id);
        $this->activeLocationCache = $location;

        return true;
    }
}
