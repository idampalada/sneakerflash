<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CartController extends Controller
{
    public function index()
    {
        $cartItems = $this->getCartItems();
        $total = $this->calculateTotal($cartItems);
        
        return view('frontend.cart.index', compact('cartItems', 'total'));
    }

    public function add(Request $request)
    {
        try {
            $request->validate([
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|integer|min:1'
            ]);

            $product = Product::find($request->product_id);
            
            // Check if product is active and available
            if (!$product || !$product->is_active) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Product is not available'
                    ], 400);
                }
                return back()->with('error', 'Product is not available');
            }
            
            // IMPROVED STOCK CHECK
            $currentStock = $product->stock_quantity ?? 0;
            if ($currentStock <= 0 || $currentStock < $request->quantity) {
                $message = $currentStock <= 0 
                    ? 'Product is out of stock' 
                    : "Insufficient stock available. Only {$currentStock} items left.";
                    
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => $message
                    ], 400);
                }
                return back()->with('error', $message);
            }

            $cart = Session::get('cart', []);
            $productId = $request->product_id;
            $quantity = $request->quantity;

            if (isset($cart[$productId])) {
                // Update quantity if product already in cart
                $newQuantity = $cart[$productId]['quantity'] + $quantity;
                
                if ($newQuantity > $currentStock) {
                    if ($request->ajax()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Cannot add more items. Stock limit reached.'
                        ], 400);
                    }
                    return back()->with('error', 'Cannot add more items. Stock limit reached.');
                }
                
                $cart[$productId]['quantity'] = $newQuantity;
                // Update stock info when adding
                $cart[$productId]['stock'] = $currentStock;
            } else {
                // Add new product to cart
                $cart[$productId] = [
                    'name' => $product->name,
                    'price' => $product->sale_price ?: $product->price,
                    'original_price' => $product->price,
                    'quantity' => $quantity,
                    'image' => $product->images[0] ?? null,
                    'slug' => $product->slug,
                    'stock' => $currentStock, // CURRENT STOCK FROM DATABASE
                    'brand' => $product->brand ?? '',
                    'category' => $product->category->name ?? ''
                ];
            }

            Session::put('cart', $cart);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Product added to cart successfully!',
                    'cart_count' => $this->getCartItemCount()
                ]);
            }
            
            return back()->with('success', 'Product added to cart successfully!');
            
        } catch (\Exception $e) {
            Log::error('Cart add error: ' . $e->getMessage());
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Something went wrong. Please try again.'
                ], 500);
            }
            
            return back()->with('error', 'Something went wrong. Please try again.');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'quantity' => 'required|integer|min:1'
            ]);

            $cart = Session::get('cart', []);
            
            if (!isset($cart[$id])) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Product not found in cart'
                    ], 404);
                }
                return back()->with('error', 'Product not found in cart');
            }

            // ALWAYS GET FRESH PRODUCT DATA
            $product = Product::find($id);
            
            if (!$product || !$product->is_active) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Product is no longer available'
                    ], 404);
                }
                return back()->with('error', 'Product is no longer available');
            }
            
            // CHECK CURRENT STOCK
            $currentStock = $product->stock_quantity ?? 0;
            
            if ($currentStock <= 0) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Product is out of stock'
                    ], 400);
                }
                return back()->with('error', 'Product is out of stock');
            }
            
            if ($request->quantity > $currentStock) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => "Quantity exceeds available stock ({$currentStock} left)"
                    ], 400);
                }
                return back()->with('error', "Quantity exceeds available stock ({$currentStock} left)");
            }

            // Update cart with fresh data
            $cart[$id]['quantity'] = $request->quantity;
            $cart[$id]['price'] = $product->sale_price ?: $product->price;
            $cart[$id]['original_price'] = $product->price;
            $cart[$id]['stock'] = $currentStock; // UPDATE WITH CURRENT STOCK
            
            Session::put('cart', $cart);
            
            if ($request->ajax()) {
                $cartItems = $this->getCartItems();
                $total = $this->calculateTotal($cartItems);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Cart updated successfully!',
                    'cart_count' => $this->getCartItemCount(),
                    'subtotal' => ($product->sale_price ?: $product->price) * $request->quantity,
                    'total' => $total,
                    'stock' => $currentStock
                ]);
            }
            
            return back()->with('success', 'Cart updated successfully!');
            
        } catch (\Exception $e) {
            Log::error('Cart update error: ' . $e->getMessage());
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Something went wrong. Please try again.'
                ], 500);
            }
            
            return back()->with('error', 'Something went wrong. Please try again.');
        }
    }

    public function remove($id)
    {
        try {
            $cart = Session::get('cart', []);
            
            if (isset($cart[$id])) {
                $productName = $cart[$id]['name'];
                unset($cart[$id]);
                Session::put('cart', $cart);
                
                if (request()->ajax()) {
                    return response()->json([
                        'success' => true,
                        'message' => "'{$productName}' removed from cart!",
                        'cart_count' => $this->getCartItemCount()
                    ]);
                }
                
                return back()->with('success', "'{$productName}' removed from cart!");
            }
            
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item not found in cart'
                ], 404);
            }
            
            return back()->with('error', 'Item not found in cart');
            
        } catch (\Exception $e) {
            Log::error('Cart remove error: ' . $e->getMessage());
            
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Something went wrong. Please try again.'
                ], 500);
            }
            
            return back()->with('error', 'Something went wrong. Please try again.');
        }
    }

    public function clear()
    {
        try {
            Session::forget('cart');
            
            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Cart cleared successfully!',
                    'cart_count' => 0
                ]);
            }
            
            return redirect()->route('cart.index')->with('success', 'Cart cleared successfully!');
            
        } catch (\Exception $e) {
            Log::error('Cart clear error: ' . $e->getMessage());
            
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Something went wrong. Please try again.'
                ], 500);
            }
            
            return back()->with('error', 'Something went wrong. Please try again.');
        }
    }

    // Helper methods
    private function getCartItems()
    {
        $cart = Session::get('cart', []);
        $cartItems = collect();
        
        foreach ($cart as $id => $details) {
            // ALWAYS GET FRESH PRODUCT DATA FOR ACCURATE STOCK
            $product = Product::find($id);
            $currentStock = $product ? $product->stock_quantity : 0;
            
            // Remove items that are no longer available
            if (!$product || !$product->is_active) {
                continue;
            }
            
            // Ensure all required keys exist with defaults
            $cartItems->push([
                'id' => $id,
                'name' => $details['name'] ?? 'Unknown Product',
                'price' => $details['price'] ?? 0,
                'original_price' => $details['original_price'] ?? ($details['price'] ?? 0),
                'quantity' => min($details['quantity'] ?? 1, $currentStock), // Ensure quantity doesn't exceed stock
                'image' => $details['image'] ?? null,
                'slug' => $details['slug'] ?? '',
                'brand' => $details['brand'] ?? '',
                'category' => $details['category'] ?? '',
                'stock' => $currentStock, // ALWAYS USE FRESH STOCK DATA
                'subtotal' => ($details['price'] ?? 0) * min($details['quantity'] ?? 1, $currentStock)
            ]);
        }
        
        return $cartItems;
    }

    private function calculateTotal($cartItems)
    {
        return $cartItems->sum('subtotal');
    }

    private function getCartItemCount()
    {
        $cart = Session::get('cart', []);
        return array_sum(array_column($cart, 'quantity'));
    }

    // API method untuk AJAX calls
    public function getCartCount()
    {
        $count = $this->getCartItemCount();
        
        return response()->json(['count' => $count]);
    }

    // Get cart data for API calls
    public function getCartData()
    {
        $cartItems = $this->getCartItems();
        $total = $this->calculateTotal($cartItems);
        $count = $this->getCartItemCount();
        
        return response()->json([
            'items' => $cartItems,
            'total' => $total,
            'count' => $count,
            'formatted_total' => 'Rp ' . number_format($total, 0, ',', '.')
        ]);
    }

    // Sync cart with latest product data - IMPROVED
    public function syncCart()
    {
        try {
            $cart = Session::get('cart', []);
            $updated = false;
            $removedItems = [];
            
            foreach ($cart as $productId => $details) {
                $product = Product::find($productId);
                
                if (!$product || !$product->is_active) {
                    // Remove inactive products
                    $removedItems[] = $details['name'] ?? 'Unknown Product';
                    unset($cart[$productId]);
                    $updated = true;
                    continue;
                }
                
                $currentStock = $product->stock_quantity ?? 0;
                
                // Update price if changed
                $currentPrice = $product->sale_price ?: $product->price;
                if ($cart[$productId]['price'] != $currentPrice) {
                    $cart[$productId]['price'] = $currentPrice;
                    $cart[$productId]['original_price'] = $product->price;
                    $updated = true;
                }
                
                // Update stock info
                if ($cart[$productId]['stock'] != $currentStock) {
                    $cart[$productId]['stock'] = $currentStock;
                    $updated = true;
                }
                
                // Remove items that are out of stock
                if ($currentStock <= 0) {
                    $removedItems[] = $details['name'] ?? 'Unknown Product';
                    unset($cart[$productId]);
                    $updated = true;
                    continue;
                }
                
                // Adjust quantity if exceeds current stock
                if ($cart[$productId]['quantity'] > $currentStock) {
                    $cart[$productId]['quantity'] = $currentStock;
                    $updated = true;
                }
            }
            
            if ($updated) {
                Session::put('cart', $cart);
            }
            
            $message = 'Cart is up to date';
            if ($updated) {
                $message = 'Cart synced with latest data';
                if (!empty($removedItems)) {
                    $message .= '. Removed out of stock items: ' . implode(', ', $removedItems);
                }
            }
            
            return response()->json([
                'success' => true,
                'updated' => $updated,
                'cart_count' => $this->getCartItemCount(),
                'removed_items' => $removedItems,
                'message' => $message
            ]);
            
        } catch (\Exception $e) {
            Log::error('Cart sync error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync cart'
            ], 500);
        }
    }
}