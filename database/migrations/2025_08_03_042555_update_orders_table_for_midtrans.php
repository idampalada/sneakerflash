<?php
// File: database/migrations/2025_08_03_040859_update_orders_table_for_midtrans.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Add missing columns for Midtrans integration
            
            // Snap token for Midtrans
            if (!Schema::hasColumn('orders', 'snap_token')) {
                $table->string('snap_token')->nullable()->after('payment_token');
            }
            
            // Payment response to store Midtrans webhook data
            if (!Schema::hasColumn('orders', 'payment_response')) {
                $table->jsonb('payment_response')->nullable()->after('snap_token');
            }
            
            // Add shipping destination fields for RajaOngkir V2
            if (!Schema::hasColumn('orders', 'shipping_destination_id')) {
                $table->string('shipping_destination_id')->nullable()->after('shipping_address');
            }
            
            if (!Schema::hasColumn('orders', 'shipping_destination_label')) {
                $table->text('shipping_destination_label')->nullable()->after('shipping_destination_id');
            }
            
            if (!Schema::hasColumn('orders', 'shipping_postal_code')) {
                $table->string('shipping_postal_code', 10)->nullable()->after('shipping_destination_label');
            }
            
            if (!Schema::hasColumn('orders', 'shipping_method')) {
                $table->string('shipping_method')->nullable()->after('shipping_postal_code');
            }
            
            if (!Schema::hasColumn('orders', 'shipping_cost')) {
                $table->decimal('shipping_cost', 15, 2)->default(0)->after('shipping_method');
            }
            
            // Add order status (separate from general status)
            if (!Schema::hasColumn('orders', 'order_status')) {
                $table->string('order_status')->default('pending')->after('status');
            }
            
            // Make sure payment_method exists and has proper length
            if (!Schema::hasColumn('orders', 'payment_method')) {
                $table->string('payment_method')->default('bank_transfer')->after('store_origin');
            }
            
            // Make sure payment_status exists
            if (!Schema::hasColumn('orders', 'payment_status')) {
                $table->string('payment_status')->default('pending')->after('payment_method');
            }
            
            // Update existing columns if needed
            // Convert shipping_amount to shipping_cost for consistency
            if (Schema::hasColumn('orders', 'shipping_amount') && !Schema::hasColumn('orders', 'shipping_cost')) {
                $table->renameColumn('shipping_amount', 'shipping_cost');
            }
        });
        
        // Add indexes for better query performance
        Schema::table('orders', function (Blueprint $table) {
            try {
                $table->index('payment_status', 'orders_payment_status_idx');
            } catch (\Exception $e) {
                // Index might already exist
            }
            
            try {
                $table->index('order_status', 'orders_order_status_idx');
            } catch (\Exception $e) {
                // Index might already exist
            }
            
            try {
                $table->index('status', 'orders_status_idx');
            } catch (\Exception $e) {
                // Index might already exist
            }
            
            try {
                $table->index('created_at', 'orders_created_at_idx');
            } catch (\Exception $e) {
                // Index might already exist
            }
            
            try {
                $table->index('order_number', 'orders_order_number_idx');
            } catch (\Exception $e) {
                // Index might already exist
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Drop added columns
            $columnsToDrop = [];
            
            if (Schema::hasColumn('orders', 'snap_token')) {
                $columnsToDrop[] = 'snap_token';
            }
            
            if (Schema::hasColumn('orders', 'payment_response')) {
                $columnsToDrop[] = 'payment_response';
            }
            
            if (Schema::hasColumn('orders', 'shipping_destination_id')) {
                $columnsToDrop[] = 'shipping_destination_id';
            }
            
            if (Schema::hasColumn('orders', 'shipping_destination_label')) {
                $columnsToDrop[] = 'shipping_destination_label';
            }
            
            if (Schema::hasColumn('orders', 'shipping_postal_code')) {
                $columnsToDrop[] = 'shipping_postal_code';
            }
            
            if (Schema::hasColumn('orders', 'shipping_method')) {
                $columnsToDrop[] = 'shipping_method';
            }
            
            if (Schema::hasColumn('orders', 'order_status')) {
                $columnsToDrop[] = 'order_status';
            }
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
            
            // Drop indexes
            $indexesToDrop = [
                'orders_payment_status_idx',
                'orders_order_status_idx',
                'orders_status_idx',
                'orders_created_at_idx',
                'orders_order_number_idx'
            ];
            
            foreach ($indexesToDrop as $index) {
                try {
                    $table->dropIndex($index);
                } catch (\Exception $e) {
                    // Index might not exist
                }
            }
        });
    }
};