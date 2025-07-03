<?php

namespace App\Models\Ahhob\Shop;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;

class InventoryNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'email',
        'phone',
        'notification_type',
        'is_notified',
        'notified_at',
        'is_active',
    ];

    protected $casts = [
        'is_notified' => 'boolean',
        'is_active' => 'boolean',
        'notified_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | 관계 (Relationships)
    |--------------------------------------------------------------------------
    */

    /**
     * 사용자
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 상품
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * 알림 발송 내역
     */
    public function logs(): HasMany
    {
        return $this->hasMany(InventoryNotificationLog::class, 'notification_id');
    }

    /*
    |--------------------------------------------------------------------------
    | 쿼리 스코프 (Query Scopes)
    |--------------------------------------------------------------------------
    */

    /**
     * 활성화된 알림만
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * 아직 알림이 가지 않은 것만
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('is_notified', false);
    }

    /**
     * 특정 상품에 대한 알림
     */
    public function scopeForProduct(Builder $query, int $productId): Builder
    {
        return $query->where('product_id', $productId);
    }

    /**
     * 특정 사용자의 알림
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /*
    |--------------------------------------------------------------------------
    | 접근자 & 변경자 (Accessors & Mutators)
    |--------------------------------------------------------------------------
    */

    /**
     * 알림 받을 연락처 반환
     */
    public function getRecipientAttribute(): string
    {
        if ($this->notification_type === 'email' || $this->notification_type === 'both') {
            return $this->email ?? $this->user?->email ?? '';
        }
        
        if ($this->notification_type === 'sms') {
            return $this->phone ?? $this->user?->phone ?? '';
        }

        return '';
    }

    /**
     * 알림 타입 레이블
     */
    public function getNotificationTypeLabelAttribute(): string
    {
        return match($this->notification_type) {
            'email' => '이메일',
            'sms' => 'SMS',
            'both' => '이메일 + SMS',
            default => '알 수 없음',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | 공개 메서드 (Public Methods)
    |--------------------------------------------------------------------------
    */

    /**
     * 알림 전송
     */
    public function sendNotification(): bool
    {
        if ($this->is_notified || !$this->is_active) {
            return false;
        }

        $success = false;

        // 이메일 알림
        if ($this->notification_type === 'email' || $this->notification_type === 'both') {
            $emailRecipient = $this->email ?? $this->user?->email;
            if ($emailRecipient) {
                $success = $this->sendEmailNotification($emailRecipient) || $success;
            }
        }

        // SMS 알림
        if ($this->notification_type === 'sms' || $this->notification_type === 'both') {
            $phoneRecipient = $this->phone ?? $this->user?->phone;
            if ($phoneRecipient) {
                $success = $this->sendSmsNotification($phoneRecipient) || $success;
            }
        }

        if ($success) {
            $this->markAsNotified();
        }

        return $success;
    }

    /**
     * 이메일 알림 전송
     */
    private function sendEmailNotification(string $email): bool
    {
        try {
            $content = $this->generateEmailContent();
            
            // 실제 이메일 전송 로직 (Mail 파사드 사용)
            // Mail::to($email)->send(new InventoryRestockNotification($this->product));
            
            // 로그 기록
            $this->logs()->create([
                'product_id' => $this->product_id,
                'channel' => 'email',
                'recipient' => $email,
                'content' => $content,
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logs()->create([
                'product_id' => $this->product_id,
                'channel' => 'email',
                'recipient' => $email,
                'content' => $this->generateEmailContent(),
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * SMS 알림 전송
     */
    private function sendSmsNotification(string $phone): bool
    {
        try {
            $content = $this->generateSmsContent();
            
            // 실제 SMS 전송 로직
            // SMS::send($phone, $content);
            
            // 로그 기록
            $this->logs()->create([
                'product_id' => $this->product_id,
                'channel' => 'sms',
                'recipient' => $phone,
                'content' => $content,
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logs()->create([
                'product_id' => $this->product_id,
                'channel' => 'sms',
                'recipient' => $phone,
                'content' => $this->generateSmsContent(),
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * 알림 완료로 표시
     */
    public function markAsNotified(): void
    {
        $this->update([
            'is_notified' => true,
            'notified_at' => now(),
        ]);
    }

    /**
     * 알림 비활성화
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * 이메일 내용 생성
     */
    private function generateEmailContent(): string
    {
        return sprintf(
            "[재입고 알림] %s 상품이 재입고되었습니다!\n\n" .
            "안녕하세요, %s님\n\n" .
            "요청하신 '%s' 상품이 재입고되었습니다.\n" .
            "지금 바로 확인해보세요!\n\n" .
            "상품 가격: %s원\n" .
            "재고 수량: %d개\n\n" .
            "놓치지 마세요!",
            $this->product->name,
            $this->user?->name ?? '고객',
            $this->product->name,
            number_format($this->product->price),
            $this->product->stock_quantity
        );
    }

    /**
     * SMS 내용 생성
     */
    private function generateSmsContent(): string
    {
        return sprintf(
            "[재입고 알림] %s 상품이 재입고되었습니다! 지금 확인해보세요. 가격: %s원",
            $this->product->name,
            number_format($this->product->price)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | 정적 메서드 (Static Methods)
    |--------------------------------------------------------------------------
    */

    /**
     * 상품 재입고 시 알림 전송
     */
    public static function notifyRestock(Product $product): int
    {
        $notifications = static::active()
            ->pending()
            ->forProduct($product->id)
            ->get();

        $sentCount = 0;

        foreach ($notifications as $notification) {
            if ($notification->sendNotification()) {
                $sentCount++;
            }
        }

        return $sentCount;
    }

    /**
     * 사용자의 알림 신청
     */
    public static function requestNotification(
        User $user, 
        Product $product, 
        string $type = 'email',
        ?string $email = null,
        ?string $phone = null
    ): self {
        return static::updateOrCreate([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ], [
            'email' => $email ?? $user->email,
            'phone' => $phone ?? $user->phone,
            'notification_type' => $type,
            'is_notified' => false,
            'is_active' => true,
        ]);
    }
}