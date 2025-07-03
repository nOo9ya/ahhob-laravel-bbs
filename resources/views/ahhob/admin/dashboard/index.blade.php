@extends('ahhob.admin.layouts.app')

@section('title', '통합 관리자 대시보드')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- 페이지 헤더 -->
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="py-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">통합 관리자 대시보드</h1>
                        <p class="mt-1 text-sm text-gray-600">
                            전체 시스템 현황을 한눈에 확인하세요
                        </p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <span class="text-sm text-gray-500" id="last-updated">
                            마지막 업데이트: {{ now()->format('Y-m-d H:i:s') }}
                        </span>
                        <button onclick="refreshDashboard()" 
                                class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            새로고침
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- 핵심 지표 카드 -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- 전체 회원 수 -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4 flex-1">
                            <h3 class="text-sm font-medium text-gray-500">전체 회원</h3>
                            <div class="mt-1 flex items-baseline">
                                <p class="text-2xl font-semibold text-gray-900">
                                    {{ number_format($overview['users']['total']) }}
                                </p>
                                <p class="ml-2 flex items-baseline text-sm font-semibold 
                                    {{ $overview['users']['growth_rate'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    <svg class="self-center flex-shrink-0 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                        @if($overview['users']['growth_rate'] >= 0)
                                            <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                                        @else
                                            <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        @endif
                                    </svg>
                                    <span class="sr-only">{{ $overview['users']['growth_rate'] >= 0 ? '증가' : '감소' }}</span>
                                    {{ abs($overview['users']['growth_rate']) }}%
                                </p>
                            </div>
                            <p class="text-sm text-gray-600">
                                오늘 {{ number_format($overview['users']['today']) }}명 가입
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 총 주문 -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4 flex-1">
                            <h3 class="text-sm font-medium text-gray-500">총 주문</h3>
                            <div class="mt-1">
                                <p class="text-2xl font-semibold text-gray-900">
                                    {{ number_format($overview['shop']['total_orders']) }}
                                </p>
                            </div>
                            <p class="text-sm text-gray-600">
                                오늘 {{ number_format($overview['shop']['today_orders']) }}건 주문
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 총 매출 -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4 flex-1">
                            <h3 class="text-sm font-medium text-gray-500">총 매출</h3>
                            <div class="mt-1">
                                <p class="text-2xl font-semibold text-gray-900">
                                    ₩{{ number_format($overview['shop']['total_revenue']) }}
                                </p>
                            </div>
                            <p class="text-sm text-gray-600">
                                오늘 ₩{{ number_format($overview['shop']['today_revenue']) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 결제 성공률 -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4 flex-1">
                            <h3 class="text-sm font-medium text-gray-500">결제 성공률</h3>
                            <div class="mt-1">
                                <p class="text-2xl font-semibold text-gray-900">
                                    {{ $overview['payments']['success_rate'] }}%
                                </p>
                            </div>
                            <p class="text-sm text-gray-600">
                                오늘 {{ number_format($overview['payments']['today_transactions']) }}건 처리
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 차트 섹션 -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- 트렌드 차트 -->
            <div class="bg-white shadow-sm rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900">30일 트렌드 분석</h3>
                        <div class="flex space-x-2">
                            <button onclick="changeTrendType('overview')" 
                                    class="px-3 py-1 text-sm rounded-md bg-blue-100 text-blue-800 trend-btn">
                                전체
                            </button>
                            <button onclick="changeTrendType('community')" 
                                    class="px-3 py-1 text-sm rounded-md bg-gray-100 hover:bg-gray-200 trend-btn">
                                커뮤니티
                            </button>
                            <button onclick="changeTrendType('shop')" 
                                    class="px-3 py-1 text-sm rounded-md bg-gray-100 hover:bg-gray-200 trend-btn">
                                쇼핑몰
                            </button>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <div class="h-80">
                        <canvas id="trendChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>

            <!-- 실시간 현황 -->
            <div class="bg-white shadow-sm rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">실시간 현황</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-6">
                        <!-- 대기 중인 주문 -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-2 h-2 bg-yellow-400 rounded-full mr-3"></div>
                                <span class="text-sm text-gray-700">대기 중인 주문</span>
                            </div>
                            <span class="text-sm font-medium text-gray-900" id="pending-orders">
                                {{ $overview['shop']['pending_orders'] }}건
                            </span>
                        </div>

                        <!-- 재고 부족 상품 -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-2 h-2 bg-red-400 rounded-full mr-3"></div>
                                <span class="text-sm text-gray-700">재고 부족 상품</span>
                            </div>
                            <span class="text-sm font-medium text-gray-900" id="low-stock">
                                {{ $overview['shop']['low_stock_products'] }}개
                            </span>
                        </div>

                        <!-- 실패한 결제 -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-2 h-2 bg-red-500 rounded-full mr-3"></div>
                                <span class="text-sm text-gray-700">실패한 결제</span>
                            </div>
                            <span class="text-sm font-medium text-gray-900" id="failed-payments">
                                {{ $overview['payments']['failed_transactions'] }}건
                            </span>
                        </div>

                        <!-- 온라인 사용자 -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-2 h-2 bg-green-400 rounded-full mr-3"></div>
                                <span class="text-sm text-gray-700">온라인 사용자</span>
                            </div>
                            <span class="text-sm font-medium text-gray-900" id="online-users">
                                {{ $realtimeStats['online_users'] }}명
                            </span>
                        </div>

                        <!-- 시스템 로드 -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-2 h-2 bg-blue-400 rounded-full mr-3"></div>
                                <span class="text-sm text-gray-700">시스템 로드</span>
                            </div>
                            <span class="text-sm font-medium text-gray-900" id="system-load">
                                {{ $realtimeStats['system_load']['cpu'] }}%
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 최근 활동 및 시스템 상태 -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- 최근 활동 -->
            <div class="bg-white shadow-sm rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">최근 활동</h3>
                </div>
                <div class="divide-y divide-gray-200">
                    @foreach($recentActivities as $activity)
                    <div class="px-6 py-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-{{ $activity['color'] }}-100 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-{{ $activity['color'] }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        @if($activity['icon'] === 'user-plus')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                                        @elseif($activity['icon'] === 'shopping-cart')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17M17 13v6a2 2 0 01-2 2H9a2 2 0 01-2-2v-6.001"/>
                                        @elseif($activity['icon'] === 'credit-card')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                        @endif
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4 flex-1">
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-medium text-gray-900">{{ $activity['title'] }}</p>
                                    <span class="text-xs text-gray-500">
                                        {{ $activity['time']->diffForHumans() }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600">{{ $activity['description'] }}</p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- 시스템 상태 -->
            <div class="bg-white shadow-sm rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">시스템 상태</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        @foreach($systemStatus as $service => $status)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-3 h-3 
                                    {{ $status['status'] === 'healthy' ? 'bg-green-400' : ($status['status'] === 'warning' ? 'bg-yellow-400' : 'bg-red-400') }} 
                                    rounded-full mr-3">
                                </div>
                                <span class="text-sm text-gray-700 capitalize">
                                    {{ ucfirst($service) }}
                                </span>
                            </div>
                            <span class="text-sm text-gray-600">{{ $status['message'] }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
let trendChart;
let currentTrendType = 'overview';

// 페이지 로드 시 초기화
document.addEventListener('DOMContentLoaded', function() {
    initializeTrendChart();
    
    // 5분마다 실시간 데이터 업데이트
    setInterval(refreshRealtimeStats, 300000);
});

// 트렌드 차트 초기화
function initializeTrendChart() {
    const ctx = document.getElementById('trendChart').getContext('2d');
    
    // 초기 데이터 로드
    fetchTrendData(currentTrendType).then(data => {
        trendChart = new Chart(ctx, {
            type: 'line',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    },
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                },
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: '날짜'
                        }
                    },
                    y: {
                        display: true,
                        title: {
                            display: true,
                            text: '수량'
                        }
                    }
                }
            }
        });
    });
}

// 트렌드 타입 변경
function changeTrendType(type) {
    currentTrendType = type;
    
    // 버튼 스타일 업데이트
    document.querySelectorAll('.trend-btn').forEach(btn => {
        btn.className = 'px-3 py-1 text-sm rounded-md bg-gray-100 hover:bg-gray-200 trend-btn';
    });
    event.target.className = 'px-3 py-1 text-sm rounded-md bg-blue-100 text-blue-800 trend-btn';
    
    // 차트 데이터 업데이트
    fetchTrendData(type).then(data => {
        trendChart.data = data;
        trendChart.update();
    });
}

// 트렌드 데이터 가져오기
async function fetchTrendData(type) {
    try {
        const response = await fetch(`/admin/dashboard/trend-data?type=${type}&period=30`);
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('트렌드 데이터 로드 실패:', error);
        return { labels: [], datasets: [] };
    }
}

// 실시간 통계 업데이트
async function refreshRealtimeStats() {
    try {
        const response = await fetch('/admin/dashboard/realtime-stats');
        const data = await response.json();
        
        // UI 업데이트
        document.getElementById('pending-orders').textContent = data.pending_orders + '건';
        document.getElementById('online-users').textContent = data.online_users + '명';
        document.getElementById('system-load').textContent = data.system_load.cpu + '%';
        
        // 마지막 업데이트 시간 갱신
        document.getElementById('last-updated').textContent = 
            '마지막 업데이트: ' + new Date().toLocaleString('ko-KR');
            
    } catch (error) {
        console.error('실시간 데이터 업데이트 실패:', error);
    }
}

// 대시보드 전체 새로고침
function refreshDashboard() {
    refreshRealtimeStats();
    
    // 트렌드 차트 업데이트
    fetchTrendData(currentTrendType).then(data => {
        trendChart.data = data;
        trendChart.update();
    });
    
    // 성공 메시지 표시
    showToast('대시보드가 업데이트되었습니다.', 'success');
}

// 토스트 메시지 표시
function showToast(message, type = 'info') {
    // 간단한 토스트 메시지 구현
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 px-4 py-2 rounded-md text-white z-50 ${
        type === 'success' ? 'bg-green-500' : 
        type === 'error' ? 'bg-red-500' : 'bg-blue-500'
    }`;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        document.body.removeChild(toast);
    }, 3000);
}
</script>
@endsection