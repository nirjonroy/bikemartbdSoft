<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignId('vehicle_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });

        Schema::table('sells', function (Blueprint $table) {
            $table->foreignId('vehicle_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vehicle_id');
        });

        Schema::table('sells', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vehicle_id');
        });
    }
};
