<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;

class PermissionManagementController extends Controller
{
    public function index()
    {
        return view('permissions.index', [
            'businessSetting' => $this->getBusinessSetting(),
            'permissions' => Permission::query()
                ->withCount('roles')
                ->orderBy('name')
                ->paginate(15),
        ]);
    }

    public function create()
    {
        return view('permissions.create', [
            'businessSetting' => $this->getBusinessSetting(),
            'permission' => new Permission(),
        ]);
    }

    public function store(Request $request)
    {
        $permission = Permission::create($this->validatedData($request));

        return redirect()
            ->route('permissions.edit', $permission)
            ->with('status', 'Permission created successfully.');
    }

    public function edit(Permission $permission)
    {
        return view('permissions.edit', [
            'businessSetting' => $this->getBusinessSetting(),
            'permission' => $permission,
        ]);
    }

    public function update(Request $request, Permission $permission)
    {
        $permission->update($this->validatedData($request, $permission));

        return redirect()
            ->route('permissions.edit', $permission)
            ->with('status', 'Permission updated successfully.');
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();

        return redirect()
            ->route('permissions.index')
            ->with('status', 'Permission deleted successfully.');
    }

    private function validatedData(Request $request, ?Permission $permission = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('permissions', 'name')->ignore($permission)],
            'guard_name' => ['required', 'string', 'in:web'],
        ]);
    }
}
