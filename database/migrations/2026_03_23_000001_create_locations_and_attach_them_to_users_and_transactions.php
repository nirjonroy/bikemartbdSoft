<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 50)->unique();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        $defaultLocationId = DB::table('locations')->insertGetId([
            'name' => 'Main Branch',
            'code' => 'MAIN',
            'email' => 'main@bikemartbd.com',
            'phone' => '01700-000000',
            'address' => 'Default location created during multi-location migration.',
            'is_active' => true,
            'notes' => 'Fallback location for existing records.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('default_location_id')
                ->nullable()
                ->after('id')
                ->constrained('locations')
                ->nullOnDelete();
        });

        Schema::create('location_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['location_id', 'user_id']);
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignId('location_id')
                ->nullable()
                ->after('vehicle_id')
                ->constrained('locations')
                ->nullOnDelete();
        });

        Schema::table('sells', function (Blueprint $table) {
            $table->foreignId('location_id')
                ->nullable()
                ->after('vehicle_id')
                ->constrained('locations')
                ->nullOnDelete();
        });

        DB::table('users')
            ->whereNull('default_location_id')
            ->update(['default_location_id' => $defaultLocationId]);

        DB::table('purchases')
            ->whereNull('location_id')
            ->update(['location_id' => $defaultLocationId]);

        DB::table('sells')
            ->whereNull('location_id')
            ->update(['location_id' => $defaultLocationId]);

        $timestamp = now();
        $userRows = DB::table('users')->pluck('id')->map(function ($userId) use ($defaultLocationId, $timestamp) {
            return [
                'location_id' => $defaultLocationId,
                'user_id' => $userId,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        })->all();

        if (! empty($userRows)) {
            DB::table('location_user')->insert($userRows);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sells', function (Blueprint $table) {
            $table->dropConstrainedForeignId('location_id');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('location_id');
        });

        Schema::dropIfExists('location_user');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_location_id');
        });

        Schema::dropIfExists('locations');
    }
};
