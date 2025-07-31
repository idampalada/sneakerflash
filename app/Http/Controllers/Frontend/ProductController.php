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

        // Filter by category
        if ($request->category) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        // Filter by brand
        if ($request->brand) {
            $query->where('brand', $request->brand);
        }

        // Filter by price range
        if ($request->min_price) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->max_price) {
            $query->where('price', '<=', $request->max_price);
        }

        // Filter by featured
        if ($request->featured) {
            $query->where('is_featured', true);
        }

        // Search functionality
        if ($request->search) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', $search) // PostgreSQL case-insensitive
                  ->orWhere('description', 'ilike', $search)
                  ->orWhere('short_description', 'ilike', $search)
                  ->orWhere('brand', 'ilike', $search);
            });
        }

        // Sort functionality
        $sortBy = $request->sort ?? 'latest';
        switch ($sortBy) {
            case 'price_low':
                $query->orderByRaw('COALESCE(sale_price, price) ASC');
                break;
            case 'price_high':
                $query->orderByRaw('COALESCE(sale_price, price) DESC');
                break;
            case 'name':
                $query->orderBy('name', 'asc');
                break;
            case 'featured':
                $query->orderBy('is_featured', 'desc')->latest('created_at');
                break;
            default: // latest
                $query->latest('created_at');
        }

        $products = $query->paginate(12)->withQueryString();

        // Get categories for filter
        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        // Get brands for filter
        $brands = Product::query()
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereNotNull('brand')
            ->distinct()
            ->pluck('brand')
            ->sort()
            ->values();

        return view('frontend.products.index', compact('products', 'categories', 'brands'));
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

        // Get related products from same category
        $relatedProducts = Product::query()
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->take(4)
            ->get();

        return view('frontend.products.show', compact('product', 'relatedProducts'));
    }

    // =====================================
    // NEW NAVIGATION METHODS - Safe with Missing Columns
    // =====================================

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

        // Filter for mens products - Handle missing columns gracefully
        $query->where(function ($q) {
            // Check if columns exist before using them
            $hasGenderTarget = Schema::hasColumn('products', 'gender_target');
            $hasMenuPlacement = Schema::hasColumn('categories', 'menu_placement');
            $hasSecondaryMenus = Schema::hasColumn('categories', 'secondary_menus');
            
            if ($hasGenderTarget) {
                $q->where('gender_target', 'mens')
                  ->orWhere('gender_target', 'unisex');
            }
            
            // Category-based filtering (only if columns exist)
            if ($hasMenuPlacement || $hasSecondaryMenus) {
                $q->orWhereHas('category', function ($cat) use ($hasMenuPlacement, $hasSecondaryMenus) {
                    if ($hasMenuPlacement) {
                        $cat->where('menu_placement', 'mens');
                    }
                    if ($hasSecondaryMenus) {
                        $cat->orWhereJsonContains('secondary_menus', 'mens');
                    }
                });
            }
            
            // Name and description based filtering (always available)
            $q->orWhere('name', 'ilike', '%mens%')
              ->orWhere('name', 'ilike', '%man%')
              ->orWhere('name', 'ilike', '%pria%')
              ->orWhere('description', 'ilike', '%mens%')
              ->orWhere('description', 'ilike', '%man%')
              ->orWhere('description', 'ilike', '%pria%');
        });

        $this->applyFiltersAndSort($query, $request);
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

        // Filter for womens products - Handle missing columns gracefully
        $query->where(function ($q) {
            // Check if columns exist before using them
            $hasGenderTarget = Schema::hasColumn('products', 'gender_target');
            $hasMenuPlacement = Schema::hasColumn('categories', 'menu_placement');
            $hasSecondaryMenus = Schema::hasColumn('categories', 'secondary_menus');
            
            if ($hasGenderTarget) {
                $q->where('gender_target', 'womens')
                  ->orWhere('gender_target', 'unisex');
            }
            
            // Category-based filtering (only if columns exist)
            if ($hasMenuPlacement || $hasSecondaryMenus) {
                $q->orWhereHas('category', function ($cat) use ($hasMenuPlacement, $hasSecondaryMenus) {
                    if ($hasMenuPlacement) {
                        $cat->where('menu_placement', 'womens');
                    }
                    if ($hasSecondaryMenus) {
                        $cat->orWhereJsonContains('secondary_menus', 'womens');
                    }
                });
            }
            
            // Name and description based filtering (always available)
            $q->orWhere('name', 'ilike', '%womens%')
              ->orWhere('name', 'ilike', '%women%')
              ->orWhere('name', 'ilike', '%woman%')
              ->orWhere('name', 'ilike', '%wanita%')
              ->orWhere('description', 'ilike', '%womens%')
              ->orWhere('description', 'ilike', '%women%')
              ->orWhere('description', 'ilike', '%woman%')
              ->orWhere('description', 'ilike', '%wanita%');
        });

        $this->applyFiltersAndSort($query, $request);
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

        // Filter for kids products - Handle missing columns gracefully
        $query->where(function ($q) {
            // Check if columns exist before using them
            $hasGenderTarget = Schema::hasColumn('products', 'gender_target');
            $hasMenuPlacement = Schema::hasColumn('categories', 'menu_placement');
            $hasSecondaryMenus = Schema::hasColumn('categories', 'secondary_menus');
            
            if ($hasGenderTarget) {
                $q->where('gender_target', 'kids');
            }
            
            // Category-based filtering (only if columns exist)
            if ($hasMenuPlacement || $hasSecondaryMenus) {
                $q->orWhereHas('category', function ($cat) use ($hasMenuPlacement, $hasSecondaryMenus) {
                    if ($hasMenuPlacement) {
                        $cat->where('menu_placement', 'kids');
                    }
                    if ($hasSecondaryMenus) {
                        $cat->orWhereJsonContains('secondary_menus', 'kids');
                    }
                });
            }
            
            // Name and description based filtering (always available)
            $q->orWhere('name', 'ilike', '%kids%')
              ->orWhere('name', 'ilike', '%children%')
              ->orWhere('name', 'ilike', '%child%')
              ->orWhere('name', 'ilike', '%anak%')
              ->orWhere('description', 'ilike', '%kids%')
              ->orWhere('description', 'ilike', '%children%')
              ->orWhere('description', 'ilike', '%child%')
              ->orWhere('description', 'ilike', '%anak%');
        });

        $this->applyFiltersAndSort($query, $request);
        $products = $query->paginate(12)->withQueryString();

        $pageTitle = 'Kids Collection';
        $pageDescription = 'Discover our latest kids sneaker collection';
        
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

        $this->applyFiltersAndSort($query, $request);
        
        // Group by brand for display
        $products = $query->paginate(12)->withQueryString();
        
        // Get all available brands
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

        // Filter for accessories - Handle missing columns gracefully
        $query->where(function ($q) {
            // Check if columns exist before using them
            $hasProductType = Schema::hasColumn('products', 'product_type');
            $hasMenuPlacement = Schema::hasColumn('categories', 'menu_placement');
            $hasSecondaryMenus = Schema::hasColumn('categories', 'secondary_menus');
            
            $accessoryTypes = ['backpack', 'bag', 'hat', 'cap', 'socks', 'laces', 'care_products', 'accessories'];
            
            if ($hasProductType) {
                $q->whereIn('product_type', $accessoryTypes);
            }
            
            // Category-based filtering (only if columns exist)
            if ($hasMenuPlacement || $hasSecondaryMenus) {
                $q->orWhereHas('category', function ($cat) use ($hasMenuPlacement, $hasSecondaryMenus) {
                    if ($hasMenuPlacement) {
                        $cat->where('menu_placement', 'accessories');
                    }
                    if ($hasSecondaryMenus) {
                        $cat->orWhereJsonContains('secondary_menus', 'accessories');
                    }
                });
            }
            
            // Name and description based filtering (always available)
            $q->orWhere('name', 'ilike', '%accessories%')
              ->orWhere('name', 'ilike', '%accessory%')
              ->orWhere('name', 'ilike', '%aksesoris%')
              ->orWhere('name', 'ilike', '%bag%')
              ->orWhere('name', 'ilike', '%backpack%')
              ->orWhere('name', 'ilike', '%hat%')
              ->orWhere('name', 'ilike', '%cap%')
              ->orWhere('name', 'ilike', '%sock%')
              ->orWhere('name', 'ilike', '%lace%')
              ->orWhere('description', 'ilike', '%accessories%')
              ->orWhere('description', 'ilike', '%aksesoris%');
        });

        $this->applyFiltersAndSort($query, $request);
        $products = $query->paginate(12)->withQueryString();

        $pageTitle = 'Accessories';
        $pageDescription = 'Complete your look with our premium accessories';
        
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
            ->whereRaw('sale_price < price') // PostgreSQL compatible
            ->with('category');

        // Check sale date validity - Handle missing columns gracefully
        $hasSaleDates = Schema::hasColumn('products', 'sale_start_date') && Schema::hasColumn('products', 'sale_end_date');
        
        if ($hasSaleDates) {
            $query->where(function ($q) {
                $q->whereNull('sale_start_date')
                  ->orWhere('sale_start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('sale_end_date')
                  ->orWhere('sale_end_date', '>=', now());
            });
        }

        $this->applyFiltersAndSort($query, $request);
        
        // Order by discount percentage (highest first) - PostgreSQL compatible
        $query->orderByRaw('((price - sale_price)::decimal / price::decimal) DESC');
        
        $products = $query->paginate(12)->withQueryString();

        $pageTitle = 'Sale Products';
        $pageDescription = 'Get the best deals on premium sneakers and accessories';
        
        return view('frontend.products.sale', compact('products', 'pageTitle', 'pageDescription'));
    }

    // =====================================
    // HELPER METHODS - Safe with Missing Columns
    // =====================================

    /**
     * Apply common filters and sorting
     */
    private function applyFiltersAndSort($query, Request $request)
    {
        // Filter by brand
        if ($request->brand) {
            $query->where('brand', $request->brand);
        }

        // Filter by price range
        if ($request->min_price) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->max_price) {
            $query->where('price', '<=', $request->max_price);
        }

        // Search functionality - PostgreSQL case-insensitive
        if ($request->search) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search, $request) {
                $q->where('name', 'ilike', $search)
                  ->orWhere('description', 'ilike', $search)
                  ->orWhere('short_description', 'ilike', $search)
                  ->orWhere('brand', 'ilike', $search);
                
                // Only search in search_keywords if column exists
                if (Schema::hasColumn('products', 'search_keywords')) {
                    $q->orWhereJsonContains('search_keywords', $request->search);
                }
            });
        }

        // Sort functionality
        $sortBy = $request->sort ?? 'latest';
        switch ($sortBy) {
            case 'price_low':
                $query->orderByRaw('COALESCE(sale_price, price) ASC');
                break;
            case 'price_high':
                $query->orderByRaw('COALESCE(sale_price, price) DESC');
                break;
            case 'name':
                $query->orderBy('name', 'asc');
                break;
            case 'featured':
                $query->orderBy('is_featured', 'desc')->latest('created_at');
                break;
            default: // latest
                $query->latest('created_at');
        }
    }

    /**
     * Get brands for filter
     */
    private function getBrands()
    {
        return Product::query()
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereNotNull('brand')
            ->distinct()
            ->pluck('brand')
            ->sort()
            ->values();
    }

    // =====================================
    // EXISTING METHODS (Safe with Missing Columns)
    // =====================================

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

        if ($request->q) {
            $search = '%' . $request->q . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', $search)
                  ->orWhere('description', 'ilike', $search)
                  ->orWhere('short_description', 'ilike', $search)
                  ->orWhere('brand', 'ilike', $search);
            });
        }

        $this->applyFiltersAndSort($query, $request);
        $products = $query->paginate(12)->withQueryString();

        $pageTitle = 'Search Results';
        if ($request->q) {
            $pageTitle .= ' for "' . $request->q . '"';
        }
        
        return view('frontend.products.search', compact('products', 'pageTitle'));
    }

    /**
     * Filter products
     */
    public function filter(Request $request)
    {
        return $this->index($request);
    }

    /**
     * Products by specific brand
     */
    public function byBrand($brand, Request $request)
    {
        $query = Product::query()
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where('brand', $brand)
            ->with('category');

        $this->applyFiltersAndSort($query, $request);
        $products = $query->paginate(12)->withQueryString();

        $pageTitle = $brand . ' Products';
        $pageDescription = 'Shop the latest ' . $brand . ' collection';
        
        return view('frontend.products.brand-single', compact('products', 'pageTitle', 'pageDescription', 'brand'));
    }

    // =====================================
    // API METHODS
    // =====================================

    /**
     * Quick search for autocomplete
     */
    public function quickSearch(Request $request)
    {
        if (!$request->q) {
            return response()->json([]);
        }

        $search = '%' . $request->q . '%';
        $products = Product::query()
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where(function ($q) use ($search) {
                $q->where('name', 'ilike', $search)
                  ->orWhere('brand', 'ilike', $search);
            })
            ->limit(10)
            ->get(['id', 'name', 'slug', 'brand', 'price', 'sale_price']);

        return response()->json($products);
    }

    /**
     * Get product variants
     */
    public function getVariants($id)
    {
        $product = Product::findOrFail($id);
        
        // Check if columns exist before accessing them
        $variants = [
            'sizes' => Schema::hasColumn('products', 'available_sizes') ? ($product->available_sizes ?? []) : [],
            'colors' => Schema::hasColumn('products', 'available_colors') ? ($product->available_colors ?? []) : [],
        ];

        return response()->json($variants);
    }

    /**
     * Check product stock
     */
    public function checkStock($id)
    {
        $product = Product::findOrFail($id);
        
        return response()->json([
            'in_stock' => $product->stock_quantity > 0,
            'stock_quantity' => $product->stock_quantity,
            'stock_status' => $product->stock_quantity > 0 ? 'in_stock' : 'out_of_stock',
        ]);
    }
}