@extends('ahhob.layouts.app')

@section('title', '주문서 작성')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">주문서 작성</h1>
        
        <form action="{{ route('ahhob.shop.orders.store') }}" method="POST" class="space-y-8">
            @csrf
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- 주문 정보 -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- 주문 상품 -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">주문 상품</h2>
                        
                        <div class="space-y-4">
                            @foreach($cartItems as $item)
                            <div class="flex items-start space-x-4 p-4 border border-gray-200 rounded">
                                @if($item->product_image)
                                <img src="{{ asset('storage/' . $item->product_image) }}" 
                                     alt="{{ $item->product_name }}"
                                     class="w-16 h-16 object-cover rounded">
                                @else
                                <div class="w-16 h-16 bg-gray-200 rounded flex items-center justify-center">
                                    <span class="text-gray-400 text-xs">이미지 없음</span>
                                </div>
                                @endif
                                
                                <div class="flex-1">
                                    <h3 class="font-medium text-gray-900">{{ $item->product_name }}</h3>
                                    <p class="text-sm text-gray-600 mt-1">수량: {{ $item->quantity }}개</p>
                                    <p class="text-lg font-semibold text-gray-900 mt-2">
                                        {{ $item->formatted_total_price }}
                                    </p>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    
                    <!-- 주문자 정보 -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">주문자 정보</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">이름 *</label>
                                <input type="text" name="customer_name" value="{{ old('customer_name', auth()->user()->name) }}" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                @error('customer_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">이메일 *</label>
                                <input type="email" name="customer_email" value="{{ old('customer_email', auth()->user()->email) }}" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                @error('customer_email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">휴대폰 *</label>
                                <input type="tel" name="customer_phone" value="{{ old('customer_phone') }}" required
                                       placeholder="010-1234-5678"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                @error('customer_phone')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <!-- 배송 정보 -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-900">배송 정보</h2>
                            <label class="flex items-center">
                                <input type="checkbox" id="same-as-customer" class="mr-2">
                                <span class="text-sm text-gray-600">주문자 정보와 동일</span>
                            </label>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">받는 분 *</label>
                                <input type="text" name="shipping_name" value="{{ old('shipping_name') }}" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                @error('shipping_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">휴대폰 *</label>
                                <input type="tel" name="shipping_phone" value="{{ old('shipping_phone') }}" required
                                       placeholder="010-1234-5678"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                @error('shipping_phone')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">주소 *</label>
                                <div class="space-y-2">
                                    <div class="flex">
                                        <input type="text" name="shipping_postal_code" value="{{ old('shipping_postal_code') }}" required
                                               placeholder="우편번호"
                                               class="flex-1 px-3 py-2 border border-gray-300 rounded-l-md focus:ring-blue-500 focus:border-blue-500">
                                        <button type="button" onclick="searchAddress()"
                                                class="px-4 py-2 bg-gray-600 text-white rounded-r-md hover:bg-gray-700">
                                            주소검색
                                        </button>
                                    </div>
                                    @error('shipping_postal_code')
                                    <p class="text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                        <div>
                                            <input type="text" name="shipping_city" value="{{ old('shipping_city') }}" required
                                                   placeholder="시/도"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                            @error('shipping_city')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        
                                        <div>
                                            <input type="text" name="shipping_state" value="{{ old('shipping_state') }}" required
                                                   placeholder="구/군"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                            @error('shipping_state')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                    
                                    <input type="text" name="shipping_address_line1" value="{{ old('shipping_address_line1') }}" required
                                           placeholder="기본 주소"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                    @error('shipping_address_line1')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                    
                                    <input type="text" name="shipping_address_line2" value="{{ old('shipping_address_line2') }}"
                                           placeholder="상세 주소"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>
                            
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">배송 요청사항</label>
                                <textarea name="shipping_notes" rows="3" placeholder="배송 시 요청사항을 입력해주세요"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">{{ old('shipping_notes') }}</textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 결제 방법 -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">결제 방법</h2>
                        
                        <div class="space-y-3">
                            <label class="flex items-center p-3 border border-gray-200 rounded cursor-pointer hover:bg-gray-50">
                                <input type="radio" name="payment_method" value="card" required
                                       class="mr-3 text-blue-600 focus:ring-blue-500">
                                <div class="flex items-center">
                                    <svg class="w-6 h-6 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4zM18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z"/>
                                    </svg>
                                    <span class="font-medium">신용카드</span>
                                </div>
                            </label>
                            
                            <label class="flex items-center p-3 border border-gray-200 rounded cursor-pointer hover:bg-gray-50">
                                <input type="radio" name="payment_method" value="bank_transfer"
                                       class="mr-3 text-blue-600 focus:ring-blue-500">
                                <div class="flex items-center">
                                    <svg class="w-6 h-6 mr-2 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm0 2h12v8H4V6z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="font-medium">계좌이체</span>
                                </div>
                            </label>
                            
                            <label class="flex items-center p-3 border border-gray-200 rounded cursor-pointer hover:bg-gray-50">
                                <input type="radio" name="payment_method" value="virtual_account"
                                       class="mr-3 text-blue-600 focus:ring-blue-500">
                                <div class="flex items-center">
                                    <svg class="w-6 h-6 mr-2 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M8 5a1 1 0 100 2h5.586l-1.293 1.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L13.586 5H8zM12 15a1 1 0 100-2H6.414l1.293-1.293a1 1 0 10-1.414-1.414l-3 3a1 1 0 000 1.414l3 3a1 1 0 001.414-1.414L6.414 15H12z"/>
                                    </svg>
                                    <span class="font-medium">가상계좌</span>
                                </div>
                            </label>
                        </div>
                        
                        @error('payment_method')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                
                <!-- 주문 요약 -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow p-6 sticky top-8">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">주문 요약</h2>
                        
                        <!-- 쿠폰 적용 -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">쿠폰 코드</label>
                            <div class="flex">
                                <input type="text" name="coupon_code" value="{{ old('coupon_code') }}"
                                       placeholder="쿠폰 코드 입력"
                                       class="flex-1 px-3 py-2 border border-gray-300 rounded-l-md focus:ring-blue-500 focus:border-blue-500">
                                <button type="button" onclick="applyCoupon()"
                                        class="px-4 py-2 bg-gray-600 text-white rounded-r-md hover:bg-gray-700">
                                    적용
                                </button>
                            </div>
                        </div>
                        
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
                            
                            <div class="flex justify-between text-green-600" id="coupon-discount" style="display: none;">
                                <span>쿠폰 할인</span>
                                <span id="coupon-discount-amount">-₩0</span>
                            </div>
                            
                            <hr>
                            
                            <div class="flex justify-between text-lg font-bold">
                                <span>총 결제 금액</span>
                                <span class="text-blue-600" id="final-total">₩{{ number_format($total) }}</span>
                            </div>
                        </div>
                        
                        <!-- 주문 약관 동의 -->
                        <div class="mb-6 space-y-2">
                            <label class="flex items-center text-sm">
                                <input type="checkbox" required class="mr-2">
                                <span>주문 내용을 확인했으며, 정보 제공 등에 동의합니다.</span>
                            </label>
                            
                            <label class="flex items-center text-sm">
                                <input type="checkbox" required class="mr-2">
                                <span>개인정보 수집 및 이용에 동의합니다.</span>
                            </label>
                        </div>
                        
                        <!-- 주문 완료 버튼 -->
                        <button type="submit" 
                                class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 transition-colors">
                            주문 완료
                        </button>
                        
                        <p class="mt-4 text-xs text-gray-500 text-center">
                            주문 완료 후 결제가 진행됩니다.
                        </p>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
// 주문자 정보와 배송 정보 동일하게 설정
document.getElementById('same-as-customer').addEventListener('change', function() {
    if (this.checked) {
        document.querySelector('input[name="shipping_name"]').value = document.querySelector('input[name="customer_name"]').value;
        document.querySelector('input[name="shipping_phone"]').value = document.querySelector('input[name="customer_phone"]').value;
    } else {
        document.querySelector('input[name="shipping_name"]').value = '';
        document.querySelector('input[name="shipping_phone"]').value = '';
    }
});

function searchAddress() {
    // 주소 검색 API 연동 (예: 카카오 주소 검색)
    alert('주소 검색 기능은 별도 API 연동이 필요합니다.');
}

function applyCoupon() {
    const couponCode = document.querySelector('input[name="coupon_code"]').value.trim();
    
    if (!couponCode) {
        alert('쿠폰 코드를 입력해주세요.');
        return;
    }
    
    // 실제 구현에서는 AJAX로 쿠폰 유효성 검사 및 할인 금액 계산
    alert('쿠폰 적용 기능은 서버 측 구현이 필요합니다.');
}
</script>
@endpush
@endsection