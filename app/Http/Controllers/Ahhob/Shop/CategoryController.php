<?php

namespace App\Http\Controllers\Ahhob\Shop;

use App\Http\Controllers\Controller;
use App\Models\Ahhob\Shop\Category;
use App\Models\Ahhob\Shop\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | 카테고리 컨트롤러 (Category Controller)
    |--------------------------------------------------------------------------
    */
    
    /**
     * 카테고리 목록
     */
    public function index(): View
    {
        $categories = Category::active()
            ->roots()
            ->with(['children' => function ($query) {
                $query->active()->ordered();
            }])
            ->ordered()
            ->get();
        
        return view('ahhob.shop.categories.index', compact('categories'));
    }
    
    /**
     * 카테고리별 상품 목록
     */
    public function show(string $categorySlug, Request $request): View
    {
        $category = Category::where('slug', $categorySlug)
            ->active()
            ->firstOrFail();
        
        $query = Product::with(['category'])
            ->active()
            ->visible()
            ->published()
            ->where('category_id', $category->id);
        
        // 가격 필터링
        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        
        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }
        
        // 정렬
        $sort = $request->get('sort', 'latest');
        match($sort) {
            'price_low' => $query->orderBy('price', 'asc'),
            'price_high' => $query->orderBy('price', 'desc'),
            'rating' => $query->orderBy('average_rating', 'desc'),
            'popular' => $query->orderBy('sales_count', 'desc'),
            default => $query->orderBy('created_at', 'desc'),
        };
        
        $products = $query->paginate(12);
        
        // 가격 범위 계산
        $priceRange = Product::where('category_id', $category->id)
            ->active()
            ->selectRaw('MIN(price) as min_price, MAX(price) as max_price')
            ->first();
        
        return view('ahhob.shop.categories.show', compact(
            'category', 
            'products', 
            'priceRange'
        ));
    }
}