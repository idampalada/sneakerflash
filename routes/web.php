<?php

use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\ProductController;
use App\Http\Controllers\Frontend\CategoryController;
use App\Http\Controllers\Frontend\CartController;
use App\Http\Controllers\Frontend\WishlistController;
use App\Http\Controllers\Frontend\CheckoutController;
use App\Http\Controllers\Frontend\OrderController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// =====================================
// PUBLIC ROUTES
// =====================================

// Homepage
Route::get('/', [HomeController::class, 'index'])->name('home');

// Products - ROUTE UTAMA
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show');

// NEW NAVIGATION ROUTES - TAMBAHKAN INI
Route::get('/mens', [ProductController::class, 'mens'])->name('products.mens');
Route::get('/womens', [ProductController::class, 'womens'])->name('products.womens');
Route::get('/kids', [ProductController::class, 'kids'])->name('products.kids');
Route::get('/brand', [ProductController::class, 'brand'])->name('products.brand');
Route::get('/accessories', [ProductController::class, 'accessories'])->name('products.accessories');
Route::get('/sale', [ProductController::class, 'sale'])->name('products.sale');

// Categories
Route::get('/categories/{slug}', [CategoryController::class, 'show'])->name('categories.show');

// Shopping Cart (accessible for all users) - UPDATED VERSION
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::patch('/cart/{id}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{id}', [CartController::class, 'remove'])->name('cart.remove');
Route::delete('/cart', [CartController::class, 'clear'])->name('cart.clear');

// AJAX Cart routes - ADDED
Route::get('/api/cart/count', [CartController::class, 'getCartCount'])->name('cart.count');
Route::get('/api/cart/data', [CartController::class, 'getCartData'])->name('cart.data');
Route::post('/cart/sync', [CartController::class, 'syncCart'])->name('cart.sync');

// =====================================
// AUTHENTICATION ROUTES
// =====================================

// Guest routes (redirect to home if already authenticated)
Route::middleware('guest')->group(function () {
    // Login routes
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
    
    // Register routes
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->name('register.submit');
    
    // Google OAuth routes
    Route::get('/auth/google', [GoogleController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('/auth/google/callback', [GoogleController::class, 'handleGoogleCallback'])->name('auth.google.callback');
});

// Logout route (requires authentication)
Route::post('/logout', [GoogleController::class, 'logout'])->name('logout')->middleware('auth');

// Password reset routes (optional - akan ditambahkan nanti)
Route::get('/password/reset', function() {
    return view('auth.passwords.email');
})->name('password.request');

// =====================================
// CHECKOUT ROUTES (Guest & Authenticated) - FIXED FOR RAJAONGKIR V2
// =====================================

// Main checkout routes
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

// FIXED: Search-based AJAX routes for checkout (RajaOngkir V2)
Route::get('/checkout/search-destinations', [CheckoutController::class, 'searchDestinations'])->name('checkout.search-destinations');
Route::post('/checkout/shipping', [CheckoutController::class, 'calculateShipping'])->name('checkout.shipping');

// LEGACY: Old routes (kept for backward compatibility)
Route::get('/checkout/cities', [CheckoutController::class, 'getCities'])->name('checkout.cities');

// Checkout completion routes
Route::get('/checkout/success/{orderNumber}', [CheckoutController::class, 'success'])->name('checkout.success');
Route::get('/checkout/finish/{orderNumber}', [CheckoutController::class, 'finish'])->name('checkout.finish');
Route::get('/checkout/unfinish', [CheckoutController::class, 'unfinish'])->name('checkout.unfinish');
Route::get('/checkout/error', [CheckoutController::class, 'error'])->name('checkout.error');

// Payment notification (for payment gateways like Midtrans)
Route::post('/checkout/payment/notification', [CheckoutController::class, 'paymentNotification'])->name('checkout.notification');

// =====================================
// AUTHENTICATED USER ROUTES
// =====================================

Route::middleware(['auth'])->group(function () {
    // User Orders
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{orderNumber}', [OrderController::class, 'show'])->name('orders.show');
    
    // =====================================
    // WISHLIST ROUTES - ADDED
    // =====================================
    
    // Wishlist main page
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
    
    // Wishlist management routes - AJAX
    Route::post('/wishlist/toggle/{productId}', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
    Route::delete('/wishlist/remove/{productId}', [WishlistController::class, 'remove'])->name('wishlist.remove');
    Route::delete('/wishlist/clear', [WishlistController::class, 'clear'])->name('wishlist.clear');
    
    // Move wishlist item to cart
    Route::post('/wishlist/move-to-cart/{productId}', [WishlistController::class, 'moveToCart'])->name('wishlist.moveToCart');
    
    // AJAX Wishlist API routes
    Route::get('/wishlist/count', [WishlistController::class, 'getCount'])->name('wishlist.count');
    Route::post('/wishlist/check', [WishlistController::class, 'checkProducts'])->name('wishlist.check');
    
    // User Profile
    Route::get('/profile', function() {
        return view('frontend.profile.index');
    })->name('profile.index');
    
    Route::get('/profile/edit', function() {
        return view('frontend.profile.edit');
    })->name('profile.edit');
    
    // User Account Settings
    Route::patch('/profile', function() {
        // Profile update logic here
        return redirect()->route('profile.index')->with('success', 'Profile updated successfully');
    })->name('profile.update');
});

// =====================================
// SEARCH & FILTER ROUTES
// =====================================

// Advanced search
Route::get('/search', [ProductController::class, 'search'])->name('search');

// Product filters
Route::get('/filter', [ProductController::class, 'filter'])->name('products.filter');

// =====================================
// STATIC PAGES
// =====================================

Route::get('/about', function() {
    return view('frontend.pages.about');
})->name('about');

Route::get('/contact', function() {
    return view('frontend.pages.contact');
})->name('contact');

Route::post('/contact', function() {
    // Contact form submission logic
    return back()->with('success', 'Message sent successfully!');
})->name('contact.submit');

Route::get('/shipping-info', function() {
    return view('frontend.pages.shipping');
})->name('shipping.info');

Route::get('/returns', function() {
    return view('frontend.pages.returns');
})->name('returns');

Route::get('/size-guide', function() {
    return view('frontend.pages.size-guide');
})->name('size.guide');

Route::get('/terms', function() {
    return view('frontend.pages.terms');
})->name('terms');

Route::get('/privacy', function() {
    return view('frontend.pages.privacy');
})->name('privacy');

// =====================================
// API ROUTES (for AJAX calls)
// =====================================

Route::prefix('api')->group(function() {
    // Quick product search for autocomplete
    Route::get('/products/search', [ProductController::class, 'quickSearch'])->name('api.products.search');
    
    // Get product variants (size, color)
    Route::get('/products/{id}/variants', [ProductController::class, 'getVariants'])->name('api.products.variants');
    
    // Check product stock
    Route::get('/products/{id}/stock', [ProductController::class, 'checkStock'])->name('api.products.stock');
    
    // AJAX Wishlist routes (requires authentication) - ADDED
    Route::middleware('auth')->group(function() {
        Route::get('/wishlist/count', [WishlistController::class, 'getCount'])->name('api.wishlist.count');
        Route::post('/wishlist/toggle/{productId}', [WishlistController::class, 'toggle'])->name('api.wishlist.toggle');
        Route::post('/wishlist/check', [WishlistController::class, 'checkProducts'])->name('api.wishlist.check');
    });
    
    // Newsletter subscription
    Route::post('/newsletter', function() {
        // Newsletter subscription logic
        return response()->json(['success' => true, 'message' => 'Subscribed successfully!']);
    })->name('api.newsletter');
    
    // =====================================
    // RAJAONGKIR V2 API ROUTES - ADDED
    // =====================================
    
    // RajaOngkir V2 destination search (alternative endpoint)
    Route::get('/rajaongkir/search', [CheckoutController::class, 'searchDestinations'])->name('api.rajaongkir.search');
    
    // RajaOngkir V2 shipping calculation (alternative endpoint)
    Route::post('/rajaongkir/shipping', [CheckoutController::class, 'calculateShipping'])->name('api.rajaongkir.shipping');
    
    // RajaOngkir V2 test connection
    Route::get('/rajaongkir/test', function() {
        $service = new \App\Services\RajaOngkirService();
        return response()->json($service->testConnection());
    })->name('api.rajaongkir.test');
});

// =====================================
// DEBUG ROUTES (Remove in production)
// =====================================

Route::prefix('debug')->group(function() {
    Route::get('/routes', function() {
        $routes = collect(Route::getRoutes())->map(function ($route) {
            return [
                'method' => implode('|', $route->methods()),
                'uri' => $route->uri(),
                'name' => $route->getName(),
                'action' => $route->getActionName(),
            ];
        });
        
        return response()->json($routes);
    });

    Route::get('/checkout', function() {
        dd([
            'session_cart' => session('cart', []),
            'cart_count' => count(session('cart', [])),
            'csrf_token' => csrf_token(),
            'user' => Auth::user(),
            'routes' => [
                'checkout.index' => route('checkout.index'),
                'checkout.store' => route('checkout.store'),
                'checkout.search-destinations' => route('checkout.search-destinations'),
                'checkout.shipping' => route('checkout.shipping'),
                'checkout.cities' => route('checkout.cities'), // Legacy
            ],
            'rajaongkir_config' => [
                'api_key' => config('services.rajaongkir.api_key'),
                'base_url' => config('services.rajaongkir.base_url'),
                'working_endpoints' => config('services.rajaongkir.working_endpoints')
            ]
        ]);
    });

    Route::get('/session', function() {
        return response()->json([
            'cart' => session('cart', []),
            'user' => Auth::user(),
            'csrf' => csrf_token(),
            'all_session' => session()->all()
        ]);
    });

    Route::get('/categories', function() {
        $allCategories = \App\Models\Category::all();
        $activeCategories = \App\Models\Category::where('is_active', true)->get();
        
        return response()->json([
            'total_categories' => $allCategories->count(),
            'active_categories' => $activeCategories->count(),
            'categories' => $activeCategories->map(function($cat) {
                return [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'slug' => $cat->slug,
                    'is_active' => $cat->is_active,
                    'products_count' => $cat->products()->count()
                ];
            })
        ]);
    });

    Route::get('/products', function() {
        $products = \App\Models\Product::with('category')->get();
        
        return response()->json([
            'total_products' => $products->count(),
            'active_products' => $products->where('is_active', true)->count(),
            'products' => $products->map(function($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'price' => $product->price,
                    'sale_price' => $product->sale_price,
                    'stock' => $product->stock_quantity,
                    'category' => $product->category->name ?? 'No Category',
                    'is_active' => $product->is_active
                ];
            })
        ]);
    });

    Route::get('/clear-cart', function() {
        session()->forget('cart');
        return response()->json(['message' => 'Cart cleared', 'cart' => session('cart', [])]);
    });

    // WISHLIST DEBUG ROUTES - ADDED
    Route::get('/wishlist', function() {
        if (!Auth::check()) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }
        
        $wishlists = \App\Models\Wishlist::where('user_id', Auth::id())->with('product')->get();
        
        return response()->json([
            'user' => Auth::user(),
            'wishlist_count' => $wishlists->count(),
            'wishlist_items' => $wishlists->map(function($wishlist) {
                return [
                    'id' => $wishlist->id,
                    'product_id' => $wishlist->product_id,
                    'product_name' => $wishlist->product->name ?? 'Product not found',
                    'created_at' => $wishlist->created_at
                ];
            })
        ]);
    });

    // =====================================
    // RAJAONGKIR V2 DEBUG ROUTES - ADDED
    // =====================================
    
    Route::get('/rajaongkir', function() {
        $service = new \App\Services\RajaOngkirService();
        $config = $service->getConfig();
        $testConnection = $service->testConnection();
        
        return response()->json([
            'service_config' => $config,
            'connection_test' => $testConnection,
            'env_config' => [
                'api_key' => config('services.rajaongkir.api_key'),
                'base_url' => config('services.rajaongkir.base_url'),
                'timeout' => config('services.rajaongkir.timeout'),
            ],
            'available_routes' => [
                'search-destinations' => route('checkout.search-destinations'),
                'calculate-shipping' => route('checkout.shipping'),
                'api-search' => route('api.rajaongkir.search'),
                'api-shipping' => route('api.rajaongkir.shipping'),
                'api-test' => route('api.rajaongkir.test'),
            ]
        ]);
    });
    
    Route::get('/rajaongkir/provinces', function() {
        $service = new \App\Services\RajaOngkirService();
        $provinces = $service->getProvinces();
        
        return response()->json([
            'total_provinces' => count($provinces),
            'provinces' => $provinces,
            'sample_province' => $provinces[0] ?? null
        ]);
    });
    
    Route::get('/rajaongkir/search/{term}', function($term) {
        $service = new \App\Services\RajaOngkirService();
        $results = $service->searchDestinations($term, 5);
        
        return response()->json([
            'search_term' => $term,
            'total_results' => count($results),
            'results' => $results,
            'sample_result' => $results[0] ?? null
        ]);
    });
    
    Route::get('/rajaongkir/major-cities', function() {
        $service = new \App\Services\RajaOngkirService();
        $cities = $service->getMajorCities();
        
        return response()->json([
            'total_cities' => count($cities),
            'cities' => $cities
        ]);
    });
});

// =====================================
// FALLBACK ROUTES
// =====================================

// Handle old URLs or redirects
Route::get('/shop', function() {
    return redirect()->route('products.index');
});

Route::get('/category/{slug}', function($slug) {
    return redirect()->route('categories.show', $slug);
});

Route::get('/product/{slug}', function($slug) {
    return redirect()->route('products.show', $slug);
});

// =====================================
// RAJAONGKIR V2 TESTING ROUTES - ADDED
// =====================================

Route::prefix('rajaongkir-test')->group(function() {
    // Quick test page
    Route::get('/', function() {
        return view('debug.rajaongkir-test', [
            'api_key' => config('services.rajaongkir.api_key'),
            'base_url' => config('services.rajaongkir.base_url')
        ]);
    })->name('rajaongkir.test.page');
    
    // Test provinces endpoint
    Route::get('/provinces', function() {
        $service = new \App\Services\RajaOngkirService();
        return $service->getProvinces();
    })->name('rajaongkir.test.provinces');
    
    // Test search endpoint
    Route::get('/search', function() {
        $search = request('q', 'jakarta');
        $service = new \App\Services\RajaOngkirService();
        return $service->searchDestinations($search, 10);
    })->name('rajaongkir.test.search');
    
    // Test shipping calculation
    Route::get('/shipping', function() {
        $service = new \App\Services\RajaOngkirService();
        return $service->calculateShipping('17473', '17474', 1000, 'jne');
    })->name('rajaongkir.test.shipping');
});

// 404 handling for specific paths
Route::fallback(function() {
    abort(404);
});
Route::prefix('api')->group(function() {
    Route::get('/google-maps/config', function() {
        return response()->json([
            'api_key' => env('GOOGLE_MAPS_API_KEY'),
            'default_location' => [
                'lat' => env('STORE_DEFAULT_LAT', -6.2088),
                'lng' => env('STORE_DEFAULT_LNG', 106.8456)
            ],
            'default_zoom' => env('GOOGLE_MAPS_DEFAULT_ZOOM', 13),
            'country_restriction' => env('GOOGLE_MAPS_COUNTRY', 'ID'),
        ]);
    })->name('api.google.maps.config');
    
    Route::get('/store/location', [CheckoutController::class, 'getStoreOrigin'])->name('api.store.location');
});