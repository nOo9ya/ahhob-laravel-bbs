<?php

namespace App\Services\Shop;

use App\Models\Ahhob\Shop\Coupon;
use App\Models\Ahhob\Shop\CouponUsage;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CouponService
{
    /*
    |--------------------------------------------------------------------------
    | 쿠폰 검증 및 적용 (Coupon Validation & Application)
    |--------------------------------------------------------------------------
    */
    // region --- 쿠폰 검증 및 적용 (Coupon Validation & Application) ---

    /**
     * 쿠폰 유효성 검증
     */
    public function validateCoupon(string $couponCode, int $userId, float $orderAmount = 0): array
    {
        $coupon = Coupon::where('code', $couponCode)
            ->where('is_active', true)
            ->first();

        if (!$coupon) {
            return [
                'valid' => false,
                'message' => '존재하지 않는 쿠폰입니다.',
                'coupon' => null,
            ];
        }

        // 사용 기간 확인
        if (!$coupon->isValidPeriod()) {
            return [
                'valid' => false,
                'message' => '쿠폰 사용 기간이 아닙니다.',
                'coupon' => $coupon,
            ];
        }

        // 최소 주문금액 확인
        if ($coupon->minimum_order_amount && $orderAmount < $coupon->minimum_order_amount) {
            return [
                'valid' => false,
                'message' => '최소 주문금액 ' . number_format($coupon->minimum_order_amount) . '원 이상이어야 합니다.',
                'coupon' => $coupon,
            ];
        }

        // 사용 횟수 제한 확인
        if ($coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit) {
            return [
                'valid' => false,
                'message' => '쿠폰 사용 한도에 도달했습니다.',
                'coupon' => $coupon,
            ];
        }

        // 사용자별 사용 횟수 확인
        if ($coupon->user_usage_limit) {
            $userUsageCount = CouponUsage::where('coupon_id', $coupon->id)
                ->where('user_id', $userId)
                ->count();

            if ($userUsageCount >= $coupon->user_usage_limit) {
                return [
                    'valid' => false,
                    'message' => '이미 사용한 쿠폰입니다.',
                    'coupon' => $coupon,
                ];
            }
        }

        return [
            'valid' => true,
            'message' => '사용 가능한 쿠폰입니다.',
            'coupon' => $coupon,
        ];
    }

    /**
     * 쿠폰 할인 금액 계산
     */
    public function calculateDiscount(Coupon $coupon, float $orderAmount): float
    {
        $discount = 0;

        switch ($coupon->discount_type) {
            case 'fixed':
                $discount = $coupon->discount_value;
                break;
            case 'percentage':
                $discount = ($orderAmount * $coupon->discount_value) / 100;
                break;
        }

        // 최대 할인 금액 제한
        if ($coupon->maximum_discount_amount && $discount > $coupon->maximum_discount_amount) {
            $discount = $coupon->maximum_discount_amount;
        }

        // 주문 금액을 초과할 수 없음
        if ($discount > $orderAmount) {
            $discount = $orderAmount;
        }

        return round($discount, 2);
    }

    /**
     * 쿠폰 사용 처리
     */
    public function useCoupon(int $couponId, int $userId, int $orderId, float $discountAmount): CouponUsage
    {
        $coupon = Coupon::findOrFail($couponId);

        DB::beginTransaction();
        try {
            // 쿠폰 사용 기록 생성
            $couponUsage = CouponUsage::create([
                'coupon_id' => $couponId,
                'user_id' => $userId,
                'order_id' => $orderId,
                'discount_amount' => $discountAmount,
                'used_at' => now(),
            ]);

            // 쿠폰 사용 횟수 증가
            $coupon->increment('used_count');

            DB::commit();
            return $couponUsage;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 쿠폰 조회 및 관리 (Coupon Retrieval & Management)
    |--------------------------------------------------------------------------
    */
    // region --- 쿠폰 조회 및 관리 (Coupon Retrieval & Management) ---

    /**
     * 사용자 사용 가능 쿠폰 목록
     */
    public function getAvailableCoupons(int $userId, float $orderAmount = 0): Collection
    {
        $coupons = Coupon::where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('expires_at', '>=', now())
            ->where(function ($query) use ($orderAmount) {
                $query->whereNull('minimum_order_amount')
                      ->orWhere('minimum_order_amount', '<=', $orderAmount);
            })
            ->where(function ($query) {
                $query->whereNull('usage_limit')
                      ->orWhereRaw('used_count < usage_limit');
            })
            ->get();

        return $coupons->filter(function ($coupon) use ($userId) {
            if ($coupon->user_usage_limit) {
                $userUsageCount = CouponUsage::where('coupon_id', $coupon->id)
                    ->where('user_id', $userId)
                    ->count();

                return $userUsageCount < $coupon->user_usage_limit;
            }
            return true;
        });
    }

    /**
     * 쿠폰 상세 정보
     */
    public function getCouponDetails(string $couponCode): ?Coupon
    {
        return Coupon::where('code', $couponCode)
            ->where('is_active', true)
            ->first();
    }

    /**
     * 사용자 쿠폰 사용 내역
     */
    public function getUserCouponHistory(int $userId, int $perPage = 15)
    {
        return CouponUsage::with(['coupon', 'order'])
            ->where('user_id', $userId)
            ->orderBy('used_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * 쿠폰 통계
     */
    public function getCouponStatistics(int $couponId = null): array
    {
        $query = CouponUsage::query();

        if ($couponId) {
            $query->where('coupon_id', $couponId);
        }

        $totalUsage = $query->count();
        $totalDiscount = $query->sum('discount_amount');
        $averageDiscount = $totalUsage > 0 ? $totalDiscount / $totalUsage : 0;

        $popularCoupons = CouponUsage::with('coupon')
            ->select('coupon_id', DB::raw('COUNT(*) as usage_count'), DB::raw('SUM(discount_amount) as total_discount'))
            ->groupBy('coupon_id')
            ->orderBy('usage_count', 'desc')
            ->limit(10)
            ->get();

        return [
            'total_usage' => $totalUsage,
            'total_discount' => $totalDiscount,
            'average_discount' => round($averageDiscount, 2),
            'popular_coupons' => $popularCoupons,
        ];
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 쿠폰 생성 및 관리 (Coupon Creation & Management)
    |--------------------------------------------------------------------------
    */
    // region --- 쿠폰 생성 및 관리 (Coupon Creation & Management) ---

    /**
     * 쿠폰 생성
     */
    public function createCoupon(array $couponData): Coupon
    {
        // 쿠폰 코드 생성 (제공되지 않은 경우)
        if (!isset($couponData['code'])) {
            $couponData['code'] = $this->generateCouponCode();
        }

        // 쿠폰 코드 중복 확인
        while (Coupon::where('code', $couponData['code'])->exists()) {
            $couponData['code'] = $this->generateCouponCode();
        }

        return Coupon::create($couponData);
    }

    /**
     * 대량 쿠폰 생성
     */
    public function createBulkCoupons(array $couponData, int $quantity): Collection
    {
        $coupons = collect();

        DB::beginTransaction();
        try {
            for ($i = 0; $i < $quantity; $i++) {
                $data = $couponData;
                $data['code'] = $this->generateCouponCode();

                // 중복 확인
                while (Coupon::where('code', $data['code'])->exists()) {
                    $data['code'] = $this->generateCouponCode();
                }

                $coupons->push(Coupon::create($data));
            }

            DB::commit();
            return $coupons;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 쿠폰 상태 업데이트
     */
    public function updateCouponStatus(int $couponId, bool $isActive): Coupon
    {
        $coupon = Coupon::findOrFail($couponId);
        $coupon->update(['is_active' => $isActive]);

        return $coupon->fresh();
    }

    /**
     * 만료된 쿠폰 정리
     */
    public function cleanupExpiredCoupons(): int
    {
        return Coupon::where('expires_at', '<', now())
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 쿠폰 발급 및 배포 (Coupon Issuance & Distribution)
    |--------------------------------------------------------------------------
    */
    // region --- 쿠폰 발급 및 배포 (Coupon Issuance & Distribution) ---

    /**
     * 특정 사용자에게 쿠폰 발급
     */
    public function issueCouponToUser(int $userId, array $couponData): Coupon
    {
        $couponData['user_usage_limit'] = 1; // 개인 발급 쿠폰은 1회만 사용 가능
        $couponData['usage_limit'] = 1;

        return $this->createCoupon($couponData);
    }

    /**
     * 신규 가입 쿠폰 발급
     */
    public function issueWelcomeCoupon(int $userId): ?Coupon
    {
        $couponData = [
            'name' => '신규 가입 축하 쿠폰',
            'description' => '회원 가입을 축하드립니다!',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'minimum_order_amount' => 30000,
            'maximum_discount_amount' => 5000,
            'user_usage_limit' => 1,
            'usage_limit' => 1,
            'starts_at' => now(),
            'expires_at' => now()->addDays(30),
        ];

        return $this->issueCouponToUser($userId, $couponData);
    }

    /**
     * 생일 쿠폰 발급
     */
    public function issueBirthdayCoupon(int $userId): ?Coupon
    {
        $couponData = [
            'name' => '생일 축하 쿠폰',
            'description' => '생일을 축하드립니다!',
            'discount_type' => 'fixed',
            'discount_value' => 5000,
            'minimum_order_amount' => 50000,
            'user_usage_limit' => 1,
            'usage_limit' => 1,
            'starts_at' => now(),
            'expires_at' => now()->addDays(7),
        ];

        return $this->issueCouponToUser($userId, $couponData);
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 비공개 헬퍼 메서드 (Private Helper Methods)
    |--------------------------------------------------------------------------
    */
    // region --- 비공개 헬퍼 메서드 (Private Helper Methods) ---

    /**
     * 쿠폰 코드 생성
     */
    private function generateCouponCode(int $length = 8): string
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';

        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $code;
    }

    // endregion
}