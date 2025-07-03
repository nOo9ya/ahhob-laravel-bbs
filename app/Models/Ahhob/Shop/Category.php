<?php

namespace App\Models\Ahhob\Shop;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Category extends Model
{
    use HasFactory;

    protected $table = 'shop_categories';

    /*
    |--------------------------------------------------------------------------
    | 모델 속성 (Attributes & Properties)
    |--------------------------------------------------------------------------
    */
    // region --- 모델 속성 (Attributes & Properties) ---

    protected $fillable = [
        'name',
        'slug',
        'description',
        'image',
        'parent_id',
        'left',
        'right',
        'depth',
        'sort_order',
        'is_active',
        'is_featured',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'products_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'products_count' => 'integer',
        'sort_order' => 'integer',
        'left' => 'integer',
        'right' => 'integer',
        'depth' => 'integer',
    ];

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 관계 (Relationships)
    |--------------------------------------------------------------------------
    */
    // region --- 관계 (Relationships) ---

    /**
     * 상위 카테고리
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * 하위 카테고리들
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('name');
    }

    /**
     * 모든 하위 카테고리들 (재귀)
     */
    public function allChildren(): HasMany
    {
        return $this->children()->with('allChildren');
    }

    /**
     * 카테고리에 속한 상품들
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    /**
     * 활성화된 상품들
     */
    public function activeProducts(): HasMany
    {
        return $this->products()
                    ->where('status', 'active')
                    ->where('visibility', '!=', 'hidden');
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 쿼리 스코프 (Query Scopes)
    |--------------------------------------------------------------------------
    */
    // region --- 쿼리 스코프 (Query Scopes) ---

    /**
     * 활성화된 카테고리만 조회
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * 추천 카테고리만 조회
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * 최상위 카테고리만 조회
     */
    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * 특정 깊이의 카테고리만 조회
     */
    public function scopeDepth(Builder $query, int $depth): Builder
    {
        return $query->where('depth', $depth);
    }

    /**
     * 정렬 순서로 정렬
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
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
     * 카테고리의 전체 경로를 반환
     */
    public function getFullPathAttribute(): string
    {
        $path = collect();
        $category = $this;
        
        while ($category) {
            $path->prepend($category->name);
            $category = $category->parent;
        }
        
        return $path->implode(' > ');
    }

    /**
     * 카테고리 URL 생성
     */
    public function getUrlAttribute(): string
    {
        return route('shop.categories.show', $this->slug);
    }

    /**
     * 이미지 URL 반환
     */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('storage/' . $this->image) : null;
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
     * 하위 카테고리가 있는지 확인
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * 최상위 카테고리인지 확인
     */
    public function isRoot(): bool
    {
        return is_null($this->parent_id);
    }

    /**
     * 특정 카테고리의 자손인지 확인
     */
    public function isDescendantOf(Category $category): bool
    {
        return $this->left > $category->left && $this->right < $category->right;
    }

    /**
     * 특정 카테고리의 조상인지 확인
     */
    public function isAncestorOf(Category $category): bool
    {
        return $this->left < $category->left && $this->right > $category->right;
    }

    /**
     * 조상 카테고리들을 가져옴
     */
    public function getAncestors(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('left', '<', $this->left)
                    ->where('right', '>', $this->right)
                    ->orderBy('left')
                    ->get();
    }

    /**
     * 후손 카테고리들을 가져옴
     */
    public function getDescendants(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('left', '>', $this->left)
                    ->where('right', '<', $this->right)
                    ->orderBy('left')
                    ->get();
    }

    /**
     * 상품 수 업데이트
     */
    public function updateProductsCount(): void
    {
        $count = $this->activeProducts()->count();
        $this->update(['products_count' => $count]);
    }

    /**
     * 네비게이션 트리 생성
     */
    public static function getNavigationTree(): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()
                    ->roots()
                    ->with(['allChildren' => function ($query) {
                        $query->active()->ordered();
                    }])
                    ->ordered()
                    ->get();
    }

    // endregion
}