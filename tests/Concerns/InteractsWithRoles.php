<?php

namespace Tests\Concerns;

use App\Models\Location;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

trait InteractsWithRoles
{
    protected function createUserWithRole(string $role = 'super-admin'): User
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $location = Location::query()->firstOrCreate(
            ['code' => 'MAIN'],
            [
                'name' => 'Main Branch',
                'email' => 'main@bikemartbd.com',
                'phone' => '01700-000000',
                'address' => 'Default testing location.',
                'is_active' => true,
            ]
        );

        $user = User::factory()->create([
            'default_location_id' => $location->id,
        ]);

        $user->locations()->syncWithoutDetaching([$location->id]);
        $user->assignRole($role);

        return $user;
    }
}
