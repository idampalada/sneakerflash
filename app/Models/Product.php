<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
        'gender_target',    // JSON array
        'product_type',
        'search_keywords',  // JSON array
        'sale_start_date',
        'sale_end_date',
        'is_featured_sale',
        'available_sizes',  // JSON array
        'available_colors', // JSON array
        'meta_title',
        'meta_description', 
        'meta_keywords',    // JSON array
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_featured_sale' => 'boolean',
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'weight' => 'decimal:2',
        'published_at' => 'datetime',
        'sale_start_date' => 'date',
        'sale_end_date' => 'date',
        
        // JSON fields
        'images' => 'array',
        'features' => 'array',
        'specifications' => 'array',
        'dimensions' => 'array',
        'meta_data' => 'array',
        'gender_target' => 'array',    // Cast as array
        'search_keywords' => 'array',
        'available_sizes' => 'array',
        'available_colors' => 'array',
        'meta_keywords' => 'array',
    ];

    protected $attributes = [
        'is_active' => true,
        'is_featured' => false,
        'is_featured_sale' => false,
        'stock_quantity' => 0,
        'min_stock_level' => 5,
    ];

    // Auto generate slug
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = static::generateUniqueSlug($product->name);
            }
        });

        static::updating(function ($product) {
            if ($product->isDirty('name') && empty($product->getOriginal('slug'))) {
                $product->slug = static::generateUniqueSlug($product->name);
            }
        });
    }

    public static function generateUniqueSlug($name)
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    // Relationships
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeOnSale($query)
    {
        return $query->whereNotNull('sale_price')->whereRaw('sale_price < price');
    }

    public function scopeInStock($query)
    {
        return $query->where('stock_quantity', '>', 0);
    }

    public function scopeForGender($query, string $gender)
    {
        return $query->whereJsonContains('gender_target', $gender);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('product_type', $type);
    }

    // Accessors
    public function getFeaturedImageAttribute()
    {
        if ($this->images && is_array($this->images) && count($this->images) > 0) {
            // Cek apakah path sudah full URL atau hanya filename
            $imagePath = $this->images[0];
            if (str_starts_with($imagePath, 'http')) {
                return $imagePath; // Sudah full URL
            }
            return Storage::url($imagePath);
        }
        return asset('images/default-product.png'); // Default image
    }

    public function getDiscountPercentageAttribute()
    {
        if ($this->sale_price && $this->price > 0) {
            return round((($this->price - $this->sale_price) / $this->price) * 100);
        }
        return 0;
    }

    public function getIsOnSaleAttribute()
    {
        return $this->sale_price && $this->sale_price < $this->price;
    }

    public function getFormattedPriceAttribute()
    {
        return 'Rp ' . number_format($this->price, 0, ',', '.');
    }

    public function getFormattedSalePriceAttribute()
    {
        if ($this->sale_price) {
            return 'Rp ' . number_format($this->sale_price, 0, ',', '.');
        }
        return null;
    }

    // Gender helper methods
    public function isForGender(string $gender): bool
    {
        return $this->gender_target && in_array($gender, $this->gender_target);
    }

    public function getGenderLabelsAttribute(): array
    {
        if (!$this->gender_target) return [];
        
        $labels = [];
        foreach ($this->gender_target as $gender) {
            $labels[] = match($gender) {
                'mens' => "Men's",
                'womens' => "Women's",
                'kids' => 'Kids',
                default => $gender
            };
        }
        return $labels;
    }

    public function getGenderBadgesAttribute(): string
    {
        if (!$this->gender_target) return '';
        
        $badges = [];
        foreach ($this->gender_target as $gender) {
            $badges[] = match($gender) {
                'mens' => '👨 Men\'s',
                'womens' => '👩 Women\'s',
                'kids' => '👶 Kids',
                default => $gender
            };
        }
        return implode(', ', $badges);
    }

    // Static methods for filtering
    public static function getForMenu(string $menuType)
    {
        return static::active()
                    ->inStock()
                    ->forGender($menuType)
                    ->with('category')
                    ->orderBy('is_featured', 'desc')
                    ->orderBy('created_at', 'desc');
    }

    public static function getFeaturedProducts()
    {
        return static::active()
                    ->featured()
                    ->inStock()
                    ->with('category')
                    ->limit(8)
                    ->get();
    }

    public static function getLatestProducts()
    {
        return static::active()
                    ->inStock()
                    ->with('category')
                    ->latest('created_at')
                    ->limit(12)
                    ->get();
    }

    public static function getSaleProducts()
    {
        return static::active()
                    ->onSale()
                    ->inStock()
                    ->with('category')
                    ->get();
    }
}