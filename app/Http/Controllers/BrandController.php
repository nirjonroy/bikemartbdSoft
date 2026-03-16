<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BrandController extends Controller
{
    public function index()
    {
        return view('brands.index', [
            'businessSetting' => $this->getBusinessSetting(),
            'brands' => Brand::query()
                ->withCount('vehicles')
                ->orderBy('name')
                ->paginate(12),
        ]);
    }

    public function create()
    {
        return view('brands.create', [
            'businessSetting' => $this->getBusinessSetting(),
            'brand' => new Brand(),
        ]);
    }

    public function store(Request $request)
    {
        $brand = Brand::create($this->validatedData($request));

        return redirect()
            ->route('brands.edit', $brand)
            ->with('status', 'Brand created successfully.');
    }

    public function edit(Brand $brand)
    {
        return view('brands.edit', [
            'businessSetting' => $this->getBusinessSetting(),
            'brand' => $brand,
        ]);
    }

    public function update(Request $request, Brand $brand)
    {
        $brand->update($this->validatedData($request, $brand));

        return redirect()
            ->route('brands.edit', $brand)
            ->with('status', 'Brand updated successfully.');
    }

    public function destroy(Brand $brand)
    {
        try {
            $brand->delete();
        } catch (QueryException) {
            return redirect()
                ->route('brands.index')
                ->withErrors(['brand' => 'This brand is already linked to one or more vehicles.']);
        }

        return redirect()
            ->route('brands.index')
            ->with('status', 'Brand deleted successfully.');
    }

    private function validatedData(Request $request, ?Brand $brand = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('brands', 'name')->ignore($brand)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
