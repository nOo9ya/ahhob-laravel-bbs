<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => '대기중',
            self::PROCESSING => '처리중',
            self::COMPLETED => '완료',
            self::FAILED => '실패',
            self::CANCELLED => '취소',
            self::REFUNDED => '환불',
            self::EXPIRED => '만료',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'yellow',
            self::PROCESSING => 'blue',
            self::COMPLETED => 'green',
            self::FAILED => 'red',
            self::CANCELLED => 'gray',
            self::REFUNDED => 'purple',
            self::EXPIRED => 'gray',
        };
    }

    public function isSuccess(): bool
    {
        return $this === self::COMPLETED;
    }

    public function isFinal(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::FAILED,
            self::CANCELLED,
            self::REFUNDED,
            self::EXPIRED,
        ]);
    }

    public function canTransitionTo(self $status): bool
    {
        return match ($this) {
            self::PENDING => in_array($status, [
                self::PROCESSING,
                self::COMPLETED,
                self::FAILED,
                self::CANCELLED,
                self::EXPIRED,
            ]),
            self::PROCESSING => in_array($status, [
                self::COMPLETED,
                self::FAILED,
                self::CANCELLED,
            ]),
            self::COMPLETED => in_array($status, [
                self::REFUNDED,
                self::CANCELLED,
            ]),
            default => false,
        };
    }
}