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
            $table->string('payment_status', 20)->default('unpaid')->after('buying_price_from_owner');
            $table->string('payment_method', 50)->nullable()->after('payment_status');
            $table->text('payment_information')->nullable()->after('payment_method');
        });

        Schema::table('sells', function (Blueprint $table) {
            $table->string('payment_status', 20)->default('unpaid')->after('selling_price_to_customer');
            $table->string('payment_method', 50)->nullable()->after('payment_status');
            $table->text('payment_information')->nullable()->after('payment_method');
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
            $table->dropColumn(['payment_status', 'payment_method', 'payment_information']);
        });

        Schema::table('sells', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'payment_method', 'payment_information']);
        });
    }
};
