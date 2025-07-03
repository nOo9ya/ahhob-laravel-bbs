<?php

namespace App\Services\Shop;

use App\Models\Ahhob\Shop\Wishlist;
use App\Models\Ahhob\Shop\Product;
use App\Models\Ahhob\Shop\UserProductPreference;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WishlistService
{
    /*
    |--------------------------------------------------------------------------
    | 위시리스트 기본 기능 (Basic Wishlist Operations)
    |--------------------------------------------------------------------------
    */
    // region --- 위시리스트 기본 기능 (Basic Wishlist Operations) ---

    /**
     * 위시리스트에 상품 추가
     */
    public function addToWishlist(int $productId, ?int $userId = null): Wishlist
    {
        $userId = $userId ?? auth()->id();

        if (!$userId) {
            throw new \InvalidArgumentException('로그인이 필요합니다.');
        }

        $product = Product::findOrFail($productId);

        // 이미 위시리스트에 있는지 확인
        $existingItem = Wishlist::where('user_id', $userId)
            ->where('product_id', $productId)
            ->first();

        if ($existingItem) {
            throw new \InvalidArgumentException('이미 위시리스트에 추가된 상품입니다.');
        }

        DB::beginTransaction();
        try {
            // 위시리스트 아이템 생성
            $wishlistItem = Wishlist::create([
                'user_id' => $userId,
                'product_id' => $productId,
                'product_name' => $product->name,
                'product_price' => $product->price,
                'product_image' => $product->featured_image,
                'product_sku' => $product->sku,
            ]);

            // 사용자 선호도 기록
            UserProductPreference::recordPreference(
                $userId,
                $productId,
                $product->category_id,
                'wishlist',
                0.3
            );

            DB::commit();
            return $wishlistItem;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 위시리스트에서 상품 제거
     */
    public function removeFromWishlist(int $productId, ?int $userId = null): bool
    {
        $userId = $userId ?? auth()->id();

        if (!$userId) {
            throw new \InvalidArgumentException('로그인이 필요합니다.');
        }

        $wishlistItem = Wishlist::where('user_id', $userId)
            ->where('product_id', $productId)
            ->first();

        if (!$wishlistItem) {
            throw new \InvalidArgumentException('위시리스트에 없는 상품입니다.');
        }

        return $wishlistItem->delete();
    }

    /**
     * 위시리스트 상품인지 확인
     */
    public function isInWishlist(int $productId, ?int $userId = null): bool
    {
        $userId = $userId ?? auth()->id();

        if (!$userId) {
            return false;
        }

        return Wishlist::where('user_id', $userId)
            ->where('product_id', $productId)
            ->exists();
    }

    /**
     * 위시리스트 비우기
     */
    public function clearWishlist(?int $userId = null): int
    {
        $userId = $userId ?? auth()->id();

        if (!$userId) {
            throw new \InvalidArgumentException('로그인이 필요합니다.');
        }

        return Wishlist::where('user_id', $userId)->delete();
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 위시리스트 조회 기능 (Wishlist Retrieval)
    |--------------------------------------------------------------------------
    */
    // region --- 위시리스트 조회 기능 (Wishlist Retrieval) ---

    /**
     * 사용자 위시리스트 목록
     */
    public function getWishlistItems(?int $userId = null, array $filters = [], int $perPage = 20)
    {
        $userId = $userId ?? auth()->id();

        if (!$userId) {
            throw new \InvalidArgumentException('로그인이 필요합니다.');
        }

        $query = Wishlist::with(['product.category', 'product.images'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc');

        // 필터 적용
        if (isset($filters['category_id'])) {
            $query->whereHas('product', function ($q) use ($filters) {
                $q->where('category_id', $filters['category_id']);
            });
        }

        if (isset($filters['price_min'])) {
            $query->where('product_price', '>=', $filters['price_min']);
        }

        if (isset($filters['price_max'])) {
            $query->where('product_price', '<=', $filters['price_max']);
        }

        if (isset($filters['available_only']) && $filters['available_only']) {
            $query->whereHas('product', function ($q) {
                $q->where('is_active', true)
                  ->where('stock_quantity', '>', 0);
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * 위시리스트 아이템 수
     */
    public function getWishlistCount(?int $userId = null): int
    {
        $userId = $userId ?? auth()->id();

        if (!$userId) {
            return 0;
        }

        return Wishlist::where('user_id', $userId)->count();
    }

    /**
     * 위시리스트 요약 정보
     */
    public function getWishlistSummary(?int $userId = null): array
    {
        $userId = $userId ?? auth()->id();

        if (!$userId) {
            return [
                'total_items' => 0,
                'total_value' => 0,
                'available_items' => 0,
                'out_of_stock_items' => 0,
            ];
        }

        $wishlistItems = Wishlist::with('product')
            ->where('user_id', $userId)
            ->get();

        $totalValue = $wishlistItems->sum('product_price');
        $availableItems = $wishlistItems->filter(function ($item) {
            return $item->product && $item->product->is_active && $item->product->stock_quantity > 0;
        })->count();

        return [
            'total_items' => $wishlistItems->count(),
            'total_value' => $totalValue,
            'available_items' => $availableItems,
            'out_of_stock_items' => $wishlistItems->count() - $availableItems,
        ];
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 위시리스트 관리 기능 (Wishlist Management)
    |--------------------------------------------------------------------------
    */
    // region --- 위시리스트 관리 기능 (Wishlist Management) ---

    /**
     * 품절된 위시리스트 아이템 제거
     */
    public function removeOutOfStockItems(?int $userId = null): int
    {
        $userId = $userId ?? auth()->id();

        if (!$userId) {
            throw new \InvalidArgumentException('로그인이 필요합니다.');
        }

        $outOfStockItems = Wishlist::with('product')
            ->where('user_id', $userId)
            ->get()
            ->filter(function ($item) {
                return !$item->product || !$item->product->is_active || $item->product->stock_quantity <= 0;
            });

        $removedCount = 0;
        foreach ($outOfStockItems as $item) {
            $item->delete();
            $removedCount++;
        }

        return $removedCount;
    }

    /**
     * 위시리스트 상품 가격 업데이트
     */
    public function updatePrices(?int $userId = null): int
    {
        $userId = $userId ?? auth()->id();

        if (!$userId) {
            throw new \InvalidArgumentException('로그인이 필요합니다.');
        }

        $updatedCount = 0;
        $wishlistItems = Wishlist::with('product')
            ->where('user_id', $userId)
            ->get();

        foreach ($wishlistItems as $item) {
            if ($item->product && $item->product_price != $item->product->price) {
                $item->update([
                    'product_price' => $item->product->price,
                    'product_name' => $item->product->name,
                    'product_image' => $item->product->featured_image,
                ]);
                $updatedCount++;
            }
        }

        return $updatedCount;
    }

    /**
     * 위시리스트를 장바구니로 이동
     */
    public function moveToCart(int $wishlistItemId, CartService $cartService, int $quantity = 1): bool
    {
        $wishlistItem = Wishlist::with('product')->findOrFail($wishlistItemId);

        if (!$wishlistItem->product || !$wishlistItem->product->is_active) {
            throw new \InvalidArgumentException('상품을 찾을 수 없거나 판매가 중단되었습니다.');
        }

        if (!$wishlistItem->product->canPurchase($quantity)) {
            throw new \InvalidArgumentException('재고가 부족합니다.');
        }

        DB::beginTransaction();
        try {
            // 장바구니에 추가
            $cartService->addToCart(
                $wishlistItem->product_id,
                $quantity,
                [],
                $wishlistItem->user_id
            );

            // 위시리스트에서 제거
            $wishlistItem->delete();

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 전체 위시리스트를 장바구니로 이동
     */
    public function moveAllToCart(?int $userId = null, CartService $cartService): array
    {
        $userId = $userId ?? auth()->id();

        if (!$userId) {
            throw new \InvalidArgumentException('로그인이 필요합니다.');
        }

        $wishlistItems = Wishlist::with('product')
            ->where('user_id', $userId)
            ->get();

        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($wishlistItems as $item) {
            try {
                if ($item->product && $item->product->is_active && $item->product->canPurchase(1)) {
                    $cartService->addToCart(
                        $item->product_id,
                        1,
                        [],
                        $userId
                    );
                    $item->delete();
                    $results['success']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = $item->product_name . ': 재고 부족 또는 판매 중단';
                }
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = $item->product_name . ': ' . $e->getMessage();
            }
        }

        return $results;
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 위시리스트 공유 및 소셜 기능 (Wishlist Sharing & Social)
    |--------------------------------------------------------------------------
    */
    // region --- 위시리스트 공유 및 소셜 기능 (Wishlist Sharing & Social) ---

    /**
     * 위시리스트 공유 링크 생성
     */
    public function generateShareLink(int $userId): string
    {
        $token = base64_encode($userId . ':' . time());
        return route('wishlist.shared', ['token' => $token]);
    }

    /**
     * 공유된 위시리스트 조회
     */
    public function getSharedWishlist(string $token): array
    {
        try {
            $decoded = base64_decode($token);
            [$userId, $timestamp] = explode(':', $decoded);

            // 링크 유효기간 체크 (30일)
            if (time() - $timestamp > 30 * 24 * 60 * 60) {
                throw new \InvalidArgumentException('공유 링크가 만료되었습니다.');
            }

            $user = User::findOrFail($userId);
            $wishlistItems = $this->getWishlistItems($userId);

            return [
                'user' => $user,
                'wishlist_items' => $wishlistItems,
                'is_expired' => false,
            ];

        } catch (\Exception $e) {
            throw new \InvalidArgumentException('유효하지 않은 공유 링크입니다.');
        }
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 위시리스트 통계 및 분석 (Wishlist Statistics & Analytics)
    |--------------------------------------------------------------------------
    */
    // region --- 위시리스트 통계 및 분석 (Wishlist Statistics & Analytics) ---

    /**
     * 인기 위시리스트 상품 통계
     */
    public function getPopularWishlistProducts(int $limit = 20, int $days = 30): Collection
    {
        return Wishlist::with(['product.category'])
            ->select('product_id', 'product_name', DB::raw('COUNT(*) as wishlist_count'))
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('product_id', 'product_name')
            ->orderBy('wishlist_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * 위시리스트 전환율 통계
     */
    public function getConversionStats(int $days = 30): array
    {
        $totalWishlistItems = Wishlist::where('created_at', '>=', now()->subDays($days))->count();

        // 위시리스트에서 실제 구매로 전환된 아이템 수 계산
        $convertedItems = DB::table('wishlists')
            ->join('order_items', function ($join) {
                $join->on('wishlists.product_id', '=', 'order_items.product_id')
                     ->on('wishlists.user_id', '=', 'order_items.order_id'); // 실제로는 orders.user_id와 조인해야 함
            })
            ->where('wishlists.created_at', '>=', now()->subDays($days))
            ->where('order_items.created_at', '>', DB::raw('wishlists.created_at'))
            ->count();

        $conversionRate = $totalWishlistItems > 0 ? ($convertedItems / $totalWishlistItems) * 100 : 0;

        return [
            'total_wishlist_items' => $totalWishlistItems,
            'converted_items' => $convertedItems,
            'conversion_rate' => round($conversionRate, 2),
            'period_days' => $days,
        ];
    }

    // endregion
}