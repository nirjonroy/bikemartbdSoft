<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function index()
    {
        return view('vehicles.index', [
            'businessSetting' => $this->getBusinessSetting(),
            'vehicles' => Vehicle::query()
                ->with(['brand', 'category'])
                ->withCount(['purchases', 'sells'])
                ->latest()
                ->paginate(12),
        ]);
    }

    public function create()
    {
        return view('vehicles.create', $this->formViewData([
            'vehicle' => new Vehicle(),
        ]));
    }

    public function store(Request $request)
    {
        $vehicle = Vehicle::create($this->validatedData($request));

        return redirect()
            ->route('vehicles.show', $vehicle)
            ->with('status', 'Vehicle created successfully.');
    }

    public function show(Vehicle $vehicle)
    {
        $vehicle->load([
            'brand',
            'category',
            'purchases' => fn ($query) => $query->with('vehicle')->latest('purchasing_date'),
            'sells' => fn ($query) => $query->with('vehicle')->latest('selling_date'),
        ]);

        return view('vehicles.show', [
            'businessSetting' => $this->getBusinessSetting(),
            'vehicle' => $vehicle,
        ]);
    }

    public function edit(Vehicle $vehicle)
    {
        $vehicle->load(['brand', 'category']);

        return view('vehicles.edit', $this->formViewData([
            'vehicle' => $vehicle,
        ]));
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $vehicle->update($this->validatedData($request));

        return redirect()
            ->route('vehicles.show', $vehicle)
            ->with('status', 'Vehicle updated successfully.');
    }

    public function destroy(Vehicle $vehicle)
    {
        $vehicle->delete();

        return redirect()
            ->route('vehicles.index')
            ->with('status', 'Vehicle deleted successfully.');
    }

    private function formViewData(array $overrides = []): array
    {
        return array_merge([
            'businessSetting' => $this->getBusinessSetting(),
            'brands' => Brand::query()->orderBy('name')->get(),
            'categories' => Category::query()->orderBy('name')->get(),
        ], $overrides);
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'brand_id' => ['required', 'exists:brands,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:255'],
            'registration_number' => ['nullable', 'string', 'max:255'],
            'engine_number' => ['nullable', 'string', 'max:255'],
            'chassis_number' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:100'],
            'year' => ['nullable', 'integer', 'between:1900,2100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
