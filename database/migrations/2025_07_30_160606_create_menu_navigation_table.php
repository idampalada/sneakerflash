<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Check if table already exists
        if (!Schema::hasTable('menu_navigation')) {
            Schema::create('menu_navigation', function (Blueprint $table) {
                $table->id();
                $table->string('menu_key')->unique();
                $table->string('menu_label');
                $table->string('menu_icon')->nullable();
                $table->text('menu_description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->json('settings')->nullable(); // PostgreSQL native JSON
                $table->timestamps();

                // Add indexes for PostgreSQL
                $table->index('is_active');
                $table->index('sort_order');
            });
            
            // Insert default menu items using DB facade for PostgreSQL compatibility
            DB::table('menu_navigation')->insert([
                [
                    'menu_key' => 'mens',
                    'menu_label' => 'MENS',
                    'menu_description' => 'Men\'s sneakers and footwear collection',
                    'is_active' => true,
                    'sort_order' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'menu_key' => 'womens',
                    'menu_label' => 'WOMENS',
                    'menu_description' => 'Women\'s sneakers and footwear collection',
                    'is_active' => true,
                    'sort_order' => 2,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'menu_key' => 'kids',
                    'menu_label' => 'KIDS',
                    'menu_description' => 'Kids\' sneakers and footwear collection',
                    'is_active' => true,
                    'sort_order' => 3,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'menu_key' => 'brand',
                    'menu_label' => 'BRAND',
                    'menu_description' => 'Shop by brand - explore all premium brands',
                    'is_active' => true,
                    'sort_order' => 4,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'menu_key' => 'accessories',
                    'menu_label' => 'ACCESSORIES',
                    'menu_description' => 'Bags, hats, socks and other accessories',
                    'is_active' => true,
                    'sort_order' => 5,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'menu_key' => 'sale',
                    'menu_label' => 'SALE',
                    'menu_description' => 'Best deals and discounted products',
                    'is_active' => true,
                    'sort_order' => 6,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_navigation');
    }
};