<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\BusinessSetting;
use App\Models\Category;
use App\Models\Location;
use App\Models\Purchase;
use App\Models\Sell;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    public function test_dashboard_shows_profit_trending_and_stock_graph_sections_for_the_active_location()
    {
        $user = $this->createUserWithRole('manager');
        $mainLocationId = $user->default_location_id;

        $trendVehicle = $this->createVehicle([
            'name' => 'Main Trend Bike',
            'code' => 'MAIN-TREND',
        ]);

        $alertVehicle = $this->createVehicle([
            'name' => 'Main Alert Bike',
            'code' => 'MAIN-ALERT',
        ]);

        $otherLocation = Location::create([
            'name' => 'Chattogram Branch',
            'code' => 'CTG',
            'email' => 'ctg@bikemartbd.com',
            'phone' => '01711-000000',
            'address' => 'Chattogram',
            'is_active' => true,
        ]);

        $hiddenVehicle = $this->createVehicle([
            'name' => 'Hidden Branch Bike',
            'code' => 'HIDDEN-01',
        ]);

        Purchase::create([
            'location_id' => $mainLocationId,
            'vehicle_id' => $trendVehicle->id,
            'name' => 'Main Owner',
            'quantity' => 5,
            'buying_price_from_owner' => '100000',
            'purchasing_date' => now()->subDays(4)->toDateString(),
        ]);

        Sell::create([
            'location_id' => $mainLocationId,
            'vehicle_id' => $trendVehicle->id,
            'name' => 'Main Customer',
            'quantity' => 3,
            'selling_price_to_customer' => '390000',
            'selling_date' => now()->subDays(1)->toDateString(),
        ]);

        Purchase::create([
            'location_id' => $mainLocationId,
            'vehicle_id' => $alertVehicle->id,
            'name' => 'Alert Owner',
            'quantity' => 1,
            'buying_price_from_owner' => '85000',
            'purchasing_date' => now()->subDays(2)->toDateString(),
        ]);

        Purchase::create([
            'location_id' => $otherLocation->id,
            'vehicle_id' => $hiddenVehicle->id,
            'name' => 'Hidden Owner',
            'quantity' => 2,
            'buying_price_from_owner' => '92000',
            'purchasing_date' => now()->subDays(2)->toDateString(),
        ]);

        Sell::create([
            'location_id' => $otherLocation->id,
            'vehicle_id' => $hiddenVehicle->id,
            'name' => 'Hidden Customer',
            'quantity' => 1,
            'selling_price_to_customer' => '120000',
            'selling_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Daily Profit / Loss');
        $response->assertSee('Weekly Profit / Loss');
        $response->assertSee('Trending Items');
        $response->assertSee('Stock Alerts');
        $response->assertSee('Main Trend Bike');
        $response->assertSee('Main Alert Bike');
        $response->assertDontSee('Hidden Branch Bike');
    }

    public function test_dashboard_hides_stock_alert_panel_when_stock_visibility_is_disabled()
    {
        BusinessSetting::create([
            'business_name' => 'BikeMart POS',
            'show_stock_information' => false,
            'show_stock_management_module' => false,
        ]);

        $user = $this->createUserWithRole('manager');

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Daily Profit / Loss');
        $response->assertSee('Trending Items');
        $response->assertDontSee('Stock Alerts');
        $response->assertDontSee('Open Stock');
    }

    private function createVehicle(array $overrides = []): Vehicle
    {
        $brand = Brand::create(['name' => uniqid('Brand ', true)]);
        $category = Category::create(['name' => uniqid('Category ', true)]);

        return Vehicle::create(array_merge([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Dashboard Bike',
            'code' => uniqid('DB-', true),
            'model' => 'FI',
            'registration_number' => uniqid('REG-', true),
            'engine_number' => uniqid('ENG-', true),
            'chassis_number' => uniqid('CHS-', true),
            'color' => 'Black',
            'year' => 2025,
        ], $overrides));
    }
}
