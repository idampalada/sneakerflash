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
                    @if(request('category'))
                        <li><a href="{{ route('products.index') }}" class="hover:text-blue-600">Products</a></li>
                        <li>/</li>
                        <li class="text-gray-900 uppercase">{{ strtoupper(request('category')) }}</li>
                        @if(request('type'))
                            <li>/</li>
                            <li class="text-gray-900 capitalize">{{ ucfirst(str_replace('_', ' ', request('type'))) }}</li>
                        @endif
                    @else
                        <li class="text-gray-900">Products</li>
                    @endif
                </ol>
            </nav>
            
            <div class="flex justify-between items-center">
                <h1 class="text-3xl font-bold text-gray-900">
                    @if(request('category') && request('type'))
                        {{ strtoupper(request('category')) }} {{ ucfirst(str_replace('_', ' ', request('type'))) }}
                    @elseif(request('category'))
                        {{ strtoupper(request('category')) }} Products
                    @else
                        All Products
                    @endif
                </h1>
                <div class="text-gray-600">
                    {{ $products->total() }} products found
                </div>
            </div>
        </div>
    </section>

    <!-- Active Filters Display - IMPROVED DESIGN -->
    @if(request()->hasAny(['category', 'type', 'brands', 'availability', 'min_price', 'max_price', 'sale', 'featured', 'colors', 'selected_colors', 'sizes', 'selected_sizes']))
    <section class="bg-gray-50 py-4 border-b border-gray-200">
        <div class="container mx-auto px-4">
            <div class="flex items-center space-x-3 flex-wrap gap-2">
                <span class="text-sm font-medium text-gray-700 flex-shrink-0">Active Filters:</span>
                
                <!-- Category Filter Badge -->
                @if(request('category'))
                    <div class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200">
                        <span class="mr-2">{{ strtoupper(request('category')) }}</span>
                        <button onclick="removeFilter('category')" class="ml-1 hover:text-gray-900 transition-colors">
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    </div>
                @endif

                <!-- Type Filter Badge -->
                @if(request('type'))
                    <div class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200">
                        <span class="mr-2">{{ ucfirst(str_replace('_', ' ', request('type'))) }}</span>
                        <button onclick="removeFilter('type')" class="ml-1 hover:text-gray-900 transition-colors">
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    </div>
                @endif

                <!-- Brand Filter Badges -->
                @if(request('brands'))
                    @foreach((array)request('brands') as $brand)
                        <div class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200">
                            <i class="fas fa-tag mr-1"></i>
                            <span class="mr-2">{{ strtoupper($brand) }}</span>
                            <button onclick="removeBrandFilter('{{ $brand }}')" class="ml-1 hover:text-gray-900 transition-colors">
                                <i class="fas fa-times text-xs"></i>
                            </button>
                        </div>
                    @endforeach
                @endif

                <!-- Price Range Badge -->
                @if(request('min_price') || request('max_price'))
                    <div class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200">
                        <i class="fas fa-dollar-sign mr-1"></i>
                        <span class="mr-2">
                            Rp{{ request('min_price') ? number_format(request('min_price'), 0, ',', '.') : '0' }} - 
                            Rp{{ request('max_price') ? number_format(request('max_price'), 0, ',', '.') : '∞' }}
                        </span>
                        <button onclick="removePriceFilter()" class="ml-1 hover:text-gray-900 transition-colors">
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    </div>
                @endif

                <!-- Sale Badge -->
                @if(request('sale'))
                    <div class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200">
                        <i class="fas fa-percent mr-1"></i>
                        <span class="mr-2">ON SALE</span>
                        <button onclick="removeFilter('sale')" class="ml-1 hover:text-gray-900 transition-colors">
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    </div>
                @endif

                <!-- Featured Badge -->
                @if(request('featured'))
                    <div class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200">
                        <i class="fas fa-star mr-1"></i>
                        <span class="mr-2">FEATURED</span>
                        <button onclick="removeFilter('featured')" class="ml-1 hover:text-gray-900 transition-colors">
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    </div>
                @endif

                <!-- Availability Badges -->
                @if(request('availability'))
                    @foreach((array)request('availability') as $status)
                        <div class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200">
                            <i class="fas fa-{{ $status === 'in_stock' ? 'check-circle' : 'times-circle' }} mr-1"></i>
                            <span class="mr-2">{{ $status === 'in_stock' ? 'In Stock' : 'Out of Stock' }}</span>
                            <button onclick="removeAvailabilityFilter('{{ $status }}')" class="ml-1 hover:text-gray-900 transition-colors">
                                <i class="fas fa-times text-xs"></i>
                            </button>
                        </div>
                    @endforeach
                @endif

                <!-- Colors Badges -->
                @if(request('colors') || request('selected_colors'))
                    @php
                        $selectedColors = request('colors') ?? request('selected_colors');
                        if (is_string($selectedColors)) {
                            $selectedColors = explode(',', $selectedColors);
                        }
                        $selectedColors = is_array($selectedColors) ? $selectedColors : [];
                    @endphp
                    @foreach($selectedColors as $color)
                        @if(trim($color))
                            <div class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200">
                                <i class="fas fa-palette mr-1"></i>
                                <span class="mr-2">{{ ucfirst(trim($color)) }}</span>
                                <button onclick="removeColorFilter('{{ trim($color) }}')" class="ml-1 hover:text-gray-900 transition-colors">
                                    <i class="fas fa-times text-xs"></i>
                                </button>
                            </div>
                        @endif
                    @endforeach
                @endif

                <!-- Sizes Badges -->
                @if(request('sizes') || request('selected_sizes'))
                    @php
                        $selectedSizes = request('sizes') ?? request('selected_sizes');
                        if (is_string($selectedSizes)) {
                            $selectedSizes = explode(',', $selectedSizes);
                        }
                        $selectedSizes = is_array($selectedSizes) ? $selectedSizes : [];
                    @endphp
                    @foreach($selectedSizes as $size)
                        @if(trim($size))
                            <div class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200">
                                <i class="fas fa-ruler mr-1"></i>
                                <span class="mr-2">Size {{ trim($size) }}</span>
                                <button onclick="removeSizeFilter('{{ trim($size) }}')" class="ml-1 hover:text-gray-900 transition-colors">
                                    <i class="fas fa-times text-xs"></i>
                                </button>
                            </div>
                        @endif
                    @endforeach
                @endif

                <!-- Reset Text Link (No Button Style) -->
                <span onclick="clearFilters()" class="text-sm text-gray-500 hover:text-gray-700 cursor-pointer transition-colors flex-shrink-0 ml-4">
                    Reset
                </span>
            </div>
        </div>
    </section>
    @endif

    <!-- Category Tabs -->
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
                    <a href="{{ route('products.index') }}" class="category-pill {{ !request('category') ? 'active' : '' }}">
                        All Products
                    </a>
                    <a href="{{ route('products.index', ['category' => 'mens']) }}" class="category-pill {{ request('category') === 'mens' ? 'active' : '' }}">
                        MENS
                    </a>
                    <a href="{{ route('products.index', ['category' => 'womens']) }}" class="category-pill {{ request('category') === 'womens' ? 'active' : '' }}">
                        WOMENS
                    </a>
                    <a href="{{ route('products.index', ['category' => 'kids']) }}" class="category-pill {{ request('category') === 'kids' ? 'active' : '' }}">
                        KIDS
                    </a>
                    <a href="{{ route('products.index', ['sale' => 'true']) }}" class="category-pill {{ request('sale') ? 'active' : '' }} special">
                        SALE
                    </a>
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
            <!-- Enhanced Filters Sidebar - Using Real Data -->
            <aside id="filterSidebar" class="w-72 flex-shrink-0 hidden">
                <div class="bg-white rounded-2xl p-6 sticky top-4 border border-gray-100">
                    <h3 class="font-bold text-gray-900 mb-6 text-lg">Filters</h3>
                    
                    <form method="GET" id="filterForm">
                        <!-- Category Filter - Real Categories -->
                        <div class="mb-8">
                            <h4 class="font-semibold text-gray-900 mb-4 text-sm uppercase tracking-wide">Category</h4>
                            <div class="space-y-2">
                                <label class="flex items-center cursor-pointer filter-option {{ !request('category') ? 'selected' : '' }}">
                                    <input type="radio" name="category" value="" class="mr-3" {{ !request('category') ? 'checked' : '' }}>
                                    <span class="text-sm">All Categories</span>
                                    @if(!request('category'))
                                        <i class="fas fa-check ml-auto text-blue-600 text-xs"></i>
                                    @endif
                                </label>
                                <label class="flex items-center cursor-pointer filter-option {{ request('category') === 'mens' ? 'selected' : '' }}">
                                    <input type="radio" name="category" value="mens" class="mr-3" {{ request('category') === 'mens' ? 'checked' : '' }}>
                                    <span class="text-sm">MENS</span>
                                    @if(request('category') === 'mens')
                                        <i class="fas fa-check ml-auto text-blue-600 text-xs"></i>
                                    @endif
                                </label>
                                <label class="flex items-center cursor-pointer filter-option {{ request('category') === 'womens' ? 'selected' : '' }}">
                                    <input type="radio" name="category" value="womens" class="mr-3" {{ request('category') === 'womens' ? 'checked' : '' }}>
                                    <span class="text-sm">WOMENS</span>
                                    @if(request('category') === 'womens')
                                        <i class="fas fa-check ml-auto text-blue-600 text-xs"></i>
                                    @endif
                                </label>
                                <label class="flex items-center cursor-pointer filter-option {{ request('category') === 'kids' ? 'selected' : '' }}">
                                    <input type="radio" name="category" value="kids" class="mr-3" {{ request('category') === 'kids' ? 'checked' : '' }}>
                                    <span class="text-sm">KIDS</span>
                                    @if(request('category') === 'kids')
                                        <i class="fas fa-check ml-auto text-blue-600 text-xs"></i>
                                    @endif
                                </label>
                            </div>
                        </div>

                        <!-- Sub-Category Filter (Product Types) -->
                        @if(request('category') && in_array(request('category'), ['mens', 'womens', 'kids']))
                        <div class="mb-8">
                            <h4 class="font-semibold text-gray-900 mb-4 text-sm uppercase tracking-wide">
                                {{ strtoupper(request('category')) }} Footwear
                            </h4>
                            <div class="space-y-2">
                                <label class="flex items-center cursor-pointer filter-option {{ !request('type') ? 'selected' : '' }}">
                                    <input type="radio" name="type" value="" class="mr-3" {{ !request('type') ? 'checked' : '' }}>
                                    <span class="text-sm">All Footwear</span>
                                    @if(!request('type'))
                                        <i class="fas fa-check ml-auto text-blue-600 text-xs"></i>
                                    @endif
                                </label>
                                <label class="flex items-center cursor-pointer filter-option {{ request('type') === 'lifestyle' ? 'selected' : '' }}">
                                    <input type="radio" name="type" value="lifestyle" class="mr-3" {{ request('type') === 'lifestyle' ? 'checked' : '' }}>
                                    <span class="text-sm">Lifestyle/Casual</span>
                                    @if(request('type') === 'lifestyle')
                                        <i class="fas fa-check ml-auto text-blue-600 text-xs"></i>
                                    @endif
                                </label>
                                <label class="flex items-center cursor-pointer filter-option {{ request('type') === 'running' ? 'selected' : '' }}">
                                    <input type="radio" name="type" value="running" class="mr-3" {{ request('type') === 'running' ? 'checked' : '' }}>
                                    <span class="text-sm">Running</span>
                                    @if(request('type') === 'running')
                                        <i class="fas fa-check ml-auto text-blue-600 text-xs"></i>
                                    @endif
                                </label>
                                <label class="flex items-center cursor-pointer filter-option {{ request('type') === 'training' ? 'selected' : '' }}">
                                    <input type="radio" name="type" value="training" class="mr-3" {{ request('type') === 'training' ? 'checked' : '' }}>
                                    <span class="text-sm">Training</span>
                                    @if(request('type') === 'training')
                                        <i class="fas fa-check ml-auto text-blue-600 text-xs"></i>
                                    @endif
                                </label>
                                <label class="flex items-center cursor-pointer filter-option {{ request('type') === 'sneakers' ? 'selected' : '' }}">
                                    <input type="radio" name="type" value="sneakers" class="mr-3" {{ request('type') === 'sneakers' ? 'checked' : '' }}>
                                    <span class="text-sm">Sneakers</span>
                                    @if(request('type') === 'sneakers')
                                        <i class="fas fa-check ml-auto text-blue-600 text-xs"></i>
                                    @endif
                                </label>
                                @if(request('category') === 'mens')
                                <label class="flex items-center cursor-pointer filter-option {{ request('type') === 'basketball' ? 'selected' : '' }}">
                                    <input type="radio" name="type" value="basketball" class="mr-3" {{ request('type') === 'basketball' ? 'checked' : '' }}>
                                    <span class="text-sm">Basketball</span>
                                    @if(request('type') === 'basketball')
                                        <i class="fas fa-check ml-auto text-blue-600 text-xs"></i>
                                    @endif
                                </label>
                                <label class="flex items-center cursor-pointer filter-option {{ request('type') === 'formal' ? 'selected' : '' }}">
                                    <input type="radio" name="type" value="formal" class="mr-3" {{ request('type') === 'formal' ? 'checked' : '' }}>
                                    <span class="text-sm">Formal Shoes</span>
                                    @if(request('type') === 'formal')
                                        <i class="fas fa-check ml-auto text-blue-600 text-xs"></i>
                                    @endif
                                </label>
                                @endif
                            </div>
                        </div>
                        @endif

                        <!-- Availability Filter - Real Stock Data -->
                        <div class="mb-8">
                            <h4 class="font-semibold text-gray-900 mb-4 text-sm uppercase tracking-wide">Availability</h4>
                            <div class="space-y-2">
                                <label class="flex items-center cursor-pointer filter-option {{ in_array('in_stock', request('availability', [])) ? 'selected' : '' }}">
                                    <input type="checkbox" name="availability[]" value="in_stock" class="mr-3" {{ in_array('in_stock', request('availability', [])) ? 'checked' : '' }}>
                                    <span class="text-sm">In stock ({{ $stockCounts['in_stock'] ?? 0 }})</span>
                                    @if(in_array('in_stock', request('availability', [])))
                                        <i class="fas fa-check ml-auto text-blue-600 text-xs"></i>
                                    @endif
                                </label>
                                <label class="flex items-center cursor-pointer filter-option {{ in_array('not_available', request('availability', [])) ? 'selected' : '' }}">
                                    <input type="checkbox" name="availability[]" value="not_available" class="mr-3" {{ in_array('not_available', request('availability', [])) ? 'checked' : '' }}>
                                    <span class="text-sm">Out of stock ({{ $stockCounts['not_available'] ?? 0 }})</span>
                                    @if(in_array('not_available', request('availability', [])))
                                        <i class="fas fa-check ml-auto text-blue-600 text-xs"></i>
                                    @endif
                                </label>
                            </div>
                        </div>

                        <!-- Brands Filter - Real Brand Data -->
                        <div class="mb-8">
                            <h4 class="font-semibold text-gray-900 mb-4 text-sm uppercase tracking-wide">Brand</h4>
                            <div class="max-h-48 overflow-y-auto space-y-2">
                                @if($brands && $brands->count() > 0)
                                    @foreach($brands as $brand)
                                        @php
                                            $brandCount = \App\Models\Product::where('is_active', true)
                                                ->whereNotNull('published_at')
                                                ->where('published_at', '<=', now())
                                                ->where('brand', $brand)
                                                ->count();
                                            $isSelected = in_array($brand, request('brands', []));
                                        @endphp
                                        <label class="flex items-center cursor-pointer hover:bg-gray-50 p-2 rounded filter-option {{ $isSelected ? 'selected' : '' }}">
                                            <input type="checkbox" name="brands[]" value="{{ $brand }}" class="mr-3" {{ $isSelected ? 'checked' : '' }}>
                                            <div class="flex items-center flex-1">
                                                <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                                                    <span class="text-xs font-bold text-gray-600">{{ strtoupper(substr($brand, 0, 2)) }}</span>
                                                </div>
                                                <div class="flex-1">
                                                    <span class="text-sm font-medium">{{ strtoupper($brand) }}</span>
                                                    <span class="text-xs text-gray-500 ml-1">({{ $brandCount }})</span>
                                                </div>
                                            </div>
                                            @if($isSelected)
                                                <i class="fas fa-check text-blue-600 text-xs ml-2"></i>
                                            @endif
                                        </label>
                                    @endforeach
                                @else
                                    <p class="text-sm text-gray-500">No brands available</p>
                                @endif
                            </div>
                        </div>

                        <!-- Price Range -->
                        <div class="mb-8">
                            <h4 class="font-semibold text-gray-900 mb-4 text-sm uppercase tracking-wide">Price</h4>
                            @php
                                $priceRange = \App\Models\Product::where('is_active', true)
                                    ->whereNotNull('published_at')
                                    ->where('published_at', '<=', now())
                                    ->selectRaw('MIN(COALESCE(sale_price, price)) as min_price, MAX(COALESCE(sale_price, price)) as max_price')
                                    ->first();
                            @endphp
                            <div class="mb-4">
                                <span class="text-sm text-gray-600">
                                    Rp{{ number_format($priceRange->min_price ?? 0, 0, ',', '.') }} - 
                                    Rp{{ number_format($priceRange->max_price ?? 0, 0, ',', '.') }}
                                </span>
                            </div>
                            <div class="flex gap-2">
                                <input type="number" name="min_price" placeholder="Min Price" value="{{ request('min_price') }}" class="flex-1 px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 {{ request('min_price') ? 'border-blue-500 bg-blue-50' : '' }}">
                                <input type="number" name="max_price" placeholder="Max Price" value="{{ request('max_price') }}" class="flex-1 px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 {{ request('max_price') ? 'border-blue-500 bg-blue-50' : '' }}">
                            </div>
                        </div>

                        <!-- Size Filter - Real Size Data -->
                        @if(isset($availableSizes) && $availableSizes->count() > 0)
                        <div class="mb-8">
                            <h4 class="font-semibold text-gray-900 mb-4 text-sm uppercase tracking-wide">Size</h4>
                            <div class="grid grid-cols-3 gap-2 max-h-48 overflow-y-auto">
                                @foreach($availableSizes as $size)
                                    @php
                                        $sizeCount = \App\Models\Product::where('is_active', true)
                                            ->whereNotNull('published_at')
                                            ->where('published_at', '<=', now())
                                            ->whereJsonContains('available_sizes', $size)
                                            ->count();
                                    @endphp
                                    <button type="button" class="size-option border border-gray-200 rounded-lg py-2 px-1 text-xs text-center hover:border-blue-500 hover:bg-blue-50 transition-all relative" data-size="{{ $size }}" title="{{ $size }} ({{ $sizeCount }} products)">
                                        {{ $size }}
                                        <i class="fas fa-check size-check-icon absolute top-1 right-1 text-blue-600 text-xs hidden"></i>
                                    </button>
                                @endforeach
                            </div>
                            <input type="hidden" name="selected_sizes" id="selectedSizes" value="">
                        </div>
                        @endif

                        <!-- Color Filter - Real Color Data -->
                        @if(isset($availableColors) && $availableColors->count() > 0)
                        <div class="mb-8">
                            <h4 class="font-semibold text-gray-900 mb-4 text-sm uppercase tracking-wide">Color</h4>
                            <div class="flex flex-wrap gap-2">
                                @foreach($availableColors as $color)
                                    @php
                                        $colorMap = [
                                            'black' => '#000000',
                                            'white' => '#FFFFFF',
                                            'red' => '#EF4444',
                                            'blue' => '#3B82F6',
                                            'navy' => '#1E3A8A',
                                            'green' => '#10B981',
                                            'yellow' => '#F59E0B',
                                            'pink' => '#EC4899',
                                            'brown' => '#92400E',
                                            'orange' => '#F97316',
                                            'purple' => '#8B5CF6',
                                            'grey' => '#6B7280',
                                            'gray' => '#6B7280',
                                            'silver' => '#C0C0C0',
                                            'gold' => '#D4AF37',
                                            'beige' => '#F5F5DC',
                                            'maroon' => '#800000'
                                        ];
                                        $colorHex = $colorMap[strtolower($color)] ?? '#9CA3AF';
                                        $isWhite = strtolower($color) === 'white';
                                    @endphp
                                    <button type="button" 
                                            class="color-option w-10 h-10 rounded-full border-2 border-gray-200 hover:scale-110 transition-transform relative {{ $isWhite ? 'white-color-border' : '' }}" 
                                            data-color="{{ strtolower($color) }}" 
                                            title="{{ ucfirst($color) }}" 
                                            style="background-color: {{ $colorHex }};">
                                        <i class="fas fa-check color-check-icon absolute inset-0 flex items-center justify-center text-white text-xs hidden {{ $isWhite ? 'text-gray-800' : 'text-white' }}"></i>
                                    </button>
                                @endforeach
                            </div>
                            <input type="hidden" name="selected_colors" id="selectedColors" value="">
                        </div>
                        @endif

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
                                <option value="latest" {{ request('sort') === 'latest' || !request('sort') ? 'selected' : '' }}>Latest</option>
                                <option value="name_az" {{ request('sort') === 'name_az' ? 'selected' : '' }}>Name A-Z</option>
                                <option value="price_low" {{ request('sort') === 'price_low' ? 'selected' : '' }}>Price: Low to High</option>
                                <option value="price_high" {{ request('sort') === 'price_high' ? 'selected' : '' }}>Price: High to Low</option>
                                <option value="featured" {{ request('sort') === 'featured' ? 'selected' : '' }}>Featured</option>
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
                    @if($products->count() > 0)
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
                                        @if($product->is_featured)
                                            <span class="bg-yellow-500 text-white text-xs px-2 py-1 rounded-full font-medium">
                                                Featured
                                            </span>
                                        @endif
                                        @if($product->sale_price && $product->sale_price < $product->price)
                                            @php
                                                $discount = round((($product->price - $product->sale_price) / $product->price) * 100);
                                            @endphp
                                            <span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full font-medium">
                                                -{{ $discount }}%
                                            </span>
                                        @endif
                                        @if($product->stock_quantity <= 0)
                                            <span class="bg-gray-500 text-white text-xs px-2 py-1 rounded-full font-medium">
                                                Out of Stock
                                            </span>
                                        @elseif($product->stock_quantity < 10)
                                            <span class="bg-orange-500 text-white text-xs px-2 py-1 rounded-full font-medium">
                                                Low Stock
                                            </span>
                                        @endif
                                    </div>

                                    <!-- Wishlist button - FIXED -->
                                    <button class="wishlist-btn absolute top-3 right-3 w-8 h-8 bg-white rounded-full flex items-center justify-center shadow-md hover:shadow-lg transition-all duration-200" 
                                            data-product-id="{{ $product->id }}"
                                            data-product-name="{{ $product->name }}">
                                        <i class="wishlist-icon far fa-heart text-gray-400 transition-colors"></i>
                                    </button>
                                </div>
                                
                                <div class="p-4">
                                    <div class="mb-2">
                                        <span class="text-xs text-gray-500 uppercase tracking-wide">
                                            {{ $product->category->name ?? 'Products' }}
                                            @if($product->brand)
                                                • {{ $product->brand }}
                                            @endif
                                        </span>
                                    </div>
                                    
                                    <h3 class="font-semibold text-gray-900 mb-3 text-sm leading-tight">
                                        <a href="{{ route('products.show', $product->slug) }}" 
                                           class="hover:text-blue-600 transition-colors">
                                            {{ $product->name }}
                                        </a>
                                    </h3>
                                    
                                    <!-- Display available sizes and colors if exist -->
                                    @if($product->available_sizes || $product->available_colors)
                                        <div class="mb-3 text-xs text-gray-500">
                                            @if($product->available_sizes)
                                                <div class="mb-1">
                                                    <span class="font-medium">Sizes:</span>
                                                    {{ is_array($product->available_sizes) ? implode(', ', array_slice($product->available_sizes, 0, 3)) : $product->available_sizes }}
                                                    @if(is_array($product->available_sizes) && count($product->available_sizes) > 3)
                                                        <span class="text-gray-400">+{{ count($product->available_sizes) - 3 }} more</span>
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
                                        @if($product->sale_price && $product->sale_price < $product->price)
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
                                    
                                    <!-- Stock Status -->
                                    <div class="mb-3">
                                        @if($product->stock_quantity > 0)
                                            <span class="text-xs text-green-600 font-medium">
                                                <i class="fas fa-check-circle mr-1"></i>
                                                In Stock ({{ $product->stock_quantity }} left)
                                            </span>
                                        @else
                                            <span class="text-xs text-red-600 font-medium">
                                                <i class="fas fa-times-circle mr-1"></i>
                                                Out of Stock
                                            </span>
                                        @endif
                                    </div>
                                    
                                    <div class="flex gap-2">
    @if($product->stock_quantity > 0)
        <form action="{{ route('cart.add') }}" method="POST" class="add-to-cart-form flex-1">
            @csrf
            <input type="hidden" name="product_id" value="{{ $product->id }}">
            <input type="hidden" name="quantity" value="1">
            <button type="submit" class="w-full bg-gray-900 text-white py-2 px-3 rounded-lg text-sm font-medium hover:bg-gray-800 transition-colors">
                <i class="fas fa-shopping-cart mr-1"></i>
                Add to Cart
            </button>
        </form>
    @else
        <button disabled class="flex-1 bg-gray-300 text-gray-500 py-2 px-3 rounded-lg text-sm font-medium cursor-not-allowed">
            <i class="fas fa-times mr-1"></i>
            Out of Stock
        </button>
    @endif
    <a href="{{ route('products.show', $product->slug) }}" class="px-3 py-2 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors flex items-center justify-center">
        <i class="fas fa-eye text-gray-600"></i>
    </a>
</div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <!-- Empty state -->
                        <div class="col-span-full text-center py-12">
                            <i class="fas fa-shoe-prints text-6xl text-gray-300 mb-4"></i>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">No products found</h3>
                            <p class="text-gray-500 mb-4">Try adjusting your filters or search terms</p>
                            <button onclick="clearFilters()" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                Clear All Filters
                            </button>
                        </div>
                    @endif
                </div>

                <!-- Pagination -->
                <div class="mt-8">
                    {{ $products->appends(request()->query())->links() }}
                </div>
            </main>
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
                    <button onclick="hideToast()" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- Add to Cart Success Toast -->
<div id="cart-toast" class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg transform translate-x-full transition-transform duration-300 z-50">
    <div class="flex items-center gap-2">
        <i class="fas fa-check-circle"></i>
        <span>Product added to cart!</span>
    </div>
</div>
@endsection

<!-- CSS -->
<style>
    /* Category Pills */
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

    .category-pill.special {
        color: #ff4757;
        border-color: #ff4757;
    }

    .category-pill.special.active {
        background: #ff4757;
        color: #ffffff;
    }

    /* Filter Sidebar Animation */
    #filterSidebar {
        transition: all 0.3s ease;
    }

    #filterSidebar.hidden {
        display: none;
    }

    /* Filter Options Styling */
    .filter-option {
        padding: 8px 12px;
        border-radius: 6px;
        transition: all 0.2s ease;
    }

    .filter-option:hover {
        background-color: #f3f4f6;
    }

    .filter-option.selected {
        background-color: #eff6ff;
        border-color: #3b82f6;
    }

    /* Size Options */
    .size-option {
        position: relative;
        transition: all 0.2s ease;
    }

    .size-option.selected {
        background-color: #3B82F6;
        color: white;
        border-color: #3B82F6;
        transform: scale(1.05);
    }

    .size-option.selected .size-check-icon {
        display: block !important;
    }

    /* Color Options */
    .color-option {
        position: relative;
        transition: all 0.2s ease;
    }

    .color-option.selected {
        box-shadow: 0 0 0 3px #3B82F6;
        transform: scale(1.1);
    }

    .color-option.selected .color-check-icon {
        display: flex !important;
    }

    .white-color-border {
        box-shadow: inset 0 0 0 1px #e5e7eb;
    }

    .white-color-border.selected {
        box-shadow: inset 0 0 0 1px #e5e7eb, 0 0 0 3px #3B82F6;
    }

    /* Wishlist button styles */
    .wishlist-btn {
        z-index: 10;
    }

    .wishlist-btn:hover .wishlist-icon {
        color: #ef4444;
        transform: scale(1.1);
    }

    .wishlist-btn.active .wishlist-icon {
        color: #ef4444;
    }

    .wishlist-btn.active .wishlist-icon::before {
        content: "\f004"; /* solid heart */
    }

    /* List view styles */
    .list-view .product-card {
        display: flex;
        flex-direction: row;
        max-width: none;
    }

    .list-view .product-card .aspect-square {
        aspect-ratio: 1;
        width: 200px;
        flex-shrink: 0;
    }

    /* Active filter badges */
    .filter-badge {
        animation: slideIn 0.3s ease-out;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Toast notification styles */
    #toastNotification {
        animation: slideInRight 0.3s ease-out;
    }

    #toastNotification.hiding {
        animation: slideOutRight 0.3s ease-in;
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

    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
</style>

<!-- JavaScript -->
<script>
    // Wishlist functionality
    let userWishlist = [];

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

        // Initialize wishlist functionality
        initWishlist();

        // Size selection with visual feedback
        document.querySelectorAll('.size-option').forEach(button => {
            button.addEventListener('click', function() {
                this.classList.toggle('selected');
                const checkIcon = this.querySelector('.size-check-icon');
                if (this.classList.contains('selected')) {
                    checkIcon.classList.remove('hidden');
                } else {
                    checkIcon.classList.add('hidden');
                }
                updateSizeFilter();
            });
        });

        // Color selection with visual feedback
        document.querySelectorAll('.color-option').forEach(button => {
            button.addEventListener('click', function() {
                this.classList.toggle('selected');
                const checkIcon = this.querySelector('.color-check-icon');
                if (this.classList.contains('selected')) {
                    checkIcon.classList.remove('hidden');
                } else {
                    checkIcon.classList.add('hidden');
                }
                updateColorFilter();
            });
        });

        // Filter option selection visual feedback
        document.querySelectorAll('.filter-option input').forEach(input => {
            input.addEventListener('change', function() {
                const label = this.closest('.filter-option');
                if (this.checked || this.selected) {
                    label.classList.add('selected');
                } else {
                    label.classList.remove('selected');
                }
            });
        });

        // View toggle functionality
        document.getElementById('gridView').addEventListener('click', function() {
            document.getElementById('productsContainer').classList.remove('list-view');
            document.getElementById('productsContainer').className = 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6';
            this.classList.add('text-blue-600', 'bg-blue-50');
            this.classList.remove('text-gray-400');
            document.getElementById('listView').classList.remove('text-blue-600', 'bg-blue-50');
            document.getElementById('listView').classList.add('text-gray-400');
        });

        document.getElementById('listView').addEventListener('click', function() {
            document.getElementById('productsContainer').className = 'list-view space-y-4';
            this.classList.add('text-blue-600', 'bg-blue-50');
            this.classList.remove('text-gray-400');
            document.getElementById('gridView').classList.remove('text-blue-600', 'bg-blue-50');
            document.getElementById('gridView').classList.add('text-gray-400');
        });

        // Dynamic subcategory filter based on main category
        const categoryRadios = document.querySelectorAll('input[name="category"]');
        categoryRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                // Reload page with new category to show relevant subcategories
                const form = document.getElementById('filterForm');
                const formData = new FormData(form);
                const params = new URLSearchParams();
                
                for (let [key, value] of formData.entries()) {
                    if (value) params.append(key, value);
                }
                
                window.location.href = '?' + params.toString();
            });
        });
    });

    // Initialize wishlist functionality
    function initWishlist() {
        // Check if user is authenticated
        @if(Auth::check())
            loadUserWishlist();
        @endif

        // Add event listeners to all wishlist buttons
        document.querySelectorAll('.wishlist-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                @guest
                    // Redirect to login if not authenticated
                    showToast('Please login to add items to wishlist', 'error');
                    setTimeout(() => {
                        window.location.href = '{{ route("login") }}';
                    }, 1500);
                    return;
                @endguest

                const productId = this.dataset.productId;
                const productName = this.dataset.productName;
                
                toggleWishlist(productId, productName, this);
            });
        });
    }

    // Load user's wishlist from server
    function loadUserWishlist() {
        fetch('{{ route("wishlist.count") }}', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            // Update wishlist UI based on server data
            updateWishlistUI();
        })
        .catch(error => {
            console.error('Error loading wishlist:', error);
        });

        // Check which products are in wishlist
        const productIds = Array.from(document.querySelectorAll('.wishlist-btn')).map(btn => btn.dataset.productId);
        
        if (productIds.length > 0) {
            fetch('{{ route("wishlist.check") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ product_ids: productIds })
            })
            .then(response => response.json())
            .then(data => {
                userWishlist = data.wishlist_products || [];
                updateWishlistUI();
            })
            .catch(error => {
                console.error('Error checking wishlist:', error);
            });
        }
    }

    // Toggle wishlist item
    function toggleWishlist(productId, productName, button) {
        button.style.pointerEvents = 'none'; // Disable button during request
        
        fetch(`{{ url('/wishlist/toggle') }}/${productId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.is_added) {
                    userWishlist.push(parseInt(productId));
                    showToast(`${productName} added to wishlist!`, 'success');
                } else {
                    userWishlist = userWishlist.filter(id => id !== parseInt(productId));
                    showToast(`${productName} removed from wishlist!`, 'info');
                }
                
                updateWishlistUI();
                updateWishlistCount(data.wishlist_count);
            } else {
                showToast(data.message || 'Something went wrong', 'error');
                if (data.redirect) {
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1500);
                }
            }
        })
        .catch(error => {
            console.error('Wishlist error:', error);
            showToast('Something went wrong. Please try again.', 'error');
        })
        .finally(() => {
            button.style.pointerEvents = 'auto'; // Re-enable button
        });
    }

    // Update wishlist UI
    function updateWishlistUI() {
        document.querySelectorAll('.wishlist-btn').forEach(button => {
            const productId = parseInt(button.dataset.productId);
            const icon = button.querySelector('.wishlist-icon');
            
            if (userWishlist.includes(productId)) {
                button.classList.add('active');
                icon.classList.remove('far');
                icon.classList.add('fas');
                icon.style.color = '#ef4444';
            } else {
                button.classList.remove('active');
                icon.classList.remove('fas');
                icon.classList.add('far');
                icon.style.color = '#9ca3af';
            }
        });
    }

    // Update wishlist count in header
    function updateWishlistCount(count) {
        const wishlistCountElement = document.querySelector('.wishlist-count');
        if (wishlistCountElement) {
            wishlistCountElement.textContent = count;
            if (count > 0) {
                wishlistCountElement.style.display = 'inline';
            } else {
                wishlistCountElement.style.display = 'none';
            }
        }
    }

    // Show toast notification
    function showToast(message, type = 'success') {
        const toast = document.getElementById('toastNotification');
        const icon = document.getElementById('toastIcon');
        const messageEl = document.getElementById('toastMessage');
        
        // Set message
        messageEl.textContent = message;
        
        // Set icon based on type
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
        
        // Show toast
        toast.classList.remove('hidden');
        
        // Auto hide after 3 seconds
        setTimeout(() => {
            hideToast();
        }, 3000);
    }

    // Hide toast notification
    function hideToast() {
        const toast = document.getElementById('toastNotification');
        toast.classList.add('hiding');
        
        setTimeout(() => {
            toast.classList.add('hidden');
            toast.classList.remove('hiding');
        }, 300);
    }

    // Individual filter removal functions
    function removeFilter(filterName) {
        const url = new URL(window.location);
        url.searchParams.delete(filterName);
        window.location = url;
    }

    function removeBrandFilter(brand) {
        const url = new URL(window.location);
        const brands = url.searchParams.getAll('brands[]');
        const filteredBrands = brands.filter(b => b !== brand);
        
        url.searchParams.delete('brands[]');
        filteredBrands.forEach(b => url.searchParams.append('brands[]', b));
        
        window.location = url;
    }

    function removeAvailabilityFilter(status) {
        const url = new URL(window.location);
        const availability = url.searchParams.getAll('availability[]');
        const filteredAvailability = availability.filter(a => a !== status);
        
        url.searchParams.delete('availability[]');
        filteredAvailability.forEach(a => url.searchParams.append('availability[]', a));
        
        window.location = url;
    }

    function removePriceFilter() {
        const url = new URL(window.location);
        url.searchParams.delete('min_price');
        url.searchParams.delete('max_price');
        window.location = url;
    }

    // Additional filter removal functions for colors and sizes
    function removeColorFilter(color) {
        const url = new URL(window.location);
        
        // Handle colors[] parameter
        const colors = url.searchParams.getAll('colors[]');
        const filteredColors = colors.filter(c => c !== color);
        
        url.searchParams.delete('colors[]');
        filteredColors.forEach(c => url.searchParams.append('colors[]', c));
        
        // Handle selected_colors parameter (comma-separated)
        const selectedColors = url.searchParams.get('selected_colors');
        if (selectedColors) {
            const colorArray = selectedColors.split(',');
            const filteredColorArray = colorArray.filter(c => c.trim() !== color);
            
            if (filteredColorArray.length > 0) {
                url.searchParams.set('selected_colors', filteredColorArray.join(','));
            } else {
                url.searchParams.delete('selected_colors');
            }
        }
        
        window.location = url;
    }

    function removeSizeFilter(size) {
        const url = new URL(window.location);
        
        // Handle sizes[] parameter
        const sizes = url.searchParams.getAll('sizes[]');
        const filteredSizes = sizes.filter(s => s !== size);
        
        url.searchParams.delete('sizes[]');
        filteredSizes.forEach(s => url.searchParams.append('sizes[]', s));
        
        // Handle selected_sizes parameter (comma-separated)
        const selectedSizes = url.searchParams.get('selected_sizes');
        if (selectedSizes) {
            const sizeArray = selectedSizes.split(',');
            const filteredSizeArray = sizeArray.filter(s => s.trim() !== size);
            
            if (filteredSizeArray.length > 0) {
                url.searchParams.set('selected_sizes', filteredSizeArray.join(','));
            } else {
                url.searchParams.delete('selected_sizes');
            }
        }
        
        window.location = url;
    }

    function updateSort(value) {
        const url = new URL(window.location);
        url.searchParams.set('sort', value);
        window.location = url;
    }

    function clearFilters() {
        window.location = window.location.pathname;
    }

    // Handle size filter selection for form submission
    function updateSizeFilter() {
        const selectedSizes = Array.from(document.querySelectorAll('.size-option.selected'))
            .map(btn => btn.dataset.size);
        
        document.getElementById('selectedSizes').value = selectedSizes.join(',');
        
        // Remove existing size inputs
        document.querySelectorAll('input[name="sizes[]"]').forEach(input => input.remove());
        
        // Add new size inputs
        const form = document.getElementById('filterForm');
        selectedSizes.forEach(size => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'sizes[]';
            input.value = size;
            form.appendChild(input);
        });
    }

    // Handle color filter selection for form submission
    function updateColorFilter() {
        const selectedColors = Array.from(document.querySelectorAll('.color-option.selected'))
            .map(btn => btn.dataset.color);
        
        document.getElementById('selectedColors').value = selectedColors.join(',');
        
        // Remove existing color inputs
        document.querySelectorAll('input[name="colors[]"]').forEach(input => input.remove());
        
        // Add new color inputs
        const form = document.getElementById('filterForm');
        selectedColors.forEach(color => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'colors[]';
            input.value = color;
            form.appendChild(input);
        });
    }

    // Update filters before form submission
    document.getElementById('filterForm').addEventListener('submit', function(e) {
        updateSizeFilter();
        updateColorFilter();
    });
    document.addEventListener('DOMContentLoaded', function() {
    console.log('🛒 Initializing add to cart functionality');
    
    // Find all add to cart forms
    const cartForms = document.querySelectorAll('.add-to-cart-form');
    const cartToast = document.getElementById('cart-toast');
    
    console.log('📝 Found', cartForms.length, 'add to cart forms');
    
    if (cartForms.length === 0) {
        console.warn('⚠️ No add to cart forms found');
        return;
    }
    
    // Initialize each form
    cartForms.forEach((form, index) => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            console.log('🚀 Add to cart form submitted');
            
            const formData = new FormData(this);
            const button = this.querySelector('button[type="submit"]');
            const originalText = button.innerHTML;
            
            // Check for CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) {
                console.error('❌ CSRF token not found');
                showToast('Security error. Please refresh the page.', 'error');
                return;
            }
            
            console.log('🔐 CSRF token found');
            
            // Show loading state
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Adding...';
            button.disabled = true;
            button.classList.add('opacity-75');
            
            // Make AJAX request
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                console.log('📡 Response status:', response.status);
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                return response.json();
            })
            .then(data => {
                console.log('📦 Response data:', data);
                
                if (data.success) {
                    console.log('✅ Product added to cart successfully');
                    
                    // Show success state
                    button.innerHTML = '<i class="fas fa-check mr-1"></i>Added!';
                    button.classList.remove('bg-gray-900', 'hover:bg-gray-800', 'opacity-75');
                    button.classList.add('bg-green-500', 'hover:bg-green-600');
                    
                    // Show success toast
                    if (cartToast) {
                        cartToast.classList.remove('translate-x-full');
                        setTimeout(() => {
                            cartToast.classList.add('translate-x-full');
                        }, 3000);
                    }
                    
                    // Update cart counter
                    updateCartCounter(data.cart_count);
                    
                    // Reset button after 2 seconds
                    setTimeout(() => {
                        resetButton(button, originalText);
                    }, 2000);
                    
                } else {
                    console.error('❌ Add to cart failed:', data.message);
                    showToast(data.message || 'Failed to add product to cart. Please try again.', 'error');
                    resetButton(button, originalText);
                }
            })
            .catch(error => {
                console.error('💥 Add to cart error:', error);
                showToast('Network error occurred. Please check your connection and try again.', 'error');
                resetButton(button, originalText);
            });
        });
    });
    
    // Helper function to reset button
    function resetButton(button, originalText) {
        button.innerHTML = originalText;
        button.disabled = false;
        button.classList.remove('bg-green-500', 'hover:bg-green-600', 'opacity-75');
        button.classList.add('bg-gray-900', 'hover:bg-gray-800');
    }
    
    // Helper function to update cart counter
    function updateCartCounter(count) {
        const cartCounters = document.querySelectorAll('.cart-counter, [data-cart-count]');
        cartCounters.forEach(counter => {
            counter.textContent = count;
            console.log('🔢 Cart counter updated to:', count);
        });
        
        // Update cart badge in navigation
        const cartBadge = document.querySelector('.cart-badge');
        if (cartBadge) {
            cartBadge.textContent = count;
            cartBadge.style.display = count > 0 ? 'block' : 'none';
        }
    }
    
    console.log('✅ Add to cart functionality initialized successfully');
});
</script>