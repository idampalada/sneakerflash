<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query()
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->with('category');

        // Apply all filters
        $this->applyAllFilters($query, $request);
        $this->applySorting($query, $request);
        
        $products = $query->paginate(12)->withQueryString();

        // Get real data for filters
        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Get real brands from database
        $brands = Product::query()
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereNotNull('brand')
            ->distinct()
            ->pluck('brand')
            ->sort()
            ->values();

        // Get availability counts
        $stockCounts = $this->getStockCounts();

        // Get size options if available_sizes column exists
        $availableSizes = [];
        if (Schema::hasColumn('products', 'available_sizes')) {
            $availableSizes = Product::query()
                ->where('is_active', true)
                ->whereNotNull('available_sizes')
                ->get()
                ->pluck('available_sizes')
                ->flatten()
                ->unique()
                ->sort()
                ->values();
        }

        // Get color options if available_colors column exists
        $availableColors = [];
        if (Schema::hasColumn('products', 'available_colors')) {
            $availableColors = Product::query()
                ->where('is_active', true)
                ->whereNotNull('available_colors')
                ->get()
                ->pluck('available_colors')
                ->flatten()
                ->unique()
                ->sort()
                ->values();
        }

        return view('frontend.products.index', compact(
            'products', 
            'categories', 
            'brands', 
            'stockCounts',
            'availableSizes',
            'availableColors'
        ));
    }

    public function show($slug)
    {
        $product = Product::query()
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->with(['category'])
            ->where('slug', $slug)
            ->firstOrFail();

        // Get related products from same category or gender
        $relatedQuery = Product::query()
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where('id', '!=', $product->id);

        // If product has gender_target, find products with same gender
        if (Schema::hasColumn('products', 'gender_target') && $product->gender_target) {
            $relatedQuery->where(function ($q) use ($product) {
                foreach ($product->gender_target as $gender) {
                    $q->orWhereJsonContains('gender_target', $gender);
                }
            });
        } else {
            // Fallback to same category
            $relatedQuery->where('category_id', $product->category_id);
        }

        $relatedProducts = $relatedQuery->take(4)->get();

        return view('frontend.products.show', compact('product', 'relatedProducts'));
    }

    /**
     * Display mens products
     */
    public function mens(Request $request)
    {
        $query = Product::query()
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->with('category');

        // Filter for mens products - JSON array search
        if (Schema::hasColumn('products', 'gender_target')) {
            $query->whereJsonContains('gender_target', 'mens');
        }

        // Apply all other filters
        $this->applyAllFilters($query, $request);
        $this->applySorting($query, $request);
        
        $products = $query->paginate(12)->withQueryString();

        $pageTitle = 'Mens Collection';
        $pageDescription = 'Discover our latest mens sneaker collection';
        
        return view('frontend.products.category', compact('products', 'pageTitle', 'pageDescription'));
    }

    /**
     * Display womens products
     */
    public function womens(Request $request)
    {
        $query = Product::query()
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->with('category');

        // Filter for womens products - JSON array search
        if (Schema::hasColumn('products', 'gender_target')) {
            $query->whereJsonContains('gender_target', 'womens');
        }

        // Apply all other filters
        $this->applyAllFilters($query, $request);
        $this->applySorting($query, $request);
        
        $products = $query->paginate(12)->withQueryString();

        $pageTitle = 'Womens Collection';
        $pageDescription = 'Discover our latest womens sneaker collection';
        
        return view('frontend.products.category', compact('products', 'pageTitle', 'pageDescription'));
    }

    /**
     * Display kids products
     */
    public function kids(Request $request)
    {
        $query = Product::query()
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->with('category');

        // Filter for kids products - JSON array search
        if (Schema::hasColumn('products', 'gender_target')) {
            $query->whereJsonContains('gender_target', 'kids');
        }

        // Apply all other filters
        $this->applyAllFilters($query, $request);
        $this->applySorting($query, $request);
        
        $products = $query->paginate(12)->withQueryString();

        $pageTitle = 'Kids Collection';
        $pageDescription = 'Discover our latest kids sneaker collection';
        
        return view('frontend.products.category', compact('products', 'pageTitle', 'pageDescription'));
    }

    /**
     * Display sale products
     */
    public function sale(Request $request)
    {
        $query = Product::query()
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereNotNull('sale_price')
            ->whereRaw('sale_price < price')
            ->with('category');

        // Check sale date validity if columns exist
        if (Schema::hasColumn('products', 'sale_start_date')) {
            $query->where(function ($q) {
                $q->whereNull('sale_start_date')
                  ->orWhere('sale_start_date', '<=', now());
            });
        }

        if (Schema::hasColumn('products', 'sale_end_date')) {
            $query->where(function ($q) {
                $q->whereNull('sale_end_date')
                  ->orWhere('sale_end_date', '>=', now());
            });
        }

        // Apply all other filters
        $this->applyAllFilters($query, $request);
        
        // Default sort by discount percentage (highest first)
        if (!$request->filled('sort')) {
            $query->orderByRaw('((price - sale_price)::decimal / price::decimal) DESC');
        } else {
            $this->applySorting($query, $request);
        }
        
        $products = $query->paginate(12)->withQueryString();

        $pageTitle = 'Sale Products';
        $pageDescription = 'Get the best deals on premium sneakers and accessories';
        
        return view('frontend.products.category', compact('products', 'pageTitle', 'pageDescription'));
    }

    /**
     * Display products by brand
     */
    public function brand(Request $request)
    {
        $query = Product::query()
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereNotNull('brand')
            ->with('category');

        // Apply all filters including brand filter
        $this->applyAllFilters($query, $request);
        $this->applySorting($query, $request);
        
        $products = $query->paginate(12)->withQueryString();
        
        // Get all available brands - REAL DATA ONLY
        $brands = Product::query()
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereNotNull('brand')
            ->distinct()
            ->pluck('brand')
            ->sort()
            ->values();

        $pageTitle = 'Shop by Brand';
        $pageDescription = 'Explore products from your favorite brands';
        
        return view('frontend.products.brands', compact('products', 'brands', 'pageTitle', 'pageDescription'));
    }

    /**
     * Display accessories products
     */
    public function accessories(Request $request)
    {
        $query = Product::query()
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->with('category');

        // Filter for accessories based on product_type
        if (Schema::hasColumn('products', 'product_type')) {
            $query->whereIn('product_type', [
                'accessories', 'backpack', 'bag', 'hat', 'socks', 'laces', 'care_products'
            ]);
        } else {
            // Fallback to name/description search
            $query->where(function ($q) {
                $q->where('name', 'ilike', '%accessories%')
                  ->orWhere('name', 'ilike', '%bag%')
                  ->orWhere('name', 'ilike', '%backpack%')
                  ->orWhere('name', 'ilike', '%hat%')
                  ->orWhere('name', 'ilike', '%cap%')
                  ->orWhere('name', 'ilike', '%socks%')
                  ->orWhere('description', 'ilike', '%accessories%');
            });
        }

        // Apply all other filters
        $this->applyAllFilters($query, $request);
        $this->applySorting($query, $request);
        
        $products = $query->paginate(12)->withQueryString();

        $pageTitle = 'Accessories';
        $pageDescription = 'Complete your look with our accessories collection';
        
        return view('frontend.products.category', compact('products', 'pageTitle', 'pageDescription'));
    }

    /**
     * Search products
     */
    public function search(Request $request)
    {
        $query = Product::query()
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->with('category');

        $searchQuery = $request->q ?? $request->search;

        // Apply search if query exists
        if ($searchQuery) {
            $search = '%' . $searchQuery . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', $search)
                  ->orWhere('description', 'ilike', $search)
                  ->orWhere('short_description', 'ilike', $search)
                  ->orWhere('brand', 'ilike', $search);
                
                // Search in search_keywords if column exists
                if (Schema::hasColumn('products', 'search_keywords')) {
                    $q->orWhereJsonContains('search_keywords', $search);
                }
            });
        }

        // Apply all other filters
        $this->applyAllFilters($query, $request);

        // Apply sorting (default to relevance for search)
        $request->merge(['sort' => $request->sort ?? 'relevance']);
        $this->applySorting($query, $request);

        $products = $query->paginate(12)->withQueryString();

        // Get real data for filters
        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $brands = Product::query()
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereNotNull('brand')
            ->distinct()
            ->pluck('brand')
            ->sort()
            ->values();

        return view('frontend.products.search', compact(
            'products', 
            'categories', 
            'brands', 
            'searchQuery'
        ));
    }

    /**
     * Apply all filters to query - SYNCED WITH ADMIN FIELDS
     */
    private function applyAllFilters($query, Request $request)
    {
        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by brand(s) - sesuai dengan field 'brand' di admin
        if ($request->filled('brand')) {
            if (is_array($request->brand)) {
                $query->whereIn('brand', $request->brand);
            } else {
                $query->where('brand', $request->brand);
            }
        }

        if ($request->filled('brands')) {
            $brands = is_array($request->brands) ? $request->brands : [$request->brands];
            $query->whereIn('brand', $brands);
        }

        // Filter by gender category (JSON array search) - sesuai dengan 'gender_target'
        if ($request->filled('category')) {
            $category = $request->category;
            if (in_array($category, ['mens', 'womens', 'kids']) && Schema::hasColumn('products', 'gender_target')) {
                $query->whereJsonContains('gender_target', $category);
            }
        }

        // Filter by product type - sesuai dengan field 'product_type' di admin
        if ($request->filled('type') && Schema::hasColumn('products', 'product_type')) {
            $type = $request->type;
            
            // Map frontend type ke admin product_type
            $typeMapping = [
                'lifestyle' => 'lifestyle_casual',
                'running' => 'running',
                'training' => 'training',
                'basketball' => 'basketball',
                'sneakers' => 'sneakers',
                'formal' => 'formal',
                'sandals' => 'sandals',
                'boots' => 'boots'
            ];
            
            if (isset($typeMapping[$type])) {
                $query->where('product_type', $typeMapping[$type]);
            } else {
                $query->where('product_type', $type);
            }
        }

        // Filter by sale products - sesuai dengan 'sale_price'
        if ($request->filled('sale') && $request->sale == 'true') {
            $query->whereNotNull('sale_price')->whereRaw('sale_price < price');
        }

        // Filter by featured products - sesuai dengan 'is_featured'
        if ($request->filled('featured') && $request->featured == '1') {
            $query->where('is_featured', true);
        }

        // Filter by price range - menggunakan sale_price jika ada
        if ($request->filled('min_price')) {
            $query->whereRaw('COALESCE(sale_price, price) >= ?', [$request->min_price]);
        }
        if ($request->filled('max_price')) {
            $query->whereRaw('COALESCE(sale_price, price) <= ?', [$request->max_price]);
        }

        // Filter by stock availability - sesuai dengan 'stock_quantity'
        if ($request->filled('availability')) {
            $availability = is_array($request->availability) ? $request->availability : [$request->availability];
            
            $query->where(function ($q) use ($availability) {
                if (in_array('in_stock', $availability)) {
                    $q->orWhere('stock_quantity', '>', 0);
                }
                if (in_array('not_available', $availability)) {
                    $q->orWhere('stock_quantity', '<=', 0);
                }
            });
        }

        // Filter by sizes - sesuai dengan field 'available_sizes'
        if ($request->filled('sizes') && Schema::hasColumn('products', 'available_sizes')) {
            $sizes = is_array($request->sizes) ? $request->sizes : [$request->sizes];
            
            $query->where(function ($q) use ($sizes) {
                foreach ($sizes as $size) {
                    $q->orWhereJsonContains('available_sizes', $size);
                }
            });
        }

        // Filter by colors - sesuai dengan field 'available_colors'
        if ($request->filled('color') && Schema::hasColumn('products', 'available_colors')) {
            $query->whereJsonContains('available_colors', $request->color);
        }

        if ($request->filled('colors') && Schema::hasColumn('products', 'available_colors')) {
            $colors = is_array($request->colors) ? $request->colors : [$request->colors];
            
            $query->where(function ($q) use ($colors) {
                foreach ($colors as $color) {
                    $q->orWhereJsonContains('available_colors', $color);
                }
            });
        }

        // Filter by conditions/features - sesuai dengan field 'features'
        if ($request->filled('conditions') && Schema::hasColumn('products', 'features')) {
            $conditions = is_array($request->conditions) ? $request->conditions : [$request->conditions];
            
            $query->where(function ($q) use ($conditions) {
                foreach ($conditions as $condition) {
                    $conditionMapping = [
                        'express_shipping' => 'Express Shipping',
                        'brand_new' => 'Brand New',
                        'used' => 'Used',
                        'pre_order' => 'Pre-Order'
                    ];
                    
                    $feature = $conditionMapping[$condition] ?? $condition;
                    $q->orWhereJsonContains('features', $feature);
                }
            });
        }
    }

    /**
     * Apply sorting to query
     */
    private function applySorting($query, Request $request)
    {
        $sortBy = $request->sort ?? 'latest';
        
        switch ($sortBy) {
            case 'price_low':
                $query->orderByRaw('COALESCE(sale_price, price) ASC');
                break;
            case 'price_high':  
                $query->orderByRaw('COALESCE(sale_price, price) DESC');
                break;
            case 'name':
            case 'name_az':
                $query->orderBy('name', 'asc');
                break;
            case 'name_za':
                $query->orderBy('name', 'desc');
                break;
            case 'featured':
                $query->orderBy('is_featured', 'desc')
                      ->orderBy('created_at', 'desc');
                break;
            case 'relevance':
                // For search results, order by relevance
                if ($request->filled('search') || $request->filled('q')) {
                    $search = $request->search ?? $request->q;
                    $query->orderByRaw("
                        CASE 
                            WHEN name ILIKE ? THEN 1
                            WHEN brand ILIKE ? THEN 2  
                            WHEN description ILIKE ? THEN 3
                            ELSE 4
                        END
                    ", ["%{$search}%", "%{$search}%", "%{$search}%"]);
                } else {
                    $query->orderBy('created_at', 'desc');
                }
                break;
            case 'latest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }
    }

    /**
     * Get stock availability counts
     */
    private function getStockCounts()
    {
        $inStock = Product::query()
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where('stock_quantity', '>', 0)
            ->count();

        $outOfStock = Product::query()
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where('stock_quantity', '<=', 0)
            ->count();

        return [
            'in_stock' => $inStock,
            'not_available' => $outOfStock
        ];
    }
}