{{-- File: resources/views/frontend/checkout/success.blade.php - NO TAX VERSION --}}
@extends('layouts.app')

@section('title', 'Order Confirmation - SneakerFlash')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Success Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Order Confirmed!</h1>
            <p class="text-gray-600">Thank you for your order. We've received your order and will process it shortly.</p>
        </div>

        <!-- Order Summary Card -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-6">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900 mb-2">
                        Order #{{ $order->order_number }}
                    </h2>
                    
                    <div class="flex flex-wrap gap-2 mb-3">
                        <!-- UPDATED: Single Status Display -->
                        @if($order->status === 'pending')
                            <span class="px-3 py-1 text-sm font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                ⏳ Pending
                            </span>
                        @elseif($order->status === 'paid')
                            <span class="px-3 py-1 text-sm font-semibold rounded-full bg-green-100 text-green-800">
                                ✅ Paid
                            </span>
                        @elseif($order->status === 'processing')
                            <span class="px-3 py-1 text-sm font-semibold rounded-full bg-blue-100 text-blue-800">
                                🔄 Processing
                            </span>
                        @elseif($order->status === 'shipped')
                            <span class="px-3 py-1 text-sm font-semibold rounded-full bg-purple-100 text-purple-800">
                                🚚 Shipped
                            </span>
                        @elseif($order->status === 'delivered')
                            <span class="px-3 py-1 text-sm font-semibold rounded-full bg-green-100 text-green-800">
                                📦 Delivered
                            </span>
                        @elseif($order->status === 'cancelled')
                            <span class="px-3 py-1 text-sm font-semibold rounded-full bg-red-100 text-red-800">
                                ❌ Cancelled
                            </span>
                        @elseif($order->status === 'refund')
                            <span class="px-3 py-1 text-sm font-semibold rounded-full bg-gray-100 text-gray-800">
                                💰 Refunded
                            </span>
                        @else
                            <span class="px-3 py-1 text-sm font-semibold rounded-full bg-gray-100 text-gray-800">
                                {{ ucfirst($order->status) }}
                            </span>
                        @endif
                    </div>
                    
                    <div class="text-sm text-gray-600 space-y-1">
                        <p><strong>Order Date:</strong> {{ $order->created_at->format('F j, Y \a\t g:i A') }}</p>
                        <p><strong>Payment Method:</strong> {{ strtoupper(str_replace('_', ' ', $order->payment_method)) }}</p>
                        @if($order->tracking_number)
                            <p><strong>Tracking Number:</strong> {{ $order->tracking_number }}</p>
                        @endif
                    </div>
                </div>
                
                <div class="mt-4 lg:mt-0 text-right">
                    <div class="text-3xl font-bold text-gray-900">
                        Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                    </div>
                    <div class="text-sm text-gray-600">
                        {{ $order->orderItems->count() }} item(s)
                    </div>
                </div>
            </div>

            <!-- UPDATED: Payment Instructions Based on Single Status -->
            @if($order->status === 'pending' && $order->payment_method !== 'cod')
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800">Payment Required</h3>
                            <p class="text-sm text-yellow-700 mt-1">
                                Complete your payment to process this order. You can retry payment anytime from your order history.
                            </p>
                        </div>
                    </div>
                </div>
            @elseif($order->payment_method === 'cod')
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">Cash on Delivery</h3>
                            <p class="text-sm text-blue-700 mt-1">
                                Payment will be collected when your order is delivered. Please prepare exact amount if possible.
                            </p>
                        </div>
                    </div>
                </div>
            @elseif($order->status === 'paid')
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-green-800">Payment Confirmed</h3>
                            <p class="text-sm text-green-700 mt-1">
                                Your payment has been successfully processed. We will start preparing your order for shipment.
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Customer Information -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Customer Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Contact Details -->
                <div>
                    <h4 class="font-medium text-gray-900 mb-2">Contact Details</h4>
                    <div class="text-sm text-gray-600 space-y-1">
                        <p><strong>Name:</strong> {{ $order->customer_name }}</p>
                        <p><strong>Email:</strong> {{ $order->customer_email }}</p>
                        <p><strong>Phone:</strong> {{ $order->customer_phone }}</p>
                    </div>
                </div>
                
                <!-- Shipping Address -->
                <div>
                    <h4 class="font-medium text-gray-900 mb-2">Shipping Address</h4>
                    <div class="text-sm text-gray-600">
                        <p>{{ $order->getFullShippingAddress() }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Order Items</h3>
            <div class="space-y-4">
                @foreach($order->orderItems as $item)
                    <div class="flex items-center space-x-4 p-4 border border-gray-200 rounded-lg">
                        <div class="flex-shrink-0">
                            @if($item->product && $item->product->featured_image)
                                <img src="{{ $item->product->featured_image }}" 
                                     alt="{{ $item->product_name }}" 
                                     class="h-20 w-20 object-cover rounded-lg">
                            @else
                                <div class="h-20 w-20 bg-gray-200 rounded-lg flex items-center justify-center">
                                    <svg class="h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                            @endif
                        </div>
                        <div class="flex-1">
                            <h4 class="font-medium text-gray-900">{{ $item->product_name }}</h4>
                            <div class="text-sm text-gray-600 mt-1">
                                <p>SKU: {{ $item->product_sku ?: 'N/A' }}</p>
                                <p>Unit Price: Rp {{ number_format($item->product_price, 0, ',', '.') }}</p>
                                <p>Quantity: {{ $item->quantity }}</p>
                            </div>
                            @if($item->product)
                                <a href="{{ route('products.show', $item->product->slug) }}" 
                                   class="text-blue-600 hover:text-blue-800 text-sm">
                                    View Product →
                                </a>
                            @endif
                        </div>
                        <div class="text-right">
                            <div class="font-semibold text-lg text-gray-900">
                                Rp {{ number_format($item->total_price, 0, ',', '.') }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Order Summary - REMOVED TAX -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Order Summary</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">Subtotal</span>
                    <span class="font-medium">Rp {{ number_format($order->subtotal, 0, ',', '.') }}</span>
                </div>
                
                @if($order->shipping_cost > 0)
                    <div class="flex justify-between">
                        <span class="text-gray-600">Shipping Cost</span>
                        <span class="font-medium">Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</span>
                    </div>
                @endif
                
                <!-- REMOVED TAX DISPLAY -->
                
                @if($order->discount_amount > 0)
                    <div class="flex justify-between text-green-600">
                        <span>Discount</span>
                        <span class="font-medium">-Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</span>
                    </div>
                @endif
                
                <hr class="my-3">
                
                <div class="flex justify-between text-lg font-bold">
                    <span>Total</span>
                    <span>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <!-- UPDATED: Next Steps Based on Single Status -->
        <div class="bg-gray-50 rounded-lg p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">What's Next?</h3>
            <div class="space-y-3 text-sm text-gray-600">
                @if($order->status === 'paid')
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>✅ Your payment has been confirmed</span>
                    </div>
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-blue-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>🔄 We're preparing your order for shipment</span>
                    </div>
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        <span>📧 You'll receive shipping notification via email</span>
                    </div>
                @elseif($order->payment_method === 'cod')
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>✅ Your COD order has been confirmed</span>
                    </div>
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-blue-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>🔄 We're preparing your order for shipment</span>
                    </div>
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-orange-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                        <span>💰 Payment will be collected upon delivery</span>
                    </div>
                @elseif($order->status === 'processing')
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-blue-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>🔄 Your order is being processed</span>
                    </div>
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        <span>📧 You'll receive shipping notification soon</span>
                    </div>
                @elseif($order->status === 'shipped')
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>🚚 Your order has been shipped</span>
                    </div>
                    @if($order->tracking_number)
                        <div class="flex items-center">
                            <svg class="h-5 w-5 text-blue-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span>📋 Tracking: {{ $order->tracking_number }}</span>
                        </div>
                    @endif
                @elseif($order->status === 'delivered')
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>📦 Your order has been delivered</span>
                    </div>
                @else
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-yellow-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                        <span>⏳ Waiting for payment confirmation</span>
                    </div>
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>🔄 Order will be processed after payment</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-wrap gap-3 justify-center">
            <a href="{{ route('home') }}" 
               class="px-6 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                Continue Shopping
            </a>
            
            @auth
                <a href="{{ route('orders.index') }}" 
                   class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    View All Orders
                </a>
                
                <a href="{{ route('orders.show', $order->order_number) }}" 
                   class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    View Order Details
                </a>
            @endauth
            
            <!-- UPDATED: Show invoice button for paid and beyond -->
            @if(in_array($order->status, ['paid', 'processing', 'shipped', 'delivered']))
                <a href="{{ route('orders.invoice', $order->order_number) }}" 
                   class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                    📄 Download Invoice
                </a>
            @endif
        </div>

        <!-- Email Confirmation Notice -->
        <div class="text-center mt-8 p-4 bg-blue-50 rounded-lg">
            <div class="flex items-center justify-center mb-2">
                <svg class="h-5 w-5 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
                <span class="text-sm font-medium text-blue-800">Order Confirmation Email</span>
            </div>
            <p class="text-sm text-blue-700">
                A confirmation email has been sent to <strong>{{ $order->customer_email }}</strong>
            </p>
            <p class="text-xs text-blue-600 mt-1">
                Please check your spam/junk folder if you don't see it in your inbox
            </p>
        </div>

        <!-- Order Status Summary -->
        <div class="text-center mt-6 p-4 bg-gray-50 rounded-lg">
            <h4 class="text-sm font-medium text-gray-800 mb-2">Order Status Summary</h4>
            <div class="text-sm text-gray-600">
                <p><strong>Current Status:</strong> {{ $order->getPaymentStatusText() }}</p>
                @if($order->status === 'pending' && $order->payment_method !== 'cod')
                    <p class="mt-1 text-yellow-600">
                        <strong>Action Required:</strong> Complete payment to process your order
                    </p>
                @elseif($order->status === 'paid')
                    <p class="mt-1 text-green-600">
                        <strong>Next Step:</strong> We will start processing your order within 24 hours
                    </p>
                @elseif($order->status === 'processing')
                    <p class="mt-1 text-blue-600">
                        <strong>Next Step:</strong> Your order will be shipped within 1-3 business days
                    </p>
                @elseif($order->status === 'shipped')
                    <p class="mt-1 text-purple-600">
                        <strong>Next Step:</strong> Your order is on its way and will be delivered soon
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection