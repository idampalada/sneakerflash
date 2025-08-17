<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CheckGineeChannelProductsCommand extends Command
{
    protected $signature = 'ginee:check-channels {--shop-id= : Specific shop ID to check}';
    protected $description = 'Check channel-specific products to find BOX';

    public function handle()
    {
        $this->info('🏪 Checking Channel-Specific Products for BOX');
        $this->newLine();

        $accessKey = config('services.ginee.access_key');
        $secretKey = config('services.ginee.secret_key');
        $apiUrl = config('services.ginee.api_url');

        $specificShopId = $this->option('shop-id');

        // 1. Get shops first
        $shops = $this->getShops($apiUrl, $accessKey, $secretKey);
        
        if (empty($shops)) {
            $this->error('❌ No shops found');
            return 1;
        }

        $this->info('✅ Found ' . count($shops) . ' shops:');
        $this->table(['Shop ID', 'Name', 'Channel'], $shops);
        $this->newLine();

        // 2. Check each shop for products
        foreach ($shops as $shop) {
            $shopId = $shop['shop_id'];
            $shopName = $shop['name'];
            $channel = $shop['channel'];

            // Skip if specific shop ID requested
            if ($specificShopId && $shopId !== $specificShopId) {
                continue;
            }

            $this->info("🔍 Checking shop: {$shopName} ({$channel})");
            
            // Try different product endpoints for this shop
            $boxFound = $this->searchBoxInShop($apiUrl, $accessKey, $secretKey, $shopId, $shopName);
            
            if ($boxFound) {
                $this->info("✅ BOX found in {$shopName}!");
                return 0; // Exit on first success
            }
            
            $this->newLine();
        }

        $this->warn('❌ BOX not found in any shop/channel');
        
        // Final recommendation
        $this->newLine();
        $this->info('💡 Final Recommendations:');
        $this->line('1. BOX might be in draft/pending status that requires manual approval');
        $this->line('2. BOX might be a marketplace-only product not synced to Ginee API');
        $this->line('3. BOX might be in a different Ginee account/environment');
        $this->line('4. Consider manual stock update or contact Ginee support');

        return 0;
    }

    private function getShops($apiUrl, $accessKey, $secretKey)
    {
        try {
            $requestUri = '/openapi/shop/v1/list';
            $httpMethod = 'POST';
            $requestData = ['page' => 0, 'size' => 50];

            $signatureString = $httpMethod . '$' . $requestUri . '$';
            $signature = base64_encode(hash_hmac('sha256', $signatureString, $secretKey, true));
            $authorization = $accessKey . ':' . $signature;

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => $authorization,
                    'Content-Type' => 'application/json',
                    'X-Advai-Country' => 'ID'
                ])
                ->post($apiUrl . $requestUri, $requestData);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['code']) && $data['code'] === 'SUCCESS') {
                    $shops = [];
                    foreach ($data['data'] ?? [] as $shop) {
                        $shops[] = [
                            'shop_id' => $shop['shopId'] ?? 'N/A',
                            'name' => $shop['name'] ?? 'N/A', 
                            'channel' => $shop['channel'] ?? 'N/A'
                        ];
                    }
                    return $shops;
                }
            }
        } catch (\Exception $e) {
            $this->warn('Shop list error: ' . $e->getMessage());
        }

        return [];
    }

    private function searchBoxInShop($apiUrl, $accessKey, $secretKey, $shopId, $shopName)
    {
        // Try shop-specific product endpoints
        $endpoints = [
            '/openapi/product/shop/v1/list' => 'Shop products',
            '/openapi/product/channel/v1/list' => 'Channel products',
            '/openapi/listing/v1/list' => 'Product listings'
        ];

        foreach ($endpoints as $endpoint => $description) {
            $this->line("  🔍 Trying {$description} ({$endpoint})...");
            
            try {
                $httpMethod = 'POST';
                $requestData = [
                    'page' => 0,
                    'size' => 100,
                    'shopId' => $shopId
                ];

                $signatureString = $httpMethod . '$' . $endpoint . '$';
                $signature = base64_encode(hash_hmac('sha256', $signatureString, $secretKey, true));
                $authorization = $accessKey . ':' . $signature;

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
                        $products = $data['data'] ?? $data['data']['content'] ?? [];
                        
                        if (is_array($products)) {
                            $boxProducts = $this->findBoxInProducts($products);
                            
                            if (!empty($boxProducts)) {
                                $this->info("    ✅ Found " . count($boxProducts) . " BOX product(s):");
                                foreach ($boxProducts as $product) {
                                    $this->table(
                                        ['Field', 'Value'],
                                        [
                                            ['SKU/ID', $this->extractSku($product)],
                                            ['Name', $product['name'] ?? $product['productName'] ?? 'N/A'],
                                            ['Status', $product['status'] ?? $product['listingStatus'] ?? 'N/A'],
                                            ['Stock', $this->extractStock($product)],
                                            ['Shop', $shopName],
                                        ]
                                    );
                                }
                                return true;
                            } else {
                                $this->line("    ❌ No BOX found (checked " . count($products) . " products)");
                            }
                        }
                    } else {
                        $this->line("    ❌ API Error: " . ($data['message'] ?? 'Unknown'));
                    }
                } else {
                    $this->line("    ❌ HTTP Error: " . $response->status());
                }
            } catch (\Exception $e) {
                $this->line("    ❌ Exception: " . $e->getMessage());
            }
        }

        return false;
    }

    private function findBoxInProducts($products)
    {
        return array_filter($products, function($product) {
            $name = $product['name'] ?? $product['productName'] ?? '';
            $sku = $this->extractSku($product);
            
            return stripos($name, 'BOX') !== false || stripos($sku, 'BOX') !== false;
        });
    }

    private function extractSku($product)
    {
        return $product['sku'] ?? 
               $product['masterSku'] ?? 
               $product['productSku'] ?? 
               $product['itemSku'] ?? 
               $product['productId'] ?? 
               'N/A';
    }

    private function extractStock($product)
    {
        return $product['stock'] ?? 
               $product['quantity'] ?? 
               $product['availableStock'] ?? 
               $product['stockQuantity'] ?? 
               'N/A';
    }
}