@extends('themes.default.layouts.app')

@section('title', '쇼핑몰 관리 대시보드')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- 헤더 -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">쇼핑몰 관리 대시보드</h1>
            <p class="mt-2 text-gray-600">실시간 쇼핑몰 현황과 주요 지표를 확인하세요</p>
        </div>

        <!-- 주요 지표 카드 -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- 총 매출 -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">오늘 매출</dt>
                            <dd class="text-lg font-semibold text-gray-900">₩{{ number_format($stats['daily_sales'] ?? 0) }}</dd>
                        </dl>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex items-center text-sm">
                        <span class="text-green-600 font-medium">+12.5%</span>
                        <span class="text-gray-500 ml-2">어제 대비</span>
                    </div>
                </div>
            </div>

            <!-- 주문 수 -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">신규 주문</dt>
                            <dd class="text-lg font-semibold text-gray-900">{{ number_format($stats['daily_orders'] ?? 0) }}건</dd>
                        </dl>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex items-center text-sm">
                        <span class="text-blue-600 font-medium">+8.2%</span>
                        <span class="text-gray-500 ml-2">어제 대비</span>
                    </div>
                </div>
            </div>

            <!-- 방문자 수 -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">일일 방문자</dt>
                            <dd class="text-lg font-semibold text-gray-900">{{ number_format($stats['daily_visitors'] ?? 0) }}명</dd>
                        </dl>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex items-center text-sm">
                        <span class="text-purple-600 font-medium">+15.3%</span>
                        <span class="text-gray-500 ml-2">어제 대비</span>
                    </div>
                </div>
            </div>

            <!-- 전환율 -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-orange-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">구매 전환율</dt>
                            <dd class="text-lg font-semibold text-gray-900">{{ number_format($stats['conversion_rate'] ?? 0, 1) }}%</dd>
                        </dl>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex items-center text-sm">
                        <span class="text-red-600 font-medium">-2.1%</span>
                        <span class="text-gray-500 ml-2">어제 대비</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 차트 및 상세 정보 -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- 매출 차트 -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">매출 추이</h3>
                    <select class="border border-gray-300 rounded-md px-3 py-1 text-sm">
                        <option value="7">최근 7일</option>
                        <option value="30">최근 30일</option>
                        <option value="90">최근 90일</option>
                    </select>
                </div>
                <div class="h-64 flex items-center justify-center bg-gray-50 rounded">
                    <span class="text-gray-500">매출 차트 (Chart.js 연동 예정)</span>
                </div>
            </div>

            <!-- 주문 상태 -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-6">주문 현황</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-yellow-400 rounded-full mr-3"></div>
                            <span class="text-sm text-gray-600">결제 대기</span>
                        </div>
                        <span class="text-sm font-medium">{{ number_format($orderStats['pending'] ?? 0) }}건</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-blue-400 rounded-full mr-3"></div>
                            <span class="text-sm text-gray-600">결제 완료</span>
                        </div>
                        <span class="text-sm font-medium">{{ number_format($orderStats['paid'] ?? 0) }}건</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-purple-400 rounded-full mr-3"></div>
                            <span class="text-sm text-gray-600">배송 준비</span>
                        </div>
                        <span class="text-sm font-medium">{{ number_format($orderStats['preparing'] ?? 0) }}건</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-green-400 rounded-full mr-3"></div>
                            <span class="text-sm text-gray-600">배송 중</span>
                        </div>
                        <span class="text-sm font-medium">{{ number_format($orderStats['shipping'] ?? 0) }}건</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-gray-400 rounded-full mr-3"></div>
                            <span class="text-sm text-gray-600">배송 완료</span>
                        </div>
                        <span class="text-sm font-medium">{{ number_format($orderStats['delivered'] ?? 0) }}건</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 최근 주문 및 베스트 상품 -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- 최근 주문 -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">최근 주문</h3>
                        <a href="{{ route('admin.shop.orders.index') }}" class="text-sm text-blue-600 hover:text-blue-800">
                            전체 보기
                        </a>
                    </div>
                </div>
                <div class="divide-y divide-gray-200">
                    @forelse($recentOrders ?? [] as $order)
                    <div class="px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900">주문번호: {{ $order->order_number }}</p>
                                <p class="text-sm text-gray-600">{{ $order->customer_name }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-gray-900">₩{{ number_format($order->total_amount) }}</p>
                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full
                                    {{ $order->status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    {{ $order->status_label }}
                                </span>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="px-6 py-8 text-center text-gray-500">
                        최근 주문이 없습니다.
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- 베스트셀러 상품 -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">베스트셀러</h3>
                        <a href="{{ route('admin.shop.products.index') }}" class="text-sm text-blue-600 hover:text-blue-800">
                            전체 보기
                        </a>
                    </div>
                </div>
                <div class="divide-y divide-gray-200">
                    @forelse($bestProducts ?? [] as $index => $product)
                    <div class="px-6 py-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <span class="inline-flex items-center justify-center w-6 h-6 text-sm font-medium text-white bg-blue-600 rounded-full">
                                    {{ $index + 1 }}
                                </span>
                            </div>
                            <div class="ml-4 flex-1">
                                <p class="text-sm font-medium text-gray-900">{{ $product->name }}</p>
                                <p class="text-sm text-gray-600">판매량: {{ number_format($product->sales_count) }}개</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-gray-900">₩{{ number_format($product->price) }}</p>
                                <p class="text-sm text-gray-600">재고: {{ number_format($product->stock_quantity) }}</p>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="px-6 py-8 text-center text-gray-500">
                        상품 판매 데이터가 없습니다.
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- 빠른 작업 버튼 -->
        <div class="mt-8 grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="{{ route('admin.shop.products.create') }}" 
               class="bg-white rounded-lg shadow p-4 text-center hover:shadow-md transition-shadow">
                <div class="w-8 h-8 bg-blue-500 rounded-md mx-auto mb-3 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                </div>
                <span class="text-sm font-medium text-gray-900">상품 등록</span>
            </a>

            <a href="{{ route('admin.shop.orders.index') }}" 
               class="bg-white rounded-lg shadow p-4 text-center hover:shadow-md transition-shadow">
                <div class="w-8 h-8 bg-green-500 rounded-md mx-auto mb-3 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <span class="text-sm font-medium text-gray-900">주문 관리</span>
            </a>

            <a href="{{ route('admin.shop.dashboard.sales') }}" 
               class="bg-white rounded-lg shadow p-4 text-center hover:shadow-md transition-shadow">
                <div class="w-8 h-8 bg-purple-500 rounded-md mx-auto mb-3 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <span class="text-sm font-medium text-gray-900">매출 분석</span>
            </a>

            <a href="{{ route('admin.shop.payments.index') }}" 
               class="bg-white rounded-lg shadow p-4 text-center hover:shadow-md transition-shadow">
                <div class="w-8 h-8 bg-orange-500 rounded-md mx-auto mb-3 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </div>
                <span class="text-sm font-medium text-gray-900">결제 관리</span>
            </a>
        </div>
    </div>
</div>

@push('scripts')
<script>
// 실시간 데이터 업데이트 (선택사항)
setInterval(function() {
    // AJAX로 실시간 통계 업데이트
}, 60000); // 1분마다
</script>
@endpush
@endsection