<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Support\Facades\Log;

/**
 * App\Models\User
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Wishlist> $wishlists
 * @property-read int|null $wishlists_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\UserAddress> $addresses
 * @property-read int|null $addresses_count
 * @method \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Wishlist> wishlists()
 * @method \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\UserAddress> addresses()
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
        'phone',
        'gender',
        'birthdate',
        'google_id',
        'avatar',
        'email_verified_at',
        'total_spent',           // ADDED - Stored spending
        'total_orders',          // ADDED - Stored order count
        'spending_updated_at',   // ADDED - Last sync timestamp
        'customer_tier',         // ADDED - Stored customer tier
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
            'birthdate' => 'date',
            'spending_updated_at' => 'datetime',    // ADDED
            'total_spent' => 'decimal:2',           // ADDED
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

    // =====================================
    // POSTGRESQL SPECIFIC SCOPES
    // =====================================
    
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
    // SPENDING-BASED SCOPES - NEW
    // =====================================

    public function scopeHighValueCustomers($query)
    {
        return $query->where('total_spent', '>=', 5000000);
    }

    public function scopeByTier($query, $tier)
    {
        // Use stored column for super fast queries
        return $query->where('customer_tier', $tier);
    }

    public function scopeTopSpenders($query, $limit = 10)
    {
        return $query->where('total_spent', '>', 0)
                    ->orderBy('total_spent', 'desc')
                    ->limit($limit);
    }

    public function scopeFrequentBuyers($query)
    {
        return $query->where('total_orders', '>=', 5);
    }

    public function scopeSpendingRange($query, $min = null, $max = null)
    {
        if ($min !== null) {
            $query->where('total_spent', '>=', $min);
        }
        if ($max !== null) {
            $query->where('total_spent', '<=', $max);
        }
        return $query;
    }

    // =====================================
    // E-COMMERCE RELATIONSHIPS
    // =====================================

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(ShoppingCart::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function couponUsage(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    // =====================================
    // ADDRESS RELATIONSHIPS & METHODS - FIXED
    // =====================================

    /**
     * Get user addresses
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(UserAddress::class)->orderBy('is_primary', 'desc')->orderBy('created_at', 'desc');
    }

    /**
     * Get primary address
     */
    public function primaryAddress(): HasOne
    {
        return $this->hasOne(UserAddress::class)->where('is_primary', true)->where('is_active', true);
    }

    /**
     * Get active addresses only
     */
    public function activeAddresses(): HasMany
    {
        return $this->hasMany(UserAddress::class)->where('is_active', true)->orderBy('is_primary', 'desc')->orderBy('created_at', 'desc');
    }

    /**
     * Check if user has addresses
     */
    public function hasAddresses(): bool
    {
        return $this->addresses()->where('is_active', true)->exists();
    }

    /**
     * Check if user has primary address
     */
    public function hasPrimaryAddress(): bool
    {
        return $this->primaryAddress()->exists();
    }

    /**
     * Get formatted address count
     */
    public function getAddressCountAttribute(): int
    {
        return $this->addresses()->where('is_active', true)->count();
    }

    /**
     * Get primary address or first available address
     */
    public function getDefaultAddressAttribute()
    {
        return $this->primaryAddress ?: $this->addresses()->where('is_active', true)->first();
    }

    // =====================================
    // WISHLIST RELATIONSHIPS & METHODS
    // =====================================

    /**
     * Get all of the wishlists for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Wishlist>
     */
    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    /**
     * Many-to-many relationship with products through wishlists
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\Product>
     */
    public function wishlistProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'wishlists')
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
    // SPENDING METHODS - HYBRID APPROACH
    // =====================================

    /**
     * Valid statuses that count as "paid/completed" orders for revenue calculation
     */
    private array $paidStatuses = ['paid', 'processing', 'shipped', 'delivered'];

    /**
     * Update spending statistics from orders table
     * This is the main method to sync stored columns with real data
     */
    public function updateSpendingStats()
    {
        // Include all statuses after 'paid' as completed revenue
        $totalSpent = $this->orders()->whereIn('status', $this->paidStatuses)->sum('total_amount');
        $totalOrders = $this->orders()->whereIn('status', $this->paidStatuses)->count();
        
        // Calculate tier based on spending
        $tier = $this->calculateTierFromSpending($totalSpent);
        
        $this->update([
            'total_spent' => $totalSpent,
            'total_orders' => $totalOrders,
            'customer_tier' => $tier,
            'spending_updated_at' => now()
        ]);
        
        Log::info('Updated spending stats for user', [
            'user_id' => $this->id,
            'total_spent' => $totalSpent,
            'total_orders' => $totalOrders,
            'customer_tier' => $tier,
            'paid_statuses' => $this->paidStatuses
        ]);
        
        return $this;
    }

    /**
     * Get total amount spent by user
     * @param bool $useStored Use stored column (fast) or calculate real-time (accurate)
     */
    public function getTotalSpent($useStored = true)
    {
        if ($useStored) {
            return $this->total_spent ?? 0;
        }
        // Fallback to real-time calculation - include all paid statuses
        return $this->orders()->whereIn('status', $this->paidStatuses)->sum('total_amount');
    }

    /**
     * Get total completed orders count
     */
    public function getTotalOrders($useStored = true)
    {
        if ($useStored) {
            return $this->total_orders ?? 0;
        }
        return $this->orders()->whereIn('status', $this->paidStatuses)->count();
    }

    /**
     * Get all orders count (including pending)
     */
    public function getOrdersCount()
    {
        return $this->orders()->count();
    }

    /**
     * Get pending orders count
     */
    public function getPendingOrdersCount()
    {
        return $this->orders()->whereIn('status', ['pending', 'processing'])->count();
    }

    /**
     * Get average order value
     */
    public function getAverageOrderValue($useStored = true)
    {
        $totalSpent = $this->getTotalSpent($useStored);
        $totalOrders = $this->getTotalOrders($useStored);
        
        return $totalOrders > 0 ? $totalSpent / $totalOrders : 0;
    }

    /**
     * Get last order date (from completed orders only)
     */
    public function getLastOrderDate()
    {
        return $this->orders()
                    ->whereIn('status', $this->paidStatuses)
                    ->latest('created_at')
                    ->value('created_at');
    }

    // =====================================
    // REAL-TIME SPENDING ANALYSIS
    // =====================================

    /**
     * Get spending this month (real-time) - include all paid statuses
     */
    public function getSpendingThisMonth()
    {
        return $this->orders()
                    ->whereIn('status', $this->paidStatuses)
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->sum('total_amount');
    }

    /**
     * Get spending this year (real-time) - include all paid statuses
     */
    public function getSpendingThisYear()
    {
        return $this->orders()
                    ->whereIn('status', $this->paidStatuses)
                    ->whereYear('created_at', now()->year)
                    ->sum('total_amount');
    }

    /**
     * Get spending in a specific period - include all paid statuses
     */
    public function getSpendingInPeriod($month = null, $year = null)
    {
        $query = $this->orders()->whereIn('status', $this->paidStatuses);
        
        if ($month) {
            $query->whereMonth('created_at', $month);
        }
        
        if ($year) {
            $query->whereYear('created_at', $year);
        }
        
        return $query->sum('total_amount');
    }

    /**
     * Get monthly spending for the current year (for charts)
     */
    public function getMonthlySpending($year = null)
    {
        $year = $year ?? now()->year;
        $monthly = [];
        
        for ($month = 1; $month <= 12; $month++) {
            $monthly[$month] = $this->getSpendingInPeriod($month, $year);
        }
        
        return $monthly;
    }

    // =====================================
    // CUSTOMER TIER SYSTEM - UPDATED WITH STORED COLUMN
    // =====================================

    /**
     * Calculate tier from spending amount (helper method)
     */
    private function calculateTierFromSpending($spending)
    {
        if ($spending >= 10000000) return 'platinum';    // 10 juta IDR
        if ($spending >= 5000000) return 'gold';         // 5 juta IDR
        if ($spending >= 1000000) return 'silver';       // 1 juta IDR
        if ($spending > 0) return 'bronze';
        return 'new';
    }

    /**
     * Get customer tier (uses stored column for speed, fallback to calculation)
     */
    public function getCustomerTier()
    {
        // Use stored column if available
        if ($this->customer_tier) {
            return $this->customer_tier;
        }
        
        // Fallback to calculation
        return $this->calculateTierFromSpending($this->total_spent ?? 0);
    }

    /**
     * Get customer tier display label
     */
    public function getCustomerTierLabel()
    {
        return match($this->getCustomerTier()) {
            'platinum' => 'Platinum Member',
            'gold' => 'Gold Member',
            'silver' => 'Silver Member',
            'bronze' => 'Bronze Member',
            'new' => 'New Customer'
        };
    }

    /**
     * Get customer tier color for UI badges
     */
    public function getCustomerTierColor()
    {
        return match($this->getCustomerTier()) {
            'platinum' => '#E5E7EB', // Platinum color
            'gold' => '#FCD34D',     // Gold color
            'silver' => '#9CA3AF',   // Silver color
            'bronze' => '#92400E',   // Bronze color
            'new' => '#6B7280'       // Default gray
        };
    }

    /**
     * Get tier requirements for next level
     */
    public function getNextTierRequirement()
    {
        $currentSpent = $this->total_spent ?? 0;
        
        return match($this->getCustomerTier()) {
            'new' => ['tier' => 'Bronze Member', 'required' => 1, 'remaining' => 1 - $currentSpent],
            'bronze' => ['tier' => 'Silver Member', 'required' => 1000000, 'remaining' => 1000000 - $currentSpent],
            'silver' => ['tier' => 'Gold Member', 'required' => 5000000, 'remaining' => 5000000 - $currentSpent],
            'gold' => ['tier' => 'Platinum Member', 'required' => 10000000, 'remaining' => 10000000 - $currentSpent],
            'platinum' => ['tier' => 'Platinum Member', 'required' => 10000000, 'remaining' => 0],
        };
    }

    // =====================================
    // PROFILE COMPLETION METHODS - ENHANCED
    // =====================================

    /**
     * Check if profile is complete (including address)
     */
    public function isProfileComplete(): bool
    {
        // Check basic profile fields
        $basicComplete = !empty($this->name) && 
                        !empty($this->email) && 
                        !empty($this->phone);
        
        // Check if has at least one address
        $hasAddress = $this->hasAddresses();
        
        return $basicComplete && $hasAddress;
    }

    /**
     * Get profile completion percentage
     */
    public function getProfileCompletionPercentage(): int
    {
        $fields = [
            'name' => !empty($this->name),
            'email' => !empty($this->email),
            'phone' => !empty($this->phone),
            'gender' => !empty($this->gender),
            'birthdate' => !empty($this->birthdate),
            'address' => $this->hasAddresses()
        ];
        
        $completedFields = array_filter($fields);
        
        return round((count($completedFields) / count($fields)) * 100);
    }

    /**
     * Get missing profile fields
     */
    public function getMissingProfileFields(): array
    {
        $missing = [];
        
        if (empty($this->name)) $missing[] = 'Name';
        if (empty($this->email)) $missing[] = 'Email';
        if (empty($this->phone)) $missing[] = 'Phone';
        if (empty($this->gender)) $missing[] = 'Gender';
        if (empty($this->birthdate)) $missing[] = 'Birth Date';
        if (!$this->hasAddresses()) $missing[] = 'Address';
        
        return $missing;
    }

    // =====================================
    // CUSTOMER CLASSIFICATION
    // =====================================

    /**
     * Check if user is a high-value customer (fast query using stored column)
     */
    public function isHighValueCustomer()
    {
        return ($this->total_spent ?? 0) >= 5000000; // 5 juta IDR
    }

    /**
     * Check if user is a frequent buyer (fast query using stored column)
     */
    public function isFrequentBuyer()
    {
        return ($this->total_orders ?? 0) >= 5;
    }

    /**
     * Check if user needs spending stats update
     */
    public function needsSpendingUpdate()
    {
        if (!$this->spending_updated_at) {
            return true;
        }
        
        // Check if there are newer orders
        $latestOrderDate = $this->orders()->latest('updated_at')->value('updated_at');
        
        return $latestOrderDate && $latestOrderDate > $this->spending_updated_at;
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
     * Get user's favorite brands based on completed orders
     */
    public function getFavoriteBrands($limit = 5)
    {
        return $this->orders()
                   ->with('orderItems.product')
                   ->whereIn('status', $this->paidStatuses)
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

    // =====================================
    // DISPLAY HELPERS
    // =====================================

    /**
     * Get formatted total spent for display
     */
    public function getFormattedTotalSpent()
    {
        return 'Rp ' . number_format($this->total_spent ?? 0, 0, ',', '.');
    }

    /**
     * Get formatted average order value for display
     */
    public function getFormattedAverageOrderValue()
    {
        return 'Rp ' . number_format($this->getAverageOrderValue(), 0, ',', '.');
    }

    /**
     * Get customer statistics summary for dashboard
     */
    public function getCustomerSummary()
    {
        return [
            'total_spent' => $this->total_spent ?? 0,
            'total_orders' => $this->total_orders ?? 0,
            'average_order_value' => $this->getAverageOrderValue(),
            'spending_this_month' => $this->getSpendingThisMonth(),
            'spending_this_year' => $this->getSpendingThisYear(),
            'customer_tier' => $this->getCustomerTier(),
            'customer_tier_label' => $this->getCustomerTierLabel(),
            'customer_tier_color' => $this->getCustomerTierColor(),
            'last_order_date' => $this->getLastOrderDate(),
            'is_high_value' => $this->isHighValueCustomer(),
            'is_frequent_buyer' => $this->isFrequentBuyer(),
            'favorite_brands' => $this->getFavoriteBrands(3),
            'next_tier' => $this->getNextTierRequirement(),
            'last_updated' => $this->spending_updated_at,
            'needs_update' => $this->needsSpendingUpdate(),
            'address_count' => $this->address_count,
            'has_primary_address' => $this->hasPrimaryAddress(),
            'profile_completion' => $this->getProfileCompletionPercentage(),
            'missing_fields' => $this->getMissingProfileFields()
        ];
    }

    // =====================================
    // ADMIN DASHBOARD HELPERS
    // =====================================

    /**
     * Get data formatted for admin dashboard
     */
    public function getAdminSummary()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'gender' => $this->gender,
            'birthdate' => $this->birthdate?->format('Y-m-d'),
            'total_spent' => $this->total_spent ?? 0,
            'total_orders' => $this->total_orders ?? 0,
            'average_order_value' => $this->getAverageOrderValue(),
            'customer_tier' => $this->getCustomerTier(),
            'customer_tier_label' => $this->getCustomerTierLabel(),
            'is_high_value' => $this->isHighValueCustomer(),
            'is_frequent_buyer' => $this->isFrequentBuyer(),
            'last_order_date' => $this->getLastOrderDate()?->format('Y-m-d H:i:s'),
            'member_since' => $this->created_at->format('Y-m-d'),
            'spending_updated_at' => $this->spending_updated_at?->format('Y-m-d H:i:s'),
            'needs_update' => $this->needsSpendingUpdate(),
            'address_count' => $this->address_count,
            'profile_completion' => $this->getProfileCompletionPercentage(),
            'is_profile_complete' => $this->isProfileComplete(),
        ];
    }
}