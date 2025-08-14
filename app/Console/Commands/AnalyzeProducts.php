<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\Category;
use App\Models\GoogleSheetsSyncLog;
use App\Services\GoogleSheetsSync;
use Exception;

class AnalyzeProducts extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'products:analyze {--detailed : Show detailed product information}';

    /**
     * The console command description.
     */
    protected $description = 'Analyze products in database and show comprehensive statistics';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 PRODUCT DATABASE ANALYSIS');
        $this->info('============================');
        $this->newLine();

        // 1. Basic Statistics
        $this->showBasicStats();
        $this->newLine();

        // 2. Category Breakdown
        $this->showCategoryBreakdown();
        $this->newLine();

        // 3. Brand Breakdown
        $this->showBrandBreakdown();
        $this->newLine();

        // 4. Recent Products
        $this->showRecentProducts();
        $this->newLine();

        // 5. Issues
        $this->showProductIssues();
        $this->newLine();

        // 6. Gender & Type Breakdown
        $this->showGenderAndTypeBreakdown();
        $this->newLine();

        // 7. Price Analysis
        $this->showPriceAnalysis();
        $this->newLine();

        // 8. Sync Logs
        $this->showSyncLogs();
        $this->newLine();

        // 9. Detailed samples (if requested)
        if ($this->option('detailed')) {
            $this->showDetailedSamples();
            $this->newLine();
        }

        // 10. Google Sheets Connection Test
        $this->testGoogleSheetsConnection();
        $this->newLine();

        $this->info('✅ Analysis Complete!');
        
        if (!$this->option('detailed')) {
            $this->comment('💡 Run with --detailed flag for more information: php artisan products:analyze --detailed');
        }
    }

    private function showBasicStats()
    {
        $this->info('📊 BASIC STATISTICS');
        $this->line('==================');

        $totalProducts = Product::count();
        $activeProducts = Product::where('is_active', true)->count();
        $inStockProducts = Product::where('stock_quantity', '>', 0)->count();
        $onSaleProducts = Product::whereNotNull('sale_price')->count();
        $featuredProducts = Product::where('is_featured', true)->count();

        $this->table(
            ['Metric', 'Count', 'Percentage'],
            [
                ['Total Products', $totalProducts, '100%'],
                ['Active Products', $activeProducts, $totalProducts > 0 ? round(($activeProducts/$totalProducts)*100, 1).'%' : '0%'],
                ['In Stock', $inStockProducts, $totalProducts > 0 ? round(($inStockProducts/$totalProducts)*100, 1).'%' : '0%'],
                ['On Sale', $onSaleProducts, $totalProducts > 0 ? round(($onSaleProducts/$totalProducts)*100, 1).'%' : '0%'],
                ['Featured', $featuredProducts, $totalProducts > 0 ? round(($featuredProducts/$totalProducts)*100, 1).'%' : '0%'],
            ]
        );

        if ($totalProducts == 0) {
            $this->error('⚠️  No products found in database!');
            $this->comment('💡 Try running Google Sheets sync or importing products.');
        }
    }

    private function showCategoryBreakdown()
    {
        $this->info('📂 PRODUCTS BY CATEGORY');
        $this->line('======================');

        $productsByCategory = Product::selectRaw('category_id, count(*) as total')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->selectRaw('categories.name as category_name, count(*) as total')
            ->groupBy('category_id', 'categories.name')
            ->orderBy('total', 'desc')
            ->get();

        if ($productsByCategory->count() > 0) {
            $categoryData = [];
            foreach($productsByCategory as $category) {
                $categoryName = $category->category_name ?? 'No Category';
                $categoryData[] = [$categoryName, $category->total];
            }
            $this->table(['Category', 'Products'], $categoryData);
        } else {
            $this->comment('No products with categories found.');
        }
    }

    private function showBrandBreakdown()
    {
        $this->info('🏷️  PRODUCTS BY BRAND (Top 10)');
        $this->line('=============================');

        $productsByBrand = Product::selectRaw('brand, count(*) as total')
            ->whereNotNull('brand')
            ->where('brand', '!=', '')
            ->groupBy('brand')
            ->orderBy('total', 'desc')
            ->limit(10)
            ->get();

        if ($productsByBrand->count() > 0) {
            $brandData = [];
            foreach($productsByBrand as $brand) {
                $brandData[] = [$brand->brand, $brand->total];
            }
            $this->table(['Brand', 'Products'], $brandData);
        } else {
            $this->comment('No products with brands found.');
        }
    }

    private function showRecentProducts()
    {
        $this->info('🕒 RECENT PRODUCTS (Last 10)');
        $this->line('============================');

        $recentProducts = Product::orderBy('created_at', 'desc')
            ->limit(10)
            ->get(['id', 'name', 'brand', 'price', 'stock_quantity', 'created_at']);

        if ($recentProducts->count() > 0) {
            $productData = [];
            foreach($recentProducts as $product) {
                $price = 'Rp ' . number_format($product->price, 0, ',', '.');
                $created = $product->created_at->format('d M Y H:i');
                $brand = $product->brand ?? 'No Brand';
                
                $productData[] = [
                    $product->id,
                    substr($product->name, 0, 30) . (strlen($product->name) > 30 ? '...' : ''),
                    $brand,
                    $price,
                    $product->stock_quantity,
                    $created
                ];
            }
            $this->table(['ID', 'Name', 'Brand', 'Price', 'Stock', 'Created'], $productData);
        } else {
            $this->comment('No recent products found.');
        }
    }

    private function showProductIssues()
    {
        $this->info('⚠️  POTENTIAL ISSUES');
        $this->line('===================');

        $outOfStock = Product::where('stock_quantity', 0)->count();
        
        // Fix untuk PostgreSQL JSON queries
        $noImages = Product::where(function($q) {
            $q->whereNull('images')
              ->orWhereRaw("images::text = '[]'")
              ->orWhereRaw("images::text = ''")
              ->orWhereRaw("json_array_length(images) = 0");
        })->count();
        
        $noBrand = Product::where(function($q) {
            $q->whereNull('brand')
              ->orWhere('brand', '');
        })->count();
        $noCategory = Product::whereNull('category_id')->count();
        $inactive = Product::where('is_active', false)->count();

        $issuesData = [
            ['Out of Stock', $outOfStock, $outOfStock > 0 ? '❌' : '✅'],
            ['No Images', $noImages, $noImages > 0 ? '❌' : '✅'],
            ['No Brand', $noBrand, $noBrand > 0 ? '❌' : '✅'],
            ['No Category', $noCategory, $noCategory > 0 ? '❌' : '✅'],
            ['Inactive Products', $inactive, $inactive > 0 ? '⚠️' : '✅'],
        ];

        $this->table(['Issue Type', 'Count', 'Status'], $issuesData);

        $totalIssues = $outOfStock + $noImages + $noBrand + $noCategory;
        if ($totalIssues > 0) {
            $this->warn("💡 Found {$totalIssues} products that need attention.");
        } else {
            $this->info('🎉 No major issues found!');
        }
    }

    private function showGenderAndTypeBreakdown()
    {
        $this->info('👥 GENDER & TYPE BREAKDOWN');
        $this->line('=========================');

        // Gender breakdown - Fix untuk PostgreSQL
        try {
            $mensProducts = Product::whereRaw("gender_target ? 'mens'")->count();
        } catch (Exception $e) {
            $mensProducts = Product::whereRaw("gender_target::text LIKE '%mens%'")->count();
        }
        
        try {
            $womensProducts = Product::whereRaw("gender_target ? 'womens'")->count();
        } catch (Exception $e) {
            $womensProducts = Product::whereRaw("gender_target::text LIKE '%womens%'")->count();
        }
        
        try {
            $kidsProducts = Product::whereRaw("gender_target ? 'kids'")->count();
        } catch (Exception $e) {
            $kidsProducts = Product::whereRaw("gender_target::text LIKE '%kids%'")->count();
        }
        
        $noGender = Product::where(function($q) {
            $q->whereNull('gender_target')
              ->orWhereRaw("gender_target::text = '[]'");
        })->count();

        $genderData = [
            ['👨 Men\'s', $mensProducts],
            ['👩 Women\'s', $womensProducts],
            ['👶 Kids', $kidsProducts],
            ['❓ No Gender', $noGender],
        ];

        $this->table(['Gender Target', 'Products'], $genderData);

        // Product type breakdown
        $this->line('');
        $this->comment('Product Types:');
        $productTypes = Product::selectRaw('product_type, count(*) as total')
            ->whereNotNull('product_type')
            ->groupBy('product_type')
            ->orderBy('total', 'desc')
            ->get();

        if ($productTypes->count() > 0) {
            $typeData = [];
            foreach($productTypes as $type) {
                $typeData[] = [$type->product_type, $type->total];
            }
            $this->table(['Product Type', 'Count'], $typeData);
        } else {
            $this->comment('No product types found.');
        }
    }

    private function showPriceAnalysis()
    {
        $this->info('💰 PRICE ANALYSIS');
        $this->line('================');

        $priceStats = Product::selectRaw('
            MIN(price) as min_price,
            MAX(price) as max_price,
            AVG(price) as avg_price,
            COUNT(*) as total_count
        ')->first();

        if ($priceStats && $priceStats->total_count > 0) {
            $statsData = [
                ['Minimum Price', 'Rp ' . number_format($priceStats->min_price, 0, ',', '.')],
                ['Maximum Price', 'Rp ' . number_format($priceStats->max_price, 0, ',', '.')],
                ['Average Price', 'Rp ' . number_format($priceStats->avg_price, 0, ',', '.')],
            ];
            $this->table(['Metric', 'Value'], $statsData);

            // Price ranges
            $ranges = [
                ['min' => 0, 'max' => 500000, 'label' => 'Under 500K'],
                ['min' => 500000, 'max' => 1000000, 'label' => '500K - 1M'],
                ['min' => 1000000, 'max' => 2000000, 'label' => '1M - 2M'],
                ['min' => 2000000, 'max' => 5000000, 'label' => '2M - 5M'],
                ['min' => 5000000, 'max' => 999999999, 'label' => 'Above 5M'],
            ];

            $this->line('');
            $this->comment('Price Distribution:');
            $rangeData = [];
            foreach($ranges as $range) {
                $count = Product::whereBetween('price', [$range['min'], $range['max']])->count();
                $rangeData[] = [$range['label'], $count];
            }
            $this->table(['Price Range', 'Products'], $rangeData);
        } else {
            $this->comment('No price data available.');
        }
    }

    private function showSyncLogs()
    {
        $this->info('🔄 RECENT SYNC LOGS');
        $this->line('==================');

        try {
            $recentSyncs = GoogleSheetsSyncLog::orderBy('started_at', 'desc')
                ->limit(5)
                ->get(['sync_id', 'status', 'started_at', 'created_products', 'updated_products', 'deleted_products', 'duration_seconds']);
            
            if($recentSyncs->count() > 0) {
                $syncData = [];
                foreach($recentSyncs as $sync) {
                    $started = $sync->started_at->format('d M Y H:i');
                    $duration = $sync->duration_seconds ? $sync->duration_seconds . 's' : 'N/A';
                    
                    $syncData[] = [
                        substr($sync->sync_id, 0, 8) . '...',
                        $sync->status,
                        $started,
                        $sync->created_products ?? 0,
                        $sync->updated_products ?? 0,
                        $sync->deleted_products ?? 0,
                        $duration
                    ];
                }
                $this->table(['Sync ID', 'Status', 'Started', 'Created', 'Updated', 'Deleted', 'Duration'], $syncData);
            } else {
                $this->comment('No sync logs found.');
                $this->comment('💡 Try running: php artisan products:sync-google-sheets');
            }
        } catch(Exception $e) {
            $this->comment('Sync logs table not available: ' . $e->getMessage());
        }
    }

    private function showDetailedSamples()
    {
        $this->info('🔍 DETAILED SAMPLE PRODUCTS (First 3)');
        $this->line('====================================');

        $sampleProducts = Product::with('category')
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get();

        foreach($sampleProducts as $index => $product) {
            $this->line("--- Product " . ($index + 1) . " ---");
            $this->line("ID: {$product->id}");
            $this->line("Name: {$product->name}");
            $this->line("Brand: " . ($product->brand ?? 'No Brand'));
            $this->line("Category: " . ($product->category->name ?? 'No Category'));
            $this->line("Price: Rp " . number_format($product->price, 0, ',', '.'));
            
            if($product->sale_price) {
                $this->line("Sale Price: Rp " . number_format($product->sale_price, 0, ',', '.'));
                $discount = round((($product->price - $product->sale_price) / $product->price) * 100);
                $this->line("Discount: {$discount}%");
            }
            
            $this->line("Stock: {$product->stock_quantity}");
            $this->line("Active: " . ($product->is_active ? 'Yes' : 'No'));
            $this->line("Featured: " . ($product->is_featured ? 'Yes' : 'No'));
            
            if($product->gender_target) {
                $this->line("Gender Target: " . implode(', ', $product->gender_target));
            }
            
            if($product->product_type) {
                $this->line("Product Type: {$product->product_type}");
            }
            
            if($product->available_sizes) {
                $this->line("Sizes: " . implode(', ', $product->available_sizes));
            }
            
            if($product->images && is_array($product->images)) {
                $this->line("Images: " . count($product->images) . " images");
                $this->line("First Image: " . ($product->images[0] ?? 'No images'));
            }
            
            $this->line("Created: " . $product->created_at->format('d M Y H:i'));
            $this->line("");
        }
    }

    private function testGoogleSheetsConnection()
    {
        $this->info('🔗 GOOGLE SHEETS CONNECTION TEST');
        $this->line('===============================');

        try {
            $syncService = new GoogleSheetsSync();
            $result = $syncService->testConnection();
            
            if ($result['success']) {
                $this->info('✅ Connection successful!');
                $this->line("Status: {$result['status_code']}");
                
                // Try to preview data
                $this->comment('Testing data preview...');
                $preview = $syncService->previewData(3);
                
                if ($preview['success']) {
                    $this->info("📊 Preview successful! Found {$preview['total_rows']} rows in spreadsheet.");
                    $this->comment("Sample data:");
                    foreach($preview['data'] as $index => $row) {
                        $this->line("  " . ($index + 1) . ". {$row['name']} ({$row['brand']}) - Rp " . number_format($row['price']));
                    }
                } else {
                    $this->warn("⚠️  Preview failed: {$preview['message']}");
                }
            } else {
                $this->error("❌ Connection failed: {$result['message']}");
                $this->comment('💡 Check your Google Sheets configuration in .env file');
            }
        } catch (Exception $e) {
            $this->error("💥 Error testing connection: " . $e->getMessage());
        }
    }
}