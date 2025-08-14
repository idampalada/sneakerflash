<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductVariantController extends Controller
{
    /**
     * ⭐ CORRECTED: Get product variant by SKU parent and size
     */
    public function getVariantBySize(Request $request)
    {
        try {
            $request->validate([
                'sku_parent' => 'required|string',
                'size' => 'required|string'
            ]);

            $skuParent = $request->sku_parent;
            $size = $request->size;

            Log::info('Looking for variant by sku_parent and size', [
                'sku_parent' => $skuParent,
                'size' => $size
            ]);

            // ⭐ CORRECTED: Find variant by sku_parent and size
            // First try to find by available_sizes field
            $variant = Product::query()
                ->where('sku_parent', $skuParent)
                ->where('available_sizes', $size)
                ->where('is_active', true)
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->first();

            // ⭐ NEW: If not found, try to find by SKU pattern (SBKVN0A3HZFCAR-S)
            if (!$variant) {
                $variant = Product::query()
                    ->where('sku_parent', $skuParent)
                    ->where('sku', 'like', '%-' . strtoupper($size))
                    ->where('is_active', true)
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now())
                    ->first();
            }

            // ⭐ FALLBACK: If still not found, try lowercase
            if (!$variant) {
                $variant = Product::query()
                    ->where('sku_parent', $skuParent)
                    ->where('sku', 'like', '%-' . strtolower($size))
                    ->where('is_active', true)
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now())
                    ->first();
            }

            if (!$variant) {
                Log::warning('Variant not found', [
                    'sku_parent' => $skuParent,
                    'size' => $size,
                    'attempted_patterns' => [
                        'available_sizes' => $size,
                        'sku_pattern_upper' => '%-' . strtoupper($size),
                        'sku_pattern_lower' => '%-' . strtolower($size)
                    ]
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Product variant not found for size: ' . $size
                ], 404);
            }

            // ⭐ EXTRACT: Get size from variant
            $variantSize = $variant->available_sizes ?: $this->extractSizeFromSku($variant->sku, $skuParent);

            Log::info('Variant found', [
                'product_id' => $variant->id,
                'sku' => $variant->sku,
                'available_sizes' => $variant->available_sizes,
                'extracted_size' => $variantSize,
                'stock' => $variant->stock_quantity
            ]);

            return response()->json([
                'success' => true,
                'product' => [
                    'id' => $variant->id,
                    'name' => $variant->name,
                    'price' => $variant->sale_price ?: $variant->price,
                    'original_price' => $variant->price,
                    'stock' => $variant->stock_quantity ?? 0,
                    'size' => $variantSize ?: 'One Size',
                    'sku' => $variant->sku,
                    'sku_parent' => $variant->sku_parent,
                    'images' => $variant->images ?? []
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validation error in getVariantBySize', [
                'errors' => $e->errors()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid request data',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error in getVariantBySize', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong. Please try again.'
            ], 500);
        }
    }

    /**
     * ⭐ CORRECTED: Get all variants for a product by SKU parent
     */
    public function getVariantsBySkuParent(Request $request, $skuParent)
    {
        try {
            Log::info('Getting all variants for sku_parent', ['sku_parent' => $skuParent]);

            $variants = Product::query()
                ->where('sku_parent', $skuParent)
                ->where('is_active', true)
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->get();

            if ($variants->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No variants found for this product'
                ], 404);
            }

            $variantData = $variants->map(function ($variant) use ($skuParent) {
                $size = $variant->available_sizes ?: $this->extractSizeFromSku($variant->sku, $skuParent);
                
                return [
                    'id' => $variant->id,
                    'name' => $variant->name,
                    'price' => $variant->sale_price ?: $variant->price,
                    'original_price' => $variant->price,
                    'stock' => $variant->stock_quantity ?? 0,
                    'size' => $size ?: 'One Size',
                    'sku' => $variant->sku,
                    'available' => ($variant->stock_quantity ?? 0) > 0
                ];
            })->sortBy('size');

            return response()->json([
                'success' => true,
                'variants' => $variantData->values(),
                'total_variants' => $variants->count(),
                'total_stock' => $variants->sum('stock_quantity')
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getVariantsBySkuParent', [
                'sku_parent' => $skuParent,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong. Please try again.'
            ], 500);
        }
    }

    /**
     * ⭐ NEW: Extract size from SKU pattern
     * Pattern: SBKVN0A3HZFCAR-S -> extract "S"
     * Pattern: SBKVN0A3HZFCAR-M -> extract "M"
     */
    private function extractSizeFromSku($sku, $skuParent = null)
    {
        if (empty($sku)) {
            return null;
        }
        
        // Find the size part after the last dash
        $parts = explode('-', $sku);
        if (count($parts) >= 2) {
            $sizePart = end($parts);
            
            // Common clothing sizes
            if (in_array(strtoupper($sizePart), ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'])) {
                return strtoupper($sizePart);
            }
            
            // Shoe sizes (numeric with optional .5)
            if (is_numeric($sizePart) || preg_match('/^\d+\.?5?$/', $sizePart)) {
                return $sizePart;
            }
            
            // Other patterns
            if (preg_match('/^[A-Z0-9]+$/', $sizePart) && strlen($sizePart) <= 4) {
                return $sizePart;
            }
        }
        
        return null;
    }

    /**
     * ⭐ NEW: Test endpoint to debug SKU patterns
     */
    public function debugSkuPatterns(Request $request)
    {
        try {
            $skuParent = $request->get('sku_parent');
            
            if (!$skuParent) {
                return response()->json([
                    'success' => false,
                    'message' => 'sku_parent parameter is required'
                ], 400);
            }

            $variants = Product::query()
                ->where('sku_parent', $skuParent)
                ->where('is_active', true)
                ->get();

            $debugData = $variants->map(function ($variant) use ($skuParent) {
                return [
                    'id' => $variant->id,
                    'name' => $variant->name,
                    'sku' => $variant->sku,
                    'sku_parent' => $variant->sku_parent,
                    'available_sizes' => $variant->available_sizes,
                    'extracted_size' => $this->extractSizeFromSku($variant->sku, $skuParent),
                    'stock' => $variant->stock_quantity
                ];
            });

            return response()->json([
                'success' => true,
                'sku_parent' => $skuParent,
                'total_variants' => $variants->count(),
                'variants' => $debugData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Debug failed: ' . $e->getMessage()
            ], 500);
        }
    }
}