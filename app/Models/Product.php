<?php
// File: app/Models/Product.php - PostgreSQL Compatible Version

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'short_description',
        'description',
        'price',
        'sale_price',
        'sku',
        'category_id',
        'brand',
        
        // NEW FIELDS FOR MENU SYSTEM
        'gender_target',
        'product_type',
        'search_keywords',
        'sale_start_date',
        'sale_end_date',
        'is_featured_sale',
        'available_sizes',
        'available_colors',
        'meta_title',
        'meta_description',
        'meta_keywords',
        
        // EXISTING FIELDS
        'images',
        'features',
        'specifications',
        'is_active',
        'is_featured',
        'stock_quantity',
        'min_stock_level',
        'weight',
        'dimensions',
        'published_at',
        'meta_data',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'weight' => 'decimal:2',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_featured_sale' => 'boolean',
            'images' => 'array',
            'features' => 'array',
            'specifications' => 'array',
            'dimensions' => 'array',
            'meta_data' => 'array',
            
            // NEW CASTS
            'search_keywords' => 'array',
            'available_sizes' => 'array',
            'available_colors' => 'array',
            'meta_keywords' => 'array',
            'sale_start_date' => 'date',
            'sale_end_date' => 'date',
            
            'published_at' => 'datetime',
        ];
    }

    // ========================================
    // RELATIONSHIPS
    // ========================================

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    // ========================================
    // ACCESSORS & MUTATORS
    // ========================================

    /**
     * Get the featured image (first image)
     */
    public function getFeaturedImageAttribute(): ?string
    {
        if ($this->images && count($this->images) > 0) {
            return $this->images[0];
        }
        return null;
    }

    /**
     * Get the featured image URL
     */
    public function getFeaturedImageUrlAttribute(): ?string
    {
        if ($this->featured_image) {
            return Storage::url($this->featured_image);
        }
        return null;
    }

    /**
     * Get all image URLs
     */
    public function getImageUrlsAttribute(): array
    {
        if (!$this->images) {
            return [];
        }

        return collect($this->images)->map(function ($image) {
            return Storage::url($image);
        })->toArray();
    }

    /**
     * Get formatted price
     */
    public function getFormattedPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->price, 0, ',', '.');
    }

    /**
     * Get formatted sale price
     */
    public function getFormattedSalePriceAttribute(): ?string
    {
        if ($this->sale_price) {
            return 'Rp ' . number_format($this->sale_price, 0, ',', '.');
        }
        return null;
    }

    /**
     * Get the effective price (sale price if available, otherwise regular price)
     */
    public function getEffectivePriceAttribute(): float
    {
        return $this->sale_price ?? $this->price;
    }

    /**
     * Get formatted effective price
     */
    public function getFormattedEffectivePriceAttribute(): string
    {
        return 'Rp ' . number_format($this->effective_price, 0, ',', '.');
    }

    /**
     * Check if product has discount
     */
    public function getHasDiscountAttribute(): bool
    {
        return !is_null($this->sale_price) && $this->sale_price < $this->price;
    }

    /**
     * Get discount percentage
     */
    public function getDiscountPercentageAttribute(): ?int
    {
        if (!$this->has_discount) {
            return null;
        }

        return round((($this->price - $this->sale_price) / $this->price) * 100);
    }

    /**
     * Check if product is in stock
     */
    public function getInStockAttribute(): bool
    {
        return $this->stock_quantity > 0;
    }

    /**
     * Check if stock is low
     */
    public function getIsLowStockAttribute(): bool
    {
        return $this->stock_quantity <= $this->min_stock_level && $this->stock_quantity > 0;
    }

    /**
     * Get stock status
     */
    public function getStockStatusAttribute(): string
    {
        if ($this->stock_quantity === 0) {
            return 'out_of_stock';
        } elseif ($this->is_low_stock) {
            return 'low_stock';
        } else {
            return 'in_stock';
        }
    }

    /**
     * Check if sale is currently active
     */
    public function getIsSaleActiveAttribute(): bool
    {
        if (!$this->sale_price) {
            return false;
        }

        $now = now()->toDateString();
        
        // If no dates set, sale is active
        if (!$this->sale_start_date && !$this->sale_end_date) {
            return true;
        }

        // Check if current date is within sale period
        $afterStart = !$this->sale_start_date || $now >= $this->sale_start_date->toDateString();
        $beforeEnd = !$this->sale_end_date || $now <= $this->sale_end_date->toDateString();

        return $afterStart && $beforeEnd;
    }

    // ========================================
    // SCOPES FOR NEW MENU SYSTEM - PostgreSQL Compatible
    // ========================================

    /**
     * Scope untuk produk aktif
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope untuk produk featured
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope untuk produk yang tersedia (published dan in stock)
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_active', true)
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now())
                    ->where('stock_quantity', '>', 0);
    }

    /**
     * Scope untuk gender targeting (MENS/WOMENS/KIDS menu) - PostgreSQL Compatible
     */
    public function scopeForGender(Builder $query, string $gender): Builder
    {
        return $query->where(function ($q) use ($gender) {
            $q->where('gender_target', $gender)
              ->orWhere('gender_target', 'unisex')
              ->orWhereHas('category', function ($cat) use ($gender) {
                  $cat->where('menu_placement', $gender)
                      ->orWhereJsonContains('secondary_menus', $gender);
              })
              ->orWhere('name', 'ilike', "%{$gender}%") // PostgreSQL case-insensitive
              ->orWhere('description', 'ilike', "%{$gender}%");
        });
    }

    /**
     * Scope untuk accessories (ACCESSORIES menu) - PostgreSQL Compatible
     */
    public function scopeAccessories(Builder $query): Builder
    {
        $accessoryTypes = ['backpack', 'bag', 'hat', 'cap', 'socks', 'laces', 'care_products', 'accessories'];
        
        return $query->where(function ($q) use ($accessoryTypes) {
            $q->whereIn('product_type', $accessoryTypes)
              ->orWhereHas('category', function ($cat) {
                  $cat->where('menu_placement', 'accessories')
                      ->orWhereJsonContains('secondary_menus', 'accessories');
              })
              ->orWhere('name', 'ilike', '%accessories%') // PostgreSQL case-insensitive
              ->orWhere('name', 'ilike', '%bag%')
              ->orWhere('name', 'ilike', '%hat%')
              ->orWhere('name', 'ilike', '%sock%')
              ->orWhere('name', 'ilike', '%lace%');
        });
    }

    /**
     * Scope untuk produk sale (SALE menu) - PostgreSQL Compatible
     */
    public function scopeOnSale(Builder $query): Builder
    {
        return $query->whereNotNull('sale_price')
                    ->whereRaw('sale_price < price') // PostgreSQL compatible
                    ->where(function ($q) {
                        $q->whereNull('sale_start_date')
                          ->orWhere('sale_start_date', '<=', now());
                    })
                    ->where(function ($q) {
                        $q->whereNull('sale_end_date')
                          ->orWhere('sale_end_date', '>=', now());
                    });
    }

    /**
     * Scope untuk filter berdasarkan brand
     */
    public function scopeByBrand(Builder $query, string $brand): Builder
    {
        return $query->where('brand', $brand);
    }

    /**
     * Scope untuk filter berdasarkan price range
     */
    public function scopePriceRange(Builder $query, $minPrice = null, $maxPrice = null): Builder
    {
        if ($minPrice) {
            $query->where('price', '>=', $minPrice);
        }
        
        if ($maxPrice) {
            $query->where('price', '<=', $maxPrice);
        }

        return $query;
    }

    /**
     * Scope untuk search - PostgreSQL Compatible
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $searchTerm = '%' . $term . '%';
        
        return $query->where(function ($q) use ($searchTerm, $term) {
            $q->where('name', 'ilike', $searchTerm) // PostgreSQL case-insensitive
              ->orWhere('description', 'ilike', $searchTerm)
              ->orWhere('short_description', 'ilike', $searchTerm)
              ->orWhere('brand', 'ilike', $searchTerm)
              ->orWhereJsonContains('search_keywords', $term)
              ->orWhereHas('category', function ($cat) use ($searchTerm) {
                  $cat->where('name', 'ilike', $searchTerm);
              });
        });
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    /**
     * Check if product matches gender target
     */
    public function isForGender(string $gender): bool
    {
        return $this->gender_target === $gender || 
               $this->gender_target === 'unisex' ||
               $this->category?->menu_placement === $gender ||
               in_array($gender, $this->category?->secondary_menus ?? []);
    }

    /**
     * Check if product is accessory
     */
    public function isAccessory(): bool
    {
        $accessoryTypes = ['backpack', 'bag', 'hat', 'cap', 'socks', 'laces', 'care_products', 'accessories'];
        
        return in_array($this->product_type, $accessoryTypes) ||
               $this->category?->menu_placement === 'accessories' ||
               in_array('accessories', $this->category?->secondary_menus ?? []);
    }

    /**
     * Get menu classifications for this product
     */
    public function getMenuClassifications(): array
    {
        $menus = [];
        
        // Gender-based menus
        if ($this->isForGender('mens')) $menus[] = 'mens';
        if ($this->isForGender('womens')) $menus[] = 'womens';
        if ($this->isForGender('kids')) $menus[] = 'kids';
        
        // Brand menu (all products with brand)
        if ($this->brand) $menus[] = 'brand';
        
        // Accessories menu
        if ($this->isAccessory()) $menus[] = 'accessories';
        
        // Sale menu
        if ($this->is_sale_active && $this->sale_price) $menus[] = 'sale';
        
        return array_unique($menus);
    }

    // ========================================
    // AUTO-GENERATION & HOOKS
    // ========================================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            // Auto-generate SKU if not provided
            if (empty($product->sku)) {
                $product->sku = static::generateSKU($product);
            }

            // Auto-generate slug if not provided
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }

            // Set default published_at if not provided
            if (empty($product->published_at)) {
                $product->published_at = now();
            }
        });

        static::updating(function ($product) {
            // Update slug if name changed
            if ($product->isDirty('name') && empty($product->getOriginal('slug'))) {
                $product->slug = Str::slug($product->name);
            }
        });
    }

    /**
     * Generate unique SKU
     */
    public static function generateSKU($product)
    {
        // Format: BRAND-CATEGORY-RANDOM (e.g., NIKE-RUN-1234)
        $brand = $product->brand ? strtoupper(substr($product->brand, 0, 4)) : 'PROD';
        
        $category = 'GEN';
        if ($product->category_id) {
            $categoryModel = Category::find($product->category_id);
            if ($categoryModel) {
                $category = strtoupper(substr($categoryModel->name, 0, 3));
            }
        }

        $random = strtoupper(Str::random(4));
        $sku = "{$brand}-{$category}-{$random}";

        // Ensure uniqueness
        $counter = 1;
        $originalSku = $sku;
        while (static::where('sku', $sku)->exists()) {
            $sku = $originalSku . '-' . str_pad($counter, 2, '0', STR_PAD_LEFT);
            $counter++;
        }

        return $sku;
    }

    /**
     * Reduce stock quantity
     */
    public function reduceStock(int $quantity): bool
    {
        if ($this->stock_quantity >= $quantity) {
            $this->decrement('stock_quantity', $quantity);
            return true;
        }
        
        return false;
    }

    /**
     * Increase stock quantity
     */
    public function increaseStock(int $quantity): void
    {
        $this->increment('stock_quantity', $quantity);
    }

    /**
     * Check if product can be purchased
     */
    public function canBePurchased(int $quantity = 1): bool
    {
        return $this->is_active && 
               $this->published_at <= now() && 
               $this->stock_quantity >= $quantity;
    }

    /**
     * Get similar products (same category)
     */
    public function getSimilarProducts(int $limit = 4)
    {
        return static::where('category_id', $this->category_id)
                    ->where('id', '!=', $this->id)
                    ->available()
                    ->limit($limit)
                    ->get();
    }
}