<?php

namespace App\Models\Ahhob\Shop;

use App\Enums\PaymentStatus;
use App\Enums\PaymentGateway;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'order_id',
        'payment_gateway',
        'payment_method',
        'amount',
        'currency',
        'status',
        'gateway_request',
        'gateway_response',
        'gateway_transaction_id',
        'card_number',
        'card_company',
        'approval_number',
        'approval_at',
        'failure_reason',
        'cancel_reason',
        'cancelled_at',
        'refund_amount',
        'refund_reason',
        'refunded_at',
        'retry_count',
        'last_retry_at',
        'webhook_data',
        'webhook_received_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'gateway_request' => 'array',
        'gateway_response' => 'array',
        'webhook_data' => 'array',
        'approval_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'refunded_at' => 'datetime',
        'last_retry_at' => 'datetime',
        'status' => PaymentStatus::class,
        'payment_gateway' => PaymentGateway::class,
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * 결제 승인 처리
     */
    public function approve(array $gatewayResponse, string $approvalNumber = null): void
    {
        $this->update([
            'status' => PaymentStatus::COMPLETED,
            'gateway_response' => $gatewayResponse,
            'approval_number' => $approvalNumber,
            'approval_at' => now(),
        ]);

        // 주문 상태 업데이트
        $this->order->updatePaymentStatus('paid');
    }

    /**
     * 결제 실패 처리
     */
    public function fail(string $reason, array $gatewayResponse = []): void
    {
        $this->update([
            'status' => PaymentStatus::FAILED,
            'failure_reason' => $reason,
            'gateway_response' => $gatewayResponse,
        ]);

        // 주문 상태 업데이트
        $this->order->updatePaymentStatus('failed');
    }

    /**
     * 결제 취소 처리
     */
    public function cancel(string $reason): void
    {
        $this->update([
            'status' => PaymentStatus::CANCELLED,
            'cancel_reason' => $reason,
            'cancelled_at' => now(),
        ]);

        // 주문 상태 업데이트
        $this->order->updatePaymentStatus('cancelled');
    }

    /**
     * 환불 처리
     */
    public function refund(float $amount, string $reason): void
    {
        $this->update([
            'status' => PaymentStatus::REFUNDED,
            'refund_amount' => $amount,
            'refund_reason' => $reason,
            'refunded_at' => now(),
        ]);

        // 주문 상태 업데이트
        $this->order->updatePaymentStatus('refunded');
    }

    /**
     * 재시도 횟수 증가
     */
    public function incrementRetry(): void
    {
        $this->increment('retry_count');
        $this->update(['last_retry_at' => now()]);
    }

    /**
     * 웹훅 데이터 저장
     */
    public function saveWebhookData(array $data): void
    {
        $this->update([
            'webhook_data' => $data,
            'webhook_received_at' => now(),
        ]);
    }

    /**
     * 결제 가능 여부 확인
     */
    public function canBePaid(): bool
    {
        return in_array($this->status, [PaymentStatus::PENDING, PaymentStatus::PROCESSING]);
    }

    /**
     * 취소 가능 여부 확인
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [PaymentStatus::PENDING, PaymentStatus::PROCESSING, PaymentStatus::COMPLETED]);
    }

    /**
     * 환불 가능 여부 확인
     */
    public function canBeRefunded(): bool
    {
        return $this->status === PaymentStatus::COMPLETED && $this->refund_amount < $this->amount;
    }

    /**
     * 재시도 가능 여부 확인
     */
    public function canRetry(): bool
    {
        return $this->status === PaymentStatus::FAILED && $this->retry_count < 3;
    }

    /**
     * 포맷된 금액 반환
     */
    public function getFormattedAmountAttribute(): string
    {
        return '₩' . number_format($this->amount);
    }

    /**
     * 포맷된 환불 금액 반환
     */
    public function getFormattedRefundAmountAttribute(): string
    {
        return '₩' . number_format($this->refund_amount);
    }

    /**
     * 상태 라벨 반환
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            PaymentStatus::PENDING => '대기중',
            PaymentStatus::PROCESSING => '처리중',
            PaymentStatus::COMPLETED => '완료',
            PaymentStatus::FAILED => '실패',
            PaymentStatus::CANCELLED => '취소',
            PaymentStatus::REFUNDED => '환불',
            PaymentStatus::EXPIRED => '만료',
            default => '알 수 없음',
        };
    }

    /**
     * 결제 게이트웨이 라벨 반환
     */
    public function getPaymentGatewayLabelAttribute(): string
    {
        return match ($this->payment_gateway) {
            PaymentGateway::INICIS => '이니시스',
            PaymentGateway::KG_INICIS => 'KG이니시스',
            PaymentGateway::STRIPE => 'Stripe',
            default => $this->payment_gateway,
        };
    }

    /**
     * 마스킹된 카드번호 반환
     */
    public function getMaskedCardNumberAttribute(): ?string
    {
        if (!$this->card_number) {
            return null;
        }

        return substr($this->card_number, 0, 4) . '-****-****-' . substr($this->card_number, -4);
    }
}