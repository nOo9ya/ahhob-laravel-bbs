@extends('ahhob.admin.layouts.app')

@section('title', '쇼핑몰 대시보드')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- 헤더 -->
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">쇼핑몰 대시보드</h1>
                    <p class="mt-1 text-gray-600">실시간 쇼핑몰 현황을 확인하세요</p>
                </div>
                <div class="flex items-center space-x-3">
                    <button onclick="refreshStats()" 
                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        새로고침
                    </button>
                    <span class="text-sm text-gray-500" id="last-updated">
                        마지막 업데이트: {{ now()->format('H:i:s') }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- 주요 지표 카드 -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- 총 매출 -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-600">총 매출</p>
                        <p class="text-2xl font-bold text-gray-900">₩{{ number_format($stats['total_revenue']) }}</p>
                        <p class="text-sm text-green-600 mt-1">
                            <span class="font-medium">오늘: ₩{{ number_format($stats['today_revenue']) }}</span>
                        </p>
                    </div>
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 총 주문 -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-600">총 주문</p>
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_orders']) }}</p>
                        <p class="text-sm text-blue-600 mt-1">
                            <span class="font-medium">대기중: {{ $stats['pending_orders'] }}건</span>
                        </p>
                    </div>
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 상품 현황 -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-600">상품 현황</p>
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_products']) }}</p>
                        <p class="text-sm text-orange-600 mt-1">
                            <span class="font-medium">재고부족: {{ $stats['low_stock'] }}개</span>
                        </p>
                    </div>
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 고객 현황 -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-600">총 고객</p>
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_customers']) }}</p>
                        <p class="text-sm text-purple-600 mt-1">
                            <span class="font-medium">신규: {{ $stats['new_customers_today'] }}명</span>
                        </p>
                    </div>
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- 월별 매출 차트 -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">월별 매출 현황</h3>
                    <div class="flex space-x-2">
                        <button onclick="changeChartPeriod('7')" class="px-3 py-1 text-sm rounded-md bg-gray-100 hover:bg-gray-200">7일</button>
                        <button onclick="changeChartPeriod('30')" class="px-3 py-1 text-sm rounded-md bg-blue-100 text-blue-800">30일</button>
                        <button onclick="changeChartPeriod('90')" class="px-3 py-1 text-sm rounded-md bg-gray-100 hover:bg-gray-200">90일</button>
                    </div>
                </div>
                <div class="h-64">
                    <canvas id="revenueChart" width="400" height="200"></canvas>
                </div>
            </div>

            <!-- 주문 상태 분포 -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">주문 상태 분포</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-yellow-400 rounded-full mr-3"></div>
                            <span class="text-sm text-gray-700">대기중</span>
                        </div>
                        <span class="text-sm font-medium text-gray-900">{{ $stats['pending_orders'] }}건</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-blue-400 rounded-full mr-3"></div>
                            <span class="text-sm text-gray-700">처리중</span>
                        </div>
                        <span class="text-sm font-medium text-gray-900">{{ $stats['processing_orders'] }}건</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-purple-400 rounded-full mr-3"></div>
                            <span class="text-sm text-gray-700">배송중</span>
                        </div>
                        <span class="text-sm font-medium text-gray-900">{{ $stats['shipped_orders'] }}건</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-green-400 rounded-full mr-3"></div>
                            <span class="text-sm text-gray-700">배송완료</span>
                        </div>
                        <span class="text-sm font-medium text-gray-900">{{ $stats['delivered_orders'] ?? 0 }}건</span>
                    </div>
                </div>
                
                <div class="mt-6 pt-4 border-t border-gray-200">
                    <a href="{{ route('ahhob.admin.shop.orders.index') }}" 
                       class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                        전체 주문 관리 →
                    </a>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- 최근 주문 -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">최근 주문</h3>
                        <a href="{{ route('ahhob.admin.shop.orders.index') }}" 
                           class="text-sm text-blue-600 hover:text-blue-800">전체보기</a>
                    </div>
                </div>
                <div class="divide-y divide-gray-200">
                    @foreach($recentOrders as $order)
                    <div class="px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3">
                                    <p class="text-sm font-medium text-gray-900">{{ $order->order_number }}</p>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                        {{ $order->status_color === 'yellow' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                        {{ $order->status_color === 'blue' ? 'bg-blue-100 text-blue-800' : '' }}
                                        {{ $order->status_color === 'green' ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $order->status_color === 'purple' ? 'bg-purple-100 text-purple-800' : '' }}">
                                        {{ $order->status_label }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600 mt-1">{{ $order->customer_name }}</p>
                                <p class="text-xs text-gray-500">{{ $order->created_at->format('Y-m-d H:i') }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-gray-900">{{ $order->formatted_total_amount }}</p>
                                <p class="text-xs text-gray-500">{{ $order->items->count() }}개 상품</p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- 인기 상품 -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">인기 상품</h3>
                        <a href="{{ route('ahhob.admin.shop.products.index') }}" 
                           class="text-sm text-blue-600 hover:text-blue-800">전체보기</a>
                    </div>
                </div>
                <div class="divide-y divide-gray-200">
                    @foreach($topProducts as $product)
                    <div class="px-6 py-4">
                        <div class="flex items-center space-x-4">
                            <div class="flex-shrink-0">
                                @if($product->featured_image)
                                <img src="{{ asset('storage/' . $product->featured_image) }}" 
                                     alt="{{ $product->name }}"
                                     class="w-12 h-12 object-cover rounded-lg">
                                @else
                                <div class="w-12 h-12 bg-gray-200 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                </div>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ $product->name }}</p>
                                <p class="text-sm text-gray-600">{{ $product->category->name }}</p>
                                <p class="text-xs text-gray-500">판매량: {{ $product->sales_count }}개</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-gray-900">₩{{ number_format($product->price) }}</p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- 재고 부족 알림 -->
        @if($lowStockProducts->isNotEmpty())
        <div class="mt-8 bg-orange-50 border border-orange-200 rounded-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-orange-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                    <h3 class="text-lg font-medium text-orange-900">재고 부족 알림</h3>
                </div>
                <a href="{{ route('ahhob.admin.shop.products.stock') }}" 
                   class="text-sm text-orange-700 hover:text-orange-900 font-medium">
                    재고 관리 →
                </a>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($lowStockProducts->take(6) as $product)
                <div class="bg-white rounded-lg p-4 border border-orange-200">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ $product->name }}</p>
                            <p class="text-sm text-gray-600">{{ $product->category->name }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-orange-600">{{ $product->stock_quantity }}개</p>
                            <p class="text-xs text-gray-500">최소: {{ $product->min_stock_quantity }}개</p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let revenueChart;

// 차트 초기화
document.addEventListener('DOMContentLoaded', function() {
    initializeRevenueChart();
    
    // 5분마다 실시간 통계 업데이트
    setInterval(refreshStats, 300000);
});

function initializeRevenueChart() {
    const ctx = document.getElementById('revenueChart').getContext('2d');
    
    const monthlyData = @json($monthlyRevenue);
    
    revenueChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: monthlyData.map(item => item.period),
            datasets: [{
                label: '매출',
                data: monthlyData.map(item => item.revenue),
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₩' + value.toLocaleString();
                        }
                    }
                }
            },
            elements: {
                point: {
                    radius: 4,
                    hoverRadius: 6
                }
            }
        }
    });
}

function changeChartPeriod(period) {
    // 활성 버튼 상태 변경
    document.querySelectorAll('[onclick^="changeChartPeriod"]').forEach(btn => {
        btn.className = 'px-3 py-1 text-sm rounded-md bg-gray-100 hover:bg-gray-200';
    });
    event.target.className = 'px-3 py-1 text-sm rounded-md bg-blue-100 text-blue-800';
    
    // 차트 데이터 업데이트
    fetch(`{{ route('ahhob.admin.shop.dashboard.chart-data') }}?type=revenue&period=${period}`)
        .then(response => response.json())
        .then(data => {
            revenueChart.data.labels = data.map(item => item.date);
            revenueChart.data.datasets[0].data = data.map(item => item.value);
            revenueChart.update();
        })
        .catch(error => {
            console.error('Error updating chart:', error);
        });
}

function refreshStats() {
    fetch('{{ route("ahhob.admin.shop.dashboard.real-time-stats") }}')
        .then(response => response.json())
        .then(data => {
            // 실시간 통계 업데이트
            document.getElementById('last-updated').textContent = 
                '마지막 업데이트: ' + new Date().toLocaleTimeString();
            
            // 필요시 페이지 새로고침 또는 개별 요소 업데이트
            console.log('Stats refreshed:', data);
        })
        .catch(error => {
            console.error('Error refreshing stats:', error);
        });
}
</script>
@endpush
@endsection