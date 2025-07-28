<?php
// File: app/Models/Product.php - UPDATED VERSION with Image Handling

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
            'images' => 'array',
            'features' => 'array',
            'specifications' => 'array',
            'dimensions' => 'array',
            'meta_data' => 'array',
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

    // ========================================
    // SCOPES
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
     * Scope untuk produk yang sudah publish
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('published_at', '<=', now());
    }

    /**
     * Scope untuk produk yang tersedia (aktif, publish, ada stock)
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->active()
                    ->published()
                    ->where('stock_quantity', '>', 0);
    }

    /**
     * Scope untuk filter berdasarkan kategori
     */
    public function scopeInCategory(Builder $query, $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope untuk filter berdasarkan brand
     */
    public function scopeByBrand(Builder $query, string $brand): Builder
    {
        return $query->where('brand', $brand);
    }

    /**
     * Scope untuk search produk
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'ILIKE', "%{$search}%")
              ->orWhere('short_description', 'ILIKE', "%{$search}%")
              ->orWhere('brand', 'ILIKE', "%{$search}%")
              ->orWhere('sku', 'ILIKE', "%{$search}%");
        });
    }

    /**
     * Scope untuk filter berdasarkan rentang harga
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

    // ========================================
    // HELPER METHODS
    // ========================================

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