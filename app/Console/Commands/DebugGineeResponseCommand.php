<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DebugGineeResponseCommand extends Command
{
    protected $signature = 'ginee:debug-response';
    protected $description = 'Debug Ginee API response structure to understand the format';

    public function handle()
    {
        $this->info('🔍 Debugging Ginee API Response Structure');
        $this->newLine();

        $accessKey = config('services.ginee.access_key');
        $secretKey = config('services.ginee.secret_key');
        $apiUrl = config('services.ginee.api_url');

        if (!$accessKey || !$secretKey) {
            $this->error('❌ Missing Ginee API credentials');
            return 1;
        }

        $this->info('📡 Testing multiple Ginee API endpoints...');
        $this->newLine();

        // Test berbagai endpoint Ginee
        $endpoints = [
            '/openapi/product/v1/list' => 'POST',
            '/openapi/products' => 'GET',
            '/openapi/inventory/v1/list' => 'POST',
            '/api/products' => 'GET',
            '/api/v1/products' => 'GET',
        ];

        foreach ($endpoints as $endpoint => $method) {
            $this->testEndpoint($apiUrl, $endpoint, $method, $accessKey, $secretKey);
            $this->newLine();
        }

        $this->info('✅ Debug completed! Check logs for detailed response structures.');
        return 0;
    }

    private function testEndpoint($apiUrl, $endpoint, $method, $accessKey, $secretKey)
    {
        $this->info("🔄 Testing: {$method} {$endpoint}");

        try {
            // Build signature
            $signatureString = $method . '$' . $endpoint . '$';
            $signature = base64_encode(hash_hmac('sha256', $signatureString, $secretKey, true));
            $authorization = $accessKey . ':' . $signature;

            $this->line("   Signature: {$signatureString}");
            $this->line("   Auth: " . substr($authorization, 0, 20) . '...');

            // Prepare request
            $requestData = [];
            if ($method === 'POST') {
                $requestData = [
                    'page' => 0,
                    'size' => 10
                ];
            }

            // Make request
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => $authorization,
                    'Content-Type' => 'application/json',
                    'X-Advai-Country' => 'ID'
                ]);

            if ($method === 'POST') {
                $response = $response->post($apiUrl . $endpoint, $requestData);
            } else {
                $response = $response->get($apiUrl . $endpoint, $requestData);
            }

            // Log response details
            $statusCode = $response->status();
            $responseBody = $response->body();
            $responseJson = $response->json();

            $this->line("   Status: {$statusCode}");
            
            if ($response->successful()) {
                $this->info("   ✅ Success!");
                
                // Analyze response structure
                if ($responseJson) {
                    $this->line("   📊 Response structure:");
                    $this->analyzeResponseStructure($responseJson, '      ');
                    
                    // Log untuk debugging
                    Log::info("Ginee API Endpoint Test: {$method} {$endpoint}", [
                        'status' => $statusCode,
                        'response_structure' => $this->getResponseStructure($responseJson),
                        'sample_data' => $this->getSampleData($responseJson)
                    ]);
                } else {
                    $this->warn("   ⚠️ Non-JSON response");
                    $this->line("   Raw response: " . substr($responseBody, 0, 200) . '...');
                }
            } else {
                $this->error("   ❌ Failed: {$statusCode}");
                $this->line("   Error: " . substr($responseBody, 0, 100) . '...');
                
                Log::warning("Ginee API Endpoint Failed: {$method} {$endpoint}", [
                    'status' => $statusCode,
                    'response' => $responseBody
                ]);
            }

        } catch (\Exception $e) {
            $this->error("   💥 Exception: " . $e->getMessage());
            Log::error("Ginee API Endpoint Exception: {$method} {$endpoint}", [
                'error' => $e->getMessage()
            ]);
        }
    }

    private function analyzeResponseStructure($data, $indent = '')
    {
        if (is_array($data)) {
            $this->line($indent . "Array (" . count($data) . " items)");
            if (!empty($data)) {
                $firstKey = array_key_first($data);
                $this->line($indent . "├─ First key: '{$firstKey}'");
                if (is_array($data[$firstKey]) || is_object($data[$firstKey])) {
                    $this->analyzeResponseStructure($data[$firstKey], $indent . '│  ');
                } else {
                    $this->line($indent . "│  └─ " . gettype($data[$firstKey]) . ": " . (string)$data[$firstKey]);
                }
            }
        } elseif (is_object($data)) {
            $properties = get_object_vars($data);
            $this->line($indent . "Object (" . count($properties) . " properties)");
            foreach (array_slice($properties, 0, 3) as $key => $value) {
                $this->line($indent . "├─ {$key}: " . gettype($value));
                if ((is_array($value) || is_object($value)) && count((array)$value) > 0) {
                    $this->analyzeResponseStructure($value, $indent . '│  ');
                }
            }
        } else {
            $this->line($indent . gettype($data) . ": " . (string)$data);
        }
    }

    private function getResponseStructure($data)
    {
        if (is_array($data)) {
            $structure = [
                'type' => 'array',
                'count' => count($data),
                'keys' => array_keys($data)
            ];
            
            if (!empty($data)) {
                $structure['first_item_type'] = gettype($data[array_key_first($data)]);
            }
            
            return $structure;
        } elseif (is_object($data)) {
            return [
                'type' => 'object',
                'properties' => array_keys(get_object_vars($data))
            ];
        } else {
            return [
                'type' => gettype($data),
                'value' => (string)$data
            ];
        }
    }

    private function getSampleData($data)
    {
        if (is_array($data) && !empty($data)) {
            return array_slice($data, 0, 2); // First 2 items
        } elseif (is_object($data)) {
            $props = get_object_vars($data);
            return array_slice($props, 0, 5); // First 5 properties
        } else {
            return $data;
        }
    }
}