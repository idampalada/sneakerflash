<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // Check and add columns only if they don't exist
            
            if (!Schema::hasColumn('categories', 'menu_placement')) {
                $table->string('menu_placement')->nullable()->after('description');
            }
            
            if (!Schema::hasColumn('categories', 'secondary_menus')) {
                $table->json('secondary_menus')->nullable()->after('menu_placement');
            }
            
            if (!Schema::hasColumn('categories', 'category_keywords')) {
                $table->json('category_keywords')->nullable()->after('secondary_menus');
            }
            
            if (!Schema::hasColumn('categories', 'show_in_menu')) {
                $table->boolean('show_in_menu')->default(true)->after('is_active');
            }
            
            if (!Schema::hasColumn('categories', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->after('show_in_menu');  
            }
            
            // Skip meta fields if they already exist (some might exist from previous migrations)
            if (!Schema::hasColumn('categories', 'meta_title') && Schema::hasColumn('categories', 'image')) {
                $table->string('meta_title')->nullable()->after('image');
            }
            
            if (!Schema::hasColumn('categories', 'meta_description')) {
                $table->text('meta_description')->nullable()->after('meta_title');
            }
            
            if (!Schema::hasColumn('categories', 'meta_keywords')) {
                $table->json('meta_keywords')->nullable()->after('meta_description');
            }
            
            if (!Schema::hasColumn('categories', 'brand_color')) {
                $table->string('brand_color')->nullable()->after('meta_keywords');
            }
        });

        // Add check constraint for menu_placement (only if column exists)
        if (Schema::hasColumn('categories', 'menu_placement')) {
            // Check if constraint already exists
            $constraintExists = DB::select("
                SELECT constraint_name 
                FROM information_schema.constraint_column_usage 
                WHERE table_name = 'categories' 
                AND constraint_name = 'categories_menu_placement_check'
            ");
            
            if (empty($constraintExists)) {
                DB::statement("ALTER TABLE categories ADD CONSTRAINT categories_menu_placement_check CHECK (menu_placement IN ('mens', 'womens', 'kids', 'accessories', 'general'))");
            }
        }

        // Add indexes for PostgreSQL (only if columns exist)
        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'menu_placement')) {
                try {
                    $table->index('menu_placement');
                } catch (\Exception $e) {
                    // Index might already exist, ignore error
                }
            }
            
            if (Schema::hasColumn('categories', 'show_in_menu')) {
                try {
                    $table->index('show_in_menu');
                } catch (\Exception $e) {
                    // Index might already exist, ignore error
                }
            }
            
            if (Schema::hasColumn('categories', 'is_featured')) {
                try {
                    $table->index('is_featured');
                } catch (\Exception $e) {
                    // Index might already exist, ignore error
                }
            }
        });
    }

    public function down(): void
    {
        // Drop constraint first (if it exists)
        try {
            DB::statement("ALTER TABLE categories DROP CONSTRAINT IF EXISTS categories_menu_placement_check");
        } catch (\Exception $e) {
            // Constraint might not exist, ignore
        }

        Schema::table('categories', function (Blueprint $table) {
            // Drop indexes (if they exist)
            $indexesToDrop = [
                'categories_menu_placement_index',
                'categories_show_in_menu_index',
                'categories_is_featured_index'
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
                'menu_placement',
                'secondary_menus',
                'category_keywords',
                'show_in_menu',
                'is_featured',
                'meta_title',
                'meta_description', 
                'meta_keywords',
                'brand_color'
            ];
            
            foreach ($columnsToDrop as $columnName) {
                if (Schema::hasColumn('categories', $columnName)) {
                    $table->dropColumn($columnName);
                }
            }
        });
    }
};