<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    public function index()
    {
        return view('users.index', [
            'businessSetting' => $this->getBusinessSetting(),
            'users' => User::query()
                ->with('roles')
                ->latest()
                ->paginate(12),
        ]);
    }

    public function create()
    {
        return view('users.create', $this->formViewData([
            'user' => new User(),
            'selectedRole' => old('role'),
        ]));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedData($request);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->syncRoles([$validated['role']]);

        return redirect()
            ->route('users.edit', $user)
            ->with('status', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $user->load('roles');

        return view('users.edit', $this->formViewData([
            'user' => $user,
            'selectedRole' => old('role', $user->roles->first()?->name),
        ]));
    }

    public function update(Request $request, User $user)
    {
        $validated = $this->validatedData($request, $user);

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();
        $user->syncRoles([$validated['role']]);

        return redirect()
            ->route('users.edit', $user)
            ->with('status', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->is(auth()->user())) {
            return redirect()
                ->route('users.index')
                ->withErrors(['user' => 'You cannot delete the currently logged-in user.']);
        }

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('status', 'User deleted successfully.');
    }

    private function formViewData(array $overrides = []): array
    {
        return array_merge([
            'businessSetting' => $this->getBusinessSetting(),
            'roles' => Role::query()->orderBy('name')->get(),
        ], $overrides);
    }

    private function validatedData(Request $request, ?User $user = null): array
    {
        $passwordRules = $user
            ? ['nullable', 'string', 'min:8', 'confirmed']
            : ['required', 'string', 'min:8', 'confirmed'];

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'password' => $passwordRules,
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
        ]);
    }
}
