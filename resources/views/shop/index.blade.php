<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Shop - {{ config('app.name', $hospitalBranding['name'] ?? 'Hospital') }}</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #0ea5e9;
            --primary-dark: #0284c7;
            --secondary-color: #8b5cf6;
            --accent-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --text-dark: #0f172a;
            --text-medium: #475569;
            --text-light: #94a3b8;
            --bg-light: #f8fafc;
            --bg-white: #ffffff;
            --border-color: #e2e8f0;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1);
        }
        
        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e0f2fe 100%);
            color: var(--text-dark);
            min-height: 100vh;
        }
        
        /* Navbar with Glass Effect */
        .shop-navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 1px 3px rgba(0,0,0,0.08), 0 4px 12px rgba(14, 165, 233, 0.1);
            padding: 1.25rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid rgba(14, 165, 233, 0.1);
        }
        
        .shop-navbar .brand {
            font-size: 1.75rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .shop-navbar .brand:hover {
            transform: scale(1.05);
        }
        
        .shop-navbar .brand i {
            margin-right: 0.5rem;
            -webkit-text-fill-color: var(--primary-color);
        }
        
        .cart-btn {
            position: relative;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .cart-btn:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 8px 20px rgba(14, 165, 233, 0.4);
            background: linear-gradient(135deg, var(--primary-dark), var(--secondary-color));
        }
        
        .cart-btn:active {
            transform: translateY(-1px);
        }
        
        .cart-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        /* Hero Slider - Enhanced */
        .hero-slider {
            background: linear-gradient(135deg, #0ea5e9 0%, #8b5cf6 100%);
            border-radius: 20px;
            overflow: hidden;
            margin: 2.5rem 0;
            box-shadow: var(--shadow-xl);
            position: relative;
        }
        
        .hero-slider::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.1), rgba(139, 92, 246, 0.1));
            z-index: 1;
        }
        
        .carousel-item {
            height: 450px;
            position: relative;
        }
        
        .carousel-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0.25;
        }
        
        .carousel-caption {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            width: 100%;
            z-index: 2;
        }
        
        .carousel-caption h2 {
            font-size: 3.5rem;
            font-weight: 800;
            color: white;
            margin-bottom: 1.5rem;
            text-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            animation: fadeInUp 0.8s ease-out;
        }
        
        .carousel-caption p {
            font-size: 1.35rem;
            color: rgba(255, 255, 255, 0.95);
            margin-bottom: 2.5rem;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            animation: fadeInUp 0.8s ease-out 0.2s both;
        }
        
        .carousel-caption .btn {
            background: white;
            color: var(--primary-color);
            padding: 1rem 3rem;
            border-radius: 50px;
            font-weight: 700;
            text-decoration: none;
            display: inline-block;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 16px rgba(255, 255, 255, 0.3);
            animation: fadeInUp 0.8s ease-out 0.4s both;
        }
        
        .carousel-caption .btn:hover {
            transform: translateY(-4px) scale(1.05);
            box-shadow: 0 12px 24px rgba(255, 255, 255, 0.4);
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Filters - Modern Design */
        .filters-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 2rem;
            border-radius: 16px;
            box-shadow: var(--shadow-lg);
            margin-bottom: 3rem;
            border: 1px solid rgba(14, 165, 233, 0.1);
        }
        
        .filter-btn {
            background: linear-gradient(135deg, var(--bg-light), var(--bg-white));
            border: 2px solid var(--border-color);
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 500;
        }
        
        .filter-btn:hover, .filter-btn:focus {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
        }
        
        /* Product Cards - Stunning Design */
        .product-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            height: 100%;
            display: flex;
            flex-direction: column;
            border: 1px solid transparent;
            position: relative;
        }
        
        .product-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border-radius: 16px;
            padding: 2px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            opacity: 0;
            transition: opacity 0.4s;
        }
        
        .product-card:hover {
            transform: translateY(-12px);
            box-shadow: 0 20px 40px rgba(14, 165, 233, 0.2);
        }
        
        .product-card:hover::before {
            opacity: 1;
        }
        
        .product-image {
            width: 100%;
            height: 280px;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }
        
        .product-image::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.5s;
        }
        
        .product-card:hover .product-image::after {
            left: 100%;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }
        
        .product-card:hover .product-image img {
            transform: scale(1.1);
        }
        
        .product-image i {
            font-size: 5rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            opacity: 0.6;
        }
        
        .product-body {
            padding: 1.5rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        
        .product-category {
            display: inline-block;
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.1), rgba(139, 92, 246, 0.1));
            color: var(--primary-color);
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 0.35rem 0.75rem;
            border-radius: 6px;
            margin-bottom: 0.75rem;
            width: fit-content;
        }
        
        .product-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.75rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.4;
        }
        
        .product-description {
            color: var(--text-medium);
            font-size: 0.9rem;
            margin-bottom: 1.25rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            flex-grow: 1;
            line-height: 1.6;
        }
        
        .product-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 1.25rem;
            border-top: 2px solid var(--bg-light);
            margin-top: auto;
        }
        
        .product-price {
            font-size: 1.75rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .product-price small {
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-light);
            -webkit-text-fill-color: var(--text-light);
        }
        
        .btn-add-cart {
            background: linear-gradient(135deg, var(--accent-color), #059669);
            color: white;
            border: none;
            padding: 0.65rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-add-cart:hover {
            background: linear-gradient(135deg, #059669, #047857);
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
        }
        
        .btn-add-cart:active {
            transform: translateY(0);
        }
        
        .btn-add-cart:disabled {
            background: linear-gradient(135deg, var(--text-light), #9ca3af);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .stock-badge {
            position: absolute;
            top: 1.25rem;
            right: 1.25rem;
            background: linear-gradient(135deg, var(--accent-color), #059669);
            color: white;
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.4);
            z-index: 10;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .out-of-stock {
            background: linear-gradient(135deg, var(--danger-color), #dc2626);
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
        }
        
        /* Search Bar - Enhanced */
        .search-bar {
            position: relative;
        }
        
        .search-bar input {
            padding-left: 3rem;
            padding-right: 1rem;
            border-radius: 50px;
            border: 2px solid var(--border-color);
            transition: all 0.3s;
            background: var(--bg-light);
        }
        
        .search-bar input:focus {
            border-color: var(--primary-color);
            background: white;
            box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.1);
            outline: none;
        }
        
        .search-bar i {
            position: absolute;
            left: 1.25rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-color);
            font-size: 1.1rem;
        }
        
        .form-select, .form-control {
            border: 2px solid var(--border-color);
            border-radius: 12px;
            transition: all 0.3s;
            background: var(--bg-light);
        }
        
        .form-select:focus, .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.1);
            background: white;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border: none;
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(14, 165, 233, 0.4);
        }
        
        /* Pagination - Modern */
        .pagination {
            margin-top: 4rem;
            gap: 0.5rem;
        }
        
        .page-link {
            color: var(--primary-color);
            border-radius: 12px;
            margin: 0 0.25rem;
            border: 2px solid var(--border-color);
            padding: 0.5rem 1rem;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .page-link:hover {
            background: var(--bg-light);
            border-color: var(--primary-color);
            transform: translateY(-2px);
        }
        
        .page-item.active .page-link {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border-color: var(--primary-color);
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 5rem 2rem;
        }
        
        .empty-state i {
            font-size: 6rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            opacity: 0.5;
        }
        
        .empty-state h3 {
            color: var(--text-dark);
            margin: 1.5rem 0 0.5rem;
            font-weight: 700;
        }
        
        .empty-state p {
            color: var(--text-medium);
            margin-bottom: 2rem;
        }
        
        /* Loading Animation */
        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
        }
        
        .loading-card {
            background: linear-gradient(90deg, #f0f0f0 0%, #f8f8f8 50%, #f0f0f0 100%);
            background-size: 1000px 100%;
            animation: shimmer 2s infinite;
        }
        
        /* Section Headers */
        .section-header {
            text-align: center;
            margin: 3rem 0 2rem;
        }
        
        .section-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.75rem;
        }
        
        .gradient-text {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .section-subtitle {
            color: var(--text-medium);
            font-size: 1.1rem;
            margin-bottom: 0;
        }
        
        /* Enhanced Buttons */
        .btn-outline-primary {
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            border-radius: 12px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-outline-primary:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
        }
        
        /* Footer Enhancement */
        .bg-dark {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%) !important;
            box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.1);
        }
        
        /* Toast Notifications */
        .toast-success {
            background: linear-gradient(135deg, var(--accent-color), #059669);
            color: white;
            border-radius: 12px;
            padding: 1rem 1.5rem;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .carousel-caption h2 {
                font-size: 2.25rem;
            }
            
            .carousel-caption p {
                font-size: 1.1rem;
            }
            
            .carousel-item {
                height: 350px;
            }
            
            .product-image {
                height: 220px;
            }
            
            .section-title {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="shop-navbar">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <a href="{{ route('shop.index') }}" class="brand">
                    <i class="bi bi-shop"></i>
                    {{ config('app.name', $hospitalBranding['name'] ?? 'Hospital') }} Shop
                </a>
                
                <div class="d-flex align-items-center gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn btn-outline-primary">
                            <i class="bi bi-grid"></i> Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-outline-primary">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </a>
                    @endauth
                    
                    <a href="{{ route('shop.cart') }}" class="cart-btn">
                        <i class="bi bi-cart3"></i> Cart
                        @if($cartCount > 0)
                            <span class="cart-badge" id="cart-count">{{ $cartCount }}</span>
                        @endif
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Hero Slider -->
        <div id="heroSlider" class="carousel slide hero-slider" data-bs-ride="carousel">
            <div class="carousel-indicators">
                @foreach($featuredProducts as $index => $product)
                    <button type="button" data-bs-target="#heroSlider" data-bs-slide-to="{{ $index }}" class="{{ $index === 0 ? 'active' : '' }}"></button>
                @endforeach
            </div>
            
            <div class="carousel-inner">
                @foreach($featuredProducts as $index => $product)
                    <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                        @if($product->image_url)
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}">
                        @else
                            <div style="width: 100%; height: 100%; background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);"></div>
                        @endif
                        <div class="carousel-caption">
                            <h2>{{ $product->name }}</h2>
                            <p>{{ Str::limit($product->description, 100) }}</p>
                            <a href="{{ route('shop.show', $product->id) }}" class="btn">Shop Now</a>
                        </div>
                    </div>
                @endforeach
            </div>
            
            <button class="carousel-control-prev" type="button" data-bs-target="#heroSlider" data-bs-slide="prev">
                <span class="carousel-control-prev-icon"></span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#heroSlider" data-bs-slide="next">
                <span class="carousel-control-next-icon"></span>
            </button>
        </div>

        <!-- Filters Section -->
        <div class="filters-section">
            <form method="GET" action="{{ route('shop.index') }}" id="filterForm">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Search Products</label>
                        <div class="search-bar">
                            <i class="bi bi-search"></i>
                            <input type="text" name="search" class="form-control" placeholder="Search medications, health products..." value="{{ request('search') }}">
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select">
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category }}" {{ request('category') == $category ? 'selected' : '' }}>
                                    {{ $category }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">Sort By</label>
                        <select name="sort" class="form-select">
                            <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>Latest</option>
                            <option value="price_low" {{ request('sort') == 'price_low' ? 'selected' : '' }}>Price: Low to High</option>
                            <option value="price_high" {{ request('sort') == 'price_high' ? 'selected' : '' }}>Price: High to Low</option>
                            <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>Name (A-Z)</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-funnel"></i> Apply Filters
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Section Header -->
        <div class="section-header mb-4">
            <h2 class="section-title">
                <span class="gradient-text">Featured Products</span>
            </h2>
            <p class="section-subtitle">Discover our wide range of quality healthcare products and medications</p>
        </div>

        <!-- Products Grid -->
        <div class="row g-4 mb-5">
            @forelse($products as $product)
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <div class="product-card">
                        <div class="product-image position-relative">
                            @if($product->image_url)
                                <img src="{{ $product->image_url }}" alt="{{ $product->name }}">
                            @else
                                <i class="bi bi-capsule"></i>
                            @endif
                            
                            @if($product->stock_quantity > 0)
                                <span class="stock-badge">In Stock</span>
                            @else
                                <span class="stock-badge out-of-stock">Out of Stock</span>
                            @endif
                        </div>
                        
                        <div class="product-body">
                            @if($product->category)
                                <div class="product-category">{{ $product->category }}</div>
                            @endif
                            
                            <h5 class="product-title">{{ $product->name }}</h5>
                            
                            <p class="product-description">
                                {{ Str::limit($product->description ?? 'Quality healthcare product', 80) }}
                            </p>
                            
                            <div class="product-footer">
                                <div class="product-price">
                                    GH₵{{ number_format($product->price, 2) }}
                                    @if($product->unit)
                                        <small>/{{ $product->unit }}</small>
                                    @endif
                                </div>
                                
                                <button 
                                    class="btn-add-cart add-to-cart-btn" 
                                    data-product-id="{{ $product->id }}"
                                    data-product-name="{{ $product->name }}"
                                    {{ $product->stock_quantity <= 0 ? 'disabled' : '' }}
                                >
                                    <i class="bi bi-cart-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="empty-state">
                        <div class="mb-4">
                            <i class="bi bi-basket"></i>
                        </div>
                        <h3>No Products Found</h3>
                        <p>Try adjusting your search or filter criteria to find what you're looking for</p>
                        <a href="{{ route('shop.index') }}" class="btn btn-primary">
                            <i class="bi bi-arrow-clockwise me-2"></i>Reset Filters
                        </a>
                    </div>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-center">
            {{ $products->links() }}
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5 class="fw-bold mb-3">
                        <i class="bi bi-shop me-2"></i>{{ config('app.name', $hospitalBranding['name'] ?? 'Hospital') }} Shop
                    </h5>
                    <p class="text-white-50">Your trusted source for quality healthcare products and medications.</p>
                </div>
                <div class="col-md-4 mb-4 mb-md-0">
                    <h6 class="fw-bold mb-3">Quick Links</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="{{ route('shop.index') }}" class="text-white-50 text-decoration-none"><i class="bi bi-chevron-right"></i> Shop</a></li>
                        @auth
                            <li class="mb-2"><a href="{{ route('shop.cart') }}" class="text-white-50 text-decoration-none"><i class="bi bi-chevron-right"></i> My Cart</a></li>
                            <li class="mb-2"><a href="{{ route('dashboard') }}" class="text-white-50 text-decoration-none"><i class="bi bi-chevron-right"></i> Dashboard</a></li>
                        @else
                            <li class="mb-2"><a href="{{ route('login') }}" class="text-white-50 text-decoration-none"><i class="bi bi-chevron-right"></i> Login</a></li>
                        @endauth
                    </ul>
                </div>
                <div class="col-md-4">
                    <h6 class="fw-bold mb-3">Contact</h6>
                    <p class="text-white-50 mb-2"><i class="bi bi-telephone me-2"></i> {{ $branding->business_phone ?? '+233 123 456 789' }}</p>
                    <p class="text-white-50 mb-2"><i class="bi bi-envelope me-2"></i> {{ $branding->business_email ?? 'shop@hospital.com' }}</p>
                    <p class="text-white-50 mb-0"><i class="bi bi-geo-alt me-2"></i> {{ $branding->business_address ?? 'Accra, Ghana' }}</p>
                </div>
            </div>
            <hr class="my-4 border-secondary">
            <div class="text-center">
                <p class="mb-0 text-white-50">&copy; {{ date('Y') }} {{ config('app.name', $hospitalBranding['name'] ?? 'Hospital') }}. All rights reserved. | Powered by Next Code Systems</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Add to Cart Script -->
    <script>
        // Add to Cart functionality
        document.querySelectorAll('.add-to-cart-btn').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.dataset.productId;
                const productName = this.dataset.productName;
                const btn = this;
                const originalHtml = btn.innerHTML;
                
                // Disable button and show loading
                btn.disabled = true;
                btn.innerHTML = '<i class="bi bi-hourglass-split"></i>';
                
                fetch('{{ route("shop.cart.add") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        store_item_id: productId,
                        quantity: 1
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update cart count
                        const cartBadge = document.getElementById('cart-count');
                        if (cartBadge) {
                            cartBadge.textContent = data.cartCount;
                        } else if (data.cartCount > 0) {
                            document.querySelector('.cart-btn').innerHTML += `<span class="cart-badge" id="cart-count">${data.cartCount}</span>`;
                        }
                        
                        // Show success message
                        showToast(`${productName} added to cart!`, 'success');
                        
                        // Reset button
                        btn.innerHTML = '<i class="bi bi-check-lg"></i>';
                        setTimeout(() => {
                            btn.innerHTML = originalHtml;
                            btn.disabled = false;
                        }, 1500);
                    } else {
                        showToast(data.message || 'Failed to add to cart', 'error');
                        btn.innerHTML = originalHtml;
                        btn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('An error occurred. Please try again.', 'error');
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                });
            });
        });
        
        // Enhanced toast notification with modern design
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = 'toast-notification';
            toast.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? 'linear-gradient(135deg, #10b981, #059669)' : 'linear-gradient(135deg, #ef4444, #dc2626)'};
                color: white;
                padding: 1rem 1.5rem;
                border-radius: 12px;
                box-shadow: 0 8px 24px rgba(0,0,0,0.2);
                z-index: 9999;
                display: flex;
                align-items: center;
                gap: 0.75rem;
                font-weight: 600;
                animation: slideInRight 0.4s ease-out;
            `;
            
            const icon = type === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill';
            toast.innerHTML = `<i class="bi bi-${icon}"></i> ${message}`;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.animation = 'slideOutRight 0.4s ease-out';
                setTimeout(() => toast.remove(), 400);
            }, 3000);
        }
        
        // Add animation styles for toast
        if (!document.getElementById('toast-animations')) {
            const style = document.createElement('style');
            style.id = 'toast-animations';
            style.textContent = `
                @keyframes slideInRight {
                    from { transform: translateX(400px); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                @keyframes slideOutRight {
                    from { transform: translateX(0); opacity: 1; }
                    to { transform: translateX(400px); opacity: 0; }
                }
            `;
            document.head.appendChild(style);
        }
        
        // Auto-submit form on select change
        document.querySelectorAll('#filterForm select').forEach(select => {
            select.addEventListener('change', () => {
                document.getElementById('filterForm').submit();
            });
        });
    </script>
</body>
</html>

