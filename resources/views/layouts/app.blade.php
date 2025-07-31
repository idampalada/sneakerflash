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
    
    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
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
            background: #f8f9fa;
        }

        .carousel-slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
        }

        .carousel-slide.active {
            opacity: 1;
        }

        .carousel-slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .carousel-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.9);
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 18px;
            color: #333;
            transition: all 0.3s ease;
            z-index: 10;
        }

        .carousel-nav:hover {
            background: rgba(255, 255, 255, 1);
            transform: translateY(-50%) scale(1.1);
        }

        .carousel-nav.prev {
            left: 20px;
        }

        .carousel-nav.next {
            right: 20px;
        }

        /* Mobile Styles */
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

    <script>
        function carousel() {
            return {
                currentSlide: 0,
                slides: [
                    { image: 'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=1200&h=250&fit=crop', alt: 'Sneaker Collection 1' },
                    { image: 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=1200&h=250&fit=crop', alt: 'Sneaker Collection 2' },
                    { image: 'https://images.unsplash.com/photo-1600185365483-26d7a4cc7519?w=1200&h=250&fit=crop', alt: 'Sneaker Collection 3' },
                    { image: 'https://images.unsplash.com/photo-1512374382149-233c42b6a83b?w=1200&h=250&fit=crop', alt: 'Sneaker Collection 4' }
                ],
                nextSlide() {
                    this.currentSlide = (this.currentSlide + 1) % this.slides.length;
                },
                prevSlide() {
                    this.currentSlide = this.currentSlide === 0 ? this.slides.length - 1 : this.currentSlide - 1;
                },
                init() {
                    setInterval(() => {
                        this.nextSlide();
                    }, 5000);
                }
            }
        }
    </script>
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
                <a href="{{ route('products.mens') }}" class="mobile-menu-item">MENS</a>
                <a href="{{ route('products.womens') }}" class="mobile-menu-item">WOMENS</a>
                <a href="{{ route('products.kids') }}" class="mobile-menu-item">KIDS</a>
                <a href="{{ route('products.brand') }}" class="mobile-menu-item">BRAND</a>
                <a href="{{ route('products.accessories') }}" class="mobile-menu-item">ACCESSORIES</a>
                <a href="{{ route('products.sale') }}" class="mobile-menu-item special">SALE</a>
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

        <!-- Baris pertama: Logo, Search, dan Auth -->
        <div class="max-w-full mx-auto px-4">
            <div class="flex items-center justify-between py-4">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="/" class="ka-logo">SNEAKERFLASH</a>
                </div>

                <!-- Search Bar - hanya tampil di desktop -->
                <div class="hidden md:flex flex-1 max-w-2xl mx-8">
                    <form action="{{ route('products.index') }}" method="GET" class="w-full ka-search-custom mx-auto">
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

                <!-- User Menu / Auth -->
                <div class="flex items-center space-x-4">
                    @auth
                        <!-- User Dropdown -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center space-x-2 text-gray-700 hover:text-gray-900">
                                <i class="fas fa-user-circle text-xl"></i>
                                <span class="hidden md:inline text-sm text-gray-600">{{ auth()->user()->name }}</span>
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
                <a href="{{ route('products.mens') }}" class="ka-nav-item-white">
                    MENS
                </a>
                <a href="{{ route('products.womens') }}" class="ka-nav-item-white">
                    WOMENS
                </a>
                <a href="{{ route('products.kids') }}" class="ka-nav-item-white">
                    KIDS
                </a>
                <a href="{{ route('products.brand') }}" class="ka-nav-item-white">
                    BRAND
                </a>
                <a href="{{ route('products.accessories') }}" class="ka-nav-item-white">
                    ACCESSORIES
                </a>
                <a href="{{ route('products.sale') }}" class="ka-nav-item-white special">
                    SALE
                </a>
            </div>
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
    </div>

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Company Info -->
                <div>
                    <div class="ka-logo text-white mb-4">SNEAKERFLASH</div>
                    <p class="text-gray-400 text-sm">
                        Premium sneakers and streetwear for everyone. Authentic products, fast delivery.
                    </p>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="font-semibold mb-4">Quick Links</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="{{ route('products.index') }}" class="text-gray-400 hover:text-white">All Products</a></li>
                        <li><a href="{{ route('products.sale') }}" class="text-gray-400 hover:text-white">Sale</a></li>
                        <li><a href="/about" class="text-gray-400 hover:text-white">About Us</a></li>
                        <li><a href="/contact" class="text-gray-400 hover:text-white">Contact</a></li>
                    </ul>
                </div>

                <!-- Customer Service -->
                <div>
                    <h4 class="font-semibold mb-4">Customer Service</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="/shipping-info" class="text-gray-400 hover:text-white">Shipping Info</a></li>
                        <li><a href="/returns" class="text-gray-400 hover:text-white">Returns</a></li>
                        <li><a href="/size-guide" class="text-gray-400 hover:text-white">Size Guide</a></li>
                        <li><a href="/terms" class="text-gray-400 hover:text-white">Terms & Conditions</a></li>
                    </ul>
                </div>

                <!-- Follow Us -->
                <div>
                    <h4 class="font-semibold mb-4">Follow Us</h4>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white">
                            <i class="fab fa-instagram text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white">
                            <i class="fab fa-tiktok text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white">
                            <i class="fab fa-facebook text-xl"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-800 mt-8 pt-8 text-center">
                <p class="text-gray-400 text-sm">
                    &copy; {{ date('Y') }} SneakerFlash. All rights reserved.
                </p>
            </div>
        </div>
    </footer>
</body>
</html>