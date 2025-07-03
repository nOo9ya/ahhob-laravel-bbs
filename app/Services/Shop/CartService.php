<?php

namespace App\Services\Shop;

use App\Models\Ahhob\Shop\Cart;
use App\Models\Ahhob\Shop\Product;
use App\Models\User;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Collection;

class CartService
{
    /*
    |--------------------------------------------------------------------------
    | 장바구니 기본 기능 (Basic Cart Operations)
    |--------------------------------------------------------------------------
    */
    // region --- 장바구니 기본 기능 (Basic Cart Operations) ---

    /**
     * 장바구니에 상품 추가
     */
    public function addToCart(
        int $productId,
        int $quantity = 1,
        array $options = [],
        ?int $userId = null,
        ?string $sessionId = null
    ): Cart {
        $product = Product::findOrFail($productId);
        
        // 재고 확인
        if (!$product->canPurchase($quantity)) {
            throw new \InvalidArgumentException('재고가 부족합니다.');
        }

        $cartItem = $this->findExistingCartItem($productId, $options, $userId, $sessionId);

        if ($cartItem) {
            // 기존 아이템 수량 업데이트
            $newQuantity = $cartItem->quantity + $quantity;
            
            if (!$product->canPurchase($newQuantity)) {
                throw new \InvalidArgumentException('재고가 부족합니다.');
            }
            
            $cartItem->update(['quantity' => $newQuantity]);
            return $cartItem->fresh();
        }

        // 새로운 아이템 생성
        return Cart::create([
            'user_id' => $userId ?? auth()->id(),
            'session_id' => $sessionId ?? Session::getId(),
            'product_id' => $productId,
            'quantity' => $quantity,
            'product_name' => $product->name,
            'product_price' => $product->price,
            'product_image' => $product->featured_image,
            'product_sku' => $product->sku,
            'product_options' => $options,
        ]);
    }

    /**
     * 장바구니 아이템 수량 업데이트
     */
    public function updateQuantity(int $cartItemId, int $quantity): Cart
    {
        $cartItem = Cart::findOrFail($cartItemId);
        
        if ($quantity <= 0) {
            $cartItem->delete();
            throw new \InvalidArgumentException('수량은 1개 이상이어야 합니다.');
        }

        $product = $cartItem->product;
        if (!$product->canPurchase($quantity)) {
            throw new \InvalidArgumentException('재고가 부족합니다.');
        }

        $cartItem->update(['quantity' => $quantity]);
        return $cartItem->fresh();
    }

    /**
     * 장바구니 아이템 삭제
     */
    public function removeFromCart(int $cartItemId): bool
    {
        $cartItem = Cart::findOrFail($cartItemId);
        return $cartItem->delete();
    }

    /**
     * 장바구니 비우기
     */
    public function clearCart(?int $userId = null, ?string $sessionId = null): int
    {
        $query = Cart::query();

        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($sessionId) {
            $query->where('session_id', $sessionId);
        } else {
            $query->where(function ($q) {
                $q->where('user_id', auth()->id())
                  ->orWhere('session_id', Session::getId());
            });
        }

        return $query->delete();
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 장바구니 조회 기능 (Cart Retrieval)
    |--------------------------------------------------------------------------
    */
    // region --- 장바구니 조회 기능 (Cart Retrieval) ---

    /**
     * 사용자 장바구니 아이템 목록
     */
    public function getCartItems(?int $userId = null, ?string $sessionId = null): Collection
    {
        $query = Cart::with('product');

        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($sessionId) {
            $query->where('session_id', $sessionId);
        } else {
            $query->where(function ($q) {
                $q->where('user_id', auth()->id())
                  ->orWhere('session_id', Session::getId());
            });
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * 장바구니 아이템 수
     */
    public function getCartItemCount(?int $userId = null, ?string $sessionId = null): int
    {
        return $this->getCartItems($userId, $sessionId)->sum('quantity');
    }

    /**
     * 장바구니 총 금액 계산
     */
    public function calculateCartTotal(?int $userId = null, ?string $sessionId = null): array
    {
        $cartItems = $this->getCartItems($userId, $sessionId);
        
        $subtotal = $cartItems->sum(function ($item) {
            return $item->product_price * $item->quantity;
        });

        $shippingCost = $this->calculateShippingCost($subtotal);
        $total = $subtotal + $shippingCost;

        return [
            'subtotal' => $subtotal,
            'shipping_cost' => $shippingCost,
            'total' => $total,
            'item_count' => $cartItems->sum('quantity'),
        ];
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 장바구니 통합 및 이전 (Cart Merge & Transfer)
    |--------------------------------------------------------------------------
    */
    // region --- 장바구니 통합 및 이전 (Cart Merge & Transfer) ---

    /**
     * 게스트 장바구니를 사용자 장바구니로 이전
     */
    public function mergeGuestCartToUser(int $userId, string $sessionId): Collection
    {
        $guestCartItems = Cart::where('session_id', $sessionId)
            ->whereNull('user_id')
            ->get();

        $userCartItems = Cart::where('user_id', $userId)->get();

        foreach ($guestCartItems as $guestItem) {
            $existingUserItem = $userCartItems->where('product_id', $guestItem->product_id)
                ->where('product_options', $guestItem->product_options)
                ->first();

            if ($existingUserItem) {
                // 기존 아이템에 수량 추가
                $newQuantity = $existingUserItem->quantity + $guestItem->quantity;
                
                if ($guestItem->product->canPurchase($newQuantity)) {
                    $existingUserItem->update(['quantity' => $newQuantity]);
                }
                
                $guestItem->delete();
            } else {
                // 게스트 아이템을 사용자 아이템으로 변경
                $guestItem->update(['user_id' => $userId]);
            }
        }

        return $this->getCartItems($userId);
    }

    /**
     * 장바구니 세션 동기화
     */
    public function syncCartSession(string $oldSessionId, string $newSessionId): int
    {
        return Cart::where('session_id', $oldSessionId)
            ->whereNull('user_id')
            ->update(['session_id' => $newSessionId]);
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 장바구니 검증 및 정리 (Cart Validation & Cleanup)
    |--------------------------------------------------------------------------
    */
    // region --- 장바구니 검증 및 정리 (Cart Validation & Cleanup) ---

    /**
     * 장바구니 유효성 검증
     */
    public function validateCart(?int $userId = null, ?string $sessionId = null): array
    {
        $cartItems = $this->getCartItems($userId, $sessionId);
        $validItems = collect();
        $invalidItems = collect();

        foreach ($cartItems as $item) {
            $product = $item->product;
            
            if (!$product || !$product->is_active || !$product->canPurchase($item->quantity)) {
                $invalidItems->push([
                    'item' => $item,
                    'reason' => !$product ? '상품이 삭제됨' : 
                               (!$product->is_active ? '판매 중단' : '재고 부족'),
                ]);
            } else {
                $validItems->push($item);
            }
        }

        return [
            'valid_items' => $validItems,
            'invalid_items' => $invalidItems,
            'is_valid' => $invalidItems->isEmpty(),
        ];
    }

    /**
     * 오래된 장바구니 아이템 정리
     */
    public function cleanupOldCartItems(int $days = 30): int
    {
        return Cart::where('created_at', '<', now()->subDays($days))
            ->whereNull('user_id')
            ->delete();
    }

    /**
     * 품절 상품 장바구니에서 제거
     */
    public function removeOutOfStockItems(?int $userId = null, ?string $sessionId = null): int
    {
        $cartItems = $this->getCartItems($userId, $sessionId);
        $removedCount = 0;

        foreach ($cartItems as $item) {
            $product = $item->product;
            
            if (!$product || !$product->is_active || !$product->canPurchase($item->quantity)) {
                $item->delete();
                $removedCount++;
            }
        }

        return $removedCount;
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 비공개 헬퍼 메서드 (Private Helper Methods)
    |--------------------------------------------------------------------------
    */
    // region --- 비공개 헬퍼 메서드 (Private Helper Methods) ---

    /**
     * 기존 장바구니 아이템 찾기
     */
    private function findExistingCartItem(
        int $productId,
        array $options = [],
        ?int $userId = null,
        ?string $sessionId = null
    ): ?Cart {
        $query = Cart::where('product_id', $productId)
            ->where('product_options', $options);

        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($sessionId) {
            $query->where('session_id', $sessionId);
        } else {
            $query->where(function ($q) {
                $q->where('user_id', auth()->id())
                  ->orWhere('session_id', Session::getId());
            });
        }

        return $query->first();
    }

    /**
     * 배송비 계산
     */
    private function calculateShippingCost(float $subtotal): float
    {
        $freeShippingThreshold = config('shop.free_shipping_threshold', 50000);
        $defaultShippingCost = config('shop.default_shipping_cost', 3000);

        return $subtotal >= $freeShippingThreshold ? 0 : $defaultShippingCost;
    }

    // endregion
}