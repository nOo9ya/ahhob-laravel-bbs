@extends('ahhob.admin.layouts.app')

@section('title', '주문 관리')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- 헤더 -->
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">주문 관리</h1>
                    <p class="mt-1 text-gray-600">고객 주문을 관리하고 처리하세요</p>
                </div>
                <div class="flex items-center space-x-3">
                    <div class="text-sm text-gray-600">
                        총 <span class="font-semibold text-gray-900">{{ number_format($stats['total_orders']) }}</span>건
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- 통계 카드 -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-600">대기중 주문</p>
                        <p class="text-2xl font-bold text-yellow-600">{{ number_format($stats['pending_orders']) }}</p>
                    </div>
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-600">처리중 주문</p>
                        <p class="text-2xl font-bold text-blue-600">{{ number_format($stats['processing_orders']) }}</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-600">배송중 주문</p>
                        <p class="text-2xl font-bold text-purple-600">{{ number_format($stats['shipped_orders']) }}</p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-600">총 매출</p>
                        <p class="text-2xl font-bold text-green-600">₩{{ number_format($stats['total_revenue']) }}</p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- 필터 및 검색 -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
                <!-- 검색 -->
                <div class="lg:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">검색</label>
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="주문번호, 고객명, 이메일, 전화번호"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <!-- 주문 상태 -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">주문 상태</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        <option value="">전체 상태</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>대기중</option>
                        <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>확인됨</option>
                        <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>처리중</option>
                        <option value="shipped" {{ request('status') === 'shipped' ? 'selected' : '' }}>배송중</option>
                        <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>배송완료</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>취소됨</option>
                        <option value="refunded" {{ request('status') === 'refunded' ? 'selected' : '' }}>환불됨</option>
                    </select>
                </div>
                
                <!-- 결제 상태 -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">결제 상태</label>
                    <select name="payment_status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        <option value="">전체</option>
                        <option value="pending" {{ request('payment_status') === 'pending' ? 'selected' : '' }}>대기중</option>
                        <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>결제완료</option>
                        <option value="failed" {{ request('payment_status') === 'failed' ? 'selected' : '' }}>결제실패</option>
                        <option value="refunded" {{ request('payment_status') === 'refunded' ? 'selected' : '' }}>환불완료</option>
                    </select>
                </div>
                
                <!-- 시작 날짜 -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">시작 날짜</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <!-- 종료 날짜 -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">종료 날짜</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div class="lg:col-span-6 flex items-end space-x-3">
                    <button type="submit" 
                            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        검색
                    </button>
                    <a href="{{ route('ahhob.admin.shop.orders.index') }}" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        초기화
                    </a>
                </div>
            </form>
        </div>

        <!-- 대량 작업 -->
        <form id="bulk-form" method="POST" action="{{ route('ahhob.admin.shop.orders.bulk-action') }}">
            @csrf
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <label class="flex items-center">
                                <input type="checkbox" id="select-all" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">전체 선택</span>
                            </label>
                            <div class="flex items-center space-x-2" id="bulk-actions" style="display: none;">
                                <select name="action" class="px-3 py-1 border border-gray-300 rounded text-sm">
                                    <option value="">작업 선택</option>
                                    <option value="confirm">주문 확인</option>
                                    <option value="ship">배송 처리</option>
                                    <option value="deliver">배송 완료</option>
                                    <option value="cancel">주문 취소</option>
                                </select>
                                <button type="submit" onclick="return confirm('선택된 주문에 대해 작업을 수행하시겠습니까?')"
                                        class="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700">
                                    실행
                                </button>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4 text-sm text-gray-600">
                            <span>총 {{ $orders->total() }}건</span>
                        </div>
                    </div>
                </div>

                <!-- 주문 목록 -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <input type="checkbox" class="rounded border-gray-300">
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">주문번호</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">고객정보</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">상품정보</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">주문금액</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">주문상태</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">결제상태</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">주문일시</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">작업</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($orders as $order)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="checkbox" name="order_ids[]" value="{{ $order->id }}" 
                                           class="order-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <a href="{{ route('ahhob.admin.shop.orders.show', $order) }}" 
                                           class="hover:text-blue-600">{{ $order->order_number }}</a>
                                    </div>
                                    @if($order->tracking_number)
                                    <div class="text-xs text-gray-500">
                                        추적: {{ $order->tracking_number }}
                                    </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $order->customer_name }}</div>
                                    <div class="text-sm text-gray-500">{{ $order->customer_email }}</div>
                                    <div class="text-xs text-gray-500">{{ $order->customer_phone }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">
                                        {{ $order->items->count() }}개 상품
                                    </div>
                                    <div class="text-xs text-gray-500 max-w-xs">
                                        @foreach($order->items->take(2) as $item)
                                        <div>{{ $item->product_name }} x{{ $item->quantity }}</div>
                                        @endforeach
                                        @if($order->items->count() > 2)
                                        <div>외 {{ $order->items->count() - 2 }}개</div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $order->formatted_total_amount }}</div>
                                    <div class="text-xs text-gray-500">{{ $order->payment_method_label }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                        {{ $order->status_color === 'yellow' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                        {{ $order->status_color === 'blue' ? 'bg-blue-100 text-blue-800' : '' }}
                                        {{ $order->status_color === 'green' ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $order->status_color === 'purple' ? 'bg-purple-100 text-purple-800' : '' }}
                                        {{ $order->status_color === 'red' ? 'bg-red-100 text-red-800' : '' }}
                                        {{ $order->status_color === 'gray' ? 'bg-gray-100 text-gray-800' : '' }}">
                                        {{ $order->status_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                        {{ $order->payment_status === 'paid' ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $order->payment_status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                        {{ $order->payment_status === 'failed' ? 'bg-red-100 text-red-800' : '' }}
                                        {{ $order->payment_status === 'refunded' ? 'bg-gray-100 text-gray-800' : '' }}">
                                        {{ $order->payment_status_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <div>{{ $order->created_at->format('Y-m-d') }}</div>
                                    <div class="text-xs">{{ $order->created_at->format('H:i') }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end space-x-2">
                                        <a href="{{ route('ahhob.admin.shop.orders.show', $order) }}" 
                                           class="text-blue-600 hover:text-blue-900" title="상세보기">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </a>
                                        
                                        @if($order->canBeCancelled())
                                        <button onclick="cancelOrder({{ $order->id }})" 
                                                class="text-red-600 hover:text-red-900" title="취소">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- 페이지네이션 -->
                <div class="bg-white px-4 py-3 border-t border-gray-200">
                    {{ $orders->withQueryString()->links() }}
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
// 전체 선택/해제
document.getElementById('select-all').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.order-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
    toggleBulkActions();
});

// 개별 체크박스 변경 시
document.querySelectorAll('.order-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', toggleBulkActions);
});

function toggleBulkActions() {
    const checkedBoxes = document.querySelectorAll('.order-checkbox:checked');
    const bulkActions = document.getElementById('bulk-actions');
    
    if (checkedBoxes.length > 0) {
        bulkActions.style.display = 'flex';
    } else {
        bulkActions.style.display = 'none';
    }
}

function cancelOrder(orderId) {
    const reason = prompt('취소 사유를 입력해주세요:');
    if (reason) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/admin/shop/orders/${orderId}/cancel`;
        
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = '{{ csrf_token() }}';
        
        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'PATCH';
        
        const reasonInput = document.createElement('input');
        reasonInput.type = 'hidden';
        reasonInput.name = 'cancel_reason';
        reasonInput.value = reason;
        
        form.appendChild(csrfInput);
        form.appendChild(methodInput);
        form.appendChild(reasonInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@endpush
@endsection