<?php

namespace Tests\Feature;

use App\Models\BusinessSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

class BusinessSettingTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    public function test_authenticated_users_can_view_business_settings_page()
    {
        $user = $this->createUserWithRole();

        $response = $this->actingAs($user)->get(route('business-settings.edit'));

        $response->assertOk();
        $response->assertSee('Business Information');
    }

    public function test_authenticated_users_can_update_business_settings()
    {
        Storage::fake('public');

        $user = $this->createUserWithRole();

        $response = $this->actingAs($user)->from(route('business-settings.edit'))->put(
            route('business-settings.update'),
            [
                'business_name' => 'BikeMart Headquarters',
                'email' => 'info@bikemartbd.com',
                'phone' => '+8801700000000',
                'address' => 'Dhaka, Bangladesh',
                'website' => 'https://bikemartbd.com',
                'currency_code' => 'BDT',
                'timezone' => 'Asia/Dhaka',
                'invoice_footer' => 'Thank you for shopping with BikeMart.',
                'show_stock_information' => '0',
                'show_quantity_fields' => '0',
                'show_stock_management_module' => '0',
                'logo' => UploadedFile::fake()->image('logo.png'),
            ]
        );

        $response->assertRedirect(route('business-settings.edit'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('business_settings', [
            'business_name' => 'BikeMart Headquarters',
            'email' => 'info@bikemartbd.com',
            'phone' => '+8801700000000',
            'currency_code' => 'BDT',
            'timezone' => 'Asia/Dhaka',
            'show_stock_information' => false,
            'show_quantity_fields' => false,
            'show_stock_management_module' => false,
        ]);

        $businessSetting = BusinessSetting::first();

        $this->assertNotNull($businessSetting);
        Storage::disk('public')->assertExists($businessSetting->logo_path);
    }
}
