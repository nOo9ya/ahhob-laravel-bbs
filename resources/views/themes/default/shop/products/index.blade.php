@extends('ahhob.layouts.app')

@section('title', '상품 목록')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- 헤더 -->
    <div class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <h1 class="text-2xl font-bold text-gray-900">상품 목록</h1>
            <p class="mt-2 text-gray-600">다양한 상품을 둘러보세요</p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="lg:grid lg:grid-cols-4 lg:gap-8">
            <!-- 사이드바 - 필터 -->
            <div class="hidden lg:block">
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">필터</h3>
                    
                    <form method="GET" class="space-y-6">
                        <!-- 카테고리 -->
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 mb-3">카테고리</h4>
                            <div class="space-y-2">
                                @foreach($categories as $category)
                                <label class="flex items-center">
                                    <input type="radio" name="category" value="{{ $category->slug }}" 
                                           {{ request('category') === $category->slug ? 'checked' : '' }}
                                           class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">{{ $category->name }}</span>
                                </label>
                                @if($category->children->isNotEmpty())
                                    <div class="ml-6 space-y-2">
                                        @foreach($category->children as $child)
                                        <label class="flex items-center">
                                            <input type="radio" name="category" value="{{ $child->slug }}"
                                                   {{ request('category') === $child->slug ? 'checked' : '' }}
                                                   class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                                            <span class="ml-2 text-sm text-gray-600">{{ $child->name }}</span>
                                        </label>
                                        @endforeach
                                    </div>
                                @endif
                                @endforeach
                            </div>
                        </div>

                        <!-- 가격 범위 -->
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 mb-3">가격 범위</h4>
                            <div class="grid grid-cols-2 gap-2">
                                <input type="number" name="min_price" placeholder="최소 가격" 
                                       value="{{ request('min_price') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">
                                <input type="number" name="max_price" placeholder="최대 가격"
                                       value="{{ request('max_price') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition-colors">
                            필터 적용
                        </button>
                        
                        @if(request()->hasAny(['category', 'min_price', 'max_price']))
                        <a href="{{ route('ahhob.shop.products.index') }}" 
                           class="block w-full text-center text-gray-600 py-2 px-4 border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
                            필터 초기화
                        </a>
                        @endif
                    </form>
                </div>
            </div>

            <!-- 메인 콘텐츠 -->
            <div class="lg:col-span-3">
                <!-- 정렬 및 결과 수 -->
                <div class="bg-white rounded-lg shadow p-4 mb-6">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm text-gray-700 mb-4 sm:mb-0">
                            총 <span class="font-semibold">{{ $products->total() }}</span>개의 상품
                        </p>
                        
                        <div class="flex items-center space-x-4">
                            <label class="text-sm font-medium text-gray-700">정렬:</label>
                            <select name="sort" onchange="updateSort(this.value)" 
                                    class="border border-gray-300 rounded-md px-3 py-1 text-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="latest" {{ request('sort') === 'latest' ? 'selected' : '' }}>최신순</option>
                                <option value="price_low" {{ request('sort') === 'price_low' ? 'selected' : '' }}>가격 낮은순</option>
                                <option value="price_high" {{ request('sort') === 'price_high' ? 'selected' : '' }}>가격 높은순</option>
                                <option value="rating" {{ request('sort') === 'rating' ? 'selected' : '' }}>평점순</option>
                                <option value="popular" {{ request('sort') === 'popular' ? 'selected' : '' }}>인기순</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- 상품 그리드 -->
                @if($products->isNotEmpty())
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($products as $product)
                    <div class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition-shadow">
                        <a href="{{ route('ahhob.shop.products.show', $product->slug) }}" class="block">
                            <div class="aspect-w-1 aspect-h-1 bg-gray-200">
                                @if($product->featured_image)
                                <img src="{{ asset('storage/' . $product->featured_image) }}" 
                                     alt="{{ $product->name }}"
                                     class="w-full h-48 object-cover">
                                @else
                                <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                                    <span class="text-gray-400">이미지 없음</span>
                                </div>
                                @endif
                            </div>
                            
                            <div class="p-4">
                                <h3 class="text-lg font-semibold text-gray-900 mb-2 line-clamp-2">
                                    {{ $product->name }}
                                </h3>
                                
                                @if($product->short_description)
                                <p class="text-sm text-gray-600 mb-3 line-clamp-2">
                                    {{ $product->short_description }}
                                </p>
                                @endif
                                
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center space-x-1">
                                        @for($i = 1; $i <= 5; $i++)
                                        <svg class="w-4 h-4 {{ $i <= $product->average_rating ? 'text-yellow-400' : 'text-gray-300' }}" 
                                             fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                        @endfor
                                        <span class="text-sm text-gray-600 ml-1">({{ $product->reviews_count }})</span>
                                    </div>
                                    <span class="text-sm text-gray-500">{{ $product->category->name }}</span>
                                </div>
                                
                                <div class="flex items-center justify-between">
                                    <div>
                                        @if($product->compare_price && $product->compare_price > $product->price)
                                        <span class="text-sm text-gray-500 line-through">
                                            ₩{{ number_format($product->compare_price) }}
                                        </span>
                                        @endif
                                        <span class="text-lg font-bold text-gray-900">
                                            ₩{{ number_format($product->price) }}
                                        </span>
                                    </div>
                                    
                                    <div class="flex items-center space-x-2">
                                        <!-- 찜하기 버튼 -->
                                        @auth
                                        <button onclick="toggleWishlist({{ $product->id }})" 
                                                class="p-2 text-gray-400 hover:text-red-500 transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                      d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                            </svg>
                                        </button>
                                        @endauth
                                        
                                        <!-- 장바구니 버튼 -->
                                        <button onclick="addToCart({{ $product->id }})" 
                                                class="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 transition-colors">
                                            담기
                                        </button>
                                    </div>
                                </div>
                                
                                @if($product->stock_status !== 'in_stock')
                                <div class="mt-2 text-sm text-red-600">
                                    {{ $product->stock_status_label }}
                                </div>
                                @endif
                            </div>
                        </a>
                    </div>
                    @endforeach
                </div>

                <!-- 페이지네이션 -->
                <div class="mt-8">
                    {{ $products->withQueryString()->links() }}
                </div>
                @else
                <div class="bg-white rounded-lg shadow p-8 text-center">
                    <p class="text-gray-500">검색 조건에 맞는 상품이 없습니다.</p>
                    <a href="{{ route('ahhob.shop.products.index') }}" 
                       class="mt-4 inline-block bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                        전체 상품 보기
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- 모바일 필터 버튼 -->
<div class="lg:hidden fixed bottom-4 right-4">
    <button onclick="toggleMobileFilter()" 
            class="bg-blue-600 text-white p-3 rounded-full shadow-lg hover:bg-blue-700 transition-colors">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.707A1 1 0 013 7V4z"/>
        </svg>
    </button>
</div>

@push('scripts')
<script>
function updateSort(value) {
    const url = new URL(window.location);
    url.searchParams.set('sort', value);
    window.location.href = url.toString();
}

function addToCart(productId) {
    fetch('{{ route("ahhob.shop.cart.add") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            product_id: productId,
            quantity: 1
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            updateCartCount(data.cart_count);
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
function toggleWishlist(productId) {
    fetch('{{ route("ahhob.shop.wishlist.add") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            product_id: productId
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

function updateCartCount(count) {
    const cartCountElement = document.getElementById('cart-count');
    if (cartCountElement) {
        cartCountElement.textContent = count;
    }
}
</script>
@endpush
@endsection