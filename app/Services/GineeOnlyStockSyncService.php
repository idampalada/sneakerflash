<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class GineeOnlyStockSyncService
{
    private $gineeApiUrl;
    private $gineeAccessKey;
    private $gineeSecretKey;

    public function __construct()
    {
        $this->gineeApiUrl = config('services.ginee.api_url') ?: env('GINEE_API_URL', 'https://api.ginee.com');
        $this->gineeAccessKey = config('services.ginee.access_key') ?: env('GINEE_ACCESS_KEY');
        $this->gineeSecretKey = config('services.ginee.secret_key') ?: env('GINEE_SECRET_KEY');

        Log::info('GineeOnlyStockSyncService initialized', [
            'api_url' => $this->gineeApiUrl,
            'access_key_set' => !empty($this->gineeAccessKey),
            'secret_key_set' => !empty($this->gineeSecretKey)
        ]);
    }

    /**
     * Sync stock dari Ginee ke database (tanpa spreadsheet)
     * Hanya update stock produk yang sudah ada di database
     */
    public function syncStockFromGinee(): array
    {
        try {
            Log::info('Starting Ginee-only stock sync');

            // Step 1: Ambil data dari Ginee
            $gineeResult = $this->fetchGineeProducts();
            if (!$gineeResult['success']) {
                return $gineeResult;
            }

            // Step 2: Buat mapping MSKU dari Ginee
            $gineeMskuMap = [];
            foreach ($gineeResult['products'] as $product) {
                // Check if product has variations
                if (isset($product['variationBriefs']) && is_array($product['variationBriefs'])) {
                    foreach ($product['variationBriefs'] as $variation) {
                        $msku = $variation['sku'] ?? '';
                        if (!empty($msku)) {
                            $stockInfo = $variation['stock'] ?? [];
                            $gineeMskuMap[$msku] = [
                                'msku' => $msku,
                                'stock' => $stockInfo['availableStock'] ?? 0,
                                'product_name' => $product['name'] ?? '',
                                'product_id' => $product['productId'] ?? '',
                                'status' => $product['masterProductStatus'] ?? 'unknown'
                            ];
                        }
                    }
                } 
                // Check direct product fields (including Master SKU from screenshot)
                elseif (isset($product['sku']) || isset($product['masterSku']) || isset($product['code']) || isset($product['productId'])) {
                    $msku = $product['sku'] ?? $product['masterSku'] ?? $product['code'] ?? $product['productId'] ?? '';
                    if (!empty($msku)) {
                        $stock = $product['stock'] ?? 
                                $product['availableStock'] ?? 
                                $product['quantity'] ?? 
                                $product['stockQuantity'] ?? 0;
                                
                        $gineeMskuMap[$msku] = [
                            'msku' => $msku,
                            'stock' => $stock,
                            'product_name' => $product['name'] ?? '',
                            'product_id' => $product['productId'] ?? '',
                            'status' => $product['masterProductStatus'] ?? $product['status'] ?? 'unknown'
                        ];
                    }
                }
            }

            // Step 3: Update products di database
            $stats = [
                'updated' => 0,
                'not_found_in_ginee' => 0,
                'not_found_in_database' => 0,
                'errors' => 0
            ];

            $errors = [];

            // Ambil semua produk dari database
            $databaseProducts = Product::all();

            foreach ($databaseProducts as $product) {
                try {
                    $sku = $product->sku;
                    
                    if (isset($gineeMskuMap[$sku])) {
                        // Update stock dari Ginee
                        $gineeData = $gineeMskuMap[$sku];
                        $oldStock = $product->stock_quantity;
                        $newStock = $gineeData['stock'];
                        
                        $product->update([
                            'stock_quantity' => $newStock,
                            'ginee_msku' => $sku,
                            'ginee_sync_status' => 'synced',
                            'ginee_last_synced_at' => now()
                        ]);
                        
                        $stats['updated']++;
                        
                        Log::info('Stock updated from Ginee', [
                            'sku' => $sku,
                            'product_name' => $product->name,
                            'old_stock' => $oldStock,
                            'new_stock' => $newStock,
                            'ginee_product_name' => $gineeData['product_name']
                        ]);
                        
                    } else {
                        // Product tidak ditemukan di Ginee
                        $stats['not_found_in_ginee']++;
                        
                        // Mark as not found in Ginee
                        $product->update([
                            'ginee_sync_status' => 'not_found',
                            'ginee_last_synced_at' => now()
                        ]);
                        
                        Log::warning('Product SKU not found in Ginee', [
                            'sku' => $sku,
                            'product_name' => $product->name
                        ]);
                    }
                    
                } catch (Exception $e) {
                    $stats['errors']++;
                    $errors[] = [
                        'sku' => $product->sku,
                        'product_name' => $product->name,
                        'error' => $e->getMessage()
                    ];
                    
                    Log::error('Failed to update product stock', [
                        'sku' => $product->sku,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Check for products in Ginee but not in database
            $databaseSkus = $databaseProducts->pluck('sku')->toArray();
            $gineeSkus = array_keys($gineeMskuMap);
            $gineeOnlySkus = array_diff($gineeSkus, $databaseSkus);
            $stats['not_found_in_database'] = count($gineeOnlySkus);

            Log::info('Ginee-only stock sync completed', [
                'stats' => $stats,
                'total_ginee_products' => count($gineeMskuMap),
                'total_database_products' => $databaseProducts->count(),
                'ginee_only_skus' => array_slice($gineeOnlySkus, 0, 5) // Sample
            ]);

            return [
                'success' => true,
                'stats' => $stats,
                'errors' => $errors,
                'ginee_only_skus' => $gineeOnlySkus,
                'message' => 'Ginee stock sync completed successfully'
            ];

        } catch (Exception $e) {
            Log::error('Ginee-only stock sync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Preview what will be synced
     */
    public function previewStockSync(): array
    {
        try {
            // Ambil data dari Ginee
            $gineeProducts = $this->fetchGineeProducts();
            
            if (!$gineeProducts['success']) {
                return $gineeProducts;
            }

            // Ambil semua SKU dari database
            $databaseSkus = Product::pluck('sku')->toArray();
            
            // Buat mapping SKU dari Ginee
            $gineeMskuMap = [];
            foreach ($gineeProducts['products'] as $product) {
                // Check if product has variations
                if (isset($product['variationBriefs']) && is_array($product['variationBriefs'])) {
                    foreach ($product['variationBriefs'] as $variation) {
                        $msku = $variation['sku'] ?? '';
                        if (!empty($msku)) {
                            $stockInfo = $variation['stock'] ?? [];
                            $gineeMskuMap[$msku] = [
                                'msku' => $msku,
                                'stock' => $stockInfo['availableStock'] ?? 0,
                                'product_name' => $product['name'] ?? ''
                            ];
                        }
                    }
                } else {
                    // Try alternative structure
                    $msku = $product['sku'] ?? $product['masterSku'] ?? $product['code'] ?? $product['productId'] ?? '';
                    if (!empty($msku)) {
                        $stock = $product['stock'] ?? 
                                $product['availableStock'] ?? 
                                $product['quantity'] ?? 
                                $product['stockQuantity'] ?? 0;
                                
                        $gineeMskuMap[$msku] = [
                            'msku' => $msku,
                            'stock' => $stock,
                            'product_name' => $product['name'] ?? ''
                        ];
                    }
                }
            }

            // Cari SKU yang match dan tidak match
            $matchedSkus = [];
            $unmatchedSkus = [];

            foreach ($databaseSkus as $sku) {
                if (isset($gineeMskuMap[$sku])) {
                    $matchedSkus[] = $sku;
                } else {
                    $unmatchedSkus[] = $sku;
                }
            }

            return [
                'success' => true,
                'ginee_products' => $gineeProducts['products'],
                'matched_skus' => $matchedSkus,
                'unmatched_skus' => $unmatchedSkus,
                'total_database_products' => count($databaseSkus),
                'total_ginee_products' => count($gineeProducts['products']),
                'msku_map' => $gineeMskuMap
            ];

        } catch (Exception $e) {
            Log::error('Preview stock sync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to preview sync: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Fetch products dari Ginee API dengan multiple endpoint fallback
     */
    private function fetchGineeProducts(): array
    {
        try {
            // Cek cache dulu untuk menghindari API call berulang
            $cacheKey = 'ginee_products_' . md5($this->gineeAccessKey);
            $cachedData = Cache::get($cacheKey);
            
            if ($cachedData) {
                Log::info('Using cached Ginee products data');
                return $cachedData;
            }

            // Coba beberapa endpoint yang berbeda
            $endpoints = [
                '/openapi/product/master/v1/list' => 'master_product', // Try master products first
                '/openapi/warehouse-inventory/v1/sku/list' => 'warehouse_inventory',
                '/openapi/inventory/v1/list' => 'inventory',
                '/openapi/shop/v1/list' => 'shop', // Test endpoint untuk validasi akses
            ];

            foreach ($endpoints as $endpoint => $type) {
                Log::info("Trying Ginee endpoint: {$endpoint}");
                
                $result = $this->tryGineeEndpoint($endpoint, $type);
                
                if ($result['success']) {
                    // Cache result untuk 10 menit
                    Cache::put($cacheKey, $result, 600);
                    return $result;
                } else {
                    Log::warning("Endpoint {$endpoint} failed: " . $result['message']);
                }
            }

            return [
                'success' => false,
                'message' => 'All Ginee API endpoints failed. Please check your API permissions.'
            ];

        } catch (Exception $e) {
            Log::error('Failed to fetch Ginee products', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to connect to Ginee API: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Try specific Ginee endpoint
     */
    private function tryGineeEndpoint(string $requestUri, string $type): array
    {
        try {
            $httpMethod = 'POST';
            
            // Different request parameters for different endpoints
            $requestData = match($type) {
                'master_product' => [
                    'page' => 0,
                    'size' => 100,
                    'order' => 'ASC',
                    'sort' => 'name'
                    // Don't filter by status to include DISABLED products
                ],
                'warehouse_inventory' => [
                    'page' => 0,
                    'size' => 100
                ],
                'inventory' => [
                    'page' => 0, 
                    'size' => 100
                ],
                'shop' => [
                    'page' => 0,
                    'size' => 10
                ],
                default => [
                    'page' => 0,
                    'size' => 100
                ]
            };
            
            // Build signature sesuai dokumentasi Ginee
            $signatureString = $httpMethod . '$' . $requestUri . '$';
            $signature = base64_encode(hash_hmac('sha256', $signatureString, $this->gineeSecretKey, true));
            $authorization = $this->gineeAccessKey . ':' . $signature;

            // Call Ginee API
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => $authorization,
                    'Content-Type' => 'application/json',
                    'X-Advai-Country' => 'ID'
                ])
                ->post($this->gineeApiUrl . $requestUri, $requestData);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => "Endpoint {$requestUri} failed: {$response->status()} - {$response->body()}"
                ];
            }

            $data = $response->json();
            
            // Parse response based on endpoint type
            if ($type === 'shop') {
                // Shop endpoint is just for testing access
                if (isset($data['code']) && $data['code'] === 'SUCCESS') {
                    return [
                        'success' => false,
                        'message' => 'Shop endpoint works but contains no product data. Trying other endpoints...'
                    ];
                }
            }
            
            // Parse product data
            $products = $this->parseGineeResponse($data, $type);
            
            if (empty($products)) {
                return [
                    'success' => false,
                    'message' => "No products found in {$requestUri} response"
                ];
            }

            Log::info('Successfully fetched products from Ginee', [
                'endpoint' => $requestUri,
                'total_products' => count($products),
                'response_code' => $data['code'] ?? 'unknown'
            ]);

            return [
                'success' => true,
                'products' => $products,
                'total' => count($products),
                'endpoint_used' => $requestUri
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => "Endpoint {$requestUri} exception: " . $e->getMessage()
            ];
        }
    }

    /**
     * Parse Ginee response based on endpoint type
     */
    private function parseGineeResponse(array $data, string $type): array
    {
        if (!isset($data['code']) || $data['code'] !== 'SUCCESS') {
            return [];
        }

        $products = [];

        switch ($type) {
            case 'warehouse_inventory':
                if (isset($data['data']) && is_array($data['data'])) {
                    $products = $data['data'];
                } elseif (isset($data['data']['content']) && is_array($data['data']['content'])) {
                    $products = $data['data']['content'];
                }
                break;
                
            case 'master_product':
                if (isset($data['data']['content']) && is_array($data['data']['content'])) {
                    $products = $data['data']['content'];
                }
                break;
                
            default:
                // Try common response patterns
                if (isset($data['data']['content']) && is_array($data['data']['content'])) {
                    $products = $data['data']['content'];
                } elseif (isset($data['data']) && is_array($data['data'])) {
                    $products = $data['data'];
                }
                break;
        }

        return $products;
    }
}