<?php

namespace App\Services\Shop;

use App\Models\Ahhob\Shop\Product;
use App\Models\Ahhob\Shop\ProductView;
use App\Models\Ahhob\Shop\ProductAssociation;
use App\Models\Ahhob\Shop\UserProductPreference;
use App\Models\Ahhob\Shop\UserRecommendation;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProductRecommendationService
{
    /**
     * 사용자 맞춤 추천 상품
     */
    public function getPersonalizedRecommendations(User $user, int $limit = 10): Collection
    {
        $cacheKey = "user_recommendations_{$user->id}_{$limit}";
        
        return Cache::remember($cacheKey, 3600, function () use ($user, $limit) {
            // 캐시된 추천 결과 조회
            $cachedRecommendations = UserRecommendation::where('user_id', $user->id)
                ->where('recommendation_type', 'for_you')
                ->where('expires_at', '>', now())
                ->orderBy('score', 'desc')
                ->limit($limit)
                ->with('product')
                ->get();

            if ($cachedRecommendations->count() >= $limit) {
                return $cachedRecommendations->pluck('product');
            }

            // 새로운 추천 생성
            return $this->generatePersonalizedRecommendations($user, $limit);
        });
    }

    /**
     * 개인화 추천 생성
     */
    private function generatePersonalizedRecommendations(User $user, int $limit): Collection
    {
        $recommendations = collect();

        // 1. 협업 필터링 기반 추천
        $collaborativeRecs = $this->getCollaborativeFilteringRecommendations($user, $limit);
        $recommendations = $recommendations->merge($collaborativeRecs);

        // 2. 콘텐츠 기반 추천
        $contentBasedRecs = $this->getContentBasedRecommendations($user, $limit);
        $recommendations = $recommendations->merge($contentBasedRecs);

        // 3. 인기 상품 기반 추천
        $popularRecs = $this->getPopularRecommendations($user, $limit);
        $recommendations = $recommendations->merge($popularRecs);

        // 중복 제거 및 점수 기반 정렬
        $uniqueRecommendations = $recommendations
            ->unique('id')
            ->sortByDesc('recommendation_score')
            ->take($limit);

        // 추천 결과 캐시에 저장
        $this->cacheUserRecommendations($user, $uniqueRecommendations, 'for_you');

        return $uniqueRecommendations;
    }

    /**
     * 협업 필터링 기반 추천
     */
    private function getCollaborativeFilteringRecommendations(User $user, int $limit): Collection
    {
        // 사용자와 유사한 구매 패턴을 가진 다른 사용자들 찾기
        $similarUsers = $this->findSimilarUsers($user, 20);

        if ($similarUsers->isEmpty()) {
            return collect();
        }

        // 유사 사용자들이 좋아하는 상품들 조회
        $recommendations = UserProductPreference::whereIn('user_id', $similarUsers->pluck('id'))
            ->where('preference_score', '>', 0.5)
            ->whereNotIn('product_id', $this->getUserPurchasedProducts($user))
            ->with('product')
            ->orderBy('preference_score', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($preference) {
                $product = $preference->product;
                $product->recommendation_score = $preference->preference_score * 0.8; // 협업 필터링 가중치
                $product->recommendation_reason = '비슷한 취향의 사용자들이 선호하는 상품';
                return $product;
            });

        return $recommendations;
    }

    /**
     * 콘텐츠 기반 추천
     */
    private function getContentBasedRecommendations(User $user, int $limit): Collection
    {
        // 사용자가 최근에 본 상품들의 카테고리와 유사한 상품들
        $recentViews = ProductView::where('user_id', $user->id)
            ->where('created_at', '>', now()->subDays(30))
            ->with('product.category')
            ->latest()
            ->limit(10)
            ->get();

        if ($recentViews->isEmpty()) {
            return collect();
        }

        $categoryIds = $recentViews->pluck('product.category_id')->unique();
        
        $recommendations = Product::whereIn('category_id', $categoryIds)
            ->whereNotIn('id', $recentViews->pluck('product_id'))
            ->whereNotIn('id', $this->getUserPurchasedProducts($user))
            ->where('status', 'active')
            ->where('stock_quantity', '>', 0)
            ->orderBy('average_rating', 'desc')
            ->orderBy('sales_count', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($product) {
                $product->recommendation_score = 0.7; // 콘텐츠 기반 가중치
                $product->recommendation_reason = '최근 본 상품과 유사한 카테고리';
                return $product;
            });

        return $recommendations;
    }

    /**
     * 인기 상품 기반 추천
     */
    private function getPopularRecommendations(User $user, int $limit): Collection
    {
        $recommendations = Product::where('status', 'active')
            ->where('stock_quantity', '>', 0)
            ->whereNotIn('id', $this->getUserPurchasedProducts($user))
            ->where('created_at', '>', now()->subDays(30)) // 최근 30일 신규 상품
            ->orderBy('sales_count', 'desc')
            ->orderBy('average_rating', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($product) {
                $product->recommendation_score = 0.6; // 인기 상품 가중치
                $product->recommendation_reason = '최근 인기 상품';
                return $product;
            });

        return $recommendations;
    }

    /**
     * 상품과 함께 구매된 상품들
     */
    public function getFrequentlyBoughtTogether(Product $product, int $limit = 5): Collection
    {
        return ProductAssociation::where('product_a_id', $product->id)
            ->where('association_type', 'bought_together')
            ->orderBy('association_score', 'desc')
            ->limit($limit)
            ->with('productB')
            ->get()
            ->pluck('productB');
    }

    /**
     * 유사한 상품들
     */
    public function getSimilarProducts(Product $product, int $limit = 8): Collection
    {
        $cacheKey = "similar_products_{$product->id}_{$limit}";
        
        return Cache::remember($cacheKey, 7200, function () use ($product, $limit) {
            // 같은 카테고리의 유사한 상품들
            $similarProducts = Product::where('category_id', $product->category_id)
                ->where('id', '!=', $product->id)
                ->where('status', 'active')
                ->where('stock_quantity', '>', 0)
                ->orderBy('average_rating', 'desc')
                ->orderBy('sales_count', 'desc')
                ->limit($limit)
                ->get();

            // 연관 상품들도 포함
            $associatedProducts = ProductAssociation::where('product_a_id', $product->id)
                ->where('association_type', 'similar')
                ->orderBy('association_score', 'desc')
                ->limit($limit)
                ->with('productB')
                ->get()
                ->pluck('productB');

            return $similarProducts->merge($associatedProducts)
                ->unique('id')
                ->take($limit);
        });
    }

    /**
     * 카테고리별 트렌딩 상품
     */
    public function getCategoryTrendingProducts(int $categoryId, int $limit = 10): Collection
    {
        $cacheKey = "category_trending_{$categoryId}_{$limit}";
        
        return Cache::remember($cacheKey, 1800, function () use ($categoryId, $limit) {
            return Product::where('category_id', $categoryId)
                ->where('status', 'active')
                ->where('stock_quantity', '>', 0)
                ->where('created_at', '>', now()->subDays(7)) // 최근 7일간 생성된 상품
                ->orderByRaw('sales_count * 0.3 + average_rating * 0.7 DESC') // 가중치 점수
                ->limit($limit)
                ->get();
        });
    }

    /**
     * 상품 조회 기록
     */
    public function recordProductView(Product $product, ?User $user = null, ?string $sessionId = null): void
    {
        ProductView::create([
            'user_id' => $user?->id,
            'product_id' => $product->id,
            'session_id' => $sessionId ?? session()->getId(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // 상품 조회수 증가
        $product->increment('view_count');

        // 사용자별 선호도 업데이트
        if ($user) {
            $this->updateUserPreference($user, $product, 'view');
        }
    }

    /**
     * 사용자 선호도 업데이트
     */
    public function updateUserPreference(User $user, Product $product, string $action, array $data = []): void
    {
        $preference = UserProductPreference::firstOrCreate([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        switch ($action) {
            case 'view':
                $preference->increment('view_count');
                $preference->preference_score = min(1.0, $preference->preference_score + 0.01);
                break;
                
            case 'cart_add':
                $preference->increment('cart_add_count');
                $preference->preference_score = min(1.0, $preference->preference_score + 0.05);
                break;
                
            case 'purchase':
                $preference->increment('purchase_count');
                $preference->preference_score = min(1.0, $preference->preference_score + 0.2);
                break;
                
            case 'wishlist':
                $preference->is_wishlisted = true;
                $preference->preference_score = min(1.0, $preference->preference_score + 0.1);
                break;
                
            case 'review':
                $preference->is_reviewed = true;
                $preference->review_rating = $data['rating'] ?? null;
                $preference->preference_score = min(1.0, $preference->preference_score + 0.15);
                break;
        }

        $preference->save();
    }

    /**
     * 상품 연관 관계 업데이트
     */
    public function updateProductAssociation(Product $productA, Product $productB, string $type = 'bought_together'): void
    {
        $association = ProductAssociation::firstOrCreate([
            'product_a_id' => $productA->id,
            'product_b_id' => $productB->id,
            'association_type' => $type,
        ]);

        $association->increment('association_count');
        
        // 연관도 점수 계산 (단순한 카운트 기반)
        $maxCount = ProductAssociation::where('association_type', $type)->max('association_count');
        $association->association_score = $maxCount > 0 ? $association->association_count / $maxCount : 0;
        $association->save();

        // 반대 방향도 추가
        $reverseAssociation = ProductAssociation::firstOrCreate([
            'product_a_id' => $productB->id,
            'product_b_id' => $productA->id,
            'association_type' => $type,
        ]);

        $reverseAssociation->increment('association_count');
        $reverseAssociation->association_score = $maxCount > 0 ? $reverseAssociation->association_count / $maxCount : 0;
        $reverseAssociation->save();
    }

    /**
     * 유사한 사용자 찾기
     */
    private function findSimilarUsers(User $user, int $limit): Collection
    {
        // 사용자의 구매 이력을 기반으로 유사한 사용자들 찾기
        $userProductIds = $this->getUserPurchasedProducts($user);
        
        if ($userProductIds->isEmpty()) {
            return collect();
        }

        return User::whereHas('orders.items', function ($query) use ($userProductIds) {
                $query->whereIn('product_id', $userProductIds);
            })
            ->where('id', '!=', $user->id)
            ->withCount(['orders as similarity_score' => function ($query) use ($userProductIds) {
                $query->whereHas('items', function ($q) use ($userProductIds) {
                    $q->whereIn('product_id', $userProductIds);
                });
            }])
            ->orderBy('similarity_score', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * 사용자가 구매한 상품 ID 목록
     */
    private function getUserPurchasedProducts(User $user): Collection
    {
        return $user->orders()
            ->whereIn('status', ['completed', 'delivered'])
            ->with('items')
            ->get()
            ->flatMap(function ($order) {
                return $order->items->pluck('product_id');
            })
            ->unique();
    }

    /**
     * 추천 결과를 캐시에 저장
     */
    private function cacheUserRecommendations(User $user, Collection $products, string $type): void
    {
        $expiresAt = now()->addHours(6); // 6시간 캐시

        foreach ($products as $index => $product) {
            UserRecommendation::updateOrCreate([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'recommendation_type' => $type,
            ], [
                'score' => $product->recommendation_score ?? (1 - $index * 0.1),
                'reason' => $product->recommendation_reason ?? '개인화 추천',
                'expires_at' => $expiresAt,
            ]);
        }
    }
}