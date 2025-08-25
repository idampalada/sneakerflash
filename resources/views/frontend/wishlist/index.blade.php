@extends('layouts.app')

@section('title', 'My Wishlist - SneakerFlash')

@section('content')
    <!-- Page Header -->
    <section class="bg-white py-6 border-b border-gray-200">
        <div class="container mx-auto px-4">
            <nav class="text-sm mb-4">
                <ol class="flex space-x-2 text-gray-600">
                    <li><a href="/" class="hover:text-blue-600">Home</a></li>
                    <li>/</li>
                    <li class="text-gray-900">My Wishlist</li>
                </ol>
            </nav>
            
            <div class="flex justify-between items-center">
                <h1 class="text-3xl font-bold text-gray-900">My Wishlist</h1>
                <div class="text-gray-600">
                    <span id="wishlistItemCount">{{ $wishlists->count() }}</span> items
                </div>
            </div>
        </div>
    </section>

    <div class="container mx-auto px-4 py-8">
        @if($wishlists->count() > 0)
            <!-- Wishlist Actions Bar -->
            <div class="bg-white rounded-2xl p-6 mb-6 border border-gray-100">
                <div class="flex justify-between items-center">
                    <div class="flex items-center space-x-4">
                        <span class="text-gray-600 font-medium">{{ $wishlists->count() }} products in your wishlist</span>
                    </div>
                    
                    <div class="flex items-center space-x-3">
                        <button type="button" id="clearWishlistBtn" class="border border-red-300 text-red-600 px-6 py-2 rounded-lg hover:bg-red-50 transition-colors font-medium">
                            <i class="fas fa-trash mr-2"></i>
                            Clear Wishlist
                        </button>
                    </div>
                </div>
            </div>

            <!-- DEBUG: Raw Controller Data -->
<div style="background: red; color: white; padding: 10px; margin: 10px;">
    <strong>DEBUG RAW DATA FROM CONTROLLER</strong><br>
    Wishlists count: {{ $wishlists->count() }}<br>
    @if($wishlists->count() > 0)
        @php $firstWishlist = $wishlists->first(); @endphp
        First product ID: {{ $firstWishlist->product->id ?? 'NULL' }}<br>
        Has size_variants: {{ isset($firstWishlist->product->size_variants) ? 'YES' : 'NO' }}<br>
        Size variants type: {{ gettype($firstWishlist->product->size_variants ?? null) }}<br>
        Size variants count: {{ ($firstWishlist->product->size_variants ?? collect())->count() }}<br>
        @if(isset($firstWishlist->product->size_variants))
            Raw size_variants: {{ json_encode($firstWishlist->product->size_variants) }}<br>
        @endif
    @endif
</div>

            <!-- Wishlist Items Grid - Same as Products Grid -->
            <div id="wishlistGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach($wishlists as $wishlist)
                    @if($wishlist->product)
                        @php
                            $product = $wishlist->product;
                            $productPrice = $product->price ?? 0;
                            $salePrice = $product->sale_price ?? null;
                            $finalPrice = $salePrice && $salePrice < $productPrice ? $salePrice : $productPrice;
                            $cleanProductName = $product->name ?? 'Unknown Product';
                            
                            // Use size_variants from controller processing
                            $sizeVariants = $product->size_variants ?? collect();
                            $hasVariants = $sizeVariants->count() > 1; // Only show "Select Size" if multiple sizes
                        @endphp
                        
                        <div class="product-card wishlist-item group bg-white rounded-2xl overflow-hidden border border-gray-100 hover:border-gray-200 hover:shadow-lg transition-all duration-300 relative">
                            <!-- Product Image -->
                            <div class="relative aspect-square overflow-hidden">
                                <a href="{{ route('products.show', $product->slug ?? $product->id) }}">
                                    @if($product->image && file_exists(public_path('storage/' . $product->image)))
                                        <img src="{{ asset('storage/' . $product->image) }}" 
                                             alt="{{ $cleanProductName }}"
                                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                    @else
                                        <div class="w-full h-full bg-gray-100 flex items-center justify-center">
                                            <i class="fas fa-image text-4xl text-gray-300"></i>
                                        </div>
                                    @endif
                                </a>
                                
                                <!-- Badges -->
                                <div class="absolute top-3 left-3 flex flex-col gap-2 z-10">
                                    @if($salePrice && $salePrice < $productPrice)
                                        @php
                                            $discount = round((($productPrice - $salePrice) / $productPrice) * 100);
                                        @endphp
                                        <span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full font-medium">
                                            -{{ $discount }}%
                                        </span>
                                    @endif
                                    @if(($product->stock_quantity ?? 0) <= 0)
                                        <span class="bg-gray-500 text-white text-xs px-2 py-1 rounded-full font-medium">
                                            Out of Stock
                                        </span>
                                    @elseif(($product->stock_quantity ?? 0) < 10)
                                        <span class="bg-orange-500 text-white text-xs px-2 py-1 rounded-full font-medium">
                                            Low Stock
                                        </span>
                                    @endif
                                </div>

                                <!-- Remove from wishlist button -->
                                <button type="button" class="remove-wishlist-btn absolute top-3 right-3 w-8 h-8 bg-white rounded-full flex items-center justify-center shadow-md hover:shadow-lg transition-all duration-200" 
                                        data-product-id="{{ $product->id }}"
                                        data-product-name="{{ $cleanProductName }}"
                                        title="Remove from wishlist">
                                    <i class="fas fa-times text-gray-600 hover:text-red-500 transition-colors"></i>
                                </button>

                                <!-- Added to wishlist date -->
                                <div class="absolute bottom-3 left-3">
                                    <span class="bg-black bg-opacity-50 text-white text-xs px-2 py-1 rounded-full">
                                        Added {{ $wishlist->created_at->diffForHumans() }}
                                    </span>
                                </div>

                                <!-- Debug: Check size_variants data -->
                                @if(config('app.debug'))
                                    <!-- Debug info (only in development) -->
                                    <div class="text-xs text-red-500 mb-2 p-2 bg-red-50 rounded">
                                        <strong>DEBUG INFO:</strong><br>
                                        hasVariants: {{ $hasVariants ? 'TRUE' : 'FALSE' }}<br>
                                        sizeVariants count: {{ $sizeVariants->count() }}<br>
                                        totalStock: {{ $product->total_stock ?? 'NULL' }}<br>
                                        product.id: {{ $product->id }}<br>
                                        @if($sizeVariants->count() > 0)
                                            Sizes available: {{ $sizeVariants->pluck('size')->implode(', ') }}<br>
                                            First variant: {{ json_encode($sizeVariants->first()) }}<br>
                                            All variants: {{ json_encode($sizeVariants->toArray()) }}
                                        @endif
                                    </div>
                                @endif

                                <!-- Hidden size data for modal (sama seperti products/index.blade.php) -->
                                @if($sizeVariants->count() > 1)
                                    <div class="mb-3">
                                        <span class="text-xs text-gray-500 font-medium">Available Sizes:</span>
                                        <div class="flex flex-wrap gap-1 mt-1" id="sizeContainer-{{ $product->id }}">
                                            @foreach($sizeVariants as $variant)
                                                @php
                                                    $size = $variant['size'] ?? 'Unknown';
                                                    $stock = (int) ($variant['stock'] ?? 0);
                                                    $variantId = $variant['id'] ?? '';
                                                    $sku = $variant['sku'] ?? '';
                                                    $isAvailable = $stock > 0;
                                                    $variantPrice = $variant['price'] ?? $finalPrice;
                                                    $variantOriginalPrice = $variant['original_price'] ?? $productPrice;
                                                @endphp
                                                <span class="size-badge text-xs px-2 py-1 rounded border {{ $isAvailable ? 'text-gray-700 bg-gray-50 border-gray-200 hover:bg-blue-50 hover:border-blue-300' : 'text-gray-400 bg-gray-100 border-gray-200 line-through' }}" 
                                                      data-size="{{ $size }}" 
                                                      data-stock="{{ $stock }}"
                                                      data-product-id="{{ $variantId }}"
                                                      data-sku="{{ $sku }}"
                                                      data-available="{{ $isAvailable ? 'true' : 'false' }}"
                                                      data-price="{{ $variantPrice }}"
                                                      data-original-price="{{ $variantOriginalPrice }}">
                                                    {{ $size }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @else
                                    <!-- Single size product - create hidden container for consistency -->
                                    <div id="sizeContainer-{{ $product->id }}" class="hidden">
                                        @if($sizeVariants->count() > 0)
                                            @php
                                                $variant = $sizeVariants->first();
                                                $size = $variant['size'] ?? 'One Size';
                                                $stock = (int) ($variant['stock'] ?? 0);
                                                $variantId = $variant['id'] ?? $product->id;
                                                $sku = $variant['sku'] ?? '';
                                                $isAvailable = $stock > 0;
                                                $variantPrice = $variant['price'] ?? $finalPrice;
                                                $variantOriginalPrice = $variant['original_price'] ?? $productPrice;
                                            @endphp
                                            <span class="size-badge" 
                                                  data-size="{{ $size }}" 
                                                  data-stock="{{ $stock }}"
                                                  data-product-id="{{ $variantId }}"
                                                  data-sku="{{ $sku }}"
                                                  data-available="{{ $isAvailable ? 'true' : 'false' }}"
                                                  data-price="{{ $variantPrice }}"
                                                  data-original-price="{{ $variantOriginalPrice }}">
                                                {{ $size }}
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                            
                            <!-- Product Details -->
                            <div class="p-4">
                                <div class="mb-2">
                                    <span class="text-xs text-gray-500 uppercase tracking-wide">
                                        {{ $product->category->name ?? 'Products' }}
                                        @if($product->brand)
                                            • {{ $product->brand }}
                                        @endif
                                    </span>
                                </div>
                                
                                <h3 class="font-semibold text-gray-900 mb-3 text-sm leading-tight line-clamp-2">
                                    <a href="{{ route('products.show', $product->slug ?? $product->id) }}" 
                                       class="hover:text-blue-600 transition-colors">
                                        {{ $cleanProductName }}
                                    </a>
                                </h3>
                                
                                <!-- Display available sizes and colors if exist -->
                                @if($sizeVariants->count() > 0 || $product->available_colors)
                                    <div class="mb-3 text-xs text-gray-500">
                                        @if($sizeVariants->count() > 0)
                                            <div class="mb-1">
                                                <span class="font-medium">Sizes:</span>
                                                @php
                                                    $sizesPreview = $sizeVariants->pluck('size')->take(3);
                                                    $remainingSizes = $sizeVariants->count() - 3;
                                                @endphp
                                                {{ $sizesPreview->implode(', ') }}
                                                @if($remainingSizes > 0)
                                                    <span class="text-gray-400">+{{ $remainingSizes }} more</span>
                                                @endif
                                            </div>
                                        @endif
                                        @if($product->available_colors)
                                            <div>
                                                <span class="font-medium">Colors:</span>
                                                {{ is_array($product->available_colors) ? implode(', ', array_slice($product->available_colors, 0, 3)) : $product->available_colors }}
                                                @if(is_array($product->available_colors) && count($product->available_colors) > 3)
                                                    <span class="text-gray-400">+{{ count($product->available_colors) - 3 }} more</span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                @endif
                                
                                <!-- Price -->
                                <div class="mb-4">
                                    @if($salePrice && $salePrice < $productPrice)
                                        <div class="flex items-center space-x-2">
                                            <span class="text-lg font-bold text-red-600">
                                                Rp {{ number_format($salePrice, 0, ',', '.') }}
                                            </span>
                                            <span class="text-sm text-gray-400 line-through">
                                                Rp {{ number_format($productPrice, 0, ',', '.') }}
                                            </span>
                                        </div>
                                    @else
                                        <span class="text-lg font-bold text-gray-900">
                                            Rp {{ number_format($productPrice, 0, ',', '.') }}
                                        </span>
                                    @endif
                                </div>
                                
                                <!-- Action Buttons - Same as Products Index -->
                                <div class="flex items-center space-x-2">
                                    @if(($product->stock_quantity ?? 0) > 0)
                                        @if($hasVariants)
                                            <!-- Size Selection Button (same as products/index.blade.php) -->
                                            <button type="button" 
                                                    class="size-select-btn flex-1 bg-gray-900 text-white py-2 px-3 rounded-lg text-sm font-medium hover:bg-gray-800 transition-colors"
                                                    data-product-id="{{ $product->id }}"
                                                    data-sku-parent="{{ $product->sku_parent ?? '' }}"
                                                    data-product-name="{{ $cleanProductName }}"
                                                    data-price="{{ $finalPrice }}"
                                                    data-original-price="{{ $productPrice }}">
                                                <i class="fas fa-shopping-cart mr-1"></i>
                                                Select Size
                                            </button>
                                        @else
                                            <!-- Direct Add to Cart -->
                                            <form action="{{ route('cart.add') }}" method="POST" class="add-to-cart-form flex-1">
                                                @csrf
                                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                                <input type="hidden" name="quantity" value="1">
                                                <button type="submit" class="w-full bg-gray-900 text-white py-2 px-3 rounded-lg text-sm font-medium hover:bg-gray-800 transition-colors">
                                                    <i class="fas fa-shopping-cart mr-1"></i>
                                                    Add to Cart
                                                </button>
                                            </form>
                                        @endif
                                    @else
                                        <button disabled class="flex-1 bg-gray-300 text-gray-500 py-2 px-3 rounded-lg text-sm font-medium cursor-not-allowed">
                                            <i class="fas fa-times mr-1"></i>
                                            Out of Stock
                                        </button>
                                    @endif
                                    <a href="{{ route('products.show', $product->slug ?? $product->id) }}" class="px-3 py-2 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors flex items-center justify-center">
                                        <i class="fas fa-eye text-gray-600"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            <!-- Continue Shopping -->
            <div class="text-center mt-8">
                <a href="{{ route('products.index') }}" class="inline-flex items-center px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Continue Shopping
                </a>
            </div>

        @else
            <!-- Empty Wishlist State -->
            <div class="bg-white rounded-2xl p-12 text-center border border-gray-100">
                <div class="max-w-md mx-auto">
                    <div class="mb-6">
                        <i class="fas fa-heart text-6xl text-gray-300"></i>
                    </div>
                    <h3 class="text-2xl font-semibold text-gray-900 mb-4">Your wishlist is empty</h3>
                    <p class="text-gray-500 mb-8">
                        Looks like you haven't added any products to your wishlist yet. 
                        Start browsing and add your favorite items!
                    </p>
                    <div class="space-y-4">
                        <a href="{{ route('products.index') }}" class="block bg-black text-white px-8 py-3 rounded-lg hover:bg-gray-800 transition-colors font-medium">
                            <i class="fas fa-search mr-2"></i>
                            Browse Products
                        </a>
                        <div class="flex justify-center space-x-4 text-sm">
                            <a href="{{ route('products.sale') }}" class="text-red-600 hover:text-red-700 transition-colors">
                                <i class="fas fa-percent mr-1"></i>
                                Sale Items
                            </a>
                            <a href="{{ route('products.index', ['featured' => '1']) }}" class="text-yellow-600 hover:text-yellow-700 transition-colors">
                                <i class="fas fa-star mr-1"></i>
                                Featured Products
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Size Selection Modal (Same as products/index.blade.php) -->
    <div id="sizeSelectionModal" class="fixed inset-0 bg-black bg-opacity-75 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white rounded-t-2xl border-b border-gray-200 p-6 z-10">
                <div class="flex items-center justify-between">
                    <h3 id="modalProductName" class="text-xl font-bold text-gray-900">Select Size</h3>
                    <button id="closeModalBtn" type="button" class="text-gray-400 hover:text-gray-600 transition-colors p-2">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            
            <div class="p-6">
                <!-- Size Options Grid -->
                <div class="mb-6">
                    <div id="sizeOptionsContainer" class="grid grid-cols-4 gap-3">
                        <!-- Size options will be populated here -->
                    </div>
                </div>
                
                <!-- Selected Size Info -->
                <div class="mb-6 hidden" id="selectedSizeInfo">
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-semibold text-blue-900">Selected Size:</span>
                            <span id="selectedSizeDisplay" class="text-lg font-bold text-blue-700"></span>
                        </div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-blue-600">Available Stock:</span>
                            <span id="selectedSizeStock" class="text-sm font-medium text-blue-700"></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-blue-600">Price:</span>
                            <span id="selectedSizePrice" class="text-sm font-semibold text-blue-700">-</span>
                        </div>
                    </div>
                </div>
                
                <!-- Add to Cart Form -->
                <form id="sizeAddToCartForm" action="{{ route('cart.add') }}" method="POST" class="hidden">
                    @csrf
                    <input type="hidden" name="product_id" id="selectedProductId">
                    <input type="hidden" name="quantity" value="1">
                    <input type="hidden" name="size" id="selectedSizeValue">
                    
                    <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white py-4 rounded-xl font-semibold hover:from-blue-700 hover:to-indigo-700 transition-all duration-300 transform hover:scale-105 shadow-lg">
                        <i class="fas fa-shopping-cart mr-2"></i>
                        Add to Cart
                    </button>
                </form>
            </div>
            
            <!-- Modal Footer -->
            <div class="bg-gray-50 px-6 py-3 rounded-b-2xl">
                <p class="text-xs text-center text-gray-500">
                    <i class="fas fa-info-circle mr-1"></i>
                    Select a size to continue with your purchase
                </p>
            </div>
        </div>
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

    <!-- Styles -->
    <style>
    /* Wishlist specific styles */
    .wishlist-item {
        transition: all 0.3s ease;
    }

    .wishlist-item:hover {
        transform: translateY(-2px);
    }

    .remove-wishlist-btn:hover {
        transform: scale(1.1);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }

    /* Animation for removing items */
    .wishlist-item.removing {
        opacity: 0.5;
        transform: scale(0.95);
        transition: all 0.3s ease;
    }

    /* Size option styles */
    .size-option {
        transition: all 0.2s ease;
    }

    .size-option:hover:not(.disabled) {
        border-color: #3B82F6;
        background-color: #EFF6FF;
    }

    .size-option.selected {
        border-color: #3B82F6 !important;
        background-color: #EFF6FF !important;
        color: #1D4ED8;
    }

    .size-option.disabled {
        background-color: #F3F4F6;
        color: #9CA3AF;
        cursor: not-allowed;
    }

    /* Line clamp utility */
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    </style>

    <!-- Enhanced JavaScript with Size Selection (Same as products/index.blade.php) -->
    <script>
    console.log('🚀 Enhanced Wishlist JavaScript with Size Selection...');

    window.addEventListener('load', function() {
        setupSizeSelection();
        setupWishlistActions();
    });

    function setupSizeSelection() {
        document.addEventListener('click', handleClick);
    }

    function handleClick(e) {
        // Size select button
        if (e.target.closest('.size-select-btn')) {
            e.preventDefault();
            openSizeModal(e.target.closest('.size-select-btn'));
            return;
        }
        
        // Close modal
        if (e.target.id === 'closeModalBtn' || e.target.closest('#closeModalBtn') || e.target.id === 'sizeSelectionModal') {
            closeModal();
            return;
        }
        
        // Size option
        if (e.target.closest('.size-option')) {
            var option = e.target.closest('.size-option');
            if (!option.classList.contains('disabled')) {
                selectSize(option);
            }
            return;
        }
    }

    function openSizeModal(button) {
        var productId = button.getAttribute('data-product-id');
        var productName = button.getAttribute('data-product-name');
        var defaultPrice = button.getAttribute('data-price') || '0';
        
        console.log('🔍 Opening modal for:', productName, 'Product ID:', productId, 'Price:', defaultPrice);
        
        var modal = document.getElementById('sizeSelectionModal');
        var title = document.getElementById('modalProductName');
        var container = document.getElementById('sizeOptionsContainer');
        
        if (!modal || !container) {
            console.log('❌ Modal or container not found');
            return;
        }
        
        // Set title
        if (title) title.textContent = 'Select Size - ' + productName;
        
        // Find the correct product card - could be .product-card or .wishlist-item
        var productCard = button.closest('.product-card') || button.closest('.wishlist-item');
        console.log('📦 Product card found:', !!productCard);
        
        if (!productCard) {
            console.log('❌ Product card not found');
            return;
        }
        
        // Try to find size container with exact ID match
        var sizeContainer = productCard.querySelector('#sizeContainer-' + productId);
        console.log('📏 Size container found:', !!sizeContainer);
        
        // Debug: Log the innerHTML of sizeContainer if found
        if (sizeContainer) {
            console.log('📝 Size container HTML:', sizeContainer.innerHTML);
            console.log('📝 Size container children:', sizeContainer.children.length);
            
            // Debug each child element
            Array.from(sizeContainer.children).forEach((child, idx) => {
                console.log(`📝 Child ${idx}:`, {
                    tagName: child.tagName,
                    className: child.className,
                    attributes: {
                        'data-size': child.getAttribute('data-size'),
                        'data-stock': child.getAttribute('data-stock'),
                        'data-product-id': child.getAttribute('data-product-id'),
                        'data-available': child.getAttribute('data-available')
                    }
                });
            });
        } else {
            // Try to find any element with id starting with sizeContainer
            var allSizeContainers = productCard.querySelectorAll('[id^="sizeContainer-"]');
            console.log('🔍 Found', allSizeContainers.length, 'size containers in product card');
            allSizeContainers.forEach(function(container, index) {
                console.log(`📋 Container ${index}:`, {
                    id: container.id,
                    innerHTML: container.innerHTML.substring(0, 200),
                    children: container.children.length
                });
            });
        }
        
        // Clear container first
        container.innerHTML = '';
        
        if (!sizeContainer) {
            console.log('❌ Size container not found, creating error message');
            container.innerHTML = '<div class="col-span-4 text-center text-gray-500 p-4">Size data not available</div>';
        } else {
            var badges = sizeContainer.querySelectorAll('.size-badge');
            console.log('🏷️ Size badges found:', badges.length);
            
            if (badges.length === 0) {
                console.log('❌ No size badges found in container');
                container.innerHTML = '<div class="col-span-4 text-center text-gray-500 p-4">No sizes available</div>';
            } else {
                console.log('✅ Processing', badges.length, 'size badges');
                
                badges.forEach(function(badge, index) {
                    var size = badge.getAttribute('data-size');
                    var stock = badge.getAttribute('data-stock');
                    var productVariantId = badge.getAttribute('data-product-id');
                    var available = badge.getAttribute('data-available') === 'true';
                    var price = badge.getAttribute('data-price') || defaultPrice;
                    var originalPrice = badge.getAttribute('data-original-price') || defaultPrice;
                    
                    console.log(`📋 Size ${index + 1}:`, {
                        size: size,
                        stock: stock,
                        productVariantId: productVariantId,
                        available: available,
                        price: price
                    });
                    
                    if (!size || !productVariantId) {
                        console.log('⚠️ Missing required data for size option', {size, productVariantId});
                        return;
                    }
                    
                    var div = document.createElement('div');
                    div.className = 'size-option cursor-pointer p-4 border-2 rounded-lg text-center transition-all ' + 
                        (available ? 'border-gray-300 hover:border-blue-500' : 'disabled border-gray-200 bg-gray-50');
                    
                    div.setAttribute('data-product-id', productVariantId);
                    div.setAttribute('data-size', size);
                    div.setAttribute('data-stock', stock);
                    div.setAttribute('data-price', price);
                    div.setAttribute('data-original-price', originalPrice);
                    
                    div.innerHTML = `
                        <div class="font-semibold text-lg ${available ? 'text-gray-900' : 'text-gray-400'}">${size}</div>
                        <div class="text-xs mt-1 ${available ? 'text-gray-600' : 'text-gray-400'}">
                            ${available ? stock + ' available' : 'Out of stock'}
                        </div>
                    `;
                    
                    container.appendChild(div);
                });
                
                console.log('✅ Size options created successfully');
            }
        }
        
        // Show modal
        modal.classList.remove('hidden');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        console.log('✅ Modal displayed');
    }

    function selectSize(element) {
        var productId = element.getAttribute('data-product-id');
        var size = element.getAttribute('data-size');
        var stock = element.getAttribute('data-stock');
        var price = element.getAttribute('data-price');
        
        console.log('📏 Size selected:', size, 'Stock:', stock, 'Price:', price);
        
        // Remove previous selections
        document.querySelectorAll('.size-option').forEach(function(opt) {
            opt.classList.remove('selected');
        });
        
        // Select current option
        element.classList.add('selected');
        
        // Update UI elements
        var sizeInfo = document.getElementById('selectedSizeInfo');
        var sizeDisplay = document.getElementById('selectedSizeDisplay');
        var sizeStock = document.getElementById('selectedSizeStock');
        var sizePriceElement = document.getElementById('selectedSizePrice');
        var form = document.getElementById('sizeAddToCartForm');
        var productInput = document.getElementById('selectedProductId');
        var sizeInput = document.getElementById('selectedSizeValue');
        
        if (sizeDisplay) sizeDisplay.textContent = size;
        if (sizeStock) sizeStock.textContent = stock + ' available';
        
        // Update price
        if (sizePriceElement) {
            var formattedPrice = 'Rp ' + new Intl.NumberFormat('id-ID').format(parseInt(price));
            sizePriceElement.textContent = formattedPrice;
            console.log('💰 Price updated to:', formattedPrice);
        }
        
        if (productInput) productInput.value = productId;
        if (sizeInput) sizeInput.value = size;
        
        if (sizeInfo) sizeInfo.classList.remove('hidden');
        if (form) form.classList.remove('hidden');
    }

    function closeModal() {
        var modal = document.getElementById('sizeSelectionModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
        
        // Reset form
        var form = document.getElementById('sizeAddToCartForm');
        var sizeInfo = document.getElementById('selectedSizeInfo');
        if (form) form.classList.add('hidden');
        if (sizeInfo) sizeInfo.classList.add('hidden');
        
        // Clear selections
        document.querySelectorAll('.size-option').forEach(function(opt) {
            opt.classList.remove('selected');
        });
    }

    function setupWishlistActions() {
        const WISHLIST_CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        // Remove from wishlist functionality
        document.addEventListener('click', function(e) {
            if (e.target.closest('.remove-wishlist-btn')) {
                e.preventDefault();
                e.stopPropagation();
                
                const btn = e.target.closest('.remove-wishlist-btn');
                const productId = btn.dataset.productId;
                const productName = btn.dataset.productName || 'Product';
                
                if (!productId) return;
                
                if (!confirm(`Remove ${productName} from your wishlist?`)) return;
                
                btn.disabled = true;
                const wishlistItem = btn.closest('.wishlist-item');
                if (wishlistItem) {
                    wishlistItem.classList.add('removing');
                }

                fetch(`/wishlist/remove/${productId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': WISHLIST_CSRF,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (wishlistItem) {
                            wishlistItem.remove();
                        }
                        updateWishlistCount();
                        showToast(`${productName} removed from wishlist`, 'success');
                    } else {
                        showToast(data.message || 'Failed to remove from wishlist', 'error');
                        btn.disabled = false;
                        if (wishlistItem) {
                            wishlistItem.classList.remove('removing');
                        }
                    }
                })
                .catch(error => {
                    console.error('Remove wishlist error:', error);
                    showToast('Failed to remove from wishlist', 'error');
                    btn.disabled = false;
                    if (wishlistItem) {
                        wishlistItem.classList.remove('removing');
                    }
                });
            }
        });

        // Clear wishlist functionality
        const clearBtn = document.getElementById('clearWishlistBtn');
        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                if (!confirm('Are you sure you want to clear your entire wishlist?')) return;
                
                this.disabled = true;
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Clearing...';

                fetch('/wishlist/clear', {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': WISHLIST_CSRF,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Wishlist cleared successfully', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast(data.message || 'Failed to clear wishlist', 'error');
                        this.disabled = false;
                        this.innerHTML = originalText;
                    }
                })
                .catch(error => {
                    console.error('Clear wishlist error:', error);
                    showToast('Failed to clear wishlist', 'error');
                    this.disabled = false;
                    this.innerHTML = originalText;
                });
            });
        }
    }

    // Update wishlist count
    function updateWishlistCount() {
        const wishlistItems = document.querySelectorAll('.wishlist-item').length;
        const countElement = document.getElementById('wishlistItemCount');
        if (countElement) {
            countElement.textContent = wishlistItems;
        }
        
        // Update header badge
        const headerBadge = document.getElementById('wishlistCount');
        if (headerBadge) {
            headerBadge.textContent = wishlistItems;
            headerBadge.style.display = wishlistItems > 0 ? 'inline' : 'none';
        }
        
        // Show empty state if no items
        if (wishlistItems === 0) {
            setTimeout(() => location.reload(), 2000);
        }
    }

    // Toast notification functions
    function showToast(message, type = 'success') {
        const toast = document.getElementById('toastNotification');
        const icon = document.getElementById('toastIcon');
        const messageElement = document.getElementById('toastMessage');
        
        if (!toast || !icon || !messageElement) return;
        
        // Set message
        messageElement.textContent = message;
        
        // Set icon based on type
        icon.className = type === 'error' 
            ? 'fas fa-exclamation-circle text-red-500'
            : type === 'info'
            ? 'fas fa-info-circle text-blue-500'
            : 'fas fa-check-circle text-green-500';
        
        // Show toast
        toast.classList.remove('hidden');
        
        // Auto hide after 3 seconds
        setTimeout(() => {
            hideToast();
        }, 3000);
    }

    function hideToast() {
        const toast = document.getElementById('toastNotification');
        if (toast) {
            toast.classList.add('hidden');
        }
    }

    // Handle add to cart form submissions
    document.addEventListener('submit', function(e) {
        if (e.target.classList.contains('add-to-cart-form') || e.target.id === 'sizeAddToCartForm') {
            e.preventDefault();
            
            const form = e.target;
            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            
            if (submitBtn) {
                submitBtn.disabled = true;
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Adding...';
                
                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message || 'Product added to cart!', 'success');
                        
                        // Update cart count if provided
                        if (data.cartCount !== undefined) {
                            const cartBadge = document.getElementById('cartCount');
                            if (cartBadge) {
                                cartBadge.textContent = data.cartCount;
                                cartBadge.style.display = data.cartCount > 0 ? 'inline' : 'none';
                            }
                        }
                        
                        // Close modal if it was from size selection
                        if (form.id === 'sizeAddToCartForm') {
                            closeModal();
                        }
                    } else {
                        showToast(data.message || 'Failed to add product to cart', 'error');
                    }
                })
                .catch(error => {
                    console.error('Add to cart error:', error);
                    showToast('Failed to add product to cart', 'error');
                })
                .finally(() => {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    }
                });
            }
        }
    });
    </script>
@endsection