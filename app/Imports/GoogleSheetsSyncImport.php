<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Exception;

class GoogleSheetsSyncImport implements ToCollection, WithHeadingRow
{
    private $importErrors = [];
    private $successCount = 0;
    private $skipCount = 0;
    private $updateCount = 0;
    private $createCount = 0;
    private $groupedProducts = [];

    public function collection(Collection $rows)
    {
        Log::info('Google Sheets sync started', ['total_rows' => $rows->count()]);

        // First pass: Group rows by sku_parent
        foreach ($rows as $index => $row) {
            try {
                $this->processRowForGrouping($row, $index + 2);
            } catch (Exception $e) {
                $this->importErrors[] = [
                    'row' => $index + 2,
                    'error' => $e->getMessage(),
                    'data' => $row->toArray()
                ];
            }
        }

        // Second pass: Create/update products
        $this->createOrUpdateProducts();

        Log::info('Google Sheets sync completed', [
            'success' => $this->successCount,
            'created' => $this->createCount,
            'updated' => $this->updateCount,
            'errors' => count($this->importErrors),
            'skipped' => $this->skipCount
        ]);
    }

    private function processRowForGrouping($row, $rowNumber)
    {
        // Skip empty rows
        if (empty($row['name']) || empty($row['sku_parent'])) {
            $this->skipCount++;
            return;
        }

        $skuParent = trim($row['sku_parent']);
        
        // Initialize product group if not exists
        if (!isset($this->groupedProducts[$skuParent])) {
            $this->groupedProducts[$skuParent] = [
                'product_data' => $this->extractProductData($row),
                'variants' => []
            ];
        }

        // Add variant
        $variant = $this->extractVariantData($row);
        if ($variant) {
            $this->groupedProducts[$skuParent]['variants'][] = $variant;
        }
    }

    private function extractProductData($row): array
    {
        // Parse product_type untuk gender_target
        $genderTarget = $this->parseGenderTarget($row['product_type'] ?? '');
        $productType = $this->parseProductType($row['product_type'] ?? '');
        
        // Combine all images
        $images = [];
        for ($i = 1; $i <= 5; $i++) {
            $imageKey = "images_{$i}";
            if (!empty($row[$imageKey])) {
                $imageUrl = trim($row[$imageKey]);
                if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                    $images[] = $imageUrl;
                }
            }
        }

        // Process dimensions
        $dimensions = [
            'length' => $this->parseFloat($row['lengh'] ?? ''), // Note: typo in sheets
            'width' => $this->parseFloat($row['wide'] ?? ''),
            'height' => $this->parseFloat($row['high'] ?? '')
        ];

        return [
            'name' => trim($row['name']),
            'description' => trim($row['description'] ?? ''),
            'short_description' => Str::limit(trim($row['name']), 100),
            'brand' => trim($row['brand'] ?? ''),
            'sku_parent' => trim($row['sku_parent']),
            'price' => $this->parseFloat($row['price'] ?? 0),
            'sale_price' => !empty($row['sale_price']) ? $this->parseFloat($row['sale_price']) : null,
            'weight' => $this->parseFloat($row['weight'] ?? 500),
            'images' => $images,
            'dimensions' => array_filter($dimensions),
            'gender_target' => $genderTarget,
            'product_type' => $productType,
            'is_featured_sale' => strtolower(trim($row['sale_show'] ?? '')) === 'on',
            'sale_start_date' => $this->parseSaleDate($row['sale_start_date'] ?? ''),
            'sale_end_date' => $this->parseSaleDate($row['sale_end_date'] ?? ''),
        ];
    }

    private function extractVariantData($row): ?array
    {
        $size = trim($row['available_sizes'] ?? '');
        $sku = trim($row['sku'] ?? '');
        $stockQuantity = (int) ($row['stock_quantity'] ?? 0);

        if (empty($size) || empty($sku)) {
            return null;
        }

        return [
            'size' => $size,
            'sku' => $sku,
            'stock_quantity' => $stockQuantity,
            'price' => $this->parseFloat($row['price'] ?? 0),
            'sale_price' => !empty($row['sale_price']) ? $this->parseFloat($row['sale_price']) : null,
        ];
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
                $genderTarget = ['mens', 'womens']; // Unisex = both
            }
        }

        return array_unique($genderTarget);
    }

    private function parseProductType(string $productTypeString): string
    {
        $types = explode(',', strtolower($productTypeString));
        
        foreach ($types as $type) {
            $type = trim($type);
            
            if (str_contains($type, 'apparel') || str_contains($type, 'pakaian') || str_contains($type, 'baju')) {
                return 'apparel';
            } elseif (str_contains($type, 'lifestyle') || str_contains($type, 'casual')) {
                return 'lifestyle_casual';
            } elseif (str_contains($type, 'running')) {
                return 'running';
            } elseif (str_contains($type, 'basketball')) {
                return 'basketball';
            }
        }

        return 'lifestyle_casual'; // Default
    }

    private function createOrUpdateProducts(): void
    {
        foreach ($this->groupedProducts as $skuParent => $productGroup) {
            try {
                $productData = $productGroup['product_data'];
                $variants = $productGroup['variants'];

                // Check if product exists
                $existingProduct = Product::where('sku', $skuParent)->first();

                // Calculate total stock and available sizes
                $totalStock = array_sum(array_column($variants, 'stock_quantity'));
                $availableSizes = array_unique(array_column($variants, 'size'));

                // Find or create category
                $category = $this->findOrCreateCategory($productData['product_type']);

                $finalProductData = [
                    'name' => $productData['name'],
                    'slug' => $existingProduct ? $existingProduct->slug : Product::generateUniqueSlug($productData['name']),
                    'description' => $productData['description'],
                    'short_description' => $productData['short_description'],
                    'brand' => $productData['brand'],
                    'sku' => $productData['sku_parent'],
                    'price' => $productData['price'],
                    'sale_price' => $productData['sale_price'],
                    'stock_quantity' => $totalStock,
                    'weight' => $productData['weight'],
                    'images' => $productData['images'],
                    'dimensions' => $productData['dimensions'],
                    'category_id' => $category->id,
                    'gender_target' => $productData['gender_target'],
                    'product_type' => $productData['product_type'],
                    'available_sizes' => $availableSizes,
                    'is_active' => true,
                    'is_featured' => false,
                    'is_featured_sale' => $productData['is_featured_sale'],
                    'sale_start_date' => $productData['sale_start_date'],
                    'sale_end_date' => $productData['sale_end_date'],
                    'published_at' => now(),
                ];

                if ($existingProduct) {
                    $existingProduct->update($finalProductData);
                    $this->updateCount++;
                    Log::info('Updated product', ['sku' => $skuParent, 'name' => $productData['name']]);
                } else {
                    Product::create($finalProductData);
                    $this->createCount++;
                    Log::info('Created product', ['sku' => $skuParent, 'name' => $productData['name']]);
                }

                $this->successCount++;

            } catch (Exception $e) {
                $this->importErrors[] = [
                    'sku_parent' => $skuParent,
                    'error' => $e->getMessage()
                ];

                Log::error('Error creating/updating product', [
                    'sku_parent' => $skuParent,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    private function findOrCreateCategory(string $productType): Category
    {
        $categoryName = match($productType) {
            'apparel' => 'Apparel',
            'lifestyle_casual' => 'Lifestyle',
            'running' => 'Running',
            'basketball' => 'Basketball',
            'sneakers' => 'Sneakers',
            default => 'Lifestyle'
        };

        return Category::firstOrCreate(
            ['name' => $categoryName],
            [
                'slug' => Str::slug($categoryName),
                'description' => $categoryName . ' products',
                'is_active' => true
            ]
        );
    }

    private function parseFloat($value): float
    {
        if (empty($value)) return 0.0;
        
        $cleaned = preg_replace('/[^0-9.]/', '', $value);
        return (float) $cleaned;
    }

    private function parseSaleDate($dateString): ?string
    {
        if (empty($dateString) || 
            $dateString === 'dd/mm/yyyy,00:00:00,PM' || 
            $dateString === 'dd/mm/yyyy,00:00:00,AM') {
            return null;
        }

        try {
            $date = \Carbon\Carbon::parse($dateString);
            return $date->format('Y-m-d');
        } catch (Exception $e) {
            Log::warning('Failed to parse sale date', ['date' => $dateString]);
            return null;
        }
    }

    // Getter methods
    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    public function getCreateCount(): int
    {
        return $this->createCount;
    }

    public function getUpdateCount(): int
    {
        return $this->updateCount;
    }

    public function getSkipCount(): int
    {
        return $this->skipCount;
    }

    public function getErrors(): array
    {
        return $this->importErrors;
    }

    public function hasErrors(): bool
    {
        return count($this->importErrors) > 0;
    }
}