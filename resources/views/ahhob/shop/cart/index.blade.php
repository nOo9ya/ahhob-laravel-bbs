@extends('ahhob.layouts.app')

@section('title', '장바구니')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">장바구니</h1>
        
        @if($cartItems->isNotEmpty())
        <div class="lg:grid lg:grid-cols-3 lg:gap-8">
            <!-- 장바구니 아이템 -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-900">상품 목록</h2>
                            <form action="{{ route('ahhob.shop.cart.clear') }}" method="POST" 
                                  onsubmit="return confirm('장바구니를 모두 비우시겠습니까?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm text-red-600 hover:text-red-800">
                                    전체 삭제
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="divide-y divide-gray-200">
                        @foreach($cartItems as $item)
                        <div class="p-6">
                            <div class="flex items-start space-x-4">
                                <!-- 상품 이미지 -->
                                <div class="flex-shrink-0">
                                    @if($item->product_image)
                                    <img src="{{ asset('storage/' . $item->product_image) }}" 
                                         alt="{{ $item->product_name }}"
                                         class="w-20 h-20 object-cover rounded">
                                    @else
                                    <div class="w-20 h-20 bg-gray-200 rounded flex items-center justify-center">
                                        <span class="text-gray-400 text-xs">이미지 없음</span>
                                    </div>
                                    @endif
                                </div>
                                
                                <!-- 상품 정보 -->
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">
                                        <a href="{{ route('ahhob.shop.products.show', $item->product->slug) }}" 
                                           class="hover:text-blue-600">
                                            {{ $item->product_name }}
                                        </a>
                                    </h3>
                                    
                                    @if($item->product_sku)
                                    <p class="text-sm text-gray-500 mb-2">상품코드: {{ $item->product_sku }}</p>
                                    @endif
                                    
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-2">
                                            <span class="text-sm text-gray-600">수량:</span>
                                            <div class="flex items-center border border-gray-300 rounded">
                                                <button type="button" onclick="updateQuantity({{ $item->id }}, {{ $item->quantity - 1 }})"
                                                        {{ $item->quantity <= 1 ? 'disabled' : '' }}
                                                        class="px-2 py-1 text-gray-600 hover:bg-gray-100 disabled:opacity-50">
                                                    -
                                                </button>
                                                <span class="px-3 py-1 text-center">{{ $item->quantity }}</span>
                                                <button type="button" onclick="updateQuantity({{ $item->id }}, {{ $item->quantity + 1 }})"
                                                        class="px-2 py-1 text-gray-600 hover:bg-gray-100">
                                                    +
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <div class="text-right">
                                            <div class="text-lg font-semibold text-gray-900">
                                                {{ $item->formatted_total_price }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                개당 {{ $item->formatted_unit_price }}
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- 재고 확인 -->
                                    @if(!$item->product->canPurchase($item->quantity))
                                    <div class="mt-2 text-sm text-red-600">
                                        재고가 부족합니다. (현재 재고: {{ $item->product->stock_quantity }}개)
                                    </div>
                                    @endif
                                </div>
                                
                                <!-- 삭제 버튼 -->
                                <div class="flex-shrink-0">
                                    <form action="{{ route('ahhob.shop.cart.destroy', $item) }}" method="POST"
                                          onsubmit="return confirm('이 상품을 장바구니에서 제거하시겠습니까?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-gray-400 hover:text-red-500">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            
            <!-- 주문 요약 -->
            <div class="mt-8 lg:mt-0">
                <div class="bg-white rounded-lg shadow p-6 sticky top-8">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">주문 요약</h2>
                    
                    <div class="space-y-3 mb-6">
                        <div class="flex justify-between">
                            <span class="text-gray-600">상품 금액</span>
                            <span class="font-medium">₩{{ number_format($subtotal) }}</span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-gray-600">배송비</span>
                            <span class="font-medium">
                                @if($shippingCost > 0)
                                ₩{{ number_format($shippingCost) }}
                                @else
                                무료
                                @endif
                            </span>
                        </div>
                        
                        @if($shippingCost > 0)
                        <div class="text-sm text-blue-600">
                            ₩{{ number_format(50000 - $subtotal) }} 더 구매하시면 무료배송!
                        </div>
                        @endif
                        
                        <hr>
                        
                        <div class="flex justify-between text-lg font-bold">
                            <span>총 결제 금액</span>
                            <span class="text-blue-600">₩{{ number_format($total) }}</span>
                        </div>
                    </div>
                    
                    <!-- 쿠폰 입력 -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">쿠폰 코드</label>
                        <div class="flex">
                            <input type="text" id="coupon-code" placeholder="쿠폰 코드 입력"
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-l-md focus:ring-blue-500 focus:border-blue-500">
                            <button type="button" onclick="applyCoupon()"
                                    class="px-4 py-2 bg-gray-600 text-white rounded-r-md hover:bg-gray-700">
                                적용
                            </button>
                        </div>
                    </div>
                    
                    <!-- 주문하기 버튼 -->
                    @auth
                    <a href="{{ route('ahhob.shop.orders.create') }}" 
                       class="block w-full bg-blue-600 text-white text-center py-3 px-4 rounded-lg font-medium hover:bg-blue-700 transition-colors">
                        주문하기
                    </a>
                    @else
                    <div class="space-y-2">
                        <a href="{{ route('ahhob.auth.login', ['redirect' => route('ahhob.shop.orders.create')]) }}" 
                           class="block w-full bg-blue-600 text-white text-center py-3 px-4 rounded-lg font-medium hover:bg-blue-700 transition-colors">
                            로그인 후 주문하기
                        </a>
                        <p class="text-xs text-gray-500 text-center">
                            주문을 진행하려면 로그인이 필요합니다.
                        </p>
                    </div>
                    @endauth
                    
                    <!-- 계속 쇼핑 -->
                    <a href="{{ route('ahhob.shop.products.index') }}" 
                       class="block w-full mt-4 text-center py-2 px-4 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        계속 쇼핑하기
                    </a>
                </div>
            </div>
        </div>
        @else
        <!-- 빈 장바구니 -->
        <div class="bg-white rounded-lg shadow p-12 text-center">
            <svg class="w-24 h-24 mx-auto text-gray-300 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" 
                      d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.293 2.293A1 1 0 015 16v0a1 1 0 001 1h11M7 13v6a1 1 0 001 1h8a1 1 0 001-1v-6"/>
            </svg>
            
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">장바구니가 비어있습니다</h2>
            <p class="text-gray-600 mb-8">원하는 상품을 장바구니에 담아보세요!</p>
            
            <a href="{{ route('ahhob.shop.products.index') }}" 
               class="inline-block bg-blue-600 text-white px-8 py-3 rounded-lg font-medium hover:bg-blue-700 transition-colors">
                상품 둘러보기
            </a>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
function updateQuantity(itemId, newQuantity) {
    if (newQuantity < 1) return;
    
    fetch(`/shop/cart/${itemId}`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            quantity: newQuantity
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload(); // 페이지 새로고침으로 가격 업데이트
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('오류가 발생했습니다.');
    });
}

function applyCoupon() {
    const couponCode = document.getElementById('coupon-code').value.trim();
    
    if (!couponCode) {
        alert('쿠폰 코드를 입력해주세요.');
        return;
    }
    
    // 쿠폰 적용 로직은 주문 단계에서 처리
    alert('쿠폰은 주문 단계에서 적용됩니다.');
}
</script>
@endpush
@endsection