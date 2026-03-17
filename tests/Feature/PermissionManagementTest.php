<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

class PermissionManagementTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    public function test_super_admin_can_create_update_and_delete_permissions()
    {
        $admin = $this->createUserWithRole();

        $storeResponse = $this->actingAs($admin)->post(route('permissions.store'), [
            'name' => 'approve discounts',
            'guard_name' => 'web',
        ]);

        $permission = Permission::where('name', 'approve discounts')->first();

        $storeResponse->assertRedirect(route('permissions.edit', $permission));

        $updateResponse = $this->actingAs($admin)->put(route('permissions.update', $permission), [
            'name' => 'approve special discounts',
            'guard_name' => 'web',
        ]);

        $updateResponse->assertRedirect(route('permissions.edit', $permission));
        $this->assertDatabaseHas('permissions', [
            'id' => $permission->id,
            'name' => 'approve special discounts',
        ]);

        $deleteResponse = $this->actingAs($admin)->delete(route('permissions.destroy', $permission));

        $deleteResponse->assertRedirect(route('permissions.index'));
        $this->assertDatabaseMissing('permissions', ['id' => $permission->id]);
    }

    public function test_users_without_manage_permissions_permission_cannot_access_permission_management()
    {
        $manager = $this->createUserWithRole('manager');

        $this->actingAs($manager)
            ->get(route('permissions.index'))
            ->assertForbidden();
    }
}
