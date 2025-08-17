<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GineeStockSyncService;
use App\Models\Product;
use Illuminate\Support\Facades\Log;

class TestGineeCommand extends Command
{
    protected $signature = 'ginee:test 
                            {--access-key= : Ginee Access Key}
                            {--secret-key= : Ginee Secret Key}
                            {--preview : Only preview without actual sync}
                            {--limit=10 : Limit number of products to display}';

    protected $description = 'Test Ginee API integration and stock sync functionality';

    public function handle()
    {
        $this->info('🚀 Starting Ginee API Integration Test');
        $this->newLine();

        // Override config if provided via options
        if ($this->option('access-key')) {
            config(['services.ginee.access_key' => $this->option('access-key')]);
        }
        
        if ($this->option('secret-key')) {
            config(['services.ginee.secret_key' => $this->option('secret-key')]);
        }

        $gineeService = new GineeStockSyncService();

        // Test 1: Check configuration
        $this->testConfiguration();

        // Test 2: Test API connection and fetch products
        $this->testApiConnection($gineeService);

        // Test 3: Preview stock sync
        $this->testPreviewSync($gineeService);

        // Test 4: Show sample SKU matching
        $this->testSkuMatching($gineeService);

        // Test 5: Actual sync (if not preview mode)
        if (!$this->option('preview')) {
            if ($this->confirm('Do you want to proceed with actual stock sync?')) {
                $this->testActualSync($gineeService);
            } else {
                $this->warn('⏸️ Skipping actual sync (preview mode)');
            }
        } else {
            $this->info('📋 Preview mode - no actual sync performed');
        }

        $this->newLine();
        $this->info('✅ Ginee API Integration Test Completed!');
        
        return 0;
    }

    private function testConfiguration()
    {
        $this->info('🔧 Test 1: Configuration Check');
        
        $accessKey = config('services.ginee.access_key');
        $secretKey = config('services.ginee.secret_key');
        $apiUrl = config('services.ginee.api_url');
        
        $this->table(
            ['Configuration', 'Value', 'Status'],
            [
                ['API URL', $apiUrl, '✅ Set'],
                ['Access Key', $accessKey ? substr($accessKey, 0, 8) . '...' : 'Not set', $accessKey ? '✅ Set' : '❌ Missing'],
                ['Secret Key', $secretKey ? substr($secretKey, 0, 8) . '...' : 'Not set', $secretKey ? '✅ Set' : '❌ Missing'],
                ['Timeout', config('services.ginee.timeout', 30) . 's', '✅ Set'],
                ['Cache Duration', config('services.ginee.cache_duration', 600) . 's', '✅ Set'],
                ['Country', config('services.ginee.country', 'ID'), '✅ Set'],
            ]
        );
        
        if (!$accessKey || !$secretKey) {
            $this->error('❌ Missing required Ginee API credentials!');
            $this->line('Please set GINEE_ACCESS_KEY and GINEE_SECRET_KEY in your .env file');
            return false;
        }
        
        $this->info('✅ Configuration check passed');
        $this->newLine();
        return true;
    }

    private function testApiConnection($gineeService)
    {
        $this->info('🌐 Test 2: API Connection & Product Fetch');
        
        $this->line('Attempting to connect to Ginee API...');
        
        try {
            $result = $gineeService->previewStockSync();
            
            if ($result['success']) {
                $this->info('✅ Successfully connected to Ginee API');
                
                $this->table(
                    ['Metric', 'Count'],
                    [
                        ['Ginee Products Found', count($result['ginee_products'])],
                        ['Database Products', $result['total_database_products']],
                        ['Matched SKUs', count($result['matched_skus'])],
                        ['Unmatched SKUs', count($result['unmatched_skus'])],
                    ]
                );
                
                // Show sample Ginee products
                if (!empty($result['ginee_products'])) {
                    $this->info('📦 Sample Ginee Products:');
                    $limit = min($this->option('limit'), count($result['ginee_products']));
                    $sampleProducts = array_slice($result['ginee_products'], 0, $limit);
                    
                    $productTable = [];
                    foreach ($sampleProducts as $product) {
                        // Try to extract MSKU from different possible locations
                        $msku = 'N/A';
                        $stock = 0;
                        
                        // Check for variations first
                        if (isset($product['variationBriefs']) && is_array($product['variationBriefs']) && !empty($product['variationBriefs'])) {
                            $firstVariation = $product['variationBriefs'][0];
                            $msku = $firstVariation['sku'] ?? 'N/A';
                            $stockInfo = $firstVariation['stock'] ?? [];
                            $stock = $stockInfo['availableStock'] ?? 0;
                        }
                        // Try direct fields
                        elseif (isset($product['sku'])) {
                            $msku = $product['sku'];
                            $stock = $product['stock'] ?? $product['availableStock'] ?? 0;
                        }
                        // Try other possible SKU fields
                        elseif (isset($product['masterSku'])) {
                            $msku = $product['masterSku'];
                            $stock = $product['stock'] ?? 0;
                        }
                        elseif (isset($product['code'])) {
                            $msku = $product['code'];
                            $stock = $product['stock'] ?? 0;
                        }
                        
                        $productTable[] = [
                            $msku,
                            substr($product['name'] ?? 'N/A', 0, 30),
                            $stock,
                            $product['price'] ?? 0,
                        ];
                    }
                    
                    $this->table(
                        ['MSKU', 'Product Name', 'Stock', 'Price'],
                        $productTable
                    );
                    
                    // Show structure info for debugging
                    if (!empty($sampleProducts)) {
                        $this->newLine();
                        $this->comment('🔍 Debug Info - First Product Structure:');
                        $firstProduct = $sampleProducts[0];
                        $this->line('Available fields: ' . implode(', ', array_keys($firstProduct)));
                        
                        if (isset($firstProduct['variationBriefs']) && !empty($firstProduct['variationBriefs'])) {
                            $this->line('Has variations: ' . count($firstProduct['variationBriefs']) . ' variations');
                            $firstVar = $firstProduct['variationBriefs'][0];
                            $this->line('Variation fields: ' . implode(', ', array_keys($firstVar)));
                        }
                    }
                }
                
            } else {
                $this->error('❌ Failed to connect to Ginee API');
                $this->line('Error: ' . $result['message']);
                return false;
            }
            
        } catch (\Exception $e) {
            $this->error('❌ API connection failed with exception');
            $this->line('Error: ' . $e->getMessage());
            return false;
        }
        
        $this->newLine();
        return true;
    }

    private function testPreviewSync($gineeService)
    {
        $this->info('👁️ Test 3: Stock Sync Preview');
        
        try {
            $preview = $gineeService->previewStockSync();
            
            if ($preview['success']) {
                $matchedCount = count($preview['matched_skus']);
                $unmatchedCount = count($preview['unmatched_skus']);
                $totalDatabase = $preview['total_database_products'];
                
                $this->info("📊 Sync Preview Results:");
                
                $this->table(
                    ['Category', 'Count', 'Percentage'],
                    [
                        ['Products in Database', $totalDatabase, '100%'],
                        ['Will be Updated (Found in Ginee)', $matchedCount, $totalDatabase > 0 ? round(($matchedCount / $totalDatabase) * 100, 1) . '%' : '0%'],
                        ['Will Keep Current Stock (Not in Ginee)', $unmatchedCount, $totalDatabase > 0 ? round(($unmatchedCount / $totalDatabase) * 100, 1) . '%' : '0%'],
                    ]
                );
                
                if ($matchedCount > 0) {
                    $this->info('✅ Sample SKUs that will be updated:');
                    $sampleMatched = array_slice($preview['matched_skus'], 0, 5);
                    foreach ($sampleMatched as $sku) {
                        $this->line("  • {$sku}");
                    }
                    if (count($preview['matched_skus']) > 5) {
                        $this->line("  ... and " . (count($preview['matched_skus']) - 5) . " more");
                    }
                }
                
                if ($unmatchedCount > 0) {
                    $this->warn('⚠️ Sample SKUs NOT found in Ginee:');
                    $sampleUnmatched = array_slice($preview['unmatched_skus'], 0, 3);
                    foreach ($sampleUnmatched as $sku) {
                        $this->line("  • {$sku}");
                    }
                    if (count($preview['unmatched_skus']) > 3) {
                        $this->line("  ... and " . (count($preview['unmatched_skus']) - 3) . " more");
                    }
                }
                
            } else {
                $this->error('❌ Preview failed: ' . $preview['message']);
                return false;
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Preview failed with exception: ' . $e->getMessage());
            return false;
        }
        
        $this->newLine();
        return true;
    }

    private function testSkuMatching($gineeService)
    {
        $this->info('🔍 Test 4: SKU Matching Analysis');
        
        try {
            $preview = $gineeService->previewStockSync();
            
            if ($preview['success'] && !empty($preview['matched_skus'])) {
                $this->info('📋 Detailed SKU Matching:');
                
                // Ambil beberapa sample untuk analisis detail
                $sampleSkus = array_slice($preview['matched_skus'], 0, 3);
                
                foreach ($sampleSkus as $sku) {
                    // Cari produk di database
                    $dbProduct = Product::where('sku', $sku)->first();
                    
                    // Cari di Ginee products
                    $gineeProduct = null;
                    foreach ($preview['ginee_products'] as $gProduct) {
                        if (($gProduct['msku'] ?? '') === $sku) {
                            $gineeProduct = $gProduct;
                            break;
                        }
                    }
                    
                    if ($dbProduct && $gineeProduct) {
                        $this->table(
                            ['Source', 'Name', 'Current Stock', 'New Stock', 'Price'],
                            [
                                [
                                    'Database',
                                    substr($dbProduct->name, 0, 25),
                                    $dbProduct->stock_quantity ?? 0,
                                    '-',
                                    $dbProduct->price ?? 0
                                ],
                                [
                                    'Ginee',
                                    substr($gineeProduct['name'] ?? 'N/A', 0, 25),
                                    '-',
                                    $gineeProduct['stock'] ?? 0,
                                    $gineeProduct['price'] ?? 0
                                ]
                            ]
                        );
                        
                        $stockDiff = ($gineeProduct['stock'] ?? 0) - ($dbProduct->stock_quantity ?? 0);
                        if ($stockDiff > 0) {
                            $this->info("  📈 Stock will increase by {$stockDiff}");
                        } elseif ($stockDiff < 0) {
                            $this->warn("  📉 Stock will decrease by " . abs($stockDiff));
                        } else {
                            $this->line("  ➡️ Stock will remain the same");
                        }
                        
                        $this->newLine();
                    }
                }
            }
            
        } catch (\Exception $e) {
            $this->error('❌ SKU matching analysis failed: ' . $e->getMessage());
        }
    }

    private function testActualSync($gineeService)
    {
        $this->info('⚡ Test 5: Actual Stock Sync');
        $this->warn('⚠️ This will modify your database!');
        
        $bar = $this->output->createProgressBar(100);
        $bar->setFormat('verbose');
        $bar->start();
        
        try {
            $result = $gineeService->syncStockWithSpreadsheet();
            
            $bar->finish();
            $this->newLine();
            
            if ($result['success']) {
                $this->info('✅ Stock sync completed successfully!');
                
                $stats = $result['stats'];
                $this->table(
                    ['Operation', 'Count'],
                    [
                        ['Products Created', $stats['created']],
                        ['Products Updated', $stats['updated']],
                        ['Products Skipped', $stats['skipped']],
                        ['Warnings', $stats['warnings']],
                        ['Errors', $stats['errors']],
                    ]
                );
                
                if (!empty($result['errors'])) {
                    $this->warn('⚠️ Errors encountered during sync:');
                    foreach (array_slice($result['errors'], 0, 5) as $error) {
                        $this->line("  • Row {$error['row']}: {$error['error']}");
                    }
                    if (count($result['errors']) > 5) {
                        $this->line("  ... and " . (count($result['errors']) - 5) . " more errors");
                    }
                }
                
            } else {
                $this->error('❌ Stock sync failed: ' . $result['message']);
            }
            
        } catch (\Exception $e) {
            $bar->finish();
            $this->newLine();
            $this->error('❌ Sync failed with exception: ' . $e->getMessage());
            Log::error('Ginee sync test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}