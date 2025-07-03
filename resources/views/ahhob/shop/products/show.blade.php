@extends('ahhob.layouts.app')

@section('title', $product->name)

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- 브레드크럼 -->
        <nav class="flex mb-8" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2">
                <li><a href="{{ route('ahhob.shop.products.index') }}" class="text-gray-500 hover:text-gray-700">상품</a></li>
                <li><span class="text-gray-400">/</span></li>
                <li><a href="{{ route('ahhob.shop.categories.show', $product->category->slug) }}" class="text-gray-500 hover:text-gray-700">{{ $product->category->name }}</a></li>
                <li><span class="text-gray-400">/</span></li>
                <li><span class="text-gray-900 font-medium">{{ $product->name }}</span></li>
            </ol>
        </nav>

        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="lg:grid lg:grid-cols-2 lg:gap-8">
                <!-- 상품 이미지 -->
                <div class="p-6">
                    <div class="aspect-w-1 aspect-h-1 bg-gray-200 rounded-lg overflow-hidden">
                        @if($product->featured_image)
                        <img src="{{ asset('storage/' . $product->featured_image) }}" 
                             alt="{{ $product->name }}"
                             class="w-full h-96 object-cover">
                        @else
                        <div class="w-full h-96 bg-gray-200 flex items-center justify-center">
                            <span class="text-gray-400 text-lg">이미지 없음</span>
                        </div>
                        @endif
                    </div>
                    
                    <!-- 갤러리 이미지 (있을 경우) -->
                    @if($product->gallery_images && count($product->gallery_images) > 0)
                    <div class="mt-4 grid grid-cols-4 gap-2">
                        @foreach($product->gallery_images as $image)
                        <img src="{{ asset('storage/' . $image) }}" 
                             alt="{{ $product->name }}"
                             class="w-full h-20 object-cover rounded cursor-pointer hover:opacity-75 transition-opacity">
                        @endforeach
                    </div>
                    @endif
                </div>

                <!-- 상품 정보 -->
                <div class="p-6">
                    <div class="mb-4">
                        <span class="inline-block px-3 py-1 text-sm bg-blue-100 text-blue-800 rounded-full">
                            {{ $product->category->name }}
                        </span>
                    </div>
                    
                    <h1 class="text-3xl font-bold text-gray-900 mb-4">{{ $product->name }}</h1>
                    
                    @if($product->short_description)
                    <p class="text-lg text-gray-600 mb-6">{{ $product->short_description }}</p>
                    @endif
                    
                    <!-- 평점 -->
                    <div class="flex items-center mb-6">
                        <div class="flex items-center">
                            @for($i = 1; $i <= 5; $i++)
                            <svg class="w-5 h-5 {{ $i <= $product->average_rating ? 'text-yellow-400' : 'text-gray-300' }}" 
                                 fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            @endfor
                        </div>
                        <span class="ml-2 text-lg text-gray-600">{{ number_format($product->average_rating, 1) }}</span>
                        <span class="ml-1 text-gray-500">({{ $product->reviews_count }}개 리뷰)</span>
                    </div>
                    
                    <!-- 가격 -->
                    <div class="mb-6">
                        @if($product->compare_price && $product->compare_price > $product->price)
                        <div class="text-lg text-gray-500 line-through mb-1">
                            ₩{{ number_format($product->compare_price) }}
                        </div>
                        @endif
                        <div class="text-3xl font-bold text-gray-900">
                            ₩{{ number_format($product->price) }}
                        </div>
                    </div>
                    
                    <!-- 재고 상태 -->
                    <div class="mb-6">
                        @if($product->stock_status === 'in_stock')
                        <span class="inline-flex items-center px-3 py-1 text-sm bg-green-100 text-green-800 rounded-full">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            재고 있음
                        </span>
                        @else
                        <span class="inline-flex items-center px-3 py-1 text-sm bg-red-100 text-red-800 rounded-full">
                            {{ $product->stock_status_label }}
                        </span>
                        @endif
                        
                        @if($product->track_stock && $product->stock_quantity <= $product->min_stock_quantity)
                        <div class="mt-2 text-sm text-red-600">
                            남은 수량: {{ $product->stock_quantity }}개
                        </div>
                        @endif
                    </div>
                    
                    <!-- 구매 옵션 -->
                    <div class="mb-8">
                        <div class="flex items-center space-x-4 mb-4">
                            <label class="text-sm font-medium text-gray-700">수량:</label>
                            <div class="flex items-center border border-gray-300 rounded">
                                <button type="button" onclick="decreaseQuantity()" 
                                        class="px-3 py-2 text-gray-600 hover:bg-gray-100">-</button>
                                <input type="number" id="quantity" value="1" min="1" max="{{ $product->max_purchase_quantity ?? 99 }}"
                                       class="w-16 px-2 py-2 text-center border-0 focus:ring-0">
                                <button type="button" onclick="increaseQuantity()"
                                        class="px-3 py-2 text-gray-600 hover:bg-gray-100">+</button>
                            </div>
                        </div>
                        
                        <div class="flex flex-col sm:flex-row gap-4">
                            <button onclick="addToCart()" 
                                    class="flex-1 bg-blue-600 text-white py-3 px-6 rounded-lg font-medium hover:bg-blue-700 transition-colors">
                                장바구니 담기
                            </button>
                            
                            @auth
                            <button onclick="toggleWishlist()" 
                                    class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                </svg>
                                찜하기
                            </button>
                            @endauth
                            
                            <button onclick="buyNow()" 
                                    class="px-6 py-3 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition-colors">
                                바로 구매
                            </button>
                        </div>
                    </div>
                    
                    <!-- 상품 정보 -->
                    @if($product->sku)
                    <div class="text-sm text-gray-600 mb-2">
                        상품코드: {{ $product->sku }}
                    </div>
                    @endif
                    
                    @if($product->requires_shipping)
                    <div class="text-sm text-gray-600">
                        배송비: {{ $product->shipping_cost ? '₩' . number_format($product->shipping_cost) : '무료' }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- 탭 메뉴 -->
        <div class="mt-8">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8">
                    <button onclick="showTab('description')" id="tab-description" 
                            class="tab-button py-2 px-1 border-b-2 border-blue-500 font-medium text-sm text-blue-600">
                        상품 설명
                    </button>
                    <button onclick="showTab('reviews')" id="tab-reviews"
                            class="tab-button py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        리뷰 ({{ $reviewStats['total'] }})
                    </button>
                    <button onclick="showTab('shipping')" id="tab-shipping"
                            class="tab-button py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        배송 정보
                    </button>
                </nav>
            </div>
            
            <!-- 탭 콘텐츠 -->
            <div class="mt-6">
                <!-- 상품 설명 -->
                <div id="content-description" class="tab-content">
                    <div class="bg-white rounded-lg shadow p-6">
                        @if($product->description)
                        <div class="prose max-w-none">
                            {!! nl2br(e($product->description)) !!}
                        </div>
                        @else
                        <p class="text-gray-500">상품 설명이 없습니다.</p>
                        @endif
                    </div>
                </div>
                
                <!-- 리뷰 -->
                <div id="content-reviews" class="tab-content hidden">
                    <div class="bg-white rounded-lg shadow p-6">
                        @if($reviewStats['total'] > 0)
                        <!-- 리뷰 통계 -->
                        <div class="mb-6 pb-6 border-b">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-3xl font-bold">{{ number_format($reviewStats['average'], 1) }}</div>
                                    <div class="flex items-center mt-1">
                                        @for($i = 1; $i <= 5; $i++)
                                        <svg class="w-4 h-4 {{ $i <= $reviewStats['average'] ? 'text-yellow-400' : 'text-gray-300' }}" 
                                             fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                        @endfor
                                        <span class="ml-2 text-sm text-gray-600">{{ $reviewStats['total'] }}개 리뷰</span>
                                    </div>
                                </div>
                                
                                <div class="text-right">
                                    @for($i = 5; $i >= 1; $i--)
                                    <div class="flex items-center text-sm">
                                        <span class="w-8">{{ $i }}점</span>
                                        <div class="w-24 bg-gray-200 rounded-full h-2 ml-2">
                                            <div class="bg-yellow-400 h-2 rounded-full" 
                                                 style="width: {{ $reviewStats['total'] > 0 ? (($reviewStats['ratings'][$i] ?? 0) / $reviewStats['total']) * 100 : 0 }}%"></div>
                                        </div>
                                        <span class="ml-2 w-8 text-right">{{ $reviewStats['ratings'][$i] ?? 0 }}</span>
                                    </div>
                                    @endfor
                                </div>
                            </div>
                        </div>
                        
                        <!-- 리뷰 목록 -->
                        <div class="space-y-6">
                            @foreach($product->reviews()->approved()->latest()->limit(5)->get() as $review)
                            <div class="border-b pb-6">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center">
                                        <span class="font-medium">{{ $review->masked_user_name }}</span>
                                        <div class="flex items-center ml-2">
                                            @for($i = 1; $i <= 5; $i++)
                                            <svg class="w-4 h-4 {{ $i <= $review->rating ? 'text-yellow-400' : 'text-gray-300' }}" 
                                                 fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                            @endfor
                                        </div>
                                    </div>
                                    <span class="text-sm text-gray-500">{{ $review->created_at->format('Y-m-d') }}</span>
                                </div>
                                
                                @if($review->title)
                                <h4 class="font-medium mb-2">{{ $review->title }}</h4>
                                @endif
                                
                                <p class="text-gray-700">{{ $review->content }}</p>
                                
                                @if($review->is_verified_purchase)
                                <span class="inline-block mt-2 px-2 py-1 text-xs bg-green-100 text-green-800 rounded">
                                    구매 확인
                                </span>
                                @endif
                            </div>
                            @endforeach
                        </div>
                        @else
                        <p class="text-gray-500 text-center py-8">아직 리뷰가 없습니다.</p>
                        @endif
                    </div>
                </div>
                
                <!-- 배송 정보 -->
                <div id="content-shipping" class="tab-content hidden">
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold mb-4">배송 정보</h3>
                        <div class="space-y-3">
                            <div class="flex">
                                <span class="w-24 text-gray-600">배송비:</span>
                                <span>{{ $product->shipping_cost ? '₩' . number_format($product->shipping_cost) : '무료' }}</span>
                            </div>
                            <div class="flex">
                                <span class="w-24 text-gray-600">배송 기간:</span>
                                <span>주문 후 2-3일 (주말, 공휴일 제외)</span>
                            </div>
                            <div class="flex">
                                <span class="w-24 text-gray-600">배송 지역:</span>
                                <span>전국 (일부 도서산간 지역 제외)</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 연관 상품 -->
        @if($relatedProducts->isNotEmpty())
        <div class="mt-12">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">관련 상품</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($relatedProducts as $related)
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <a href="{{ route('ahhob.shop.products.show', $related->slug) }}">
                        <div class="aspect-w-1 aspect-h-1 bg-gray-200">
                            @if($related->featured_image)
                            <img src="{{ asset('storage/' . $related->featured_image) }}" 
                                 alt="{{ $related->name }}"
                                 class="w-full h-48 object-cover">
                            @else
                            <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                                <span class="text-gray-400">이미지 없음</span>
                            </div>
                            @endif
                        </div>
                        
                        <div class="p-4">
                            <h3 class="font-medium text-gray-900 mb-2 line-clamp-2">{{ $related->name }}</h3>
                            <div class="text-lg font-bold text-gray-900">₩{{ number_format($related->price) }}</div>
                        </div>
                    </a>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
function increaseQuantity() {
    const input = document.getElementById('quantity');
    const max = parseInt(input.getAttribute('max'));
    const current = parseInt(input.value);
    if (current < max) {
        input.value = current + 1;
    }
}

function decreaseQuantity() {
    const input = document.getElementById('quantity');
    const current = parseInt(input.value);
    if (current > 1) {
        input.value = current - 1;
    }
}

function addToCart() {
    const quantity = document.getElementById('quantity').value;
    
    fetch('{{ route("ahhob.shop.cart.add") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            product_id: {{ $product->id }},
            quantity: parseInt(quantity)
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('오류가 발생했습니다.');
    });
}

@auth
function toggleWishlist() {
    fetch('{{ route("ahhob.shop.wishlist.add") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            product_id: {{ $product->id }}
        })
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
    })
    .catch(error => {
        console.error('Error:', error);
        alert('오류가 발생했습니다.');
    });
}
@endauth

function buyNow() {
    addToCart();
    setTimeout(() => {
        window.location.href = '{{ route("ahhob.shop.orders.create") }}';
    }, 1000);
}

function showTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    // Reset all tab buttons
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('border-blue-500', 'text-blue-600');
        button.classList.add('border-transparent', 'text-gray-500');
    });
    
    // Show selected tab content
    document.getElementById('content-' + tabName).classList.remove('hidden');
    
    // Activate selected tab button
    const activeButton = document.getElementById('tab-' + tabName);
    activeButton.classList.remove('border-transparent', 'text-gray-500');
    activeButton.classList.add('border-blue-500', 'text-blue-600');
}
</script>
@endpush
@endsection