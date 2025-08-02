<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

/**
 * App\Models\User
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Wishlist> $wishlists
 * @property-read int|null $wishlists_count
 * @method \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Wishlist> wishlists()
 * @method int getWishlistCount()
 * @method bool hasInWishlist(int $productId)
 * @method bool toggleWishlist(int $productId)
 * @method bool addToWishlist(int $productId)
 * @method int removeFromWishlist(int $productId)
 */
class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'avatar',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Determine if the user can access the Filament admin panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->email, [
            'admin@sneakerflash.com',
            'admin@sneaker.com',
        ]);
    }

    // PostgreSQL specific scopes
    public function scopeGoogleUsers($query)
    {
        return $query->whereNotNull('google_id');
    }

    public function scopeRegularUsers($query)
    {
        return $query->whereNull('google_id');
    }

    public function scopeVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    // =====================================
    // E-COMMERCE RELATIONSHIPS
    // =====================================

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function cartItems()
    {
        return $this->hasMany(ShoppingCart::class);
    }

    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    public function couponUsage()
    {
        return $this->hasMany(CouponUsage::class);
    }

    // =====================================
    // WISHLIST RELATIONSHIPS & METHODS
    // =====================================

    /**
     * Get all of the wishlists for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Wishlist>
     */
    public function wishlists()
    {
        return $this->hasMany(\App\Models\Wishlist::class);
    }

    /**
     * Many-to-many relationship with products through wishlists
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\Product>
     */
    public function wishlistProducts()
    {
        return $this->belongsToMany(\App\Models\Product::class, 'wishlists')
                    ->withTimestamps()
                    ->orderBy('wishlists.created_at', 'desc');
    }

    /**
     * Get wishlist count for header badge
     *
     * @return int
     */
    public function getWishlistCount(): int
    {
        return $this->wishlists()->count();
    }

    /**
     * Check if product is in user's wishlist
     *
     * @param int $productId
     * @return bool
     */
    public function hasInWishlist(int $productId): bool
    {
        return $this->wishlists()->where('product_id', $productId)->exists();
    }

    /**
     * Add product to wishlist
     *
     * @param int $productId
     * @return \App\Models\Wishlist|false
     */
    public function addToWishlist(int $productId)
    {
        if (!$this->hasInWishlist($productId)) {
            return $this->wishlists()->create(['product_id' => $productId]);
        }
        return false;
    }

    /**
     * Remove product from wishlist
     *
     * @param int $productId
     * @return int
     */
    public function removeFromWishlist(int $productId): int
    {
        return $this->wishlists()->where('product_id', $productId)->delete();
    }

    /**
     * Toggle product in wishlist (add if not exists, remove if exists)
     *
     * @param int $productId
     * @return bool True if added, false if removed
     */
    public function toggleWishlist(int $productId): bool
    {
        if ($this->hasInWishlist($productId)) {
            $this->removeFromWishlist($productId);
            return false; // Removed
        } else {
            $this->addToWishlist($productId);
            return true; // Added
        }
    }

    /**
     * Get all wishlist product IDs for this user
     *
     * @return array<int>
     */
    public function getWishlistProductIds(): array
    {
        return $this->wishlists()->pluck('product_id')->toArray();
    }

    // =====================================
    // ACCESSORS
    // =====================================

    public function getAvatarUrlAttribute()
    {
        if ($this->avatar) {
            return $this->avatar;
        }
        $hash = md5(strtolower(trim($this->email)));
        return "https://www.gravatar.com/avatar/{$hash}?d=identicon&s=150";
    }

    public function getIsGoogleUserAttribute()
    {
        return !is_null($this->google_id);
    }

    // =====================================
    // CART HELPER METHODS
    // =====================================

    /**
     * Get cart items count
     */
    public function getCartCount()
    {
        return $this->cartItems()
            ->whereHas('product', function ($query) {
                $query->where('is_active', true)
                      ->where('stock_quantity', '>', 0);
            })
            ->sum('quantity');
    }

    /**
     * Get cart total amount
     */
    public function getCartTotal()
    {
        return $this->cartItems()
            ->whereHas('product', function ($query) {
                $query->where('is_active', true)
                      ->where('stock_quantity', '>', 0);
            })
            ->get()
            ->sum(function ($item) {
                $price = $item->product->sale_price ?? $item->product->price;
                return $price * $item->quantity;
            });
    }

    // =====================================
    // ORDER HELPER METHODS
    // =====================================

    /**
     * Get total amount spent by user
     */
    public function getTotalSpent()
    {
        return $this->orders()->where('payment_status', 'paid')->sum('total_amount');
    }

    /**
     * Get total orders count
     */
    public function getOrdersCount()
    {
        return $this->orders()->count();
    }

    /**
     * Get completed orders count
     */
    public function getCompletedOrdersCount()
    {
        return $this->orders()
                   ->where('payment_status', 'paid')
                   ->where('order_status', 'completed')
                   ->count();
    }

    /**
     * Get pending orders count
     */
    public function getPendingOrdersCount()
    {
        return $this->orders()
                   ->whereIn('order_status', ['pending', 'processing'])
                   ->count();
    }

    // =====================================
    // USER ACTIVITY METHODS
    // =====================================

    /**
     * Check if user is active (has orders or recent activity)
     */
    public function isActiveUser()
    {
        return $this->orders()->exists() || 
               $this->wishlists()->exists() || 
               $this->cartItems()->exists();
    }

    /**
     * Get user's favorite brands based on orders
     */
    public function getFavoriteBrands($limit = 5)
    {
        return $this->orders()
                   ->with('orderItems.product')
                   ->where('payment_status', 'paid')
                   ->get()
                   ->flatMap(function ($order) {
                       return $order->orderItems->pluck('product.brand');
                   })
                   ->filter()
                   ->countBy()
                   ->sortDesc()
                   ->take($limit)
                   ->keys()
                   ->toArray();
    }
}