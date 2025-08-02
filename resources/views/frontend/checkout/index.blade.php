{{-- File: resources/views/frontend/checkout/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Checkout - SneakerFlash')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">Checkout</h1>
        
        <!-- Step Indicator -->
        <div class="flex justify-between mb-8 px-8">
            <div class="step active" id="step-1">
                <div class="step-number">1</div>
                <div class="step-title">Personal Information</div>
            </div>
            <div class="step" id="step-2">
                <div class="step-number">2</div>
                <div class="step-title">Delivery Address</div>
            </div>
            <div class="step" id="step-3">
                <div class="step-number">3</div>
                <div class="step-title">Shipping Method</div>
            </div>
            <div class="step" id="step-4">
                <div class="step-number">4</div>
                <div class="step-title">Payment</div>
            </div>
        </div>
        
        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                {{ session('error') }}
            </div>
        @endif

        <!-- Connection Status -->
        <div id="connection-status" class="mb-4 p-3 rounded-lg border hidden">
            <span id="status-text"></span>
        </div>

        <form action="{{ route('checkout.store') }}" method="POST" id="checkout-form">
            @csrf
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column: Checkout Steps -->
                <div class="lg:col-span-2">
                    
                    <!-- Step 1: Personal Information -->
                    <div class="checkout-section active" id="section-1">
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <h2 class="text-xl font-semibold text-gray-900 mb-6">Personal Information</h2>
                            
                            @if(!Auth::check())
                                <!-- Login Option -->
                                <div class="mb-6 p-4 bg-blue-50 rounded-lg">
                                    <p class="text-sm text-gray-700 mb-3">Already have an account? 
                                        <a href="{{ route('login') }}" class="text-blue-600 hover:underline font-medium">Log in instead!</a>
                                    </p>
                                    
                                    <p class="text-sm text-gray-600 mb-3">Or connect with social account:</p>
                                    <a href="{{ route('auth.google') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50">
                                        Google
                                    </a>
                                </div>
                            @endif
                            
                            <!-- Social Title -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Social title</label>
                                <div class="flex space-x-4">
                                    <label class="flex items-center">
                                        <input type="radio" name="social_title" value="Mr." class="mr-2" {{ old('social_title') == 'Mr.' ? 'checked' : '' }}>
                                        <span class="text-sm">Mr.</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="radio" name="social_title" value="Mrs." class="mr-2" {{ old('social_title') == 'Mrs.' ? 'checked' : '' }}>
                                        <span class="text-sm">Mrs.</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="radio" name="social_title" value="Ms." class="mr-2" {{ old('social_title') == 'Ms.' ? 'checked' : '' }}>
                                        <span class="text-sm">Ms.</span>
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Name Fields -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">First name *</label>
                                    <input type="text" name="first_name" id="first_name" required
                                           value="{{ old('first_name') }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">Last name *</label>
                                    <input type="text" name="last_name" id="last_name" required
                                           value="{{ old('last_name') }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>
                            
                            <!-- Email -->
                            <div class="mb-4">
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                                <input type="email" name="email" id="email" required
                                       value="{{ old('email') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                            </div>
                            
                            <!-- Phone -->
                            <div class="mb-4">
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone *</label>
                                <input type="tel" name="phone" id="phone" required
                                       value="{{ old('phone') }}"
                                       placeholder="08xxxxxxxxxx"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                            </div>
                            
                            <!-- Birthdate -->
                            <div class="mb-4">
                                <label for="birthdate" class="block text-sm font-medium text-gray-700 mb-2">
                                    <span class="text-gray-400">Optional</span> Birthdate
                                </label>
                                <input type="date" name="birthdate" id="birthdate"
                                       value="{{ old('birthdate') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                            </div>
                            
                            @if(!Auth::check())
                                <!-- Create Account Option -->
                                <div class="mb-4">
                                    <label class="flex items-center">
                                        <input type="checkbox" name="create_account" id="create_account" value="1" 
                                               onchange="togglePassword()" class="mr-3" {{ old('create_account') ? 'checked' : '' }}>
                                        <span class="text-sm font-medium">Create an account (optional)</span>
                                    </label>
                                    <p class="text-xs text-gray-500 mt-1">Save time on your next order!</p>
                                </div>
                                
                                <!-- Password Fields -->
                                <div id="password-fields" class="hidden mb-4">
                                    <div class="mb-4">
                                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                                        <input type="password" name="password" id="password" 
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                                        <p class="text-xs text-gray-500 mt-1">Enter a password between 8 and 72 characters</p>
                                    </div>
                                    <div>
                                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
                                        <input type="password" name="password_confirmation" id="password_confirmation"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                                    </div>
                                </div>
                            @endif
                            
                            <!-- Newsletter -->
                            <div class="mb-4">
                                <label class="flex items-start">
                                    <input type="checkbox" name="newsletter_subscribe" value="1" class="mr-3 mt-1" {{ old('newsletter_subscribe') ? 'checked' : '' }}>
                                    <div>
                                        <span class="text-sm font-medium">Sign up for our newsletter</span>
                                        <p class="text-xs text-gray-500 italic">*Get exclusive offers and early discounts*</p>
                                    </div>
                                </label>
                            </div>
                            
                            <!-- Privacy Policy -->
                            <div class="mb-6">
                                <label class="flex items-start">
                                    <input type="checkbox" name="privacy_accepted" id="privacy_accepted" required class="mr-3 mt-1">
                                    <div>
                                        <span class="text-sm font-medium">Customer data privacy *</span>
                                        <p class="text-xs text-gray-500 italic">*I agree to the processing of my personal data and accept the privacy policy.*</p>
                                    </div>
                                </label>
                            </div>
                            
                            <button type="button" onclick="nextStep(2)" 
                                    class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                                Continue
                            </button>
                        </div>
                    </div>

                    <!-- Step 2: Delivery Address -->
                    <div class="checkout-section hidden" id="section-2">
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <h2 class="text-xl font-semibold text-gray-900 mb-6">Delivery Address</h2>
                            <p class="text-sm text-gray-600 mb-6">Search and select your delivery location using RajaOngkir V2.</p>
                            
                            <div class="space-y-4">
                                <!-- Address -->
                                <div>
                                    <label for="address" class="block text-sm font-medium text-gray-700 mb-2">Street Address *</label>
                                    <textarea name="address" id="address" rows="3" required
                                              placeholder="Enter your complete street address"
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">{{ old('address') }}</textarea>
                                </div>
                                
                                <!-- Location Search -->
                                <div class="relative">
                                    <label for="destination_search" class="block text-sm font-medium text-gray-700 mb-2">
                                        Search Your Location * 
                                        <span class="text-xs text-gray-500">(Type at least 2 characters)</span>
                                    </label>
                                    <input type="text" 
                                           id="destination_search" 
                                           placeholder="e.g., kebayoran lama, jakarta selatan"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
                                           autocomplete="off">
                                    
                                    <!-- Search Results -->
                                    <div id="search-results" class="hidden absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto">
                                        <!-- Results will be populated here -->
                                    </div>
                                </div>
                                
                                <!-- Selected Destination Display -->
                                <div id="selected-destination" class="hidden p-4 bg-green-50 border border-green-200 rounded-md">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h4 class="font-medium text-green-800">Selected Location:</h4>
                                            <p id="selected-destination-text" class="text-sm text-green-700"></p>
                                        </div>
                                        <button type="button" onclick="clearDestination()" class="text-red-600 hover:text-red-800 text-sm">
                                            Change
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Hidden fields for destination -->
                                <input type="hidden" name="destination_id" id="destination_id" required>
                                <input type="hidden" name="destination_label" id="destination_label" required>
                                
                                <!-- Postal Code -->
                                <div>
                                    <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-2">Postal Code *</label>
                                    <input type="text" name="postal_code" id="postal_code" required
                                           value="{{ old('postal_code') }}"
                                           placeholder="e.g., 12310"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>
                            
                            <div class="flex space-x-4 mt-8">
                                <button type="button" onclick="prevStep(1)" 
                                        class="flex-1 bg-gray-300 text-gray-700 py-3 rounded-lg hover:bg-gray-400 transition-colors font-medium">
                                    Previous
                                </button>
                                <button type="button" onclick="nextStep(3)" id="continue-step-2"
                                        class="flex-1 bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                                    Continue
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Shipping Method -->
                    <div class="checkout-section hidden" id="section-3">
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <h2 class="text-xl font-semibold text-gray-900 mb-6">Shipping Method</h2>
                            <p class="text-sm text-gray-600 mb-4">Package weight: <strong>{{ $totalWeight ?? 1000 }}g</strong></p>
                            
                            <div id="shipping-loading" class="hidden p-4 text-center">
                                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                                <p class="text-sm text-gray-600 mt-2">Calculating shipping options...</p>
                            </div>
                            
                            <div id="shipping-options" class="space-y-3 min-h-[150px]">
                                <div class="p-4 text-center text-gray-500 border-2 border-dashed border-gray-200 rounded-lg">
                                    <p>📍 Please select your delivery location first</p>
                                </div>
                            </div>
                            
                            <input type="hidden" name="shipping_method" id="shipping_method" required>
                            <input type="hidden" name="shipping_cost" id="shipping_cost" value="0" required>
                            
                            <div class="flex space-x-4 mt-8">
                                <button type="button" onclick="prevStep(2)" 
                                        class="flex-1 bg-gray-300 text-gray-700 py-3 rounded-lg hover:bg-gray-400 transition-colors font-medium">
                                    Previous
                                </button>
                                <button type="button" onclick="nextStep(4)" id="continue-step-3"
                                        class="flex-1 bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                                    Continue
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: Payment -->
                    <div class="checkout-section hidden" id="section-4">
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <h2 class="text-xl font-semibold text-gray-900 mb-6">Payment</h2>
                            
                            <div class="space-y-3 mb-6">
                                <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                                    <input type="radio" name="payment_method" value="bank_transfer" checked class="mr-4">
                                    <div class="flex-1">
                                        <div class="font-medium">Bank Transfer</div>
                                        <div class="text-sm text-gray-600">Transfer via ATM, Internet Banking, or Mobile Banking</div>
                                    </div>
                                </label>
                                
                                <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                                    <input type="radio" name="payment_method" value="credit_card" class="mr-4">
                                    <div class="flex-1">
                                        <div class="font-medium">Credit Card</div>
                                        <div class="text-sm text-gray-600">Visa, Mastercard, JCB</div>
                                    </div>
                                </label>
                                
                                <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                                    <input type="radio" name="payment_method" value="ewallet" class="mr-4">
                                    <div class="flex-1">
                                        <div class="font-medium">E-Wallet</div>
                                        <div class="text-sm text-gray-600">GoPay, OVO, DANA, ShopeePay</div>
                                    </div>
                                </label>
                                
                                <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                                    <input type="radio" name="payment_method" value="cod" class="mr-4">
                                    <div class="flex-1">
                                        <div class="font-medium">Cash on Delivery (COD)</div>
                                        <div class="text-sm text-gray-600">Pay when the order is delivered</div>
                                    </div>
                                </label>
                            </div>
                            
                            <!-- Order Notes -->
                            <div class="mb-6">
                                <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Order Notes (Optional)</label>
                                <textarea name="notes" id="notes" rows="3" 
                                          placeholder="Any special instructions for your order?"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">{{ old('notes') }}</textarea>
                            </div>
                            
                            <div class="flex space-x-4 mt-8">
                                <button type="button" onclick="prevStep(3)" 
                                        class="flex-1 bg-gray-300 text-gray-700 py-3 rounded-lg hover:bg-gray-400 transition-colors font-medium">
                                    Previous
                                </button>
                                <button type="submit" id="place-order-btn" 
                                        class="flex-1 bg-green-600 text-white py-3 rounded-lg hover:bg-green-700 transition-colors font-medium">
                                    Place Order
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Order Summary -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-md p-6 sticky top-4">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Order Summary</h2>
                        
                        <!-- Cart Items Preview -->
                        <div class="mb-4 max-h-40 overflow-y-auto">
                            @foreach($cartItems as $item)
                                <div class="flex items-center space-x-3 py-2 border-b border-gray-100 last:border-b-0">
                                    <div class="w-12 h-12 bg-gray-200 rounded flex-shrink-0">
                                        @if($item['image'])
                                            <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}" class="w-full h-full object-cover rounded">
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate">{{ $item['name'] }}</p>
                                        <p class="text-xs text-gray-500">Qty: {{ $item['quantity'] }} × Rp {{ number_format($item['price'], 0, ',', '.') }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        <div class="mb-4">
                            <p class="text-sm text-gray-600">{{ count($cartItems) }} Item{{ count($cartItems) > 1 ? 's' : '' }}</p>
                        </div>
                        
                        <!-- Order Totals -->
                        <div class="border-t pt-4 space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Subtotal:</span>
                                <span class="font-semibold" id="subtotal-display">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Shipping:</span>
                                <span class="font-semibold" id="shipping-display">Rp 0</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Tax (11%):</span>
                                <span class="font-semibold" id="tax-display">Rp {{ number_format($subtotal * 0.11, 0, ',', '.') }}</span>
                            </div>
                            <div class="border-t pt-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-900 font-semibold">Total:</span>
                                    <span class="text-2xl font-bold text-blue-600" id="total-display">Rp {{ number_format($subtotal * 1.11, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Hidden inputs for JavaScript -->
                        <input type="hidden" id="subtotal-value" value="{{ $subtotal }}">
                        <input type="hidden" id="tax-rate" value="0.11">
                        <input type="hidden" id="total-weight" value="{{ $totalWeight ?? 1000 }}">
                        
                        <!-- Security Badge -->
                        <div class="mt-6 text-center">
                            <p class="text-xs text-gray-500">
                                🔒 Secure checkout with 256-bit SSL encryption
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
.step {
    flex: 1;
    text-align: center;
    position: relative;
}

.step.active .step-number {
    background-color: #3b82f6;
    color: white;
    border-color: #3b82f6;
}

.step.completed .step-number {
    background-color: #10b981;
    color: white;
    border-color: #10b981;
}

.step-number {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #e5e7eb;
    color: #6b7280;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 0.5rem;
    font-weight: 600;
    border: 3px solid #e5e7eb;
    transition: all 0.3s ease;
}

.step-title {
    font-size: 0.875rem;
    color: #6b7280;
    transition: all 0.3s ease;
}

.step.active .step-title {
    color: #3b82f6;
    font-weight: 600;
}

.step.completed .step-title {
    color: #10b981;
    font-weight: 500;
}

.checkout-section.active {
    display: block !important;
}

#search-results .search-result-item {
    padding: 12px;
    cursor: pointer;
    border-bottom: 1px solid #e5e7eb;
    transition: background-color 0.2s ease;
}

#search-results .search-result-item:hover {
    background-color: #f3f4f6;
}

#search-results .search-result-item:last-child {
    border-bottom: none;
}

.shipping-option {
    transition: all 0.2s ease;
}

.shipping-option:hover {
    background-color: #f8fafc;
    border-color: #3b82f6;
}

.shipping-option input[type="radio"]:checked + .shipping-content {
    border-color: #3b82f6;
    background-color: #eff6ff;
}
</style>

<!-- Load the separated JavaScript file -->
<script src="{{ asset('js/simple-checkout.js') }}"></script>

@endsection