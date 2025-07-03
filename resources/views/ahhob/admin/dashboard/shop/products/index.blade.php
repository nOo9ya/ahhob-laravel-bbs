@extends('ahhob.admin.layouts.app')

@section('title', '상품 관리')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- 헤더 -->
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">상품 관리</h1>
                    <p class="mt-1 text-gray-600">상품을 등록하고 관리하세요</p>
                </div>
                <div class="flex items-center space-x-3">
                    <a href="{{ route('ahhob.admin.shop.products.stock') }}" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        재고 관리
                    </a>
                    <a href="{{ route('ahhob.admin.shop.products.create') }}" 
                       class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        상품 등록
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- 필터 및 검색 -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <!-- 검색 -->
                <div class="lg:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">검색</label>
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="상품명, SKU, 설명 검색"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <!-- 카테고리 -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">카테고리</label>
                    <select name="category_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        <option value="">전체 카테고리</option>
                        @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                
                <!-- 상태 -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">상태</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        <option value="">전체 상태</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>활성</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>비활성</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>임시저장</option>
                    </select>
                </div>
                
                <!-- 재고 상태 -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">재고 상태</label>
                    <select name="stock_status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        <option value="">전체</option>
                        <option value="in_stock" {{ request('stock_status') === 'in_stock' ? 'selected' : '' }}>재고 있음</option>
                        <option value="out_of_stock" {{ request('stock_status') === 'out_of_stock' ? 'selected' : '' }}>재고 없음</option>
                        <option value="on_backorder" {{ request('stock_status') === 'on_backorder' ? 'selected' : '' }}>예약주문</option>
                    </select>
                </div>
                
                <div class="lg:col-span-5 flex items-end space-x-3">
                    <button type="submit" 
                            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        검색
                    </button>
                    <a href="{{ route('ahhob.admin.shop.products.index') }}" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        초기화
                    </a>
                </div>
            </form>
        </div>

        <!-- 대량 작업 -->
        <form id="bulk-form" method="POST" action="{{ route('ahhob.admin.shop.products.bulk-action') }}">
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
                                    <option value="activate">활성화</option>
                                    <option value="deactivate">비활성화</option>
                                    <option value="delete">삭제</option>
                                </select>
                                <button type="submit" onclick="return confirm('선택된 상품에 대해 작업을 수행하시겠습니까?')"
                                        class="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700">
                                    실행
                                </button>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4 text-sm text-gray-600">
                            <span>총 {{ $products->total() }}개 상품</span>
                            <div class="flex items-center space-x-2">
                                <label>정렬:</label>
                                <select onchange="changeSort(this.value)" class="border border-gray-300 rounded px-2 py-1 text-sm">
                                    <option value="created_at.desc" {{ request('sort') === 'created_at' && request('direction') === 'desc' ? 'selected' : '' }}>최신순</option>
                                    <option value="name.asc" {{ request('sort') === 'name' && request('direction') === 'asc' ? 'selected' : '' }}>이름순</option>
                                    <option value="price.asc" {{ request('sort') === 'price' && request('direction') === 'asc' ? 'selected' : '' }}>가격 낮은순</option>
                                    <option value="price.desc" {{ request('sort') === 'price' && request('direction') === 'desc' ? 'selected' : '' }}>가격 높은순</option>
                                    <option value="sales_count.desc" {{ request('sort') === 'sales_count' && request('direction') === 'desc' ? 'selected' : '' }}>판매량순</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 상품 목록 -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <input type="checkbox" class="rounded border-gray-300">
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">상품</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">카테고리</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">가격</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">재고</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">판매량</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">상태</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">등록일</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">작업</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($products as $product)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="checkbox" name="product_ids[]" value="{{ $product->id }}" 
                                           class="product-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-12 w-12">
                                            @if($product->featured_image)
                                            <img src="{{ asset('storage/' . $product->featured_image) }}" 
                                                 alt="{{ $product->name }}"
                                                 class="h-12 w-12 object-cover rounded-lg">
                                            @else
                                            <div class="h-12 w-12 bg-gray-200 rounded-lg flex items-center justify-center">
                                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                                </svg>
                                            </div>
                                            @endif
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <a href="{{ route('ahhob.admin.shop.products.show', $product) }}" 
                                                   class="hover:text-blue-600">{{ $product->name }}</a>
                                            </div>
                                            @if($product->sku)
                                            <div class="text-sm text-gray-500">SKU: {{ $product->sku }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $product->category->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">₩{{ number_format($product->price) }}</div>
                                    @if($product->compare_price && $product->compare_price > $product->price)
                                    <div class="text-sm text-gray-500 line-through">₩{{ number_format($product->compare_price) }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($product->track_stock)
                                    <div class="text-sm text-gray-900">{{ $product->stock_quantity }}개</div>
                                    <div class="text-xs text-gray-500">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium 
                                            {{ $product->stock_status === 'in_stock' ? 'bg-green-100 text-green-800' : '' }}
                                            {{ $product->stock_status === 'out_of_stock' ? 'bg-red-100 text-red-800' : '' }}
                                            {{ $product->stock_status === 'on_backorder' ? 'bg-yellow-100 text-yellow-800' : '' }}">
                                            {{ $product->stock_status_label }}
                                        </span>
                                    </div>
                                    @else
                                    <span class="text-sm text-gray-500">추적 안함</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ number_format($product->sales_count) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                        {{ $product->status === 'active' ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $product->status === 'inactive' ? 'bg-red-100 text-red-800' : '' }}
                                        {{ $product->status === 'draft' ? 'bg-yellow-100 text-yellow-800' : '' }}">
                                        {{ $product->status_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $product->created_at->format('Y-m-d') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end space-x-2">
                                        <a href="{{ route('ahhob.shop.products.show', $product->slug) }}" 
                                           target="_blank"
                                           class="text-gray-600 hover:text-gray-900" title="미리보기">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </a>
                                        <a href="{{ route('ahhob.admin.shop.products.edit', $product) }}" 
                                           class="text-blue-600 hover:text-blue-900" title="수정">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>
                                        <form method="POST" action="{{ route('ahhob.admin.shop.products.destroy', $product) }}" 
                                              class="inline" onsubmit="return confirm('이 상품을 삭제하시겠습니까?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900" title="삭제">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- 페이지네이션 -->
                <div class="bg-white px-4 py-3 border-t border-gray-200">
                    {{ $products->withQueryString()->links() }}
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
// 전체 선택/해제
document.getElementById('select-all').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.product-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
    toggleBulkActions();
});

// 개별 체크박스 변경 시
document.querySelectorAll('.product-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', toggleBulkActions);
});

function toggleBulkActions() {
    const checkedBoxes = document.querySelectorAll('.product-checkbox:checked');
    const bulkActions = document.getElementById('bulk-actions');
    
    if (checkedBoxes.length > 0) {
        bulkActions.style.display = 'flex';
    } else {
        bulkActions.style.display = 'none';
    }
}

function changeSort(value) {
    const [sort, direction] = value.split('.');
    const url = new URL(window.location);
    url.searchParams.set('sort', sort);
    url.searchParams.set('direction', direction);
    window.location.href = url.toString();
}
</script>
@endpush
@endsection