<?php

namespace App\Services\Shop;

use App\Models\Ahhob\Shop\Review;
use App\Models\Ahhob\Shop\Product;
use App\Models\Ahhob\Shop\Order;
use App\Models\Ahhob\Shop\UserProductPreference;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReviewService
{
    /*
    |--------------------------------------------------------------------------
    | 리뷰 작성 및 관리 (Review Creation & Management)
    |--------------------------------------------------------------------------
    */
    // region --- 리뷰 작성 및 관리 (Review Creation & Management) ---

    /**
     * 리뷰 작성
     */
    public function createReview(array $reviewData): Review
    {
        $userId = $reviewData['user_id'] ?? auth()->id();
        $productId = $reviewData['product_id'];

        // 구매 이력 확인
        if (!$this->hasPurchaseHistory($userId, $productId)) {
            throw new \InvalidArgumentException('구매한 상품만 리뷰를 작성할 수 있습니다.');
        }

        // 중복 리뷰 확인
        if ($this->hasExistingReview($userId, $productId)) {
            throw new \InvalidArgumentException('이미 리뷰를 작성한 상품입니다.');
        }

        DB::beginTransaction();
        try {
            // 리뷰 생성
            $review = Review::create([
                'user_id' => $userId,
                'product_id' => $productId,
                'order_id' => $reviewData['order_id'] ?? null,
                'rating' => $reviewData['rating'],
                'title' => $reviewData['title'] ?? null,
                'content' => $reviewData['content'],
                'is_verified_purchase' => true,
                'is_published' => $reviewData['is_published'] ?? true,
            ]);

            // 상품 평점 업데이트
            $this->updateProductRating($productId);

            // 사용자 선호도 기록
            UserProductPreference::recordPreference(
                $userId,
                $productId,
                null,
                'review',
                0.5
            );

            DB::commit();
            return $review->fresh(['user', 'product']);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 리뷰 수정
     */
    public function updateReview(int $reviewId, array $reviewData): Review
    {
        $review = Review::findOrFail($reviewId);

        // 작성자 확인
        if ($review->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            throw new \InvalidArgumentException('리뷰를 수정할 권한이 없습니다.');
        }

        DB::beginTransaction();
        try {
            $review->update([
                'rating' => $reviewData['rating'] ?? $review->rating,
                'title' => $reviewData['title'] ?? $review->title,
                'content' => $reviewData['content'] ?? $review->content,
                'is_published' => $reviewData['is_published'] ?? $review->is_published,
            ]);

            // 평점이 변경된 경우 상품 평점 업데이트
            if (isset($reviewData['rating']) && $reviewData['rating'] != $review->getOriginal('rating')) {
                $this->updateProductRating($review->product_id);
            }

            DB::commit();
            return $review->fresh(['user', 'product']);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 리뷰 삭제
     */
    public function deleteReview(int $reviewId): bool
    {
        $review = Review::findOrFail($reviewId);

        // 작성자 확인
        if ($review->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            throw new \InvalidArgumentException('리뷰를 삭제할 권한이 없습니다.');
        }

        DB::beginTransaction();
        try {
            $productId = $review->product_id;
            $review->delete();

            // 상품 평점 업데이트
            $this->updateProductRating($productId);

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 리뷰 조회 기능 (Review Retrieval)
    |--------------------------------------------------------------------------
    */
    // region --- 리뷰 조회 기능 (Review Retrieval) ---

    /**
     * 상품 리뷰 목록
     */
    public function getProductReviews(int $productId, array $filters = [], int $perPage = 20)
    {
        $query = Review::with(['user'])
            ->where('product_id', $productId)
            ->where('is_published', true)
            ->orderBy('created_at', 'desc');

        // 필터 적용
        if (isset($filters['rating'])) {
            $query->where('rating', $filters['rating']);
        }

        if (isset($filters['verified_only']) && $filters['verified_only']) {
            $query->where('is_verified_purchase', true);
        }

        if (isset($filters['sort'])) {
            switch ($filters['sort']) {
                case 'rating_desc':
                    $query->orderBy('rating', 'desc');
                    break;
                case 'rating_asc':
                    $query->orderBy('rating', 'asc');
                    break;
                case 'helpful':
                    $query->orderBy('helpful_count', 'desc');
                    break;
                case 'newest':
                    $query->orderBy('created_at', 'desc');
                    break;
                case 'oldest':
                    $query->orderBy('created_at', 'asc');
                    break;
            }
        }

        return $query->paginate($perPage);
    }

    /**
     * 사용자 리뷰 목록
     */
    public function getUserReviews(int $userId, int $perPage = 20)
    {
        return Review::with(['product'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * 리뷰 상세 조회
     */
    public function getReview(int $reviewId): Review
    {
        return Review::with(['user', 'product'])
            ->findOrFail($reviewId);
    }

    /**
     * 리뷰 통계
     */
    public function getReviewStatistics(int $productId): array
    {
        $reviews = Review::where('product_id', $productId)
            ->where('is_published', true)
            ->get();

        $totalReviews = $reviews->count();
        $averageRating = $totalReviews > 0 ? $reviews->avg('rating') : 0;

        $ratingDistribution = [];
        for ($i = 1; $i <= 5; $i++) {
            $count = $reviews->where('rating', $i)->count();
            $ratingDistribution[$i] = [
                'count' => $count,
                'percentage' => $totalReviews > 0 ? round(($count / $totalReviews) * 100, 1) : 0,
            ];
        }

        return [
            'total_reviews' => $totalReviews,
            'average_rating' => round($averageRating, 1),
            'rating_distribution' => $ratingDistribution,
            'verified_purchase_count' => $reviews->where('is_verified_purchase', true)->count(),
        ];
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 리뷰 상호작용 (Review Interactions)
    |--------------------------------------------------------------------------
    */
    // region --- 리뷰 상호작용 (Review Interactions) ---

    /**
     * 리뷰 도움됨 표시
     */
    public function markReviewHelpful(int $reviewId, int $userId): bool
    {
        $review = Review::findOrFail($reviewId);

        // 이미 도움됨을 표시했는지 확인 (별도 테이블 필요시 구현)
        // 여기서는 간단히 도움됨 카운트만 증가
        $review->increment('helpful_count');

        return true;
    }

    /**
     * 리뷰에 답글 작성 (관리자)
     */
    public function replyToReview(int $reviewId, string $replyContent): Review
    {
        if (!auth()->user()->isAdmin()) {
            throw new \InvalidArgumentException('관리자만 답글을 작성할 수 있습니다.');
        }

        $review = Review::findOrFail($reviewId);
        $review->update([
            'admin_reply' => $replyContent,
            'admin_reply_at' => now(),
            'admin_reply_by' => auth()->id(),
        ]);

        return $review->fresh();
    }

    /**
     * 리뷰 신고
     */
    public function reportReview(int $reviewId, int $userId, string $reason): bool
    {
        // 리뷰 신고 로직 구현 (별도 테이블 필요)
        logger()->info('Review reported', [
            'review_id' => $reviewId,
            'reported_by' => $userId,
            'reason' => $reason,
        ]);

        return true;
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 리뷰 분석 및 통계 (Review Analytics & Statistics)
    |--------------------------------------------------------------------------
    */
    // region --- 리뷰 분석 및 통계 (Review Analytics & Statistics) ---

    /**
     * 베스트 리뷰 목록
     */
    public function getBestReviews(int $limit = 10, int $days = 30): Collection
    {
        return Review::with(['user', 'product'])
            ->where('is_published', true)
            ->where('created_at', '>=', now()->subDays($days))
            ->where('rating', '>=', 4)
            ->orderBy('helpful_count', 'desc')
            ->orderBy('rating', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * 최근 리뷰 목록
     */
    public function getRecentReviews(int $limit = 10): Collection
    {
        return Review::with(['user', 'product'])
            ->where('is_published', true)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * 리뷰 작성 가능한 상품 목록
     */
    public function getReviewableProducts(int $userId): Collection
    {
        // 구매했지만 아직 리뷰를 작성하지 않은 상품들
        $purchasedProductIds = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.user_id', $userId)
            ->where('orders.status', 'completed')
            ->pluck('order_items.product_id')
            ->unique()
            ->toArray();

        $reviewedProductIds = Review::where('user_id', $userId)
            ->pluck('product_id')
            ->toArray();

        $reviewableProductIds = array_diff($purchasedProductIds, $reviewedProductIds);

        return Product::whereIn('id', $reviewableProductIds)
            ->where('is_active', true)
            ->get();
    }

    /**
     * 리뷰 작성률 통계
     */
    public function getReviewRateStatistics(int $days = 30): array
    {
        $totalOrders = Order::where('created_at', '>=', now()->subDays($days))
            ->where('status', 'completed')
            ->count();

        $reviewedOrders = Order::where('created_at', '>=', now()->subDays($days))
            ->where('status', 'completed')
            ->whereHas('reviews')
            ->count();

        $reviewRate = $totalOrders > 0 ? ($reviewedOrders / $totalOrders) * 100 : 0;

        return [
            'total_orders' => $totalOrders,
            'reviewed_orders' => $reviewedOrders,
            'review_rate' => round($reviewRate, 2),
            'period_days' => $days,
        ];
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 비공개 헬퍼 메서드 (Private Helper Methods)
    |--------------------------------------------------------------------------
    */
    // region --- 비공개 헬퍼 메서드 (Private Helper Methods) ---

    /**
     * 구매 이력 확인
     */
    private function hasPurchaseHistory(int $userId, int $productId): bool
    {
        return DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.user_id', $userId)
            ->where('order_items.product_id', $productId)
            ->where('orders.status', 'completed')
            ->exists();
    }

    /**
     * 기존 리뷰 확인
     */
    private function hasExistingReview(int $userId, int $productId): bool
    {
        return Review::where('user_id', $userId)
            ->where('product_id', $productId)
            ->exists();
    }

    /**
     * 상품 평점 업데이트
     */
    private function updateProductRating(int $productId): void
    {
        $averageRating = Review::where('product_id', $productId)
            ->where('is_published', true)
            ->avg('rating');

        $reviewCount = Review::where('product_id', $productId)
            ->where('is_published', true)
            ->count();

        Product::where('id', $productId)->update([
            'average_rating' => $averageRating ? round($averageRating, 1) : 0,
            'review_count' => $reviewCount,
        ]);
    }

    // endregion
}