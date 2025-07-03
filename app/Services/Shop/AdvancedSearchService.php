<?php

namespace App\Services\Shop;

use App\Models\Ahhob\Shop\Product;
use App\Models\Ahhob\Shop\SearchLog;
use App\Models\Ahhob\Shop\PopularKeyword;
use App\Models\Ahhob\Shop\SearchSuggestion;
use App\Models\Ahhob\Shop\ProductSearchIndex;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class AdvancedSearchService
{
    /**
     * 고급 검색 실행
     */
    public function search(array $params): array
    {
        $keyword = $params['keyword'] ?? '';
        $filters = $params['filters'] ?? [];
        $sortBy = $params['sort'] ?? 'relevance';
        $page = $params['page'] ?? 1;
        $perPage = $params['per_page'] ?? 20;
        $userId = $params['user_id'] ?? null;

        // 키워드 정규화
        $normalizedKeyword = $this->normalizeKeyword($keyword);

        // 검색 쿼리 빌드
        $query = $this->buildSearchQuery($normalizedKeyword, $filters, $sortBy);

        // 검색 실행
        $results = $query->paginate($perPage, ['*'], 'page', $page);

        // 검색 로그 기록
        $this->logSearch($keyword, $normalizedKeyword, $results->total(), $filters, $sortBy, $userId);

        // 인기 검색어 업데이트
        if (!empty($keyword)) {
            $this->updatePopularKeywords($keyword, $normalizedKeyword);
        }

        return [
            'results' => $results,
            'suggestions' => $this->getSearchSuggestions($keyword),
            'filters' => $this->getAvailableFilters($normalizedKeyword),
            'related_keywords' => $this->getRelatedKeywords($normalizedKeyword),
        ];
    }

    /**
     * 검색 쿼리 빌드
     */
    private function buildSearchQuery(string $keyword, array $filters, string $sortBy)
    {
        $query = Product::where('status', 'active');

        // 키워드 검색
        if (!empty($keyword)) {
            $query->whereExists(function ($subQuery) use ($keyword) {
                $subQuery->select(DB::raw(1))
                    ->from('product_search_index')
                    ->whereColumn('product_search_index.product_id', 'shop_products.id')
                    ->where(function ($q) use ($keyword) {
                        // 전문검색 매치
                        $q->whereRaw('MATCH(searchable_content) AGAINST(? IN BOOLEAN MODE)', [$keyword . '*'])
                          // 또는 LIKE 검색 (fallback)
                          ->orWhere('searchable_content', 'LIKE', "%{$keyword}%");
                    });
            });
        }

        // 필터 적용
        $this->applyFilters($query, $filters);

        // 정렬 적용
        $this->applySorting($query, $sortBy, $keyword);

        return $query->with(['category', 'images']);
    }

    /**
     * 필터 적용
     */
    private function applyFilters($query, array $filters): void
    {
        foreach ($filters as $filterName => $filterValue) {
            switch ($filterName) {
                case 'category':
                    if (!empty($filterValue)) {
                        $query->whereIn('category_id', (array) $filterValue);
                    }
                    break;

                case 'price_min':
                    if (is_numeric($filterValue)) {
                        $query->where('price', '>=', $filterValue);
                    }
                    break;

                case 'price_max':
                    if (is_numeric($filterValue)) {
                        $query->where('price', '<=', $filterValue);
                    }
                    break;

                case 'rating':
                    if (is_numeric($filterValue)) {
                        $query->where('average_rating', '>=', $filterValue);
                    }
                    break;

                case 'in_stock':
                    if ($filterValue) {
                        $query->where('stock_quantity', '>', 0);
                    }
                    break;

                case 'on_sale':
                    if ($filterValue) {
                        $query->where('sale_price', '<', DB::raw('price'));
                    }
                    break;

                case 'brand':
                    if (!empty($filterValue)) {
                        $query->whereIn('brand', (array) $filterValue);
                    }
                    break;

                case 'tags':
                    if (!empty($filterValue)) {
                        $query->whereJsonContains('tags', $filterValue);
                    }
                    break;
            }
        }
    }

    /**
     * 정렬 적용
     */
    private function applySorting($query, string $sortBy, string $keyword = ''): void
    {
        switch ($sortBy) {
            case 'relevance':
                if (!empty($keyword)) {
                    // 검색어 관련도 순
                    $query->orderByRaw('
                        CASE 
                            WHEN name LIKE ? THEN 1
                            WHEN name LIKE ? THEN 2
                            ELSE 3
                        END
                    ', ["{$keyword}%", "%{$keyword}%"])
                    ->orderBy('sales_count', 'desc');
                } else {
                    $query->orderBy('sales_count', 'desc');
                }
                break;

            case 'price_low':
                $query->orderByRaw('COALESCE(sale_price, price) ASC');
                break;

            case 'price_high':
                $query->orderByRaw('COALESCE(sale_price, price) DESC');
                break;

            case 'rating':
                $query->orderBy('average_rating', 'desc')
                      ->orderBy('reviews_count', 'desc');
                break;

            case 'popularity':
                $query->orderBy('sales_count', 'desc')
                      ->orderBy('view_count', 'desc');
                break;

            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;

            case 'name':
                $query->orderBy('name', 'asc');
                break;

            default:
                $query->orderBy('created_at', 'desc');
        }
    }

    /**
     * 키워드 정규화
     */
    private function normalizeKeyword(string $keyword): string
    {
        // 공백 정리
        $normalized = trim($keyword);
        
        // 여러 공백을 하나로
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        
        // 소문자 변환
        $normalized = strtolower($normalized);
        
        // 특수문자 제거 (한글, 영문, 숫자, 공백만 허용)
        $normalized = preg_replace('/[^\p{L}\p{N}\s]/u', '', $normalized);
        
        return $normalized;
    }

    /**
     * 자동완성 제안
     */
    public function getAutocompleteSuggestions(string $keyword, int $limit = 10): Collection
    {
        $normalizedKeyword = $this->normalizeKeyword($keyword);
        
        if (strlen($normalizedKeyword) < 2) {
            return collect();
        }

        $cacheKey = "autocomplete_{$normalizedKeyword}_{$limit}";
        
        return Cache::remember($cacheKey, 300, function () use ($normalizedKeyword, $limit) {
            // 1. 인기 검색어에서 찾기
            $popularSuggestions = PopularKeyword::where('normalized_keyword', 'LIKE', "{$normalizedKeyword}%")
                ->orderBy('search_count', 'desc')
                ->limit($limit / 2)
                ->pluck('keyword');

            // 2. 상품명에서 찾기
            $productSuggestions = Product::where('name', 'LIKE', "%{$normalizedKeyword}%")
                ->where('status', 'active')
                ->orderBy('sales_count', 'desc')
                ->limit($limit / 2)
                ->pluck('name');

            // 3. 저장된 제안어에서 찾기
            $savedSuggestions = SearchSuggestion::where('keyword', 'LIKE', "{$normalizedKeyword}%")
                ->where('type', 'autocomplete')
                ->where('is_active', true)
                ->orderBy('frequency', 'desc')
                ->limit($limit)
                ->pluck('suggestion');

            return $popularSuggestions
                ->merge($productSuggestions)
                ->merge($savedSuggestions)
                ->unique()
                ->take($limit);
        });
    }

    /**
     * 검색 제안 (오타 교정, 관련어)
     */
    public function getSearchSuggestions(string $keyword): array
    {
        $suggestions = [];
        $normalizedKeyword = $this->normalizeKeyword($keyword);

        if (empty($normalizedKeyword)) {
            return $suggestions;
        }

        // 오타 교정 제안
        $correctionSuggestions = SearchSuggestion::where('keyword', $normalizedKeyword)
            ->where('type', 'correction')
            ->where('is_active', true)
            ->orderBy('relevance_score', 'desc')
            ->limit(3)
            ->pluck('suggestion');

        if ($correctionSuggestions->isNotEmpty()) {
            $suggestions['corrections'] = $correctionSuggestions;
        }

        // 관련어 제안
        $relatedSuggestions = SearchSuggestion::where('keyword', $normalizedKeyword)
            ->where('type', 'related')
            ->where('is_active', true)
            ->orderBy('frequency', 'desc')
            ->limit(5)
            ->pluck('suggestion');

        if ($relatedSuggestions->isNotEmpty()) {
            $suggestions['related'] = $relatedSuggestions;
        }

        return $suggestions;
    }

    /**
     * 사용 가능한 필터 옵션
     */
    public function getAvailableFilters(string $keyword = ''): array
    {
        $cacheKey = "search_filters_" . md5($keyword);
        
        return Cache::remember($cacheKey, 600, function () use ($keyword) {
            $baseQuery = Product::where('status', 'active');

            if (!empty($keyword)) {
                $baseQuery->whereExists(function ($subQuery) use ($keyword) {
                    $subQuery->select(DB::raw(1))
                        ->from('product_search_index')
                        ->whereColumn('product_search_index.product_id', 'shop_products.id')
                        ->where('searchable_content', 'LIKE', "%{$keyword}%");
                });
            }

            return [
                'categories' => $this->getCategoryFilters($baseQuery),
                'price_range' => $this->getPriceRange($baseQuery),
                'brands' => $this->getBrandFilters($baseQuery),
                'ratings' => $this->getRatingFilters($baseQuery),
            ];
        });
    }

    /**
     * 카테고리 필터
     */
    private function getCategoryFilters($baseQuery): array
    {
        return $baseQuery->select('category_id')
            ->with('category')
            ->get()
            ->groupBy('category_id')
            ->map(function ($products, $categoryId) {
                $category = $products->first()->category;
                return [
                    'id' => $categoryId,
                    'name' => $category->name,
                    'count' => $products->count(),
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * 가격 범위
     */
    private function getPriceRange($baseQuery): array
    {
        $priceStats = $baseQuery->selectRaw('MIN(price) as min_price, MAX(price) as max_price')->first();
        
        return [
            'min' => (int) $priceStats->min_price,
            'max' => (int) $priceStats->max_price,
        ];
    }

    /**
     * 브랜드 필터
     */
    private function getBrandFilters($baseQuery): array
    {
        return $baseQuery->select('brand')
            ->whereNotNull('brand')
            ->groupBy('brand')
            ->selectRaw('brand, COUNT(*) as count')
            ->orderBy('count', 'desc')
            ->limit(20)
            ->get()
            ->toArray();
    }

    /**
     * 평점 필터
     */
    private function getRatingFilters($baseQuery): array
    {
        $ratings = [];
        for ($i = 5; $i >= 1; $i--) {
            $count = $baseQuery->where('average_rating', '>=', $i)->count();
            if ($count > 0) {
                $ratings[] = [
                    'rating' => $i,
                    'count' => $count,
                    'label' => $i . '점 이상',
                ];
            }
        }
        return $ratings;
    }

    /**
     * 관련 키워드
     */
    public function getRelatedKeywords(string $keyword, int $limit = 5): Collection
    {
        return SearchSuggestion::where('keyword', $keyword)
            ->where('type', 'related')
            ->where('is_active', true)
            ->orderBy('frequency', 'desc')
            ->limit($limit)
            ->pluck('suggestion');
    }

    /**
     * 인기 검색어
     */
    public function getPopularKeywords(string $period = 'daily', int $limit = 10): Collection
    {
        $orderColumn = match($period) {
            'daily' => 'daily_count',
            'weekly' => 'weekly_count',
            'monthly' => 'monthly_count',
            default => 'search_count',
        };

        return PopularKeyword::where($orderColumn, '>', 0)
            ->orderBy($orderColumn, 'desc')
            ->limit($limit)
            ->pluck('keyword');
    }

    /**
     * 검색 로그 기록
     */
    private function logSearch(
        string $keyword,
        string $normalizedKeyword,
        int $resultsCount,
        array $filters,
        string $sortBy,
        ?int $userId
    ): void {
        SearchLog::create([
            'user_id' => $userId,
            'session_id' => session()->getId(),
            'keyword' => $keyword,
            'normalized_keyword' => $normalizedKeyword,
            'results_count' => $resultsCount,
            'filters' => $filters,
            'sort_by' => $sortBy,
            'has_results' => $resultsCount > 0,
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * 인기 검색어 업데이트
     */
    private function updatePopularKeywords(string $keyword, string $normalizedKeyword): void
    {
        $popularKeyword = PopularKeyword::firstOrCreate([
            'normalized_keyword' => $normalizedKeyword,
        ], [
            'keyword' => $keyword,
            'last_searched_date' => today(),
        ]);

        $popularKeyword->increment('search_count');

        // 날짜별 카운트 업데이트
        if ($popularKeyword->last_searched_date->isToday()) {
            $popularKeyword->increment('daily_count');
        } else {
            $popularKeyword->daily_count = 1;
            $popularKeyword->last_searched_date = today();
        }

        $popularKeyword->save();
    }

    /**
     * 상품 검색 인덱스 업데이트
     */
    public function updateProductSearchIndex(Product $product): void
    {
        $searchableContent = $this->buildSearchableContent($product);
        
        ProductSearchIndex::updateOrCreate([
            'product_id' => $product->id,
        ], [
            'searchable_content' => $searchableContent,
            'category_path' => $this->buildCategoryPath($product),
            'tags' => $product->tags ?? [],
            'price' => $product->price,
            'stock_quantity' => $product->stock_quantity,
            'average_rating' => $product->average_rating,
            'sales_count' => $product->sales_count,
            'is_active' => $product->status === 'active',
        ]);
    }

    /**
     * 검색 가능한 내용 빌드
     */
    private function buildSearchableContent(Product $product): string
    {
        $content = [];
        
        // 기본 상품 정보
        $content[] = $product->name;
        $content[] = $product->description;
        $content[] = $product->sku;
        $content[] = $product->brand;
        
        // 카테고리 정보
        if ($product->category) {
            $content[] = $product->category->name;
            $content[] = $product->category->description;
        }
        
        // 태그
        if ($product->tags) {
            $content = array_merge($content, $product->tags);
        }
        
        // 리뷰 키워드 (상위 리뷰에서 추출)
        $reviewKeywords = $product->reviews()
            ->approved()
            ->orderBy('helpful_count', 'desc')
            ->limit(10)
            ->pluck('content')
            ->map(function ($content) {
                // 간단한 키워드 추출 (실제로는 더 정교한 NLP 처리 필요)
                return str_word_count($content, 1);
            })
            ->flatten()
            ->unique()
            ->take(20);
            
        $content = array_merge($content, $reviewKeywords->toArray());
        
        return implode(' ', array_filter($content));
    }

    /**
     * 카테고리 경로 빌드
     */
    private function buildCategoryPath(Product $product): string
    {
        if (!$product->category) {
            return '';
        }

        $path = [];
        $category = $product->category;
        
        while ($category) {
            array_unshift($path, $category->name);
            $category = $category->parent;
        }
        
        return implode(' > ', $path);
    }
}