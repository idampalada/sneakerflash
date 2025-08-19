<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Api\ProductVariantController;
use App\Http\Controllers\Frontend\ProductController;
use App\Http\Controllers\Frontend\CartController;
use App\Http\Controllers\Frontend\WishlistController;
use App\Http\Controllers\Frontend\GineeWebhookController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| File ini diprefix otomatis dengan /api dan berada di middleware group "api".
| Tidak ada CSRF di sini, sehingga cocok untuk webhook Ginee.
*/

// Authenticated user (Sanctum)
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/* ============================================================
| Products API
|============================================================ */
Route::prefix('products')->name('api.products.')->group(function () {
    // SKU/Variant handling
    Route::post('/variant-by-size', [ProductVariantController::class, 'getVariantBySize'])
        ->name('variant-by-size');

    Route::get('/variants/{sku_parent}', [ProductVariantController::class, 'getVariantsBySkuParent'])
        ->name('variants-by-sku-parent');

    Route::get('/debug-sku-patterns', [ProductVariantController::class, 'debugSkuPatterns'])
        ->name('debug-sku-patterns');

    // Additional product API endpoints
    Route::get('/search', [ProductController::class, 'quickSearch'])->name('search');
    Route::get('/{id}/variants', [ProductController::class, 'getVariants'])->name('variants');
    Route::get('/{id}/stock', [ProductController::class, 'checkStock'])->name('stock');
});

/* ============================================================
| Cart API
|============================================================ */
Route::prefix('cart')->name('api.cart.')->group(function () {
    Route::get('/count', [CartController::class, 'getCartCount'])->name('count');
    Route::get('/data',  [CartController::class, 'getCartData'])->name('data');
    Route::post('/sync', [CartController::class, 'syncCart'])->name('sync');
});

/* ============================================================
| Wishlist API (Authenticated)
|============================================================ */
Route::middleware('auth')->prefix('wishlist')->name('api.wishlist.')->group(function () {
    Route::get('/count', [WishlistController::class, 'getCount'])->name('count');
    Route::post('/toggle/{productId}', [WishlistController::class, 'toggle'])->name('toggle');
    Route::post('/check', [WishlistController::class, 'checkProducts'])->name('check');
});

/* ============================================================
| Debug API
|============================================================ */
Route::prefix('debug')->name('api.debug.')->group(function () {
    Route::get('/test', function () {
        return response()->json([
            'status'      => 'success',
            'message'     => 'API is working',
            'timestamp'   => now(),
            'environment' => app()->environment(),
        ]);
    })->name('test');

    Route::get('/products/{sku_parent}', function ($sku_parent) {
        $products = \App\Models\Product::where('sku_parent', $sku_parent)
            ->where('is_active', true)
            ->get()
            ->map(function ($product) {
                return [
                    'id'                 => $product->id,
                    'name'               => $product->name,
                    'sku'                => $product->sku,
                    'sku_parent'         => $product->sku_parent,
                    'available_sizes'    => $product->available_sizes,
                    'stock_quantity'     => $product->stock_quantity,
                    'price'              => $product->price,
                    'sale_price'         => $product->sale_price,
                ];
            });

        return response()->json([
            'sku_parent'     => $sku_parent,
            'total_variants' => $products->count(),
            'variants'       => $products,
        ]);
    })->name('products');
});

/* ============================================================
| Ginee Webhooks (NO CSRF needed in api.php)
| URL yang didaftarkan ke Ginee:
| - https://domainmu.id/api/webhooks/ginee/orders
| - https://domainmu.id/api/webhooks/ginee/master-products
|============================================================ */
Route::prefix('webhooks/ginee')->name('webhooks.ginee.')->group(function () {
    Route::post('/orders',          [GineeWebhookController::class, 'orders'])->name('orders');
    Route::post('/master-products', [GineeWebhookController::class, 'masterProducts'])->name('master_products');
});
