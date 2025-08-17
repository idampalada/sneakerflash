<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Exception;

class GineeStockSyncService
{
    private $gineeApiUrl;
    private $gineeAccessKey;
    private $gineeSecretKey;
    private $googleSheetsService;

    public function __construct()
    {
        $this->gineeApiUrl = config('services.ginee.api_url') ?: env('GINEE_API_URL', 'https://api.ginee.com');
        $this->gineeAccessKey = config('services.ginee.access_key') ?: env('GINEE_ACCESS_KEY');
        $this->gineeSecretKey = config('services.ginee.secret_key') ?: env('GINEE_SECRET_KEY');
        $this->googleSheetsService = new GoogleSheetsSync();

        Log::info('GineeStockSyncService initialized', [
            'api_url' => $this->gineeApiUrl,
            'access_key_set' => !empty($this->gineeAccessKey),
            'secret_key_set' => !empty($this->gineeSecretKey),
            'country' => config('services.ginee.country', 'ID')
        ]);
    }

    /**
     * Preview stock sync - menampilkan data sebelum sync
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
            
            // Buat mapping SKU dari Ginee dengan struktur yang benar dan debug
            $gineeMskuMap = [];
            
            Log::info('Preview: Processing Ginee products', [
                'total_products' => count($gineeProducts['products'])
            ]);
            
            foreach ($gineeProducts['products'] as $index => $product) {
                // Log sample for debugging
                if ($index < 2) {
                    Log::info("Preview sample product #{$index}", [
                        'keys' => array_keys($product),
                        'has_variations' => isset($product['variationBriefs']),
                        'name' => $product['name'] ?? 'no name'
                    ]);
                }
                
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
                }
                // Try alternative structure
                elseif (isset($product['sku']) || isset($product['masterSku']) || isset($product['code'])) {
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
                // Fallback
                else {
                    $msku = $product['productId'] ?? $product['id'] ?? '';
                    if (!empty($msku)) {
                        $gineeMskuMap[$msku] = [
                            'msku' => $msku,
                            'stock' => $product['stock'] ?? 0,
                            'product_name' => $product['name'] ?? ''
                        ];
                    }
                }
            }
            
            Log::info('Preview: MSKU mapping completed', [
                'total_msku_mapped' => count($gineeMskuMap),
                'sample_msku' => array_slice(array_keys($gineeMskuMap), 0, 3)
            ]);

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
                'total_ginee_products' => count($gineeProducts['products'])
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
     * Sync stock dengan kombinasi Ginee + Spreadsheet
     */
    public function syncStockWithSpreadsheet(): array
    {
        try {
            Log::info('Starting Ginee stock sync with spreadsheet integration');

            // Step 1: Ambil data dari Ginee
            $gineeResult = $this->fetchGineeProducts();
            if (!$gineeResult['success']) {
                return $gineeResult;
            }

            // Step 2: Ambil data dari spreadsheet (tapi abaikan kolom stock)
            $spreadsheetData = $this->fetchGoogleSheetsData();
            if (empty($spreadsheetData)) {
                return [
                    'success' => false,
                    'message' => 'Failed to fetch spreadsheet data: No data found in Google Sheets'
                ];
            }

            // Step 3: Buat mapping data dari Ginee dengan debug logging
            // Berdasarkan dokumentasi, structure produk Ginee:
            // { "productId": "...", "variationBriefs": [{ "sku": "...", "stock": {...} }] }
            $gineeMskuMap = [];
            
            Log::info('Processing Ginee products for MSKU mapping', [
                'total_products' => count($gineeResult['products']),
                'sample_product_structure' => isset($gineeResult['products'][0]) ? array_keys($gineeResult['products'][0]) : []
            ]);
            
            foreach ($gineeResult['products'] as $index => $product) {
                // Log first few products for debugging
                if ($index < 3) {
                    Log::info("Sample Ginee product #{$index}", [
                        'product_keys' => array_keys($product),
                        'has_variations' => isset($product['variationBriefs']),
                        'variation_count' => isset($product['variationBriefs']) ? count($product['variationBriefs']) : 0,
                        'sample_product' => $product
                    ]);
                }
                
                // Check if product has variations (most common structure)
                if (isset($product['variationBriefs']) && is_array($product['variationBriefs'])) {
                    foreach ($product['variationBriefs'] as $variation) {
                        $msku = $variation['sku'] ?? '';
                        if (!empty($msku)) {
                            // Extract stock dari struktur yang kompleks
                            $stockInfo = $variation['stock'] ?? [];
                            $availableStock = $stockInfo['availableStock'] ?? 0;
                            
                            $gineeMskuMap[$msku] = [
                                'msku' => $msku,
                                'stock' => $availableStock,
                                'product_name' => $product['name'] ?? '',
                                'product_id' => $product['productId'] ?? '',
                                'barcode' => $variation['barcode'] ?? '',
                                'warehouse_stock' => $stockInfo['warehouseStock'] ?? 0,
                                'spare_stock' => $stockInfo['spareStock'] ?? 0,
                                'safety_stock' => $stockInfo['safetyStock'] ?? 0
                            ];
                        }
                    }
                }
                // Check for direct SKU fields (alternative structure)
                elseif (isset($product['sku']) || isset($product['masterSku']) || isset($product['code'])) {
                    $msku = $product['sku'] ?? $product['masterSku'] ?? $product['code'] ?? $product['productId'] ?? '';
                    if (!empty($msku)) {
                        // Try different stock field names
                        $stock = $product['stock'] ?? 
                                $product['availableStock'] ?? 
                                $product['quantity'] ?? 
                                $product['stockQuantity'] ?? 0;
                                
                        $gineeMskuMap[$msku] = [
                            'msku' => $msku,
                            'stock' => $stock,
                            'product_name' => $product['name'] ?? '',
                            'product_id' => $product['productId'] ?? $product['id'] ?? ''
                        ];
                    }
                }
                // Fallback: use productId as MSKU
                else {
                    $msku = $product['productId'] ?? $product['id'] ?? '';
                    if (!empty($msku)) {
                        $gineeMskuMap[$msku] = [
                            'msku' => $msku,
                            'stock' => $product['stock'] ?? 0,
                            'product_name' => $product['name'] ?? '',
                            'product_id' => $msku
                        ];
                    }
                }
            }
            
            Log::info('MSKU mapping completed', [
                'total_msku_mapped' => count($gineeMskuMap),
                'sample_msku_keys' => array_slice(array_keys($gineeMskuMap), 0, 5),
                'sample_msku_data' => array_slice($gineeMskuMap, 0, 2)
            ]);

            // Step 4: Process setiap row dari spreadsheet
            $stats = [
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'warnings' => 0,
                'errors' => 0
            ];

            $errors = [];

            foreach ($spreadsheetData as $index => $row) {
                try {
                    $result = $this->processRowWithGineeStock($row, $gineeMskuMap, $index + 2);
                    $stats[$result['action']]++;
                    
                    if ($result['action'] === 'warning') {
                        $stats['warnings']++;
                    }

                } catch (Exception $e) {
                    $stats['errors']++;
                    $errors[] = [
                        'row' => $index + 2,
                        'sku' => $row['sku'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ];
                    
                    Log::error('Row processing failed', [
                        'row' => $index + 2,
                        'error' => $e->getMessage(),
                        'data' => $row
                    ]);
                }
            }

            Log::info('Ginee stock sync completed', [
                'stats' => $stats,
                'total_errors' => count($errors)
            ]);

            return [
                'success' => true,
                'stats' => $stats,
                'errors' => $errors,
                'message' => 'Ginee stock sync completed successfully'
            ];

        } catch (Exception $e) {
            Log::error('Ginee stock sync failed', [
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
     * Process single row dengan stock dari Ginee
     */
    private function processRowWithGineeStock(array $row, array $gineeMskuMap, int $rowNumber): array
    {
        $sku = trim($row['sku'] ?? '');
        
        if (empty($sku)) {
            return ['action' => 'skipped', 'reason' => 'Empty SKU'];
        }

        // Ambil stock dari Ginee berdasarkan MSKU
        $gineeStock = 0;
        $gineeData = null;
        
        if (isset($gineeMskuMap[$sku])) {
            $gineeData = $gineeMskuMap[$sku];
            $gineeStock = $gineeData['stock'] ?? 0;
        } else {
            Log::warning('SKU not found in Ginee', [
                'sku' => $sku,
                'row' => $rowNumber
            ]);
        }

        // Extract product data dari spreadsheet (tanpa stock)
        $productData = $this->extractProductDataFromRow($row);
        
        // Override stock dengan data dari Ginee
        $productData['stock_quantity'] = $gineeStock;
        
        // Tambahkan metadata Ginee
        $productData['ginee_msku'] = $sku; // Store MSKU untuk tracking
        $productData['ginee_sync_status'] = isset($gineeMskuMap[$sku]) ? 'synced' : 'not_found';
        $productData['ginee_last_synced_at'] = now();

        // Pastikan ada category_id
        if (empty($productData['category_id'])) {
            $category = $this->findOrCreateCategory($productData['product_type'] ?? 'general');
            $productData['category_id'] = $category->id;
        }

        // Cari produk existing
        $existingProduct = Product::where('sku', $sku)->first();

        if ($existingProduct) {
            // Update produk existing
            $existingProduct->update($productData);
            
            Log::info('Product updated with Ginee stock', [
                'sku' => $sku,
                'old_stock' => $existingProduct->stock_quantity,
                'new_stock' => $gineeStock,
                'ginee_found' => isset($gineeMskuMap[$sku])
            ]);

            return [
                'action' => 'updated',
                'sku' => $sku,
                'stock_updated' => $gineeStock
            ];
        } else {
            // Buat produk baru
            $productData['slug'] = $this->generateUniqueSlug($productData['name']);
            $product = Product::create($productData);

            Log::info('New product created with Ginee stock', [
                'sku' => $sku,
                'stock' => $gineeStock,
                'ginee_found' => isset($gineeMskuMap[$sku])
            ]);

            return [
                'action' => 'created',
                'sku' => $sku,
                'stock_set' => $gineeStock
            ];
        }
    }

    /**
     * Fetch products dari Ginee API
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

            Log::info('Fetching products from Ginee API', [
                'api_url' => $this->gineeApiUrl,
                'access_key' => substr($this->gineeAccessKey, 0, 8) . '...'
            ]);

            // Ginee API menggunakan format authentication khusus
            $requestUri = '/openapi/product/master/v1/list'; // ✅ FIXED: Correct endpoint
            $httpMethod = 'POST';
            $paramJson = json_encode([
                'page' => 0,
                'size' => 100, // Ambil banyak produk sekaligus
                'order' => 'ASC',
                'sort' => 'name' // Sort by name
            ]);
            
            // Build signature sesuai dokumentasi Ginee
            $signatureString = $httpMethod . '$' . $requestUri . '$';
            $signature = base64_encode(hash_hmac('sha256', $signatureString, $this->gineeSecretKey, true));
            $authorization = $this->gineeAccessKey . ':' . $signature;

            Log::info('Ginee API signature built', [
                'method' => $httpMethod,
                'uri' => $requestUri,
                'signature_string' => $signatureString,
                'authorization_format' => substr($authorization, 0, 20) . '...'
            ]);

            // Call Ginee API dengan format yang benar
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => $authorization,
                    'Content-Type' => 'application/json',
                    'X-Advai-Country' => 'ID' // Required header untuk Indonesia
                ])
                ->post($this->gineeApiUrl . $requestUri, json_decode($paramJson, true));

            Log::info('Ginee API response received', [
                'status' => $response->status(),
                'success' => $response->successful(),
                'response_size' => strlen($response->body())
            ]);

            if (!$response->successful()) {
                Log::error('Ginee API request failed', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'headers' => $response->headers()
                ]);

                return [
                    'success' => false,
                    'message' => 'Ginee API request failed: ' . $response->status() . ' - ' . $response->body()
                ];
            }

            $data = $response->json();
            
            Log::info('Ginee API raw response', [
                'response_body' => substr($response->body(), 0, 500) . '...',
                'response_json_structure' => array_keys($data ?? []),
                'has_data_key' => isset($data['data']),
                'has_code_key' => isset($data['code']),
                'response_code' => $data['code'] ?? 'not_set'
            ]);
            
            // Ginee API response structure berdasarkan dokumentasi:
            // { "code": "SUCCESS", "message": "成功", "data": { "content": [...] } }
            $products = [];
            
            if (isset($data['code']) && $data['code'] === 'SUCCESS') {
                if (isset($data['data']['content']) && is_array($data['data']['content'])) {
                    $products = $data['data']['content'];
                } elseif (isset($data['data']) && is_array($data['data'])) {
                    $products = $data['data'];
                }
            } else {
                $errorMsg = $data['message'] ?? 'Unknown error';
                Log::error('Ginee API returned error', [
                    'code' => $data['code'] ?? 'unknown',
                    'message' => $errorMsg,
                    'full_response' => $data
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Ginee API error: ' . $errorMsg
                ];
            }
            
            if (empty($products)) {
                Log::warning('No products found in Ginee response', [
                    'response_structure' => array_keys($data ?? [])
                ]);
                
                return [
                    'success' => true,
                    'products' => [],
                    'total' => 0,
                    'message' => 'No products found in Ginee'
                ];
            }
            
            Log::info('Successfully fetched products from Ginee', [
                'total_products' => count($products),
                'response_status' => $data['status'] ?? 'unknown'
            ]);

            $result = [
                'success' => true,
                'products' => $products,
                'total' => count($products)
            ];

            // Cache result untuk 10 menit
            Cache::put($cacheKey, $result, 600);

            return $result;

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
     * Extract product data dari row spreadsheet (tanpa stock)
     */
    private function extractProductDataFromRow(array $row): array
    {
        // Parse product type dan gender target
        $productTypeString = trim($row['product_type'] ?? '');
        $productType = $this->parseProductType($productTypeString);
        $genderTarget = $this->parseGenderTarget($productTypeString);

        return [
            'name' => trim($row['name'] ?? ''),
            'brand' => trim($row['brand'] ?? ''),
            'sku_parent' => trim($row['sku_parent'] ?? ''),
            'sku' => trim($row['sku'] ?? ''),
            'price' => $this->parseFloat($row['price'] ?? 0),
            'sale_price' => !empty($row['sale_price']) ? $this->parseFloat($row['sale_price']) : null,
            'weight' => $this->parseFloat($row['weight'] ?? 0),
            'length' => $this->parseFloat($row['length'] ?? 0),
            'width' => $this->parseFloat($row['width'] ?? 0),
            'height' => $this->parseFloat($row['height'] ?? 0),
            'available_sizes' => $this->parseAvailableSizes($row['available_sizes'] ?? ''),
            'is_featured' => $this->parseBoolean($row['sale_show'] ?? false),
            'sale_start_date' => $this->parseDate($row['sale_start_date'] ?? null),
            'sale_end_date' => $this->parseDate($row['sale_end_date'] ?? null),
            'images' => $this->parseImages($row),
            'description' => trim($row['description'] ?? ''),
            'product_type' => $productType,
            'gender_target' => $genderTarget,
            // Stock akan di-override dengan data dari Ginee
            // category_id akan diset setelah ini
        ];
    }

    /**
     * Helper methods
     */
    private function findOrCreateCategory(string $productType): Category
    {
        // Cari category existing berdasarkan name
        $category = Category::where('name', $productType)->first();
        
        if (!$category) {
            // Buat category baru jika tidak ada
            $category = Category::create([
                'name' => $productType,
                'slug' => Str::slug($productType),
                'is_active' => true,
                'description' => "Auto-created category for {$productType}"
            ]);
            
            Log::info('Created new category', ['name' => $productType, 'id' => $category->id]);
        }
        
        return $category;
    }

    private function parseFloat($value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }
        
        $cleanValue = preg_replace('/[^0-9.,]/', '', (string) $value);
        $cleanValue = str_replace(',', '.', $cleanValue);
        
        return (float) $cleanValue;
    }

    private function parseBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        
        if (is_string($value)) {
            $value = strtolower(trim($value));
            return in_array($value, ['true', '1', 'yes', 'ya', 'active', 'aktif']);
        }
        
        return (bool) $value;
    }

    private function parseDate($dateString)
    {
        if (empty($dateString)) {
            return null;
        }
        
        try {
            return \Carbon\Carbon::parse($dateString)->format('Y-m-d');
        } catch (Exception $e) {
            Log::warning('Failed to parse date', ['date' => $dateString]);
            return null;
        }
    }

    private function parseAvailableSizes($sizesString): array
    {
        if (empty($sizesString)) {
            return [];
        }
        
        if (is_array($sizesString)) {
            return $sizesString;
        }
        
        return array_filter(array_map('trim', explode(',', $sizesString)));
    }

    private function parseImages(array $row): array
    {
        $images = [];
        
        for ($i = 1; $i <= 5; $i++) {
            $imageUrl = trim($row["images_{$i}"] ?? '');
            if (!empty($imageUrl)) {
                $images[] = $imageUrl;
            }
        }
        
        return $images;
    }

    private function parseProductType(string $productTypeString): string
    {
        $types = explode(',', strtolower($productTypeString));
        
        foreach ($types as $type) {
            $type = trim($type);
            
            if (str_contains($type, 'apparel') || str_contains($type, 'pakaian')) {
                return 'apparel';
            } elseif (str_contains($type, 'lifestyle')) {
                return 'lifestyle_casual';
            } elseif (str_contains($type, 'running')) {
                return 'running';
            } elseif (str_contains($type, 'basketball')) {
                return 'basketball';
            }
        }
        
        return 'lifestyle_casual';
    }

    private function parseGenderTarget(string $productTypeString): array
    {
        $types = explode(',', strtolower($productTypeString));
        $genderTarget = [];
        
        foreach ($types as $type) {
            $type = trim($type);
            
            if (str_contains($type, 'mens') || str_contains($type, 'men') || str_contains($type, 'pria')) {
                $genderTarget[] = 'mens';
            } elseif (str_contains($type, 'womens') || str_contains($type, 'women') || str_contains($type, 'wanita')) {
                $genderTarget[] = 'womens';
            } elseif (str_contains($type, 'kids') || str_contains($type, 'anak')) {
                $genderTarget[] = 'kids';
            } elseif (str_contains($type, 'unisex')) {
                $genderTarget = ['mens', 'womens'];
            }
        }
        
        return array_unique($genderTarget);
    }

    private function generateUniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (Product::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Fetch data from Google Sheets (wrapper method)
     */
    private function fetchGoogleSheetsData(): array
    {
        try {
            // Use the public previewData method to get spreadsheet data
            $result = $this->googleSheetsService->previewData(1000); // Get large preview
            
            if ($result['success']) {
                // Convert preview data back to raw format for processing
                $rawData = [];
                foreach ($result['data'] as $row) {
                    $rawData[] = [
                        'name' => $row['name'],
                        'brand' => $row['brand'], 
                        'sku_parent' => $row['sku_parent'],
                        'sku' => $row['sku'],
                        'price' => $row['price'],
                        'stock_quantity' => $row['stock'],
                        'available_sizes' => $row['size'],
                        'product_type' => $row['product_type_raw'] ?? $row['product_type'],
                        'sale_show' => $row['is_featured'] ?? false,
                        'sale_price' => null, // Not in preview, will be null
                        'weight' => 0,
                        'length' => 0,
                        'width' => 0,
                        'height' => 0,
                        'description' => '',
                        'sale_start_date' => null,
                        'sale_end_date' => null,
                        'images_1' => '',
                        'images_2' => '',
                        'images_3' => '',
                        'images_4' => '',
                        'images_5' => '',
                    ];
                }
                return $rawData;
            } else {
                Log::error('Failed to get Google Sheets preview data', [
                    'error' => $result['message']
                ]);
                return [];
            }
        } catch (Exception $e) {
            Log::error('Failed to fetch Google Sheets data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }
}