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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Homepage
Route::get('/', [HomeController::class, 'index'])->name('home');

// =====================================
// PRODUCT ROUTES
// =====================================
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show');
Route::get('/mens', [ProductController::class, 'mens'])->name('products.mens');
Route::get('/womens', [ProductController::class, 'womens'])->name('products.womens');
Route::get('/kids', [ProductController::class, 'kids'])->name('products.kids');
Route::get('/brand', [ProductController::class, 'brand'])->name('products.brand');
Route::get('/accessories', [ProductController::class, 'accessories'])->name('products.accessories');
Route::get('/sale', [ProductController::class, 'sale'])->name('products.sale');
Route::get('/search', [ProductController::class, 'search'])->name('search');
Route::get('/filter', [ProductController::class, 'filter'])->name('products.filter');

// Categories
Route::get('/categories/{slug}', [CategoryController::class, 'show'])->name('categories.show');

// =====================================
// CART ROUTES
// =====================================
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::patch('/cart/{id}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{id}', [CartController::class, 'remove'])->name('cart.remove');
Route::delete('/cart', [CartController::class, 'clear'])->name('cart.clear');
Route::get('/api/cart/count', [CartController::class, 'getCartCount'])->name('cart.count');
Route::get('/api/cart/data', [CartController::class, 'getCartData'])->name('cart.data');
Route::post('/cart/sync', [CartController::class, 'syncCart'])->name('cart.sync');

// =====================================
// AUTHENTICATION ROUTES
// =====================================
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->name('register.submit');
    Route::get('/auth/google', [GoogleController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('/auth/google/callback', [GoogleController::class, 'handleGoogleCallback'])->name('auth.google.callback');
});

Route::post('/logout', [GoogleController::class, 'logout'])->name('logout')->middleware('auth');
Route::get('/password/reset', function() {
    return view('auth.passwords.email');
})->name('password.request');

// =====================================
// CHECKOUT & PAYMENT ROUTES
// =====================================
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/checkout/search-destinations', [CheckoutController::class, 'searchDestinations'])->name('checkout.search-destinations');
Route::post('/checkout/shipping', [CheckoutController::class, 'calculateShipping'])->name('checkout.shipping');
Route::get('/checkout/success/{orderNumber}', [CheckoutController::class, 'success'])->name('checkout.success');

// Payment routes
Route::get('/checkout/payment/{orderNumber}', [CheckoutController::class, 'payment'])->name('checkout.payment');
Route::get('/checkout/payment-success', [CheckoutController::class, 'paymentSuccess'])->name('checkout.payment.success');

// Payment callbacks
Route::get('/checkout/payment-pending', function(Request $request) {
    $orderNumber = $request->get('order_id');
    if ($orderNumber) {
        return redirect()->route('checkout.success', $orderNumber)->with('warning', 'Payment is being processed.');
    }
    return redirect()->route('home')->with('warning', 'Payment is being processed.');
})->name('checkout.payment.pending');

Route::get('/checkout/payment-error', function(Request $request) {
    $orderNumber = $request->get('order_id');
    if ($orderNumber) {
        return redirect()->route('checkout.success', $orderNumber)->with('error', 'Payment failed.');
    }
    return redirect()->route('home')->with('error', 'Payment failed.');
})->name('checkout.payment.error');

Route::get('/checkout/finish', function(Request $request) {
    $orderNumber = $request->get('order_id');
    if ($orderNumber) {
        return redirect()->route('checkout.success', $orderNumber)->with('success', 'Payment completed!');
    }
    return redirect()->route('home')->with('success', 'Payment completed!');
})->name('checkout.finish');

Route::get('/checkout/unfinish', function(Request $request) {
    $orderNumber = $request->get('order_id');
    if ($orderNumber) {
        return redirect()->route('checkout.success', $orderNumber)->with('warning', 'Payment pending.');
    }
    return redirect()->route('home')->with('warning', 'Payment pending.');
})->name('checkout.unfinish');

Route::get('/checkout/error', function(Request $request) {
    $orderNumber = $request->get('order_id');
    if ($orderNumber) {
        return redirect()->route('checkout.success', $orderNumber)->with('error', 'Payment error.');
    }
    return redirect()->route('home')->with('error', 'Payment error.');
})->name('checkout.error');

// =====================================
// WEBHOOK ROUTES (No CSRF)
// =====================================
Route::withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])->group(function() {
    Route::post('/checkout/payment-notification', [CheckoutController::class, 'paymentNotification'])->name('checkout.payment.notification');
    Route::post('/midtrans/notification', [CheckoutController::class, 'paymentNotification'])->name('midtrans.notification');
    Route::post('/webhook/midtrans', [CheckoutController::class, 'paymentNotification'])->name('webhook.midtrans');
    Route::post('/payment/webhook', [CheckoutController::class, 'paymentNotification'])->name('payment.webhook');
});

// =====================================
// AUTHENTICATED USER ROUTES
// =====================================
Route::middleware(['auth'])->group(function () {
    // Orders
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{orderNumber}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{orderNumber}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::get('/orders/{orderNumber}/invoice', [OrderController::class, 'invoice'])->name('orders.invoice');
    
    // Wishlist
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist/toggle/{productId}', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
    Route::delete('/wishlist/remove/{productId}', [WishlistController::class, 'remove'])->name('wishlist.remove');
    Route::delete('/wishlist/clear', [WishlistController::class, 'clear'])->name('wishlist.clear');
    Route::post('/wishlist/move-to-cart/{productId}', [WishlistController::class, 'moveToCart'])->name('wishlist.moveToCart');
    Route::get('/wishlist/count', [WishlistController::class, 'getCount'])->name('wishlist.count');
    Route::post('/wishlist/check', [WishlistController::class, 'checkProducts'])->name('wishlist.check');
    
    // Profile
    Route::get('/profile', function() {
        return view('frontend.profile.index');
    })->name('profile.index');
    
    Route::get('/profile/edit', function() {
        return view('frontend.profile.edit');
    })->name('profile.edit');
    
    Route::patch('/profile', function() {
        return redirect()->route('profile.index')->with('success', 'Profile updated successfully');
    })->name('profile.update');
});

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
// API ROUTES
// =====================================
Route::prefix('api')->group(function() {
    // Products
    Route::get('/products/search', [ProductController::class, 'quickSearch'])->name('api.products.search');
    Route::get('/products/{id}/variants', [ProductController::class, 'getVariants'])->name('api.products.variants');
    Route::get('/products/{id}/stock', [ProductController::class, 'checkStock'])->name('api.products.stock');
    
    // Wishlist (auth required)
    Route::middleware('auth')->group(function() {
        Route::get('/wishlist/count', [WishlistController::class, 'getCount'])->name('api.wishlist.count');
        Route::post('/wishlist/toggle/{productId}', [WishlistController::class, 'toggle'])->name('api.wishlist.toggle');
        Route::post('/wishlist/check', [WishlistController::class, 'checkProducts'])->name('api.wishlist.check');
    });
    
    // Newsletter
    Route::post('/newsletter', function() {
        return response()->json(['success' => true, 'message' => 'Subscribed successfully!']);
    })->name('api.newsletter');
    
    // RajaOngkir
    Route::get('/rajaongkir/search', [CheckoutController::class, 'searchDestinations'])->name('api.rajaongkir.search');
    Route::post('/rajaongkir/shipping', [CheckoutController::class, 'calculateShipping'])->name('api.rajaongkir.shipping');
    
    // Store info
    Route::get('/store/location', function() {
        return response()->json([
            'store_name' => env('APP_NAME', 'SneakerFlash'),
            'origin_city' => env('STORE_ORIGIN_CITY_NAME', 'Jakarta Selatan'),
            'origin_city_id' => env('STORE_ORIGIN_CITY_ID', 158),
            'address' => env('STORE_ADDRESS', 'Jakarta, Indonesia'),
            'phone' => env('STORE_PHONE', '+62-21-xxxxxxxx'),
            'email' => env('STORE_EMAIL', 'info@sneakerflash.com'),
        ]);
    })->name('api.store.location');
    
    // Payment
    Route::get('/payment/status/{orderNumber}', function($orderNumber) {
        try {
            $order = \App\Models\Order::where('order_number', $orderNumber)->first();
            if (!$order) {
                return response()->json(['error' => 'Order not found'], 404);
            }
            return response()->json([
                'success' => true,
                'order_number' => $order->order_number,
                'payment_status' => $order->payment_status,
                'order_status' => $order->order_status,
                'total_amount' => $order->total_amount,
                'created_at' => $order->created_at,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to get payment status'], 500);
        }
    })->name('api.payment.status');
    
    Route::post('/payment/retry/{orderNumber}', function($orderNumber) {
        try {
            $order = \App\Models\Order::where('order_number', $orderNumber)->first();
            if (!$order) {
                return response()->json(['error' => 'Order not found'], 404);
            }
            if ($order->payment_status === 'paid') {
                return response()->json(['error' => 'Order already paid'], 400);
            }
            
            $midtransService = app(\App\Services\MidtransService::class);
            $orderItems = $order->orderItems;
            $items = [];
            
            foreach ($orderItems as $item) {
                $items[] = [
                    'id' => $item->product_id,
                    'price' => (int) $item->product_price,
                    'quantity' => (int) $item->quantity,
                    'name' => $item->product_name
                ];
            }
            
            if ($order->shipping_cost > 0) {
                $items[] = [
                    'id' => 'shipping',
                    'price' => (int) $order->shipping_cost,
                    'quantity' => 1,
                    'name' => 'Shipping Cost'
                ];
            }
            
            if ($order->tax_amount > 0) {
                $items[] = [
                    'id' => 'tax',
                    'price' => (int) $order->tax_amount,
                    'quantity' => 1,
                    'name' => 'Tax'
                ];
            }
            
            $midtransOrder = [
                'order_id' => $order->order_number,
                'gross_amount' => (int) $order->total_amount,
                'customer' => [
                    'first_name' => explode(' ', $order->customer_name)[0] ?? 'Customer',
                    'last_name' => explode(' ', $order->customer_name, 2)[1] ?? '',
                    'email' => $order->customer_email,
                    'phone' => $order->customer_phone
                ],
                'billing_address' => [
                    'address' => $order->shipping_address,
                    'city' => $order->shipping_destination_label,
                    'postal_code' => $order->shipping_postal_code
                ],
                'shipping_address' => [
                    'address' => $order->shipping_address,
                    'city' => $order->shipping_destination_label,
                    'postal_code' => $order->shipping_postal_code
                ],
                'items' => $items
            ];
            
            $response = $midtransService->createSnapToken($midtransOrder);
            
            if ($response && isset($response['token'])) {
                $order->update(['snap_token' => $response['token']]);
                return response()->json([
                    'success' => true,
                    'snap_token' => $response['token'],
                    'order_number' => $order->order_number
                ]);
            }
            
            return response()->json(['error' => 'Failed to create payment session'], 500);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retry payment'], 500);
        }
    })->name('api.payment.retry');

    // Debug info endpoint
    Route::get('/debug-info', function () {
        return response()->json([
            'app_env' => app()->environment(),
            'app_debug' => config('app.debug'),
            'app_url' => config('app.url'),
            'database_default' => config('database.default'),
            'session_driver' => config('session.driver'),
            'cache_driver' => config('cache.default'),
            'mail_driver' => config('mail.default'),
            'php_version' => phpversion(),
            'laravel_version' => app()->version(),
            'server_info' => [
                'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'
            ]
        ]);
    })->name('api.debug-info');

    // Test route for CSRF
    Route::post('/csrf-test', function (Request $request) {
        return response()->json([
            'status' => 'success',
            'message' => 'CSRF token valid',
            'data' => $request->all()
        ]);
    })->name('api.csrf-test');
});

// =====================================
// ENHANCED DEBUG ROUTES
// =====================================
Route::prefix('debug')->group(function() {
    
    // Basic tests
    Route::get('/test', function () {
        return response()->json([
            'status' => 'success',
            'message' => 'Laravel is working',
            'timestamp' => now(),
            'environment' => app()->environment(),
            'debug_mode' => config('app.debug'),
            'php_version' => phpversion(),
            'laravel_version' => app()->version()
        ]);
    })->name('debug.test');
    
    // Test CSRF token
    Route::post('/csrf-test', function (Request $request) {
        return response()->json([
            'status' => 'success',
            'message' => 'CSRF token is valid',
            'request_data' => $request->all()
        ]);
    })->name('debug.csrf-test');
    
    // Test session
    Route::get('/session-test', function (Request $request) {
        $request->session()->put('test_key', 'test_value');
        return response()->json([
            'status' => 'success',
            'session_id' => Session::getId(),
            'test_value' => Session::get('test_key'),
            'cart_data' => Session::get('cart', [])
        ]);
    })->name('debug.session-test');
    
    // Test database
    Route::get('/database-test', function () {
        try {
            DB::connection()->getPdo();
            $orders_count = DB::table('orders')->count();
            $products_count = DB::table('products')->count();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Database connection successful',
                'orders_count' => $orders_count,
                'products_count' => $products_count
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Database connection failed',
                'error' => $e->getMessage()
            ], 500);
        }
    })->name('debug.database-test');
    
    // Test checkout validation
    Route::post('/checkout-validation-test', function (Request $request) {
        try {
            // Test validation rules yang sama dengan CheckoutController
            $validated = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'phone' => 'required|string|max:20',
                'address' => 'required|string|max:500',
                'destination_id' => 'required|string',
                'destination_label' => 'required|string',
                'postal_code' => 'required|string|max:10',
                'shipping_method' => 'required|string',
                'shipping_cost' => 'required|numeric|min:0',
                'payment_method' => 'required|in:bank_transfer,credit_card,ewallet,cod',
                'privacy_accepted' => 'required|accepted',
            ]);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Validation passed',
                'validated_data' => $validated
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'validation_error',
                'errors' => $e->errors(),
                'message' => 'Validation failed'
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unexpected error during validation',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    })->name('debug.checkout-validation-test');
    
    // Test cart functionality
    Route::get('/cart-test', function (Request $request) {
        try {
            $cart = Session::get('cart', []);
            
            // Simulate cart items jika kosong
            if (empty($cart)) {
                $cart = [
                    1 => [
                        'id' => 1,
                        'name' => 'Test Product',
                        'price' => 100000,
                        'quantity' => 1,
                        'weight' => 800
                    ]
                ];
                Session::put('cart', $cart);
            }
            
            return response()->json([
                'status' => 'success',
                'cart_items' => $cart,
                'cart_count' => count($cart)
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cart test failed',
                'error' => $e->getMessage()
            ], 500);
        }
    })->name('debug.cart-test');
    
    // Test minimal checkout submission
    Route::post('/minimal-checkout-test', function (Request $request) {
        try {
            Log::info('Debug: Minimal checkout test started', $request->all());
            
            // Simulate minimal checkout process
            $validated = $request->validate([
                'first_name' => 'required|string',
                'last_name' => 'required|string',
                'email' => 'required|email',
                'payment_method' => 'required|string',
                'privacy_accepted' => 'required'
            ]);
            
            Log::info('Debug: Validation passed');
            
            // Simulate order creation without database
            $orderNumber = 'TEST-' . date('Ymd') . '-' . strtoupper(Str::random(6));
            
            Log::info('Debug: Order number generated', ['order_number' => $orderNumber]);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Minimal checkout test successful',
                'order_number' => $orderNumber,
                'validated_data' => $validated
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Debug: Validation error', $e->errors());
            return response()->json([
                'status' => 'validation_error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Debug: Unexpected error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Minimal checkout test failed',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    })->name('debug.minimal-checkout-test');

    // Test checkout form submission
    Route::post('/test-checkout', function(Request $request) {
        try {
            Log::info('Debug: Test checkout form submission received', [
                'method' => $request->method(),
                'headers' => $request->headers->all(),
                'input' => $request->all()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Debug checkout received successfully',
                'data' => [
                    'headers' => $request->headers->all(),
                    'method' => $request->method(),
                    'all_input' => $request->all(),
                    'files' => $request->allFiles(),
                    'csrf_token' => $request->header('X-CSRF-TOKEN'),
                    'session_cart' => Session::get('cart', []),
                    'user' => Auth::user(),
                    'request_time' => now()->toDateTimeString()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Debug: Test checkout error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    })->name('debug.test-checkout');

    // Rest of existing debug routes...
    Route::get('/routes', function() {
        $routes = collect(Route::getRoutes())->map(function ($route) {
            return [
                'method' => implode('|', $route->methods()),
                'uri' => $route->uri(),
                'name' => $route->getName(),
            ];
        });
        return response()->json($routes);
    })->name('debug.routes');

    Route::get('/checkout', function() {
        return response()->json([
            'session_cart' => Session::get('cart', []),
            'cart_count' => count(Session::get('cart', [])),
            'csrf_token' => csrf_token(),
            'user' => Auth::user(),
            'midtrans_config' => [
                'client_key' => config('services.midtrans.client_key'),
                'server_key_set' => !empty(config('services.midtrans.server_key')),
                'is_production' => config('services.midtrans.is_production'),
            ],
        ]);
    })->name('debug.checkout');

    Route::get('/session', function() {
        return response()->json([
            'cart' => Session::get('cart', []),
            'user' => Auth::user(),
            'csrf' => csrf_token(),
        ]);
    })->name('debug.session');

    Route::get('/midtrans-config', function() {
        return response()->json([
            'config' => [
                'merchant_id' => config('services.midtrans.merchant_id'),
                'client_key' => config('services.midtrans.client_key'),
                'server_key_set' => !empty(config('services.midtrans.server_key')),
                'is_production' => config('services.midtrans.is_production'),
            ],
            'webhook_urls' => [
                route('checkout.payment.notification'),
                route('midtrans.notification'),
                route('webhook.midtrans'),
                route('payment.webhook'),
            ],
        ]);
    })->name('debug.midtrans-config');

    Route::get('/test-midtrans-token', function() {
        try {
            $midtransService = app(\App\Services\MidtransService::class);
            $testOrder = [
                'order_id' => 'TEST-' . time(),
                'gross_amount' => 100000,
                'customer' => [
                    'first_name' => 'Test',
                    'last_name' => 'User',
                    'email' => 'test@example.com',
                    'phone' => '08123456789'
                ],
                'billing_address' => [
                    'address' => 'Jl. Test No. 123',
                    'city' => 'Jakarta',
                    'postal_code' => '12345'
                ],
                'shipping_address' => [
                    'address' => 'Jl. Test No. 123',
                    'city' => 'Jakarta',
                    'postal_code' => '12345'
                ],
                'items' => [
                    [
                        'id' => 'test-item',
                        'price' => 100000,
                        'quantity' => 1,
                        'name' => 'Test Product'
                    ]
                ]
            ];
            
            $response = $midtransService->createSnapToken($testOrder);
            
            return response()->json([
                'success' => !empty($response['token'] ?? null),
                'snap_token' => $response['token'] ?? null,
                'error' => empty($response['token']) ? 'No token received' : null
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    })->name('debug.test-midtrans-token');

    Route::get('/create-test-order', function() {
        try {
            $order = \App\Models\Order::create([
                'order_number' => 'TEST-' . time(),
                'customer_name' => 'Test Customer',
                'customer_email' => 'test@example.com',
                'customer_phone' => '08123456789',
                'shipping_address' => 'Jl. Test No. 123',
                'shipping_destination_label' => 'Jakarta Selatan, DKI Jakarta',
                'shipping_postal_code' => '12345',
                'shipping_method' => 'JNE REG',
                'shipping_cost' => 15000,
                'payment_method' => 'credit_card',
                'subtotal' => 100000,
                'tax_amount' => 11000,
                'total_amount' => 126000,
                'payment_status' => 'pending',
                'order_status' => 'pending'
            ]);
            
            return response()->json([
                'success' => true,
                'order' => $order,
                'payment_url' => route('checkout.payment', $order->order_number),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    })->name('debug.create-test-order');

    Route::get('/clear-cart', function() {
        Session::forget('cart');
        return response()->json([
            'success' => true,
            'message' => 'Cart cleared'
        ]);
    })->name('debug.clear-cart');

    Route::get('/add-test-cart', function() {
        $testCart = [
            1 => [
                'id' => 1,
                'name' => 'Adidas Samba Black',
                'price' => 1300000,
                'quantity' => 1,
                'weight' => 800
            ]
        ];
        Session::put('cart', $testCart);
        return response()->json([
            'success' => true,
            'message' => 'Test cart added',
            'checkout_url' => route('checkout.index')
        ]);
    })->name('debug.add-test-cart');

    Route::get('/health-check', function() {
        $health = ['overall_status' => 'ok'];
        
        // Test database
        try {
            DB::connection()->getPdo();
            $health['database'] = 'ok';
        } catch (\Exception $e) {
            $health['database'] = 'error: ' . $e->getMessage();
            $health['overall_status'] = 'error';
        }
        
        // Test Midtrans
        if (!empty(config('services.midtrans.server_key'))) {
            $health['midtrans'] = 'ok';
        } else {
            $health['midtrans'] = 'warning: server key not configured';
        }
        
        // Test session
        try {
            Session::put('health_test', time());
            $health['session'] = Session::get('health_test') ? 'ok' : 'error';
        } catch (\Exception $e) {
            $health['session'] = 'error: ' . $e->getMessage();
        }
        
        // Test cache
        try {
            Cache::put('test_key', 'test_value', 10);
            $health['cache'] = Cache::get('test_key') === 'test_value' ? 'ok' : 'error';
        } catch (\Exception $e) {
            $health['cache'] = 'error: ' . $e->getMessage();
        }
        
        // Test logs
        try {
            Log::info('Health check test log');
            $health['logs'] = 'ok';
        } catch (\Exception $e) {
            $health['logs'] = 'error: ' . $e->getMessage();
        }
        
        return response()->json($health);
    })->name('debug.health-check');

    // Test form submission with different approaches
    Route::get('/form-test', function() {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <title>Form Test</title>
            <meta name="csrf-token" content="' . csrf_token() . '">
        </head>
        <body>
            <h1>Form Test Page</h1>
            
            <h2>1. Test Normal Form Submission</h2>
            <form action="/debug/test-checkout" method="POST">
                ' . csrf_field() . '
                <input type="text" name="first_name" value="Test" required>
                <input type="text" name="last_name" value="User" required>
                <input type="email" name="email" value="test@example.com" required>
                <input type="text" name="payment_method" value="credit_card" required>
                <input type="checkbox" name="privacy_accepted" value="1" checked required>
                <button type="submit">Submit Normal Form</button>
            </form>
            
            <h2>2. Test AJAX Submission</h2>
            <button onclick="testAjaxSubmission()">Submit via AJAX</button>
            
            <h2>3. Test Actual Checkout</h2>
            <button onclick="testActualCheckout()">Test Real Checkout</button>
            
            <div id="results"></div>
            
            <script>
                function testAjaxSubmission() {
                    const formData = new FormData();
                    formData.append("_token", document.querySelector("meta[name=csrf-token]").content);
                    formData.append("first_name", "Test");
                    formData.append("last_name", "User");
                    formData.append("email", "test@example.com");
                    formData.append("payment_method", "credit_card");
                    formData.append("privacy_accepted", "1");
                    
                    fetch("/debug/test-checkout", {
                        method: "POST",
                        headers: {
                            "X-CSRF-TOKEN": document.querySelector("meta[name=csrf-token]").content,
                            "Accept": "application/json",
                            "X-Requested-With": "XMLHttpRequest",
                        },
                        body: formData,
                    })
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById("results").innerHTML = "<pre>" + JSON.stringify(data, null, 2) + "</pre>";
                    })
                    .catch(error => {
                        document.getElementById("results").innerHTML = "<pre>Error: " + error + "</pre>";
                    });
                }
                
                function testActualCheckout() {
                    const formData = new FormData();
                    const token = document.querySelector("meta[name=csrf-token]").content;
                    
                    formData.append("_token", token);
                    formData.append("first_name", "Test");
                    formData.append("last_name", "User");
                    formData.append("email", "test@example.com");
                    formData.append("phone", "08123456789");
                    formData.append("address", "Jl. Test No. 123");
                    formData.append("destination_id", "test_123");
                    formData.append("destination_label", "Test Location");
                    formData.append("postal_code", "12345");
                    formData.append("shipping_method", "JNE REG");
                    formData.append("shipping_cost", "15000");
                    formData.append("payment_method", "cod");
                    formData.append("privacy_accepted", "1");
                    
                    fetch("/checkout", {
                        method: "POST",
                        headers: {
                            "X-CSRF-TOKEN": token,
                            "Accept": "application/json",
                            "X-Requested-With": "XMLHttpRequest",
                        },
                        body: formData,
                    })
                    .then(async response => {
                        const text = await response.text();
                        document.getElementById("results").innerHTML = 
                            "<h3>Status: " + response.status + "</h3>" +
                            "<pre>" + text.substring(0, 1000) + "</pre>";
                    })
                    .catch(error => {
                        document.getElementById("results").innerHTML = "<pre>Error: " + error + "</pre>";
                    });
                }
            </script>
        </body>
        </html>';
    })->name('debug.form-test');

    // Test controller methods individually
    Route::get('/test-controller-method/{method}', function($method) {
        try {
            $controller = new \App\Http\Controllers\Frontend\CheckoutController(
                new \App\Services\MidtransService()
            );
            
            switch($method) {
                case 'index':
                    // Add test cart first
                    Session::put('cart', [
                        1 => [
                            'id' => 1,
                            'name' => 'Test Product',
                            'price' => 100000,
                            'quantity' => 1,
                            'weight' => 800
                        ]
                    ]);
                    
                    $result = $controller->index();
                    return response()->json([
                        'success' => true,
                        'message' => 'Index method executed successfully',
                        'view_data' => 'View rendered successfully'
                    ]);
                    
                case 'searchDestinations':
                    $request = request();
                    $request->merge(['search' => 'jakarta', 'limit' => 5]);
                    $result = $controller->searchDestinations($request);
                    return $result;
                    
                case 'calculateShipping':
                    $request = request();
                    $request->merge([
                        'destination_id' => 'test_123',
                        'destination_label' => 'Test Location',
                        'weight' => 1000
                    ]);
                    $result = $controller->calculateShipping($request);
                    return $result;
                    
                default:
                    return response()->json([
                        'error' => 'Method not supported for testing'
                    ], 400);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    })->name('debug.test-controller-method');

    // Performance test
    Route::get('/performance-test', function() {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        try {
            // Test database queries
            $dbStart = microtime(true);
            $orderCount = DB::table('orders')->count();
            $productCount = DB::table('products')->count();
            $dbTime = microtime(true) - $dbStart;
            
            // Test session
            $sessionStart = microtime(true);
            Session::put('perf_test', time());
            $sessionValue = Session::get('perf_test');
            $sessionTime = microtime(true) - $sessionStart;
            
            // Test cache
            $cacheStart = microtime(true);
            Cache::put('perf_test', 'test_value', 60);
            $cacheValue = Cache::get('perf_test');
            $cacheTime = microtime(true) - $cacheStart;
            
            $endTime = microtime(true);
            $endMemory = memory_get_usage();
            
            return response()->json([
                'success' => true,
                'performance' => [
                    'total_time' => round(($endTime - $startTime) * 1000, 2) . 'ms',
                    'memory_used' => round(($endMemory - $startMemory) / 1024, 2) . 'KB',
                    'database_time' => round($dbTime * 1000, 2) . 'ms',
                    'session_time' => round($sessionTime * 1000, 2) . 'ms',
                    'cache_time' => round($cacheTime * 1000, 2) . 'ms',
                ],
                'results' => [
                    'order_count' => $orderCount,
                    'product_count' => $productCount,
                    'session_test' => $sessionValue,
                    'cache_test' => $cacheValue,
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    })->name('debug.performance-test');

    // Comprehensive system info
    Route::get('/system-info', function() {
        return response()->json([
            'php' => [
                'version' => phpversion(),
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size'),
            ],
            'laravel' => [
                'version' => app()->version(),
                'environment' => app()->environment(),
                'debug_mode' => config('app.debug'),
                'timezone' => config('app.timezone'),
                'url' => config('app.url'),
            ],
            'database' => [
                'default' => config('database.default'),
                'connection' => config('database.connections.' . config('database.default')),
            ],
            'session' => [
                'driver' => config('session.driver'),
                'lifetime' => config('session.lifetime'),
                'secure' => config('session.secure'),
                'http_only' => config('session.http_only'),
            ],
            'cache' => [
                'default' => config('cache.default'),
            ],
            'mail' => [
                'default' => config('mail.default'),
            ],
            'services' => [
                'midtrans_configured' => !empty(config('services.midtrans.server_key')),
                'rajaongkir_configured' => !empty(config('services.rajaongkir.api_key')),
            ],
            'server' => [
                'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
                'server_name' => $_SERVER['SERVER_NAME'] ?? 'Unknown',
                'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
            ]
        ]);
    })->name('debug.system-info');
});

// =====================================
// FALLBACK ROUTES
// =====================================
Route::get('/shop', function() {
    return redirect()->route('products.index');
});

Route::get('/category/{slug}', function($slug) {
    return redirect()->route('categories.show', $slug);
});

Route::get('/product/{slug}', function($slug) {
    return redirect()->route('products.show', $slug);
});

Route::fallback(function() {
    abort(404);
});

/*
=====================================
INSTRUKSI PENGGUNAAN DEBUG ROUTES:
=====================================

1. TESTING BASIC FUNCTIONALITY:
   GET  /debug/test                    - Test basic Laravel
   GET  /debug/health-check            - Comprehensive health check
   GET  /debug/system-info             - System information

2. TESTING FORM SUBMISSION:
   GET  /debug/form-test               - Interactive form testing page
   POST /debug/test-checkout           - Test checkout form submission
   POST /debug/minimal-checkout-test   - Test minimal checkout

3. TESTING INDIVIDUAL COMPONENTS:
   GET  /debug/database-test           - Test database connection
   GET  /debug/session-test            - Test session functionality
   POST /debug/csrf-test               - Test CSRF token
   GET  /debug/cart-test               - Test cart functionality

4. TESTING CHECKOUT SPECIFIC:
   POST /debug/checkout-validation-test - Test checkout validation
   GET  /debug/test-controller-method/{method} - Test controller methods
   GET  /debug/checkout                - Get checkout debug info

5. TESTING MIDTRANS:
   GET  /debug/test-midtrans-token     - Test Midtrans token creation
   GET  /debug/midtrans-config         - Check Midtrans configuration

6. UTILITY ROUTES:
   GET  /debug/add-test-cart           - Add test items to cart
   GET  /debug/clear-cart              - Clear cart
   GET  /debug/create-test-order       - Create test order
   GET  /debug/performance-test        - Performance testing

7. API DEBUGGING:
   GET  /api/debug-info               - API debug information
   POST /api/csrf-test                - API CSRF test

USAGE EXAMPLES:
- Test basic functionality: curl http://localhost:8001/debug/test
- Test form submission: Open http://localhost:8001/debug/form-test in browser
- Test checkout validation: Use the enhanced debug script in browser console

IMPORTANT: Remove all debug routes before production deployment!
*/