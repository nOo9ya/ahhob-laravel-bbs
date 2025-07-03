<?php

namespace App\Http\Controllers\Ahhob\Shop;

use App\Http\Controllers\Controller;
use App\Models\Ahhob\Shop\Cart;
use App\Models\Ahhob\Shop\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class CartController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | 장바구니 컨트롤러 (Cart Controller)
    |--------------------------------------------------------------------------
    */
    
    /**
     * 장바구니 목록
     */
    public function index(): View
    {
        $cartItems = Cart::with(['product'])
            ->where('user_id', auth()->id() ?? session()->getId())
            ->get();
        
        $subtotal = $cartItems->sum('total_price');
        $shippingCost = $this->calculateShippingCost($subtotal);
        $total = $subtotal + $shippingCost;
        
        return view('ahhob.shop.cart.index', compact(
            'cartItems', 
            'subtotal', 
            'shippingCost', 
            'total'
        ));
    }
    
    /**
     * 장바구니에 상품 추가
     */
    public function add(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:shop_products,id',
            'quantity' => 'required|integer|min:1|max:99',
        ]);
        
        $product = Product::findOrFail($request->product_id);
        
        // 재고 확인
        if (!$product->canPurchase($request->quantity)) {
            return response()->json([
                'success' => false,
                'message' => '재고가 부족합니다.'
            ], 400);
        }
        
        $userId = auth()->id() ?? session()->getId();
        
        // 기존 장바구니 아이템 확인
        $cartItem = Cart::where('user_id', $userId)
            ->where('product_id', $product->id)
            ->first();
        
        if ($cartItem) {
            // 수량 업데이트
            $newQuantity = $cartItem->quantity + $request->quantity;
            
            if (!$product->canPurchase($newQuantity)) {
                return response()->json([
                    'success' => false,
                    'message' => '재고가 부족합니다.'
                ], 400);
            }
            
            $cartItem->update([
                'quantity' => $newQuantity,
                'total_price' => $product->price * $newQuantity,
            ]);
        } else {
            // 새 아이템 추가
            Cart::create([
                'user_id' => $userId,
                'product_id' => $product->id,
                'quantity' => $request->quantity,
                'unit_price' => $product->price,
                'total_price' => $product->price * $request->quantity,
                'product_name' => $product->name,
                'product_image' => $product->featured_image,
                'product_sku' => $product->sku,
            ]);
        }
        
        // 장바구니 총 개수
        $cartCount = Cart::where('user_id', $userId)->sum('quantity');
        
        return response()->json([
            'success' => true,
            'message' => '장바구니에 추가되었습니다.',
            'cart_count' => $cartCount
        ]);
    }
    
    /**
     * 장바구니 아이템 수량 업데이트
     */
    public function update(Request $request, Cart $cartItem): JsonResponse
    {
        $request->validate([
            'quantity' => 'required|integer|min:1|max:99',
        ]);
        
        if (!$cartItem->product->canPurchase($request->quantity)) {
            return response()->json([
                'success' => false,
                'message' => '재고가 부족합니다.'
            ], 400);
        }
        
        $cartItem->update([
            'quantity' => $request->quantity,
            'total_price' => $cartItem->unit_price * $request->quantity,
        ]);
        
        return response()->json([
            'success' => true,
            'message' => '수량이 업데이트되었습니다.',
            'item_total' => $cartItem->formatted_total_price
        ]);
    }
    
    /**
     * 장바구니 아이템 삭제
     */
    public function destroy(Cart $cartItem): RedirectResponse
    {
        $cartItem->delete();
        
        return redirect()->route('ahhob.shop.cart.index')
            ->with('success', '상품이 장바구니에서 제거되었습니다.');
    }
    
    /**
     * 장바구니 전체 비우기
     */
    public function clear(): RedirectResponse
    {
        $userId = auth()->id() ?? session()->getId();
        
        Cart::where('user_id', $userId)->delete();
        
        return redirect()->route('ahhob.shop.cart.index')
            ->with('success', '장바구니가 비워졌습니다.');
    }
    
    /**
     * 배송비 계산
     */
    private function calculateShippingCost(float $subtotal): float
    {
        // 5만원 이상 무료배송
        return $subtotal >= 50000 ? 0 : 3000;
    }
}