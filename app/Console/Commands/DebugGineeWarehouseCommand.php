<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class DebugGineeWarehouseCommand extends Command
{
    protected $signature = 'ginee:debug-warehouse';
    protected $description = 'Debug warehouse inventory endpoint with proper warehouseId';

    public function handle()
    {
        $this->info('🏭 Debugging Ginee Warehouse Inventory');
        $this->newLine();

        $accessKey = config('services.ginee.access_key');
        $secretKey = config('services.ginee.secret_key');
        $apiUrl = config('services.ginee.api_url');

        // Step 1: Get warehouses list first
        $this->info('📋 Step 1: Getting warehouses list...');
        $warehouses = $this->getWarehouses($apiUrl, $accessKey, $secretKey);

        if (empty($warehouses)) {
            $this->warn('⚠️ No warehouses found or API error');
            $this->line('Trying with default warehouse ID...');
            $warehouses = [
                ['id' => 'WW614C578E21B840001DDD306', 'name' => 'Default Warehouse'],
                ['id' => 'DEFAULT', 'name' => 'Default']
            ];
        } else {
            $this->info('✅ Found warehouses:');
            $this->table(['Warehouse ID', 'Name'], $warehouses);
        }

        $this->newLine();

        // Step 2: Try each warehouse to find BOX
        $this->info('🔍 Step 2: Searching BOX in each warehouse...');
        
        foreach ($warehouses as $warehouse) {
            $warehouseId = $warehouse['id'];
            $warehouseName = $warehouse['name'];
            
            $this->line("🏭 Testing warehouse: {$warehouseName} ({$warehouseId})");
            
            $boxProducts = $this->searchBoxInWarehouse($apiUrl, $accessKey, $secretKey, $warehouseId);
            
            if (!empty($boxProducts)) {
                $this->info("  ✅ Found " . count($boxProducts) . " BOX product(s):");
                foreach ($boxProducts as $product) {
                    $this->table(
                        ['Field', 'Value'],
                        [
                            ['SKU', $product['sku']],
                            ['Product Name', $product['productName'] ?? 'N/A'],
                            ['Available Stock', $product['availableStock'] ?? 0],
                            ['Warehouse Stock', $product['warehouseStock'] ?? 0],
                            ['Reserved Stock', $product['reservedStock'] ?? 0],
                        ]
                    );
                }
            } else {
                $this->warn("  ❌ No BOX products found in this warehouse");
            }
        }

        $this->newLine();

        // Step 3: Show solution
        $this->info('💡 Next Steps:');
        $this->line('1. If BOX found in a warehouse, update service to use that warehouseId');
        $this->line('2. If no BOX found, check if product exists in different Ginee account/environment');
        $this->line('3. Verify BOX product is properly synced to warehouse in Ginee dashboard');

        return 0;
    }

    private function getWarehouses($apiUrl, $accessKey, $secretKey)
    {
        try {
            // Try to get warehouses list
            $requestUri = '/openapi/warehouse/v1/list';
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
                if (isset($data['code']) && $data['code'] === 'SUCCESS' && isset($data['data'])) {
                    $warehouses = [];
                    foreach ($data['data'] as $warehouse) {
                        $warehouses[] = [
                            'id' => $warehouse['warehouseId'] ?? $warehouse['id'] ?? 'unknown',
                            'name' => $warehouse['warehouseName'] ?? $warehouse['name'] ?? 'Unknown Warehouse'
                        ];
                    }
                    return $warehouses;
                }
            }
        } catch (\Exception $e) {
            $this->warn('Failed to get warehouses: ' . $e->getMessage());
        }

        return [];
    }

    private function searchBoxInWarehouse($apiUrl, $accessKey, $secretKey, $warehouseId)
    {
        try {
            $requestUri = '/openapi/warehouse-inventory/v1/sku/list';
            $httpMethod = 'POST';
            $requestData = [
                'page' => 0,
                'size' => 100,
                'warehouseId' => $warehouseId
            ];

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
                    $products = $data['data'] ?? [];
                    
                    // Filter for BOX products
                    return array_filter($products, function($product) {
                        $sku = $product['sku'] ?? '';
                        $productName = $product['productName'] ?? '';
                        return stripos($sku, 'BOX') !== false || stripos($productName, 'BOX') !== false;
                    });
                }
            }
        } catch (\Exception $e) {
            $this->warn("  ❌ Error: " . $e->getMessage());
        }

        return [];
    }
}