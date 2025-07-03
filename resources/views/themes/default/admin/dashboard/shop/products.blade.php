@extends('themes.default.layouts.app')

@section('title', '상품 관리')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- 헤더 -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">상품 관리</h1>
                    <p class="mt-2 text-gray-600">등록된 상품을 관리하고 새로운 상품을 추가하세요</p>
                </div>
                <div class="flex space-x-3">
                    <a href="{{ route('admin.shop.products.create') }}" 
                       class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        상품 등록
                    </a>
                    <button onclick="bulkAction()" 
                            class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors">
                        일괄 작업
                    </button>
                </div>
            </div>
        </div>

        <!-- 필터 및 검색 -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">카테고리</label>
                    <select name="category" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">전체 카테고리</option>
                        @foreach($categories ?? [] as $category)
                        <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">상태</label>
                    <select name="status" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">전체</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>판매중</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>판매중지</option>
                        <option value="out_of_stock" {{ request('status') === 'out_of_stock' ? 'selected' : '' }}>품절</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">정렬</label>
                    <select name="sort" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="created_at_desc" {{ request('sort') === 'created_at_desc' ? 'selected' : '' }}>최신순</option>
                        <option value="created_at_asc" {{ request('sort') === 'created_at_asc' ? 'selected' : '' }}>오래된순</option>
                        <option value="name_asc" {{ request('sort') === 'name_asc' ? 'selected' : '' }}>이름순</option>
                        <option value="price_asc" {{ request('sort') === 'price_asc' ? 'selected' : '' }}>가격 낮은순</option>
                        <option value="price_desc" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>가격 높은순</option>
                        <option value="sales_desc" {{ request('sort') === 'sales_desc' ? 'selected' : '' }}>판매량순</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">검색</label>
                    <div class="flex">
                        <input type="text" name="search" value="{{ request('search') }}" 
                               placeholder="상품명, SKU로 검색"
                               class="flex-1 border border-gray-300 rounded-l-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                        <button type="submit" 
                                class="px-4 py-2 bg-gray-600 text-white rounded-r-md hover:bg-gray-700 transition-colors">
                            검색
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- 상품 목록 -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">
                        상품 목록 ({{ $products->total() ?? 0 }}개)
                    </h2>
                    <div class="flex items-center space-x-2">
                        <label class="flex items-center">
                            <input type="checkbox" id="select-all" class="mr-2">
                            <span class="text-sm text-gray-600">전체 선택</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <input type="checkbox" class="select-all-checkbox">
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                상품
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                카테고리
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                가격
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                재고
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                판매량
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                상태
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                등록일
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                작업
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($products ?? [] as $product)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="checkbox" class="product-checkbox" value="{{ $product->id }}">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-12 w-12">
                                        @if($product->featured_image)
                                        <img class="h-12 w-12 rounded-lg object-cover" 
                                             src="{{ asset('storage/' . $product->featured_image) }}" 
                                             alt="{{ $product->name }}">
                                        @else
                                        <div class="h-12 w-12 rounded-lg bg-gray-200 flex items-center justify-center">
                                            <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                        @endif
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            <a href="{{ route('admin.shop.products.show', $product) }}" 
                                               class="hover:text-blue-600">
                                                {{ $product->name }}
                                            </a>
                                        </div>
                                        <div class="text-sm text-gray-500">SKU: {{ $product->sku }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $product->category->name ?? '미분류' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                ₩{{ number_format($product->price) }}
                                @if($product->compare_price && $product->compare_price > $product->price)
                                <div class="text-xs text-gray-500 line-through">
                                    ₩{{ number_format($product->compare_price) }}
                                </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                @if($product->track_stock)
                                <span class="{{ $product->stock_quantity <= $product->min_stock_quantity ? 'text-red-600' : 'text-gray-900' }}">
                                    {{ number_format($product->stock_quantity) }}개
                                </span>
                                @if($product->stock_quantity <= $product->min_stock_quantity)
                                <div class="text-xs text-red-500">재고 부족</div>
                                @endif
                                @else
                                <span class="text-gray-400">추적 안함</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ number_format($product->sales_count ?? 0) }}개
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($product->is_active)
                                    @if($product->stock_quantity > 0 || !$product->track_stock)
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                        판매중
                                    </span>
                                    @else
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                        품절
                                    </span>
                                    @endif
                                @else
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                    판매중지
                                </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $product->created_at->format('Y-m-d') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                <a href="{{ route('ahhob.shop.products.show', $product->slug) }}" 
                                   target="_blank"
                                   class="text-gray-600 hover:text-gray-900">
                                    <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                <a href="{{ route('admin.shop.products.edit', $product) }}" 
                                   class="text-blue-600 hover:text-blue-900">
                                    <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                <button onclick="toggleProductStatus({{ $product->id }})" 
                                        class="text-yellow-600 hover:text-yellow-900">
                                    @if($product->is_active)
                                    <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636"/>
                                    </svg>
                                    @else
                                    <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    @endif
                                </button>
                                <button onclick="deleteProduct({{ $product->id }})" 
                                        class="text-red-600 hover:text-red-900">
                                    <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="px-6 py-8 text-center text-gray-500">
                                등록된 상품이 없습니다.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- 페이지네이션 -->
            @if(isset($products) && $products->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $products->withQueryString()->links() }}
            </div>
            @endif
        </div>

        <!-- 일괄 작업 패널 (숨김) -->
        <div id="bulk-action-panel" class="hidden fixed inset-x-0 bottom-0 bg-white border-t border-gray-200 shadow-lg p-4">
            <div class="max-w-7xl mx-auto flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <span class="text-sm font-medium text-gray-700">
                        <span id="selected-count">0</span>개 상품 선택됨
                    </span>
                    <select id="bulk-action-select" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                        <option value="">작업 선택</option>
                        <option value="activate">활성화</option>
                        <option value="deactivate">비활성화</option>
                        <option value="delete">삭제</option>
                        <option value="update_category">카테고리 변경</option>
                        <option value="update_price">가격 일괄 수정</option>
                    </select>
                </div>
                <div class="flex space-x-3">
                    <button onclick="cancelBulkAction()" 
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50">
                        취소
                    </button>
                    <button onclick="executeBulkAction()" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        실행
                    </button>
                </div>
            </div>
        </div>
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
    updateBulkActionPanel();
});

// 개별 체크박스 이벤트
document.querySelectorAll('.product-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', updateBulkActionPanel);
});

function updateBulkActionPanel() {
    const selectedCheckboxes = document.querySelectorAll('.product-checkbox:checked');
    const selectedCount = selectedCheckboxes.length;
    const panel = document.getElementById('bulk-action-panel');
    
    document.getElementById('selected-count').textContent = selectedCount;
    
    if (selectedCount > 0) {
        panel.classList.remove('hidden');
    } else {
        panel.classList.add('hidden');
    }
}

function bulkAction() {
    const panel = document.getElementById('bulk-action-panel');
    if (panel.classList.contains('hidden')) {
        alert('일괄 작업할 상품을 선택해주세요.');
    }
}

function cancelBulkAction() {
    document.querySelectorAll('.product-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
    document.getElementById('select-all').checked = false;
    updateBulkActionPanel();
}

function executeBulkAction() {
    const action = document.getElementById('bulk-action-select').value;
    const selectedIds = Array.from(document.querySelectorAll('.product-checkbox:checked'))
        .map(checkbox => checkbox.value);
    
    if (!action) {
        alert('실행할 작업을 선택해주세요.');
        return;
    }
    
    if (selectedIds.length === 0) {
        alert('선택된 상품이 없습니다.');
        return;
    }
    
    if (!confirm(`선택한 ${selectedIds.length}개 상품에 대해 "${action}" 작업을 실행하시겠습니까?`)) {
        return;
    }
    
    // AJAX 요청으로 일괄 작업 실행
    fetch('/admin/shop/products/bulk-action', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            action: action,
            product_ids: selectedIds
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message || '작업 실행에 실패했습니다.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('작업 실행 중 오류가 발생했습니다.');
    });
}

function toggleProductStatus(productId) {
    if (!confirm('상품 상태를 변경하시겠습니까?')) {
        return;
    }
    
    fetch(`/admin/shop/products/${productId}/toggle-status`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || '상태 변경에 실패했습니다.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('상태 변경 중 오류가 발생했습니다.');
    });
}

function deleteProduct(productId) {
    if (!confirm('정말로 이 상품을 삭제하시겠습니까? 이 작업은 되돌릴 수 없습니다.')) {
        return;
    }
    
    fetch(`/admin/shop/products/${productId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('상품이 삭제되었습니다.');
            location.reload();
        } else {
            alert(data.message || '삭제에 실패했습니다.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('삭제 중 오류가 발생했습니다.');
    });
}
</script>
@endpush
@endsection