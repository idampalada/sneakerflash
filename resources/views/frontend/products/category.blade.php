@extends('layouts.app')

@section('title', $pageTitle . ' - SneakerFlash')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Page Header -->
    <div class="text-center mb-12">
        <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">{{ $pageTitle }}</h1>
        <p class="text-gray-600 text-lg max-w-2xl mx-auto">{{ $pageDescription }}</p>
    </div>

    <!-- Filters & Sort -->
    <div class="bg-white rounded-lg shadow-sm border p-6 mb-8">
        <form method="GET" class="flex flex-wrap items-center gap-4">
            <!-- Search -->
            <div class="flex-1 min-w-64">
                <input type="text" 
                       name="search" 
                       placeholder="Search products..."
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
                <a href="{{ url()->current() }}" class="text-gray-500 hover:text-gray-700">
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
                of {{ $products->total() }} results
            </p>
        </div>
        
        <!-- View Toggle (Grid/List) -->
        <div class="flex items-center gap-2">
            <button class="p-2 border border-gray-300 rounded-lg bg-blue-50 text-blue-600">
                <i class="fas fa-th-large"></i>
            </button>
            <button class="p-2 border border-gray-300 rounded-lg text-gray-400 hover:text-gray-600">
                <i class="fas fa-list"></i>
            </button>
        </div>
    </div>

    @if($products->count() > 0)
        <!-- Products Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-12">
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

                        <!-- Quick Actions -->
                        <div class="absolute top-2 right-2 flex flex-col gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button class="bg-white p-2 rounded-full shadow-md hover:bg-gray-50">
                                <i class="fas fa-heart text-gray-600"></i>
                            </button>
                            <button class="bg-white p-2 rounded-full shadow-md hover:bg-gray-50">
                                <i class="fas fa-eye text-gray-600"></i>
                            </button>
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

        <!-- Pagination -->
        <div class="flex justify-center">
            {{ $products->links() }}
        </div>

    @else
        <!-- No Products Found -->
        <div class="text-center py-16">
            <div class="max-w-md mx-auto">
                <i class="fas fa-box-open text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">No Products Found</h3>
                <p class="text-gray-600 mb-6">
                    We couldn't find any products matching your criteria. Try adjusting your filters or search terms.
                </p>
                <a href="{{ url()->current() }}" 
                   class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-redo mr-2"></i>
                    Reset Filters
                </a>
            </div>
        </div>
    @endif
</div>

<!-- Add to Cart Success Toast -->
<div id="cart-toast" class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg transform translate-x-full transition-transform duration-300 z-50">
    <div class="flex items-center gap-2">
        <i class="fas fa-check-circle"></i>
        <span>Product added to cart!</span>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add to cart functionality
    const cartForms = document.querySelectorAll('.add-to-cart-form');
    const cartToast = document.getElementById('cart-toast');
    
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
                    // Show success toast
                    cartToast.classList.remove('translate-x-full');
                    setTimeout(() => {
                        cartToast.classList.add('translate-x-full');
                    }, 3000);
                    
                    // Update cart counter if exists
                    const cartCounter = document.querySelector('.cart-counter');
                    if (cartCounter && data.cart_count) {
                        cartCounter.textContent = data.cart_count;
                    }
                } else {
                    alert('Failed to add product to cart. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            })
            .finally(() => {
                // Restore button state
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

/* Hover effects for product cards */
.group:hover .group-hover\:opacity-100 {
    opacity: 1;
}

/* Custom pagination styles */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.5rem;
}

.pagination .page-link {
    padding: 0.5rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    color: #374151;
    text-decoration: none;
    transition: all 0.2s;
}

.pagination .page-link:hover {
    background-color: #f3f4f6;
    border-color: #9ca3af;
}

.pagination .page-item.active .page-link {
    background-color: #2563eb;
    border-color: #2563eb;
    color: white;
}

.pagination .page-item.disabled .page-link {
    color: #9ca3af;
    cursor: not-allowed;
}
</style>
@endsection