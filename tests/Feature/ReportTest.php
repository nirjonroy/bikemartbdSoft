<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Location;
use App\Models\Purchase;
use App\Models\Sell;
use App\Models\Vehicle;
use App\Support\LocationManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    public function test_manager_can_access_all_report_routes()
    {
        $user = $this->createUserWithRole('manager');

        $routes = [
            'reports.profit-loss' => 'Profit / Loss Report',
            'reports.purchase-sale' => 'Purchase & Sale Report',
            'reports.supplier-customer' => 'Supplier & Customer Report',
            'reports.stock' => 'Stock Report',
            'reports.trending-products' => 'Trending Products',
            'reports.items' => 'Items Report',
            'reports.product-purchase' => 'Product Purchase Report',
            'reports.product-sell' => 'Product Sell Report',
            'reports.purchase-payment' => 'Purchase Payment Report',
            'reports.sell-payment' => 'Sell Payment Report',
            'reports.expense' => 'Expense Report',
            'reports.activity-log' => 'Activity Log',
        ];

        foreach ($routes as $routeName => $title) {
            $response = $this->actingAs($user)->get(route($routeName));

            $response->assertOk();
            $response->assertSee($title);
        }
    }

    public function test_user_without_report_permission_cannot_access_reports()
    {
        $user = $this->createUserWithRole('purchase-operator');

        $response = $this->actingAs($user)->get(route('reports.profit-loss'));

        $response->assertForbidden();
    }

    public function test_purchase_and_sale_report_only_shows_active_location_records()
    {
        $user = $this->createUserWithRole('manager');
        $vehicle = $this->createVehicle();
        $otherLocation = Location::create([
            'name' => 'Chattogram Branch',
            'code' => 'CTG',
            'email' => 'ctg@bikemartbd.com',
            'phone' => '01800-000000',
            'address' => 'Chattogram',
            'is_active' => true,
        ]);

        Purchase::create([
            'location_id' => $user->default_location_id,
            'vehicle_id' => $vehicle->id,
            'name' => 'Main Branch Owner',
            'quantity' => 3,
            'buying_price_from_owner' => '100000',
            'purchasing_date' => '2026-03-20',
        ]);

        Purchase::create([
            'location_id' => $otherLocation->id,
            'vehicle_id' => $vehicle->id,
            'name' => 'Other Branch Owner',
            'quantity' => 2,
            'buying_price_from_owner' => '110000',
            'purchasing_date' => '2026-03-20',
        ]);

        Sell::create([
            'location_id' => $user->default_location_id,
            'vehicle_id' => $vehicle->id,
            'name' => 'Main Branch Customer',
            'quantity' => 1,
            'selling_price_to_customer' => '130000',
            'selling_date' => '2026-03-21',
        ]);

        Sell::create([
            'location_id' => $otherLocation->id,
            'vehicle_id' => $vehicle->id,
            'name' => 'Other Branch Customer',
            'quantity' => 1,
            'selling_price_to_customer' => '135000',
            'selling_date' => '2026-03-21',
        ]);

        $response = $this->actingAs($user)->get(route('reports.purchase-sale'));

        $response->assertOk();
        $response->assertSee('Main Branch Owner');
        $response->assertSee('Main Branch Customer');
        $response->assertDontSee('Other Branch Owner');
        $response->assertDontSee('Other Branch Customer');
    }

    public function test_all_branches_mode_combines_accessible_location_report_data()
    {
        $user = $this->createUserWithRole('manager');
        $vehicle = $this->createVehicle();
        $secondLocation = Location::create([
            'name' => 'Cumilla Branch',
            'code' => 'CUM',
            'email' => 'cumilla@bikemartbd.com',
            'phone' => '01900-000000',
            'address' => 'Cumilla',
            'is_active' => true,
        ]);

        $user->locations()->syncWithoutDetaching([$secondLocation->id]);

        Purchase::create([
            'location_id' => $user->default_location_id,
            'vehicle_id' => $vehicle->id,
            'name' => 'Main Scope Owner',
            'quantity' => 2,
            'buying_price_from_owner' => '100000',
            'purchasing_date' => '2026-03-22',
        ]);

        Purchase::create([
            'location_id' => $secondLocation->id,
            'vehicle_id' => $vehicle->id,
            'name' => 'Second Scope Owner',
            'quantity' => 2,
            'buying_price_from_owner' => '100000',
            'purchasing_date' => '2026-03-22',
        ]);

        $this->actingAs($user)->post(route('locations.switch'), [
            'location_id' => LocationManager::ALL_LOCATIONS,
        ])->assertRedirect(route('dashboard'));

        $response = $this->actingAs($user)->get(route('reports.purchase-sale'));

        $response->assertOk();
        $response->assertSee('All Branches');
        $response->assertSee('Main Scope Owner');
        $response->assertSee('Second Scope Owner');
    }

    private function createVehicle(array $overrides = []): Vehicle
    {
        $brand = Brand::create(['name' => uniqid('Brand ', true)]);
        $category = Category::create(['name' => uniqid('Category ', true)]);

        return Vehicle::create(array_merge([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Report Test Bike',
            'code' => uniqid('REP-', true),
            'model' => 'ABS',
            'registration_number' => uniqid('REG-', true),
            'engine_number' => uniqid('ENG-', true),
            'chassis_number' => uniqid('CHS-', true),
            'color' => 'Blue',
            'year' => 2024,
        ], $overrides));
    }
}
