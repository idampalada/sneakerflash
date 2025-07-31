@extends('layouts.app')

@section('title', 'Shop by Brand - Premium Sneaker Brands - SneakerFlash')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Brands Header -->
    <div class="text-center mb-12">
        <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">Shop by Brand</h1>
        <p class="text-gray-600 text-lg max-w-2xl mx-auto">
            Discover premium sneakers from the world's most iconic brands. 
            From limited editions to classic collections.
        </p>
    </div>

    <!-- Featured Brands Grid -->
    @if(isset($brands) && $brands->count() > 0)
        <div class="mb-12">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Featured Brands</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-8">
                @foreach($brands as $brand)
                    <a href="{{ route('products.index', ['brand' => $brand]) }}" 
                       class="group bg-white border border-gray-200 rounded-lg p-6 hover:shadow-md transition-all duration-300 hover:border-blue-300">
                        <div class="text-center">
                            <!-- Brand Logo Placeholder -->
                            <div class="w-16 h-16 mx-auto mb-3 bg-gray-100 rounded-full flex items-center justify-center group-hover:bg-blue-50 transition-colors">
                                <span class="text-xl font-bold text-gray-600 group-hover:text-blue-600">
                                    {{ strtoupper(substr($brand, 0, 2)) }}
                                </span>
                            </div>
                            <!-- Brand Name -->
                            <h3 class="font-semibold text-gray-900 group-hover:text-blue-600 transition-colors">
                                {{ $brand }}
                            </h3>
                            <!-- Product Count -->
                            <p class="text-sm text-gray-500 mt-1">
                                {{ \App\Models\Product::where('brand', $brand)->where('is_active', true)->count() }} products
                            </p>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Filters & Sort -->
    <div class="bg-white rounded-lg shadow-sm border p-6 mb-8">
        <form method="GET" class="flex flex-wrap items-center gap-4">
            <!-- Search -->
            <div class="flex-1 min-w-64">
                <input type="text" 
                       name="search" 
                       placeholder="Search products by brand..."
                       value="{{ request('search') }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <!-- Brand Filter -->
            <div>
                <select name="brand" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
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

            <!-- Price Range -->
            <div class="flex items-center gap-2">
                <input type="number" 
                       name="min_price" 
                       placeholder="Min Price"
                       value="{{ request('min_price') }}"
                       class="w-24 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <span class="text-gray-500">-</span>
                <input type="number" 
                       name="max_price" 
                       placeholder="Max Price"
                       value="{{ request('max_price') }}"
                       class="w-24 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <!-- Sort -->
            <div>
                <select name="sort" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="brand" {{ request('sort') == 'brand' ? 'selected' : '' }}>Brand A-Z</option>
                    <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>Latest</option>
                    <option value="price_low" {{ request('sort') == 'price_low' ? 'selected' : '' }}>Price: Low to High</option>
                    <option value="price_high" {{ request('sort') == 'price_high' ? 'selected' : '' }}>Price: High to Low</option>
                    <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>Name A-Z</option>
                    <option value="featured" {{ request('sort') == 'featured' ? 'selected' : '' }}>Featured</option>
                </select>
            </div>

            <!-- Filter Button -->
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-filter mr-2"></i>Filter
            </button>

            <!-- Clear Filters -->
            @if(request()->hasAny(['search', 'brand', 'min_price', 'max_price', 'sort']))
                <a href="{{ route('products.brand') }}" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times mr-1"></i>Clear
                </a>
            @endif
        </form>
    </div>

    <!-- Results Info -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <p class="text-gray-600">
                @if(request('brand'))
                    Showing {{ $products->firstItem() ?? 0 }} - {{ $products->lastItem() ?? 0 }} 
                    of {{ $products->total() }} products from {{ request('brand') }}
                @else
                    Showing {{ $products->firstItem() ?? 0 }} - {{ $products->lastItem() ?? 0 }} 
                    of {{ $products->total() }} products from all brands
                @endif
            </p>
        </div>
        
        <!-- Brand View Toggle -->
        <div class="flex items-center gap-2">
            <button id="brand-view-btn" class="p-2 border border-gray-300 rounded-lg bg-blue-50 text-blue-600">
                <i class="fas fa-th-large"></i> Brand View
            </button>
            <button id="product-view-btn" class="p-2 border border-gray-300 rounded-lg text-gray-400 hover:text-gray-600">
                <i class="fas fa-list"></i> Product View
            </button>
        </div>
    </div>

    @if($products->count() > 0)
        <!-- Brand View (Grouped by Brand) -->
        <div id="brand-view" class="mb-12">
            @php
                $productsByBrand = $products->groupBy('brand');
            @endphp
            
            @foreach($productsByBrand as $brandName => $brandProducts)
                <div class="mb-12 last:mb-0">
                    <!-- Brand Header -->
                    <div class="flex items-center justify-between mb-6 pb-4 border-b-2 border-gray-200">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                                <span class="text-white font-bold text-lg">
                                    {{ strtoupper(substr($brandName, 0, 2)) }}
                                </span>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900">{{ $brandName }}</h2>
                                <p class="text-gray-600">{{ $brandProducts->count() }} products</p>
                            </div>
                        </div>
                        <a href="{{ route('products.index', ['brand' => $brandName]) }}" 
                           class="text-blue-600 hover:text-blue-700 font-semibold flex items-center gap-2">
                            View All <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>

                    <!-- Brand Products Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        @foreach($brandProducts->take(4) as $product)
                            <div class="bg-white rounded-lg shadow-sm border hover:shadow-md transition-shadow">
                                <!-- Product Image -->
                                <div class="relative overflow-hidden rounded-t-lg">
                                    <a href="{{ route('products.show', $product->slug) }}">
                                        @if($product->featured_image)
                                            <img src="{{ $product->featured_image_url }}" 
                                                 alt="{{ $product->name }}"
                                                 class="w-full h-48 object-cover hover:scale-105 transition-transform duration-300">
                                        @else
                                            <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                                                <i class="fas fa-image text-gray-400 text-3xl"></i>
                                            </div>
                                        @endif
                                    </a>

                                    <!-- Sale Badge -->
                                    @if($product->has_discount)
                                        <div class="absolute top-2 left-2 bg-red-500 text-white px-2 py-1 rounded-full text-xs font-semibold">
                                            {{ $product->discount_percentage }}% OFF
                                        </div>
                                    @endif
                                </div>

                                <!-- Product Info -->
                                <div class="p-4">
                                    <!-- Product Name -->
                                    <h3 class="font-semibold text-gray-900 mb-2 line-clamp-2">
                                        <a href="{{ route('products.show', $product->slug) }}" class="hover:text-blue-600">
                                            {{ $product->name }}
                                        </a>
                                    </h3>

                                    <!-- Price -->
                                    <div class="flex items-center justify-between mb-3">
                                        <div class="flex items-center gap-2">
                                            @if($product->has_discount)
                                                <span class="text-lg font-bold text-gray-900">{{ $product->formatted_sale_price }}</span>
                                                <span class="text-sm text-gray-500 line-through">{{ $product->formatted_price }}</span>
                                            @else
                                                <span class="text-lg font-bold text-gray-900">{{ $product->formatted_price }}</span>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Add to Cart Button -->
                                    @if($product->in_stock)
                                        <form action="{{ route('cart.add') }}" method="POST" class="add-to-cart-form">
                                            @csrf
                                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" 
                                                    class="w-full bg-gray-900 text-white py-2 px-4 rounded-lg hover:bg-gray-800 transition-colors flex items-center justify-center gap-2 text-sm">
                                                <i class="fas fa-shopping-cart text-xs"></i>
                                                Add to Cart
                                            </button>
                                        </form>
                                    @else
                                        <button disabled 
                                                class="w-full bg-gray-300 text-gray-500 py-2 px-4 rounded-lg cursor-not-allowed text-sm">
                                            Out of Stock
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Product View (Regular Grid) -->
        <div id="product-view" class="hidden mb-12">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach($products as $product)
                    <div class="bg-white rounded-lg shadow-sm border hover:shadow-md transition-shadow">
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

                            <!-- Sale Badge -->
                            @if($product->has_discount)
                                <div class="absolute top-2 left-2 bg-red-500 text-white px-2 py-1 rounded-full text-xs font-semibold">
                                    {{ $product->discount_percentage }}% OFF
                                </div>
                            @endif

                            <!-- Brand Badge -->
                            <div class="absolute top-2 right-2 bg-black bg-opacity-75 text-white px-2 py-1 rounded text-xs font-semibold">
                                {{ $product->brand }}
                            </div>
                        </div>

                        <!-- Product Info -->
                        <div class="p-4">
                            <!-- Brand -->
                            <p class="text-sm text-gray-500 mb-1">{{ $product->brand }}</p>

                            <!-- Product Name -->
                            <h3 class="font-semibold text-gray-900 mb-2 line-clamp-2">
                                <a href="{{ route('products.show', $product->slug) }}" class="hover:text-blue-600">
                                    {{ $product->name }}
                                </a>
                            </h3>

                            <!-- Category -->
                            @if($product->category)
                                <p class="text-xs text-gray-400 mb-2">{{ $product->category->name }}</p>
                            @endif

                            <!-- Price -->
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    @if($product->has_discount)
                                        <span class="text-lg font-bold text-gray-900">{{ $product->formatted_sale_price }}</span>
                                        <span class="text-sm text-gray-500 line-through">{{ $product->formatted_price }}</span>
                                    @else
                                        <span class="text-lg font-bold text-gray-900">{{ $product->formatted_price }}</span>
                                    @endif
                                </div>

                                <!-- Stock Status -->
                                <div class="text-xs">
                                    @if($product->stock_status === 'in_stock')
                                        <span class="text-green-600 font-semibold">In Stock</span>
                                    @elseif($product->stock_status === 'low_stock')
                                        <span class="text-yellow-600 font-semibold">Low Stock</span>
                                    @else
                                        <span class="text-red-600 font-semibold">Out of Stock</span>
                                    @endif
                                </div>
                            </div>

                            <!-- Add to Cart Button -->
                            @if($product->in_stock)
                                <form action="{{ route('cart.add') }}" method="POST" class="add-to-cart-form">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" 
                                            class="w-full bg-gray-900 text-white py-2 px-4 rounded-lg hover:bg-gray-800 transition-colors flex items-center justify-center gap-2">
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
        </div>

        <!-- Pagination -->
        <div class="flex justify-center">
            {{ $products->links() }}
        </div>

    @else
        <!-- No Products Found -->
        <div class="text-center py-16">
            <div class="max-w-md mx-auto">
                <i class="fas fa-tags text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">No Brand Products Found</h3>
                <p class="text-gray-600 mb-6">
                    We couldn't find any products matching your criteria. Try adjusting your filters or search terms.
                </p>
                <a href="{{ route('products.brand') }}" 
                   class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-redo mr-2"></i>
                    View All Brands
                </a>
            </div>
        </div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // View toggle functionality
    const brandViewBtn = document.getElementById('brand-view-btn');
    const productViewBtn = document.getElementById('product-view-btn');
    const brandView = document.getElementById('brand-view');
    const productView = document.getElementById('product-view');

    brandViewBtn.addEventListener('click', function() {
        brandView.classList.remove('hidden');
        productView.classList.add('hidden');
        
        brandViewBtn.classList.add('bg-blue-50', 'text-blue-600');
        brandViewBtn.classList.remove('text-gray-400');
        
        productViewBtn.classList.remove('bg-blue-50', 'text-blue-600');
        productViewBtn.classList.add('text-gray-400');
    });

    productViewBtn.addEventListener('click', function() {
        productView.classList.remove('hidden');
        brandView.classList.add('hidden');
        
        productViewBtn.classList.add('bg-blue-50', 'text-blue-600');
        productViewBtn.classList.remove('text-gray-400');
        
        brandViewBtn.classList.remove('bg-blue-50', 'text-blue-600');
        brandViewBtn.classList.add('text-gray-400');
    });

    // Add to cart functionality
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
                    // Show success state
                    button.innerHTML = '<i class="fas fa-check mr-2"></i>Added!';
                    button.classList.remove('bg-gray-900', 'hover:bg-gray-800');
                    button.classList.add('bg-green-500');
                    
                    setTimeout(() => {
                        button.innerHTML = originalText;
                        button.classList.remove('bg-green-500');
                        button.classList.add('bg-gray-900', 'hover:bg-gray-800');
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
});
</script>

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Brand card hover effects */
.group:hover .group-hover\:bg-blue-50 {
    background-color: rgb(239 246 255);
}

.group:hover .group-hover\:text-blue-600 {
    color: rgb(37 99 235);
}

/* Brand header gradient */
.brand-header-gradient {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
</style>
@endsection