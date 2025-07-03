<?php

namespace App\Models\Ahhob\Shop;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;

class Order extends Model
{
    use HasFactory;

    protected $table = 'shop_orders';

    /*
    |--------------------------------------------------------------------------
    | 모델 속성 (Attributes & Properties)
    |--------------------------------------------------------------------------
    */
    // region --- 모델 속성 (Attributes & Properties) ---

    protected $fillable = [
        'order_number',
        'user_id',
        'status',
        'payment_status',
        'payment_method',
        'subtotal',
        'shipping_cost',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'customer_name',
        'customer_email',
        'customer_phone',
        'shipping_name',
        'shipping_phone',
        'shipping_address_line1',
        'shipping_address_line2',
        'shipping_city',
        'shipping_state',
        'shipping_postal_code',
        'shipping_notes',
        'tracking_number',
        'shipping_company',
        'payment_transaction_id',
        'payment_response',
        'coupon_code',
        'coupon_discount',
        'admin_notes',
        'confirmed_at',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'coupon_discount' => 'decimal:2',
        'payment_response' => 'array',
        'confirmed_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 관계 (Relationships)
    |--------------------------------------------------------------------------
    */
    // region --- 관계 (Relationships) ---

    /**
     * 주문자
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 주문 상품들
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * 결제 트랜잭션들
     */
    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    /**
     * 최신 결제 트랜잭션
     */
    public function latestPaymentTransaction(): HasMany
    {
        return $this->paymentTransactions()->latest();
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 쿼리 스코프 (Query Scopes)
    |--------------------------------------------------------------------------
    */
    // region --- 쿼리 스코프 (Query Scopes) ---

    /**
     * 특정 상태의 주문만 조회
     */
    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * 특정 결제 상태의 주문만 조회
     */
    public function scopePaymentStatus(Builder $query, string $paymentStatus): Builder
    {
        return $query->where('payment_status', $paymentStatus);
    }

    /**
     * 특정 사용자의 주문들
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * 최신 순으로 정렬
     */
    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * 특정 기간의 주문들
     */
    public function scopeDateRange(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 접근자 & 변경자 (Accessors & Mutators)
    |--------------------------------------------------------------------------
    */
    // region --- 접근자 & 변경자 (Accessors & Mutators) ---

    /**
     * 주문 상태 레이블
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => '주문 접수',
            'confirmed' => '주문 확인',
            'processing' => '처리 중',
            'shipped' => '배송 중',
            'delivered' => '배송 완료',
            'cancelled' => '주문 취소',
            'refunded' => '환불',
            default => '알 수 없음',
        };
    }

    /**
     * 결제 상태 레이블
     */
    public function getPaymentStatusLabelAttribute(): string
    {
        return match($this->payment_status) {
            'pending' => '결제 대기',
            'paid' => '결제 완료',
            'failed' => '결제 실패',
            'refunded' => '환불 완료',
            'partially_refunded' => '부분 환불',
            default => '알 수 없음',
        };
    }

    /**
     * 결제 방법 레이블
     */
    public function getPaymentMethodLabelAttribute(): ?string
    {
        return match($this->payment_method) {
            'card' => '신용카드',
            'bank_transfer' => '계좌이체',
            'virtual_account' => '가상계좌',
            'mobile' => '휴대폰',
            'point' => '포인트',
            default => null,
        };
    }

    /**
     * 포맷된 총 금액
     */
    public function getFormattedTotalAmountAttribute(): string
    {
        return '₩' . number_format($this->total_amount);
    }

    /**
     * 포맷된 배송비
     */
    public function getFormattedShippingCostAttribute(): string
    {
        return '₩' . number_format($this->shipping_cost);
    }

    /**
     * 전체 배송 주소
     */
    public function getFullShippingAddressAttribute(): string
    {
        $address = $this->shipping_address_line1;
        
        if ($this->shipping_address_line2) {
            $address .= ' ' . $this->shipping_address_line2;
        }
        
        return "{$this->shipping_city} {$this->shipping_state} {$address} ({$this->shipping_postal_code})";
    }

    /**
     * 주문 상태 색상 (UI용)
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'yellow',
            'confirmed' => 'blue',
            'processing' => 'indigo',
            'shipped' => 'purple',
            'delivered' => 'green',
            'cancelled' => 'red',
            'refunded' => 'gray',
            default => 'gray',
        };
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 공개 메서드 (Public Methods)
    |--------------------------------------------------------------------------
    */
    // region --- 공개 메서드 (Public Methods) ---

    /**
     * 주문번호 생성
     */
    public static function generateOrderNumber(): string
    {
        $prefix = 'ORD';
        $date = now()->format('Ymd');
        $random = str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        
        return "{$prefix}{$date}{$random}";
    }

    /**
     * 주문 상태 변경
     */
    public function updateStatus(string $status, ?string $adminNotes = null): void
    {
        $this->status = $status;
        
        if ($adminNotes) {
            $this->admin_notes = $adminNotes;
        }

        // 상태별 타임스탬프 업데이트
        match($status) {
            'confirmed' => $this->confirmed_at = now(),
            'shipped' => $this->shipped_at = now(),
            'delivered' => $this->delivered_at = now(),
            'cancelled' => $this->cancelled_at = now(),
            default => null,
        };

        $this->save();
    }

    /**
     * 결제 상태 변경
     */
    public function updatePaymentStatus(string $paymentStatus, ?array $paymentResponse = null): void
    {
        $this->payment_status = $paymentStatus;
        
        if ($paymentResponse) {
            $this->payment_response = $paymentResponse;
        }

        $this->save();
    }

    /**
     * 배송 정보 업데이트
     */
    public function updateShippingInfo(string $trackingNumber, string $shippingCompany): void
    {
        $this->tracking_number = $trackingNumber;
        $this->shipping_company = $shippingCompany;
        
        if ($this->status === 'processing') {
            $this->updateStatus('shipped');
        }

        $this->save();
    }

    /**
     * 주문 취소 가능 여부 확인
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed']) 
            && $this->payment_status !== 'refunded';
    }

    /**
     * 환불 가능 여부 확인
     */
    public function canBeRefunded(): bool
    {
        return $this->payment_status === 'paid' 
            && !in_array($this->status, ['cancelled', 'refunded']);
    }

    /**
     * 결제 가능 여부 확인
     */
    public function canBePaid(): bool
    {
        return $this->status === 'pending' 
            && in_array($this->payment_status, ['pending', 'failed']);
    }

    /**
     * 총 주문 금액 재계산
     */
    public function recalculateTotal(): void
    {
        $this->subtotal = $this->items->sum('total_price');
        $this->total_amount = $this->subtotal + $this->shipping_cost + $this->tax_amount - $this->discount_amount - $this->coupon_discount;
        $this->save();
    }

    /**
     * 주문 완료 처리
     */
    public function markAsCompleted(): void
    {
        // 재고 차감
        foreach ($this->items as $item) {
            $item->product->decrementStock($item->quantity);
            $item->product->incrementSales($item->quantity);
        }

        // 상태 업데이트
        $this->updateStatus('confirmed');
        $this->updatePaymentStatus('paid');
    }

    // endregion
}