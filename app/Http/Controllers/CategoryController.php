<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        return view('categories.index', [
            'businessSetting' => $this->getBusinessSetting(),
            'categories' => Category::query()
                ->withCount('vehicles')
                ->orderBy('name')
                ->paginate(12),
        ]);
    }

    public function create()
    {
        return view('categories.create', [
            'businessSetting' => $this->getBusinessSetting(),
            'category' => new Category(),
        ]);
    }

    public function store(Request $request)
    {
        $category = Category::create($this->validatedData($request));

        return redirect()
            ->route('categories.edit', $category)
            ->with('status', 'Category created successfully.');
    }

    public function edit(Category $category)
    {
        return view('categories.edit', [
            'businessSetting' => $this->getBusinessSetting(),
            'category' => $category,
        ]);
    }

    public function update(Request $request, Category $category)
    {
        $category->update($this->validatedData($request, $category));

        return redirect()
            ->route('categories.edit', $category)
            ->with('status', 'Category updated successfully.');
    }

    public function destroy(Category $category)
    {
        try {
            $category->delete();
        } catch (QueryException) {
            return redirect()
                ->route('categories.index')
                ->withErrors(['category' => 'This category is already linked to one or more vehicles.']);
        }

        return redirect()
            ->route('categories.index')
            ->with('status', 'Category deleted successfully.');
    }

    private function validatedData(Request $request, ?Category $category = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($category)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
