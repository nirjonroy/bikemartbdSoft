<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Purchase;
use App\Models\Sell;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

class StockManagementTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    public function test_authorized_users_can_view_stock_management_page_with_inventory_quantities()
    {
        $user = $this->createUserWithRole('manager');

        $brand = Brand::create(['name' => 'Honda']);
        $category = Category::create(['name' => 'Commuter']);

        $inStockVehicle = Vehicle::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'SP 125',
            'code' => 'STK-001',
        ]);

        Purchase::create([
            'vehicle_id' => $inStockVehicle->id,
            'name' => 'Owner One',
            'quantity' => 5,
            'buying_price_from_owner' => '100000',
            'purchasing_date' => '2026-03-17',
        ]);

        $soldVehicle = Vehicle::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'XBlade',
            'code' => 'STK-002',
        ]);

        Purchase::create([
            'vehicle_id' => $soldVehicle->id,
            'name' => 'Owner Two',
            'quantity' => 3,
            'buying_price_from_owner' => '110000',
            'purchasing_date' => '2026-03-15',
        ]);

        Sell::create([
            'vehicle_id' => $soldVehicle->id,
            'name' => 'Customer One',
            'quantity' => 3,
            'selling_price_to_customer' => '125000',
            'selling_date' => '2026-03-17',
        ]);

        $notPurchasedVehicle = Vehicle::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Shine',
            'code' => 'STK-003',
        ]);

        $response = $this->actingAs($user)->get(route('stock.index'));

        $response->assertOk();
        $response->assertSee('Stock Management');
        $response->assertSee('Purchased Units');
        $response->assertSee('Sold Units');
        $response->assertSee('Available Stock');
        $response->assertSee('In Stock');
        $response->assertSee('Out of Stock');
        $response->assertSee('Not Purchased');
        $response->assertSee('SP 125');
        $response->assertSee('XBlade');
        $response->assertSee('Shine');
    }

    public function test_stock_management_can_filter_vehicles_by_status()
    {
        $user = $this->createUserWithRole('manager');

        $brand = Brand::create(['name' => 'Yamaha']);
        $category = Category::create(['name' => 'Sports']);

        $inStockVehicle = Vehicle::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'R15',
            'code' => 'STK-100',
        ]);

        Purchase::create([
            'vehicle_id' => $inStockVehicle->id,
            'name' => 'Owner Three',
            'quantity' => 2,
            'buying_price_from_owner' => '150000',
            'purchasing_date' => '2026-03-17',
        ]);

        Vehicle::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'MT15',
            'code' => 'STK-101',
        ]);

        Sell::create([
            'vehicle_id' => $inStockVehicle->id,
            'name' => 'Customer Two',
            'quantity' => 2,
            'selling_price_to_customer' => '175000',
            'selling_date' => '2026-03-18',
        ]);

        $response = $this->actingAs($user)->get(route('stock.index', ['status' => 'Not Purchased']));

        $response->assertOk();
        $response->assertSee('MT15');
        $response->assertDontSee('R15');
    }

    public function test_users_without_stock_permission_cannot_access_stock_management()
    {
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('stock.index'))
            ->assertForbidden();
    }
}
