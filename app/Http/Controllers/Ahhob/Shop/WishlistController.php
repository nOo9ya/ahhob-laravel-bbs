<?php

namespace App\Http\Controllers\Ahhob\Shop;

use App\Http\Controllers\Controller;
use App\Models\Ahhob\Shop\Wishlist;
use App\Models\Ahhob\Shop\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class WishlistController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | 위시리스트 컨트롤러 (Wishlist Controller)
    |--------------------------------------------------------------------------
    */
    
    /**
     * 위시리스트 목록
     */
    public function index(): View
    {
        $wishlistItems = Wishlist::with(['product'])
            ->where('user_id', auth()->id())
            ->orderByPriority()
            ->get();
        
        return view('ahhob.shop.wishlist.index', compact('wishlistItems'));
    }
    
    /**
     * 위시리스트에 상품 추가
     */
    public function add(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:shop_products,id',
            'notes' => 'nullable|string|max:500',
            'priority' => 'nullable|integer|in:0,1,2',
        ]);
        
        $product = Product::findOrFail($request->product_id);
        
        // 이미 위시리스트에 있는지 확인
        $existingItem = Wishlist::where('user_id', auth()->id())
            ->where('product_id', $product->id)
            ->first();
        
        if ($existingItem) {
            return response()->json([
                'success' => false,
                'message' => '이미 위시리스트에 추가된 상품입니다.'
            ], 400);
        }
        
        Wishlist::create([
            'user_id' => auth()->id(),
            'product_id' => $product->id,
            'notes' => $request->notes,
            'priority' => $request->priority ?? 1,
            'notify_price_drop' => $request->boolean('notify_price_drop'),
            'notify_back_in_stock' => $request->boolean('notify_back_in_stock'),
        ]);
        
        return response()->json([
            'success' => true,
            'message' => '위시리스트에 추가되었습니다.'
        ]);
    }
    
    /**
     * 위시리스트에서 제거
     */
    public function destroy(Wishlist $wishlistItem): RedirectResponse
    {
        if ($wishlistItem->user_id !== auth()->id()) {
            abort(403);
        }
        
        $wishlistItem->delete();
        
        return redirect()->route('ahhob.shop.wishlist.index')
            ->with('success', '위시리스트에서 제거되었습니다.');
    }
    
    /**
     * 위시리스트에서 장바구니로 이동
     */
    public function moveToCart(Request $request, Wishlist $wishlistItem): JsonResponse
    {
        if ($wishlistItem->user_id !== auth()->id()) {
            abort(403);
        }
        
        $request->validate([
            'quantity' => 'nullable|integer|min:1|max:99',
        ]);
        
        $quantity = $request->get('quantity', 1);
        
        $cartItem = $wishlistItem->moveToCart($quantity);
        
        if (!$cartItem) {
            return response()->json([
                'success' => false,
                'message' => '상품을 장바구니에 추가할 수 없습니다.'
            ], 400);
        }
        
        // 위시리스트에서 제거 (선택사항)
        $wishlistItem->delete();
        
        return response()->json([
            'success' => true,
            'message' => '장바구니에 추가되었습니다.'
        ]);
    }
}