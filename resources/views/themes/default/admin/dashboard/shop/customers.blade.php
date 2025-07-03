@extends('themes.default.layouts.app')

@section('title', '고객 관리')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- 헤더 -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">고객 관리</h1>
            <p class="mt-2 text-gray-600">고객 정보와 주문 이력을 관리하세요</p>
        </div>

        <!-- 고객 통계 카드 -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- 총 고객 수 -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">총 고객</dt>
                            <dd class="text-2xl font-bold text-gray-900">{{ number_format($customerStats['total_customers'] ?? 0) }}명</dd>
                        </dl>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex items-center text-sm">
                        <span class="text-blue-600 font-medium">+{{ number_format($customerStats['new_customers_this_month'] ?? 0) }}</span>
                        <span class="text-gray-500 ml-2">이번 달 신규</span>
                    </div>
                </div>
            </div>

            <!-- 활성 고객 -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">활성 고객</dt>
                            <dd class="text-2xl font-bold text-gray-900">{{ number_format($customerStats['active_customers'] ?? 0) }}명</dd>
                        </dl>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex items-center text-sm">
                        <span class="text-green-600 font-medium">{{ number_format($customerStats['active_percentage'] ?? 0, 1) }}%</span>
                        <span class="text-gray-500 ml-2">전체 중</span>
                    </div>
                </div>
            </div>

            <!-- 평균 주문 금액 -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">평균 주문 금액</dt>
                            <dd class="text-2xl font-bold text-gray-900">₩{{ number_format($customerStats['avg_order_value'] ?? 0) }}</dd>
                        </dl>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex items-center text-sm">
                        <span class="text-purple-600 font-medium">{{ number_format($customerStats['avg_orders_per_customer'] ?? 0, 1) }}</span>
                        <span class="text-gray-500 ml-2">평균 주문수</span>
                    </div>
                </div>
            </div>

            <!-- VIP 고객 -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">VIP 고객</dt>
                            <dd class="text-2xl font-bold text-gray-900">{{ number_format($customerStats['vip_customers'] ?? 0) }}명</dd>
                        </dl>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex items-center text-sm">
                        <span class="text-yellow-600 font-medium">₩{{ number_format($customerStats['vip_total_spent'] ?? 0) }}</span>
                        <span class="text-gray-500 ml-2">총 구매액</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 필터 및 검색 -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">고객 등급</label>
                    <select name="grade" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">전체</option>
                        <option value="bronze" {{ request('grade') === 'bronze' ? 'selected' : '' }}>브론즈</option>
                        <option value="silver" {{ request('grade') === 'silver' ? 'selected' : '' }}>실버</option>
                        <option value="gold" {{ request('grade') === 'gold' ? 'selected' : '' }}>골드</option>
                        <option value="vip" {{ request('grade') === 'vip' ? 'selected' : '' }}>VIP</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">활동 상태</label>
                    <select name="activity" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">전체</option>
                        <option value="active" {{ request('activity') === 'active' ? 'selected' : '' }}>활성</option>
                        <option value="inactive" {{ request('activity') === 'inactive' ? 'selected' : '' }}>비활성</option>
                        <option value="new" {{ request('activity') === 'new' ? 'selected' : '' }}>신규 (30일)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">정렬</label>
                    <select name="sort" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="created_at_desc" {{ request('sort') === 'created_at_desc' ? 'selected' : '' }}>최신 가입순</option>
                        <option value="created_at_asc" {{ request('sort') === 'created_at_asc' ? 'selected' : '' }}>오래된 가입순</option>
                        <option value="total_spent_desc" {{ request('sort') === 'total_spent_desc' ? 'selected' : '' }}>총 구매액 높은순</option>
                        <option value="order_count_desc" {{ request('sort') === 'order_count_desc' ? 'selected' : '' }}>주문수 많은순</option>
                        <option value="last_order_desc" {{ request('sort') === 'last_order_desc' ? 'selected' : '' }}>최근 주문순</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">검색</label>
                    <div class="flex">
                        <input type="text" name="search" value="{{ request('search') }}" 
                               placeholder="이름, 이메일, 전화번호"
                               class="flex-1 border border-gray-300 rounded-l-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                        <button type="submit" 
                                class="px-4 py-2 bg-gray-600 text-white rounded-r-md hover:bg-gray-700 transition-colors">
                            검색
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- 고객 목록 -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">
                    고객 목록 ({{ $customers->total() ?? 0 }}명)
                </h2>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                고객 정보
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                등급
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                가입일
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                총 주문
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                총 구매액
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                최근 주문
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                상태
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                작업
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($customers ?? [] as $customer)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        @if($customer->avatar)
                                        <img class="h-10 w-10 rounded-full object-cover" 
                                             src="{{ asset('storage/' . $customer->avatar) }}" 
                                             alt="{{ $customer->name }}">
                                        @else
                                        <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                            <span class="text-sm font-medium text-gray-600">
                                                {{ substr($customer->name, 0, 1) }}
                                            </span>
                                        </div>
                                        @endif
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $customer->name }}</div>
                                        <div class="text-sm text-gray-500">{{ $customer->email }}</div>
                                        @if($customer->phone)
                                        <div class="text-xs text-gray-400">{{ $customer->phone }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $grade = $customer->customer_grade ?? 'bronze';
                                    $gradeConfig = [
                                        'bronze' => ['name' => '브론즈', 'color' => 'bg-amber-100 text-amber-800'],
                                        'silver' => ['name' => '실버', 'color' => 'bg-gray-100 text-gray-800'],
                                        'gold' => ['name' => '골드', 'color' => 'bg-yellow-100 text-yellow-800'],
                                        'vip' => ['name' => 'VIP', 'color' => 'bg-purple-100 text-purple-800']
                                    ];
                                @endphp
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $gradeConfig[$grade]['color'] }}">
                                    {{ $gradeConfig[$grade]['name'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $customer->created_at->format('Y-m-d') }}
                                <div class="text-xs text-gray-400">
                                    {{ $customer->created_at->diffForHumans() }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ number_format($customer->orders_count ?? 0) }}건
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                ₩{{ number_format($customer->total_spent ?? 0) }}
                                @if(($customer->total_spent ?? 0) > 0)
                                <div class="text-xs text-gray-500">
                                    평균 ₩{{ number_format(($customer->total_spent ?? 0) / max(($customer->orders_count ?? 1), 1)) }}
                                </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                @if($customer->last_order_at)
                                {{ $customer->last_order_at->format('Y-m-d') }}
                                <div class="text-xs text-gray-400">
                                    {{ $customer->last_order_at->diffForHumans() }}
                                </div>
                                @else
                                <span class="text-gray-400">주문 없음</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $isActive = $customer->last_order_at && $customer->last_order_at->gt(now()->subDays(90));
                                @endphp
                                @if($isActive)
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                    활성
                                </span>
                                @else
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                    비활성
                                </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                <a href="{{ route('admin.customers.show', $customer) }}" 
                                   class="text-blue-600 hover:text-blue-900">
                                    <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                <button onclick="sendEmail({{ $customer->id }})" 
                                        class="text-green-600 hover:text-green-900">
                                    <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                </button>
                                <button onclick="updateGrade({{ $customer->id }})" 
                                        class="text-purple-600 hover:text-purple-900">
                                    <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                                등록된 고객이 없습니다.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- 페이지네이션 -->
            @if(isset($customers) && $customers->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $customers->withQueryString()->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
function sendEmail(customerId) {
    const subject = prompt('이메일 제목을 입력하세요:');
    if (!subject) return;
    
    const message = prompt('이메일 내용을 입력하세요:');
    if (!message) return;
    
    fetch(`/admin/customers/${customerId}/send-email`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            subject: subject,
            message: message
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('이메일이 발송되었습니다.');
        } else {
            alert(data.message || '이메일 발송에 실패했습니다.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('이메일 발송 중 오류가 발생했습니다.');
    });
}

function updateGrade(customerId) {
    const grade = prompt('변경할 등급을 입력하세요 (bronze, silver, gold, vip):');
    if (!grade || !['bronze', 'silver', 'gold', 'vip'].includes(grade)) {
        alert('올바른 등급을 입력해주세요.');
        return;
    }
    
    fetch(`/admin/customers/${customerId}/update-grade`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            grade: grade
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('고객 등급이 변경되었습니다.');
            location.reload();
        } else {
            alert(data.message || '등급 변경에 실패했습니다.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('등급 변경 중 오류가 발생했습니다.');
    });
}
</script>
@endpush
@endsection