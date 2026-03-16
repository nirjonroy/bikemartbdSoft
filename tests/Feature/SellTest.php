<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Sell;
use App\Models\SellDocument;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SellTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_can_create_a_sale_with_documents()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $vehicle = $this->createVehicle();

        $response = $this->actingAs($user)->post(route('sells.store'), [
            'vehicle_id' => $vehicle->id,
            'name' => 'Customer One',
            'father_name' => 'Father One',
            'address' => 'Dhaka, Bangladesh',
            'mobile_number' => '01711111111',
            'selling_price_to_customer' => '175000.00',
            'selling_date' => '2026-03-16',
            'extra_additional_note' => 'Customer collected documents.',
            'picture' => UploadedFile::fake()->image('customer-bike.jpg'),
            'registration_copy' => UploadedFile::fake()->create('registration.pdf', 100, 'application/pdf'),
            'smart_card' => UploadedFile::fake()->image('smart-card.png'),
            'nid_copy' => UploadedFile::fake()->image('nid.png'),
            'tax_token' => UploadedFile::fake()->create('tax-token.pdf', 100, 'application/pdf'),
            'fitness_paper' => UploadedFile::fake()->create('fitness.pdf', 100, 'application/pdf'),
            'insurance' => UploadedFile::fake()->create('insurance.pdf', 100, 'application/pdf'),
        ]);

        $sell = Sell::first();

        $response->assertRedirect(route('sells.show', $sell));

        $this->assertDatabaseHas('sells', [
            'vehicle_id' => $vehicle->id,
            'name' => 'Customer One',
            'mobile_number' => '01711111111',
        ]);

        $this->assertDatabaseCount('sell_documents', 7);
        Storage::disk('public')->assertExists($sell->documents()->first()->file_path);
    }

    public function test_authenticated_users_can_update_and_delete_a_sale()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $vehicle = $this->createVehicle();
        $updatedVehicle = $this->createVehicle([
            'name' => 'Suzuki Gixxer SF',
            'code' => 'BM-SELL-2',
        ]);

        $sell = Sell::create([
            'vehicle_id' => $vehicle->id,
            'name' => 'Old Customer',
            'father_name' => 'Old Father',
            'address' => 'Old Address',
            'mobile_number' => '01811111111',
            'selling_price_to_customer' => '95000',
            'selling_date' => '2026-03-15',
            'extra_additional_note' => 'Old note',
        ]);

        $picture = $sell->documents()->create([
            'type' => SellDocument::TYPE_PICTURE,
            'file_path' => UploadedFile::fake()->image('old-picture.png')->store("sells/{$sell->id}/picture", 'public'),
            'original_name' => 'old-picture.png',
        ]);

        $insurance = $sell->documents()->create([
            'type' => 'insurance',
            'file_path' => UploadedFile::fake()->create('old-insurance.pdf', 100, 'application/pdf')->store("sells/{$sell->id}/insurance", 'public'),
            'original_name' => 'old-insurance.pdf',
        ]);

        $updateResponse = $this->actingAs($user)->put(route('sells.update', $sell), [
            'vehicle_id' => $updatedVehicle->id,
            'name' => 'New Customer',
            'father_name' => 'New Father',
            'address' => 'New Address',
            'mobile_number' => '01911111111',
            'selling_price_to_customer' => '115000',
            'selling_date' => '2026-03-17',
            'extra_additional_note' => 'Updated note',
            'remove_documents' => ['picture', 'insurance'],
            'registration_copy' => UploadedFile::fake()->create('registration.pdf', 100, 'application/pdf'),
        ]);

        $updateResponse->assertRedirect(route('sells.show', $sell));

        $sell->refresh();

        $this->assertSame('New Customer', $sell->name);
        $this->assertSame($updatedVehicle->id, $sell->vehicle_id);
        $this->assertDatabaseMissing('sell_documents', ['id' => $picture->id]);
        $this->assertDatabaseMissing('sell_documents', ['id' => $insurance->id]);
        $this->assertDatabaseHas('sell_documents', ['sell_id' => $sell->id, 'type' => 'registration_copy']);

        $deleteResponse = $this->actingAs($user)->delete(route('sells.destroy', $sell));

        $deleteResponse->assertRedirect(route('sells.index'));
        $this->assertDatabaseMissing('sells', ['id' => $sell->id]);
    }

    private function createVehicle(array $overrides = []): Vehicle
    {
        $brand = Brand::create(['name' => uniqid('Brand ', true)]);
        $category = Category::create(['name' => uniqid('Category ', true)]);

        return Vehicle::create(array_merge([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Honda CB Hornet',
            'code' => uniqid('BM-', true),
            'model' => 'CBS',
            'registration_number' => uniqid('REG-', true),
            'engine_number' => uniqid('ENG-', true),
            'chassis_number' => uniqid('CHS-', true),
            'color' => 'Red',
            'year' => 2023,
        ], $overrides));
    }
}
