<?php

namespace App\Models\Ahhob\Shop;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Models\User;
use App\Models\Ahhob\Shared\Attachment;

class Product extends Model
{
    use HasFactory;

    protected $table = 'shop_products';

    /*
    |--------------------------------------------------------------------------
    | 모델 속성 (Attributes & Properties)
    |--------------------------------------------------------------------------
    */
    // region --- 모델 속성 (Attributes & Properties) ---

    protected $fillable = [
        'name',
        'slug',
        'short_description',
        'description',
        'sku',
        'category_id',
        'price',
        'compare_price',
        'cost_price',
        'stock_quantity',
        'min_stock_quantity',
        'track_stock',
        'stock_status',
        'featured_image',
        'gallery_images',
        'weight',
        'dimensions',
        'requires_shipping',
        'shipping_cost',
        'status',
        'visibility',
        'is_featured',
        'is_digital',
        'min_purchase_quantity',
        'max_purchase_quantity',
        'allow_backorder',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'views_count',
        'sales_count',
        'average_rating',
        'reviews_count',
        'created_by',
        'updated_by',
        'published_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'weight' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'average_rating' => 'decimal:2',
        'gallery_images' => 'array',
        'dimensions' => 'array',
        'track_stock' => 'boolean',
        'requires_shipping' => 'boolean',
        'is_featured' => 'boolean',
        'is_digital' => 'boolean',
        'allow_backorder' => 'boolean',
        'views_count' => 'integer',
        'sales_count' => 'integer',
        'reviews_count' => 'integer',
        'stock_quantity' => 'integer',
        'min_stock_quantity' => 'integer',
        'min_purchase_quantity' => 'integer',
        'max_purchase_quantity' => 'integer',
        'published_at' => 'datetime',
    ];

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 관계 (Relationships)
    |--------------------------------------------------------------------------
    */
    // region --- 관계 (Relationships) ---

    /**
     * 카테고리
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * 상품 옵션들
     */
    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class)
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('name');
    }

    /**
     * 상품 리뷰들
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)
                    ->where('status', 'approved')
                    ->latest();
    }

    /**
     * 모든 리뷰들 (관리자용)
     */
    public function allReviews(): HasMany
    {
        return $this->hasMany(Review::class)->latest();
    }

    /**
     * 장바구니 아이템들
     */
    public function cartItems(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    /**
     * 위시리스트 아이템들
     */
    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    /**
     * 주문 아이템들
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * 첨부파일 (다형적 관계)
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * 생성자
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * 수정자
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 쿼리 스코프 (Query Scopes)
    |--------------------------------------------------------------------------
    */
    // region --- 쿼리 스코프 (Query Scopes) ---

    /**
     * 활성화된 상품만 조회
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * 공개된 상품만 조회
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->whereIn('visibility', ['visible', 'catalog', 'search']);
    }

    /**
     * 추천 상품만 조회
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * 재고가 있는 상품만 조회
     */
    public function scopeInStock(Builder $query): Builder
    {
        return $query->where('stock_status', 'in_stock');
    }

    /**
     * 출시된 상품만 조회
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')
                    ->where('published_at', '<=', now());
    }

    /**
     * 가격 범위로 필터링
     */
    public function scopePriceRange(Builder $query, float $min, float $max): Builder
    {
        return $query->whereBetween('price', [$min, $max]);
    }

    /**
     * 카테고리로 필터링
     */
    public function scopeInCategory(Builder $query, $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * 검색
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function ($query) use ($term) {
            $query->where('name', 'LIKE', "%{$term}%")
                  ->orWhere('short_description', 'LIKE', "%{$term}%")
                  ->orWhere('description', 'LIKE', "%{$term}%")
                  ->orWhere('sku', 'LIKE', "%{$term}%");
        });
    }

    /**
     * 정렬
     */
    public function scopeOrderBy(Builder $query, string $sort): Builder
    {
        return match($sort) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'name_asc' => $query->orderBy('name', 'asc'),
            'name_desc' => $query->orderBy('name', 'desc'),
            'newest' => $query->orderBy('published_at', 'desc'),
            'oldest' => $query->orderBy('published_at', 'asc'),
            'popular' => $query->orderBy('sales_count', 'desc'),
            'rating' => $query->orderBy('average_rating', 'desc'),
            default => $query->orderBy('created_at', 'desc'),
        };
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 라우트 모델 바인딩 (Route Model Binding)
    |--------------------------------------------------------------------------
    */
    // region --- 라우트 모델 바인딩 (Route Model Binding) ---

    /**
     * 라우트 모델 바인딩에 사용할 키 설정
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 접근자 & 변경자 (Accessors & Mutators)
    |--------------------------------------------------------------------------
    */
    // region --- 접근자 & 변경자 (Accessors & Mutators) ---

    /**
     * 상품 URL 생성
     */
    public function getUrlAttribute(): string
    {
        return route('shop.products.show', $this->slug);
    }

    /**
     * 대표 이미지 URL 반환
     */
    public function getFeaturedImageUrlAttribute(): ?string
    {
        return $this->featured_image ? asset('storage/' . $this->featured_image) : null;
    }

    /**
     * 갤러리 이미지 URL들 반환
     */
    public function getGalleryImageUrlsAttribute(): array
    {
        if (!$this->gallery_images) {
            return [];
        }

        return collect($this->gallery_images)
            ->map(fn ($image) => asset('storage/' . $image))
            ->toArray();
    }

    /**
     * 할인율 계산
     */
    public function getDiscountPercentageAttribute(): ?float
    {
        if (!$this->compare_price || $this->compare_price <= $this->price) {
            return null;
        }

        return round((($this->compare_price - $this->price) / $this->compare_price) * 100, 1);
    }

    /**
     * 재고 상태 확인
     */
    public function getIsInStockAttribute(): bool
    {
        if (!$this->track_stock) {
            return true;
        }

        return $this->stock_quantity > 0 || $this->allow_backorder;
    }

    /**
     * 구매 가능 여부
     */
    public function getIsPurchasableAttribute(): bool
    {
        return $this->status === 'active' 
            && in_array($this->visibility, ['visible', 'catalog'])
            && $this->is_in_stock
            && ($this->published_at === null || $this->published_at <= now());
    }

    /**
     * 포맷된 가격
     */
    protected function formattedPrice(): Attribute
    {
        return Attribute::make(
            get: fn () => '₩' . number_format($this->price)
        );
    }

    /**
     * 포맷된 정가
     */
    protected function formattedComparePrice(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->compare_price ? '₩' . number_format($this->compare_price) : null
        );
    }

    /**
     * 슬러그 자동 생성
     */
    public function setNameAttribute(string $value): void
    {
        $this->attributes['name'] = $value;
        
        if (empty($this->attributes['slug'])) {
            $this->attributes['slug'] = \Str::slug($value);
        }
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 공개 메서드 (Public Methods)
    |--------------------------------------------------------------------------
    */
    // region --- 공개 메서드 (Public Methods) ---

    /**
     * 조회수 증가
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    /**
     * 재고 감소
     */
    public function decrementStock(int $quantity): bool
    {
        if (!$this->track_stock) {
            return true;
        }

        if ($this->stock_quantity < $quantity && !$this->allow_backorder) {
            return false;
        }

        $this->decrement('stock_quantity', $quantity);
        $this->updateStockStatus();
        
        return true;
    }

    /**
     * 재고 증가
     */
    public function incrementStock(int $quantity): void
    {
        if ($this->track_stock) {
            $this->increment('stock_quantity', $quantity);
            $this->updateStockStatus();
        }
    }

    /**
     * 재고 상태 업데이트
     */
    public function updateStockStatus(): void
    {
        if (!$this->track_stock) {
            $this->update(['stock_status' => 'in_stock']);
            return;
        }

        $status = match (true) {
            $this->stock_quantity > 0 => 'in_stock',
            $this->allow_backorder => 'on_backorder',
            default => 'out_of_stock'
        };

        $this->update(['stock_status' => $status]);
    }

    /**
     * 평균 평점 업데이트
     */
    public function updateAverageRating(): void
    {
        $averageRating = $this->reviews()->avg('rating') ?? 0;
        $reviewsCount = $this->reviews()->count();

        $this->update([
            'average_rating' => round($averageRating, 2),
            'reviews_count' => $reviewsCount,
        ]);
    }

    /**
     * 판매량 증가
     */
    public function incrementSales(int $quantity = 1): void
    {
        $this->increment('sales_count', $quantity);
    }

    /**
     * 구매 가능 여부 확인
     */
    public function canPurchase(int $quantity = 1): bool
    {
        if (!$this->is_purchasable) {
            return false;
        }
        
        if (!$this->track_stock) {
            return true;
        }
        
        if ($this->stock_quantity >= $quantity) {
            return true;
        }
        
        return $this->allow_backorder;
    }
    
    /**
     * 연관 상품 추천
     */
    public function getRelatedProducts(int $limit = 4): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()
            ->visible()
            ->published()
            ->inStock()
            ->where('category_id', $this->category_id)
            ->where('id', '!=', $this->id)
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    // endregion
}