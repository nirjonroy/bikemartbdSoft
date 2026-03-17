<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    public function test_super_admin_can_create_update_and_delete_users_with_roles()
    {
        $admin = $this->createUserWithRole();

        $storeResponse = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Manager User',
            'email' => 'manager@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'manager',
        ]);

        $user = User::where('email', 'manager@example.com')->first();

        $storeResponse->assertRedirect(route('users.edit', $user));
        $this->assertTrue($user->hasRole('manager'));

        $updateResponse = $this->actingAs($admin)->put(route('users.update', $user), [
            'name' => 'Purchase User',
            'email' => 'purchase@example.com',
            'password' => '',
            'password_confirmation' => '',
            'role' => 'purchase-operator',
        ]);

        $updateResponse->assertRedirect(route('users.edit', $user));
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Purchase User',
            'email' => 'purchase@example.com',
        ]);

        $user->refresh();
        $this->assertTrue($user->hasRole('purchase-operator'));

        $deleteResponse = $this->actingAs($admin)->delete(route('users.destroy', $user));

        $deleteResponse->assertRedirect(route('users.index'));
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_users_without_permission_cannot_access_user_management_or_purchase_pages()
    {
        $salesUser = $this->createUserWithRole('sales-operator');
        $purchaseUser = $this->createUserWithRole('purchase-operator');

        $this->actingAs($salesUser)
            ->get(route('users.index'))
            ->assertForbidden();

        $this->actingAs($salesUser)
            ->get(route('purchases.index'))
            ->assertForbidden();

        $this->actingAs($purchaseUser)
            ->get(route('purchases.index'))
            ->assertOk();
    }
}
