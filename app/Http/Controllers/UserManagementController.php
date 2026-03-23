<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    public function index()
    {
        return view('users.index', [
            'businessSetting' => $this->getBusinessSetting(),
            'users' => User::query()
                ->with(['roles', 'locations', 'defaultLocation'])
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
            'default_location_id' => $validated['default_location_id'],
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->locations()->sync($validated['location_ids']);
        $user->syncRoles([$validated['role']]);

        return redirect()
            ->route('users.edit', $user)
            ->with('status', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $user->load(['roles', 'locations', 'defaultLocation']);

        return view('users.edit', $this->formViewData([
            'user' => $user,
            'selectedRole' => old('role', $user->roles->first()?->name),
        ]));
    }

    public function update(Request $request, User $user)
    {
        $validated = $this->validatedData($request, $user);

        $user->default_location_id = $validated['default_location_id'];
        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();
        $user->locations()->sync($validated['location_ids']);
        $user->syncRoles([$validated['role']]);

        if ($user->is(auth()->user())) {
            $this->setActiveLocation((int) $validated['default_location_id']);
        }

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
        /** @var \App\Models\User $user */
        $user = $overrides['user'] ?? new User();
        $activeLocationId = $this->getActiveLocation()?->id;

        return array_merge([
            'businessSetting' => $this->getBusinessSetting(),
            'roles' => Role::query()->orderBy('name')->get(),
            'locations' => Location::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'selectedLocationIds' => old(
                'location_ids',
                $user->exists
                    ? $user->locations->pluck('id')->all()
                    : array_filter([$activeLocationId])
            ),
            'selectedDefaultLocationId' => old(
                'default_location_id',
                $user->default_location_id ?: $activeLocationId
            ),
        ], $overrides);
    }

    private function validatedData(Request $request, ?User $user = null): array
    {
        $passwordRules = $user
            ? ['nullable', 'string', 'min:8', 'confirmed']
            : ['required', 'string', 'min:8', 'confirmed'];

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'password' => $passwordRules,
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
            'location_ids' => ['required', 'array', 'min:1'],
            'location_ids.*' => [
                'required',
                'integer',
                Rule::exists('locations', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'default_location_id' => [
                'required',
                'integer',
                Rule::exists('locations', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
        ]);

        $locationIds = collect($validated['location_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (! in_array((int) $validated['default_location_id'], $locationIds, true)) {
            throw ValidationException::withMessages([
                'default_location_id' => 'Default location must be one of the assigned locations.',
            ]);
        }

        $validated['location_ids'] = $locationIds;

        return $validated;
    }
}
