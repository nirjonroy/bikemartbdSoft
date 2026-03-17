<?php

namespace Tests\Concerns;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

trait InteractsWithRoles
{
    protected function createUserWithRole(string $role = 'super-admin'): User
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
