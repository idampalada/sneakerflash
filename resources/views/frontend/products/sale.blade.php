@extends('layouts.app')

@section('title', 'Sale - Best Deals on Premium Sneakers - SneakerFlash')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Sale Header with Special Styling -->
    <div class="text-center mb-12 relative">
        <div class="absolute inset-0 bg-gradient-to-r from-red-500 to-pink-500 opacity-10 rounded-3xl"></div>
        <div class="relative py-12">
            <h1 class="text-5xl md:text-6xl font-black text-transparent bg-clip-text bg-gradient-to-r from-red-500 to-pink-500 mb-4">
                🔥 MEGA SALE 🔥
            </h1>
            <p class="text-xl text-gray-700 max-w-2xl mx-auto mb-6">
                Unbeatable prices on premium sneakers. Limited time offers!
            </p>
            <div class="flex justify-center items-center gap-4 text-sm font-semibold">
                <div class="bg-red-500 text-white px-4 py-2 rounded-full animate-pulse">
                    UP TO 70% OFF
                </div>
                <div class="bg-gradient-to-r from-red-500 to-pink-500 text-white px-4 py-2 rounded-full">
                    FREE SHIPPING
                </div>
            </div>
        </div>
    </div>

    <!-- Sale Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-gradient-to-r from-red-50 to-pink-50 border border-red-200 rounded-lg p-6 text-center">
            <div class="text-3xl font-bold text-red-600 mb-2">{{ $products->total() }}</div>
            <div class="text-gray-600">Products on Sale</div>
        </div>
        <div class="bg-gradient-to-r from-yellow-50 to-orange-50 border border-yellow-200 rounded-lg p-6 text-center">
            <div class="text-3xl font-bold text-orange-600 mb-2">70%</div>
            <div class="text-gray-600">Max Discount</div>
        </div>
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-lg p-6 text-center">
            <div class="text-3xl font-bold text-green-600 mb-2">FREE</div>
            <div class="text-gray-600">Shipping Nationwide</div>
        </div>
    </div>

    <!-- Filters & Sort -->
    <div class="bg-white rounded-lg shadow-sm border p-6 mb-8">
        <form method="GET" class="flex flex-wrap items-center gap-4">
            <!-- Search -->
            <div class="flex-1 min-w-64">
                <input type="text" 
                       name="search" 
                       placeholder="Search sale products..."
                       value="{{ request('search') }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
            </div>

            <!-- Brand Filter -->
            <div>
                <select name="brand" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                    <option value="">All Brands</option>
                    @if(isset($brands))
                        @foreach($brands as $brand)
                            <option value="{{ $brand }}" {{ request('brand') == $brand ? 'selected' : '' }}>
                                {{ $brand }}
                            </option>
                        @endforeach
                    @endif
                </select>
            </div>

            <!-- Discount Range -->
            <div>
                <select name="discount" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                    <option value="">All Discounts</option>
                    <option value="10-30" {{ request('discount') == '10-30' ? 'selected' : '' }}>10% - 30% OFF</option>
                    <option value="30-50" {{ request('discount') == '30-50' ? 'selected' : '' }}>30% - 50% OFF</option>
                    <option value="50-70" {{ request('discount') == '50-70' ? 'selected' : '' }}>50% - 70% OFF</option>
                    <option value="70+" {{ request('discount') == '70+' ? 'selected' : '' }}>70%+ OFF</option>
                </select>
            </div>

            <!-- Price Range -->
            <div class="flex items-center gap-2">
                <input type="number" 
                       name="min_price" 
                       placeholder="Min Price"
                       value="{{ request('min_price') }}"
                       class="w-24 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                <span class="text-gray-500">-</span>
                <input type="number" 
                       name="max_price" 
                       placeholder="Max Price"
                       value="{{ request('max_price') }}"
                       class="w-24 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
            </div>

            <!-- Sort -->
            <div>
                <select name="sort" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                    <option value="discount_high" {{ request('sort') == 'discount_high' ? 'selected' : '' }}>Highest Discount</option>
                    <option value="price_low" {{ request('sort') == 'price_low' ? 'selected' : '' }}>Price: Low to High</option>
                    <option value="price_high" {{ request('sort') == 'price_high' ? 'selected' : '' }}>Price: High to Low</option>
                    <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>Latest</option>
                    <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>Name A-Z</option>
                </select>
            </div>

            <!-- Filter Button -->
            <button type="submit" class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700 transition-colors">
                <i class="fas fa-filter mr-2"></i>Filter
            </button>

            <!-- Clear Filters -->
            @if(request()->hasAny(['search', 'brand', 'discount', 'min_price', 'max_price', 'sort']))
                <a href="{{ route('products.sale') }}" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times mr-1"></i>Clear
                </a>
            @endif
        </form>
    </div>

    <!-- Results Info -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <p class="text-gray-600">
                Showing {{ $products->firstItem() ?? 0 }} - {{ $products->lastItem() ?? 0 }} 
                of {{ $products->total() }} sale products
            </p>
        </div>
    </div>

    @if($products->count() > 0)
        <!-- Sale Products Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-12">
            @foreach($products as $product)
                <div class="bg-white rounded-lg shadow-sm border hover:shadow-md transition-shadow group relative overflow-hidden">
                    <!-- Sale Ribbon -->
                    <div class="absolute top-0 right-0 z-10">
                        <div class="bg-gradient-to-r from-red-500 to-pink-500 text-white px-3 py-1 text-sm font-bold transform rotate-12 translate-x-3 -translate-y-1 shadow-lg">
                            {{ $product->discount_percentage }}% OFF
                        </div>
                    </div>

                    <!-- Product Image -->
                    <div class="relative overflow-hidden rounded-t-lg">
                        <a href="{{ route('products.show', $product->slug) }}">
                            @if($product->featured_image)
                                <img src="{{ $product->featured_image_url }}" 
                                     alt="{{ $product->name }}"
                                     class="w-full h-64 object-cover hover:scale-105 transition-transform duration-300">
                            @else
                                <div class="w-full h-64 bg-gray-200 flex items-center justify-center">
                                    <i class="fas fa-image text-gray-400 text-4xl"></i>
                                </div>
                            @endif
                        </a>

                        <!-- Hot Deal Badge -->
                        @if($product->discount_percentage >= 50)
                            <div class="absolute top-2 left-2 bg-gradient-to-r from-red-500 to-pink-500 text-white px-2 py-1 rounded-full text-xs font-semibold animate-pulse">
                                🔥 HOT DEAL
                            </div>
                        @endif

                        <!-- Limited Time Badge -->
                        <div class="absolute bottom-2 left-2 bg-black bg-opacity-75 text-white px-2 py-1 rounded text-xs font-semibold">
                            ⏰ Limited Time
                        </div>
                    </div>

                    <!-- Product Info -->
                    <div class="p-4">
                        <!-- Brand -->
                        @if($product->brand)
                            <p class="text-sm text-gray-500 mb-1">{{ $product->brand }}</p>
                        @endif

                        <!-- Product Name -->
                        <h3 class="font-semibold text-gray-900 mb-2 line-clamp-2">
                            <a href="{{ route('products.show', $product->slug) }}" class="hover:text-red-600">
                                {{ $product->name }}
                            </a>
                        </h3>

                        <!-- Price with Savings -->
                        <div class="mb-3">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-xl font-bold text-red-600">{{ $product->formatted_sale_price }}</span>
                                <span class="text-sm text-gray-500 line-through">{{ $product->formatted_price }}</span>
                            </div>
                            <div class="text-sm text-green-600 font-semibold">
                                You save: Rp {{ number_format($product->price - $product->sale_price, 0, ',', '.') }}
                            </div>
                        </div>

                        <!-- Stock Status -->
                        <div class="text-xs mb-3">
                            @if($product->stock_status === 'in_stock')
                                <span class="text-green-600 font-semibold">✓ In Stock</span>
                            @elseif($product->stock_status === 'low_stock')
                                <span class="text-yellow-600 font-semibold">⚠ Only {{ $product->stock_quantity }} left!</span>
                            @else
                                <span class="text-red-600 font-semibold">✗ Out of Stock</span>
                            @endif
                        </div>

                        <!-- Add to Cart Button -->
                        @if($product->in_stock)
                            <form action="{{ route('cart.add') }}" method="POST" class="add-to-cart-form">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" 
                                        class="w-full bg-gradient-to-r from-red-500 to-pink-500 text-white py-2 px-4 rounded-lg hover:from-red-600 hover:to-pink-600 transition-all transform hover:scale-105 flex items-center justify-center gap-2 font-semibold">
                                    <i class="fas fa-shopping-cart text-sm"></i>
                                    Add to Cart
                                </button>
                            </form>
                        @else
                            <button disabled 
                                    class="w-full bg-gray-300 text-gray-500 py-2 px-4 rounded-lg cursor-not-allowed">
                                Out of Stock
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="flex justify-center">
            {{ $products->links() }}
        </div>

    @else
        <!-- No Sale Products -->
        <div class="text-center py-16">
            <div class="max-w-md mx-auto">
                <i class="fas fa-percentage text-6xl text-red-300 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">No Sale Products Found</h3>
                <p class="text-gray-600 mb-6">
                    We couldn't find any sale products matching your criteria. Check back soon for new deals!
                </p>
                <a href="{{ route('products.sale') }}" 
                   class="inline-flex items-center px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    <i class="fas fa-redo mr-2"></i>
                    View All Sales
                </a>
            </div>
        </div>
    @endif
</div>

<!-- Sale Alert Banner -->
<div class="fixed bottom-4 right-4 bg-gradient-to-r from-red-500 to-pink-500 text-white px-6 py-3 rounded-lg shadow-lg max-w-sm z-50" id="sale-alert">
    <div class="flex items-center gap-3">
        <i class="fas fa-fire text-yellow-300"></i>
        <div>
            <div class="font-semibold text-sm">Sale ends soon!</div>
            <div class="text-xs opacity-90">Don't miss these amazing deals</div>
        </div>
        <button onclick="document.getElementById('sale-alert').style.display='none'" class="text-white hover:text-gray-200">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>

<style>
/* Sale page specific styles */
@keyframes pulse-red {
    0%, 100% { background-color: rgb(239 68 68); }
    50% { background-color: rgb(220 38 38); }
}

.animate-pulse-red {
    animation: pulse-red 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Sale ribbon effect */
.sale-ribbon {
    position: absolute;
    top: 10px;
    right: -10px;
    background: linear-gradient(45deg, #ef4444, #ec4899);
    color: white;
    padding: 5px 20px;
    font-size: 12px;
    font-weight: bold;
    transform: rotate(45deg);
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

/* Hot deal glow effect */
.hot-deal {
    box-shadow: 0 0 20px rgba(239, 68, 68, 0.5);
    animation: glow 2s ease-in-out infinite alternate;
}

@keyframes glow {
    from { box-shadow: 0 0 20px rgba(239, 68, 68, 0.5); }
    to { box-shadow: 0 0 30px rgba(239, 68, 68, 0.8); }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add to cart functionality for sale products
    const cartForms = document.querySelectorAll('.add-to-cart-form');
    
    cartForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const button = this.querySelector('button[type="submit"]');
            const originalText = button.innerHTML;
            
            // Show loading state
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Adding...';
            button.disabled = true;
            
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message with sale emphasis
                    button.innerHTML = '<i class="fas fa-check mr-2"></i>Added!';
                    button.classList.remove('from-red-500', 'to-pink-500');
                    button.classList.add('bg-green-500');
                    
                    setTimeout(() => {
                        button.innerHTML = originalText;
                        button.classList.remove('bg-green-500');
                        button.classList.add('from-red-500', 'to-pink-500');
                        button.disabled = false;
                    }, 2000);
                    
                    // Update cart counter if exists
                    const cartCounter = document.querySelector('.cart-counter');
                    if (cartCounter && data.cart_count) {
                        cartCounter.textContent = data.cart_count;
                    }
                } else {
                    alert('Failed to add product to cart. Please try again.');
                    button.innerHTML = originalText;
                    button.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                button.innerHTML = originalText;
                button.disabled = false;
            });
        });
    });

    // Auto-hide sale alert after 10 seconds
    setTimeout(() => {
        const saleAlert = document.getElementById('sale-alert');
        if (saleAlert) {
            saleAlert.style.display = 'none';
        }
    }, 10000);
});
</script>
@endsection