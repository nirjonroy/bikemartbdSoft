<?php

namespace Tests\Feature;

use App\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

class BrandTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    public function test_authenticated_users_can_create_update_and_delete_a_brand()
    {
        $user = $this->createUserWithRole();

        $storeResponse = $this->actingAs($user)->post(route('brands.store'), [
            'name' => 'Yamaha',
            'notes' => 'Japanese motorcycle brand.',
        ]);

        $brand = Brand::first();

        $storeResponse->assertRedirect(route('brands.edit', $brand));
        $this->assertDatabaseHas('brands', ['name' => 'Yamaha']);

        $updateResponse = $this->actingAs($user)->put(route('brands.update', $brand), [
            'name' => 'Yamaha Motors',
            'notes' => 'Updated brand note.',
        ]);

        $updateResponse->assertRedirect(route('brands.edit', $brand));
        $this->assertDatabaseHas('brands', ['id' => $brand->id, 'name' => 'Yamaha Motors']);

        $deleteResponse = $this->actingAs($user)->delete(route('brands.destroy', $brand));

        $deleteResponse->assertRedirect(route('brands.index'));
        $this->assertDatabaseMissing('brands', ['id' => $brand->id]);
    }
}
