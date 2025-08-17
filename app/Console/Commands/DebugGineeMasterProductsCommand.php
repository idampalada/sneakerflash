<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class DebugGineeMasterProductsCommand extends Command
{
    protected $signature = 'ginee:debug-master-products';
    protected $description = 'Debug master products endpoint with different filters to find BOX';

    public function handle()
    {
        $this->info('🎯 Debugging Master Products with Different Filters');
        $this->newLine();

        $accessKey = config('services.ginee.access_key');
        $secretKey = config('services.ginee.secret_key');
        $apiUrl = config('services.ginee.api_url');

        // Try different filter combinations
        $filterCombinations = [
            [
                'name' => 'No Status Filter',
                'params' => [
                    'page' => 0,
                    'size' => 100,
                    'order' => 'ASC',
                    'sort' => 'name'
                ]
            ],
            [
                'name' => 'Include All Statuses', 
                'params' => [
                    'page' => 0,
                    'size' => 100,
                    'order' => 'ASC',
                    'sort' => 'name',
                    'status' => ['ENABLED', 'DISABLED', 'PENDING_REVIEW', 'DRAFT']
                ]
            ],
            [
                'name' => 'Only Disabled Products',
                'params' => [
                    'page' => 0,
                    'size' => 100,
                    'order' => 'ASC', 
                    'sort' => 'name',
                    'status' => 'DISABLED'
                ]
            ],
            [
                'name' => 'Search by Name',
                'params' => [
                    'page' => 0,
                    'size' => 100,
                    'searchKeyword' => 'BOX'
                ]
            ],
            [
                'name' => 'Search by SKU',
                'params' => [
                    'page' => 0,
                    'size' => 100,
                    'searchKeyword' => 'BOX',
                    'searchType' => 'sku'
                ]
            ]
        ];

        foreach ($filterCombinations as $combination) {
            $this->info("🔍 Testing: {$combination['name']}");
            $this->line("Parameters: " . json_encode($combination['params']));
            
            $boxProducts = $this->searchMasterProducts($apiUrl, $accessKey, $secretKey, $combination['params']);
            
            if (!empty($boxProducts)) {
                $this->info("  ✅ Found " . count($boxProducts) . " BOX product(s):");
                foreach ($boxProducts as $product) {
                    $this->table(
                        ['Field', 'Value'],
                        [
                            ['Product ID', $product['productId'] ?? 'N/A'],
                            ['Name', $product['name'] ?? 'N/A'],
                            ['Master SKU', $this->extractMasterSku($product)],
                            ['Status', $product['masterProductStatus'] ?? 'N/A'],
                            ['Categories', $this->extractCategories($product)],
                            ['Variations', $this->countVariations($product)],
                            ['Sample Variation SKUs', $this->extractVariationSkus($product)]
                        ]
                    );
                }
                
                // If found, show detailed structure
                $this->newLine();
                $this->info('📋 Detailed Structure of First BOX Product:');
                $firstProduct = $boxProducts[0];
                $this->showProductStructure($firstProduct);
                
            } else {
                $this->warn("  ❌ No BOX products found");
            }
            
            $this->newLine();
        }

        return 0;
    }

    private function searchMasterProducts($apiUrl, $accessKey, $secretKey, $params)
    {
        try {
            $requestUri = '/openapi/product/master/v1/list';
            $httpMethod = 'POST';

            $signatureString = $httpMethod . '$' . $requestUri . '$';
            $signature = base64_encode(hash_hmac('sha256', $signatureString, $secretKey, true));
            $authorization = $accessKey . ':' . $signature;

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => $authorization,
                    'Content-Type' => 'application/json',
                    'X-Advai-Country' => 'ID'
                ])
                ->post($apiUrl . $requestUri, $params);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['code']) && $data['code'] === 'SUCCESS') {
                    $products = $data['data']['content'] ?? [];
                    
                    // Filter for BOX products
                    return array_filter($products, function($product) {
                        $name = $product['name'] ?? '';
                        $productId = $product['productId'] ?? '';
                        
                        // Check name
                        if (stripos($name, 'BOX') !== false) {
                            return true;
                        }
                        
                        // Check variations for BOX SKU
                        if (isset($product['variationBriefs'])) {
                            foreach ($product['variationBriefs'] as $variation) {
                                $sku = $variation['sku'] ?? '';
                                if (stripos($sku, 'BOX') !== false) {
                                    return true;
                                }
                            }
                        }
                        
                        return false;
                    });
                }
            } else {
                $this->warn("  ❌ API Error: " . $response->status() . " - " . $response->body());
            }
        } catch (\Exception $e) {
            $this->warn("  ❌ Exception: " . $e->getMessage());
        }

        return [];
    }

    private function extractMasterSku($product)
    {
        // Try different possible SKU fields
        return $product['masterSku'] ?? 
               $product['sku'] ?? 
               $product['productCode'] ?? 
               $product['productId'] ?? 
               'N/A';
    }

    private function extractCategories($product)
    {
        $categories = $product['fullCategoryName'] ?? [];
        return is_array($categories) ? implode(' > ', $categories) : 'N/A';
    }

    private function countVariations($product)
    {
        $variations = $product['variationBriefs'] ?? [];
        return is_array($variations) ? count($variations) : 0;
    }

    private function extractVariationSkus($product)
    {
        $variations = $product['variationBriefs'] ?? [];
        if (is_array($variations)) {
            $skus = array_map(function($variation) {
                return $variation['sku'] ?? 'No SKU';
            }, array_slice($variations, 0, 3)); // Show first 3
            return implode(', ', $skus);
        }
        return 'N/A';
    }

    private function showProductStructure($product)
    {
        $this->line('📄 Product Structure:');
        $this->line('Available fields: ' . implode(', ', array_keys($product)));
        
        if (isset($product['variationBriefs']) && !empty($product['variationBriefs'])) {
            $this->line('Variation fields: ' . implode(', ', array_keys($product['variationBriefs'][0])));
            
            $firstVariation = $product['variationBriefs'][0];
            if (isset($firstVariation['stock'])) {
                $this->line('Stock fields: ' . implode(', ', array_keys($firstVariation['stock'])));
            }
        }
    }
}