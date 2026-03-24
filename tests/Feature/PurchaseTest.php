<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Location;
use App\Models\Purchase;
use App\Models\PurchaseDocument;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

class PurchaseTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    public function test_authenticated_users_can_create_a_purchase_with_documents_and_costs()
    {
        Storage::fake('public');

        $user = $this->createUserWithRole();
        $vehicle = $this->createVehicle();

        $response = $this->actingAs($user)->post(route('purchases.store'), [
            'vehicle_id' => $vehicle->id,
            'name' => 'Rahim Uddin',
            'father_name' => 'Karim Uddin',
            'address' => 'Dhaka, Bangladesh',
            'mobile_number' => '01700000000',
            'quantity' => '4',
            'buying_price_from_owner' => '125000.00',
            'payment_status' => 'paid',
            'payment_method' => 'bank_transfer',
            'payment_information' => 'Bank reference TXN-1001',
            'purchasing_date' => '2026-03-16',
            'extra_additional_note' => 'Owner delivered all papers.',
            'pictures' => [
                UploadedFile::fake()->image('bike-front.jpg'),
                UploadedFile::fake()->image('bike-back.jpg'),
            ],
            'nid_copy' => UploadedFile::fake()->image('nid.png'),
            'registration_copy' => UploadedFile::fake()->create('registration.pdf', 100, 'application/pdf'),
            'smart_card' => UploadedFile::fake()->image('smart-card.png'),
            'tax_token' => UploadedFile::fake()->create('tax-token.pdf', 100, 'application/pdf'),
            'fitness_paper' => UploadedFile::fake()->create('fitness.pdf', 100, 'application/pdf'),
            'insurance' => UploadedFile::fake()->create('insurance.pdf', 100, 'application/pdf'),
            'modifying_costs' => [
                ['reason' => 'Paint correction', 'cost' => '1500'],
                ['reason' => 'Service', 'cost' => '2500'],
            ],
        ]);

        $purchase = Purchase::first();

        $response->assertRedirect(route('purchases.show', $purchase));

        $this->assertDatabaseHas('purchases', [
            'vehicle_id' => $vehicle->id,
            'name' => 'Rahim Uddin',
            'mobile_number' => '01700000000',
            'quantity' => 4,
            'payment_status' => 'paid',
            'payment_method' => 'bank_transfer',
        ]);

        $this->assertDatabaseCount('purchase_documents', 8);
        $this->assertDatabaseCount('purchase_modifying_costs', 2);

        Storage::disk('public')->assertExists($purchase->documents()->first()->file_path);
    }

    public function test_authenticated_users_can_update_and_delete_a_purchase()
    {
        Storage::fake('public');

        $user = $this->createUserWithRole();
        $vehicle = $this->createVehicle();
        $updatedVehicle = $this->createVehicle([
            'name' => 'Hero Xtreme 160R',
            'code' => 'BM-002',
        ]);

        $purchase = Purchase::create([
            'vehicle_id' => $vehicle->id,
            'name' => 'Old Name',
            'father_name' => 'Old Father',
            'address' => 'Old Address',
            'mobile_number' => '01800000000',
            'quantity' => 1,
            'buying_price_from_owner' => '100000',
            'purchasing_date' => '2026-03-15',
            'extra_additional_note' => 'Old note',
        ]);

        $document = $purchase->documents()->create([
            'type' => 'nid_copy',
            'file_path' => UploadedFile::fake()->image('old-nid.png')->store("purchases/{$purchase->id}/nid_copy", 'public'),
            'original_name' => 'old-nid.png',
        ]);

        $picture = $purchase->documents()->create([
            'type' => PurchaseDocument::TYPE_PICTURE,
            'file_path' => UploadedFile::fake()->image('old-picture.png')->store("purchases/{$purchase->id}/picture", 'public'),
            'original_name' => 'old-picture.png',
        ]);

        $purchase->modifyingCosts()->create([
            'reason' => 'Old repair',
            'cost' => '500',
        ]);

        $updateResponse = $this->actingAs($user)->put(route('purchases.update', $purchase), [
            'vehicle_id' => $updatedVehicle->id,
            'name' => 'New Name',
            'father_name' => 'New Father',
            'address' => 'New Address',
            'mobile_number' => '01900000000',
            'quantity' => 3,
            'buying_price_from_owner' => '145000',
            'payment_status' => 'partial',
            'payment_method' => 'mobile_banking',
            'payment_information' => 'bKash partial payment collected',
            'purchasing_date' => '2026-03-17',
            'extra_additional_note' => 'Updated note',
            'remove_documents' => ['nid_copy'],
            'remove_picture_ids' => [$picture->id],
            'registration_copy' => UploadedFile::fake()->create('registration.pdf', 100, 'application/pdf'),
            'modifying_costs' => [
                ['reason' => 'Wheel alignment', 'cost' => '800'],
            ],
        ]);

        $updateResponse->assertRedirect(route('purchases.show', $purchase));

        $purchase->refresh();

        $this->assertSame('New Name', $purchase->name);
        $this->assertSame($updatedVehicle->id, $purchase->vehicle_id);
        $this->assertSame(3, $purchase->quantity);
        $this->assertSame('partial', $purchase->payment_status);
        $this->assertSame('mobile_banking', $purchase->payment_method);
        $this->assertDatabaseMissing('purchase_documents', ['id' => $document->id]);
        $this->assertDatabaseMissing('purchase_documents', ['id' => $picture->id]);
        $this->assertDatabaseCount('purchase_modifying_costs', 1);

        $deleteResponse = $this->actingAs($user)->delete(route('purchases.destroy', $purchase));

        $deleteResponse->assertRedirect(route('purchases.index'));
        $this->assertDatabaseMissing('purchases', ['id' => $purchase->id]);
    }

    public function test_purchase_records_increase_stock_for_the_same_vehicle()
    {
        $user = $this->createUserWithRole();
        $vehicle = $this->createVehicle();

        Purchase::create([
            'vehicle_id' => $vehicle->id,
            'name' => 'Existing Owner',
            'quantity' => 3,
            'buying_price_from_owner' => '100000',
            'purchasing_date' => '2026-03-15',
        ]);

        $response = $this->actingAs($user)->post(route('purchases.store'), [
            'vehicle_id' => $vehicle->id,
            'name' => 'Second Owner',
            'quantity' => 2,
            'buying_price_from_owner' => '120000',
            'purchasing_date' => '2026-03-17',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('purchases', 2);
        $this->assertSame(5, $vehicle->fresh()->purchased_quantity);
        $this->assertSame(5, $vehicle->fresh()->available_stock_quantity);
    }

    public function test_purchase_form_lists_all_vehicle_products()
    {
        $user = $this->createUserWithRole();
        $inStockVehicle = $this->createVehicle([
            'name' => 'In Stock Bike',
            'code' => 'IN-STOCK',
        ]);
        $availableVehicle = $this->createVehicle([
            'name' => 'Available Bike',
            'code' => 'AVAILABLE',
        ]);

        Purchase::create([
            'vehicle_id' => $inStockVehicle->id,
            'name' => 'Owner One',
            'quantity' => 2,
            'buying_price_from_owner' => '100000',
            'purchasing_date' => '2026-03-15',
        ]);

        $response = $this->actingAs($user)->get(route('purchases.create'));

        $response->assertOk();
        $response->assertSee('Available Bike');
        $response->assertSee('In Stock Bike');
    }

    public function test_purchase_form_shows_vehicle_search_and_quick_add_controls()
    {
        $user = $this->createUserWithRole();
        $this->createVehicle([
            'name' => 'Searchable Bike',
            'code' => 'SEARCH-001',
        ]);

        $response = $this->actingAs($user)->get(route('purchases.create'));

        $response->assertOk();
        $response->assertSee('Search by vehicle, code, registration, engine, brand or category');
        $response->assertSee('Add Vehicle');
        $response->assertSee('quickVehicleModal', false);
    }

    public function test_purchase_index_can_be_filtered()
    {
        $user = $this->createUserWithRole();
        $matchingVehicle = $this->createVehicle([
            'name' => 'Filter Match Bike',
            'code' => 'FILTER-MATCH',
        ]);
        $otherVehicle = $this->createVehicle([
            'name' => 'Hidden Bike',
            'code' => 'FILTER-HIDE',
        ]);

        Purchase::create([
            'vehicle_id' => $matchingVehicle->id,
            'name' => 'Matched Owner',
            'quantity' => 2,
            'buying_price_from_owner' => '100000',
            'purchasing_date' => '2026-03-17',
        ]);

        Purchase::create([
            'vehicle_id' => $matchingVehicle->id,
            'name' => 'Old Owner',
            'quantity' => 1,
            'buying_price_from_owner' => '98000',
            'purchasing_date' => '2026-03-10',
        ]);

        Purchase::create([
            'vehicle_id' => $otherVehicle->id,
            'name' => 'Hidden Owner',
            'quantity' => 3,
            'buying_price_from_owner' => '110000',
            'purchasing_date' => '2026-03-17',
        ]);

        $response = $this->actingAs($user)->get(route('purchases.index', [
            'search' => 'Matched Owner',
            'brand_id' => $matchingVehicle->brand_id,
            'category_id' => $matchingVehicle->category_id,
            'date_from' => '2026-03-15',
        ]));

        $response->assertOk();
        $response->assertSee('Matched Owner');
        $response->assertDontSee('Old Owner');
        $response->assertDontSee('Hidden Owner');
    }

    public function test_purchase_index_only_shows_records_from_the_active_location()
    {
        $user = $this->createUserWithRole();
        $vehicle = $this->createVehicle();
        $otherLocation = Location::create([
            'name' => 'Sylhet Branch',
            'code' => 'SYL',
            'email' => 'sylhet@bikemartbd.com',
            'phone' => '01700-654321',
            'address' => 'Sylhet',
            'is_active' => true,
        ]);

        Purchase::create([
            'vehicle_id' => $vehicle->id,
            'name' => 'Main Branch Owner',
            'quantity' => 2,
            'buying_price_from_owner' => '100000',
            'purchasing_date' => '2026-03-18',
        ]);

        Purchase::create([
            'location_id' => $otherLocation->id,
            'vehicle_id' => $vehicle->id,
            'name' => 'Other Branch Owner',
            'quantity' => 1,
            'buying_price_from_owner' => '98000',
            'purchasing_date' => '2026-03-18',
        ]);

        $response = $this->actingAs($user)->get(route('purchases.index'));

        $response->assertOk();
        $response->assertSee('Main Branch Owner');
        $response->assertDontSee('Other Branch Owner');
    }

    private function createVehicle(array $overrides = []): Vehicle
    {
        $brand = Brand::create(['name' => uniqid('Brand ', true)]);
        $category = Category::create(['name' => uniqid('Category ', true)]);

        return Vehicle::create(array_merge([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Yamaha FZ-S',
            'code' => uniqid('BM-', true),
            'model' => 'FI',
            'registration_number' => uniqid('REG-', true),
            'engine_number' => uniqid('ENG-', true),
            'chassis_number' => uniqid('CHS-', true),
            'color' => 'Blue',
            'year' => 2024,
        ], $overrides));
    }
}
