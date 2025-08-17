<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GineeOnlyStockSyncService;
use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DebugBoxSkuCommand extends Command
{
    protected $signature = 'ginee:debug-box';
    protected $description = 'Debug specifically why BOX SKU is not being found in Ginee sync';

    public function handle()
    {
        $this->info('🔍 Debugging BOX SKU Issue');
        $this->newLine();

        // 1. Check database
        $boxProduct = Product::where('sku', 'BOX')->first();
        if ($boxProduct) {
            $this->info('✅ BOX found in Database:');
            $this->table(
                ['Field', 'Value'],
                [
                    ['ID', $boxProduct->id],
                    ['Name', $boxProduct->name],
                    ['SKU', $boxProduct->sku],
                    ['Current Stock', $boxProduct->stock_quantity ?? 0],
                    ['Brand', $boxProduct->brand ?? 'N/A'],
                    ['Category', $boxProduct->category->name ?? 'N/A'],
                ]
            );
        } else {
            $this->error('❌ BOX not found in database');
            return 1;
        }

        $this->newLine();

        // 2. Check all Ginee endpoints for BOX
        $this->info('🔍 Searching BOX in all Ginee endpoints...');
        $this->searchBoxInGinee();

        $this->newLine();

        // 3. Test sync service
        $this->info('🔄 Testing GineeOnlyStockSyncService...');
        try {
            $gineeService = new GineeOnlyStockSyncService();
            $preview = $gineeService->previewStockSync();

            if ($preview['success']) {
                $mskuMap = $preview['msku_map'] ?? [];
                
                if (isset($mskuMap['BOX'])) {
                    $this->info('✅ BOX found in sync service MSKU map:');
                    $boxData = $mskuMap['BOX'];
                    $this->table(
                        ['Field', 'Value'],
                        [
                            ['MSKU', $boxData['msku']],
                            ['Stock', $boxData['stock']],
                            ['Product Name', $boxData['product_name']],
                            ['Product ID', $boxData['product_id'] ?? 'N/A'],
                            ['Status', $boxData['status'] ?? 'N/A'],
                        ]
                    );
                } else {
                    $this->warn('❌ BOX not found in sync service MSKU map');
                    
                    // Show similar SKUs
                    $similarSkus = [];
                    foreach ($mskuMap as $sku => $data) {
                        if (stripos($sku, 'box') !== false || stripos($data['product_name'], 'box') !== false) {
                            $similarSkus[] = [
                                'sku' => $sku,
                                'name' => substr($data['product_name'], 0, 40),
                                'stock' => $data['stock']
                            ];
                        }
                    }
                    
                    if (!empty($similarSkus)) {
                        $this->warn('📦 Found BOX-related products:');
                        $this->table(['SKU', 'Product Name', 'Stock'], $similarSkus);
                    } else {
                        $this->error('❌ No BOX-related products found in Ginee');
                    }
                }

                $this->newLine();
                $this->info('📊 Overall MSKU Statistics:');
                $this->line("Total MSKUs from Ginee: " . count($mskuMap));
                $this->line("Matched with Database: " . count($preview['matched_skus']));
                $this->line("Unmatched: " . count($preview['unmatched_skus']));

            } else {
                $this->error('❌ Sync service failed: ' . $preview['message']);
            }
        } catch (\Exception $e) {
            $this->error('❌ Sync service exception: ' . $e->getMessage());
        }

        $this->newLine();

        // 4. Recommendations
        $this->info('💡 Recommendations based on analysis:');
        $this->line('1. Check if BOX product is enabled in Ginee dashboard');
        $this->line('2. Verify BOX product is in correct warehouse/channel');
        $this->line('3. Check if API has access to disabled/draft products');
        $this->line('4. Consider using different endpoint or filters');

        return 0;
    }

    private function searchBoxInGinee()
    {
        $accessKey = config('services.ginee.access_key');
        $secretKey = config('services.ginee.secret_key');
        $apiUrl = config('services.ginee.api_url');

        $endpoints = [
            '/openapi/product/master/v1/list' => 'Master Products',
            '/openapi/warehouse-inventory/v1/sku/list' => 'Warehouse Inventory',
            '/openapi/inventory/v1/list' => 'Inventory List'
        ];

        foreach ($endpoints as $endpoint => $description) {
            $this->line("🔍 Testing {$description} ({$endpoint})...");
            
            try {
                $httpMethod = 'POST';
                $requestData = [
                    'page' => 0,
                    'size' => 100
                ];

                // Build signature
                $signatureString = $httpMethod . '$' . $endpoint . '$';
                $signature = base64_encode(hash_hmac('sha256', $signatureString, $secretKey, true));
                $authorization = $accessKey . ':' . $signature;

                // Make API call
                $response = Http::timeout(30)
                    ->withHeaders([
                        'Authorization' => $authorization,
                        'Content-Type' => 'application/json',
                        'X-Advai-Country' => 'ID'
                    ])
                    ->post($apiUrl . $endpoint, $requestData);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    if (isset($data['code']) && $data['code'] === 'SUCCESS') {
                        $products = $this->extractProductsFromResponse($data);
                        $boxProducts = $this->findBoxProducts($products);
                        
                        if (!empty($boxProducts)) {
                            $this->info("  ✅ Found " . count($boxProducts) . " BOX product(s):");
                            foreach ($boxProducts as $product) {
                                $this->line("    • SKU: {$product['sku']}, Name: {$product['name']}, Stock: {$product['stock']}");
                            }
                        } else {
                            $this->warn("  ❌ No BOX products found");
                        }
                        
                        $this->line("  📊 Total products in endpoint: " . count($products));
                    } else {
                        $this->error("  ❌ API error: " . ($data['message'] ?? 'Unknown error'));
                    }
                } else {
                    $this->error("  ❌ HTTP error: {$response->status()}");
                }
            } catch (\Exception $e) {
                $this->error("  ❌ Exception: " . $e->getMessage());
            }

            $this->newLine();
        }
    }

    private function extractProductsFromResponse($data)
    {
        $products = [];

        // Try different response structures
        if (isset($data['data']['content']) && is_array($data['data']['content'])) {
            $rawProducts = $data['data']['content'];
        } elseif (isset($data['data']) && is_array($data['data'])) {
            $rawProducts = $data['data'];
        } else {
            return [];
        }

        foreach ($rawProducts as $product) {
            // Extract basic info
            $productInfo = [
                'name' => $product['name'] ?? 'No Name',
                'product_id' => $product['productId'] ?? $product['id'] ?? 'No ID',
                'status' => $product['masterProductStatus'] ?? $product['status'] ?? 'unknown'
            ];

            // Extract SKUs and stocks
            if (isset($product['variationBriefs']) && is_array($product['variationBriefs'])) {
                foreach ($product['variationBriefs'] as $variation) {
                    $sku = $variation['sku'] ?? '';
                    if (!empty($sku)) {
                        $stockInfo = $variation['stock'] ?? [];
                        $products[] = array_merge($productInfo, [
                            'sku' => $sku,
                            'stock' => $stockInfo['availableStock'] ?? 0,
                            'type' => 'variation'
                        ]);
                    }
                }
            } else {
                // Direct product SKU
                $sku = $product['sku'] ?? $product['masterSku'] ?? $product['code'] ?? $product['productId'] ?? '';
                if (!empty($sku)) {
                    $products[] = array_merge($productInfo, [
                        'sku' => $sku,
                        'stock' => $product['stock'] ?? $product['availableStock'] ?? 0,
                        'type' => 'direct'
                    ]);
                }
            }
        }

        return $products;
    }

    private function findBoxProducts($products)
    {
        return array_filter($products, function($product) {
            return stripos($product['sku'], 'BOX') !== false || 
                   stripos($product['name'], 'BOX') !== false;
        });
    }
}