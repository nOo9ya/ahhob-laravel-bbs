<?php

namespace App\Services\Shop;

use App\Models\Ahhob\Shop\Order;
use App\Models\Ahhob\Shop\OrderItem;
use App\Models\Ahhob\Shop\Cart;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    public function __construct(
        private CartService $cartService
    ) {}

    /*
    |--------------------------------------------------------------------------
    | 주문 생성 및 처리 (Order Creation & Processing)
    |--------------------------------------------------------------------------
    */
    // region --- 주문 생성 및 처리 (Order Creation & Processing) ---

    /**
     * 주문 생성
     */
    public function createOrder(array $orderData, ?int $userId = null, ?string $sessionId = null): Order
    {
        $cartItems = $this->cartService->getCartItems($userId, $sessionId);
        
        if ($cartItems->isEmpty()) {
            throw new \InvalidArgumentException('장바구니가 비어있습니다.');
        }

        // 장바구니 유효성 검증
        $validation = $this->cartService->validateCart($userId, $sessionId);
        if (!$validation['is_valid']) {
            throw new \InvalidArgumentException('장바구니에 유효하지 않은 상품이 있습니다.');
        }

        $cartTotal = $this->cartService->calculateCartTotal($userId, $sessionId);

        DB::beginTransaction();
        try {
            // 주문 생성
            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'user_id' => $userId ?? auth()->id(),
                'status' => 'pending',
                'payment_status' => 'pending',
                
                // 고객 정보
                'customer_name' => $orderData['customer_name'],
                'customer_email' => $orderData['customer_email'],
                'customer_phone' => $orderData['customer_phone'],
                
                // 배송 정보
                'shipping_name' => $orderData['shipping_name'],
                'shipping_phone' => $orderData['shipping_phone'],
                'shipping_postal_code' => $orderData['shipping_postal_code'],
                'shipping_city' => $orderData['shipping_city'],
                'shipping_state' => $orderData['shipping_state'],
                'shipping_address_line1' => $orderData['shipping_address_line1'],
                'shipping_address_line2' => $orderData['shipping_address_line2'] ?? null,
                'shipping_notes' => $orderData['shipping_notes'] ?? null,
                
                // 금액 정보
                'subtotal_amount' => $cartTotal['subtotal'],
                'shipping_cost' => $cartTotal['shipping_cost'],
                'total_amount' => $cartTotal['total'],
                
                // 결제 정보
                'payment_method' => $orderData['payment_method'],
                'currency' => 'KRW',
            ]);

            // 주문 아이템 생성
            foreach ($cartItems as $cartItem) {
                $this->createOrderItem($order, $cartItem);
            }

            // 장바구니 비우기
            $this->cartService->clearCart($userId, $sessionId);

            DB::commit();
            return $order->fresh(['orderItems.product']);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 주문 아이템 생성
     */
    private function createOrderItem(Order $order, Cart $cartItem): OrderItem
    {
        return OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $cartItem->product_id,
            'product_name' => $cartItem->product_name,
            'product_sku' => $cartItem->product_sku,
            'product_image' => $cartItem->product_image,
            'unit_price' => $cartItem->product_price,
            'quantity' => $cartItem->quantity,
            'total_price' => $cartItem->product_price * $cartItem->quantity,
            'product_options' => $cartItem->product_options,
        ]);
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 주문 상태 관리 (Order Status Management)
    |--------------------------------------------------------------------------
    */
    // region --- 주문 상태 관리 (Order Status Management) ---

    /**
     * 주문 상태 업데이트
     */
    public function updateOrderStatus(int $orderId, string $status, ?string $notes = null): Order
    {
        $order = Order::findOrFail($orderId);
        
        $validStatuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];
        if (!in_array($status, $validStatuses)) {
            throw new \InvalidArgumentException('유효하지 않은 주문 상태입니다.');
        }

        $oldStatus = $order->status;
        $order->update([
            'status' => $status,
            'status_notes' => $notes,
            'status_updated_at' => now(),
        ]);

        // 상태 변경 이력 기록
        $this->logStatusChange($order, $oldStatus, $status, $notes);

        // 상태별 후처리
        $this->handleStatusChange($order, $status);

        return $order->fresh();
    }

    /**
     * 결제 상태 업데이트
     */
    public function updatePaymentStatus(int $orderId, string $paymentStatus): Order
    {
        $order = Order::findOrFail($orderId);
        
        $validStatuses = ['pending', 'processing', 'paid', 'failed', 'cancelled', 'refunded'];
        if (!in_array($paymentStatus, $validStatuses)) {
            throw new \InvalidArgumentException('유효하지 않은 결제 상태입니다.');
        }

        $order->update([
            'payment_status' => $paymentStatus,
            'payment_status_updated_at' => now(),
        ]);

        // 결제 완료 시 주문 상태도 업데이트
        if ($paymentStatus === 'paid' && $order->status === 'pending') {
            $this->updateOrderStatus($orderId, 'confirmed', '결제 완료');
        }

        return $order->fresh();
    }

    /**
     * 주문 취소
     */
    public function cancelOrder(int $orderId, string $reason = ''): Order
    {
        $order = Order::findOrFail($orderId);
        
        if (!$order->canBeCancelled()) {
            throw new \InvalidArgumentException('취소할 수 없는 주문 상태입니다.');
        }

        DB::beginTransaction();
        try {
            // 재고 복구
            $this->restoreInventory($order);
            
            // 주문 상태 업데이트
            $order->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancel_reason' => $reason,
            ]);

            DB::commit();
            return $order->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 주문 조회 및 통계 (Order Retrieval & Statistics)
    |--------------------------------------------------------------------------
    */
    // region --- 주문 조회 및 통계 (Order Retrieval & Statistics) ---

    /**
     * 사용자 주문 목록
     */
    public function getUserOrders(int $userId, array $filters = [], int $perPage = 15)
    {
        $query = Order::where('user_id', $userId)
            ->with(['orderItems.product'])
            ->orderBy('created_at', 'desc');

        // 필터 적용
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage);
    }

    /**
     * 주문 통계
     */
    public function getOrderStatistics(array $filters = []): array
    {
        $query = Order::query();

        // 날짜 필터 적용
        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $totalOrders = $query->count();
        $totalRevenue = $query->where('payment_status', 'paid')->sum('total_amount');
        
        $statusCounts = $query->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $paymentStatusCounts = $query->select('payment_status', DB::raw('count(*) as count'))
            ->groupBy('payment_status')
            ->pluck('count', 'payment_status');

        return [
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue,
            'average_order_value' => $totalOrders > 0 ? $totalRevenue / $totalOrders : 0,
            'status_breakdown' => $statusCounts,
            'payment_status_breakdown' => $paymentStatusCounts,
        ];
    }

    /**
     * 일일 주문 통계
     */
    public function getDailyOrderStats(int $days = 30): \Illuminate\Support\Collection
    {
        return Order::selectRaw('DATE(created_at) as date, COUNT(*) as order_count, SUM(total_amount) as total_revenue')
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 주문 관리 기능 (Order Management Functions)
    |--------------------------------------------------------------------------
    */
    // region --- 주문 관리 기능 (Order Management Functions) ---

    /**
     * 배송 정보 업데이트
     */
    public function updateShippingInfo(int $orderId, array $shippingData): Order
    {
        $order = Order::findOrFail($orderId);
        
        $order->update([
            'shipping_company' => $shippingData['shipping_company'] ?? null,
            'tracking_number' => $shippingData['tracking_number'] ?? null,
            'shipped_at' => $shippingData['shipped_at'] ?? now(),
        ]);

        // 배송 시작 시 주문 상태 업데이트
        if (isset($shippingData['tracking_number']) && $order->status === 'processing') {
            $this->updateOrderStatus($orderId, 'shipped', '배송 시작');
        }

        return $order->fresh();
    }

    /**
     * 주문 환불 처리
     */
    public function refundOrder(int $orderId, float $refundAmount = null, string $reason = ''): Order
    {
        $order = Order::findOrFail($orderId);
        
        if (!$order->canBeRefunded()) {
            throw new \InvalidArgumentException('환불할 수 없는 주문 상태입니다.');
        }

        $refundAmount = $refundAmount ?? $order->total_amount;
        
        if ($refundAmount > $order->total_amount) {
            throw new \InvalidArgumentException('환불 금액이 주문 금액을 초과할 수 없습니다.');
        }

        DB::beginTransaction();
        try {
            // 재고 복구
            $this->restoreInventory($order);
            
            // 주문 상태 업데이트
            $order->update([
                'payment_status' => 'refunded',
                'refund_amount' => $refundAmount,
                'refund_reason' => $reason,
                'refunded_at' => now(),
            ]);

            DB::commit();
            return $order->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 비공개 헬퍼 메서드 (Private Helper Methods)
    |--------------------------------------------------------------------------
    */
    // region --- 비공개 헬퍼 메서드 (Private Helper Methods) ---

    /**
     * 주문 번호 생성
     */
    private function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'ORD' . date('Ymd') . Str::upper(Str::random(6));
        } while (Order::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    /**
     * 재고 복구
     */
    private function restoreInventory(Order $order): void
    {
        foreach ($order->orderItems as $item) {
            $product = $item->product;
            if ($product && $product->track_stock) {
                $product->increment('stock_quantity', $item->quantity);
            }
        }
    }

    /**
     * 상태 변경 이력 기록
     */
    private function logStatusChange(Order $order, string $oldStatus, string $newStatus, ?string $notes): void
    {
        // 상태 변경 로그 기록 (별도 모델 필요시 구현)
        logger()->info('Order status changed', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'notes' => $notes,
            'changed_by' => auth()->id(),
        ]);
    }

    /**
     * 상태 변경 후처리
     */
    private function handleStatusChange(Order $order, string $status): void
    {
        switch ($status) {
            case 'confirmed':
                // 재고 차감
                $this->deductInventory($order);
                break;
                
            case 'delivered':
                // 배송 완료 처리
                $order->update(['delivered_at' => now()]);
                break;
        }
    }

    /**
     * 재고 차감
     */
    private function deductInventory(Order $order): void
    {
        foreach ($order->orderItems as $item) {
            $product = $item->product;
            if ($product && $product->track_stock) {
                $product->decrement('stock_quantity', $item->quantity);
            }
        }
    }

    // endregion
}