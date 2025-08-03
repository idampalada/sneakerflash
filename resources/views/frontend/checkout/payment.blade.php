{{-- File: resources/views/frontend/checkout/payment.blade.php --}}
@extends('layouts.app')

@section('title', 'Payment - SneakerFlash')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-4">Complete Your Payment</h1>
            <p class="text-gray-600">Order #{{ $order->order_number }}</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Order Summary -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Order Summary</h2>
                
                <!-- Order Items -->
                <div class="space-y-3 mb-4">
                    @foreach($order->orderItems as $item)
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <div>
                                <p class="font-medium">{{ $item->product_name }}</p>
                                <p class="text-sm text-gray-600">Qty: {{ $item->quantity }}</p>
                            </div>
                            <p class="font-semibold">Rp {{ number_format($item->total_price, 0, ',', '.') }}</p>
                        </div>
                    @endforeach
                </div>

                <!-- Order Totals -->
                <div class="border-t pt-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Subtotal:</span>
                        <span class="font-semibold">Rp {{ number_format($order->subtotal, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Shipping:</span>
                        <span class="font-semibold">Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Tax (11%):</span>
                        <span class="font-semibold">Rp {{ number_format($order->tax_amount, 0, ',', '.') }}</span>
                    </div>
                    <div class="border-t pt-2">
                        <div class="flex justify-between">
                            <span class="text-gray-900 font-semibold">Total:</span>
                            <span class="text-2xl font-bold text-blue-600">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Shipping Info -->
                <div class="mt-6 pt-4 border-t">
                    <h3 class="font-semibold text-gray-900 mb-2">Shipping Information</h3>
                    <p class="text-sm text-gray-600">{{ $order->customer_name }}</p>
                    <p class="text-sm text-gray-600">{{ $order->shipping_address }}</p>
                    <p class="text-sm text-gray-600">{{ $order->shipping_destination_label }}</p>
                    <p class="text-sm text-gray-600">{{ $order->shipping_postal_code }}</p>
                    <p class="text-sm text-gray-600 mt-2">
                        <strong>Method:</strong> {{ $order->shipping_method }}
                    </p>
                </div>
            </div>

            <!-- Payment Section -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Payment</h2>
                
                <!-- Payment Status -->
                <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <div class="flex items-center">
                        <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-yellow-600 mr-3"></div>
                        <p class="text-yellow-800">
                            <strong>Status:</strong> 
                            @if($order->payment_status === 'pending')
                                Waiting for payment
                            @elseif($order->payment_status === 'processing')
                                Processing payment
                            @else
                                {{ ucfirst($order->payment_status) }}
                            @endif
                        </p>
                    </div>
                </div>

                <!-- Midtrans Snap Payment -->
                <div id="payment-container">
                    <div class="text-center">
                        <button id="pay-button" 
                                class="w-full bg-blue-600 text-white py-3 px-6 rounded-lg hover:bg-blue-700 transition-colors font-medium text-lg">
                            Pay Now - Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                        </button>
                        
                        <p class="text-sm text-gray-500 mt-3">
                            🔒 Secure payment powered by Midtrans
                        </p>
                        
                        <div class="mt-4 flex justify-center space-x-4 text-xs text-gray-400">
                            <span>💳 Credit Card</span>
                            <span>🏦 Bank Transfer</span>
                            <span>📱 E-Wallet</span>
                        </div>
                    </div>
                </div>

                <!-- Alternative Actions -->
                <div class="mt-6 pt-4 border-t text-center">
                    <a href="{{ route('orders.show', $order->order_number) }}" 
                       class="text-blue-600 hover:underline text-sm">
                        View Order Details
                    </a>
                    <span class="mx-2 text-gray-300">|</span>
                    <a href="{{ route('home') }}" 
                       class="text-blue-600 hover:underline text-sm">
                        Continue Shopping
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Midtrans Snap JS -->
<script type="text/javascript" 
        src="{{ config('services.midtrans.is_production') ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}" 
        data-client-key="{{ config('services.midtrans.client_key') }}">
</script>

<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
    const payButton = document.getElementById('pay-button');
    const snapToken = '{{ $snapToken }}';
    
    if (!snapToken) {
        payButton.disabled = true;
        payButton.textContent = 'Payment session expired';
        payButton.classList.add('bg-gray-400', 'cursor-not-allowed');
        return;
    }
    
    payButton.addEventListener('click', function() {
        // Disable button to prevent double click
        payButton.disabled = true;
        payButton.innerHTML = '<div class="animate-spin rounded-full h-5 w-5 border-b-2 border-white mx-auto"></div>';
        
        snap.pay(snapToken, {
            onSuccess: function(result) {
                console.log('Payment success:', result);
                window.location.href = '{{ route("checkout.payment.success") }}?order_id={{ $order->order_number }}';
            },
            onPending: function(result) {
                console.log('Payment pending:', result);
                alert('Payment is being processed. You will receive confirmation shortly.');
                window.location.href = '{{ route("checkout.success", $order->order_number) }}';
            },
            onError: function(result) {
                console.log('Payment error:', result);
                alert('Payment failed. Please try again.');
                // Re-enable button
                payButton.disabled = false;
                payButton.textContent = 'Pay Now - Rp {{ number_format($order->total_amount, 0, ",", ".") }}';
            },
            onClose: function() {
                console.log('Payment popup closed');
                // Re-enable button
                payButton.disabled = false;
                payButton.textContent = 'Pay Now - Rp {{ number_format($order->total_amount, 0, ",", ".") }}';
            }
        });
    });
});
</script>

@endsection