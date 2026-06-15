@extends('layouts.app')

@section('title', $product->name . ' - Product Details')

@section('content')
<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('shop.index') }}">Shop</a></li>
            <li class="breadcrumb-item active">{{ $product->name }}</li>
        </ol>
    </nav>

    <div class="row">
        <!-- Product Image -->
        <div class="col-md-5 mb-4">
            <div class="card shadow">
                <div class="card-body text-center">
                    @if($product->image_url)
                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="img-fluid rounded" style="max-height: 400px;">
                    @else
                        <div class="bg-light rounded d-flex align-items-center justify-content-center" style="height: 400px;">
                            <i class="bi bi-box-seam" style="font-size: 100px; color: #ddd;"></i>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Product Details -->
        <div class="col-md-7">
            <div class="card shadow">
                <div class="card-body">
                    <h1 class="h3 mb-3">{{ $product->name }}</h1>
                    
                    @if($product->sku)
                        <p class="text-muted mb-3">SKU: {{ $product->sku }}</p>
                    @endif

                    <div class="mb-4">
                        <h2 class="h4 text-primary mb-0">
                            GH₵ {{ number_format($product->price, 2) }}
                        </h2>
                    </div>

                    <!-- Stock Status -->
                    <div class="mb-4">
                        @if($product->stock_quantity > 0)
                            <span class="badge badge-success">
                                <i class="bi bi-check-circle"></i> In Stock ({{ $product->stock_quantity }} available)
                            </span>
                        @else
                            <span class="badge badge-danger">
                                <i class="bi bi-x-circle"></i> Out of Stock
                            </span>
                        @endif
                    </div>

                    <!-- Category -->
                    <div class="mb-3">
                        <strong>Category:</strong> 
                        <span class="badge badge-info">{{ $product->category ?? 'N/A' }}</span>
                    </div>

                    <!-- Description -->
                    @if($product->description)
                    <div class="mb-4">
                        <h6><strong>Description:</strong></h6>
                        <p>{{ $product->description }}</p>
                    </div>
                    @endif

                    <!-- Prescription Required -->
                    @if($product->prescription_required)
                    <div class="alert alert-warning mb-4">
                        <i class="bi bi-exclamation-triangle"></i> <strong>Prescription Required:</strong> This item requires a valid prescription.
                    </div>
                    @endif

                    <!-- Dosage Instructions -->
                    @if($product->dosage_instructions)
                    <div class="mb-3">
                        <h6><strong>Dosage Instructions:</strong></h6>
                        <p class="text-muted">{{ $product->dosage_instructions }}</p>
                    </div>
                    @endif

                    <!-- Add to Cart Form -->
                    @if($product->stock_quantity > 0 && $product->is_active && $product->is_available)
                    <form action="{{ route('shop.cart.add') }}" method="POST" class="mb-3">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        
                        <div class="form-group">
                            <label for="quantity">Quantity:</label>
                            <div class="input-group" style="max-width: 200px;">
                                <button type="button" class="btn btn-outline-secondary" onclick="decrementQty()">
                                    <i class="bi bi-dash"></i>
                                </button>
                                <input type="number" 
                                       id="quantity" 
                                       name="quantity" 
                                       class="form-control text-center" 
                                       value="1" 
                                       min="1" 
                                       max="{{ $product->stock_quantity }}"
                                       required>
                                <button type="button" class="btn btn-outline-secondary" onclick="incrementQty()">
                                    <i class="bi bi-plus"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-cart-plus"></i> Add to Cart
                            </button>
                            <a href="{{ route('shop.index') }}" class="btn btn-outline-secondary btn-lg">
                                <i class="bi bi-arrow-left"></i> Continue Shopping
                            </a>
                        </div>
                    </form>
                    @else
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> This product is currently unavailable.
                    </div>
                    <a href="{{ route('shop.index') }}" class="btn btn-outline-secondary btn-lg">
                        <i class="bi bi-arrow-left"></i> Back to Shop
                    </a>
                    @endif

                    <!-- Additional Product Info -->
                    @if($product->manufacturer)
                    <div class="mt-4 pt-4 border-top">
                        <p><strong>Manufacturer:</strong> {{ $product->manufacturer }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Side Effects & Contraindications -->
            @if($product->side_effects || $product->contraindications)
            <div class="card shadow mt-4">
                <div class="card-header bg-warning text-white">
                    <h6 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Important Information</h6>
                </div>
                <div class="card-body">
                    @if($product->side_effects)
                    <div class="mb-3">
                        <h6><strong>Possible Side Effects:</strong></h6>
                        <p class="text-muted">{{ $product->side_effects }}</p>
                    </div>
                    @endif

                    @if($product->contraindications)
                    <div>
                        <h6><strong>Contraindications:</strong></h6>
                        <p class="text-muted">{{ $product->contraindications }}</p>
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Related Products -->
    @if($relatedProducts->count() > 0)
    <div class="mt-5">
        <h4 class="mb-4">Related Products</h4>
        <div class="row">
            @foreach($relatedProducts as $related)
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card shadow h-100">
                    <div class="card-body text-center">
                        @if($related->image_url)
                            <img src="{{ $related->image_url }}" alt="{{ $related->name }}" class="img-fluid mb-3" style="max-height: 150px;">
                        @else
                            <div class="bg-light rounded mb-3 d-flex align-items-center justify-content-center" style="height: 150px;">
                                <i class="bi bi-box-seam" style="font-size: 50px; color: #ddd;"></i>
                            </div>
                        @endif
                        
                        <h6 class="card-title">{{ Str::limit($related->name, 50) }}</h6>
                        <p class="text-primary font-weight-bold">GH₵ {{ number_format($related->price, 2) }}</p>
                        
                        @if($related->stock_quantity > 0)
                            <span class="badge badge-success mb-2">In Stock</span>
                        @else
                            <span class="badge badge-danger mb-2">Out of Stock</span>
                        @endif
                        
                        <div class="mt-3">
                            <a href="{{ route('shop.show', $related->id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i> View Details
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
function incrementQty() {
    const input = document.getElementById('quantity');
    const max = parseInt(input.max);
    const current = parseInt(input.value);
    if (current < max) {
        input.value = current + 1;
    }
}

function decrementQty() {
    const input = document.getElementById('quantity');
    const current = parseInt(input.value);
    if (current > 1) {
        input.value = current - 1;
    }
}
</script>
@endpush
@endsection

