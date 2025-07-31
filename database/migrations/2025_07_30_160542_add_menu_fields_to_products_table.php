<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Check and add columns only if they don't exist
            
            if (!Schema::hasColumn('products', 'gender_target')) {
                $table->string('gender_target')->nullable()->after('brand');
            }
            
            if (!Schema::hasColumn('products', 'product_type')) {
                $table->string('product_type')->nullable()->after('gender_target');
            }
            
            if (!Schema::hasColumn('products', 'search_keywords')) {
                $table->json('search_keywords')->nullable()->after('product_type');
            }
            
            if (!Schema::hasColumn('products', 'sale_start_date')) {
                $table->date('sale_start_date')->nullable()->after('sale_price');
            }
            
            if (!Schema::hasColumn('products', 'sale_end_date')) {
                $table->date('sale_end_date')->nullable()->after('sale_start_date');
            }
            
            if (!Schema::hasColumn('products', 'is_featured_sale')) {
                $table->boolean('is_featured_sale')->default(false)->after('sale_end_date');
            }
            
            if (!Schema::hasColumn('products', 'available_sizes')) {
                $table->json('available_sizes')->nullable()->after('features');
            }
            
            if (!Schema::hasColumn('products', 'available_colors')) {
                $table->json('available_colors')->nullable()->after('available_sizes');
            }
            
            // Skip meta_title, meta_description, meta_keywords if they already exist
            if (!Schema::hasColumn('products', 'meta_title')) {
                $table->string('meta_title')->nullable()->after('meta_data');
            }
            
            if (!Schema::hasColumn('products', 'meta_description')) {
                $table->text('meta_description')->nullable()->after('meta_title');
            }
            
            if (!Schema::hasColumn('products', 'meta_keywords')) {
                $table->json('meta_keywords')->nullable()->after('meta_description');
            }
        });

        // Add check constraints for PostgreSQL (only if columns exist)
        if (Schema::hasColumn('products', 'gender_target')) {
            // Check if constraint already exists
            $constraintExists = DB::select("
                SELECT constraint_name 
                FROM information_schema.constraint_column_usage 
                WHERE table_name = 'products' 
                AND constraint_name = 'products_gender_target_check'
            ");
            
            if (empty($constraintExists)) {
                DB::statement("ALTER TABLE products ADD CONSTRAINT products_gender_target_check CHECK (gender_target IN ('mens', 'womens', 'kids', 'unisex'))");
            }
        }
        
        if (Schema::hasColumn('products', 'product_type')) {
            // Check if constraint already exists
            $constraintExists = DB::select("
                SELECT constraint_name 
                FROM information_schema.constraint_column_usage 
                WHERE table_name = 'products' 
                AND constraint_name = 'products_product_type_check'
            ");
            
            if (empty($constraintExists)) {
                DB::statement("ALTER TABLE products ADD CONSTRAINT products_product_type_check CHECK (product_type IN ('sneakers', 'running_shoes', 'basketball_shoes', 'casual_shoes', 'boots', 'sandals', 'backpack', 'bag', 'hat', 'cap', 'socks', 'laces', 'care_products', 'accessories'))");
            }
        }

        // Add indexes for better performance (only if columns exist and index doesn't exist)
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'gender_target')) {
                try {
                    $table->index('gender_target');
                } catch (\Exception $e) {
                    // Index might already exist, ignore error
                }
            }
            
            if (Schema::hasColumn('products', 'product_type')) {
                try {
                    $table->index('product_type');
                } catch (\Exception $e) {
                    // Index might already exist, ignore error
                }
            }
            
            if (Schema::hasColumn('products', 'is_featured_sale')) {
                try {
                    $table->index('is_featured_sale');
                } catch (\Exception $e) {
                    // Index might already exist, ignore error
                }
            }
            
            if (Schema::hasColumn('products', 'sale_start_date') && Schema::hasColumn('products', 'sale_end_date')) {
                try {
                    $table->index(['sale_start_date', 'sale_end_date']);
                } catch (\Exception $e) {
                    // Index might already exist, ignore error
                }
            }
        });
    }

    public function down(): void
    {
        // Drop constraints first (if they exist)
        try {
            DB::statement("ALTER TABLE products DROP CONSTRAINT IF EXISTS products_gender_target_check");
        } catch (\Exception $e) {
            // Constraint might not exist, ignore
        }
        
        try {
            DB::statement("ALTER TABLE products DROP CONSTRAINT IF EXISTS products_product_type_check");
        } catch (\Exception $e) {
            // Constraint might not exist, ignore
        }

        Schema::table('products', function (Blueprint $table) {
            // Drop indexes (if they exist)
            $indexesToDrop = [
                'products_gender_target_index',
                'products_product_type_index', 
                'products_is_featured_sale_index',
                'products_sale_start_date_sale_end_date_index'
            ];
            
            foreach ($indexesToDrop as $indexName) {
                try {
                    $table->dropIndex($indexName);
                } catch (\Exception $e) {
                    // Index might not exist, ignore
                }
            }

            // Drop columns (only if they exist)
            $columnsToDrop = [
                'gender_target',
                'product_type', 
                'search_keywords',
                'sale_start_date',
                'sale_end_date',
                'is_featured_sale',
                'available_sizes',
                'available_colors',
                'meta_title',
                'meta_description',
                'meta_keywords'
            ];
            
            foreach ($columnsToDrop as $columnName) {
                if (Schema::hasColumn('products', $columnName)) {
                    $table->dropColumn($columnName);
                }
            }
        });
    }
};