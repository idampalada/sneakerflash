<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SneakerFlash - Premium Sneakers')</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Custom Styles -->
    <style>
        /* Exact Kick Avenue Colors - Background PUTIH */
        .ka-header {
            background: #ffffff;
            border-bottom: 1px solid #e5e5e5;
        }
        
        .ka-logo {
            font-family: 'Arial Black', 'Helvetica', sans-serif;
            font-weight: 900;
            font-size: 24px;
            letter-spacing: 2px;
            color: #000000;
            text-decoration: none;
            font-style: italic;
            transform: skew(-10deg);
            display: inline-block;
        }
        
        /* Search container dengan lebar custom 1500px */
        .ka-search-custom {
            max-width: 1500px;
        }
        
        .ka-search-container {
            background: #f8f9fa;
            border-radius: 20px;
            border: 1px solid #dee2e6;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .ka-search-input {
            border: none !important;
            outline: none !important;
            box-shadow: none !important;
            background: transparent;
            padding: 12px 45px 12px 45px;
            width: 100%;
            font-size: 14px;
            color: #495057;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        
        .ka-search-input:focus {
            outline: none !important;
            border: none !important;
            box-shadow: none !important;
        }
        
        .ka-search-input::placeholder {
            color: #6c757d;
            font-weight: 400;
        }
        
        .ka-search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            font-size: 14px;
            pointer-events: none;
        }
        
        .ka-search-btn {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            font-size: 16px;
            cursor: pointer;
            padding: 0;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .ka-search-btn:hover {
            color: #495057;
        }
        
        .ka-auth-btn {
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .ka-login-btn {
            color: #666;
            border: 1px solid #ddd;
            background: white;
        }
        
        .ka-login-btn:hover {
            background: #f0f0f0;
            border-color: #ccc;
        }
        
        .ka-register-btn {
            background: #333;
            color: white;
            border: 1px solid #333;
        }
        
        .ka-register-btn:hover {
            background: #555;
        }
        
        /* Navigation Menu items untuk background PUTIH - font sama seperti logo */
        .ka-nav-item-white {
            color: #666666;
            padding: 8px 20px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            font-family: 'Arial Black', 'Helvetica', sans-serif;
            transition: all 0.3s ease;
            display: block;
            text-align: center;
        }
        
        .ka-nav-item-white:hover {
            background-color: rgba(0,0,0,0.05);
            color: #333;
        }
        
        .ka-nav-item-white.special {
            color: #ff4757 !important;
            font-weight: 600;
        }
        
        .ka-nav-item-white.special:hover {
            color: #ff6b7d !important;
        }

        /* Image Carousel Styles - Like Kick Avenue */
        .carousel-container {
            position: relative;
            width: 100%;
            height: 250px;
            overflow: hidden;
            margin: 0;
            border-radius: 0;
        }

        .carousel-slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 0.8s ease-in-out;
        }

        .carousel-slide.active {
            opacity: 1;
        }

        .carousel-slide img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        /* Navigation Arrows */
        .carousel-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.8);
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: #333;
            transition: all 0.3s ease;
            z-index: 10;
        }

        .carousel-nav:hover {
            background: rgba(255, 255, 255, 0.95);
            transform: translateY(-50%) scale(1.1);
        }

        .carousel-nav.prev {
            left: 20px;
        }

        .carousel-nav.next {
            right: 20px;
        }

        /* Dots Indicator */
        .carousel-dots {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
            z-index: 10;
        }

        .carousel-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .carousel-dot.active {
            background: rgba(255, 255, 255, 1);
            transform: scale(1.2);
        }

        /* Auto-play pause on hover */
        .carousel-container:hover .carousel-slide {
            animation-play-state: paused;
        }
        
        /* Footer styles exactly like Kick Avenue */
        .nav-footer {
            background-color: #333333;
            color: #ffffff;
            padding: 40px 0;
            position: relative;
            overflow: hidden;
        }
        
        .kick-logo {
            font-family: 'Arial Black', sans-serif;
            font-weight: 900;
            font-size: 32px;
            color: #ffffff;
            font-style: italic;
            transform: skew(-10deg);
            display: inline-block;
            letter-spacing: -2px;
        }
        
        .kick-option {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        
        .kick-option a {
            color: #cccccc;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }
        
        .kick-option a:hover {
            color: #ffffff;
        }
        
        .kick-social-child {
            display: flex;
            gap: 16px;
            margin-top: 16px;
        }
        
        .kick-social-child a {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #cccccc;
            transition: all 0.3s ease;
        }
        
        .kick-social-child a:hover {
            background-color: rgba(255,255,255,0.2);
            color: #ffffff;
        }
        
        .kick-app-img {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .kick-app-img img {
            height: 40px;
            width: auto;
            cursor: pointer;
        }
        
        .kick-download {
            margin-bottom: 16px;
        }
        
        .kick-download h2 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 16px;
            color: #ffffff;
        }
        
        .registered {
            background-color: #1a1a1a;
            padding: 20px 0;
            text-align: center;
            margin-top: 40px;
        }
        
        .registered h4 {
            color: #999999;
            font-size: 12px;
            font-weight: 400;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Footer Column Dividers */
        .footer-column-divider {
            width: 1px;
            background-color: #555555;
            height: 120px;
            margin: 0 20px;
            flex-shrink: 0;
        }

        /* Mobile Slide Menu Styles */
        .mobile-menu {
            position: fixed;
            top: 0;
            left: -100%;
            width: 300px;
            height: 100vh;
            background: white;
            z-index: 9999;
            transition: left 0.3s ease;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .mobile-menu.open {
            left: 0;
        }

        .mobile-menu-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            background: rgba(0,0,0,0.5);
            z-index: 9998;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .mobile-menu-overlay.open {
            opacity: 1;
            visibility: visible;
        }

        .mobile-menu-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            text-align: center;
        }

        .mobile-menu-item {
            display: block;
            padding: 15px 20px;
            color: #333;
            text-decoration: none;
            border-bottom: 1px solid #f0f0f0;
            font-weight: 500;
            transition: background 0.3s ease;
        }

        .mobile-menu-item:hover {
            background: #f8f9fa;
        }

        .mobile-menu-item.special {
            color: #ff4757;
            font-weight: 600;
        }

        .mobile-auth-buttons {
            padding: 20px;
            border-top: 1px solid #eee;
        }

        .mobile-auth-btn {
            display: block;
            width: 100%;
            padding: 12px;
            margin-bottom: 10px;
            text-align: center;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .mobile-login-btn {
            background: white;
            border: 2px solid #ddd;
            color: #666;
        }

        .mobile-register-btn {
            background: #333;
            border: 2px solid #333;
            color: white;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .carousel-container {
                height: 180px;
            }
            
            .carousel-nav {
                width: 40px;
                height: 40px;
                font-size: 16px;
            }
            
            .carousel-nav.prev {
                left: 10px;
            }
            
            .carousel-nav.next {
                right: 10px;
            }

            .footer-column-divider {
                display: none;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header dengan navigation menu menyatu - background PUTIH -->
    <header class="ka-header sticky top-0 z-50" x-data="{ mobileMenuOpen: false }">
        <!-- Mobile Menu Overlay -->
        <div class="mobile-menu-overlay" :class="{ 'open': mobileMenuOpen }" @click="mobileMenuOpen = false"></div>
        
        <!-- Mobile Slide Menu -->
        <div class="mobile-menu" :class="{ 'open': mobileMenuOpen }">
            <!-- Menu Header -->
            <div class="mobile-menu-header">
                <div class="ka-logo text-black">SNEAKERFLASH</div>
            </div>
            
            <!-- Menu Items -->
            <div class="mobile-menu-content">
                <a href="/blind-box" class="mobile-menu-item special">Blind Box</a>
                <a href="{{ route('products.index') }}" class="mobile-menu-item">SNEAKERS</a>
                <a href="/apparel" class="mobile-menu-item">APPAREL</a>
                <a href="/k-brands" class="mobile-menu-item">K-BRANDS</a>
                <a href="/luxury" class="mobile-menu-item">LUXURY</a>
                <a href="/electronics-collectibles" class="mobile-menu-item">ELECTRONICS & COLLECTIBLES</a>
                <a href="/watches" class="mobile-menu-item">WATCHES</a>
            </div>
            
            <!-- Auth Buttons -->
            <div class="mobile-auth-buttons">
                @auth
                    <a href="/orders" class="mobile-menu-item">
                        <i class="fas fa-shopping-bag mr-2"></i>My Orders
                    </a>
                    <a href="{{ route('cart.index') }}" class="mobile-menu-item">
                        <i class="fas fa-shopping-cart mr-2"></i>Cart
                    </a>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="mobile-menu-item w-full text-left">
                            <i class="fas fa-sign-out-alt mr-2"></i>Logout
                        </button>
                    </form>
                @else
                    <a href="/login" class="mobile-auth-btn mobile-login-btn">Login</a>
                    <a href="/register" class="mobile-auth-btn mobile-register-btn">Register</a>
                @endauth
            </div>
        </div>
        <!-- Baris pertama: Logo, Search, Login/Register -->
        <div class="max-w-full px-4">
            <div class="flex items-center justify-between h-16">
                <!-- Logo SNEAKERFLASH pojok kiri -->
                <div class="flex items-center pl-5">
                    <a href="{{ route('home') }}" class="ka-logo">
                        SNEAKERFLASH
                    </a>
                </div>

                <!-- Search Bar tengah dengan background abu-abu - 1500px -->
                <div class="hidden md:flex flex-1 ka-search-custom mx-2">
                    <form action="{{ route('products.index') }}" method="GET" class="w-full">
                        <div class="ka-search-container">
                            <div class="relative flex items-center">
                                <i class="fas fa-search ka-search-icon"></i>
                                <input type="text" 
                                       name="search" 
                                       placeholder="Type any products here"
                                       value="{{ request('search') }}"
                                       class="ka-search-input flex-1">
                                <button type="submit" class="ka-search-btn">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Login Register pojok kanan -->
                <div class="hidden md:flex items-center space-x-3 pr-2">
                    @auth
                        <!-- User Dropdown -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center space-x-2 text-gray-600 hover:text-gray-800 transition-colors">
                                <div class="w-8 h-8 bg-gray-600 rounded-full flex items-center justify-center">
                                    <i class="fas fa-user text-white text-sm"></i>
                                </div>
                                <span class="font-medium text-gray-600">{{ auth()->user()->name }}</span>
                                <i class="fas fa-chevron-down text-sm text-gray-600"></i>
                            </button>

                            <!-- Dropdown Menu -->
                            <div x-show="open" @click.away="open = false" 
                                 class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                                <a href="/orders" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-shopping-bag mr-2"></i>My Orders
                                </a>
                                <a href="{{ route('cart.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-shopping-cart mr-2"></i>Cart
                                </a>
                                <hr class="my-1">
                                <form action="{{ route('logout') }}" method="POST" class="block">
                                    @csrf
                                    <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        <!-- Login/Register untuk guest -->
                        <a href="/login" class="ka-auth-btn ka-login-btn">
                            Login
                        </a>
                        <a href="/register" class="ka-auth-btn ka-register-btn">
                            Register
                        </a>
                    @endauth
                </div>

                <!-- Mobile Menu Button -->
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden text-gray-600 hover:text-gray-800">
                    <i class="fas fa-bars text-xl"></i>
                </button>
            </div>
        </div>

        <!-- Baris kedua: Navigation Menu (menyatu dengan header putih tanpa border) -->
        <div class="max-w-full px-4">
            <!-- Desktop Navigation -->
            <div class="hidden md:flex items-center justify-center py-0">
                <a href="/blind-box" class="ka-nav-item-white special">
                    Blind Box
                </a>
                <a href="/k-brands" class="ka-nav-item-white">
                    K-Brands
                </a>
                <a href="{{ route('products.index') }}" class="ka-nav-item-white">
                    Sneakers
                </a>
                <a href="/apparel" class="ka-nav-item-white">
                    Apparel
                </a>
                <a href="/luxury" class="ka-nav-item-white">
                    Luxury
                </a>
                <a href="/electronics-collectibles" class="ka-nav-item-white">
                    Electronics & Collectibles
                </a>
                <a href="/watches" class="ka-nav-item-white">
                    Watches
                </a>
            </div>

            <!-- Mobile Navigation Menu - Horizontal Scrollable (Remove this section) -->
            <!-- Navigation now handled by slide menu -->
        </div>
    </header>

    <!-- Mobile Search (tampil di mobile saja) -->
    <div class="md:hidden bg-white border-b px-4 py-3">
        <form action="{{ route('products.index') }}" method="GET">
            <div class="ka-search-container">
                <div class="relative flex items-center">
                    <i class="fas fa-search ka-search-icon"></i>
                    <input type="text" 
                           name="search" 
                           placeholder="Type any products here"
                           value="{{ request('search') }}"
                           class="ka-search-input flex-1">
                    <button type="submit" class="ka-search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Image Carousel Slider (Replacing Hero Section) -->
    <div class="carousel-container" x-data="carousel()">
        <!-- Carousel Slides -->
        <template x-for="(slide, index) in slides" :key="index">
            <div class="carousel-slide" :class="{ 'active': currentSlide === index }">
                <img :src="slide.image" :alt="slide.alt" />
            </div>
        </template>

        <!-- Navigation Arrows -->
        <button class="carousel-nav prev" @click="prevSlide()">
            <i class="fas fa-chevron-left"></i>
        </button>
        <button class="carousel-nav next" @click="nextSlide()">
            <i class="fas fa-chevron-right"></i>
        </button>

        <!-- Dots Indicator -->
        <div class="carousel-dots">
            <template x-for="(slide, index) in slides" :key="index">
                <span class="carousel-dot" 
                      :class="{ 'active': currentSlide === index }"
                      @click="goToSlide(index)"></span>
            </template>
        </div>
    </div>

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3" x-data="{ show: true }" x-show="show">
            <div class="container mx-auto flex justify-between items-center">
                <span><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</span>
                <button @click="show = false" class="text-green-700 hover:text-green-900">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3" x-data="{ show: true }" x-show="show">
            <div class="container mx-auto flex justify-between items-center">
                <span><i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}</span>
                <button @click="show = false" class="text-red-700 hover:text-red-900">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    @endif

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer with 6 Equal Columns in 1 Row -->
    <div class="nav-footer">
        <div class="container mx-auto px-6">
            <div class="hidden md:flex items-start justify-between">
                <!-- Column 1: Logo -->
                <div class="flex-1 max-w-xs">
                    <div class="mb-4">
                        <img src="/images/logo-sneakerflash.jpg" 
                             alt="SNEAKERFLASH" 
                             style="height: 40px; width: auto; filter: brightness(0) invert(1);">
                    </div>
                    <div class="mt-4">
                        <p class="text-white font-bold text-lg mb-1">200% Money Back Guarantee</p>
                        <p class="text-gray-400">Authentic. Guaranteed.</p>
                    </div>
                </div>
                
                <!-- Divider 1 -->
                <div class="footer-column-divider"></div>
                
                <!-- Column 2: FAQ Links -->
                <div class="flex-1 max-w-xs">
                    <div class="kick-option">
                        <a href="/helps/faq">FAQ</a>
                        <a href="/helps/terms-and-conditions">Terms and Conditions</a>
                        <a href="/helps/general-faq">Buying & Selling Guide</a>
                        <a href="/kickavenews">SneakerFlash News</a>
                    </div>
                </div>
                
                <!-- Divider 2 -->
                <div class="footer-column-divider"></div>
                
                <!-- Column 3: Instagram Links -->
                <div class="flex-1 max-w-xs">
                    <h2 class="text-white font-semibold text-base mb-3">Explore us more on Instagram!</h2>
                    <div class="space-y-2">
                        <a href="https://www.instagram.com/sneakerflash/" target="_blank" class="text-gray-300 hover:text-white text-sm flex items-center">
                            <i class="fab fa-instagram mr-2"></i>
                            SneakerFlash
                        </a>
                        <a href="https://www.instagram.com/luxavenue_id/" target="_blank" class="text-gray-300 hover:text-white text-sm flex items-center">
                            <i class="fab fa-instagram mr-2"></i>
                            LuxAvenue
                        </a>
                    </div>
                </div>
                
                <!-- Divider 3 -->
                <div class="footer-column-divider"></div>
                
                <!-- Column 4: Keep in Touch Social -->
                <div class="flex-1 max-w-xs">
                    <h2 class="text-white font-semibold text-base mb-3">Keep in touch with us!</h2>
                    <div class="flex gap-3">
                        <a href="https://www.instagram.com/sneakerflash/" target="_blank" class="w-10 h-10 bg-gray-600 rounded-full flex items-center justify-center text-white hover:bg-gray-500">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="https://www.youtube.com/channel/UCchannel" target="_blank" class="w-10 h-10 bg-gray-600 rounded-full flex items-center justify-center text-white hover:bg-gray-500">
                            <i class="fab fa-youtube"></i>
                        </a>
                        <a href="https://www.facebook.com/sneakerflash" target="_blank" class="w-10 h-10 bg-gray-600 rounded-full flex items-center justify-center text-white hover:bg-gray-500">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="https://twitter.com/sneakerflash" target="_blank" class="w-10 h-10 bg-gray-600 rounded-full flex items-center justify-center text-white hover:bg-gray-500">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="https://www.tiktok.com/@sneakerflash" target="_blank" class="w-10 h-10 bg-gray-600 rounded-full flex items-center justify-center text-white hover:bg-gray-500">
                            <i class="fab fa-tiktok"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Divider 4 -->
                <div class="footer-column-divider"></div>
                
                <!-- Column 5: Phone Image -->
                <div class="flex-1 max-w-xs flex justify-center">
                    <img src="/images/footer-phone-display.png" 
                         alt="SneakerFlash App" 
                         style="width: 160px; height: auto; object-fit: contain;">
                </div>
                
                <!-- Divider 5 -->
                <div class="footer-column-divider"></div>
                
                <!-- Column 6: Download App -->
                <div class="flex-1 max-w-xs">
                    <div class="kick-download">
                        <h2>Download Our App</h2>
                        <div class="kick-app-img">
                            <a href="https://apps.apple.com/app/sneakerflash" target="_blank">
                                <img src="/images/footer-app-download.9bd3e5ea.png" 
                                     alt="Download on App Store" 
                                     style="margin-bottom: 10px; cursor: pointer; height: 40px; width: auto;">
                            </a>
                            <a href="https://play.google.com/store/apps/details?id=com.sneakerflash" target="_blank">
                                <img src="/images/footer-google-play.5b128317.png" 
                                     alt="Get it on Google Play" 
                                     style="display: block; cursor: pointer; height: 40px; width: auto;">
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mobile Footer (Stack Vertically) -->
            <div class="md:hidden space-y-8">
                <div>
                    <img src="/images/logo-sneakerflash.jpg" 
                         alt="SNEAKERFLASH" 
                         style="height: 40px; width: auto; filter: brightness(0) invert(1);">
                    <p class="text-white font-bold text-lg mb-1 mt-4">200% Money Back Guarantee</p>
                    <p class="text-gray-400">Authentic. Guaranteed.</p>
                </div>
                
                <div class="kick-option">
                    <a href="/helps/faq">FAQ</a>
                    <a href="/helps/terms-and-conditions">Terms and Conditions</a>
                    <a href="/helps/general-faq">Buying & Selling Guide</a>
                    <a href="/kickavenews">SneakerFlash News</a>
                </div>
                
                <div>
                    <h2 class="text-white font-semibold text-base mb-3">Explore us more on Instagram!</h2>
                    <div class="space-y-2">
                        <a href="https://www.instagram.com/sneakerflash/" target="_blank" class="text-gray-300 hover:text-white text-sm flex items-center">
                            <i class="fab fa-instagram mr-2"></i>
                            SneakerFlash
                        </a>
                        <a href="https://www.instagram.com/luxavenue_id/" target="_blank" class="text-gray-300 hover:text-white text-sm flex items-center">
                            <i class="fab fa-instagram mr-2"></i>
                            LuxAvenue
                        </a>
                    </div>
                </div>
                
                <div>
                    <h2 class="text-white font-semibold text-base mb-3">Keep in touch with us!</h2>
                    <div class="flex gap-3">
                        <a href="https://www.instagram.com/sneakerflash/" target="_blank" class="w-10 h-10 bg-gray-600 rounded-full flex items-center justify-center text-white">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="https://www.youtube.com/channel/UCchannel" target="_blank" class="w-10 h-10 bg-gray-600 rounded-full flex items-center justify-center text-white">
                            <i class="fab fa-youtube"></i>
                        </a>
                        <a href="https://www.facebook.com/sneakerflash" target="_blank" class="w-10 h-10 bg-gray-600 rounded-full flex items-center justify-center text-white">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="https://twitter.com/sneakerflash" target="_blank" class="w-10 h-10 bg-gray-600 rounded-full flex items-center justify-center text-white">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="https://www.tiktok.com/@sneakerflash" target="_blank" class="w-10 h-10 bg-gray-600 rounded-full flex items-center justify-center text-white">
                            <i class="fab fa-tiktok"></i>
                        </a>
                    </div>
                </div>
                
                <div class="flex justify-center">
                    <img src="/images/footer-phone-display.png" 
                         alt="SneakerFlash App" 
                         style="width: 120px; height: auto; object-fit: contain;">
                </div>
                
                <div>
                    <h2 class="text-white font-semibold text-base mb-3">Download Our App</h2>
                    <div class="kick-app-img">
                        <a href="https://apps.apple.com/app/sneakerflash" target="_blank">
                            <img src="/images/footer-app-download.9bd3e5ea.png" 
                                 alt="Download on App Store" 
                                 style="margin-bottom: 10px; cursor: pointer; height: 40px; width: auto;">
                        </a>
                        <a href="https://play.google.com/store/apps/details?id=com.sneakerflash" target="_blank">
                            <img src="/images/footer-google-play.5b128317.png" 
                                 alt="Get it on Google Play" 
                                 style="display: block; cursor: pointer; height: 40px; width: auto;">
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Registered section -->
        <div class="registered">
            <h4>
                <span>REGISTERED UNDER </span>
                <span>PT. KARUNIA INTERNASIONAL CITRA KENCANA</span>
            </h4>
        </div>
    </div>

    <!-- WhatsApp Float Button -->
    <div class="fixed bottom-6 right-6 z-50">
        <a href="https://wa.me/1234567890" target="_blank" 
           class="w-14 h-14 bg-green-500 rounded-full flex items-center justify-center text-white shadow-lg hover:bg-green-600 transition-all duration-300 hover:scale-110 transform">
            <i class="fab fa-whatsapp text-2xl"></i>
        </a>
    </div>

    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Carousel JavaScript -->
    <script>
        function carousel() {
            return {
                currentSlide: 0,
                slides: [
                    {
                        image: '/images/slide1.jpg',
                        alt: 'SneakerFlash Promotion 1'
                    },
                    {
                        image: '/images/slide2.jpg',
                        alt: 'SneakerFlash Promotion 2'
                    },
                    {
                        image: '/images/slide3.jpg',
                        alt: 'SneakerFlash Promotion 3'
                    },
                    {
                        image: '/images/slide4.jpg',
                        alt: 'SneakerFlash Promotion 4'
                    },
                    {
                        image: '/images/slide5.jpg',
                        alt: 'SneakerFlash Promotion 5'
                    }
                ],
                autoplayInterval: null,

                init() {
                    this.startAutoplay();
                },

                startAutoplay() {
                    this.autoplayInterval = setInterval(() => {
                        this.nextSlide();
                    }, 5000);
                },

                stopAutoplay() {
                    if (this.autoplayInterval) {
                        clearInterval(this.autoplayInterval);
                    }
                },

                nextSlide() {
                    this.currentSlide = (this.currentSlide + 1) % this.slides.length;
                },

                prevSlide() {
                    this.currentSlide = this.currentSlide === 0 ? this.slides.length - 1 : this.currentSlide - 1;
                },

                goToSlide(index) {
                    this.currentSlide = index;
                    this.stopAutoplay();
                    setTimeout(() => this.startAutoplay(), 3000);
                }
            };
        }
    </script>
    
    @stack('scripts')
</body>
</html>