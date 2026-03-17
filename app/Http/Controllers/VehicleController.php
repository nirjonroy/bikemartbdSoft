<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        $search = trim((string) ($filters['search'] ?? ''));
        $brandId = (int) ($filters['brand_id'] ?? 0);
        $categoryId = (int) ($filters['category_id'] ?? 0);

        return view('vehicles.index', [
            'businessSetting' => $this->getBusinessSetting(),
            'vehicles' => Vehicle::query()
                ->with(['brand', 'category'])
                ->withCount(['purchases', 'sells'])
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($vehicleQuery) use ($search) {
                        $vehicleQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%")
                            ->orWhere('model', 'like', "%{$search}%")
                            ->orWhere('registration_number', 'like', "%{$search}%")
                            ->orWhere('engine_number', 'like', "%{$search}%")
                            ->orWhere('chassis_number', 'like', "%{$search}%")
                            ->orWhere('color', 'like', "%{$search}%")
                            ->orWhereHas('brand', fn ($brandQuery) => $brandQuery->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('category', fn ($categoryQuery) => $categoryQuery->where('name', 'like', "%{$search}%"));
                    });
                })
                ->when($brandId > 0, fn ($query) => $query->where('brand_id', $brandId))
                ->when($categoryId > 0, fn ($query) => $query->where('category_id', $categoryId))
                ->latest()
                ->paginate(12)
                ->withQueryString(),
            'search' => $search,
            'selectedBrandId' => $brandId ?: null,
            'selectedCategoryId' => $categoryId ?: null,
            'hasFilters' => $search !== '' || $brandId > 0 || $categoryId > 0,
            'brands' => Brand::query()->orderBy('name')->get(),
            'categories' => Category::query()->orderBy('name')->get(),
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
