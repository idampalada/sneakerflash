@extends('layouts.app')

@section('title', 'Products - SneakerFlash')

@section('content')
    <!-- Page Header -->
    <section class="bg-white py-6 border-b border-gray-200">
        <div class="container mx-auto px-4">
            <nav class="text-sm mb-4">
                <ol class="flex space-x-2 text-gray-600">
                    <li><a href="/" class="hover:text-blue-600">Home</a></li>
                    <li>/</li>
                    <li class="text-gray-900">Products</li>
                </ol>
            </nav>
            
            <div class="flex justify-between items-center">
                <h1 class="text-3xl font-bold text-gray-900">All Products</h1>
                <div class="text-gray-600">
                    {{ isset($products) ? $products->total() : '0' }} products found
                </div>
            </div>
        </div>
    </section>

    <!-- Category Tabs - Kick Avenue Style -->
    <section class="bg-white py-4 border-b border-gray-200">
        <div class="container mx-auto px-4">
            <div class="flex items-center space-x-4 overflow-x-auto">
                <!-- Filter Toggle Button -->
                <button id="filterToggle" class="flex items-center space-x-2 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors flex-shrink-0">
                    <i class="fas fa-filter text-gray-600"></i>
                    <span class="text-sm font-medium text-gray-700">Filter</span>
                    <i id="filterIcon" class="fas fa-chevron-down text-gray-400 text-xs"></i>
                </button>

                <!-- Category Pills -->
                <div class="flex space-x-2 flex-shrink-0">
                    <a href="{{ route('products.index') }}" class="category-pill active">
                        Sneakers
                    </a>
                    <a href="#" class="category-pill">Apparel</a>
                    <a href="#" class="category-pill">Luxury</a>
                    <a href="#" class="category-pill">Electronics & Collectibles</a>
                </div>

                <!-- Reset Filter -->
                <button onclick="clearFilters()" class="flex items-center space-x-1 text-sm text-gray-500 hover:text-gray-700 flex-shrink-0">
                    <span>Reset</span>
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>
        </div>
    </section>

    <div class="container mx-auto px-4 py-8">
        <div class="flex gap-8">
            <!-- Filters Sidebar - Hidden by default -->
            <aside id="filterSidebar" class="w-72 flex-shrink-0 hidden">
                <div class="bg-white rounded-2xl p-6 sticky top-4 border border-gray-100">
                    <h3 class="font-bold text-gray-900 mb-6 text-lg">Filters</h3>
                    
                    <form method="GET" id="filterForm">
                        <!-- Category Filter -->
                        <div class="mb-8">
                            <h4 class="font-semibold text-gray-900 mb-4 text-sm uppercase tracking-wide">Category</h4>
                            <div class="space-y-2">
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="category" value="" class="mr-3" checked>
                                    <span class="text-sm">All Categories</span>
                                </label>
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="category" value="sneakers" class="mr-3">
                                    <span class="text-sm">Sneakers</span>
                                </label>
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="category" value="nike-cortez" class="mr-3">
                                    <span class="text-sm">Nike Cortez</span>
                                </label>
                            </div>
                        </div>

                        <!-- US Size Filter -->
                        <div class="mb-8">
                            <h4 class="font-semibold text-gray-900 mb-4 text-sm uppercase tracking-wide">US Size</h4>
                            <div class="grid grid-cols-4 gap-2">
                                <button type="button" class="size-option border border-gray-200 rounded-lg py-2 px-1 text-xs text-center hover:border-blue-500 hover:bg-blue-50 transition-all" data-size="6">6</button>
                                <button type="button" class="size-option border border-gray-200 rounded-lg py-2 px-1 text-xs text-center hover:border-blue-500 hover:bg-blue-50 transition-all" data-size="6.5">6.5</button>
                                <button type="button" class="size-option border border-gray-200 rounded-lg py-2 px-1 text-xs text-center hover:border-blue-500 hover:bg-blue-50 transition-all" data-size="7">7</button>
                                <button type="button" class="size-option border border-gray-200 rounded-lg py-2 px-1 text-xs text-center hover:border-blue-500 hover:bg-blue-50 transition-all" data-size="7.5">7.5</button>
                                <button type="button" class="size-option border border-gray-200 rounded-lg py-2 px-1 text-xs text-center hover:border-blue-500 hover:bg-blue-50 transition-all" data-size="8">8</button>
                                <button type="button" class="size-option border border-gray-200 rounded-lg py-2 px-1 text-xs text-center hover:border-blue-500 hover:bg-blue-50 transition-all" data-size="8.5">8.5</button>
                                <button type="button" class="size-option border border-gray-200 rounded-lg py-2 px-1 text-xs text-center hover:border-blue-500 hover:bg-blue-50 transition-all" data-size="9">9</button>
                                <button type="button" class="size-option border border-gray-200 rounded-lg py-2 px-1 text-xs text-center hover:border-blue-500 hover:bg-blue-50 transition-all" data-size="9.5">9.5</button>
                                <button type="button" class="size-option border border-gray-200 rounded-lg py-2 px-1 text-xs text-center hover:border-blue-500 hover:bg-blue-50 transition-all" data-size="10">10</button>
                                <button type="button" class="size-option border border-gray-200 rounded-lg py-2 px-1 text-xs text-center hover:border-blue-500 hover:bg-blue-50 transition-all" data-size="10.5">10.5</button>
                                <button type="button" class="size-option border border-gray-200 rounded-lg py-2 px-1 text-xs text-center hover:border-blue-500 hover:bg-blue-50 transition-all" data-size="11">11</button>
                                <button type="button" class="size-option border border-gray-200 rounded-lg py-2 px-1 text-xs text-center hover:border-blue-500 hover:bg-blue-50 transition-all" data-size="11.5">11.5</button>
                                <button type="button" class="size-option border border-gray-200 rounded-lg py-2 px-1 text-xs text-center hover:border-blue-500 hover:bg-blue-50 transition-all" data-size="12">12</button>
                                <button type="button" class="size-option border border-gray-200 rounded-lg py-2 px-1 text-xs text-center hover:border-blue-500 hover:bg-blue-50 transition-all" data-size="12.5">12.5</button>
                                <button type="button" class="size-option border border-gray-200 rounded-lg py-2 px-1 text-xs text-center hover:border-blue-500 hover:bg-blue-50 transition-all" data-size="13">13</button>
                                <button type="button" class="size-option border border-gray-200 rounded-lg py-2 px-1 text-xs text-center hover:border-blue-500 hover:bg-blue-50 transition-all" data-size="14">14</button>
                            </div>
                        </div>

                        <!-- Conditions Filter -->
                        <div class="mb-8">
                            <h4 class="font-semibold text-gray-900 mb-4 text-sm uppercase tracking-wide">Conditions</h4>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" class="condition-tag px-3 py-1 text-xs border border-gray-200 rounded-full hover:border-blue-500 hover:bg-blue-50 transition-all" data-condition="express_shipping">Express Shipping</button>
                                <button type="button" class="condition-tag px-3 py-1 text-xs border border-gray-200 rounded-full hover:border-blue-500 hover:bg-blue-50 transition-all" data-condition="brand_new">Brand New</button>
                                <button type="button" class="condition-tag px-3 py-1 text-xs border border-gray-200 rounded-full hover:border-blue-500 hover:bg-blue-50 transition-all" data-condition="used">Used</button>
                                <button type="button" class="condition-tag px-3 py-1 text-xs border border-gray-200 rounded-full hover:border-blue-500 hover:bg-blue-50 transition-all" data-condition="pre_order">Pre-Order</button>
                            </div>
                        </div>

                        <!-- Price Range -->
                        <div class="mb-8">
                            <h4 class="font-semibold text-gray-900 mb-4 text-sm uppercase tracking-wide">Price Range</h4>
                            <div class="flex gap-2">
                                <input type="number" name="min_price" placeholder="Min Price" class="flex-1 px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <input type="number" name="max_price" placeholder="Max Price" class="flex-1 px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <!-- Gender Filter -->
                        <div class="mb-8">
                            <h4 class="font-semibold text-gray-900 mb-4 text-sm uppercase tracking-wide">Gender</h4>
                            <div class="grid grid-cols-3 gap-2">
                                <button type="button" class="gender-option border border-gray-200 rounded-lg py-2 text-xs text-center hover:border-blue-500 hover:bg-blue-50 transition-all" data-gender="men">Men</button>
                                <button type="button" class="gender-option border border-gray-200 rounded-lg py-2 text-xs text-center hover:border-blue-500 hover:bg-blue-50 transition-all" data-gender="women">Women</button>
                                <button type="button" class="gender-option border border-gray-200 rounded-lg py-2 text-xs text-center hover:border-blue-500 hover:bg-blue-50 transition-all" data-gender="youth">Youth</button>
                            </div>
                        </div>

                        <!-- Color Filter -->
                        <div class="mb-8">
                            <h4 class="font-semibold text-gray-900 mb-4 text-sm uppercase tracking-wide">Color</h4>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" class="color-option w-8 h-8 rounded-full border-2 border-gray-200 hover:scale-110 transition-transform" data-color="black" title="Black" style="background-color: #000000;"></button>
                                <button type="button" class="color-option w-8 h-8 rounded-full border-2 border-gray-200 hover:scale-110 transition-transform white-color-border" data-color="white" title="White" style="background-color: #FFFFFF;"></button>
                                <button type="button" class="color-option w-8 h-8 rounded-full border-2 border-gray-200 hover:scale-110 transition-transform" data-color="red" title="Red" style="background-color: #EF4444;"></button>
                                <button type="button" class="color-option w-8 h-8 rounded-full border-2 border-gray-200 hover:scale-110 transition-transform" data-color="blue" title="Blue" style="background-color: #3B82F6;"></button>
                                <button type="button" class="color-option w-8 h-8 rounded-full border-2 border-gray-200 hover:scale-110 transition-transform" data-color="green" title="Green" style="background-color: #10B981;"></button>
                                <button type="button" class="color-option w-8 h-8 rounded-full border-2 border-gray-200 hover:scale-110 transition-transform" data-color="brown" title="Brown" style="background-color: #92400E;"></button>
                            </div>
                        </div>

                        <!-- Brands Filter -->
                        <div class="mb-8">
                            <h4 class="font-semibold text-gray-900 mb-4 text-sm uppercase tracking-wide">Brands</h4>
                            <div class="max-h-48 overflow-y-auto space-y-2">
                                <label class="flex items-center cursor-pointer hover:bg-gray-50 p-1 rounded">
                                    <input type="checkbox" name="brands[]" value="nike" class="mr-3">
                                    <span class="text-sm">Nike</span>
                                </label>
                                <label class="flex items-center cursor-pointer hover:bg-gray-50 p-1 rounded">
                                    <input type="checkbox" name="brands[]" value="adidas" class="mr-3">
                                    <span class="text-sm">Adidas</span>
                                </label>
                                <label class="flex items-center cursor-pointer hover:bg-gray-50 p-1 rounded">
                                    <input type="checkbox" name="brands[]" value="puma" class="mr-3">
                                    <span class="text-sm">Puma</span>
                                </label>
                                <label class="flex items-center cursor-pointer hover:bg-gray-50 p-1 rounded">
                                    <input type="checkbox" name="brands[]" value="newbalance" class="mr-3">
                                    <span class="text-sm">New Balance</span>
                                </label>
                                <label class="flex items-center cursor-pointer hover:bg-gray-50 p-1 rounded">
                                    <input type="checkbox" name="brands[]" value="converse" class="mr-3">
                                    <span class="text-sm">Converse</span>
                                </label>
                                <label class="flex items-center cursor-pointer hover:bg-gray-50 p-1 rounded">
                                    <input type="checkbox" name="brands[]" value="vans" class="mr-3">
                                    <span class="text-sm">Vans</span>
                                </label>
                            </div>
                        </div>

                        <!-- Filter Buttons -->
                        <div class="space-y-3">
                            <button type="submit" class="w-full bg-black text-white py-3 rounded-lg font-medium hover:bg-gray-800 transition-colors">
                                Apply Filters
                            </button>
                            <button type="button" onclick="clearFilters()" class="w-full border border-gray-300 text-gray-700 py-3 rounded-lg font-medium hover:bg-gray-50 transition-colors">
                                Clear Filters
                            </button>
                        </div>
                    </form>
                </div>
            </aside>

            <!-- Products Grid -->
            <main class="flex-1">
                <!-- Sort Options & View Toggle -->
                <div class="bg-white rounded-2xl p-6 mb-6 border border-gray-100">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center space-x-4">
                            <span class="text-gray-600 font-medium">Sort by:</span>
                            <select name="sort" onchange="updateSort(this.value)" class="px-4 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="name_az">Name A-Z</option>
                                <option value="price_low">Price: Low to High</option>
                                <option value="price_high">Price: High to Low</option>
                                <option value="latest">Latest</option>
                                <option value="featured">Featured</option>
                            </select>
                        </div>
                        
                        <div class="flex items-center space-x-2">
                            <button id="gridView" class="p-2 rounded-lg border border-gray-200 text-blue-600 bg-blue-50">
                                <i class="fas fa-th-large"></i>
                            </button>
                            <button id="listView" class="p-2 rounded-lg border border-gray-200 text-gray-400">
                                <i class="fas fa-list"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Products Grid -->
                <div id="productsContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    @if(isset($products) && $products->count() > 0)
                        @foreach($products as $product)
                            <div class="product-card bg-white rounded-2xl overflow-hidden border border-gray-100 hover:shadow-lg transition-all duration-300 group">
                                <div class="relative aspect-square bg-gray-50 overflow-hidden">
                                    @if($product->images && count($product->images) > 0)
                                        <img src="{{ Storage::url($product->images[0]) }}" 
                                             alt="{{ $product->name }}"
                                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center bg-gray-100">
                                            <i class="fas fa-shoe-prints text-4xl text-gray-300"></i>
                                        </div>
                                    @endif
                                    
                                    <!-- Product badges -->
                                    <div class="absolute top-3 left-3 flex flex-col gap-2">
                                        @if($product->is_featured ?? false)
                                            <span class="bg-yellow-500 text-white text-xs px-2 py-1 rounded-full font-medium">
                                                Featured
                                            </span>
                                        @endif
                                        @if(isset($product->sale_price) && $product->sale_price)
                                            @php
                                                $discount = round((($product->price - $product->sale_price) / $product->price) * 100);
                                            @endphp
                                            <span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full font-medium">
                                                -{{ $discount }}%
                                            </span>
                                        @endif
                                    </div>

                                    <!-- Wishlist button -->
                                    <button class="absolute top-3 right-3 w-8 h-8 bg-white rounded-full flex items-center justify-center shadow-md hover:shadow-lg transition-shadow">
                                        <i class="far fa-heart text-gray-400 hover:text-red-500 transition-colors"></i>
                                    </button>
                                </div>
                                
                                <div class="p-4">
                                    <div class="mb-2">
                                        <span class="text-xs text-gray-500 uppercase tracking-wide">
                                            {{ $product->category->name ?? 'Sneakers' }} • {{ $product->brand ?? 'Brand' }}
                                        </span>
                                    </div>
                                    
                                    <h3 class="font-semibold text-gray-900 mb-3 text-sm leading-tight">
                                        <a href="{{ route('products.show', $product->slug) }}" 
                                           class="hover:text-blue-600 transition-colors">
                                            {{ $product->name }}
                                        </a>
                                    </h3>
                                    
                                    <!-- PRICE ONLY - NO RATING -->
                                    <div class="mb-4">
                                        @if(isset($product->sale_price) && $product->sale_price)
                                            <div class="flex items-center space-x-2">
                                                <span class="text-lg font-bold text-red-600">
                                                    Rp {{ number_format($product->sale_price, 0, ',', '.') }}
                                                </span>
                                                <span class="text-sm text-gray-400 line-through">
                                                    Rp {{ number_format($product->price, 0, ',', '.') }}
                                                </span>
                                            </div>
                                        @else
                                            <span class="text-lg font-bold text-gray-900">
                                                Rp {{ number_format($product->price, 0, ',', '.') }}
                                            </span>
                                        @endif
                                    </div>
                                    
                                    <div class="flex gap-2">
                                        <button class="flex-1 bg-gray-900 text-white py-2 px-3 rounded-lg text-sm font-medium hover:bg-gray-800 transition-colors">
                                            <i class="fas fa-shopping-cart mr-1"></i>
                                            Add to Cart
                                        </button>
                                        <button class="px-3 py-2 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                            <i class="fas fa-eye text-gray-600"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <!-- Empty state -->
                        <div class="col-span-full text-center py-12">
                            <i class="fas fa-shoe-prints text-6xl text-gray-300 mb-4"></i>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">No products found</h3>
                            <p class="text-gray-500">Try adjusting your filters or search terms</p>
                        </div>
                    @endif
                </div>

                <!-- Pagination -->
                @if(isset($products) && method_exists($products, 'links'))
                    <div class="mt-8">
                        {{ $products->appends(request()->query())->links() }}
                    </div>
                @endif
            </main>
        </div>
    </div>
@endsection

<!-- CSS -->
<style>
    /* Category Pills - Kick Avenue Style */
    .category-pill {
        display: inline-flex;
        align-items: center;
        padding: 8px 16px;
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        color: #6c757d;
        transition: all 0.2s ease;
        white-space: nowrap;
    }

    .category-pill:hover {
        background: #e9ecef;
        color: #495057;
        border-color: #adb5bd;
    }

    .category-pill.active {
        background: #000000;
        color: #ffffff;
        border-color: #000000;
    }

    /* Filter Sidebar Animation */
    #filterSidebar {
        transition: all 0.3s ease;
    }

    #filterSidebar.hidden {
        display: none;
    }

    /* Custom styles for SneakerFlash filters */
    .size-option.selected {
        background-color: #3B82F6;
        color: white;
        border-color: #3B82F6;
    }

    .condition-tag.selected {
        background-color: #3B82F6;
        color: white;
        border-color: #3B82F6;
    }

    .gender-option.selected {
        background-color: #3B82F6;
        color: white;
        border-color: #3B82F6;
    }

    .color-option.selected {
        box-shadow: 0 0 0 3px #3B82F6;
        transform: scale(1.1);
    }

    /* Special styling for white color option */
    .white-color-border {
        box-shadow: inset 0 0 0 1px #e5e7eb;
    }

    .white-color-border.selected {
        box-shadow: inset 0 0 0 1px #e5e7eb, 0 0 0 3px #3B82F6;
    }
</style>

<!-- JavaScript -->
<script>
    // Filter Toggle Functionality
    document.addEventListener('DOMContentLoaded', function() {
        const filterToggle = document.getElementById('filterToggle');
        const filterSidebar = document.getElementById('filterSidebar');
        const filterIcon = document.getElementById('filterIcon');

        let filterVisible = false;

        filterToggle.addEventListener('click', function() {
            filterVisible = !filterVisible;
            
            if (filterVisible) {
                filterSidebar.classList.remove('hidden');
                filterIcon.classList.remove('fa-chevron-down');
                filterIcon.classList.add('fa-chevron-up');
            } else {
                filterSidebar.classList.add('hidden');
                filterIcon.classList.remove('fa-chevron-up');
                filterIcon.classList.add('fa-chevron-down');
            }
        });

        // Size selection
        document.querySelectorAll('.size-option').forEach(button => {
            button.addEventListener('click', function() {
                this.classList.toggle('selected');
            });
        });

        // Condition selection
        document.querySelectorAll('.condition-tag').forEach(button => {
            button.addEventListener('click', function() {
                this.classList.toggle('selected');
            });
        });

        // Gender selection (single select)
        document.querySelectorAll('.gender-option').forEach(button => {
            button.addEventListener('click', function() {
                document.querySelectorAll('.gender-option').forEach(btn => btn.classList.remove('selected'));
                this.classList.add('selected');
            });
        });

        // Color selection (single select)
        document.querySelectorAll('.color-option').forEach(button => {
            button.addEventListener('click', function() {
                document.querySelectorAll('.color-option').forEach(btn => btn.classList.remove('selected'));
                this.classList.add('selected');
            });
        });

        // View toggle
        document.getElementById('gridView').addEventListener('click', function() {
            document.getElementById('productsContainer').classList.remove('list-view');
            this.classList.add('text-blue-600', 'bg-blue-50');
            document.getElementById('listView').classList.remove('text-blue-600', 'bg-blue-50');
            document.getElementById('listView').classList.add('text-gray-400');
        });

        document.getElementById('listView').addEventListener('click', function() {
            document.getElementById('productsContainer').classList.add('list-view');
            this.classList.add('text-blue-600', 'bg-blue-50');
            document.getElementById('gridView').classList.remove('text-blue-600', 'bg-blue-50');
            document.getElementById('gridView').classList.add('text-gray-400');
        });
    });

    function updateSort(value) {
        const url = new URL(window.location);
        url.searchParams.set('sort', value);
        window.location = url;
    }

    function clearFilters() {
        window.location = window.location.pathname;
    }
</script>