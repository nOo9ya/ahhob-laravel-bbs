<?php

namespace App\Services\Shop;

use App\Models\Ahhob\Shop\Product;
use App\Models\Ahhob\Shop\ProductView;
use App\Models\Ahhob\Shop\ProductAssociation;
use App\Models\Ahhob\Shop\UserProductPreference;
use App\Models\Ahhob\Shop\Category;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ProductService
{
    /*
    |--------------------------------------------------------------------------
    | 상품 조회 및 검색 (Product Retrieval & Search)
    |--------------------------------------------------------------------------
    */
    // region --- 상품 조회 및 검색 (Product Retrieval & Search) ---

    /**
     * 상품 목록 조회
     */
    public function getProducts(array $filters = [], int $perPage = 20)
    {
        $query = Product::with(['category', 'tags', 'images'])
            ->where('is_active', true)
            ->orderBy('created_at', 'desc');

        // 필터 적용
        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['tag'])) {
            $query->whereHas('tags', function ($q) use ($filters) {
                $q->where('name', $filters['tag']);
            });
        }

        if (isset($filters['price_min'])) {
            $query->where('price', '>=', $filters['price_min']);
        }

        if (isset($filters['price_max'])) {
            $query->where('price', '<=', $filters['price_max']);
        }

        if (isset($filters['in_stock']) && $filters['in_stock']) {
            $query->where('stock_quantity', '>', 0);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'LIKE', '%' . $filters['search'] . '%')
                  ->orWhere('description', 'LIKE', '%' . $filters['search'] . '%')
                  ->orWhere('sku', 'LIKE', '%' . $filters['search'] . '%');
            });
        }

        // 정렬 적용
        if (isset($filters['sort'])) {
            switch ($filters['sort']) {
                case 'price_asc':
                    $query->orderBy('price', 'asc');
                    break;
                case 'price_desc':
                    $query->orderBy('price', 'desc');
                    break;
                case 'name':
                    $query->orderBy('name', 'asc');
                    break;
                case 'popular':
                    $query->orderBy('view_count', 'desc');
                    break;
                case 'newest':
                    $query->orderBy('created_at', 'desc');
                    break;
            }
        }

        return $query->paginate($perPage);
    }

    /**
     * 상품 상세 조회
     */
    public function getProduct(int $productId, bool $recordView = true): Product
    {
        $product = Product::with(['category', 'tags', 'images', 'attributes'])
            ->where('is_active', true)
            ->findOrFail($productId);

        // 조회수 기록
        if ($recordView) {
            $this->recordProductView($product);
        }

        return $product;
    }

    /**
     * 상품 검색
     */
    public function searchProducts(string $query, array $filters = [], int $perPage = 20)
    {
        $searchQuery = Product::with(['category', 'tags', 'images'])
            ->where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', '%' . $query . '%')
                  ->orWhere('description', 'LIKE', '%' . $query . '%')
                  ->orWhere('sku', 'LIKE', '%' . $query . '%');
            });

        // 필터 적용
        if (isset($filters['category_id'])) {
            $searchQuery->where('category_id', $filters['category_id']);
        }

        if (isset($filters['price_min'])) {
            $searchQuery->where('price', '>=', $filters['price_min']);
        }

        if (isset($filters['price_max'])) {
            $searchQuery->where('price', '<=', $filters['price_max']);
        }

        return $searchQuery->orderBy('name', 'asc')->paginate($perPage);
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 상품 추천 시스템 (Product Recommendation)
    |--------------------------------------------------------------------------
    */
    // region --- 상품 추천 시스템 (Product Recommendation) ---

    /**
     * 추천 상품 목록
     */
    public function getRecommendedProducts(int $productId, int $limit = 8): Collection
    {
        $cacheKey = "recommended_products_{$productId}_{$limit}";

        return Cache::remember($cacheKey, 3600, function () use ($productId, $limit) {
            // 1. 연관 상품 추천
            $associatedProducts = ProductAssociation::getAssociatedProducts($productId, null, 0.1, $limit);

            if ($associatedProducts->count() >= $limit) {
                return $associatedProducts;
            }

            // 2. 같은 카테고리 인기 상품
            $product = Product::find($productId);
            if ($product) {
                $categoryProducts = Product::where('category_id', $product->category_id)
                    ->where('id', '!=', $productId)
                    ->where('is_active', true)
                    ->orderBy('view_count', 'desc')
                    ->limit($limit - $associatedProducts->count())
                    ->get();

                $associatedProducts = $associatedProducts->merge($categoryProducts);
            }

            return $associatedProducts->take($limit);
        });
    }

    /**
     * 사용자 맞춤 추천 상품
     */
    public function getPersonalizedRecommendations(int $userId, int $limit = 12): Collection
    {
        $cacheKey = "personalized_recommendations_{$userId}_{$limit}";

        return Cache::remember($cacheKey, 1800, function () use ($userId, $limit) {
            // 사용자 선호도 기반 추천
            $preferences = UserProductPreference::where('user_id', $userId)
                ->where('preference_score', '>', 0.3)
                ->orderBy('preference_score', 'desc')
                ->limit(5)
                ->get();

            $recommendedProducts = collect();

            foreach ($preferences as $preference) {
                if ($preference->product_id) {
                    $related = $this->getRecommendedProducts($preference->product_id, 3);
                    $recommendedProducts = $recommendedProducts->merge($related);
                }

                if ($preference->category_id) {
                    $categoryProducts = Product::where('category_id', $preference->category_id)
                        ->where('is_active', true)
                        ->orderBy('view_count', 'desc')
                        ->limit(3)
                        ->get();
                    $recommendedProducts = $recommendedProducts->merge($categoryProducts);
                }
            }

            return $recommendedProducts->unique('id')->take($limit);
        });
    }

    /**
     * 인기 상품 목록
     */
    public function getPopularProducts(int $limit = 10, int $days = 30): Collection
    {
        $cacheKey = "popular_products_{$limit}_{$days}";

        return Cache::remember($cacheKey, 3600, function () use ($limit, $days) {
            return Product::with(['category', 'images'])
                ->where('is_active', true)
                ->where('created_at', '>=', now()->subDays($days))
                ->orderBy('view_count', 'desc')
                ->orderBy('sales_count', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * 신상품 목록
     */
    public function getNewProducts(int $limit = 10, int $days = 30): Collection
    {
        return Product::with(['category', 'images'])
            ->where('is_active', true)
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 상품 통계 및 분석 (Product Statistics & Analytics)
    |--------------------------------------------------------------------------
    */
    // region --- 상품 통계 및 분석 (Product Statistics & Analytics) ---

    /**
     * 상품 조회 기록
     */
    public function recordProductView(Product $product): void
    {
        // 조회수 증가
        $product->increment('view_count');

        // 상세 조회 기록
        ProductView::recordView(
            $product->id,
            auth()->id(),
            request()->ip(),
            request()->userAgent(),
            session()->getId()
        );

        // 사용자 선호도 기록
        if (auth()->check()) {
            UserProductPreference::recordPreference(
                auth()->id(),
                $product->id,
                $product->category_id,
                'view',
                0.1
            );
        }
    }

    /**
     * 상품 통계
     */
    public function getProductStatistics(int $productId, int $days = 30): array
    {
        $product = Product::findOrFail($productId);
        $startDate = now()->subDays($days);

        $dailyViews = ProductView::where('product_id', $productId)
            ->where('viewed_at', '>=', $startDate)
            ->selectRaw('DATE(viewed_at) as date, COUNT(*) as views')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $totalViews = ProductView::where('product_id', $productId)
            ->where('viewed_at', '>=', $startDate)
            ->count();

        $uniqueViewers = ProductView::where('product_id', $productId)
            ->where('viewed_at', '>=', $startDate)
            ->distinct('user_id')
            ->count('user_id');

        return [
            'product' => $product,
            'total_views' => $totalViews,
            'unique_viewers' => $uniqueViewers,
            'daily_views' => $dailyViews,
            'period_days' => $days,
        ];
    }

    /**
     * 카테고리별 상품 통계
     */
    public function getCategoryStatistics(int $categoryId = null): array
    {
        $query = Product::where('is_active', true);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $totalProducts = $query->count();
        $inStockProducts = $query->where('stock_quantity', '>', 0)->count();
        $averagePrice = $query->avg('price');
        $totalValue = $query->sum(DB::raw('price * stock_quantity'));

        return [
            'total_products' => $totalProducts,
            'in_stock_products' => $inStockProducts,
            'out_of_stock_products' => $totalProducts - $inStockProducts,
            'average_price' => round($averagePrice, 2),
            'total_inventory_value' => $totalValue,
            'stock_rate' => $totalProducts > 0 ? round(($inStockProducts / $totalProducts) * 100, 1) : 0,
        ];
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 상품 관리 기능 (Product Management)
    |--------------------------------------------------------------------------
    */
    // region --- 상품 관리 기능 (Product Management) ---

    /**
     * 상품 재고 업데이트
     */
    public function updateStock(int $productId, int $quantity, string $reason = ''): Product
    {
        $product = Product::findOrFail($productId);

        DB::beginTransaction();
        try {
            $oldStock = $product->stock_quantity;
            $product->update(['stock_quantity' => $quantity]);

            // 재고 변경 로그 (필요시 구현)
            logger()->info('Product stock updated', [
                'product_id' => $productId,
                'old_stock' => $oldStock,
                'new_stock' => $quantity,
                'reason' => $reason,
                'updated_by' => auth()->id(),
            ]);

            DB::commit();
            return $product->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 상품 활성화/비활성화
     */
    public function toggleProductStatus(int $productId): Product
    {
        $product = Product::findOrFail($productId);
        $product->update(['is_active' => !$product->is_active]);

        return $product->fresh();
    }

    /**
     * 품절 상품 목록
     */
    public function getOutOfStockProducts(): Collection
    {
        return Product::where('is_active', true)
            ->where('track_stock', true)
            ->where('stock_quantity', '<=', 0)
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    /**
     * 재고 부족 상품 목록
     */
    public function getLowStockProducts(int $threshold = 5): Collection
    {
        return Product::where('is_active', true)
            ->where('track_stock', true)
            ->where('stock_quantity', '>', 0)
            ->where('stock_quantity', '<=', $threshold)
            ->orderBy('stock_quantity', 'asc')
            ->get();
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 비공개 헬퍼 메서드 (Private Helper Methods)
    |--------------------------------------------------------------------------
    */
    // region --- 비공개 헬퍼 메서드 (Private Helper Methods) ---

    /**
     * 캐시 무효화
     */
    private function invalidateProductCache(int $productId): void
    {
        Cache::forget("recommended_products_{$productId}_8");
        Cache::forget("popular_products_10_30");
        Cache::tags(['products'])->flush();
    }

    // endregion
}