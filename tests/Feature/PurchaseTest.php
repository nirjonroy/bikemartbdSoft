<?php

namespace Tests\Feature;

use App\Models\Purchase;
use App\Models\PurchaseDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PurchaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_can_create_a_purchase_with_documents_and_costs()
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('purchases.store'), [
            'name' => 'Rahim Uddin',
            'father_name' => 'Karim Uddin',
            'address' => 'Dhaka, Bangladesh',
            'mobile_number' => '01700000000',
            'buying_price_from_owner' => '125000.00',
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
            'name' => 'Rahim Uddin',
            'mobile_number' => '01700000000',
        ]);

        $this->assertDatabaseCount('purchase_documents', 8);
        $this->assertDatabaseCount('purchase_modifying_costs', 2);

        Storage::disk('public')->assertExists($purchase->documents()->first()->file_path);
    }

    public function test_authenticated_users_can_update_and_delete_a_purchase()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $purchase = Purchase::create([
            'name' => 'Old Name',
            'father_name' => 'Old Father',
            'address' => 'Old Address',
            'mobile_number' => '01800000000',
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
            'name' => 'New Name',
            'father_name' => 'New Father',
            'address' => 'New Address',
            'mobile_number' => '01900000000',
            'buying_price_from_owner' => '145000',
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
        $this->assertDatabaseMissing('purchase_documents', ['id' => $document->id]);
        $this->assertDatabaseMissing('purchase_documents', ['id' => $picture->id]);
        $this->assertDatabaseCount('purchase_modifying_costs', 1);

        $deleteResponse = $this->actingAs($user)->delete(route('purchases.destroy', $purchase));

        $deleteResponse->assertRedirect(route('purchases.index'));
        $this->assertDatabaseMissing('purchases', ['id' => $purchase->id]);
    }
}
