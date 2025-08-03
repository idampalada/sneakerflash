<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'user_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'status',
        'order_status',
        'subtotal',
        'tax_amount',
        'shipping_cost', // Updated from shipping_amount to match migration
        'discount_amount',
        'total_amount',
        'currency',
        'shipping_address',
        'shipping_destination_id', // NEW: For RajaOngkir V2
        'shipping_destination_label', // NEW: For RajaOngkir V2
        'shipping_postal_code', // NEW: For RajaOngkir V2
        'shipping_method', // NEW: For shipping method
        'billing_address',
        'store_origin',
        'payment_method',
        'payment_status',
        'payment_token',
        'payment_url',
        'snap_token', // NEW: For Midtrans Snap
        'payment_response', // NEW: For Midtrans webhook data
        'tracking_number',
        'shipped_at',
        'delivered_at',
        'notes',
        'meta_data',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2', // Updated from shipping_amount
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'shipping_address' => 'array',
        'billing_address' => 'array',
        'store_origin' => 'array',
        'payment_response' => 'array', // NEW: For Midtrans data
        'meta_data' => 'array',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Define route key for URL routing
     */
    public function getRouteKeyName()
    {
        return 'order_number';
    }

    // =====================================
    // RELATIONSHIPS
    // =====================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // =====================================
    // HELPER METHODS
    // =====================================

    public function calculateTotals()
    {
        $this->subtotal = $this->orderItems->sum('total_price');
        $this->total_amount = $this->subtotal + $this->tax_amount + $this->shipping_cost - $this->discount_amount;
        $this->save();
    }

    // =====================================
    // FORMATTED ATTRIBUTES
    // =====================================

    public function getFormattedTotalAttribute()
    {
        return 'Rp ' . number_format($this->total_amount, 0, ',', '.');
    }

    public function getFormattedSubtotalAttribute()
    {
        return 'Rp ' . number_format($this->subtotal, 0, ',', '.');
    }

    public function getFormattedShippingAttribute()
    {
        return 'Rp ' . number_format($this->shipping_cost, 0, ',', '.');
    }

    public function getFormattedTaxAttribute()
    {
        return 'Rp ' . number_format($this->tax_amount, 0, ',', '.');
    }

    public function getFormattedDiscountAttribute()
    {
        return 'Rp ' . number_format($this->discount_amount, 0, ',', '.');
    }

    // =====================================
    // CUSTOMER ATTRIBUTES WITH FALLBACKS
    // =====================================

    public function getCustomerNameAttribute($value)
    {
        // Fallback to user name if customer_name is null
        return $value ?: ($this->user ? $this->user->name : 'Guest Customer');
    }

    public function getCustomerEmailAttribute($value)
    {
        // Fallback to user email if customer_email is null
        return $value ?: ($this->user ? $this->user->email : null);
    }

    // =====================================
    // STATUS METHODS
    // =====================================

    public function canBeCancelled()
    {
        return in_array($this->payment_status, ['pending', 'failed']) &&
               in_array($this->order_status, ['pending', 'confirmed']);
    }

    public function isCompleted()
    {
        return $this->payment_status === 'paid' && 
               in_array($this->order_status, ['shipped', 'delivered']);
    }

    public function isPaid()
    {
        return $this->payment_status === 'paid';
    }

    public function isPending()
    {
        return $this->payment_status === 'pending';
    }

    // =====================================
    // STATUS COLORS FOR UI
    // =====================================

    public function getPaymentStatusColorAttribute()
    {
        return match($this->payment_status) {
            'pending' => 'yellow',
            'paid' => 'green',
            'failed' => 'red',
            'cancelled' => 'gray',
            'processing' => 'blue',
            'challenge' => 'orange',
            default => 'gray'
        };
    }

    public function getOrderStatusColorAttribute()
    {
        return match($this->order_status) {
            'pending' => 'yellow',
            'confirmed' => 'blue',
            'processing' => 'indigo',
            'shipped' => 'purple',
            'delivered' => 'green',
            'cancelled' => 'red',
            default => 'gray'
        };
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'pending' => 'yellow',
            'confirmed' => 'blue',
            'processing' => 'indigo',
            'shipped' => 'purple',
            'delivered' => 'green',
            'cancelled' => 'red',
            'completed' => 'green',
            default => 'gray'
        };
    }

    // =====================================
    // SCOPES
    // =====================================

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeByPaymentStatus($query, $status)
    {
        return $query->where('payment_status', $status);
    }

    public function scopeByOrderStatus($query, $status)
    {
        return $query->where('order_status', $status);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // PostgreSQL: Search orders by number or customer
    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('order_number', 'ILIKE', "%{$search}%")
              ->orWhere('customer_name', 'ILIKE', "%{$search}%")
              ->orWhere('customer_email', 'ILIKE', "%{$search}%");
        });
    }

    // PostgreSQL: Filter by date range
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    // PostgreSQL: Get orders with total amount in range
    public function scopeAmountRange($query, $minAmount, $maxAmount)
    {
        return $query->whereBetween('total_amount', [$minAmount, $maxAmount]);
    }

    // =====================================
    // MIDTRANS SPECIFIC METHODS
    // =====================================

    public function hasSnapToken()
    {
        return !empty($this->snap_token);
    }

    public function getMidtransStatus()
    {
        if (empty($this->payment_response)) {
            return null;
        }

        $response = is_array($this->payment_response) ? $this->payment_response : json_decode($this->payment_response, true);
        return $response['transaction_status'] ?? null;
    }

    public function getMidtransFraudStatus()
    {
        if (empty($this->payment_response)) {
            return null;
        }

        $response = is_array($this->payment_response) ? $this->payment_response : json_decode($this->payment_response, true);
        return $response['fraud_status'] ?? null;
    }

    // =====================================
    // SHIPPING INFORMATION METHODS
    // =====================================

    public function getShippingAddressFormatted()
    {
        if (is_array($this->shipping_address)) {
            return implode(', ', array_filter($this->shipping_address));
        }
        
        return $this->shipping_address ?: 'No shipping address';
    }

    public function getFullShippingAddress()
    {
        $parts = array_filter([
            $this->shipping_address,
            $this->shipping_destination_label,
            $this->shipping_postal_code
        ]);
        
        return implode(', ', $parts) ?: 'No shipping address';
    }

    // =====================================
    // STATIC HELPER METHODS
    // =====================================

    public static function getPaymentStatuses()
    {
        return [
            'pending' => 'Pending',
            'paid' => 'Paid',
            'failed' => 'Failed',
            'cancelled' => 'Cancelled',
            'processing' => 'Processing',
            'challenge' => 'Challenge'
        ];
    }

    public static function getOrderStatuses()
    {
        return [
            'pending' => 'Pending',
            'confirmed' => 'Confirmed',
            'processing' => 'Processing',
            'shipped' => 'Shipped',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled'
        ];
    }

    public static function getStatuses()
    {
        return [
            'pending' => 'Pending',
            'confirmed' => 'Confirmed',
            'processing' => 'Processing',
            'shipped' => 'Shipped',
            'delivered' => 'Delivered',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled'
        ];
    }
}