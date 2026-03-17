<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    public function test_authenticated_users_can_create_update_and_delete_a_category()
    {
        $user = $this->createUserWithRole();

        $storeResponse = $this->actingAs($user)->post(route('categories.store'), [
            'name' => 'Sports Bike',
            'notes' => 'Performance motorcycles.',
        ]);

        $category = Category::first();

        $storeResponse->assertRedirect(route('categories.edit', $category));
        $this->assertDatabaseHas('categories', ['name' => 'Sports Bike']);

        $updateResponse = $this->actingAs($user)->put(route('categories.update', $category), [
            'name' => 'Sports Touring',
            'notes' => 'Updated category note.',
        ]);

        $updateResponse->assertRedirect(route('categories.edit', $category));
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Sports Touring']);

        $deleteResponse = $this->actingAs($user)->delete(route('categories.destroy', $category));

        $deleteResponse->assertRedirect(route('categories.index'));
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }
}
