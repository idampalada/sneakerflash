<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class VerifyGineeAccountCommand extends Command
{
    protected $signature = 'ginee:verify-account';
    protected $description = 'Verify Ginee account info and API environment';

    public function handle()
    {
        $this->info('🔐 Verifying Ginee Account & Environment');
        $this->newLine();

        $accessKey = config('services.ginee.access_key');
        $secretKey = config('services.ginee.secret_key');
        $apiUrl = config('services.ginee.api_url');

        $this->info('📋 Current Configuration:');
        $this->table(
            ['Config', 'Value'],
            [
                ['API URL', $apiUrl],
                ['Access Key', substr($accessKey, 0, 8) . '...'],
                ['Secret Key', substr($secretKey, 0, 8) . '...'],
                ['Environment', 'Production'], // Ginee API is production
            ]
        );

        $this->newLine();

        // 1. Get account info
        $this->info('👤 Step 1: Getting account information...');
        $accountInfo = $this->getAccountInfo($apiUrl, $accessKey, $secretKey);
        
        if ($accountInfo) {
            $this->info('✅ Account Info Retrieved:');
            $this->table(['Field', 'Value'], $accountInfo);
        } else {
            $this->warn('❌ Failed to get account info');
        }

        $this->newLine();

        // 2. Get shop/channel list
        $this->info('🏪 Step 2: Getting shops/channels...');
        $shops = $this->getShops($apiUrl, $accessKey, $secretKey);
        
        if (!empty($shops)) {
            $this->info('✅ Found ' . count($shops) . ' shop(s):');
            $this->table(['Shop ID', 'Name', 'Channel', 'Status'], $shops);
        } else {
            $this->warn('❌ No shops found or API error');
        }

        $this->newLine();

        // 3. Check API permissions
        $this->info('🔑 Step 3: Testing API endpoint permissions...');
        $permissions = $this->testPermissions($apiUrl, $accessKey, $secretKey);
        
        $this->table(['Endpoint', 'Status', 'Details'], $permissions);

        $this->newLine();

        // 4. Recommendations
        $this->info('💡 Recommendations:');
        
        if (empty($shops)) {
            $this->warn('⚠️ No shops found - this might explain missing products');
            $this->line('1. Verify API credentials are for correct Ginee account');
            $this->line('2. Check if shops need to be connected/enabled for API access');
        }
        
        $blibliShop = collect($shops)->firstWhere('channel', 'BLIBLI');
        if (!$blibliShop) {
            $this->warn('⚠️ BLIBLI shop not found in API - BOX might be in non-API channel');
            $this->line('1. Check if SF-BLIBLI shop needs API integration setup');
            $this->line('2. Verify shop is properly connected to Ginee account');
        } else {
            $this->info('✅ BLIBLI shop found - should have access to BOX product');
        }

        return 0;
    }

    private function getAccountInfo($apiUrl, $accessKey, $secretKey)
    {
        try {
            // Try to get account/user info
            $requestUri = '/openapi/user/v1/info';
            $httpMethod = 'GET';

            $signatureString = $httpMethod . '$' . $requestUri . '$';
            $signature = base64_encode(hash_hmac('sha256', $signatureString, $secretKey, true));
            $authorization = $accessKey . ':' . $signature;

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => $authorization,
                    'Content-Type' => 'application/json',
                    'X-Advai-Country' => 'ID'
                ])
                ->get($apiUrl . $requestUri);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['code']) && $data['code'] === 'SUCCESS') {
                    $user = $data['data'] ?? [];
                    return [
                        ['User ID', $user['userId'] ?? 'N/A'],
                        ['Email', $user['email'] ?? 'N/A'],
                        ['Company', $user['companyName'] ?? 'N/A'],
                        ['Role', $user['role'] ?? 'N/A'],
                        ['Status', $user['status'] ?? 'N/A'],
                    ];
                }
            }
        } catch (\Exception $e) {
            $this->warn('Account info error: ' . $e->getMessage());
        }

        return null;
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
                            'channel' => $shop['channel'] ?? 'N/A',
                            'status' => $shop['authorizationStatus'] ?? 'N/A'
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

    private function testPermissions($apiUrl, $accessKey, $secretKey)
    {
        $endpoints = [
            '/openapi/shop/v1/list' => 'Basic shop access',
            '/openapi/product/master/v1/list' => 'Master products',
            '/openapi/warehouse-inventory/v1/sku/list' => 'Warehouse inventory',
            '/openapi/inventory/v1/list' => 'Inventory management',
            '/openapi/order/v1/list' => 'Order management'
        ];

        $results = [];
        
        foreach ($endpoints as $endpoint => $description) {
            try {
                $httpMethod = 'POST';
                $requestData = ['page' => 0, 'size' => 1];

                $signatureString = $httpMethod . '$' . $endpoint . '$';
                $signature = base64_encode(hash_hmac('sha256', $signatureString, $secretKey, true));
                $authorization = $accessKey . ':' . $signature;

                $response = Http::timeout(15)
                    ->withHeaders([
                        'Authorization' => $authorization,
                        'Content-Type' => 'application/json',
                        'X-Advai-Country' => 'ID'
                    ])
                    ->post($apiUrl . $endpoint, $requestData);

                if ($response->successful()) {
                    $data = $response->json();
                    if (isset($data['code']) && $data['code'] === 'SUCCESS') {
                        $results[] = [$endpoint, '✅ Accessible', $description];
                    } else {
                        $results[] = [$endpoint, '❌ API Error', $data['message'] ?? 'Unknown error'];
                    }
                } else {
                    $results[] = [$endpoint, '❌ HTTP ' . $response->status(), substr($response->body(), 0, 50)];
                }
            } catch (\Exception $e) {
                $results[] = [$endpoint, '❌ Exception', $e->getMessage()];
            }
        }

        return $results;
    }
}