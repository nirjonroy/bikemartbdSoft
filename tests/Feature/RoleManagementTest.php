<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    public function test_super_admin_can_create_update_and_delete_roles()
    {
        $admin = $this->createUserWithRole();

        $storeResponse = $this->actingAs($admin)->post(route('roles.store'), [
            'name' => 'inventory-manager',
            'permissions' => ['manage vehicles', 'manage purchases'],
        ]);

        $role = Role::where('name', 'inventory-manager')->first();

        $storeResponse->assertRedirect(route('roles.edit', $role));
        $this->assertTrue($role->hasPermissionTo('manage vehicles'));
        $this->assertTrue($role->hasPermissionTo('manage purchases'));

        $updateResponse = $this->actingAs($admin)->put(route('roles.update', $role), [
            'name' => 'inventory-lead',
            'permissions' => ['manage vehicles', 'manage sales'],
        ]);

        $updateResponse->assertRedirect(route('roles.edit', $role));

        $role->refresh();
        $this->assertSame('inventory-lead', $role->name);
        $this->assertTrue($role->hasPermissionTo('manage sales'));

        $deleteResponse = $this->actingAs($admin)->delete(route('roles.destroy', $role));

        $deleteResponse->assertRedirect(route('roles.index'));
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_users_without_manage_roles_permission_cannot_access_role_management()
    {
        $manager = $this->createUserWithRole('manager');

        $this->actingAs($manager)
            ->get(route('roles.index'))
            ->assertForbidden();
    }
}
