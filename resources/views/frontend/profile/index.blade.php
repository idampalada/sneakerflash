@extends('layouts.app')

@section('title', 'My Profile - SneakerFlash')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">My Profile</h1>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Profile Information Form -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold text-gray-900">Profile Information</h2>
                        
                        <button id="edit-profile-btn" 
                                class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition-colors">
                            Edit Profile
                        </button>
                    </div>
                    
                    <!-- Show locked fields info if any field is locked -->
                    @if(isset($lockedFields) && (($lockedFields['name'] ?? false) || ($lockedFields['email'] ?? false) || ($lockedFields['phone'] ?? false)))
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-blue-500 mt-0.5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                                </svg>
                                <div>
                                    <h3 class="text-sm font-medium text-blue-800">Field Protection</h3>
                                    <p class="text-sm text-blue-700 mt-1">
                                        Fields with data are protected and cannot be modified for security reasons.
                                        @if(($lockedFields['gender'] ?? false) === false || ($lockedFields['birthdate'] ?? false) === false)
                                            You can still edit empty fields like gender and birthdate.
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
                    
                    <form id="profile-form" action="{{ route('profile.update') }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <!-- Profile Display Mode -->
                        <div id="profile-display" class="space-y-4">
                            <!-- Name -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Full Name
                                    @if($lockedFields['name'] ?? false)
                                        <span class="text-xs text-blue-600 ml-2">🔒 Protected</span>
                                    @endif
                                </label>
                                <p class="text-gray-900 py-2 px-3 bg-gray-50 rounded">
                                    {{ $user->name ?? 'N/A' }}
                                </p>
                            </div>

                            <!-- Email -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Email
                                    @if($lockedFields['email'] ?? false)
                                        <span class="text-xs text-blue-600 ml-2">🔒 Protected</span>
                                    @endif
                                </label>
                                <p class="text-gray-900 py-2 px-3 bg-gray-50 rounded">{{ $user->email ?? 'N/A' }}</p>
                            </div>

                            <!-- Phone -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Phone Number
                                    @if($lockedFields['phone'] ?? false)
                                        <span class="text-xs text-blue-600 ml-2">🔒 Protected</span>
                                    @endif
                                </label>
                                <p class="text-gray-900 py-2 px-3 bg-gray-50 rounded">
                                    {{ $user->phone ?? 'Not set' }}
                                    @if(empty($user->phone))
                                        <span class="text-red-500 text-sm">(Required for completion)</span>
                                    @endif
                                </p>
                            </div>

                            <!-- Gender -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Gender
                                    @if($lockedFields['gender'] ?? false)
                                        <span class="text-xs text-blue-600 ml-2">🔒 Protected</span>
                                    @else
                                        <span class="text-xs text-green-600 ml-2">✏️ Editable</span>
                                    @endif
                                </label>
                                <p class="text-gray-900 py-2 px-3 bg-gray-50 rounded">
                                    {{ $user->gender ? ucfirst($user->gender) : 'Not set' }}
                                </p>
                            </div>

                            <!-- Birthdate -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Birthdate
                                    @if($lockedFields['birthdate'] ?? false)
                                        <span class="text-xs text-blue-600 ml-2">🔒 Protected</span>
                                    @else
                                        <span class="text-xs text-green-600 ml-2">✏️ Editable</span>
                                    @endif
                                </label>
                                <p class="text-gray-900 py-2 px-3 bg-gray-50 rounded">
                                    @if($user->birthdate)
                                        {{ \Carbon\Carbon::parse($user->birthdate)->format('d M Y') }}
                                        @php
                                            $age = \Carbon\Carbon::parse($user->birthdate)->age;
                                        @endphp
                                        @if($age)
                                            ({{ $age }} years old)
                                        @endif
                                    @else
                                        Not set
                                    @endif
                                </p>
                            </div>

                            {{-- NEW: Zodiac Display --}}
                            @if($user->zodiac && isset($zodiacInfo))
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Zodiac Sign
                                    <span class="text-xs text-purple-600 ml-2">✨ Auto-calculated</span>
                                </label>
                                <div class="py-2 px-3 bg-gradient-to-r from-purple-50 to-pink-50 rounded border border-purple-200">
                                    <div class="flex items-center space-x-3">
                                        <span class="text-2xl">{{ $zodiacInfo['symbol'] }}</span>
                                        <div>
                                            <div class="font-semibold text-purple-800">{{ $zodiacInfo['name'] }}</div>
                                            <div class="text-sm text-purple-600">{{ $zodiacInfo['dates'] }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- Profile Completion Info -->
                            @if(!($isProfileLocked ?? false))
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-blue-800">Profile Completion</span>
                                    <span class="text-sm text-blue-600">
                                        @php
                                            $requiredFields = ['name', 'email', 'phone'];
                                            $completedFields = 0;
                                            foreach($requiredFields as $field) {
                                                if(!empty($user->$field)) $completedFields++;
                                            }
                                            $percentage = round(($completedFields / count($requiredFields)) * 100);
                                        @endphp
                                        {{ $percentage }}%
                                    </span>
                                </div>
                                <div class="w-full bg-blue-200 rounded-full h-2 mt-2">
                                    <div class="bg-blue-600 h-2 rounded-full" data-width="{{ $percentage }}"></div>
                                </div>
                                @if($percentage < 100)
                                <p class="text-xs text-blue-700 mt-2">
                                    @php
                                        $missingFields = [];
                                        foreach($requiredFields as $field) {
                                            if(empty($user->$field)) {
                                                $missingFields[] = ucfirst($field);
                                            }
                                        }
                                    @endphp
                                    Missing: {{ implode(', ', $missingFields) }}
                                </p>
                                @else
                                <p class="text-xs text-blue-700 mt-2">
                                    ✅ Profile completed! All required fields are filled.
                                </p>
                                @endif
                            </div>
                            @endif
                        </div>

                        <!-- Profile Edit Mode -->
                        <div id="profile-edit" class="hidden space-y-4">
                            <!-- Name -->
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                    Full Name *
                                    @if($lockedFields['name'] ?? false)
                                        <span class="text-xs text-blue-600 ml-2">🔒 Cannot be changed</span>
                                    @endif
                                </label>
                                <input type="text" 
                                       name="name" 
                                       id="name" 
                                       value="{{ old('name', $user->name ?? '') }}"
                                       @if($lockedFields['name'] ?? false)
                                           disabled
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 text-gray-500 cursor-not-allowed"
                                       @else
                                           required
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       @endif>
                                @if($lockedFields['name'] ?? false)
                                    <p class="text-xs text-blue-600 mt-1">This field is protected and cannot be modified</p>
                                @endif
                            </div>

                            <!-- Email -->
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                    Email *
                                    @if($lockedFields['email'] ?? false)
                                        <span class="text-xs text-blue-600 ml-2">🔒 Cannot be changed</span>
                                    @endif
                                </label>
                                <input type="email" 
                                       name="email" 
                                       id="email" 
                                       value="{{ old('email', $user->email ?? '') }}"
                                       @if($lockedFields['email'] ?? false)
                                           disabled
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 text-gray-500 cursor-not-allowed"
                                       @else
                                           required
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       @endif>
                                @if($lockedFields['email'] ?? false)
                                    <p class="text-xs text-blue-600 mt-1">This field is protected and cannot be modified</p>
                                @endif
                            </div>

                            <!-- Phone -->
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                                    Phone Number *
                                    @if($lockedFields['phone'] ?? false)
                                        <span class="text-xs text-blue-600 ml-2">🔒 Cannot be changed</span>
                                    @endif
                                </label>
                                <input type="tel" 
                                       name="phone" 
                                       id="phone" 
                                       value="{{ old('phone', $user->phone ?? '') }}"
                                       placeholder="08xxxxxxxxxx"
                                       @if($lockedFields['phone'] ?? false)
                                           disabled
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 text-gray-500 cursor-not-allowed"
                                       @else
                                           required
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       @endif>
                                @if($lockedFields['phone'] ?? false)
                                    <p class="text-xs text-blue-600 mt-1">This field is protected and cannot be modified</p>
                                @elseif(empty($user->phone))
                                    <p class="text-xs text-gray-500 mt-1">Required for profile completion</p>
                                @endif
                            </div>

                            <!-- Gender -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Gender
                                    @if(!($lockedFields['gender'] ?? false))
                                        <span class="text-xs text-green-600 ml-2">✏️ Can be edited</span>
                                    @endif
                                </label>
                                <div class="flex space-x-4">
                                    <label class="flex items-center">
                                        <input type="radio" 
                                               name="gender" 
                                               value="mens" 
                                               class="mr-2" 
                                               {{ old('gender', $user->gender ?? '') == 'mens' ? 'checked' : '' }}
                                               @if($lockedFields['gender'] ?? false) disabled @endif>
                                        <span class="text-sm {{ ($lockedFields['gender'] ?? false) ? 'text-gray-500' : '' }}">Mens</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="radio" 
                                               name="gender" 
                                               value="womens" 
                                               class="mr-2" 
                                               {{ old('gender', $user->gender ?? '') == 'womens' ? 'checked' : '' }}
                                               @if($lockedFields['gender'] ?? false) disabled @endif>
                                        <span class="text-sm {{ ($lockedFields['gender'] ?? false) ? 'text-gray-500' : '' }}">Womens</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="radio" 
                                               name="gender" 
                                               value="kids" 
                                               class="mr-2" 
                                               {{ old('gender', $user->gender ?? '') == 'kids' ? 'checked' : '' }}
                                               @if($lockedFields['gender'] ?? false) disabled @endif>
                                        <span class="text-sm {{ ($lockedFields['gender'] ?? false) ? 'text-gray-500' : '' }}">Kids</span>
                                    </label>
                                </div>
                                @if($lockedFields['gender'] ?? false)
                                    <p class="text-xs text-blue-600 mt-1">This field is protected and cannot be modified</p>
                                @endif
                            </div>

                            <!-- Birthdate -->
                            <div>
                                <label for="birthdate" class="block text-sm font-medium text-gray-700 mb-2">
                                    Birthdate
                                    @if(!($lockedFields['birthdate'] ?? false))
                                        <span class="text-xs text-green-600 ml-2">✏️ Can be edited</span>
                                    @endif
                                </label>
                                <input type="date" 
                                       name="birthdate" 
                                       id="birthdate"
                                       value="{{ old('birthdate', $user->birthdate ? \Carbon\Carbon::parse($user->birthdate)->format('Y-m-d') : '') }}"
                                       @if($lockedFields['birthdate'] ?? false)
                                           disabled
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 text-gray-500 cursor-not-allowed"
                                       @else
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       @endif>
                                @if($lockedFields['birthdate'] ?? false)
                                    <p class="text-xs text-blue-600 mt-1">This field is protected and cannot be modified</p>
                                @endif

                                {{-- NEW: Real-time Zodiac Preview --}}
                                <div id="zodiac-preview" class="mt-3 p-3 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-lg hidden">
                                    <div class="flex items-center space-x-3">
                                        <span id="zodiac-symbol" class="text-2xl"></span>
                                        <div>
                                            <div id="zodiac-name" class="font-semibold"></div>
                                            <div id="zodiac-dates" class="text-sm opacity-90"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex space-x-4 mt-6 pt-4 border-t">
                                <button type="submit" 
                                        class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 transition-colors">
                                    Save Changes
                                </button>
                                <button type="button" id="cancel-edit-btn"
                                        class="bg-gray-300 text-gray-700 px-6 py-2 rounded hover:bg-gray-400 transition-colors">
                                    Cancel
                                </button>
                            </div>
                            
                            <!-- Field Protection Notice -->
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mt-4">
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-yellow-400 mt-0.5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                    <div>
                                        <h4 class="text-sm font-medium text-yellow-800">Field Protection System</h4>
                                        <p class="text-sm text-yellow-700 mt-1">
                                            Once a field is filled with data, it becomes protected and cannot be changed for security reasons. 
                                            Empty fields can still be edited until they are filled.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right Sidebar -->
            <div class="space-y-6">
                {{-- NEW: Zodiac Info Card --}}
                @if($user->zodiac && isset($zodiacInfo))
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4">Your Zodiac</h2>
                    
                    <div class="text-center mb-4">
                        <div class="w-16 h-16 bg-gradient-to-r from-purple-500 to-pink-500 rounded-full flex items-center justify-center mx-auto mb-3">
                            <span class="text-2xl text-white">{{ $zodiacInfo['symbol'] }}</span>
                        </div>
                        <h4 class="text-xl font-bold text-gray-900">{{ $zodiacInfo['name'] }}</h4>
                        <p class="text-sm text-gray-600">{{ $zodiacInfo['dates'] }}</p>
                    </div>
                    
                    <div class="space-y-3 mb-4">
                        <div class="flex justify-between">
                            <span class="text-sm font-medium text-gray-700">Element:</span>
                            <span class="text-gray-900">{{ $zodiacInfo['element'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm font-medium text-gray-700">Ruling Planet:</span>
                            <span class="text-gray-900">{{ $zodiacInfo['ruling_planet'] }}</span>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <p class="text-sm text-gray-700">{{ $zodiacInfo['description'] }}</p>
                    </div>
                    
                    <div>
                        <span class="text-sm font-medium text-gray-700">Key Traits:</span>
                        <div class="flex flex-wrap gap-1 mt-2">
                            @foreach($zodiacInfo['traits'] as $trait)
                                <span class="px-2 py-1 bg-purple-100 text-purple-800 text-xs rounded-full">{{ $trait }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                <!-- Address Management -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4">Address Management</h2>
                    
                    @php
                        // Query langsung seperti controller bersih lainnya
                        $addressCount = \App\Models\UserAddress::where('user_id', Auth::id())->where('is_active', true)->count();
                        $primaryAddress = \App\Models\UserAddress::where('user_id', Auth::id())->where('is_primary', true)->where('is_active', true)->first();
                    @endphp
                    
                    <div class="space-y-4">
                        <!-- Address Count -->
                        <div class="text-center p-4 bg-green-50 rounded-lg">
                            <div class="text-2xl font-bold text-green-600">{{ $addressCount }}</div>
                            <div class="text-sm text-gray-600">Saved Addresses</div>
                        </div>
                        
                        <!-- Primary Address Info -->
                        @if($primaryAddress)
                            <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                <h4 class="font-medium text-blue-800 text-sm mb-1">Primary Address:</h4>
                                <p class="text-xs text-blue-700 font-medium">{{ $primaryAddress->label }}</p>
                                <p class="text-xs text-blue-600">{{ $primaryAddress->recipient_name }}</p>
                                <p class="text-xs text-blue-600">{{ $primaryAddress->subdistrict_name }}, {{ $primaryAddress->city_name }}</p>
                            </div>
                        @else
                            <div class="p-3 bg-orange-50 border border-orange-200 rounded-lg">
                                <p class="text-xs text-orange-700">No primary address set</p>
                            </div>
                        @endif
                        
                        <!-- Action Buttons -->
                        <div class="space-y-2">
                            <a href="{{ route('profile.addresses.index') }}" 
                               class="block w-full text-center bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition-colors text-sm">
                                Manage Addresses
                            </a>
                            
                            <a href="{{ route('profile.addresses.create') }}" 
                               class="block w-full text-center bg-green-600 text-white py-2 rounded hover:bg-green-700 transition-colors text-sm">
                                + Add New Address
                            </a>
                        </div>
                        
                        <!-- Quick Info -->
                        <div class="text-xs text-gray-500 bg-gray-50 p-2 rounded">
                            <p>• Primary address is used for checkout</p>
                            <p>• You can have multiple delivery addresses</p>
                            <p>• Addresses are used for shipping calculations</p>
                        </div>
                    </div>
                </div>

                <!-- Order Statistics -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4">Order Statistics</h2>
                    
                    <div class="grid grid-cols-1 gap-4">
                        <div class="text-center p-4 bg-blue-50 rounded-lg">
                            <div class="text-2xl font-bold text-blue-600">{{ $totalOrders ?? 0 }}</div>
                            <div class="text-sm text-gray-600">Total Orders</div>
                        </div>
                        <div class="text-center p-4 bg-green-50 rounded-lg">
                            <div class="text-2xl font-bold text-green-600">
                                Rp {{ number_format($totalSpent ?? 0, 0, ',', '.') }}
                            </div>
                            <div class="text-sm text-gray-600">Total Spent</div>
                        </div>
                    </div>

                    @if(Route::has('orders.index'))
                    <a href="{{ route('orders.index') }}" 
                       class="mt-4 block w-full text-center bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition-colors">
                        View Order History
                    </a>
                    @endif
                </div>

                <!-- Account Settings -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4">Account Settings</h2>
                    
                    <div class="space-y-3">
                        <a href="{{ route('profile.password.change') }}" 
                           class="block w-full text-left bg-gray-50 hover:bg-gray-100 p-3 rounded transition-colors">
                            <div class="font-medium">Change Password</div>
                            <div class="text-sm text-gray-600">Update your password</div>
                        </a>
                        
                        <div class="block w-full text-left bg-gray-50 p-3 rounded">
                            <div class="font-medium">Account Status</div>
                            <div class="text-sm text-gray-600">
                                @if($user->email_verified_at)
                                    <span class="text-green-600">✓ Email Verified</span>
                                @else
                                    <span class="text-yellow-600">⚠ Email Not Verified</span>
                                @endif
                                
                                @if($isProfileLocked ?? false)
                                    <br><span class="text-blue-600">✅ Profile Completed</span>
                                @else
                                    <br><span class="text-orange-600">⚠ Profile Incomplete</span>
                                @endif
                                
                                <br><span class="text-green-600">📍 {{ $addressCount }} Address{{ $addressCount != 1 ? 'es' : '' }}</span>
                                
                                {{-- NEW: Zodiac Status --}}
                                @if($user->zodiac)
                                    <br><span class="text-purple-600">✨ Zodiac: {{ $user->zodiac }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Zodiac calculation function
function calculateZodiac(birthdate) {
    if (!birthdate) return null;
    
    const date = new Date(birthdate);
    const month = date.getMonth() + 1;
    const day = date.getDate();
    const monthDay = month * 100 + day;
    
    const zodiacData = {
        'PISCES': { symbol: '♓', dates: '19 FEB - 20 MAR' },
        'ARIES': { symbol: '♈', dates: '21 MAR - 19 APR' },
        'TAURUS': { symbol: '♉', dates: '20 APR - 20 MEI' },
        'GEMINI': { symbol: '♊', dates: '21 MEI - 20 JUNI' },
        'CANCER': { symbol: '♋', dates: '21 JUN - 22 JUL' },
        'LEO': { symbol: '♌', dates: '23 JULI - 22 AGUS' },
        'VIRGO': { symbol: '♍', dates: '23 AGUS - 22 SEP' },
        'LIBRA': { symbol: '♎', dates: '23 SEP - 22 OKT' },
        'SCORPIO': { symbol: '♏', dates: '23 OKT - 21 NOV' },
        'SAGITARIUS': { symbol: '♐', dates: '22 NOV - 21 DES' },
        'CAPRICORN': { symbol: '♑', dates: '22 DES - 19 JAN' },
        'AQUARIUS': { symbol: '♒', dates: '20 JAN - 18 FEB' }
    };
    
    let zodiacName = null;
    
    if ((monthDay >= 1222) || (monthDay <= 119)) zodiacName = 'CAPRICORN';
    else if (monthDay >= 120 && monthDay <= 218) zodiacName = 'AQUARIUS';
    else if (monthDay >= 219 && monthDay <= 320) zodiacName = 'PISCES';
    else if (monthDay >= 321 && monthDay <= 419) zodiacName = 'ARIES';
    else if (monthDay >= 420 && monthDay <= 520) zodiacName = 'TAURUS';
    else if (monthDay >= 521 && monthDay <= 620) zodiacName = 'GEMINI';
    else if (monthDay >= 621 && monthDay <= 722) zodiacName = 'CANCER';
    else if (monthDay >= 723 && monthDay <= 822) zodiacName = 'LEO';
    else if (monthDay >= 823 && monthDay <= 922) zodiacName = 'VIRGO';
    else if (monthDay >= 923 && monthDay <= 1022) zodiacName = 'LIBRA';
    else if (monthDay >= 1023 && monthDay <= 1121) zodiacName = 'SCORPIO';
    else if (monthDay >= 1122 && monthDay <= 1221) zodiacName = 'SAGITARIUS';
    
    return zodiacName ? {
        name: zodiacName,
        symbol: zodiacData[zodiacName].symbol,
        dates: zodiacData[zodiacName].dates
    } : null;
}

document.addEventListener('DOMContentLoaded', function() {
    // Set width for progress bar using data attribute
    const progressBar = document.querySelector('[data-width]');
    if (progressBar) {
        const width = progressBar.getAttribute('data-width');
        progressBar.style.width = width + '%';
    }

    const editBtn = document.getElementById('edit-profile-btn');
    const cancelBtn = document.getElementById('cancel-edit-btn');
    const profileDisplay = document.getElementById('profile-display');
    const profileEdit = document.getElementById('profile-edit');

    if (editBtn && cancelBtn && profileDisplay && profileEdit) {
        editBtn.addEventListener('click', function() {
            profileDisplay.classList.add('hidden');
            profileEdit.classList.remove('hidden');
            editBtn.style.display = 'none';
        });

        cancelBtn.addEventListener('click', function() {
            profileEdit.classList.add('hidden');
            profileDisplay.classList.remove('hidden');
            editBtn.style.display = 'block';
            
            // Reset form to original values
            const form = document.getElementById('profile-form');
            if (form) {
                form.reset();
            }
            
            // Hide zodiac preview when canceling
            const zodiacPreview = document.getElementById('zodiac-preview');
            if (zodiacPreview) {
                zodiacPreview.classList.add('hidden');
            }
        });
    }

    // NEW: Birth date change handler for zodiac preview
    const birthdateInput = document.getElementById('birthdate');
    if (birthdateInput) {
        birthdateInput.addEventListener('change', function() {
            const birthdate = this.value;
            const preview = document.getElementById('zodiac-preview');
            
            if (birthdate) {
                const zodiac = calculateZodiac(birthdate);
                
                if (zodiac) {
                    document.getElementById('zodiac-symbol').textContent = zodiac.symbol;
                    document.getElementById('zodiac-name').textContent = zodiac.name;
                    document.getElementById('zodiac-dates').textContent = zodiac.dates;
                    preview.classList.remove('hidden');
                } else {
                    preview.classList.add('hidden');
                }
            } else {
                preview.classList.add('hidden');
            }
        });

        // Show zodiac preview on page load if birthdate exists
        if (birthdateInput.value) {
            birthdateInput.dispatchEvent(new Event('change'));
        }
    }
});
</script>
@endsection