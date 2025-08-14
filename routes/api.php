<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductVariantController;
use App\Http\Controllers\Frontend\ProductController;
use App\Http\Controllers\Frontend\CartController;
use App\Http\Controllers\Frontend\WishlistController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// ⭐ CORRECTED: Product Variant API Routes - Handle SKU patterns
Route::prefix('products')->name('api.products.')->group(function () {
    // Get variant by SKU parent and size
    Route::post('/variant-by-size', [ProductVariantController::class, 'getVariantBySize'])
        ->name('variant-by-size');
    
    // Get all variants by SKU parent
    Route::get('/variants/{sku_parent}', [ProductVariantController::class, 'getVariantsBySkuParent'])
        ->name('variants-by-sku-parent');
        
    // ⭐ NEW: Debug endpoint for SKU patterns
    Route::get('/debug-sku-patterns', [ProductVariantController::class, 'debugSkuPatterns'])
        ->name('debug-sku-patterns');
        
    // Additional product API endpoints
    Route::get('/search', [ProductController::class, 'quickSearch'])->name('search');
    Route::get('/{id}/variants', [ProductController::class, 'getVariants'])->name('variants');
    Route::get('/{id}/stock', [ProductController::class, 'checkStock'])->name('stock');
});

// ⭐ ENHANCED: Cart API Routes
Route::prefix('cart')->name('api.cart.')->group(function () {
    Route::get('/count', [CartController::class, 'getCartCount'])->name('count');
    Route::get('/data', [CartController::class, 'getCartData'])->name('data');
    Route::post('/sync', [CartController::class, 'syncCart'])->name('sync');
});

// ⭐ ENHANCED: Wishlist API Routes (Authenticated)
Route::middleware('auth')->prefix('wishlist')->name('api.wishlist.')->group(function () {
    Route::get('/count', [WishlistController::class, 'getCount'])->name('count');
    Route::post('/toggle/{productId}', [WishlistController::class, 'toggle'])->name('toggle');
    Route::post('/check', [WishlistController::class, 'checkProducts'])->name('check');
});

// ⭐ NEW: Debug endpoints untuk testing
Route::prefix('debug')->name('api.debug.')->group(function () {
    // Test endpoint untuk debugging
    Route::get('/test', function () {
        return response()->json([
            'status' => 'success',
            'message' => 'API is working',
            'timestamp' => now(),
            'environment' => app()->environment()
        ]);
    })->name('test');
    
    // Debug product data
    Route::get('/products/{sku_parent}', function ($sku_parent) {
        $products = \App\Models\Product::where('sku_parent', $sku_parent)
            ->where('is_active', true)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'sku_parent' => $product->sku_parent,
                    'available_sizes' => $product->available_sizes,
                    'stock_quantity' => $product->stock_quantity,
                    'price' => $product->price,
                    'sale_price' => $product->sale_price
                ];
            });
            
        return response()->json([
            'sku_parent' => $sku_parent,
            'total_variants' => $products->count(),
            'variants' => $products
        ]);
    })->name('products');
});