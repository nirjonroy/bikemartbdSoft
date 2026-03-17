<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        Schema::table('purchases', function (Blueprint $table) {
            $table->unsignedInteger('quantity')->default(1)->after('mobile_number');
        });

        Schema::table('sells', function (Blueprint $table) {
            $table->unsignedInteger('quantity')->default(1)->after('mobile_number');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });

        Schema::table('sells', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });
    }
};
