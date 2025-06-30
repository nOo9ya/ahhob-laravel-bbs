<?php

namespace App\Services\Ahhob\Shared;

use App\Models\Ahhob\Shared\PointHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * 포인트 시스템 관리 서비스
 * 
 * 이 서비스는 사용자 포인트 관리 기능을 담당합니다.
 * 기존 point_histories 테이블 구조에 맞춰 구현되었습니다.
 */
class PointService
{
    /**
     * 기본 포인트 설정
     */
    private array $defaultConfig = [
        'post_create' => 10,
        'comment_create' => 5,
        'like_received' => 2,
        'daily_attendance' => 10,
        'daily_limit' => 500,
        'expiry_days' => 365,
        'transfer_fee_rate' => 0.1,
        'min_transfer_amount' => 10,
    ];

    /**
     * 포인트 지급 (기본)
     */
    public function awardPoints(
        User $user, 
        int $amount, 
        string $type, 
        ?string $description = null,
        ?Model $pointable = null
    ): PointHistory {
        if ($amount <= 0) {
            throw new InvalidArgumentException('지급할 포인트는 0보다 커야 합니다.');
        }

        $balanceBefore = $user->points;
        $user->increment('points', $amount);
        $balanceAfter = $user->fresh()->points;

        return PointHistory::create([
            'user_id' => $user->id,
            'pointable_type' => $pointable ? get_class($pointable) : null,
            'pointable_id' => $pointable?->id,
            'points' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'type' => $type,
            'description' => $description ?: $this->getTypeDescription($type),
            'expires_at' => now()->addDays($this->defaultConfig['expiry_days'])->toDateString(),
            'user_ip' => request()->ip(),
        ]);
    }

    /**
     * 포인트 차감
     */
    public function deductPoints(
        User $user,
        int $amount,
        string $type,
        ?string $description = null,
        ?Model $pointable = null
    ): PointHistory {
        if ($amount <= 0) {
            throw new InvalidArgumentException('차감할 포인트는 0보다 커야 합니다.');
        }

        if ($user->points < $amount) {
            throw new InvalidArgumentException('포인트가 부족합니다.');
        }

        $balanceBefore = $user->points;
        $user->decrement('points', $amount);
        $balanceAfter = $user->fresh()->points;

        return PointHistory::create([
            'user_id' => $user->id,
            'pointable_type' => $pointable ? get_class($pointable) : null,
            'pointable_id' => $pointable?->id,
            'points' => -$amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'type' => $type,
            'description' => $description ?: $this->getTypeDescription($type),
            'user_ip' => request()->ip(),
        ]);
    }

    /**
     * 게시글 작성 포인트 지급
     */
    public function awardPostPoints(User $user, Model $post): PointHistory
    {
        return $this->awardPoints(
            $user,
            $this->defaultConfig['post_create'],
            'post_write',
            '게시글 작성',
            $post
        );
    }

    /**
     * 댓글 작성 포인트 지급
     */
    public function awardCommentPoints(User $user, Model $post): PointHistory
    {
        return $this->awardPoints(
            $user,
            $this->defaultConfig['comment_create'],
            'comment_write',
            '댓글 작성',
            $post
        );
    }

    /**
     * 좋아요 받기 포인트 지급
     */
    public function awardLikePoints(User $user, Model $post, User $giver): PointHistory
    {
        return $this->awardPoints(
            $user,
            $this->defaultConfig['like_received'],
            'post_like',
            '좋아요 받음',
            $post
        );
    }

    /**
     * 출석 체크 포인트 지급
     */
    public function awardAttendancePoints(User $user): ?PointHistory
    {
        // 오늘 이미 출석 체크했는지 확인
        $today = now()->startOfDay();
        $existingAttendance = PointHistory::where('user_id', $user->id)
            ->where('type', 'daily_login')
            ->where('created_at', '>=', $today)
            ->exists();

        if ($existingAttendance) {
            return null;
        }

        return $this->awardPoints(
            $user,
            $this->defaultConfig['daily_attendance'],
            'daily_login',
            '출석 체크'
        );
    }

    /**
     * 포인트 전송
     */
    public function transferPoints(User $sender, User $receiver, int $amount, string $message = ''): bool
    {
        if ($amount < $this->defaultConfig['min_transfer_amount']) {
            throw new InvalidArgumentException('최소 전송 포인트는 ' . $this->defaultConfig['min_transfer_amount'] . '포인트입니다.');
        }

        $fee = (int)($amount * $this->defaultConfig['transfer_fee_rate']);
        $totalDeduction = $amount + $fee;

        if ($sender->points < $totalDeduction) {
            throw new InvalidArgumentException('포인트가 부족합니다.');
        }

        // 트랜잭션 처리
        \DB::transaction(function () use ($sender, $receiver, $amount, $fee, $message) {
            // 보내는 사용자에서 차감
            $this->deductPoints($sender, $amount, 'other', "포인트 전송: {$message}");
            
            if ($fee > 0) {
                $this->deductPoints($sender, $fee, 'other', '포인트 전송 수수료');
            }

            // 받는 사용자에게 지급
            $this->awardPoints($receiver, $amount, 'other', "포인트 받음: {$message}");
        });

        return true;
    }

    /**
     * 포인트 히스토리 조회
     */
    public function getPointHistory(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return PointHistory::where('user_id', $user->id)
            ->with(['pointable'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * 포인트 통계 조회
     */
    public function getPointStats(User $user): array
    {
        $histories = PointHistory::where('user_id', $user->id)->get();

        $totalEarned = $histories->where('points', '>', 0)->sum('points');
        $totalSpent = abs($histories->where('points', '<', 0)->sum('points'));

        return [
            'total_earned' => $totalEarned,
            'total_spent' => $totalSpent,
            'current_balance' => $user->points,
            'transaction_count' => $histories->count(),
            'lifetime_earned' => $totalEarned,
            'net_points' => $totalEarned - $totalSpent,
        ];
    }

    /**
     * 포인트 랭킹 조회
     */
    public function getPointRankings(int $limit = 10): Collection
    {
        return User::orderByDesc('points')
            ->limit($limit)
            ->get(['id', 'nickname', 'points', 'created_at']);
    }

    /**
     * 포인트 획득 가능 여부 확인
     */
    public function canEarnPoints(User $user, int $amount, array $config = []): bool
    {
        $config = array_merge($this->defaultConfig, $config);
        $dailyLimit = $config['daily_limit'];

        // 오늘 획득한 포인트 계산
        $today = now()->startOfDay();
        $todayEarned = PointHistory::where('user_id', $user->id)
            ->where('points', '>', 0)
            ->where('created_at', '>=', $today)
            ->sum('points');

        return ($todayEarned + $amount) <= $dailyLimit;
    }

    /**
     * 만료된 포인트 처리
     */
    public function expirePoints(): int
    {
        $expiredHistories = PointHistory::where('points', '>', 0)
            ->where('expires_at', '<', now()->toDateString())
            ->where('is_expired', false)
            ->get();

        $expiredCount = 0;

        foreach ($expiredHistories as $history) {
            $user = $history->user;
            if ($user && $user->points >= $history->points) {
                $user->decrement('points', $history->points);
                
                // 만료 처리 표시
                $history->update(['is_expired' => true]);
                
                // 만료 히스토리 생성
                $this->deductPoints(
                    $user,
                    $history->points,
                    'other',
                    '포인트 만료'
                );

                $expiredCount++;
            }
        }

        return $expiredCount;
    }

    /**
     * 관리자 포인트 조정
     */
    public function adjustPointsAsAdmin(User $user, int $amount, string $reason, User $admin): PointHistory
    {
        if ($amount == 0) {
            throw new InvalidArgumentException('조정할 포인트는 0이 될 수 없습니다.');
        }

        $balanceBefore = $user->points;
        
        if ($amount > 0) {
            $user->increment('points', $amount);
        } else {
            $user->decrement('points', abs($amount));
        }
        
        $balanceAfter = $user->fresh()->points;

        return PointHistory::create([
            'user_id' => $user->id,
            'points' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'type' => 'admin_adjust',
            'description' => $reason,
            'admin_id' => $admin->id,
            'user_ip' => request()->ip(),
        ]);
    }

    /**
     * 포인트 타입별 설명 반환
     */
    private function getTypeDescription(string $type): string
    {
        return match($type) {
            'post_write' => '게시글 작성',
            'comment_write' => '댓글 작성',
            'post_like' => '게시글 좋아요 받음',
            'comment_like' => '댓글 좋아요 받음',
            'daily_login' => '일일 로그인',
            'welcome_bonus' => '가입 축하',
            'admin_adjust' => '관리자 조정',
            'purchase' => '상품 구매',
            'refund' => '환불',
            'event' => '이벤트',
            'penalty' => '제재',
            'other' => '기타',
            default => $type,
        };
    }
}