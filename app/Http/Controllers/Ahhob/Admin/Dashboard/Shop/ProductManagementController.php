<?php

namespace App\Http\Controllers\Ahhob\Admin\Dashboard\Shop;

use App\Http\Controllers\Controller;
use App\Models\Ahhob\Shop\Product;
use App\Models\Ahhob\Shop\Category;
use App\Models\Ahhob\Shop\Coupon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class ProductManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    /*
    |--------------------------------------------------------------------------
    | 상품 관리 (Product Management)
    |--------------------------------------------------------------------------
    */

    /**
     * 상품 목록
     */
    public function index(Request $request): View
    {
        $query = Product::with(['category', 'attachments']);

        // 검색 조건
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // 카테고리 필터
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->get('category_id'));
        }

        // 상태 필터
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        // 재고 상태 필터
        if ($request->filled('stock_status')) {
            $query->where('stock_status', $request->get('stock_status'));
        }

        // 가격 범위 필터
        if ($request->filled('price_min')) {
            $query->where('price', '>=', $request->get('price_min'));
        }
        if ($request->filled('price_max')) {
            $query->where('price', '<=', $request->get('price_max'));
        }

        // 정렬
        $sortBy = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $products = $query->paginate(20);
        $categories = Category::orderBy('sort_order')->get();

        return view('ahhob.admin.dashboard.shop.products.index', compact('products', 'categories'));
    }

    /**
     * 상품 생성 폼
     */
    public function create(): View
    {
        $categories = Category::orderBy('sort_order')->get();
        
        return view('ahhob.admin.dashboard.shop.products.create', compact('categories'));
    }

    /**
     * 상품 생성
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100|unique:shop_products,sku',
            'category_id' => 'required|exists:shop_categories,id',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|string|max:100',
            'status' => 'required|in:active,inactive,draft',
            'is_featured' => 'boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        DB::beginTransaction();
        try {
            $product = Product::create($request->except(['images']));

            // 이미지 업로드 처리
            if ($request->hasFile('images')) {
                $this->handleImageUploads($product, $request->file('images'));
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '상품이 생성되었습니다.',
                'product_id' => $product->id,
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => '상품 생성 중 오류가 발생했습니다.',
            ], 500);
        }
    }

    /**
     * 상품 상세보기
     */
    public function show(Product $product): View
    {
        $product->load(['category', 'attachments', 'reviews.user', 'orderItems.order']);
        
        // 상품 통계
        $stats = $this->getProductStats($product);
        
        // 최근 주문 내역
        $recentOrders = $product->orderItems()
            ->with('order.user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('ahhob.admin.dashboard.shop.products.show', compact('product', 'stats', 'recentOrders'));
    }

    /**
     * 상품 수정 폼
     */
    public function edit(Product $product): View
    {
        $product->load(['category', 'attachments']);
        $categories = Category::orderBy('sort_order')->get();
        
        return view('ahhob.admin.dashboard.shop.products.edit', compact('product', 'categories'));
    }

    /**
     * 상품 업데이트
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100|unique:shop_products,sku,' . $product->id,
            'category_id' => 'required|exists:shop_categories,id',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|string|max:100',
            'status' => 'required|in:active,inactive,draft',
            'is_featured' => 'boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'new_images' => 'nullable|array',
            'new_images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'remove_images' => 'nullable|array',
            'remove_images.*' => 'integer|exists:attachments,id',
        ]);

        DB::beginTransaction();
        try {
            $product->update($request->except(['new_images', 'remove_images']));

            // 기존 이미지 삭제
            if ($request->has('remove_images')) {
                $this->removeImages($product, $request->remove_images);
            }

            // 새 이미지 업로드
            if ($request->hasFile('new_images')) {
                $this->handleImageUploads($product, $request->file('new_images'));
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '상품이 업데이트되었습니다.',
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => '상품 업데이트 중 오류가 발생했습니다.',
            ], 500);
        }
    }

    /**
     * 상품 삭제
     */
    public function destroy(Product $product): JsonResponse
    {
        try {
            // 관련 이미지 파일 삭제
            foreach ($product->attachments as $attachment) {
                Storage::disk('public')->delete($attachment->file_path);
            }

            $product->delete();

            return response()->json([
                'success' => true,
                'message' => '상품이 삭제되었습니다.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '상품 삭제 중 오류가 발생했습니다.',
            ], 500);
        }
    }

    /**
     * 대량 작업
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'action' => 'required|in:activate,deactivate,delete,feature,unfeature',
            'product_ids' => 'required|array',
            'product_ids.*' => 'integer|exists:shop_products,id',
        ]);

        $productIds = $request->product_ids;
        $action = $request->action;

        try {
            switch ($action) {
                case 'activate':
                    Product::whereIn('id', $productIds)->update(['status' => 'active']);
                    $message = '선택된 상품이 활성화되었습니다.';
                    break;

                case 'deactivate':
                    Product::whereIn('id', $productIds)->update(['status' => 'inactive']);
                    $message = '선택된 상품이 비활성화되었습니다.';
                    break;

                case 'feature':
                    Product::whereIn('id', $productIds)->update(['is_featured' => true]);
                    $message = '선택된 상품이 추천상품으로 설정되었습니다.';
                    break;

                case 'unfeature':
                    Product::whereIn('id', $productIds)->update(['is_featured' => false]);
                    $message = '선택된 상품의 추천상품 설정이 해제되었습니다.';
                    break;

                case 'delete':
                    $products = Product::whereIn('id', $productIds)->get();
                    foreach ($products as $product) {
                        // 관련 이미지 파일 삭제
                        foreach ($product->attachments as $attachment) {
                            Storage::disk('public')->delete($attachment->file_path);
                        }
                        $product->delete();
                    }
                    $message = '선택된 상품이 삭제되었습니다.';
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => $message,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '대량 작업 처리 중 오류가 발생했습니다.',
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 재고 관리 (Inventory Management)
    |--------------------------------------------------------------------------
    */

    /**
     * 상품 재고 상세
     */
    public function inventory(Product $product): View
    {
        $product->load('options');
        
        // 재고 히스토리 (추후 구현)
        $stockHistory = [];
        
        return view('ahhob.admin.dashboard.shop.products.inventory', compact('product', 'stockHistory'));
    }

    /**
     * 재고 업데이트
     */
    public function updateInventory(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'stock_quantity' => 'required|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'reason' => 'nullable|string|max:255',
        ]);

        $oldQuantity = $product->stock_quantity;
        $newQuantity = $request->stock_quantity;

        $product->update([
            'stock_quantity' => $newQuantity,
            'low_stock_threshold' => $request->low_stock_threshold,
            'stock_status' => $this->determineStockStatus($newQuantity, $request->low_stock_threshold),
        ]);

        // 재고 변동 기록 (추후 구현)
        // $this->recordStockHistory($product, $oldQuantity, $newQuantity, $request->reason);

        return response()->json([
            'success' => true,
            'message' => '재고가 업데이트되었습니다.',
            'old_quantity' => $oldQuantity,
            'new_quantity' => $newQuantity,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 카테고리 관리 (Category Management)
    |--------------------------------------------------------------------------
    */

    /**
     * 카테고리 목록
     */
    public function categories(): View
    {
        $categories = Category::withCount('products')
            ->orderBy('sort_order')
            ->get();

        return view('ahhob.admin.dashboard.shop.products.categories', compact('categories'));
    }

    /**
     * 카테고리 생성
     */
    public function storeCategory(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'required|string|max:100|unique:shop_categories,slug',
            'description' => 'nullable|string|max:255',
            'parent_id' => 'nullable|exists:shop_categories,id',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        $category = Category::create($request->all());

        return response()->json([
            'success' => true,
            'message' => '카테고리가 생성되었습니다.',
            'category' => $category,
        ]);
    }

    /**
     * 카테고리 업데이트
     */
    public function updateCategory(Request $request, Category $category): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'required|string|max:100|unique:shop_categories,slug,' . $category->id,
            'description' => 'nullable|string|max:255',
            'parent_id' => 'nullable|exists:shop_categories,id',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        $category->update($request->all());

        return response()->json([
            'success' => true,
            'message' => '카테고리가 업데이트되었습니다.',
        ]);
    }

    /**
     * 카테고리 삭제
     */
    public function destroyCategory(Category $category): JsonResponse
    {
        // 하위 카테고리나 상품이 있는지 확인
        if ($category->children()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => '하위 카테고리가 있는 카테고리는 삭제할 수 없습니다.',
            ], 422);
        }

        if ($category->products()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => '상품이 등록된 카테고리는 삭제할 수 없습니다.',
            ], 422);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => '카테고리가 삭제되었습니다.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 통계 (Statistics)
    |--------------------------------------------------------------------------
    */

    /**
     * 상품 통계
     */
    public function productStatistics(): View
    {
        $stats = Cache::remember('admin.product.statistics', 1800, function () {
            return [
                'overview' => $this->getProductOverview(),
                'category_stats' => $this->getCategoryStats(),
                'inventory_alerts' => $this->getInventoryAlerts(),
                'performance_metrics' => $this->getPerformanceMetrics(),
            ];
        });

        return view('ahhob.admin.dashboard.shop.products.statistics', compact('stats'));
    }

    /**
     * 재고 통계
     */
    public function inventoryStatistics(): View
    {
        $inventoryStats = Cache::remember('admin.inventory.statistics', 1800, function () {
            return [
                'low_stock_products' => Product::where('stock_status', 'low_stock')->count(),
                'out_of_stock_products' => Product::where('stock_status', 'out_of_stock')->count(),
                'total_inventory_value' => Product::sum(DB::raw('stock_quantity * cost_price')),
                'top_selling_products' => $this->getTopSellingProducts(),
                'inventory_turnover' => $this->calculateInventoryTurnover(),
            ];
        });

        return view('ahhob.admin.dashboard.shop.products.inventory-statistics', compact('inventoryStats'));
    }

    /*
    |--------------------------------------------------------------------------
    | 헬퍼 메서드 (Helper Methods)
    |--------------------------------------------------------------------------
    */

    /**
     * 이미지 업로드 처리
     */
    private function handleImageUploads(Product $product, array $images): void
    {
        foreach ($images as $index => $image) {
            $filename = time() . '_' . $index . '.' . $image->getClientOriginalExtension();
            $path = $image->storeAs('products', $filename, 'public');

            // 썸네일 생성
            $thumbnailPath = 'products/thumbs/' . $filename;
            $thumbnailFullPath = storage_path('app/public/' . $thumbnailPath);
            
            if (!file_exists(dirname($thumbnailFullPath))) {
                mkdir(dirname($thumbnailFullPath), 0755, true);
            }

            Image::make($image)
                ->fit(300, 300)
                ->save($thumbnailFullPath);

            // 첨부파일 레코드 생성
            $product->attachments()->create([
                'file_name' => $image->getClientOriginalName(),
                'file_path' => $path,
                'file_size' => $image->getSize(),
                'mime_type' => $image->getMimeType(),
                'sort_order' => $index,
            ]);
        }
    }

    /**
     * 이미지 삭제
     */
    private function removeImages(Product $product, array $imageIds): void
    {
        $attachments = $product->attachments()->whereIn('id', $imageIds)->get();
        
        foreach ($attachments as $attachment) {
            // 파일 삭제
            Storage::disk('public')->delete($attachment->file_path);
            
            // 썸네일 삭제
            $thumbnailPath = 'products/thumbs/' . basename($attachment->file_path);
            Storage::disk('public')->delete($thumbnailPath);
            
            // 레코드 삭제
            $attachment->delete();
        }
    }

    /**
     * 재고 상태 결정
     */
    private function determineStockStatus(int $quantity, ?int $threshold = null): string
    {
        if ($quantity <= 0) {
            return 'out_of_stock';
        }
        
        if ($threshold && $quantity <= $threshold) {
            return 'low_stock';
        }
        
        return 'in_stock';
    }

    /**
     * 상품 통계 조회
     */
    private function getProductStats(Product $product): array
    {
        return [
            'total_sales' => $product->sales_count ?? 0,
            'total_revenue' => $product->orderItems()->sum(DB::raw('quantity * price')),
            'average_rating' => $product->average_rating ?? 0,
            'total_reviews' => $product->reviews_count ?? 0,
            'view_count' => $product->view_count ?? 0,
            'conversion_rate' => $this->calculateConversionRate($product),
        ];
    }

    /**
     * 상품 개요 통계
     */
    private function getProductOverview(): array
    {
        return [
            'total_products' => Product::count(),
            'active_products' => Product::where('status', 'active')->count(),
            'featured_products' => Product::where('is_featured', true)->count(),
            'draft_products' => Product::where('status', 'draft')->count(),
            'out_of_stock' => Product::where('stock_status', 'out_of_stock')->count(),
            'low_stock' => Product::where('stock_status', 'low_stock')->count(),
        ];
    }

    /**
     * 카테고리 통계
     */
    private function getCategoryStats(): array
    {
        return Category::withCount('products')
            ->orderBy('products_count', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * 재고 알림
     */
    private function getInventoryAlerts(): array
    {
        return [
            'low_stock' => Product::where('stock_status', 'low_stock')->limit(10)->get(),
            'out_of_stock' => Product::where('stock_status', 'out_of_stock')->limit(10)->get(),
        ];
    }

    /**
     * 성능 지표
     */
    private function getPerformanceMetrics(): array
    {
        // 추후 구현: 판매 성과, 전환율 등
        return [
            'best_sellers' => [],
            'worst_performers' => [],
            'trending_products' => [],
        ];
    }

    /**
     * 최고 판매 상품
     */
    private function getTopSellingProducts(): array
    {
        return Product::orderBy('sales_count', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * 재고 회전율 계산
     */
    private function calculateInventoryTurnover(): float
    {
        // 추후 구현: 재고 회전율 계산 로직
        return 0.0;
    }

    /**
     * 전환율 계산
     */
    private function calculateConversionRate(Product $product): float
    {
        $views = $product->view_count ?? 0;
        $sales = $product->sales_count ?? 0;
        
        return $views > 0 ? round(($sales / $views) * 100, 2) : 0;
    }
}