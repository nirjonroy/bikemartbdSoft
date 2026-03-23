<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $location = Location::query()->first();

        $user = User::updateOrCreate(
            ['email' => 'admin@bikemartbd.com'],
            [
                'default_location_id' => $location?->id,
                'name' => 'Admin',
                'email_verified_at' => now(),
                'password' => Hash::make('bikemart321#'),
            ]
        );

        $user->syncRoles(['super-admin']);
        if ($location) {
            $user->locations()->syncWithoutDetaching([$location->id]);
        }
    }
}
