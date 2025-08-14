<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\GoogleSheetsSync;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class CheckSyncedColumns extends Command
{
    protected $signature = 'check:synced-columns';
    protected $description = 'Check what columns were synced from Google Sheets';

    public function handle()
    {
        $this->info("🔍 Checking synced columns and data...");
        $this->newLine();

        try {
            // 1. Check database columns
            $this->checkDatabaseColumns();
            
            // 2. Check Google Sheets headers
            $this->checkGoogleSheetsHeaders();
            
            // 3. Check recent products
            $this->checkRecentProducts();
            
            // 4. Show column mapping issues
            $this->showColumnMappingIssues();
            
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function checkDatabaseColumns()
    {
        $this->info("📊 Database Columns (products table):");
        
        $columns = Schema::getColumnListing('products');
        
        $this->table(['Column Name'], array_map(function($col) {
            return [$col];
        }, $columns));
        
        $this->newLine();
    }

    private function checkGoogleSheetsHeaders()
    {
        $this->info("📋 Google Sheets Headers:");
        
        try {
            $service = new GoogleSheetsSync();
            $result = $service->previewData(1);
            
            if ($result['success'] && !empty($result['headers'])) {
                $this->table(['Header Name'], array_map(function($header) {
                    return [$header];
                }, $result['headers']));
            } else {
                $this->error("Failed to fetch headers: " . ($result['message'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            $this->error("Error fetching Google Sheets headers: " . $e->getMessage());
        }
        
        $this->newLine();
    }

    private function checkRecentProducts()
    {
        $this->info("🔍 Recent Products (last 5):");
        
        $products = Product::latest()->limit(5)->get();
        
        if ($products->isEmpty()) {
            $this->warn("No products found in database.");
            return;
        }

        foreach ($products as $product) {
            $this->info("Product: {$product->name}");
            $this->line("  ID: {$product->id}");
            $this->line("  SKU: {$product->sku}");
            $this->line("  Brand: {$product->brand}");
            $this->line("  Price: Rp " . number_format($product->price));
            $this->line("  Category: " . ($product->category->name ?? 'No category'));
            $this->line("  Gender Target: " . (is_array($product->gender_target) ? implode(', ', $product->gender_target) : $product->gender_target));
            $this->line("  Images Count: " . (is_array($product->images) ? count($product->images) : 0));
            $this->line("  Stock: {$product->stock_quantity}");
            $this->line("  Created: {$product->created_at}");
            $this->newLine();
        }
    }

    private function showColumnMappingIssues()
    {
        $this->info("🔧 Column Mapping Analysis:");
        
        // Expected Google Sheets columns
        $expectedGoogleColumns = [
            'product_type',
            'brand', 
            'name',
            'description',
            'sku_parent',
            'available_sizes',
            'price',
            'sale_price',
            'stock_quantity',
            'sku',
            'images_1',
            'images_2', 
            'images_3',
            'images_4',
            'images_5',
            'weight',
            'lengh',  // Note: typo in sheets
            'wide',
            'high',
            'sale_show',
            'sale_start_date',
            'sale_end_date'
        ];

        // Expected database columns
        $expectedDbColumns = [
            'name',
            'slug',
            'description',
            'short_description',
            'brand',
            'sku',
            'price',
            'sale_price',
            'stock_quantity',
            'weight',
            'images',
            'gender_target',
            'product_type',
            'available_sizes',
            'category_id',
            'is_active',
            'is_featured',
            'published_at'
        ];

        $this->table(
            ['Google Sheets Column', 'Maps To Database Column', 'Status'],
            [
                ['product_type', 'gender_target + product_type', '✅ Mapped'],
                ['brand', 'brand', '✅ Direct'],
                ['name', 'name', '✅ Direct'],
                ['description', 'description', '✅ Direct'],
                ['sku_parent', 'sku', '✅ Mapped'],
                ['available_sizes', 'available_sizes', '✅ Direct'],
                ['price', 'price', '✅ Direct'],
                ['sale_price', 'sale_price', '✅ Direct'],
                ['stock_quantity', 'stock_quantity', '✅ Summed by SKU'],
                ['images_1-5', 'images (JSON array)', '✅ Combined'],
                ['weight', 'weight', '✅ Direct'],
                ['lengh/wide/high', 'dimensions (JSON)', '✅ Combined'],
                ['sale_show', 'is_featured_sale', '✅ Mapped'],
                ['sale_start_date', 'sale_start_date', '✅ Direct'],
                ['sale_end_date', 'sale_end_date', '✅ Direct'],
            ]
        );

        $this->newLine();
        $this->info("🔧 Potential Issues:");
        $this->warn("1. 'lengh' column has typo - should be 'length'");
        $this->warn("2. Products grouped by 'sku_parent' - multiple rows = 1 product");
        $this->warn("3. Stock calculated as sum of all size variants");
        $this->warn("4. Images combined from multiple columns");
    }
}