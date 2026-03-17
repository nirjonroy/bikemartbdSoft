<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

class VehicleTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    public function test_authenticated_users_can_create_view_update_and_delete_a_vehicle()
    {
        $user = $this->createUserWithRole();
        $brand = Brand::create(['name' => 'Honda']);
        $updatedBrand = Brand::create(['name' => 'Suzuki']);
        $category = Category::create(['name' => 'Commuter']);
        $updatedCategory = Category::create(['name' => 'Sports']);

        $storeResponse = $this->actingAs($user)->post(route('vehicles.store'), [
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'CB Shine',
            'code' => 'BM-VEH-1',
            'model' => 'Disc',
            'registration_number' => 'DHA-12345',
            'engine_number' => 'ENG-1001',
            'chassis_number' => 'CHS-1001',
            'color' => 'Black',
            'year' => 2024,
            'notes' => 'Ready for listing.',
        ]);

        $vehicle = Vehicle::first();

        $storeResponse->assertRedirect(route('vehicles.show', $vehicle));
        $this->assertDatabaseHas('vehicles', ['name' => 'CB Shine', 'brand_id' => $brand->id]);

        $this->actingAs($user)
            ->get(route('vehicles.show', $vehicle))
            ->assertOk()
            ->assertSee('CB Shine')
            ->assertSee('Honda')
            ->assertSee('Commuter');

        $updateResponse = $this->actingAs($user)->put(route('vehicles.update', $vehicle), [
            'brand_id' => $updatedBrand->id,
            'category_id' => $updatedCategory->id,
            'name' => 'Gixxer',
            'code' => 'BM-VEH-2',
            'model' => 'ABS',
            'registration_number' => 'DHA-99999',
            'engine_number' => 'ENG-2002',
            'chassis_number' => 'CHS-2002',
            'color' => 'Blue',
            'year' => 2025,
            'notes' => 'Updated vehicle.',
        ]);

        $updateResponse->assertRedirect(route('vehicles.show', $vehicle));
        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'brand_id' => $updatedBrand->id,
            'category_id' => $updatedCategory->id,
            'name' => 'Gixxer',
        ]);

        $deleteResponse = $this->actingAs($user)->delete(route('vehicles.destroy', $vehicle));

        $deleteResponse->assertRedirect(route('vehicles.index'));
        $this->assertDatabaseMissing('vehicles', ['id' => $vehicle->id]);
    }

    public function test_vehicle_index_can_be_filtered()
    {
        $user = $this->createUserWithRole();
        $matchingBrand = Brand::create(['name' => 'Yamaha']);
        $matchingCategory = Category::create(['name' => 'Sports']);
        $otherBrand = Brand::create(['name' => 'Hero']);
        $otherCategory = Category::create(['name' => 'Scooter']);

        Vehicle::create([
            'brand_id' => $matchingBrand->id,
            'category_id' => $matchingCategory->id,
            'name' => 'R15 V4',
            'code' => 'YMH-01',
            'model' => 'Race Edition',
            'registration_number' => 'DHK-R15',
        ]);

        Vehicle::create([
            'brand_id' => $matchingBrand->id,
            'category_id' => $matchingCategory->id,
            'name' => 'FZ-X',
            'code' => 'YMH-02',
            'model' => 'Classic',
            'registration_number' => 'DHK-FZX',
        ]);

        Vehicle::create([
            'brand_id' => $otherBrand->id,
            'category_id' => $otherCategory->id,
            'name' => 'Pleasure',
            'code' => 'HER-01',
            'model' => 'Scooter',
            'registration_number' => 'DHK-PLS',
        ]);

        $response = $this->actingAs($user)->get(route('vehicles.index', [
            'search' => 'R15',
            'brand_id' => $matchingBrand->id,
            'category_id' => $matchingCategory->id,
        ]));

        $response->assertOk();
        $response->assertSee('R15 V4');
        $response->assertDontSee('FZ-X');
        $response->assertDontSee('Pleasure');
    }
}
