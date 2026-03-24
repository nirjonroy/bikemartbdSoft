<?php

namespace App\Support;

use App\Models\Location;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class LocationManager
{
    public const ALL_LOCATIONS = 'all';

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

        if ($this->isAllLocationsMode($user)) {
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

    public function isAllLocationsMode(?User $user = null): bool
    {
        $user ??= Auth::user();

        if (! $user) {
            return false;
        }

        return Session::get('active_location_id') === self::ALL_LOCATIONS
            && $this->accessibleLocations($user)->isNotEmpty();
    }

    public function selectedLocationIds(?User $user = null): Collection
    {
        $user ??= Auth::user();

        if (! $user) {
            return collect();
        }

        $locations = $this->accessibleLocations($user);

        if ($locations->isEmpty()) {
            return collect();
        }

        if ($this->isAllLocationsMode($user)) {
            return $locations->pluck('id')->values();
        }

        $activeLocation = $this->activeLocation($user);

        return $activeLocation
            ? collect([$activeLocation->id])
            : collect();
    }

    public function selectionLabel(?User $user = null): string
    {
        if ($this->isAllLocationsMode($user)) {
            return 'All Branches';
        }

        return $this->activeLocation($user)?->display_name
            ?? 'No location selected';
    }

    public function setActiveLocation(int|string $locationId, ?User $user = null): bool
    {
        $user ??= Auth::user();

        if (! $user) {
            return false;
        }

        if ($locationId === self::ALL_LOCATIONS) {
            if ($this->accessibleLocations($user)->count() < 2) {
                return false;
            }

            Session::put('active_location_id', self::ALL_LOCATIONS);
            $this->activeLocationCache = null;

            return true;
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
