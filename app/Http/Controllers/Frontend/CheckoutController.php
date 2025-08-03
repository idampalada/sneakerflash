<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\MidtransService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class CheckoutController extends Controller
{
    private $rajaOngkirApiKey;
    private $rajaOngkirBaseUrl;
    private $midtransService;

    public function __construct(MidtransService $midtransService)
    {
        $this->midtransService = $midtransService;
        
        // RajaOngkir API V2 via Komerce - Fixed Format
        $this->rajaOngkirApiKey = config('services.rajaongkir.api_key') ?: env('RAJAONGKIR_API_KEY');
        $this->rajaOngkirBaseUrl = 'https://rajaongkir.komerce.id/api/v1';
        
        Log::info('RajaOngkir V2 Controller initialized (Fixed Format)', [
            'base_url' => $this->rajaOngkirBaseUrl,
            'api_key_set' => !empty($this->rajaOngkirApiKey),
            'origin_city' => env('STORE_ORIGIN_CITY_NAME', 'Not configured'),
            'origin_city_id' => env('STORE_ORIGIN_CITY_ID', 'Not configured'),
            'midtrans_configured' => !empty(config('services.midtrans.server_key'))
        ]);
    }

    public function index()
    {
        // Get cart from session
        $cart = Session::get('cart', []);
        
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        // Get cart items and calculate weight
        $cartItems = $this->getCartItems($cart);
        $subtotal = $cartItems->sum('subtotal');
        $totalWeight = $this->calculateTotalWeight($cartItems);

        // Get provinces from RajaOngkir API V2 (Fixed Format)
        $provinces = $this->getProvinces();

        // Get major cities for quick selection
        $majorCities = $this->getMajorCities();

        Log::info('Checkout initialized with RajaOngkir V2 (Fixed Format)', [
            'cart_count' => count($cart),
            'cart_items_count' => $cartItems->count(),
            'subtotal' => $subtotal,
            'total_weight' => $totalWeight,
            'provinces_count' => count($provinces),
            'major_cities_count' => count($majorCities),
            'store_origin' => env('STORE_ORIGIN_CITY_NAME', 'Not configured')
        ]);

        return view('frontend.checkout.index', compact('cartItems', 'subtotal', 'provinces', 'majorCities', 'totalWeight'));
    }


    /**
     * Search destinations - Main method for location selection
     * Enhanced error handling for API issues
     */
    public function searchDestinations(Request $request)
    {
        $search = $request->get('search');
        $limit = $request->get('limit', 10);
        
        Log::info('Searching destinations via RajaOngkir V2 (Fixed)', ['search' => $search]);
        
        if (!$search || strlen($search) < 2) {
            return response()->json(['error' => 'Search term must be at least 2 characters'], 400);
        }

        try {
            $response = Http::timeout(10)->withHeaders([
                'key' => $this->rajaOngkirApiKey
            ])->get($this->rajaOngkirBaseUrl . '/destination/domestic-destination', [
                'search' => $search,
                'limit' => $limit,
                'offset' => 0
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['data']) && is_array($data['data'])) {
                    $destinations = array_map(function($dest) {
                        return [
                            'location_id' => $dest['id'],
                            'subdistrict_name' => $dest['subdistrict_name'],
                            'district_name' => $dest['district_name'],
                            'city_name' => $dest['city_name'],
                            'province_name' => $dest['province_name'],
                            'zip_code' => $dest['zip_code'],
                            'label' => $dest['label'],
                            'display_name' => $dest['subdistrict_name'] . ', ' . $dest['district_name'] . ', ' . $dest['city_name'],
                            'full_address' => $dest['label']
                        ];
                    }, $data['data']);
                    
                    Log::info('Found ' . count($destinations) . ' destinations for search: ' . $search);
                    
                    return response()->json([
                        'success' => true,
                        'total' => count($destinations),
                        'data' => $destinations
                    ]);
                }
            } else {
                // API error - return mock data based on search term
                Log::warning('API search failed, returning mock data', [
                    'status' => $response->status(),
                    'search' => $search
                ]);
                
                return $this->getMockDestinations($search);
            }

            return response()->json(['error' => 'No destinations found'], 404);

        } catch (\Exception $e) {
            Log::error('RajaOngkir V2 search error: ' . $e->getMessage());
            
            // Return mock data on API failure
            return $this->getMockDestinations($search);
        }
    }

    /**
     * Generate mock destination data when API is not available
     */
    private function getMockDestinations($search)
    {
        $mockDestinations = [];
        
        // Common destinations based on search term
        $searchLower = strtolower($search);
        
        if (strpos($searchLower, 'jakarta') !== false) {
            $mockDestinations = [
                [
                    'location_id' => 'mock_jkt_001',
                    'subdistrict_name' => 'Menteng',
                    'district_name' => 'Menteng',
                    'city_name' => 'Jakarta Pusat',
                    'province_name' => 'DKI Jakarta',
                    'zip_code' => '10310',
                    'label' => 'Menteng, Jakarta Pusat, DKI Jakarta 10310',
                    'display_name' => 'Menteng, Jakarta Pusat',
                    'full_address' => 'Menteng, Jakarta Pusat, DKI Jakarta 10310'
                ],
                [
                    'location_id' => 'mock_jkt_002',
                    'subdistrict_name' => 'Kebayoran Lama',
                    'district_name' => 'Kebayoran Lama',
                    'city_name' => 'Jakarta Selatan',
                    'province_name' => 'DKI Jakarta',
                    'zip_code' => '12240',
                    'label' => 'Kebayoran Lama, Jakarta Selatan, DKI Jakarta 12240',
                    'display_name' => 'Kebayoran Lama, Jakarta Selatan',
                    'full_address' => 'Kebayoran Lama, Jakarta Selatan, DKI Jakarta 12240'
                ]
            ];
        } elseif (strpos($searchLower, 'bandung') !== false) {
            $mockDestinations = [
                [
                    'location_id' => 'mock_bdg_001',
                    'subdistrict_name' => 'Sukasari',
                    'district_name' => 'Sukasari',
                    'city_name' => 'Bandung',
                    'province_name' => 'Jawa Barat',
                    'zip_code' => '40164',
                    'label' => 'Sukasari, Bandung, Jawa Barat 40164',
                    'display_name' => 'Sukasari, Bandung',
                    'full_address' => 'Sukasari, Bandung, Jawa Barat 40164'
                ]
            ];
        } elseif (strpos($searchLower, 'surabaya') !== false) {
            $mockDestinations = [
                [
                    'location_id' => 'mock_sby_001',
                    'subdistrict_name' => 'Gubeng',
                    'district_name' => 'Gubeng',
                    'city_name' => 'Surabaya',
                    'province_name' => 'Jawa Timur',
                    'zip_code' => '60281',
                    'label' => 'Gubeng, Surabaya, Jawa Timur 60281',
                    'display_name' => 'Gubeng, Surabaya',
                    'full_address' => 'Gubeng, Surabaya, Jawa Timur 60281'
                ]
            ];
        }
        
        if (empty($mockDestinations)) {
            // Generic mock for unknown search terms
            $mockDestinations = [
                [
                    'location_id' => 'mock_generic_001',
                    'subdistrict_name' => ucfirst($search),
                    'district_name' => ucfirst($search),
                    'city_name' => ucfirst($search),
                    'province_name' => 'Indonesia',
                    'zip_code' => '10000',
                    'label' => ucfirst($search) . ', Indonesia 10000',
                    'display_name' => ucfirst($search),
                    'full_address' => ucfirst($search) . ', Indonesia 10000'
                ]
            ];
        }
        
        Log::info('Returning mock destinations for search: ' . $search, [
            'count' => count($mockDestinations)
        ]);
        
        return response()->json([
            'success' => true,
            'total' => count($mockDestinations),
            'data' => $mockDestinations,
            'note' => 'Mock data - API not available'
        ]);
    }

    /**
     * Calculate shipping - Modified for search-based approach
     */
    public function calculateShipping(Request $request)
    {
        $destinationId = $request->get('destination_id');
        $destinationLabel = $request->get('destination_label', '');
        $weight = $request->get('weight', 1000);

        Log::info('Auto-calculating shipping via RajaOngkir V2 (Search-based)', [
            'destination_id' => $destinationId,
            'destination_label' => $destinationLabel,
            'weight' => $weight,
            'store_origin_city' => env('STORE_ORIGIN_CITY_NAME', 'Not configured')
        ]);

        if (!$destinationId) {
            return response()->json(['error' => 'Destination ID is required'], 400);
        }

        try {
            // Get origin from .env configuration
            $originId = $this->getOriginIdFromEnv();
            
            Log::info('Using origin from .env configuration', [
                'origin_id' => $originId,
                'origin_city_name' => env('STORE_ORIGIN_CITY_NAME'),
                'origin_city_id_fallback' => env('STORE_ORIGIN_CITY_ID')
            ]);

            // Try to calculate real shipping costs
            $shippingOptions = $this->calculateRealShipping($originId, $destinationId, $weight);
            
            if (empty($shippingOptions)) {
                // Fallback to mock shipping options
                Log::info('Using mock shipping options (real endpoint not found)');
                $shippingOptions = $this->getMockShippingOptions($weight, $destinationLabel);
            }

            if (!empty($shippingOptions)) {
                // Auto-sort by best value
                $shippingOptions = $this->autoSortShippingOptions($shippingOptions);
                
                Log::info('Successfully calculated ' . count($shippingOptions) . ' shipping options');
                
                return response()->json([
                    'success' => true,
                    'total_options' => count($shippingOptions),
                    'origin_id' => $originId,
                    'origin_city_name' => env('STORE_ORIGIN_CITY_NAME'),
                    'destination_id' => $destinationId,
                    'destination_label' => $destinationLabel,
                    'weight' => $weight,
                    'api_version' => 'v2_fixed_env',
                    'options' => $shippingOptions
                ]);
            } else {
                Log::warning('No shipping options available');
                return response()->json(['error' => 'No shipping options available for this route'], 404);
            }

        } catch (\Exception $e) {
            Log::error('RajaOngkir V2 shipping calculation error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to calculate shipping: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get origin ID from .env configuration
     * Modified to read from environment variables with API error handling
     */
    private function getOriginIdFromEnv()
    {
        // Get origin configuration from .env
        $originCityName = env('STORE_ORIGIN_CITY_NAME', 'jakarta selatan');
        $originCityIdFallback = env('STORE_ORIGIN_CITY_ID', 158);
        // Jika .env ada membaca dari .env kalo tidak ada membaca default 158
        
        Log::info('Getting origin from .env', [
            'configured_city_name' => $originCityName,
            'configured_city_id_fallback' => $originCityIdFallback
        ]);

        // First try to get origin ID by searching the city name
        try {
            $response = Http::timeout(10)->withHeaders([
                'key' => $this->rajaOngkirApiKey
            ])->get($this->rajaOngkirBaseUrl . '/destination/domestic-destination', [
                'search' => strtolower($originCityName),
                'limit' => 1
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['data'][0])) {
                    $foundOrigin = $data['data'][0];
                    Log::info('Found origin via API search', [
                        'origin_id' => $foundOrigin['id'],
                        'origin_label' => $foundOrigin['label'],
                        'search_term' => $originCityName
                    ]);
                    return $foundOrigin['id'];
                }
            } else {
                Log::warning('API search failed', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'search_term' => $originCityName
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Error searching origin city via API: ' . $e->getMessage());
        }
        
        // Fallback to configured city ID from .env
        Log::info('Using fallback origin ID from .env (API not available)', [
            'fallback_origin_id' => $originCityIdFallback,
            'reason' => 'API search failed or unauthorized'
        ]);
        
        return $originCityIdFallback;
    }

    /**
     * Legacy method - keeping for compatibility but now calls getOriginIdFromEnv
     */
    private function getOriginId($destinationId)
    {
        return $this->getOriginIdFromEnv();
    }

    /**
     * Try to calculate real shipping costs
     */
    private function calculateRealShipping($originId, $destinationId, $weight)
    {
        $couriers = ['jne', 'pos', 'tiki', 'sicepat'];
        $shippingOptions = [];

        // Try different cost endpoints
        $endpoints = ['/cost', '/shipping/cost', '/destination/cost', '/calculate'];
        
        foreach ($endpoints as $endpoint) {
            foreach ($couriers as $courier) {
                try {
                    $response = Http::timeout(15)->withHeaders([
                        'key' => $this->rajaOngkirApiKey
                    ])->post($this->rajaOngkirBaseUrl . $endpoint, [
                        'origin' => $originId,
                        'destination' => $destinationId,
                        'weight' => $weight,
                        'courier' => $courier
                    ]);

                    if ($response->successful()) {
                        $data = $response->json();
                        
                        Log::info("Found working cost endpoint: {$endpoint} for courier: {$courier}");
                        Log::info("Response: " . json_encode($data));
                        
                        // Parse response based on actual format
                        $parsed = $this->parseShippingResponse($data, $courier);
                        $shippingOptions = array_merge($shippingOptions, $parsed);
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
            
            if (!empty($shippingOptions)) {
                break; // Stop if we found working endpoint
            }
        }

        return $shippingOptions;
    }

    /**
     * Parse shipping response when we find working endpoint
     */
    private function parseShippingResponse($data, $courier)
    {
        // This will need to be adjusted based on actual response format
        // For now, return empty array until we find working endpoint
        return [];
    }

    /**
     * Generate mock shipping options for testing
     * Enhanced to show origin information
     */
    private function getMockShippingOptions($weight, $destinationLabel = '')
    {
        $basePrice = max(10000, $weight * 5); // Minimum 10k, 5 rupiah per gram
        
        // Add distance factor based on destination
        $distanceFactor = 1;
        $originCity = env('STORE_ORIGIN_CITY_NAME', 'jakarta');
        
        // Calculate distance factor based on origin and destination
        if (stripos($destinationLabel, strtolower($originCity)) !== false) {
            $distanceFactor = 1; // Same city
        } elseif (stripos($destinationLabel, 'jakarta') !== false && stripos($originCity, 'jakarta') !== false) {
            $distanceFactor = 1; // Within Jakarta area
        } elseif (stripos($destinationLabel, 'bandung') !== false || stripos($destinationLabel, 'jawa barat') !== false) {
            $distanceFactor = 1.2;
        } elseif (stripos($destinationLabel, 'surabaya') !== false || stripos($destinationLabel, 'jawa timur') !== false) {
            $distanceFactor = 1.5;
        } elseif (stripos($destinationLabel, 'medan') !== false || stripos($destinationLabel, 'sumatera') !== false) {
            $distanceFactor = 2;
        } else {
            $distanceFactor = 1.8;
        }
        
        $adjustedPrice = $basePrice * $distanceFactor;

        return [
            [
                'courier' => 'JNE',
                'courier_name' => 'Jalur Nugraha Ekakurir (JNE)',
                'service' => 'REG',
                'description' => 'Layanan Reguler',
                'cost' => (int) $adjustedPrice,
                'etd' => '2-3',
                'formatted_cost' => 'Rp ' . number_format($adjustedPrice, 0, ',', '.'),
                'formatted_etd' => '2-3 hari',
                'is_mock' => true,
                'origin_info' => env('STORE_ORIGIN_CITY_NAME', 'jakarta')
            ],
            [
                'courier' => 'POS',
                'courier_name' => 'Pos Indonesia',
                'service' => 'Paket Kilat',
                'description' => 'Pos Kilat Khusus',
                'cost' => (int) ($adjustedPrice * 0.8),
                'etd' => '3-4',
                'formatted_cost' => 'Rp ' . number_format($adjustedPrice * 0.8, 0, ',', '.'),
                'formatted_etd' => '3-4 hari',
                'is_mock' => true,
                'origin_info' => env('STORE_ORIGIN_CITY_NAME', 'jakarta')
            ],
            [
                'courier' => 'TIKI',
                'courier_name' => 'Citra Van Titipan Kilat',
                'service' => 'ECO',
                'description' => 'Ekonomi Service',
                'cost' => (int) ($adjustedPrice * 0.7),
                'etd' => '4-5',
                'formatted_cost' => 'Rp ' . number_format($adjustedPrice * 0.7, 0, ',', '.'),
                'formatted_etd' => '4-5 hari',
                'is_mock' => true,
                'origin_info' => env('STORE_ORIGIN_CITY_NAME', 'jakarta')
            ],
            [
                'courier' => 'SICEPAT',
                'courier_name' => 'SiCepat Ekspres',
                'service' => 'REG',
                'description' => 'Layanan Reguler',
                'cost' => (int) ($adjustedPrice * 0.9),
                'etd' => '2-3',
                'formatted_cost' => 'Rp ' . number_format($adjustedPrice * 0.9, 0, ',', '.'),
                'formatted_etd' => '2-3 hari',
                'is_mock' => true,
                'origin_info' => env('STORE_ORIGIN_CITY_NAME', 'jakarta')
            ],
            [
                'courier' => 'JNE',
                'courier_name' => 'Jalur Nugraha Ekakurir (JNE)',
                'service' => 'YES',
                'description' => 'Yakin Esok Sampai',
                'cost' => (int) ($adjustedPrice * 1.5),
                'etd' => '1',
                'formatted_cost' => 'Rp ' . number_format($adjustedPrice * 1.5, 0, ',', '.'),
                'formatted_etd' => '1 hari',
                'is_mock' => true,
                'origin_info' => env('STORE_ORIGIN_CITY_NAME', 'jakarta')
            ]
        ];
    }

    private function getProvinces()
    {
        try {
            $response = Http::timeout(10)->withHeaders([
                'key' => $this->rajaOngkirApiKey
            ])->get($this->rajaOngkirBaseUrl . '/destination/province');

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['data']) && is_array($data['data'])) {
                    return array_map(function($province) {
                        return [
                            'province_id' => $province['id'],      // Fixed format
                            'province' => $province['name']        // Fixed format
                        ];
                    }, $data['data']);
                }
            }
        } catch (\Exception $e) {
            Log::error('RajaOngkir V2 provinces API error: ' . $e->getMessage());
        }

        return [];
    }

    private function getMajorCities()
    {
        $majorCityNames = ['jakarta', 'bandung', 'surabaya', 'medan', 'semarang', 'makassar'];
        $cities = [];
        
        foreach ($majorCityNames as $cityName) {
            try {
                $response = Http::timeout(10)->withHeaders([
                    'key' => $this->rajaOngkirApiKey
                ])->get($this->rajaOngkirBaseUrl . '/destination/domestic-destination', [
                    'search' => $cityName,
                    'limit' => 1
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    if (isset($data['data'][0])) {
                        $location = $data['data'][0];
                        $cities[] = [
                            'name' => ucfirst($cityName),
                            'location_id' => $location['id'],
                            'label' => $location['label'],
                            'city_name' => $location['city_name'],
                            'province_name' => $location['province_name']
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::error("Error getting major city {$cityName}: " . $e->getMessage());
            }
        }

        return $cities;
    }

    private function autoSortShippingOptions($options)
    {
        usort($options, function($a, $b) {
            $etdA = $this->parseEtd($a['etd']);
            $etdB = $this->parseEtd($b['etd']);
            
            $scoreA = ($a['cost'] / 1000) + ($etdA * 2);
            $scoreB = ($b['cost'] / 1000) + ($etdB * 2);
            
            return $scoreA <=> $scoreB;
        });
        
        return $options;
    }

    private function parseEtd($etd)
    {
        if (strpos($etd, '-') !== false) {
            $parts = explode('-', $etd);
            return (intval($parts[0]) + intval($parts[1])) / 2;
        }
        
        return intval($etd);
    }

    // ... Rest of the methods (store, success, getCartItems, etc.) remain the same
    
public function store(Request $request)
{
    Log::info('=== CHECKOUT REQUEST START ===', [
        'payment_method' => $request->payment_method,
        'is_ajax' => $request->ajax(),
        'content_type' => $request->header('Content-Type'),
        'accept' => $request->header('Accept'),
        'all_data' => $request->all(),
        'url' => $request->url(),
        'method' => $request->method()
    ]);

    // Enhanced validation with detailed error messages
    try {
        $validatedData = $request->validate([
            'social_title' => 'nullable|in:Mr.,Mrs.,Ms.,Miss',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'birthdate' => 'nullable|date|before:today',
            'address' => 'required|string|max:500',
            'destination_id' => 'required|string',
            'destination_label' => 'required|string',
            'postal_code' => 'required|string|max:10',
            'shipping_method' => 'required|string',
            'shipping_cost' => 'required|numeric|min:0',
            'payment_method' => 'required|in:bank_transfer,credit_card,ewallet,cod',
            'create_account' => 'nullable|boolean',
            'password' => 'required_if:create_account,1|nullable|string|min:8',
            'password_confirmation' => 'required_if:create_account,1|nullable|string|same:password',
            'privacy_accepted' => 'required|accepted',
            'newsletter_subscribe' => 'nullable|boolean',
            'notes' => 'nullable|string|max:1000'
        ], [
            'privacy_accepted.required' => 'You must accept the privacy policy to continue.',
            'privacy_accepted.accepted' => 'You must accept the privacy policy to continue.',
            'destination_id.required' => 'Please select a delivery location.',
            'destination_label.required' => 'Please select a delivery location.',
            'shipping_method.required' => 'Please select a shipping method.',
            'shipping_cost.required' => 'Shipping cost is required.',
            'payment_method.required' => 'Please select a payment method.',
            'payment_method.in' => 'Invalid payment method selected.',
            'first_name.required' => 'First name is required.',
            'last_name.required' => 'Last name is required.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'phone.required' => 'Phone number is required.',
            'address.required' => 'Street address is required.',
            'postal_code.required' => 'Postal code is required.'
        ]);

        Log::info('✅ Validation passed', [
            'validated_fields' => array_keys($validatedData),
            'payment_method' => $validatedData['payment_method']
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        Log::error('❌ Validation failed', [
            'errors' => $e->errors(),
            'input_data' => $request->all()
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
                'message' => 'Validation failed. Please check the form fields.'
            ], 422);
        }

        return back()->withErrors($e->errors())->withInput();
    }
    
    // Additional privacy check - Double verification
    if (!$request->has('privacy_accepted') || $request->privacy_accepted != '1') {
        Log::error('❌ Privacy policy not accepted', [
            'privacy_accepted' => $request->privacy_accepted,
            'has_privacy_accepted' => $request->has('privacy_accepted')
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => false,
                'errors' => ['privacy_accepted' => ['You must accept the privacy policy to continue.']],
                'message' => 'Privacy policy must be accepted.'
            ], 422);
        }
        return back()->withInput()->withErrors([
            'privacy_accepted' => 'You must accept the privacy policy to continue.'
        ]);
    }

    try {
        DB::beginTransaction();
        Log::info('📊 Starting database transaction');

        // Get and validate cart
        $cart = Session::get('cart', []);
        
        if (empty($cart)) {
            throw new \Exception('Cart is empty');
        }

        $cartItems = $this->getCartItems($cart);
        
        if ($cartItems->isEmpty()) {
            throw new \Exception('No valid items in cart');
        }

        Log::info('🛒 Cart validated', [
            'cart_count' => count($cart),
            'valid_items' => $cartItems->count(),
            'cart_items' => $cartItems->pluck('name')->toArray()
        ]);

        $subtotal = $cartItems->sum('subtotal');
        $shippingCost = (float) $request->shipping_cost;
        $taxRate = 0.11; // 11% PPN
        $tax = $subtotal * $taxRate;
        $totalAmount = $subtotal + $shippingCost + $tax;

        Log::info('💰 Order calculations', [
            'subtotal' => $subtotal,
            'shipping_cost' => $shippingCost,
            'tax_rate' => $taxRate,
            'tax_amount' => $tax,
            'total_amount' => $totalAmount
        ]);

        // Create user account if requested
        $user = null;
        if ($request->create_account && !Auth::check()) {
            $existingUser = User::where('email', $request->email)->first();
            if ($existingUser) {
                throw new \Exception('Email already exists. Please login or use different email.');
            }

            $user = User::create([
                'name' => $request->first_name . ' ' . $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'email_verified_at' => now(),
                'password' => Hash::make($request->password),
            ]);

            Log::info('👤 User account created', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);
        } elseif (Auth::check()) {
            $user = Auth::user();
            Log::info('👤 Using existing authenticated user', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);
        }

        // Generate unique order number
        do {
            $orderNumber = 'SF-' . date('Ymd') . '-' . strtoupper(Str::random(6));
        } while (Order::where('order_number', $orderNumber)->exists());

        Log::info('📋 Generated order number', ['order_number' => $orderNumber]);

        // Create order
        $orderData = [
            'order_number' => $orderNumber,
            'user_id' => $user ? $user->id : null,
            'customer_name' => $request->first_name . ' ' . $request->last_name,
            'customer_email' => $request->email,
            'customer_phone' => $request->phone,
            'shipping_address' => $request->address,
            'shipping_destination_id' => $request->destination_id,
            'shipping_destination_label' => $request->destination_label,
            'shipping_postal_code' => $request->postal_code,
            'shipping_method' => $request->shipping_method,
            'shipping_cost' => $shippingCost,
            'payment_method' => $request->payment_method,
            'subtotal' => $subtotal,
            'tax_amount' => $tax,
            'total_amount' => $totalAmount,
            'payment_status' => 'pending',
            'order_status' => 'pending',
            'notes' => $request->notes,
            'created_at' => now(),
            'updated_at' => now()
        ];

        $order = Order::create($orderData);
        Log::info('📦 Order created', [
            'order_id' => $order->id,
            'order_number' => $order->order_number
        ]);

        // Create order items and update stock
        foreach ($cartItems as $item) {
            $product = Product::lockForUpdate()->find($item['id']);
            
            if (!$product || $product->stock_quantity < $item['quantity']) {
                throw new \Exception("Insufficient stock for {$item['name']}. Available: " . ($product ? $product->stock_quantity : 0) . ", Requested: {$item['quantity']}");
            }

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['id'],
                'product_name' => $item['name'],
                'product_sku' => $item['sku'] ?? '',
                'product_price' => (float) $item['price'],
                'quantity' => (int) $item['quantity'],
                'total_price' => (float) $item['subtotal']
            ]);

            $product->decrement('stock_quantity', $item['quantity']);
            
            Log::info("📦 Order item created and stock updated", [
                'product_id' => $item['id'],
                'product_name' => $item['name'],
                'quantity' => $item['quantity'],
                'remaining_stock' => $product->stock_quantity - $item['quantity']
            ]);
        }

        DB::commit();
        Log::info('✅ Database transaction committed successfully');

        // Clear cart after successful order
        Session::forget('cart');
        Log::info('🛒 Cart cleared from session');

        // Handle response based on payment method
        if ($request->payment_method === 'cod') {
            // COD - direct success
            Log::info('🚚 COD payment selected - redirecting to success');
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Order placed successfully! Payment will be collected on delivery.',
                    'order_number' => $order->order_number,
                    'redirect_url' => route('checkout.success', ['orderNumber' => $order->order_number])
                ]);
            } else {
                return redirect()->route('checkout.success', ['orderNumber' => $order->order_number])
                               ->with('success', 'Order placed successfully! Payment will be collected on delivery.');
            }
        } else {
            // Online payment - create Midtrans session
            Log::info('💳 Online payment selected - creating Midtrans session');
            
            $snapToken = $this->createMidtransPayment($order, $cartItems, $request);
            
            if ($snapToken) {
                Log::info('✅ Midtrans token created successfully', [
                    'order_number' => $order->order_number,
                    'snap_token' => substr($snapToken, 0, 10) . '...' // Don't log full token
                ]);

                if ($request->ajax()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Order created successfully. Opening payment gateway...',
                        'order_number' => $order->order_number,
                        'snap_token' => $snapToken,
                        'redirect_url' => route('checkout.payment', ['orderNumber' => $order->order_number])
                    ]);
                } else {
                    return redirect()->route('checkout.payment', ['orderNumber' => $order->order_number])
                                   ->with('snap_token', $snapToken);
                }
            } else {
                Log::error('❌ Failed to create Midtrans token', [
                    'order_number' => $order->order_number
                ]);
                
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Failed to create payment session. Please try again or contact support.',
                        'order_number' => $order->order_number
                    ], 500);
                } else {
                    return redirect()->route('checkout.success', ['orderNumber' => $order->order_number])
                                   ->with('error', 'Order created but payment session failed. Please contact support.');
                }
            }
        }

    } catch (\Exception $e) {
        DB::rollback();
        Log::error('❌ Checkout error occurred', [
            'error_message' => $e->getMessage(),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
        
        if ($request->ajax()) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to process checkout: ' . $e->getMessage(),
                'debug_info' => config('app.debug') ? [
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ] : null
            ], 500);
        } else {
            return back()->withInput()->with('error', 'Failed to process checkout: ' . $e->getMessage());
        }
    }
}

    private function createMidtransPayment($order, $cartItems, $request)
{
    try {
        Log::info('Creating Midtrans payment', [
            'order_number' => $order->order_number,
            'total_amount' => $order->total_amount
        ]);

        // Prepare item details for Midtrans
        $itemDetails = [];
        
        foreach ($cartItems as $item) {
            $itemDetails[] = [
                'id' => $item['id'],
                'price' => (int) $item['price'],
                'quantity' => (int) $item['quantity'],
                'name' => $item['name']
            ];
        }
        
        // Add shipping as item
        if ($order->shipping_cost > 0) {
            $itemDetails[] = [
                'id' => 'shipping',
                'price' => (int) $order->shipping_cost,
                'quantity' => 1,
                'name' => 'Shipping Cost - ' . $order->shipping_method
            ];
        }
        
        // Add tax as item
        if ($order->tax_amount > 0) {
            $itemDetails[] = [
                'id' => 'tax',
                'price' => (int) $order->tax_amount,
                'quantity' => 1,
                'name' => 'Tax (PPN 11%)'
            ];
        }

        // Prepare order data for Midtrans
        $midtransOrder = [
            'order_id' => $order->order_number,
            'gross_amount' => (int) $order->total_amount,
            'customer' => [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone
            ],
            'billing_address' => [
                'address' => $request->address,
                'city' => $request->destination_label,
                'postal_code' => $request->postal_code
            ],
            'shipping_address' => [
                'address' => $request->address,
                'city' => $request->destination_label,
                'postal_code' => $request->postal_code
            ],
            'items' => $itemDetails
        ];

        Log::info('Midtrans order data prepared', $midtransOrder);

        $response = $this->midtransService->createSnapToken($midtransOrder);
        
        Log::info('Midtrans response received', $response);
        
        if ($response && isset($response['token'])) {
            // Save snap token to order
            $order->update(['snap_token' => $response['token']]);
            
            Log::info('Midtrans Snap Token created successfully', [
                'order_number' => $order->order_number,
                'snap_token' => $response['token']
            ]);
            
            return $response['token'];
        }

        Log::error('Failed to create Midtrans Snap Token', [
            'order_number' => $order->order_number,
            'response' => $response
        ]);

        return null;

    } catch (\Exception $e) {
        Log::error('Midtrans payment creation error: ' . $e->getMessage(), [
            'order_number' => $order->order_number,
            'trace' => $e->getTraceAsString()
        ]);
        return null;
    }
}

    public function payment($orderNumber)
    {
        $order = Order::with('orderItems.product')
                     ->where('order_number', $orderNumber)
                     ->firstOrFail();
        
        if ($order->payment_status !== 'pending') {
            return redirect()->route('checkout.success', ['orderNumber' => $orderNumber]);
        }

        $snapToken = session('snap_token') ?: $order->snap_token;
        
        if (!$snapToken) {
            return redirect()->route('checkout.success', ['orderNumber' => $orderNumber])
                           ->with('error', 'Payment session expired. Please contact support.');
        }

        return view('frontend.checkout.payment', compact('order', 'snapToken'));
    }

    public function paymentSuccess(Request $request)
    {
        $orderNumber = $request->get('order_id');
        
        if ($orderNumber) {
            $order = Order::where('order_number', $orderNumber)->first();
            
            if ($order) {
                // Update payment status (will be confirmed by webhook)
                $order->update(['payment_status' => 'processing']);
                
                return redirect()->route('checkout.success', ['orderNumber' => $orderNumber])
                               ->with('success', 'Payment completed! We are processing your order.');
            }
        }
        
        return redirect()->route('home')->with('success', 'Payment completed successfully!');
    }

    public function paymentNotification(Request $request)
    {
        try {
            $notification = $this->midtransService->handleNotification($request->all());
            
            if ($notification) {
                $order = Order::where('order_number', $notification['order_id'])->first();
                
                if ($order) {
                    $order->update([
                        'payment_status' => $notification['payment_status'],
                        'payment_response' => json_encode($notification['raw_notification'])
                    ]);
                    
                    // Update order status based on payment
                    if ($notification['payment_status'] === 'paid') {
                        $order->update(['order_status' => 'confirmed']);
                    } elseif (in_array($notification['payment_status'], ['failed', 'cancelled'])) {
                        $order->update(['order_status' => 'cancelled']);
                        
                        // Restore stock
                        foreach ($order->orderItems as $item) {
                            $product = Product::find($item->product_id);
                            if ($product) {
                                $product->increment('stock_quantity', $item->quantity);
                            }
                        }
                    }
                    
                    Log::info('Payment notification processed', [
                        'order_number' => $notification['order_id'],
                        'payment_status' => $notification['payment_status']
                    ]);
                }
            }
            
            return response()->json(['status' => 'success']);
            
        } catch (\Exception $e) {
            Log::error('Payment notification error: ' . $e->getMessage());
            return response()->json(['status' => 'error'], 500);
        }
    }



    public function success($orderNumber)
    {
        $order = Order::with('orderItems.product')
                     ->where('order_number', $orderNumber)
                     ->firstOrFail();
        
        return view('frontend.checkout.success', compact('order'));
    }

    // Other methods remain the same...
    private function getCartItems($cart)
    {
        $cartItems = collect();
        
        foreach ($cart as $productId => $item) {
            if (!is_numeric($productId)) continue;

            $product = Product::where('id', $productId)->where('is_active', true)->first();
            if (!$product) continue;

            $quantity = isset($item['quantity']) ? (int) $item['quantity'] : 1;
            if ($quantity <= 0) continue;

            $price = (float) ($product->sale_price ?? $product->price);
            if ($price <= 0) continue;

            $cartItems->push([
                'id' => (int) $product->id,
                'name' => $product->name,
                'price' => $price,
                'quantity' => $quantity,
                'subtotal' => $price * $quantity,
                'weight' => $product->weight ?? 300,
                'image' => $product->featured_image ?? ($product->images[0] ?? null),
                'slug' => $product->slug,
                'sku' => $product->sku ?? ''
            ]);
        }
        
        return $cartItems;
    }

    private function calculateTotalWeight($cartItems)
    {
        $totalWeight = 0;
        
        foreach ($cartItems as $item) {
            $itemWeight = $item['weight'] ?? 300;
            $totalWeight += $itemWeight * $item['quantity'];
        }
        
        return max($totalWeight, 1000);
    }
}