<?php

namespace App\Services\Ahhob\User;

use App\Models\User;
use App\Models\Ahhob\Board\Board;
use App\Models\Ahhob\User\PostingLimit;
use App\Models\Ahhob\User\DailyActivityCount;
use Illuminate\Support\Facades\Cache;

class ActivityLimitService
{
    /**
     * 사용자가 특정 활동을 수행할 수 있는지 확인
     */
    public function canPerformActivity(
        User $user,
        string $activityType,
        Board $board = null
    ): array {
        // 1. 적용 가능한 제한 정책 조회
        $limits = $this->getApplicableLimits($user, $activityType, $board);
        
        if ($limits->isEmpty()) {
            return ['allowed' => true, 'message' => null];
        }
        
        // 2. 가장 제한적인 정책 선택 (우선순위가 높은 순)
        $mostRestrictiveLimit = $limits->sortBy(function ($limit) {
            return $limit->getPriority();
        })->first();
        
        // 3. 시간대 제한 확인
        if ($mostRestrictiveLimit->isTimeRestricted()) {
            return [
                'allowed' => false,
                'message' => '현재 시간대에는 해당 활동이 제한됩니다.',
            ];
        }
        
        // 4. 일일 제한 확인
        $todayCount = $this->getTodayActivityCount($user, $activityType, $board);
        
        if ($todayCount >= $mostRestrictiveLimit->daily_limit) {
            return [
                'allowed' => false,
                'message' => "일일 {$activityType} 제한({$mostRestrictiveLimit->daily_limit}회)을 초과했습니다.",
            ];
        }
        
        // 5. 시간당 제한 확인 (설정된 경우)
        if ($mostRestrictiveLimit->hourly_limit) {
            $hourlyCount = $this->getHourlyActivityCount($user, $activityType, $board);
            
            if ($hourlyCount >= $mostRestrictiveLimit->hourly_limit) {
                return [
                    'allowed' => false,
                    'message' => "시간당 {$activityType} 제한({$mostRestrictiveLimit->hourly_limit}회)을 초과했습니다.",
                ];
            }
        }
        
        // 6. 다중 계정 스팸 방지 확인
        if ($this->isSuspiciousActivity($user, $activityType)) {
            return [
                'allowed' => false,
                'message' => '스팸 의심 활동이 감지되었습니다. 잠시 후 다시 시도해주세요.',
            ];
        }
        
        return [
            'allowed' => true,
            'message' => null,
            'remaining' => $mostRestrictiveLimit->daily_limit - $todayCount,
        ];
    }

    /**
     * 사용자에게 적용 가능한 제한 정책 조회
     */
    private function getApplicableLimits(
        User $user,
        string $activityType,
        Board $board = null
    ) {
        return PostingLimit::forUser($user, $activityType, $board)->get();
    }

    /**
     * 오늘의 활동 카운트 조회
     */
    private function getTodayActivityCount(
        User $user,
        string $activityType,
        Board $board = null
    ): int {
        $cacheKey = "activity_count:{$user->id}:{$activityType}:" . 
                   ($board ? "board:{$board->id}" : 'global') . ':' . 
                   today()->toDateString();
        
        return Cache::remember($cacheKey, 300, function () use ($user, $activityType, $board) {
            return DailyActivityCount::getTodayCount(
                $user,
                $activityType,
                $board ? 'board' : null,
                $board?->id
            );
        });
    }

    /**
     * 시간당 활동 카운트 조회 (향후 구현)
     */
    private function getHourlyActivityCount(
        User $user,
        string $activityType,
        Board $board = null
    ): int {
        // 현재는 간단히 0 반환, 향후 시간당 카운트 테이블 구현 시 수정
        return 0;
    }

    /**
     * 스팸 의심 활동 확인
     */
    private function isSuspiciousActivity(User $user, string $activityType): bool
    {
        $cacheKey = "suspicious_check:{$user->id}:{$activityType}:" . today()->toDateString();
        
        return Cache::remember($cacheKey, 600, function () use ($user, $activityType) {
            // 오늘 해당 사용자의 활동 중 스팸 의심 활동이 있는지 확인
            $todayActivity = DailyActivityCount::forUser($user)
                ->forActivity($activityType)
                ->today()
                ->first();
            
            return $todayActivity ? $todayActivity->isSuspiciousActivity() : false;
        });
    }

    /**
     * 활동 제한 상태 조회
     */
    public function getActivityLimitStatus(User $user, string $activityType, Board $board = null): array
    {
        $limits = $this->getApplicableLimits($user, $activityType, $board);
        
        if ($limits->isEmpty()) {
            return [
                'has_limit' => false,
                'daily_limit' => null,
                'today_count' => 0,
                'remaining' => null,
            ];
        }
        
        $mostRestrictiveLimit = $limits->sortBy(function ($limit) {
            return $limit->getPriority();
        })->first();
        
        $todayCount = $this->getTodayActivityCount($user, $activityType, $board);
        
        return [
            'has_limit' => true,
            'daily_limit' => $mostRestrictiveLimit->daily_limit,
            'today_count' => $todayCount,
            'remaining' => max(0, $mostRestrictiveLimit->daily_limit - $todayCount),
            'time_restricted' => $mostRestrictiveLimit->isTimeRestricted(),
        ];
    }

    /**
     * 캐시 무효화
     */
    public function clearActivityCache(User $user, string $activityType, Board $board = null): void
    {
        $patterns = [
            "activity_count:{$user->id}:{$activityType}:" . 
            ($board ? "board:{$board->id}" : 'global') . ':*',
            "suspicious_check:{$user->id}:{$activityType}:*",
        ];
        
        foreach ($patterns as $pattern) {
            // Redis를 사용하는 경우 패턴 기반 삭제 가능
            // 여기서는 간단히 특정 키들만 삭제
            Cache::forget(str_replace('*', today()->toDateString(), $pattern));
        }
    }
}