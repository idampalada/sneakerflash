<?php
// File: database/migrations/xxxx_xx_xx_add_destination_fields_to_orders_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Add destination fields for search-based approach
            $table->string('shipping_destination_id')->nullable()->after('shipping_postal_code');
            $table->text('shipping_destination_label')->nullable()->after('shipping_destination_id');
            
            // Make some existing fields nullable for backward compatibility
            $table->integer('shipping_province_id')->nullable()->change();
            $table->integer('shipping_city_id')->nullable()->change();
            $table->integer('shipping_district_id')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['shipping_destination_id', 'shipping_destination_label']);
        });
    }
};