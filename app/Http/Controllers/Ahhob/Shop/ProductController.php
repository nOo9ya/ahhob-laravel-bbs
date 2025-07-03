<?php

namespace App\Http\Controllers\Ahhob\Shop;

use App\Http\Controllers\Controller;
use App\Models\Ahhob\Shop\Product;
use App\Models\Ahhob\Shop\Category;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | 상품 컨트롤러 (Product Controller)
    |--------------------------------------------------------------------------
    */
    
    /**
     * 상품 목록 (쇼핑몰 메인)
     */
    public function index(Request $request): View
    {
        $query = Product::with(['category'])
            ->active()
            ->visible()
            ->published();
        
        // 카테고리 필터링
        if ($request->filled('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }
        
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
        $categories = Category::active()->roots()->with('children')->get();
        
        return view('ahhob.shop.products.index', compact('products', 'categories'));
    }
    
    /**
     * 상품 상세
     */
    public function show(string $productSlug): View
    {
        $product = Product::with(['category', 'reviews.user', 'attachments'])
            ->where('slug', $productSlug)
            ->active()
            ->visible()
            ->published()
            ->firstOrFail();
        
        // 조회수 증가
        $product->incrementViews();
        
        // 연관 상품
        $relatedProducts = $product->getRelatedProducts(4);
        
        // 리뷰 통계
        $reviewStats = [
            'total' => $product->reviews_count,
            'average' => $product->average_rating,
            'ratings' => $product->reviews()
                ->selectRaw('rating, COUNT(*) as count')
                ->groupBy('rating')
                ->orderBy('rating', 'desc')
                ->pluck('count', 'rating')
                ->toArray()
        ];
        
        return view('ahhob.shop.products.show', compact(
            'product', 
            'relatedProducts', 
            'reviewStats'
        ));
    }
}