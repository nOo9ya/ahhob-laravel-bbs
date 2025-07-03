<?php

namespace App\Http\Controllers\Ahhob\Admin\Dashboard\Shop;

use App\Http\Controllers\Controller;
use App\Models\Ahhob\Shop\Product;
use App\Models\Ahhob\Shop\Category;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | 관리자 상품 관리 컨트롤러 (Admin Product Controller)
    |--------------------------------------------------------------------------
    */
    
    /**
     * 상품 목록
     */
    public function index(Request $request): View
    {
        $query = Product::with(['category']);
        
        // 검색 필터
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        // 카테고리 필터
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        
        // 상태 필터
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // 재고 상태 필터
        if ($request->filled('stock_status')) {
            $query->where('stock_status', $request->stock_status);
        }
        
        // 정렬
        $sort = $request->get('sort', 'created_at');
        $direction = $request->get('direction', 'desc');
        $query->orderBy($sort, $direction);
        
        $products = $query->paginate(20);
        $categories = Category::active()->get();
        
        return view('ahhob.admin.dashboard.shop.products.index', compact(
            'products', 
            'categories'
        ));
    }
    
    /**
     * 상품 생성 폼
     */
    public function create(): View
    {
        $categories = Category::active()->get();
        
        return view('ahhob.admin.dashboard.shop.products.create', compact('categories'));
    }
    
    /**
     * 상품 저장
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:shop_categories,id',
            'price' => 'required|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'sku' => 'nullable|string|max:100|unique:shop_products,sku',
            'short_description' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'stock_quantity' => 'nullable|integer|min:0',
            'min_stock_quantity' => 'nullable|integer|min:0',
            'track_stock' => 'boolean',
            'allow_backorder' => 'boolean',
            'requires_shipping' => 'boolean',
            'shipping_cost' => 'nullable|numeric|min:0',
            'weight' => 'nullable|numeric|min:0',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'gallery_images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'status' => 'required|in:active,inactive,draft',
            'visibility' => 'required|in:visible,hidden,catalog,search',
            'is_featured' => 'boolean',
            'is_digital' => 'boolean',
            'min_purchase_quantity' => 'nullable|integer|min:1',
            'max_purchase_quantity' => 'nullable|integer|min:1',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:255',
        ]);
        
        $data = $request->except(['featured_image', 'gallery_images']);
        $data['slug'] = \Str::slug($request->name);
        $data['created_by'] = auth('admin')->id();
        $data['published_at'] = $request->status === 'active' ? now() : null;
        
        // 재고 추적하지 않으면 항상 재고 있음으로 설정
        if (!$request->boolean('track_stock')) {
            $data['stock_status'] = 'in_stock';
        }
        
        // 대표 이미지 업로드
        if ($request->hasFile('featured_image')) {
            $data['featured_image'] = $request->file('featured_image')
                ->store('shop/products', 'public');
        }
        
        // 갤러리 이미지 업로드
        if ($request->hasFile('gallery_images')) {
            $galleryImages = [];
            foreach ($request->file('gallery_images') as $image) {
                $galleryImages[] = $image->store('shop/products/gallery', 'public');
            }
            $data['gallery_images'] = $galleryImages;
        }
        
        $product = Product::create($data);
        
        // 카테고리 상품 수 업데이트
        $product->category->updateProductsCount();
        
        return redirect()->route('ahhob.admin.dashboard.shop.products.index')
            ->with('success', '상품이 성공적으로 등록되었습니다.');
    }
    
    /**
     * 상품 상세
     */
    public function show(Product $product): View
    {
        $product->load(['category', 'reviews.user', 'orderItems']);
        
        // 판매 통계
        $salesStats = [
            'total_sales' => $product->sales_count,
            'total_revenue' => $product->orderItems->sum('total_price'),
            'average_order_value' => $product->orderItems->count() > 0 
                ? $product->orderItems->avg('total_price') 
                : 0,
        ];
        
        return view('ahhob.admin.dashboard.shop.products.show', compact(
            'product',
            'salesStats'
        ));
    }
    
    /**
     * 상품 수정 폼
     */
    public function edit(Product $product): View
    {
        $categories = Category::active()->get();
        
        return view('ahhob.admin.dashboard.shop.products.edit', compact(
            'product',
            'categories'
        ));
    }
    
    /**
     * 상품 업데이트
     */
    public function update(Request $request, Product $product): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:shop_categories,id',
            'price' => 'required|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'sku' => 'nullable|string|max:100|unique:shop_products,sku,' . $product->id,
            'short_description' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'stock_quantity' => 'nullable|integer|min:0',
            'min_stock_quantity' => 'nullable|integer|min:0',
            'track_stock' => 'boolean',
            'allow_backorder' => 'boolean',
            'requires_shipping' => 'boolean',
            'shipping_cost' => 'nullable|numeric|min:0',
            'weight' => 'nullable|numeric|min:0',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'gallery_images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'status' => 'required|in:active,inactive,draft',
            'visibility' => 'required|in:visible,hidden,catalog,search',
            'is_featured' => 'boolean',
            'is_digital' => 'boolean',
            'min_purchase_quantity' => 'nullable|integer|min:1',
            'max_purchase_quantity' => 'nullable|integer|min:1',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:255',
        ]);
        
        $data = $request->except(['featured_image', 'gallery_images']);
        $data['slug'] = \Str::slug($request->name);
        $data['updated_by'] = auth('admin')->id();
        
        // 상태가 active로 변경되고 published_at이 없으면 현재 시간 설정
        if ($request->status === 'active' && !$product->published_at) {
            $data['published_at'] = now();
        }
        
        // 재고 추적하지 않으면 항상 재고 있음으로 설정
        if (!$request->boolean('track_stock')) {
            $data['stock_status'] = 'in_stock';
        } else {
            // 재고 상태 업데이트
            $product->updateStockStatus();
        }
        
        // 대표 이미지 업데이트
        if ($request->hasFile('featured_image')) {
            // 기존 이미지 삭제
            if ($product->featured_image) {
                Storage::disk('public')->delete($product->featured_image);
            }
            $data['featured_image'] = $request->file('featured_image')
                ->store('shop/products', 'public');
        }
        
        // 갤러리 이미지 업데이트
        if ($request->hasFile('gallery_images')) {
            // 기존 이미지들 삭제
            if ($product->gallery_images) {
                foreach ($product->gallery_images as $image) {
                    Storage::disk('public')->delete($image);
                }
            }
            
            $galleryImages = [];
            foreach ($request->file('gallery_images') as $image) {
                $galleryImages[] = $image->store('shop/products/gallery', 'public');
            }
            $data['gallery_images'] = $galleryImages;
        }
        
        // 카테고리가 변경된 경우
        $oldCategoryId = $product->category_id;
        
        $product->update($data);
        
        // 카테고리 상품 수 업데이트
        if ($oldCategoryId !== $product->category_id) {
            Category::find($oldCategoryId)->updateProductsCount();
            $product->category->updateProductsCount();
        }
        
        return redirect()->route('ahhob.admin.dashboard.shop.products.index')
            ->with('success', '상품이 성공적으로 수정되었습니다.');
    }
    
    /**
     * 상품 삭제
     */
    public function destroy(Product $product): RedirectResponse
    {
        // 주문된 상품은 삭제할 수 없음
        if ($product->orderItems()->exists()) {
            return redirect()->back()
                ->with('error', '이미 주문된 상품은 삭제할 수 없습니다.');
        }
        
        $categoryId = $product->category_id;
        
        // 이미지 파일 삭제
        if ($product->featured_image) {
            Storage::disk('public')->delete($product->featured_image);
        }
        
        if ($product->gallery_images) {
            foreach ($product->gallery_images as $image) {
                Storage::disk('public')->delete($image);
            }
        }
        
        $product->delete();
        
        // 카테고리 상품 수 업데이트
        Category::find($categoryId)->updateProductsCount();
        
        return redirect()->route('ahhob.admin.dashboard.shop.products.index')
            ->with('success', '상품이 성공적으로 삭제되었습니다.');
    }
    
    /**
     * 대량 작업
     */
    public function bulkAction(Request $request): RedirectResponse
    {
        $request->validate([
            'action' => 'required|in:activate,deactivate,delete',
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:shop_products,id',
        ]);
        
        $products = Product::whereIn('id', $request->product_ids);
        
        switch ($request->action) {
            case 'activate':
                $products->update([
                    'status' => 'active',
                    'published_at' => now(),
                    'updated_by' => auth('admin')->id(),
                ]);
                $message = '선택된 상품들이 활성화되었습니다.';
                break;
                
            case 'deactivate':
                $products->update([
                    'status' => 'inactive',
                    'updated_by' => auth('admin')->id(),
                ]);
                $message = '선택된 상품들이 비활성화되었습니다.';
                break;
                
            case 'delete':
                // 주문된 상품이 있는지 확인
                $orderedProducts = $products->whereHas('orderItems')->count();
                if ($orderedProducts > 0) {
                    return redirect()->back()
                        ->with('error', '주문된 상품이 포함되어 있어 삭제할 수 없습니다.');
                }
                
                $productsList = $products->get();
                
                // 이미지 파일들 삭제
                foreach ($productsList as $product) {
                    if ($product->featured_image) {
                        Storage::disk('public')->delete($product->featured_image);
                    }
                    
                    if ($product->gallery_images) {
                        foreach ($product->gallery_images as $image) {
                            Storage::disk('public')->delete($image);
                        }
                    }
                }
                
                $products->delete();
                $message = '선택된 상품들이 삭제되었습니다.';
                break;
        }
        
        return redirect()->back()->with('success', $message);
    }
    
    /**
     * 재고 관리
     */
    public function stock(Request $request): View
    {
        $query = Product::with(['category'])
            ->where('track_stock', true);
        
        // 재고 상태 필터
        if ($request->filled('stock_filter')) {
            switch ($request->stock_filter) {
                case 'low':
                    $query->whereColumn('stock_quantity', '<=', 'min_stock_quantity');
                    break;
                case 'out':
                    $query->where('stock_quantity', 0);
                    break;
                case 'backorder':
                    $query->where('stock_status', 'on_backorder');
                    break;
            }
        }
        
        $products = $query->orderBy('stock_quantity', 'asc')->paginate(20);
        
        return view('ahhob.admin.dashboard.shop.products.stock', compact('products'));
    }
    
    /**
     * 재고 업데이트
     */
    public function updateStock(Request $request, Product $product): RedirectResponse
    {
        $request->validate([
            'stock_quantity' => 'required|integer|min:0',
            'min_stock_quantity' => 'nullable|integer|min:0',
        ]);
        
        $product->update([
            'stock_quantity' => $request->stock_quantity,
            'min_stock_quantity' => $request->min_stock_quantity,
            'updated_by' => auth('admin')->id(),
        ]);
        
        $product->updateStockStatus();
        
        return redirect()->back()
            ->with('success', '재고가 성공적으로 업데이트되었습니다.');
    }
}