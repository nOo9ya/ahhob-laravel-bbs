<?php

namespace App\Http\Controllers\Ahhob\Shop;

use App\Http\Controllers\Controller;
use App\Models\Ahhob\Shop\Order;
use App\Models\Ahhob\Shop\OrderItem;
use App\Models\Ahhob\Shop\Cart;
use App\Models\Ahhob\Shop\Coupon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | 주문 컨트롤러 (Order Controller)
    |--------------------------------------------------------------------------
    */
    
    /**
     * 내 주문 목록
     */
    public function index(): View
    {
        $orders = Order::with(['items.product'])
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(10);
        
        return view('ahhob.shop.orders.index', compact('orders'));
    }
    
    /**
     * 주문서 작성
     */
    public function create(): View
    {
        $cartItems = Cart::with(['product'])
            ->where('user_id', auth()->id())
            ->get();
        
        if ($cartItems->isEmpty()) {
            return redirect()->route('ahhob.shop.cart.index')
                ->with('error', '장바구니가 비어있습니다.');
        }
        
        // 재고 재확인
        foreach ($cartItems as $item) {
            if (!$item->product->canPurchase($item->quantity)) {
                return redirect()->route('ahhob.shop.cart.index')
                    ->with('error', "{$item->product->name} 상품의 재고가 부족합니다.");
            }
        }
        
        $subtotal = $cartItems->sum('total_price');
        $shippingCost = $this->calculateShippingCost($subtotal);
        $total = $subtotal + $shippingCost;
        
        return view('ahhob.shop.orders.create', compact(
            'cartItems',
            'subtotal', 
            'shippingCost',
            'total'
        ));
    }
    
    /**
     * 주문 생성
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:20',
            'shipping_name' => 'required|string|max:255',
            'shipping_phone' => 'required|string|max:20',
            'shipping_address_line1' => 'required|string|max:255',
            'shipping_address_line2' => 'nullable|string|max:255',
            'shipping_city' => 'required|string|max:100',
            'shipping_state' => 'required|string|max:100',
            'shipping_postal_code' => 'required|string|max:10',
            'shipping_notes' => 'nullable|string|max:500',
            'coupon_code' => 'nullable|string|max:50',
            'payment_method' => 'required|in:card,bank_transfer,virtual_account',
        ]);
        
        $cartItems = Cart::with(['product'])
            ->where('user_id', auth()->id())
            ->get();
        
        if ($cartItems->isEmpty()) {
            return redirect()->route('ahhob.shop.cart.index')
                ->with('error', '장바구니가 비어있습니다.');
        }
        
        DB::beginTransaction();
        
        try {
            // 주문 생성
            $subtotal = $cartItems->sum('total_price');
            $shippingCost = $this->calculateShippingCost($subtotal);
            $couponDiscount = 0;
            
            // 쿠폰 적용
            if ($request->filled('coupon_code')) {
                $coupon = Coupon::where('code', $request->coupon_code)->first();
                if ($coupon && $coupon->canBeUsed(auth()->user(), $subtotal)['can_use']) {
                    $couponDiscount = $coupon->calculateDiscount($subtotal);
                    $coupon->incrementUsage();
                }
            }
            
            $totalAmount = $subtotal + $shippingCost - $couponDiscount;
            
            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'user_id' => auth()->id(),
                'status' => 'pending',
                'payment_status' => 'pending',
                'payment_method' => $request->payment_method,
                'subtotal' => $subtotal,
                'shipping_cost' => $shippingCost,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'coupon_discount' => $couponDiscount,
                'total_amount' => $totalAmount,
                'customer_name' => $request->customer_name,
                'customer_email' => $request->customer_email,
                'customer_phone' => $request->customer_phone,
                'shipping_name' => $request->shipping_name,
                'shipping_phone' => $request->shipping_phone,
                'shipping_address_line1' => $request->shipping_address_line1,
                'shipping_address_line2' => $request->shipping_address_line2,
                'shipping_city' => $request->shipping_city,
                'shipping_state' => $request->shipping_state,
                'shipping_postal_code' => $request->shipping_postal_code,
                'shipping_notes' => $request->shipping_notes,
                'coupon_code' => $request->coupon_code,
            ]);
            
            // 주문 아이템 생성
            foreach ($cartItems as $cartItem) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'product_name' => $cartItem->product_name,
                    'product_sku' => $cartItem->product_sku,
                    'product_image' => $cartItem->product_image,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $cartItem->unit_price,
                    'total_price' => $cartItem->total_price,
                    'status' => 'pending',
                ]);
            }
            
            // 장바구니 비우기
            Cart::where('user_id', auth()->id())->delete();
            
            DB::commit();
            
            return redirect()->route('ahhob.shop.orders.show', $order)
                ->with('success', '주문이 완료되었습니다.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->withInput()
                ->with('error', '주문 처리 중 오류가 발생했습니다.');
        }
    }
    
    /**
     * 주문 상세
     */
    public function show(Order $order): View
    {
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }
        
        $order->load(['items.product']);
        
        return view('ahhob.shop.orders.show', compact('order'));
    }
    
    /**
     * 주문 취소
     */
    public function cancel(Order $order): RedirectResponse
    {
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }
        
        if (!$order->canBeCancelled()) {
            return redirect()->back()
                ->with('error', '취소할 수 없는 주문입니다.');
        }
        
        $order->updateStatus('cancelled', '고객 요청으로 취소');
        
        return redirect()->back()
            ->with('success', '주문이 취소되었습니다.');
    }
    
    /**
     * 배송비 계산
     */
    private function calculateShippingCost(float $subtotal): float
    {
        return $subtotal >= 50000 ? 0 : 3000;
    }
}