<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GineeOnlyStockSyncService;
use App\Models\Product;

class DebugGineeMskuCommand extends Command
{
    protected $signature = 'ginee:debug-msku {--sku= : Specific SKU to debug}';
    protected $description = 'Debug MSKU mapping between database and Ginee';

    public function handle()
    {
        $this->info('🔍 Debugging Ginee MSKU Mapping');
        $this->newLine();

        $specificSku = $this->option('sku');

        try {
            $gineeService = new GineeOnlyStockSyncService();
            $preview = $gineeService->previewStockSync();

            if (!$preview['success']) {
                $this->error('❌ Failed to get preview: ' . $preview['message']);
                return 1;
            }

            // 1. Show database products
            $this->info('📊 Database Products Analysis:');
            $dbProducts = Product::all();
            
            if ($specificSku) {
                $dbProducts = $dbProducts->where('sku', $specificSku);
                $this->line("Filtering for SKU: {$specificSku}");
            } else {
                $dbProducts = $dbProducts->take(10); // Show first 10
                $this->line('Showing first 10 products:');
            }

            $this->table(
                ['ID', 'Name', 'SKU', 'Current Stock'],
                $dbProducts->map(function($product) {
                    return [
                        $product->id,
                        substr($product->name, 0, 30),
                        $product->sku,
                        $product->stock_quantity ?? 0
                    ];
                })->toArray()
            );

            $this->newLine();

            // 2. Show Ginee products
            $this->info('📦 Ginee Products Analysis:');
            $gineeProducts = array_slice($preview['ginee_products'], 0, 10);
            
            $gineeTable = [];
            foreach ($gineeProducts as $product) {
                // Try to extract MSKU and stock from different possible structures
                $mskus = [];
                $stocks = [];
                
                // Method 1: Check variationBriefs
                if (isset($product['variationBriefs']) && is_array($product['variationBriefs'])) {
                    foreach ($product['variationBriefs'] as $variation) {
                        if (!empty($variation['sku'])) {
                            $mskus[] = $variation['sku'];
                            $stockInfo = $variation['stock'] ?? [];
                            $stocks[] = $stockInfo['availableStock'] ?? 0;
                        }
                    }
                }
                
                // Method 2: Direct fields
                if (empty($mskus)) {
                    $directSku = $product['sku'] ?? $product['masterSku'] ?? $product['code'] ?? $product['productId'] ?? 'No SKU';
                    $mskus[] = $directSku;
                    $stocks[] = $product['stock'] ?? $product['availableStock'] ?? 0;
                }

                $gineeTable[] = [
                    substr($product['name'] ?? 'No Name', 0, 25),
                    implode(', ', array_slice($mskus, 0, 3)), // Show max 3 SKUs
                    implode(', ', array_slice($stocks, 0, 3)), // Show max 3 stocks
                    count($mskus) // Total variations
                ];
            }
            
            $this->table(
                ['Product Name', 'MSKU(s)', 'Stock(s)', 'Variations'],
                $gineeTable
            );

            $this->newLine();

            // 3. MSKU Mapping Analysis
            $this->info('🔗 MSKU Mapping Analysis:');
            $mskuMap = $preview['msku_map'] ?? [];
            
            $this->line("Total MSKUs from Ginee: " . count($mskuMap));
            $this->line("Matched SKUs: " . count($preview['matched_skus']));
            $this->line("Unmatched SKUs: " . count($preview['unmatched_skus']));

            // 4. Specific SKU Analysis
            if ($specificSku) {
                $this->newLine();
                $this->info("🎯 Detailed Analysis for SKU: {$specificSku}");
                
                // Check if SKU exists in database
                $dbProduct = Product::where('sku', $specificSku)->first();
                if ($dbProduct) {
                    $this->info("✅ Found in Database:");
                    $this->line("  Name: {$dbProduct->name}");
                    $this->line("  Current Stock: {$dbProduct->stock_quantity}");
                } else {
                    $this->warn("❌ Not found in Database");
                }

                // Check if SKU exists in Ginee
                if (isset($mskuMap[$specificSku])) {
                    $gineeData = $mskuMap[$specificSku];
                    $this->info("✅ Found in Ginee:");
                    $this->line("  MSKU: {$gineeData['msku']}");
                    $this->line("  Stock: {$gineeData['stock']}");
                    $this->line("  Product Name: {$gineeData['product_name']}");
                } else {
                    $this->warn("❌ Not found in Ginee MSKU mapping");
                    
                    // Search for similar SKUs
                    $this->line("🔍 Searching for similar SKUs in Ginee...");
                    $similarSkus = [];
                    foreach ($mskuMap as $gineeSku => $data) {
                        if (str_contains(strtolower($gineeSku), strtolower($specificSku)) || 
                            str_contains(strtolower($specificSku), strtolower($gineeSku))) {
                            $similarSkus[] = [
                                'ginee_sku' => $gineeSku,
                                'stock' => $data['stock'],
                                'name' => substr($data['product_name'], 0, 30)
                            ];
                        }
                    }
                    
                    if (!empty($similarSkus)) {
                        $this->warn("⚠️ Found similar SKUs:");
                        $this->table(
                            ['Ginee SKU', 'Stock', 'Product Name'],
                            $similarSkus
                        );
                    } else {
                        $this->line("❌ No similar SKUs found");
                    }
                }
            }

            // 5. Show sample unmatched products
            $this->newLine();
            $this->warn('⚠️ Sample Unmatched Products:');
            $unmatchedSample = array_slice($preview['unmatched_skus'], 0, 5);
            foreach ($unmatchedSample as $sku) {
                $product = Product::where('sku', $sku)->first();
                $this->line("  • {$sku} - " . ($product ? substr($product->name, 0, 30) : 'Product not found'));
            }

            // 6. Show sample Ginee-only products
            $this->newLine();
            $this->info('📦 Sample Ginee-only MSKUs (not in database):');
            $databaseSkus = Product::pluck('sku')->toArray();
            $gineeOnlySkus = array_diff(array_keys($mskuMap), $databaseSkus);
            $gineeOnlySample = array_slice($gineeOnlySkus, 0, 5);
            
            foreach ($gineeOnlySample as $msku) {
                $gineeData = $mskuMap[$msku];
                $this->line("  • {$msku} (Stock: {$gineeData['stock']}) - " . substr($gineeData['product_name'], 0, 30));
            }

            // 7. Recommendations
            $this->newLine();
            $this->info('💡 Recommendations:');
            
            $matchRate = count($preview['matched_skus']) / max(1, count($preview['matched_skus']) + count($preview['unmatched_skus'])) * 100;
            
            if ($matchRate < 50) {
                $this->warn("⚠️ Low match rate ({$matchRate}%). Consider:");
                $this->line("1. Check SKU format consistency between database and Ginee");
                $this->line("2. Verify MSKU setup in Ginee dashboard");
                $this->line("3. Consider SKU mapping/transformation rules");
            } else {
                $this->info("✅ Good match rate ({$matchRate}%)");
            }

            if (count($gineeOnlySkus) > 0) {
                $this->info("💡 Found " . count($gineeOnlySkus) . " products in Ginee not in database");
                $this->line("Consider importing missing products or updating SKUs");
            }

        } catch (\Exception $e) {
            $this->error('❌ Debug failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}