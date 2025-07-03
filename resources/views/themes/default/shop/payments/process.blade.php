@extends('ahhob.layouts.app')

@section('title', '결제 진행')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- 결제 진행 단계 표시 -->
        <div class="mb-8">
            <div class="flex items-center justify-center">
                <div class="flex items-center w-full max-w-md">
                    <div class="flex items-center text-blue-600">
                        <div class="flex-shrink-0 w-8 h-8 border-2 border-blue-600 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
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
                            <span class="text-sm font-medium text-white">2</span>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium">결제 진행</div>
                        </div>
                    </div>
                    
                    <div class="flex-1 border-t-2 border-gray-300 mx-4"></div>
                    
                    <div class="flex items-center text-gray-400">
                        <div class="flex-shrink-0 w-8 h-8 border-2 border-gray-300 rounded-full flex items-center justify-center">
                            <span class="text-sm font-medium">3</span>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium">주문 완료</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- 결제 정보 -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow p-6">
                    <h1 class="text-2xl font-bold text-gray-900 mb-6">결제 진행</h1>
                    
                    <!-- 주문 정보 요약 -->
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">주문 정보</h3>
                        <div class="text-sm text-gray-600 space-y-1">
                            <div>주문번호: <span class="font-medium">{{ $order->order_number }}</span></div>
                            <div>주문일시: <span class="font-medium">{{ $order->created_at->format('Y-m-d H:i') }}</span></div>
                            <div>결제방법: <span class="font-medium">{{ $order->payment_method_label }}</span></div>
                        </div>
                    </div>

                    <!-- 결제 방법별 UI -->
                    @if($order->payment_method === 'card')
                    <div class="payment-section">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">신용카드 결제</h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">카드 번호</label>
                                <input type="text" id="card-number" placeholder="1234 5678 9012 3456" maxlength="19"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">유효기간</label>
                                    <input type="text" id="card-expiry" placeholder="MM/YY" maxlength="5"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">CVC</label>
                                    <input type="text" id="card-cvc" placeholder="123" maxlength="4"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">카드 소유자명</label>
                                <input type="text" id="card-holder" placeholder="홍길동"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                    </div>
                    @elseif($order->payment_method === 'bank_transfer')
                    <div class="payment-section">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">계좌이체</h3>
                        
                        <div class="p-4 bg-blue-50 rounded-lg">
                            <div class="flex items-center mb-3">
                                <svg class="w-5 h-5 text-blue-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-sm font-medium text-blue-800">실시간 계좌이체 안내</span>
                            </div>
                            <p class="text-sm text-blue-700">
                                은행 선택 후 인터넷뱅킹 또는 폰뱅킹을 통해 결제가 진행됩니다.
                            </p>
                        </div>
                        
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">은행 선택</label>
                            <select id="bank-select" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                <option value="">은행을 선택해주세요</option>
                                <option value="kookmin">국민은행</option>
                                <option value="shinhan">신한은행</option>
                                <option value="woori">우리은행</option>
                                <option value="hana">하나은행</option>
                                <option value="nh">농협은행</option>
                                <option value="ibk">기업은행</option>
                            </select>
                        </div>
                    </div>
                    @elseif($order->payment_method === 'virtual_account')
                    <div class="payment-section">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">가상계좌</h3>
                        
                        <div class="p-4 bg-purple-50 rounded-lg">
                            <div class="flex items-center mb-3">
                                <svg class="w-5 h-5 text-purple-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-sm font-medium text-purple-800">가상계좌 안내</span>
                            </div>
                            <p class="text-sm text-purple-700">
                                결제 진행 후 발급되는 가상계좌로 입금해주세요. 입금 확인 후 상품이 발송됩니다.
                            </p>
                        </div>
                        
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">입금자명</label>
                            <input type="text" id="depositor-name" value="{{ $order->customer_name }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <p class="mt-1 text-xs text-gray-500">입금자명이 주문자명과 다를 경우 입금확인이 지연될 수 있습니다.</p>
                        </div>
                    </div>
                    @endif

                    <!-- 결제 진행 버튼 -->
                    <div class="mt-8 flex space-x-4">
                        <button onclick="processPayment()" 
                                class="flex-1 bg-blue-600 text-white py-3 px-6 rounded-lg font-medium hover:bg-blue-700 transition-colors">
                            결제하기
                        </button>
                        <a href="{{ route('ahhob.shop.orders.create') }}" 
                           class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                            이전 단계
                        </a>
                    </div>
                </div>
            </div>

            <!-- 주문 요약 -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow p-6 sticky top-8">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">결제 정보</h2>
                    
                    <!-- 주문 상품 -->
                    <div class="mb-4">
                        <h3 class="text-sm font-medium text-gray-700 mb-2">주문 상품</h3>
                        <div class="space-y-2">
                            @foreach($order->orderItems as $item)
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">{{ $item->product_name }} × {{ $item->quantity }}</span>
                                <span class="font-medium">₩{{ number_format($item->total_price) }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    
                    <!-- 배송 정보 -->
                    <div class="mb-4 p-3 bg-gray-50 rounded">
                        <h3 class="text-sm font-medium text-gray-700 mb-1">배송 정보</h3>
                        <div class="text-xs text-gray-600">
                            <div>{{ $order->shipping_name }}</div>
                            <div>{{ $order->shipping_phone }}</div>
                            <div>{{ $order->full_shipping_address }}</div>
                        </div>
                    </div>
                    
                    <!-- 결제 금액 -->
                    <div class="space-y-2 border-t pt-4">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">상품 금액</span>
                            <span>₩{{ number_format($order->subtotal_amount) }}</span>
                        </div>
                        
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">배송비</span>
                            <span>
                                @if($order->shipping_cost > 0)
                                ₩{{ number_format($order->shipping_cost) }}
                                @else
                                무료
                                @endif
                            </span>
                        </div>
                        
                        @if($order->discount_amount > 0)
                        <div class="flex justify-between text-sm text-green-600">
                            <span>할인</span>
                            <span>-₩{{ number_format($order->discount_amount) }}</span>
                        </div>
                        @endif
                        
                        <hr class="my-2">
                        
                        <div class="flex justify-between text-lg font-bold">
                            <span>총 결제 금액</span>
                            <span class="text-blue-600">₩{{ number_format($order->total_amount) }}</span>
                        </div>
                    </div>
                    
                    <!-- 보안 안내 -->
                    <div class="mt-6 p-3 bg-green-50 rounded">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-xs text-green-800 font-medium">SSL 보안 결제</span>
                        </div>
                        <p class="text-xs text-green-700 mt-1">
                            모든 결제 정보는 SSL로 암호화되어 안전하게 처리됩니다.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 결제 진행 중 로딩 모달 -->
<div id="payment-loading" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen">
        <div class="bg-white rounded-lg p-8 text-center">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">결제 진행 중</h3>
            <p class="text-gray-600">잠시만 기다려주세요...</p>
        </div>
    </div>
</div>

@push('scripts')
<script>
// 카드 번호 입력 시 자동 하이픈 추가
document.getElementById('card-number')?.addEventListener('input', function(e) {
    let value = e.target.value.replace(/\s/g, '').replace(/[^0-9]/gi, '');
    let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
    e.target.value = formattedValue;
});

// 유효기간 입력 시 자동 슬래시 추가
document.getElementById('card-expiry')?.addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length >= 2) {
        value = value.substring(0, 2) + '/' + value.substring(2, 4);
    }
    e.target.value = value;
});

// CVC 숫자만 입력
document.getElementById('card-cvc')?.addEventListener('input', function(e) {
    e.target.value = e.target.value.replace(/[^0-9]/gi, '');
});

function processPayment() {
    const paymentMethod = '{{ $order->payment_method }}';
    
    // 결제 방법별 유효성 검사
    if (paymentMethod === 'card') {
        if (!validateCardForm()) {
            return;
        }
    } else if (paymentMethod === 'bank_transfer') {
        if (!document.getElementById('bank-select').value) {
            alert('은행을 선택해주세요.');
            return;
        }
    }
    
    // 로딩 표시
    document.getElementById('payment-loading').classList.remove('hidden');
    
    // 실제 결제 API 호출
    fetch('{{ route("ahhob.shop.payments.process", $order) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            payment_method: paymentMethod,
            card_number: document.getElementById('card-number')?.value,
            card_expiry: document.getElementById('card-expiry')?.value,
            card_cvc: document.getElementById('card-cvc')?.value,
            card_holder: document.getElementById('card-holder')?.value,
            bank_code: document.getElementById('bank-select')?.value,
            depositor_name: document.getElementById('depositor-name')?.value,
        })
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('payment-loading').classList.add('hidden');
        
        if (data.success) {
            // 결제 성공 시 완료 페이지로 이동
            window.location.href = data.redirect_url;
        } else {
            alert(data.message || '결제 처리 중 오류가 발생했습니다.');
        }
    })
    .catch(error => {
        document.getElementById('payment-loading').classList.add('hidden');
        console.error('Payment error:', error);
        alert('결제 처리 중 오류가 발생했습니다.');
    });
}

function validateCardForm() {
    const cardNumber = document.getElementById('card-number').value.replace(/\s/g, '');
    const cardExpiry = document.getElementById('card-expiry').value;
    const cardCvc = document.getElementById('card-cvc').value;
    const cardHolder = document.getElementById('card-holder').value;
    
    if (!cardNumber || cardNumber.length < 13) {
        alert('올바른 카드 번호를 입력해주세요.');
        return false;
    }
    
    if (!cardExpiry || !/^\d{2}\/\d{2}$/.test(cardExpiry)) {
        alert('올바른 유효기간을 입력해주세요.');
        return false;
    }
    
    if (!cardCvc || cardCvc.length < 3) {
        alert('올바른 CVC를 입력해주세요.');
        return false;
    }
    
    if (!cardHolder) {
        alert('카드 소유자명을 입력해주세요.');
        return false;
    }
    
    return true;
}
</script>
@endpush
@endsection