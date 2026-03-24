<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Location;
use App\Models\Purchase;
use App\Models\Sell;
use App\Models\SellDocument;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

class SellTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    public function test_authenticated_users_can_create_a_sale_with_documents()
    {
        Storage::fake('public');

        $user = $this->createUserWithRole();
        $vehicle = $this->createVehicle();

        Purchase::create([
            'vehicle_id' => $vehicle->id,
            'name' => 'Owner One',
            'quantity' => 5,
            'buying_price_from_owner' => '135000',
            'purchasing_date' => '2026-03-15',
        ]);

        $response = $this->actingAs($user)->post(route('sells.store'), [
            'vehicle_id' => $vehicle->id,
            'name' => 'Customer One',
            'father_name' => 'Father One',
            'address' => 'Dhaka, Bangladesh',
            'mobile_number' => '01711111111',
            'quantity' => '2',
            'selling_price_to_customer' => '175000.00',
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'payment_information' => 'Paid in full at showroom counter',
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
            'quantity' => 2,
            'payment_status' => 'paid',
            'payment_method' => 'cash',
        ]);

        $this->assertDatabaseCount('sell_documents', 7);
        Storage::disk('public')->assertExists($sell->documents()->first()->file_path);
    }

    public function test_authenticated_users_can_update_and_delete_a_sale()
    {
        Storage::fake('public');

        $user = $this->createUserWithRole();
        $vehicle = $this->createVehicle();
        $updatedVehicle = $this->createVehicle([
            'name' => 'Suzuki Gixxer SF',
            'code' => 'BM-SELL-2',
        ]);

        Purchase::create([
            'vehicle_id' => $vehicle->id,
            'name' => 'Owner For Vehicle One',
            'quantity' => 3,
            'buying_price_from_owner' => '85000',
            'purchasing_date' => '2026-03-14',
        ]);

        Purchase::create([
            'vehicle_id' => $updatedVehicle->id,
            'name' => 'Owner For Vehicle Two',
            'quantity' => 4,
            'buying_price_from_owner' => '90000',
            'purchasing_date' => '2026-03-16',
        ]);

        $sell = Sell::create([
            'vehicle_id' => $vehicle->id,
            'name' => 'Old Customer',
            'father_name' => 'Old Father',
            'address' => 'Old Address',
            'mobile_number' => '01811111111',
            'quantity' => 1,
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
            'quantity' => 2,
            'selling_price_to_customer' => '115000',
            'payment_status' => 'partial',
            'payment_method' => 'card',
            'payment_information' => 'Card payment pending final settlement',
            'selling_date' => '2026-03-17',
            'extra_additional_note' => 'Updated note',
            'remove_documents' => ['picture', 'insurance'],
            'registration_copy' => UploadedFile::fake()->create('registration.pdf', 100, 'application/pdf'),
        ]);

        $updateResponse->assertRedirect(route('sells.show', $sell));

        $sell->refresh();

        $this->assertSame('New Customer', $sell->name);
        $this->assertSame($updatedVehicle->id, $sell->vehicle_id);
        $this->assertSame(2, $sell->quantity);
        $this->assertSame('partial', $sell->payment_status);
        $this->assertSame('card', $sell->payment_method);
        $this->assertDatabaseMissing('sell_documents', ['id' => $picture->id]);
        $this->assertDatabaseMissing('sell_documents', ['id' => $insurance->id]);
        $this->assertDatabaseHas('sell_documents', ['sell_id' => $sell->id, 'type' => 'registration_copy']);

        $deleteResponse = $this->actingAs($user)->delete(route('sells.destroy', $sell));

        $deleteResponse->assertRedirect(route('sells.index'));
        $this->assertDatabaseMissing('sells', ['id' => $sell->id]);
    }

    public function test_cannot_create_a_sale_for_a_vehicle_that_is_not_in_stock()
    {
        $user = $this->createUserWithRole();
        $vehicle = $this->createVehicle();

        $response = $this->actingAs($user)->post(route('sells.store'), [
            'vehicle_id' => $vehicle->id,
            'name' => 'Customer Two',
            'quantity' => 1,
            'selling_price_to_customer' => '125000',
            'selling_date' => '2026-03-17',
        ]);

        $response->assertSessionHasErrors('vehicle_id');
        $this->assertDatabaseCount('sells', 0);
    }

    public function test_sale_records_reduce_available_stock_quantity()
    {
        $user = $this->createUserWithRole();
        $vehicle = $this->createVehicle();

        Purchase::create([
            'vehicle_id' => $vehicle->id,
            'name' => 'Owner One',
            'quantity' => 4,
            'buying_price_from_owner' => '145000',
            'purchasing_date' => '2026-03-15',
        ]);

        Sell::create([
            'vehicle_id' => $vehicle->id,
            'name' => 'Customer One',
            'quantity' => 3,
            'selling_price_to_customer' => '165000',
            'selling_date' => '2026-03-16',
        ]);

        $response = $this->actingAs($user)->post(route('sells.store'), [
            'vehicle_id' => $vehicle->id,
            'name' => 'Customer Two',
            'quantity' => 2,
            'selling_price_to_customer' => '170000',
            'selling_date' => '2026-03-17',
        ]);

        $response->assertSessionHasErrors('quantity');
        $this->assertDatabaseCount('sells', 1);
        $this->assertSame(1, $vehicle->fresh()->available_stock_quantity);
    }

    public function test_sale_form_only_lists_vehicles_that_are_currently_in_stock()
    {
        $user = $this->createUserWithRole();
        $inStockVehicle = $this->createVehicle([
            'name' => 'Ready To Sell',
            'code' => 'READY',
        ]);
        $awaitingPurchaseVehicle = $this->createVehicle([
            'name' => 'Awaiting Purchase',
            'code' => 'AWAIT',
        ]);

        Purchase::create([
            'vehicle_id' => $inStockVehicle->id,
            'name' => 'Owner One',
            'quantity' => 2,
            'buying_price_from_owner' => '145000',
            'purchasing_date' => '2026-03-15',
        ]);

        $response = $this->actingAs($user)->get(route('sells.create'));

        $response->assertOk();
        $response->assertSee('Ready To Sell');
        $response->assertDontSee('Awaiting Purchase');
    }

    public function test_sale_form_shows_purchase_costs_and_profit_preview_for_selected_vehicle()
    {
        $user = $this->createUserWithRole();
        $vehicle = $this->createVehicle([
            'name' => 'Margin Bike',
            'code' => 'MARGIN-001',
        ]);

        $purchase = Purchase::create([
            'vehicle_id' => $vehicle->id,
            'name' => 'Margin Owner',
            'quantity' => 2,
            'buying_price_from_owner' => '100000',
            'purchasing_date' => '2026-03-15',
        ]);

        $purchase->modifyingCosts()->createMany([
            ['reason' => 'Registration fee', 'cost' => '2000'],
            ['reason' => 'Service charge', 'cost' => '1000'],
        ]);

        $sell = Sell::create([
            'vehicle_id' => $vehicle->id,
            'name' => 'Margin Customer',
            'quantity' => 1,
            'selling_price_to_customer' => '120000',
            'selling_date' => '2026-03-16',
        ]);

        $response = $this->actingAs($user)->get(route('sells.edit', $sell));

        $response->assertOk();
        $response->assertSee('Profit Preview');
        $response->assertSee('Excluding modifying cost: BDT 100,000.00 total');
        $response->assertSee('Including modifying cost: BDT 103,000.00 total');
        $response->assertSee('BDT 70,000.00');
        $response->assertSee('BDT 68,500.00');
        $response->assertSee('140.00%');
        $response->assertSee('133.01%');
    }

    public function test_sale_index_can_be_filtered()
    {
        $user = $this->createUserWithRole();
        $matchingVehicle = $this->createVehicle([
            'name' => 'Filter Ready Bike',
            'code' => 'SELL-MATCH',
        ]);
        $otherVehicle = $this->createVehicle([
            'name' => 'Filter Hidden Bike',
            'code' => 'SELL-HIDE',
        ]);

        Purchase::create([
            'vehicle_id' => $matchingVehicle->id,
            'name' => 'Owner Match',
            'quantity' => 5,
            'buying_price_from_owner' => '130000',
            'purchasing_date' => '2026-03-10',
        ]);

        Purchase::create([
            'vehicle_id' => $otherVehicle->id,
            'name' => 'Owner Hidden',
            'quantity' => 5,
            'buying_price_from_owner' => '135000',
            'purchasing_date' => '2026-03-10',
        ]);

        Sell::create([
            'vehicle_id' => $matchingVehicle->id,
            'name' => 'Matched Customer',
            'quantity' => 2,
            'selling_price_to_customer' => '160000',
            'selling_date' => '2026-03-17',
        ]);

        Sell::create([
            'vehicle_id' => $matchingVehicle->id,
            'name' => 'Old Customer',
            'quantity' => 1,
            'selling_price_to_customer' => '150000',
            'selling_date' => '2026-03-11',
        ]);

        Sell::create([
            'vehicle_id' => $otherVehicle->id,
            'name' => 'Hidden Customer',
            'quantity' => 2,
            'selling_price_to_customer' => '165000',
            'selling_date' => '2026-03-17',
        ]);

        $response = $this->actingAs($user)->get(route('sells.index', [
            'search' => 'Matched Customer',
            'brand_id' => $matchingVehicle->brand_id,
            'category_id' => $matchingVehicle->category_id,
            'date_from' => '2026-03-15',
        ]));

        $response->assertOk();
        $response->assertSee('Matched Customer');
        $response->assertDontSee('Old Customer');
        $response->assertDontSee('Hidden Customer');
    }

    public function test_sale_index_only_shows_records_from_the_active_location()
    {
        $user = $this->createUserWithRole();
        $vehicle = $this->createVehicle();
        $otherLocation = Location::create([
            'name' => 'Rajshahi Branch',
            'code' => 'RAJ',
            'email' => 'rajshahi@bikemartbd.com',
            'phone' => '01700-987654',
            'address' => 'Rajshahi',
            'is_active' => true,
        ]);

        Purchase::create([
            'vehicle_id' => $vehicle->id,
            'name' => 'Main Owner',
            'quantity' => 3,
            'buying_price_from_owner' => '120000',
            'purchasing_date' => '2026-03-15',
        ]);

        Purchase::create([
            'location_id' => $otherLocation->id,
            'vehicle_id' => $vehicle->id,
            'name' => 'Other Owner',
            'quantity' => 3,
            'buying_price_from_owner' => '118000',
            'purchasing_date' => '2026-03-15',
        ]);

        Sell::create([
            'vehicle_id' => $vehicle->id,
            'name' => 'Main Branch Customer',
            'quantity' => 1,
            'selling_price_to_customer' => '150000',
            'selling_date' => '2026-03-18',
        ]);

        Sell::create([
            'location_id' => $otherLocation->id,
            'vehicle_id' => $vehicle->id,
            'name' => 'Other Branch Customer',
            'quantity' => 1,
            'selling_price_to_customer' => '149000',
            'selling_date' => '2026-03-18',
        ]);

        $response = $this->actingAs($user)->get(route('sells.index'));

        $response->assertOk();
        $response->assertSee('Main Branch Customer');
        $response->assertDontSee('Other Branch Customer');
    }

    public function test_sale_invoice_can_be_rendered_for_printing()
    {
        $user = $this->createUserWithRole();
        $vehicle = $this->createVehicle([
            'name' => 'Invoice Bike',
            'code' => 'INV-001',
        ]);

        Purchase::create([
            'vehicle_id' => $vehicle->id,
            'name' => 'Invoice Owner',
            'quantity' => 1,
            'buying_price_from_owner' => '120000',
            'purchasing_date' => '2026-03-17',
        ]);

        $sell = Sell::create([
            'vehicle_id' => $vehicle->id,
            'name' => 'Invoice Customer',
            'father_name' => 'Invoice Father',
            'address' => 'Dhaka',
            'mobile_number' => '01712345678',
            'quantity' => 1,
            'selling_price_to_customer' => '150000',
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'payment_information' => 'Paid at counter',
            'selling_date' => '2026-03-18',
            'extra_additional_note' => 'Deliver with all papers.',
        ]);

        $sell->documents()->create([
            'type' => 'registration_copy',
            'file_path' => 'sells/test/registration.pdf',
            'original_name' => 'registration.pdf',
        ]);

        $response = $this->actingAs($user)->get(route('sells.invoice', [
            'sell' => $sell,
            'copy' => 'customer',
        ]));

        $response->assertOk();
        $response->assertSee('Vehicle Sales Invoice');
        $response->assertSee('Customer Copy');
        $response->assertSee('Selected output:');
        $response->assertSee('single A4 portrait sheet');
        $response->assertSee('sheet is-single', false);
        $response->assertSee('Invoice Customer');
        $response->assertSee('Invoice Bike');
        $response->assertSee('Registration Copy');
        $response->assertSee($sell->invoice_number);

        $bothResponse = $this->actingAs($user)->get(route('sells.invoice', [
            'sell' => $sell,
            'copy' => 'both',
        ]));

        $bothResponse->assertOk();
        $bothResponse->assertSee('Both Copies');
        $bothResponse->assertSee('sheet is-double', false);
        $bothResponse->assertSee('Customer Copy');
        $bothResponse->assertSee('Office Copy');
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
