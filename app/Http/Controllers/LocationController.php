<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocationController extends Controller
{
    public function index()
    {
        return view('locations.index', [
            'businessSetting' => $this->getBusinessSetting(),
            'locations' => Location::query()
                ->withCount(['users', 'purchases', 'sells'])
                ->orderBy('name')
                ->paginate(12),
        ]);
    }

    public function create()
    {
        return view('locations.create', [
            'businessSetting' => $this->getBusinessSetting(),
            'location' => new Location(),
        ]);
    }

    public function store(Request $request)
    {
        $location = Location::create($this->validatedData($request));

        if (auth()->check() && ! auth()->user()->hasRole('super-admin')) {
            auth()->user()->locations()->syncWithoutDetaching([$location->id]);

            if (! auth()->user()->default_location_id) {
                auth()->user()->update(['default_location_id' => $location->id]);
            }
        }

        $this->setActiveLocation($location->id);

        return redirect()
            ->route('locations.edit', $location)
            ->with('status', 'Location created successfully.');
    }

    public function edit(Location $location)
    {
        return view('locations.edit', [
            'businessSetting' => $this->getBusinessSetting(),
            'location' => $location,
        ]);
    }

    public function update(Request $request, Location $location)
    {
        $location->update($this->validatedData($request, $location));

        return redirect()
            ->route('locations.edit', $location)
            ->with('status', 'Location updated successfully.');
    }

    public function destroy(Location $location)
    {
        if (Location::query()->count() <= 1) {
            return redirect()
                ->route('locations.index')
                ->withErrors(['location' => 'At least one location must remain in the system.']);
        }

        try {
            $location->delete();
        } catch (QueryException) {
            return redirect()
                ->route('locations.index')
                ->withErrors(['location' => 'This location is already linked to users or transactions and cannot be deleted.']);
        }

        return redirect()
            ->route('locations.index')
            ->with('status', 'Location deleted successfully.');
    }

    public function switch(Request $request)
    {
        $validated = $request->validate([
            'location_id' => ['required', 'string'],
        ]);

        $locationSelection = $validated['location_id'] === \App\Support\LocationManager::ALL_LOCATIONS
            ? $validated['location_id']
            : (int) $validated['location_id'];

        abort_unless($this->setActiveLocation($locationSelection), 403);

        return redirect()
            ->route('dashboard')
            ->with('status', 'Working location scope updated successfully.');
    }

    private function validatedData(Request $request, ?Location $location = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('locations', 'name')->ignore($location)],
            'code' => ['required', 'string', 'max:50', Rule::unique('locations', 'code')->ignore($location)],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
