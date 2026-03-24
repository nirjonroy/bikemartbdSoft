<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('business_settings', function (Blueprint $table) {
            $table->boolean('show_stock_information')->default(true)->after('logo_path');
            $table->boolean('show_quantity_fields')->default(true)->after('show_stock_information');
            $table->boolean('show_stock_management_module')->default(true)->after('show_quantity_fields');
        });
    }

    public function down()
    {
        Schema::table('business_settings', function (Blueprint $table) {
            $table->dropColumn([
                'show_stock_information',
                'show_quantity_fields',
                'show_stock_management_module',
            ]);
        });
    }
};
