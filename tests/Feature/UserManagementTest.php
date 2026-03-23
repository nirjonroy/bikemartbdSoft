<?php

namespace Tests\Feature;

use App\Models\Location;
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
        $mainLocation = Location::query()->first();
        $branchLocation = Location::create([
            'name' => 'Chattogram Branch',
            'code' => 'CTG',
            'email' => 'ctg@bikemartbd.com',
            'phone' => '01700-123456',
            'address' => 'Chattogram',
            'is_active' => true,
        ]);

        $storeResponse = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Manager User',
            'email' => 'manager@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'manager',
            'location_ids' => [$mainLocation->id, $branchLocation->id],
            'default_location_id' => $branchLocation->id,
        ]);

        $user = User::where('email', 'manager@example.com')->first();

        $storeResponse->assertRedirect(route('users.edit', $user));
        $this->assertTrue($user->hasRole('manager'));
        $this->assertSame($branchLocation->id, $user->default_location_id);
        $storedLocationIds = array_map('intval', $user->locations()->pluck('locations.id')->all());
        sort($storedLocationIds);
        $expectedLocationIds = [$mainLocation->id, $branchLocation->id];
        sort($expectedLocationIds);
        $this->assertSame($expectedLocationIds, $storedLocationIds);

        $updateResponse = $this->actingAs($admin)->put(route('users.update', $user), [
            'name' => 'Purchase User',
            'email' => 'purchase@example.com',
            'password' => '',
            'password_confirmation' => '',
            'role' => 'purchase-operator',
            'location_ids' => [$mainLocation->id],
            'default_location_id' => $mainLocation->id,
        ]);

        $updateResponse->assertRedirect(route('users.edit', $user));
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Purchase User',
            'email' => 'purchase@example.com',
        ]);

        $user->refresh();
        $this->assertTrue($user->hasRole('purchase-operator'));
        $this->assertSame($mainLocation->id, $user->default_location_id);
        $this->assertSame([$mainLocation->id], array_map('intval', $user->locations()->pluck('locations.id')->all()));

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
