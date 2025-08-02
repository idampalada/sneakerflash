@extends('layouts.app')

@section('title', 'Shopping Cart - SneakerFlash')

@section('content')
<!-- Page Header -->
<section class="bg-white py-6 border-b border-gray-200">
    <div class="container mx-auto px-4">
        <nav class="text-sm mb-4">
            <ol class="flex space-x-2 text-gray-600">
                <li><a href="/" class="hover:text-blue-600">Home</a></li>
                <li>/</li>
                <li class="text-gray-900">Shopping Cart</li>
            </ol>
        </nav>
        
        <div class="flex justify-between items-center">
            <h1 class="text-3xl font-bold text-gray-900">Shopping Cart</h1>
            <div class="text-gray-600">
                @php
                    $itemCount = isset($cartItems) ? $cartItems->count() : 0;
                @endphp
                {{ $itemCount }} items
            </div>
        </div>
    </div>
</section>

<div class="container mx-auto px-4 py-8">
    @if(isset($cartItems) && $cartItems->count() > 0)
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Cart Items -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-md overflow-hidden border border-gray-100">
                    @foreach($cartItems as $item)
                        <div class="p-6 border-b border-gray-200 last:border-b-0 cart-item" data-product-id="{{ $item['id'] }}">
                            <div class="flex items-center space-x-4">
                                <!-- Product Image -->
                                <div class="flex-shrink-0">
                                    @if($item['image'])
                                        @php
                                            $imageUrl = Storage::url($item['image']);
                                        @endphp
                                        <img src="{{ $imageUrl }}" 
                                             alt="{{ $item['name'] }}"
                                             class="w-24 h-24 object-cover rounded-xl">
                                    @else
                                        <div class="w-24 h-24 bg-gray-100 rounded-xl flex items-center justify-center">
                                            <i class="fas fa-shoe-prints text-2xl text-gray-300"></i>
                                        </div>
                                    @endif
                                </div>

                                <!-- Product Info -->
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-semibold text-gray-900 text-lg mb-1">{{ $item['name'] }}</h3>
                                    @if($item['brand'])
                                        <p class="text-sm text-gray-500 mb-1">{{ $item['brand'] }}</p>
                                    @endif
                                    @if($item['category'])
                                        <p class="text-xs text-gray-400 uppercase tracking-wide mb-2">{{ $item['category'] }}</p>
                                    @endif
                                    
                                    <!-- Price Display -->
                                    <div class="flex items-center space-x-2">
                                        @php
                                            $currentPrice = $item['price'];
                                            $originalPrice = $item['original_price'];
                                            $formattedPrice = number_format($currentPrice, 0, ',', '.');
                                            $formattedOriginalPrice = number_format($originalPrice, 0, ',', '.');
                                        @endphp
                                        
                                        @if($currentPrice < $originalPrice)
                                            <span class="text-lg font-bold text-red-600">
                                                Rp {{ $formattedPrice }}
                                            </span>
                                            <span class="text-sm text-gray-400 line-through">
                                                Rp {{ $formattedOriginalPrice }}
                                            </span>
                                        @else
                                            <span class="text-lg font-bold text-gray-900">
                                                Rp {{ $formattedPrice }}
                                            </span>
                                        @endif
                                    </div>

                                    <!-- Stock Status - FIXED -->
                                    @php
                                        $stockQuantity = $item['stock'];
                                        $isInStock = $stockQuantity > 0;
                                    @endphp
                                    
                                    @if($isInStock)
                                        <p class="text-xs text-green-600 mt-1">
                                            <i class="fas fa-check-circle mr-1"></i>
                                            In Stock ({{ $stockQuantity }} left)
                                        </p>
                                    @else
                                        <p class="text-xs text-red-600 mt-1">
                                            <i class="fas fa-times-circle mr-1"></i>
                                            Out of Stock
                                        </p>
                                    @endif
                                </div>

                                <!-- Quantity Controls -->
                                <div class="flex flex-col items-center space-y-4">
                                    <div class="flex items-center space-x-0 border border-gray-200 rounded-lg overflow-hidden">
                                        <!-- DECREASE BUTTON -->
                                        @php
                                            $currentQuantity = $item['quantity'];
                                            $maxStock = $item['stock'];
                                            $decreaseDisabled = $currentQuantity <= 1 ? 'disabled' : '';
                                            $increaseDisabled = $currentQuantity >= $maxStock ? 'disabled' : '';
                                        @endphp
                                        
                                        <button type="button" 
                                                onclick="decreaseQuantity({{ $item['id'] }})"
                                                class="decrease-btn w-10 h-10 bg-gray-50 hover:bg-gray-100 flex items-center justify-center transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                                {{ $decreaseDisabled }}
                                                data-product-id="{{ $item['id'] }}">
                                            <i class="fas fa-minus text-xs text-gray-600"></i>
                                        </button>
                                        
                                        <!-- QUANTITY INPUT -->
                                        <input type="number" 
                                               id="quantity-{{ $item['id'] }}"
                                               value="{{ $currentQuantity }}" 
                                               min="1"
                                               max="{{ $maxStock }}"
                                               class="quantity-input w-16 h-10 text-center border-0 focus:outline-none text-sm font-medium"
                                               data-product-id="{{ $item['id'] }}"
                                               data-original-value="{{ $currentQuantity }}">
                                        
                                        <!-- INCREASE BUTTON -->
                                        <button type="button" 
                                                onclick="increaseQuantity({{ $item['id'] }})"
                                                class="increase-btn w-10 h-10 bg-gray-50 hover:bg-gray-100 flex items-center justify-center transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                                {{ $increaseDisabled }}
                                                data-product-id="{{ $item['id'] }}">
                                            <i class="fas fa-plus text-xs text-gray-600"></i>
                                        </button>
                                    </div>

                                    <!-- Subtotal -->
                                    <div class="text-center">
                                        <p class="text-sm text-gray-500">Subtotal</p>
                                        @php
                                            $subtotal = $item['subtotal'];
                                            $formattedSubtotal = number_format($subtotal, 0, ',', '.');
                                        @endphp
                                        <p class="font-bold text-gray-900" id="subtotal-{{ $item['id'] }}">
                                            Rp {{ $formattedSubtotal }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Remove Button -->
                                <div class="flex flex-col space-y-2">
                                    @php
                                        $productName = addslashes($item['name']);
                                    @endphp
                                    <button type="button" 
                                            onclick="removeFromCart({{ $item['id'] }}, '{{ $productName }}')"
                                            class="text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50 transition-all"
                                            title="Remove from cart">
                                        <i class="fas fa-trash text-lg"></i>
                                    </button>
                                    
                                    @if($item['slug'])
                                        @php
                                            $productUrl = route('products.show', $item['slug']);
                                        @endphp
                                        <a href="{{ $productUrl }}" 
                                           class="text-gray-600 hover:text-gray-800 p-2 rounded-lg hover:bg-gray-50 transition-all"
                                           title="View product">
                                            <i class="fas fa-eye text-lg"></i>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Cart Actions -->
                <div class="mt-6 flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0 bg-white rounded-2xl p-6 border border-gray-100">
                    @php
                        $productsUrl = route('products.index');
                    @endphp
                    <a href="{{ $productsUrl }}" class="flex items-center text-blue-600 hover:text-blue-800 font-medium transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>Continue Shopping
                    </a>
                    
                    <div class="flex space-x-4">
                        <button type="button"
                                onclick="syncCart()"
                                class="flex items-center text-gray-600 hover:text-gray-800 font-medium transition-colors">
                            <i class="fas fa-sync-alt mr-2"></i>Update Prices
                        </button>
                        
                        <button type="button"
                                onclick="clearCart()"
                                class="flex items-center text-red-600 hover:text-red-800 font-medium transition-colors">
                            <i class="fas fa-trash mr-2"></i>Clear Cart
                        </button>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl shadow-md p-6 sticky top-4 border border-gray-100">
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">Order Summary</h2>
                    
                    <div class="space-y-4 mb-6">
                        @php
                            $totalItems = $cartItems->count();
                            $totalQuantity = $cartItems->sum('quantity');
                            $formattedTotal = number_format($total, 0, ',', '.');
                        @endphp
                        
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Items ({{ $totalItems }}):</span>
                            <span class="font-medium">{{ $totalQuantity }} pcs</span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-gray-600">Subtotal:</span>
                            <span class="font-semibold" id="cartTotal">Rp {{ $formattedTotal }}</span>
                        </div>
                        
                        <div class="flex justify-between text-sm text-gray-500">
                            <span>Shipping:</span>
                            <span>Calculated at checkout</span>
                        </div>
                        
                        <div class="flex justify-between text-sm text-gray-500">
                            <span>Tax:</span>
                            <span>Calculated at checkout</span>
                        </div>
                        
                        <div class="border-t pt-4">
                            <div class="flex justify-between text-lg font-bold">
                                <span>Total:</span>
                                <span id="finalTotal">Rp {{ $formattedTotal }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <button type="button"
                            onclick="proceedToCheckout()"
                            class="w-full bg-black text-white py-4 rounded-xl hover:bg-gray-800 transition-colors font-medium text-center flex items-center justify-center">
                        <i class="fas fa-lock mr-2"></i>Proceed to Checkout
                    </button>

                    <!-- Security Badge -->
                    <div class="mt-4 text-center">
                        <p class="text-xs text-gray-500 flex items-center justify-center">
                            <i class="fas fa-shield-alt mr-1"></i>
                            Secure checkout with 256-bit SSL encryption
                        </p>
                    </div>

                    <!-- Payment Methods -->
                    <div class="mt-6 text-center">
                        <p class="text-xs text-gray-500 mb-2">We accept:</p>
                        <div class="flex justify-center space-x-2">
                            <i class="fab fa-cc-visa text-2xl text-blue-600"></i>
                            <i class="fab fa-cc-mastercard text-2xl text-red-600"></i>
                            <i class="fas fa-mobile-alt text-2xl text-green-600"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- Empty Cart -->
        <div class="text-center py-16">
            <div class="max-w-md mx-auto">
                <div class="mb-6">
                    <i class="fas fa-shopping-cart text-6xl text-gray-300"></i>
                </div>
                <h2 class="text-2xl font-semibold text-gray-600 mb-4">Your cart is empty</h2>
                <p class="text-gray-500 mb-8">Looks like you haven't added any items to your cart yet. Start browsing our amazing products!</p>
                <div class="space-y-4">
                    @php
                        $productsUrl = route('products.index');
                        $saleUrl = route('products.sale');
                        $featuredUrl = route('products.index', ['featured' => '1']);
                    @endphp
                    
                    <a href="{{ $productsUrl }}" 
                       class="inline-block bg-black text-white px-8 py-3 rounded-xl hover:bg-gray-800 transition-colors font-medium">
                        <i class="fas fa-search mr-2"></i>Start Shopping
                    </a>
                    <div class="flex justify-center space-x-4 text-sm">
                        <a href="{{ $saleUrl }}" class="text-red-600 hover:text-red-700 transition-colors">
                            <i class="fas fa-percent mr-1"></i>
                            Sale Items
                        </a>
                        <a href="{{ $featuredUrl }}" class="text-yellow-600 hover:text-yellow-700 transition-colors">
                            <i class="fas fa-star mr-1"></i>
                            Featured Products
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Toast Notification -->
<div id="toastNotification" class="fixed top-4 right-4 z-50 hidden">
    <div class="bg-white border border-gray-200 rounded-lg shadow-lg p-4 min-w-80">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <i id="toastIcon" class="fas fa-check-circle text-green-500"></i>
            </div>
            <div class="ml-3 flex-1">
                <p id="toastMessage" class="text-sm font-medium text-gray-900"></p>
            </div>
            <div class="ml-4 flex-shrink-0">
                <button type="button" onclick="hideToast()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Hidden data for JavaScript -->
<script id="cartData" type="application/json">
@php
    $cartData = [
        'checkoutUrl' => route('checkout.index'),
        'csrfToken' => csrf_token()
    ];
@endphp
{!! json_encode($cartData) !!}
</script>

<style>
/* Cart specific animations */
.cart-item {
    transition: all 0.3s ease;
}

.cart-item.removing {
    opacity: 0.5;
    transform: scale(0.98);
}

.cart-item.loading {
    opacity: 0.6;
    pointer-events: none;
}

/* Quantity button states */
.quantity-input:focus {
    outline: 2px solid #3b82f6;
    outline-offset: -2px;
}

.decrease-btn:disabled,
.increase-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.decrease-btn:not(:disabled):hover,
.increase-btn:not(:disabled):hover {
    background-color: #f3f4f6;
}

/* Toast animation */
#toastNotification {
    animation: slideInRight 0.3s ease-out;
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

/* Loading spinner */
.loading-spinner {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #3498db;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<!-- JAVASCRIPT LENGKAP -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== CART PAGE LOADED ===');
    
    // Get cart data from hidden script tag
    const cartDataElement = document.getElementById('cartData');
    if (!cartDataElement) {
        console.error('Cart data element not found');
        return;
    }
    
    let cartData;
    try {
        cartData = JSON.parse(cartDataElement.textContent);
    } catch (e) {
        console.error('Failed to parse cart data:', e);
        return;
    }
    
    const token = cartData.csrfToken;
    const checkoutUrl = cartData.checkoutUrl;

    console.log('CSRF token:', token ? 'Found' : 'Missing');
    console.log('Checkout URL:', checkoutUrl);

    // =================
    // MAIN FUNCTIONS
    // =================

    // Update cart function
    window.updateCart = function(productId, quantity) {
        console.log(`=== UPDATE CART ===`);
        console.log('Product ID:', productId);
        console.log('New Quantity:', quantity);
        
        const cartItemElement = document.querySelector(`[data-product-id="${productId}"]`);
        const quantityInput = document.getElementById(`quantity-${productId}`);
        const decreaseBtn = cartItemElement?.querySelector('.decrease-btn');
        const increaseBtn = cartItemElement?.querySelector('.increase-btn');
        
        if (!cartItemElement) {
            console.error('Cart item element not found');
            return;
        }
        
        // Show loading state
        cartItemElement.classList.add('loading');
        if (decreaseBtn) decreaseBtn.disabled = true;
        if (increaseBtn) increaseBtn.disabled = true;

        fetch(`/cart/${productId}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token
            },
            body: JSON.stringify({ quantity: quantity })
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            
            if (data.success) {
                // Update quantity input
                if (quantityInput) {
                    quantityInput.value = quantity;
                    quantityInput.setAttribute('data-original-value', quantity);
                }
                
                // Update subtotal
                const subtotalElement = document.getElementById(`subtotal-${productId}`);
                if (subtotalElement && data.subtotal) {
                    subtotalElement.textContent = `Rp ${new Intl.NumberFormat('id-ID').format(data.subtotal)}`;
                }

                // Update button states
                if (decreaseBtn) {
                    decreaseBtn.disabled = quantity <= 1;
                }
                if (increaseBtn && data.stock) {
                    increaseBtn.disabled = quantity >= data.stock;
                }

                // Update totals
                if (data.total) {
                    updateTotals(data.total);
                }
                if (data.cart_count !== undefined) {
                    updateCartCount(data.cart_count);
                }
                
                showToast(data.message || 'Cart updated successfully', 'success');
            } else {
                // Revert quantity on error
                if (quantityInput) {
                    const originalValue = quantityInput.getAttribute('data-original-value') || '1';
                    quantityInput.value = originalValue;
                }
                showToast(data.message || 'Failed to update cart', 'error');
            }
        })
        .catch(error => {
            console.error('Update cart error:', error);
            // Revert quantity on error
            if (quantityInput) {
                const originalValue = quantityInput.getAttribute('data-original-value') || '1';
                quantityInput.value = originalValue;
            }
            showToast('Something went wrong. Please try again.', 'error');
        })
        .finally(() => {
            // Remove loading state
            cartItemElement.classList.remove('loading');
            if (decreaseBtn) decreaseBtn.disabled = false;
            if (increaseBtn) increaseBtn.disabled = false;
        });
    };

    // Increase quantity
    window.increaseQuantity = function(productId) {
        console.log(`=== INCREASE QUANTITY ===`);
        console.log('Product ID:', productId);
        
        const input = document.getElementById(`quantity-${productId}`);
        if (!input) {
            console.error('Quantity input not found');
            return;
        }
        
        const currentQuantity = parseInt(input.value) || 1;
        const maxQuantity = parseInt(input.getAttribute('max')) || 999;
        
        console.log('Current:', currentQuantity, 'Max:', maxQuantity);
        
        if (currentQuantity < maxQuantity) {
            const newQuantity = currentQuantity + 1;
            console.log('New quantity:', newQuantity);
            updateCart(productId, newQuantity);
        } else {
            showToast('Maximum stock reached', 'info');
        }
    };

    // Decrease quantity
    window.decreaseQuantity = function(productId) {
        console.log(`=== DECREASE QUANTITY ===`);
        console.log('Product ID:', productId);
        
        const input = document.getElementById(`quantity-${productId}`);
        if (!input) {
            console.error('Quantity input not found');
            return;
        }
        
        const currentQuantity = parseInt(input.value) || 1;
        
        console.log('Current quantity:', currentQuantity);
        
        if (currentQuantity > 1) {
            const newQuantity = currentQuantity - 1;
            console.log('New quantity:', newQuantity);
            updateCart(productId, newQuantity);
        } else {
            showToast('Minimum quantity is 1', 'info');
        }
    };

    // Remove from cart
    window.removeFromCart = function(productId, productName) {
        console.log(`=== REMOVE FROM CART ===`);
        console.log('Product ID:', productId);
        console.log('Product Name:', productName);
        
        if (!confirm(`Remove "${productName}" from cart?`)) {
            return;
        }

        const cartItem = document.querySelector(`[data-product-id="${productId}"]`);
        if (cartItem) {
            cartItem.classList.add('removing');
        }

        fetch(`/cart/${productId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token
            }
        })
        .then(response => response.json())
        .then(data => {
            console.log('Remove response:', data);
            
            if (data.success) {
                setTimeout(() => {
                    if (cartItem) {
                        cartItem.remove();
                    }
                    
                    // Check if cart is empty
                    const remainingItems = document.querySelectorAll('[data-product-id]').length;
                    console.log('Remaining items:', remainingItems);
                    
                    if (remainingItems === 1) {
                        setTimeout(() => location.reload(), 500);
                    } else {
                        recalculateCartTotals();
                    }
                }, 300);
                
                updateCartCount(data.cart_count);
                showToast(data.message, 'info');
            } else {
                if (cartItem) {
                    cartItem.classList.remove('removing');
                }
                showToast(data.message || 'Failed to remove item', 'error');
            }
        })
        .catch(error => {
            console.error('Remove cart error:', error);
            if (cartItem) {
                cartItem.classList.remove('removing');
            }
            showToast('Something went wrong. Please try again.', 'error');
        });
    };

    // Clear cart
    window.clearCart = function() {
        if (!confirm('Are you sure you want to clear all items from your cart?')) {
            return;
        }

        fetch('/cart', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.message || 'Failed to clear cart', 'error');
            }
        })
        .catch(error => {
            console.error('Clear cart error:', error);
            showToast('Something went wrong. Please try again.', 'error');
        });
    };

    // Sync cart
    window.syncCart = function() {
        fetch('/cart/sync', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.updated) {
                    showToast('Cart updated with latest prices!', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast('Cart is already up to date', 'info');
                }
            } else {
                showToast('Failed to sync cart', 'error');
            }
        })
        .catch(error => {
            console.error('Sync cart error:', error);
            showToast('Something went wrong. Please try again.', 'error');
        });
    };

    // Proceed to checkout - FIXED STOCK CHECK
    window.proceedToCheckout = function() {
        // Check for items with zero stock
        const cartItems = document.querySelectorAll('.cart-item');
        let hasOutOfStockItems = false;
        
        cartItems.forEach(item => {
            const quantityInput = item.querySelector('.quantity-input');
            const maxStock = parseInt(quantityInput.getAttribute('max')) || 0;
            
            if (maxStock <= 0) {
                hasOutOfStockItems = true;
            }
        });
        
        if (hasOutOfStockItems) {
            showToast('Please remove out of stock items before checkout', 'error');
            return;
        }
        
        window.location.href = checkoutUrl;
    };

    // =================
    // EVENT LISTENERS
    // =================

    // Handle direct input changes
    document.querySelectorAll('.quantity-input').forEach(input => {
        console.log('Setting up input listener for:', input.id);
        
        input.addEventListener('change', function() {
            const productId = this.getAttribute('data-product-id');
            const quantity = parseInt(this.value) || 1;
            const minQuantity = parseInt(this.getAttribute('min')) || 1;
            const maxQuantity = parseInt(this.getAttribute('max')) || 999;
            
            console.log('Input change:', productId, 'quantity:', quantity);
            
            if (quantity < minQuantity) {
                this.value = minQuantity;
                showToast(`Minimum quantity is ${minQuantity}`, 'info');
                return;
            }
            
            if (quantity > maxQuantity) {
                this.value = maxQuantity;
                showToast(`Maximum quantity is ${maxQuantity}`, 'info');
                return;
            }
            
            const originalValue = parseInt(this.getAttribute('data-original-value')) || 1;
            if (quantity !== originalValue) {
                updateCart(productId, quantity);
            }
        });
        
        input.addEventListener('keypress', function(e) {
            if (!/[\d\b\t\x1b\r]/.test(String.fromCharCode(e.which))) {
                e.preventDefault();
            }
        });
        
        input.addEventListener('blur', function() {
            const quantity = parseInt(this.value) || 1;
            const minQuantity = parseInt(this.getAttribute('min')) || 1;
            if (quantity < minQuantity) {
                this.value = minQuantity;
            }
        });
    });

    // =================
    // HELPER FUNCTIONS
    // =================

    function updateTotals(total) {
        const formattedTotal = `Rp ${new Intl.NumberFormat('id-ID').format(total)}`;
        
        const cartTotalElement = document.getElementById('cartTotal');
        const finalTotalElement = document.getElementById('finalTotal');
        
        if (cartTotalElement) cartTotalElement.textContent = formattedTotal;
        if (finalTotalElement) finalTotalElement.textContent = formattedTotal;
    }

    function recalculateCartTotals() {
        let total = 0;
        document.querySelectorAll('[id^="subtotal-"]').forEach(element => {
            const subtotalText = element.textContent.replace(/[^\d]/g, '');
            total += parseInt(subtotalText) || 0;
        });
        updateTotals(total);
    }

    function updateCartCount(count) {
        const cartBadge = document.getElementById('cartCount');
        if (cartBadge) {
            cartBadge.textContent = count;
            cartBadge.style.display = count > 0 ? 'inline' : 'none';
        }
    }

    function showToast(message, type) {
        type = type || 'success';
        const toast = document.getElementById('toastNotification');
        const icon = document.getElementById('toastIcon');
        const messageEl = document.getElementById('toastMessage');
        
        if (!toast || !icon || !messageEl) {
            console.error('Toast elements not found');
            return;
        }
        
        messageEl.textContent = message;
        
        icon.className = 'fas ';
        switch(type) {
            case 'success':
                icon.className += 'fa-check-circle text-green-500';
                break;
            case 'error':
                icon.className += 'fa-exclamation-circle text-red-500';
                break;
            case 'info':
                icon.className += 'fa-info-circle text-blue-500';
                break;
            default:
                icon.className += 'fa-check-circle text-green-500';
        }
        
        toast.classList.remove('hidden');
        setTimeout(() => hideToast(), 3000);
    }

    window.hideToast = function() {
        const toast = document.getElementById('toastNotification');
        if (toast) {
            toast.classList.add('hidden');
        }
    };

    // =================
    // INITIALIZATION
    // =================

    // Test all buttons on page load
    console.log('=== TESTING BUTTONS ===');
    
    const decreaseButtons = document.querySelectorAll('.decrease-btn');
    const increaseButtons = document.querySelectorAll('.increase-btn');
    const quantityInputs = document.querySelectorAll('.quantity-input');
    
    console.log('Decrease buttons found:', decreaseButtons.length);
    console.log('Increase buttons found:', increaseButtons.length);
    console.log('Quantity inputs found:', quantityInputs.length);
    
    // Test button clicks
    decreaseButtons.forEach((btn, index) => {
        const productId = btn.getAttribute('data-product-id');
        console.log(`Decrease button ${index + 1}: Product ID = ${productId}`);
        
        // Test click event
        btn.addEventListener('click', function() {
            console.log('Decrease button clicked via event listener');
        });
    });
    
    increaseButtons.forEach((btn, index) => {
        const productId = btn.getAttribute('data-product-id');
        console.log(`Increase button ${index + 1}: Product ID = ${productId}`);
        
        // Test click event
        btn.addEventListener('click', function() {
            console.log('Increase button clicked via event listener');
        });
    });

    // Auto-sync cart after 2 seconds
    setTimeout(() => {
        console.log('=== AUTO-SYNC CART ===');
        fetch('/cart/sync', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token
            }
        })
        .then(response => response.json())
        .then(data => {
            console.log('Auto-sync result:', data);
            if (data.updated) {
                console.log('Cart was updated, reloading...');
                location.reload();
            }
        })
        .catch(error => {
            console.log('Auto-sync failed:', error);
        });
    }, 2000);

    console.log('=== CART INITIALIZATION COMPLETE ===');
});

// =================
// GLOBAL TEST FUNCTIONS
// =================

// Test functions yang bisa dipanggil dari console browser
window.testIncrease = function(productId) {
    console.log('Testing increase for product:', productId);
    increaseQuantity(productId);
};

window.testDecrease = function(productId) {
    console.log('Testing decrease for product:', productId);
    decreaseQuantity(productId);
};

window.testUpdate = function(productId, quantity) {
    console.log('Testing update for product:', productId, 'quantity:', quantity);
    updateCart(productId, quantity);
};

// Debug function untuk melihat semua data cart
window.debugCart = function() {
    console.log('=== CART DEBUG INFO ===');
    
    const cartItems = document.querySelectorAll('[data-product-id]');
    console.log('Cart items found:', cartItems.length);
    
    cartItems.forEach((item, index) => {
        const productId = item.getAttribute('data-product-id');
        const quantityInput = document.getElementById(`quantity-${productId}`);
        const decreaseBtn = item.querySelector('.decrease-btn');
        const increaseBtn = item.querySelector('.increase-btn');
        
        console.log(`Item ${index + 1}:`, {
            productId: productId,
            currentQuantity: quantityInput ? quantityInput.value : 'NOT FOUND',
            maxStock: quantityInput ? quantityInput.getAttribute('max') : 'NOT FOUND',
            decreaseDisabled: decreaseBtn ? decreaseBtn.disabled : 'NOT FOUND',
            increaseDisabled: increaseBtn ? increaseBtn.disabled : 'NOT FOUND'
        });
    });
    
    console.log('=== END DEBUG ===');
};
</script>
@endsection