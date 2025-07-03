@extends('themes.default.layouts.app')

@section('title', '매출 분석 대시보드')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- 헤더 -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">매출 분석</h1>
            <p class="mt-2 text-gray-600">상세한 매출 현황과 통계를 확인하세요</p>
        </div>

        <!-- 기간 선택 -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-lg font-semibold text-gray-900 mb-4 sm:mb-0">기간별 분석</h2>
                
                <div class="flex items-center space-x-4">
                    <select id="period-select" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="7">최근 7일</option>
                        <option value="30" selected>최근 30일</option>
                        <option value="90">최근 90일</option>
                        <option value="365">최근 1년</option>
                        <option value="custom">사용자 정의</option>
                    </select>
                    
                    <div id="custom-period" class="hidden flex items-center space-x-2">
                        <input type="date" id="start-date" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                        <span class="text-gray-500">~</span>
                        <input type="date" id="end-date" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                    </div>
                    
                    <button onclick="updatePeriod()" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                        적용
                    </button>
                </div>
            </div>
        </div>

        <!-- 매출 요약 카드 -->
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
                            <dt class="text-sm font-medium text-gray-500 truncate">총 매출</dt>
                            <dd class="text-2xl font-bold text-gray-900" id="total-sales">₩{{ number_format($salesData['total_sales'] ?? 0) }}</dd>
                        </dl>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex items-center text-sm">
                        <span class="text-green-600 font-medium" id="sales-growth">+15.3%</span>
                        <span class="text-gray-500 ml-2">전 기간 대비</span>
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
                            <dt class="text-sm font-medium text-gray-500 truncate">총 주문</dt>
                            <dd class="text-2xl font-bold text-gray-900" id="total-orders">{{ number_format($salesData['total_orders'] ?? 0) }}건</dd>
                        </dl>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex items-center text-sm">
                        <span class="text-blue-600 font-medium" id="orders-growth">+8.7%</span>
                        <span class="text-gray-500 ml-2">전 기간 대비</span>
                    </div>
                </div>
            </div>

            <!-- 평균 주문 금액 -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">평균 주문 금액</dt>
                            <dd class="text-2xl font-bold text-gray-900" id="avg-order-value">₩{{ number_format($salesData['avg_order_value'] ?? 0) }}</dd>
                        </dl>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex items-center text-sm">
                        <span class="text-purple-600 font-medium" id="aov-growth">+5.2%</span>
                        <span class="text-gray-500 ml-2">전 기간 대비</span>
                    </div>
                </div>
            </div>

            <!-- 신규 고객 -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-orange-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">신규 고객</dt>
                            <dd class="text-2xl font-bold text-gray-900" id="new-customers">{{ number_format($salesData['new_customers'] ?? 0) }}명</dd>
                        </dl>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex items-center text-sm">
                        <span class="text-orange-600 font-medium" id="customers-growth">+12.1%</span>
                        <span class="text-gray-500 ml-2">전 기간 대비</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 매출 차트 -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- 일별 매출 추이 -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">일별 매출 추이</h3>
                    <select class="border border-gray-300 rounded-md px-3 py-1 text-sm">
                        <option value="sales">매출</option>
                        <option value="orders">주문수</option>
                        <option value="customers">고객수</option>
                    </select>
                </div>
                <div class="h-80">
                    <canvas id="sales-chart"></canvas>
                </div>
            </div>

            <!-- 상품별 매출 -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-6">상품별 매출 TOP 10</h3>
                <div class="space-y-4">
                    @foreach($topProducts ?? [] as $index => $product)
                    <div class="flex items-center">
                        <div class="flex-shrink-0 w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                            <span class="text-sm font-medium text-blue-600">{{ $index + 1 }}</span>
                        </div>
                        <div class="ml-4 flex-1">
                            <div class="text-sm font-medium text-gray-900">{{ $product['name'] }}</div>
                            <div class="text-xs text-gray-500">{{ $product['sales_count'] }}개 판매</div>
                        </div>
                        <div class="text-sm font-medium text-gray-900">
                            ₩{{ number_format($product['total_sales']) }}
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- 카테고리별 매출 및 지역별 분석 -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- 카테고리별 매출 -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-6">카테고리별 매출</h3>
                <div class="h-64">
                    <canvas id="category-chart"></canvas>
                </div>
            </div>

            <!-- 결제 방법별 통계 -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-6">결제 방법별 통계</h3>
                <div class="space-y-4">
                    @foreach($paymentMethods ?? [] as $method)
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-3 h-3 rounded-full mr-3" style="background-color: {{ $method['color'] }}"></div>
                            <span class="text-sm text-gray-700">{{ $method['name'] }}</span>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-medium">{{ $method['percentage'] }}%</div>
                            <div class="text-xs text-gray-500">₩{{ number_format($method['amount']) }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- 매출 세부 분석 -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">매출 세부 분석</h3>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">날짜</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">주문수</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">매출</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">평균 주문금액</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">신규 고객</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">전환율</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($dailyStats ?? [] as $stat)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $stat['date'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ number_format($stat['orders']) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                ₩{{ number_format($stat['sales']) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                ₩{{ number_format($stat['avg_order_value']) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ number_format($stat['new_customers']) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ number_format($stat['conversion_rate'], 1) }}%
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// 기간 선택 처리
document.getElementById('period-select').addEventListener('change', function() {
    const customPeriod = document.getElementById('custom-period');
    if (this.value === 'custom') {
        customPeriod.classList.remove('hidden');
    } else {
        customPeriod.classList.add('hidden');
    }
});

// 매출 차트 생성
const salesCtx = document.getElementById('sales-chart').getContext('2d');
const salesChart = new Chart(salesCtx, {
    type: 'line',
    data: {
        labels: {!! json_encode($chartData['labels'] ?? []) !!},
        datasets: [{
            label: '매출',
            data: {!! json_encode($chartData['sales'] ?? []) !!},
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '₩' + value.toLocaleString();
                    }
                }
            }
        }
    }
});

// 카테고리 차트 생성
const categoryCtx = document.getElementById('category-chart').getContext('2d');
const categoryChart = new Chart(categoryCtx, {
    type: 'doughnut',
    data: {
        labels: {!! json_encode($categoryData['labels'] ?? []) !!},
        datasets: [{
            data: {!! json_encode($categoryData['sales'] ?? []) !!},
            backgroundColor: [
                '#ef4444',
                '#f97316',
                '#eab308',
                '#22c55e',
                '#3b82f6',
                '#8b5cf6',
                '#ec4899'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

function updatePeriod() {
    const period = document.getElementById('period-select').value;
    let startDate, endDate;
    
    if (period === 'custom') {
        startDate = document.getElementById('start-date').value;
        endDate = document.getElementById('end-date').value;
        
        if (!startDate || !endDate) {
            alert('시작일과 종료일을 모두 선택해주세요.');
            return;
        }
    }
    
    // AJAX로 데이터 업데이트
    const params = new URLSearchParams({
        period: period,
        start_date: startDate || '',
        end_date: endDate || ''
    });
    
    fetch(`/admin/shop/sales/data?${params}`)
        .then(response => response.json())
        .then(data => {
            // 차트 및 통계 업데이트
            updateDashboard(data);
        })
        .catch(error => {
            console.error('Error:', error);
            alert('데이터를 불러오는 중 오류가 발생했습니다.');
        });
}

function updateDashboard(data) {
    // 요약 카드 업데이트
    document.getElementById('total-sales').textContent = '₩' + data.total_sales.toLocaleString();
    document.getElementById('total-orders').textContent = data.total_orders.toLocaleString() + '건';
    document.getElementById('avg-order-value').textContent = '₩' + data.avg_order_value.toLocaleString();
    document.getElementById('new-customers').textContent = data.new_customers.toLocaleString() + '명';
    
    // 차트 업데이트
    salesChart.data.labels = data.chart_data.labels;
    salesChart.data.datasets[0].data = data.chart_data.sales;
    salesChart.update();
    
    categoryChart.data.labels = data.category_data.labels;
    categoryChart.data.datasets[0].data = data.category_data.sales;
    categoryChart.update();
}
</script>
@endpush
@endsection