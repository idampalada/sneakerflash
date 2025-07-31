<?php
// database/migrations/2025_07_30_025752_add_sneakerflash_filter_fields_to_products_table.php

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
        Schema::table('products', function (Blueprint $table) {
            // Cek dan tambah kolom yang belum ada saja
            
            // Size information - Cek dulu apakah sudah ada
            if (!Schema::hasColumn('products', 'sizes')) {
                $table->jsonb('sizes')->nullable()->after('description');
            }
            
            // Color information - JSONB field untuk PostgreSQL
            if (!Schema::hasColumn('products', 'colors')) {
                $table->jsonb('colors')->nullable()->after('sizes');
            }
            
            // Gender target - men, women, youth, unisex
            if (!Schema::hasColumn('products', 'gender')) {
                $table->enum('gender', ['men', 'women', 'youth', 'unisex'])->nullable()->after('colors');
            }
            
            // Condition of the product - new, used, refurbished
            if (!Schema::hasColumn('products', 'condition')) {
                $table->enum('condition', ['new', 'used', 'refurbished'])->default('new')->after('gender');
            }
            
            // Express shipping availability
            if (!Schema::hasColumn('products', 'express_shipping')) {
                $table->boolean('express_shipping')->default(false)->after('condition');
            }
            
            // Pre-order status
            if (!Schema::hasColumn('products', 'is_preorder')) {
                $table->boolean('is_preorder')->default(false)->after('express_shipping');
            }
            
            // Rating average (for display) - DECIMAL lebih baik untuk PostgreSQL
            if (!Schema::hasColumn('products', 'rating_average')) {
                $table->decimal('rating_average', 3, 2)->default(0.00)->after('is_preorder');
            }
            
            // Rating count
            if (!Schema::hasColumn('products', 'rating_count')) {
                $table->unsignedInteger('rating_count')->default(0)->after('rating_average');
            }
            
            // Material information
            if (!Schema::hasColumn('products', 'material')) {
                $table->string('material')->nullable()->after('rating_count');
            }
            
            // Style/Type (basketball, casual, running, etc.)
            if (!Schema::hasColumn('products', 'style')) {
                $table->string('style')->nullable()->after('material');
            }
            
            // Tags for better search - JSONB untuk PostgreSQL
            if (!Schema::hasColumn('products', 'tags')) {
                $table->jsonb('tags')->nullable()->after('style');
            }
            
            // SEO fields
            if (!Schema::hasColumn('products', 'meta_title')) {
                $table->string('meta_title')->nullable()->after('tags');
            }
            
            if (!Schema::hasColumn('products', 'meta_description')) {
                $table->text('meta_description')->nullable()->after('meta_title');
            }
            
            if (!Schema::hasColumn('products', 'meta_keywords')) {
                $table->jsonb('meta_keywords')->nullable()->after('meta_description');
            }
        });
        
        // Tambah index setelah semua kolom dibuat
        Schema::table('products', function (Blueprint $table) {
            // Basic indexing untuk performance PostgreSQL - dengan try catch untuk safety
            $indexColumns = ['gender', 'condition', 'express_shipping', 'is_preorder', 'rating_average', 'material', 'style'];
            
            foreach ($indexColumns as $column) {
                if (Schema::hasColumn('products', $column)) {
                    try {
                        $table->index($column);
                    } catch (\Exception $e) {
                        // Index mungkin sudah ada, skip
                    }
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Drop indexes first (jika ada)
            $indexColumns = ['gender', 'condition', 'express_shipping', 'is_preorder', 'rating_average', 'material', 'style'];
            
            foreach ($indexColumns as $column) {
                if (Schema::hasColumn('products', $column)) {
                    try {
                        $table->dropIndex(['products_' . $column . '_index']);
                    } catch (\Exception $e) {
                        // Index mungkin tidak ada, skip
                    }
                }
            }
            
            // Drop columns - hanya yang kita tambahkan
            $columnsToDrop = [
                'colors', 
                'gender',
                'condition',
                'express_shipping',
                'is_preorder',
                'rating_average',
                'rating_count',
                'material',
                'style',
                'tags',
                'meta_title',
                'meta_description',
                'meta_keywords'
            ];
            
            // Hanya drop kolom sizes jika memang kita yang menambahkan (cek apakah ada kolom lain yang kita tambah)
            if (Schema::hasColumn('products', 'colors')) {
                $columnsToDrop[] = 'sizes';
            }
            
            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};