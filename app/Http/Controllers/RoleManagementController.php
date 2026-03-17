<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleManagementController extends Controller
{
    public function index()
    {
        return view('roles.index', [
            'businessSetting' => $this->getBusinessSetting(),
            'roles' => Role::query()
                ->withCount(['permissions', 'users'])
                ->orderBy('name')
                ->paginate(12),
        ]);
    }

    public function create()
    {
        return view('roles.create', $this->formViewData([
            'role' => new Role(),
            'selectedPermissions' => old('permissions', []),
        ]));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedData($request);

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($validated['permissions']);

        return redirect()
            ->route('roles.edit', $role)
            ->with('status', 'Role created successfully.');
    }

    public function edit(Role $role)
    {
        $role->load('permissions');

        return view('roles.edit', $this->formViewData([
            'role' => $role,
            'selectedPermissions' => old('permissions', $role->permissions->pluck('name')->all()),
        ]));
    }

    public function update(Request $request, Role $role)
    {
        $validated = $this->validatedData($request, $role);

        $role->update([
            'name' => $validated['name'],
        ]);

        $role->syncPermissions($validated['permissions']);

        return redirect()
            ->route('roles.edit', $role)
            ->with('status', 'Role updated successfully.');
    }

    public function destroy(Role $role)
    {
        $role->delete();

        return redirect()
            ->route('roles.index')
            ->with('status', 'Role deleted successfully.');
    }

    private function formViewData(array $overrides = []): array
    {
        return array_merge([
            'businessSetting' => $this->getBusinessSetting(),
            'permissions' => Permission::query()->orderBy('name')->get(),
        ], $overrides);
    }

    private function validatedData(Request $request, ?Role $role = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role)],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ]);
    }
}
