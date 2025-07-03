@extends('ahhob.layouts.app')

@section('title', '결제 완료')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- 결제 완료 단계 표시 -->
        <div class="mb-8">
            <div class="flex items-center justify-center">
                <div class="flex items-center w-full max-w-md">
                    <div class="flex items-center text-blue-600">
                        <div class="flex-shrink-0 w-8 h-8 border-2 border-blue-600 bg-blue-600 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium">주문서 작성</div>
                        </div>
                    </div>
                    
                    <div class="flex-1 border-t-2 border-blue-600 mx-4"></div>
                    
                    <div class="flex items-center text-blue-600">
                        <div class="flex-shrink-0 w-8 h-8 border-2 border-blue-600 bg-blue-600 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium">결제 진행</div>
                        </div>
                    </div>
                    
                    <div class="flex-1 border-t-2 border-blue-600 mx-4"></div>
                    
                    <div class="flex items-center text-blue-600">
                        <div class="flex-shrink-0 w-8 h-8 border-2 border-blue-600 bg-blue-600 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium">주문 완료</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-8 text-center">
            <!-- 성공 아이콘 -->
            <div class="mb-6">
                <div class="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
            </div>

            <h1 class="text-3xl font-bold text-gray-900 mb-4">
                @if($order->payment_status === 'paid')
                결제가 완료되었습니다!
                @else
                주문이 접수되었습니다!
                @endif
            </h1>

            @if($order->payment_method === 'virtual_account' && $order->payment_status === 'pending')
            <div class="mb-6 p-4 bg-blue-50 rounded-lg">
                <h3 class="text-lg font-semibold text-blue-900 mb-2">가상계좌 입금 안내</h3>
                <div class="text-blue-800">
                    <div class="mb-2">
                        <span class="font-medium">입금 계좌:</span> 
                        {{ $paymentInfo->bank_name ?? '국민은행' }} {{ $paymentInfo->account_number ?? '123-456-789012' }}
                    </div>
                    <div class="mb-2">
                        <span class="font-medium">입금 금액:</span> 
                        ₩{{ number_format($order->total_amount) }}
                    </div>
                    <div class="mb-2">
                        <span class="font-medium">입금 기한:</span> 
                        {{ $paymentInfo->due_date ?? now()->addDays(3)->format('Y-m-d 23:59') }}
                    </div>
                    <div class="text-sm text-blue-600">
                        입금자명: {{ $order->customer_name }}
                    </div>
                </div>
            </div>
            @endif

            <div class="mb-8 text-gray-600">
                <p class="mb-2">주문번호: <span class="font-medium text-gray-900">{{ $order->order_number }}</span></p>
                <p class="mb-2">주문일시: <span class="font-medium text-gray-900">{{ $order->created_at->format('Y-m-d H:i') }}</span></p>
                @if($order->payment_status === 'paid')
                <p>결제수단: <span class="font-medium text-gray-900">{{ $order->payment_method_label }}</span></p>
                @endif
            </div>

            <!-- 주문 상품 정보 -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">주문 상품</h2>
                <div class="border border-gray-200 rounded-lg divide-y divide-gray-200">
                    @foreach($order->orderItems as $item)
                    <div class="p-4 flex items-center space-x-4">
                        @if($item->product_image)
                        <img src="{{ asset('storage/' . $item->product_image) }}" 
                             alt="{{ $item->product_name }}"
                             class="w-16 h-16 object-cover rounded">
                        @else
                        <div class="w-16 h-16 bg-gray-200 rounded flex items-center justify-center">
                            <span class="text-gray-400 text-xs">이미지 없음</span>
                        </div>
                        @endif
                        
                        <div class="flex-1 text-left">
                            <h3 class="font-medium text-gray-900">{{ $item->product_name }}</h3>
                            <p class="text-sm text-gray-600">수량: {{ $item->quantity }}개</p>
                        </div>
                        
                        <div class="text-right">
                            <div class="font-semibold text-gray-900">₩{{ number_format($item->total_price) }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- 결제 금액 요약 -->
            <div class="mb-8 bg-gray-50 rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">결제 정보</h2>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">상품 금액</span>
                        <span class="font-medium">₩{{ number_format($order->subtotal_amount) }}</span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-gray-600">배송비</span>
                        <span class="font-medium">
                            @if($order->shipping_cost > 0)
                            ₩{{ number_format($order->shipping_cost) }}
                            @else
                            무료
                            @endif
                        </span>
                    </div>
                    
                    @if($order->discount_amount > 0)
                    <div class="flex justify-between text-green-600">
                        <span>할인</span>
                        <span class="font-medium">-₩{{ number_format($order->discount_amount) }}</span>
                    </div>
                    @endif
                    
                    <hr class="my-3">
                    
                    <div class="flex justify-between text-lg font-bold">
                        <span>총 결제 금액</span>
                        <span class="text-blue-600">₩{{ number_format($order->total_amount) }}</span>
                    </div>
                </div>
            </div>

            <!-- 배송 정보 -->
            <div class="mb-8 bg-gray-50 rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">배송 정보</h2>
                <div class="text-left text-sm text-gray-600">
                    <div class="mb-2">
                        <span class="font-medium text-gray-900">받는 분:</span> {{ $order->shipping_name }}
                    </div>
                    <div class="mb-2">
                        <span class="font-medium text-gray-900">연락처:</span> {{ $order->shipping_phone }}
                    </div>
                    <div class="mb-2">
                        <span class="font-medium text-gray-900">주소:</span> {{ $order->full_shipping_address }}
                    </div>
                    @if($order->shipping_notes)
                    <div>
                        <span class="font-medium text-gray-900">배송 요청사항:</span> {{ $order->shipping_notes }}
                    </div>
                    @endif
                </div>
            </div>

            <!-- 다음 단계 안내 -->
            @if($order->payment_status === 'paid')
            <div class="mb-8 p-4 bg-green-50 rounded-lg">
                <div class="flex items-center justify-center mb-2">
                    <svg class="w-5 h-5 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-sm font-medium text-green-800">배송 준비 중</span>
                </div>
                <p class="text-sm text-green-700">
                    결제가 완료되어 상품 준비 중입니다. 영업일 기준 1-2일 내 배송이 시작됩니다.
                </p>
            </div>
            @elseif($order->payment_status === 'pending' && $order->payment_method === 'virtual_account')
            <div class="mb-8 p-4 bg-yellow-50 rounded-lg">
                <div class="flex items-center justify-center mb-2">
                    <svg class="w-5 h-5 text-yellow-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-sm font-medium text-yellow-800">입금 대기 중</span>
                </div>
                <p class="text-sm text-yellow-700">
                    입금 확인 후 상품 배송이 시작됩니다. 입금 기한 내에 입금해주세요.
                </p>
            </div>
            @endif

            <!-- 액션 버튼들 -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('ahhob.shop.orders.show', $order) }}" 
                   class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    주문 상세보기
                </a>
                
                <a href="{{ route('ahhob.shop.products.index') }}" 
                   class="inline-flex items-center px-6 py-3 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    쇼핑 계속하기
                </a>
            </div>

            <!-- 고객 지원 안내 -->
            <div class="mt-8 pt-8 border-t border-gray-200">
                <p class="text-sm text-gray-600">
                    주문이나 배송에 관해 문의사항이 있으시면 
                    <a href="{{ route('ahhob.support.contact') }}" class="text-blue-600 hover:text-blue-800 font-medium">
                        고객센터
                    </a>로 연락해주세요.
                </p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// 페이지 로드 시 주문 완료 이벤트 트래킹 (Google Analytics 등)
document.addEventListener('DOMContentLoaded', function() {
    // 주문 완료 이벤트 추적
    if (typeof gtag !== 'undefined') {
        gtag('event', 'purchase', {
            'transaction_id': '{{ $order->order_number }}',
            'value': {{ $order->total_amount }},
            'currency': 'KRW',
            'items': [
                @foreach($order->orderItems as $item)
                {
                    'item_id': '{{ $item->product_sku }}',
                    'item_name': '{{ $item->product_name }}',
                    'category': '{{ $item->product->category->name ?? '' }}',
                    'quantity': {{ $item->quantity }},
                    'price': {{ $item->unit_price }}
                },
                @endforeach
            ]
        });
    }
});
</script>
@endpush
@endsection