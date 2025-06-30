<?php

namespace App\Services\Ahhob\Shared;

use App\Models\Ahhob\Shared\PostLike;
use App\Models\Ahhob\Shared\Scrap;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * 좋아요/싫어요 및 스크랩 관리 서비스
 * 
 * 이 서비스는 게시글, 댓글 등의 좋아요/싫어요 및 스크랩 기능을 담당합니다.
 * 
 * 주요 기능:
 * - 좋아요/싫어요 토글 기능
 * - 스크랩 추가/제거 기능
 * - 통계 집계 및 상태 확인
 * - 다형적 관계를 통한 다양한 모델 지원
 */
class LikeScrapService
{
    /**
     * 좋아요 토글
     * 
     * @param Model $likeable 좋아요 대상 모델
     * @param User $user 사용자
     * @return PostLike|null 좋아요 추가 시 PostLike 인스턴스, 제거 시 null
     */
    public function toggleLike(Model $likeable, User $user): ?PostLike
    {
        $existing = $this->findExistingLike($likeable, $user);
        
        if ($existing) {
            if ($existing->is_like) {
                // 이미 좋아요가 있으면 제거
                $existing->delete();
                return null;
            } else {
                // 싫어요가 있으면 좋아요로 변경
                $existing->update(['is_like' => true]);
                return $existing;
            }
        }
        
        // 새로운 좋아요 생성
        return PostLike::create([
            'likeable_type' => get_class($likeable),
            'likeable_id' => $likeable->id,
            'user_id' => $user->id,
            'is_like' => true,
            'ip_address' => request()->ip(),
        ]);
    }
    
    /**
     * 싫어요 토글
     * 
     * @param Model $likeable 싫어요 대상 모델
     * @param User $user 사용자
     * @return PostLike|null 싫어요 추가 시 PostLike 인스턴스, 제거 시 null
     */
    public function toggleDislike(Model $likeable, User $user): ?PostLike
    {
        $existing = $this->findExistingLike($likeable, $user);
        
        if ($existing) {
            if (!$existing->is_like) {
                // 이미 싫어요가 있으면 제거
                $existing->delete();
                return null;
            } else {
                // 좋아요가 있으면 싫어요로 변경
                $existing->update(['is_like' => false]);
                return $existing;
            }
        }
        
        // 새로운 싫어요 생성
        return PostLike::create([
            'likeable_type' => get_class($likeable),
            'likeable_id' => $likeable->id,
            'user_id' => $user->id,
            'is_like' => false,
            'ip_address' => request()->ip(),
        ]);
    }
    
    /**
     * 스크랩 토글
     * 
     * @param Model $scrapable 스크랩 대상 모델
     * @param User $user 사용자
     * @param string|null $memo 스크랩 메모
     * @param string $category 스크랩 카테고리
     * @return Scrap|null 스크랩 추가 시 Scrap 인스턴스, 제거 시 null
     */
    public function toggleScrap(Model $scrapable, User $user, ?string $memo = null, string $category = 'default'): ?Scrap
    {
        $existing = $this->findExistingScrap($scrapable, $user);
        
        if ($existing) {
            // 이미 스크랩이 있으면 제거
            $existing->delete();
            return null;
        }
        
        // 새로운 스크랩 생성
        return Scrap::create([
            'scrapable_type' => get_class($scrapable),
            'scrapable_id' => $scrapable->id,
            'user_id' => $user->id,
            'memo' => $memo,
            'category' => $category,
            'ip_address' => request()->ip(),
        ]);
    }
    
    /**
     * 좋아요/싫어요 통계 조회
     * 
     * @param Model $likeable 대상 모델
     * @return array 통계 데이터
     */
    public function getLikeStats(Model $likeable): array
    {
        $likes = PostLike::forModel(get_class($likeable), $likeable->id)
            ->likes()
            ->count();
            
        $dislikes = PostLike::forModel(get_class($likeable), $likeable->id)
            ->dislikes()
            ->count();
            
        $total = $likes + $dislikes;
        
        return [
            'likes' => $likes,
            'dislikes' => $dislikes,
            'total' => $total,
            'like_ratio' => $total > 0 ? $likes / $total : 0,
        ];
    }
    
    /**
     * 사용자의 좋아요/스크랩 상태 확인
     * 
     * @param Model $target 대상 모델
     * @param User $user 사용자
     * @return array 사용자 상태
     */
    public function getUserStatus(Model $target, User $user): array
    {
        $like = $this->findExistingLike($target, $user);
        $scrap = $this->findExistingScrap($target, $user);
        
        return [
            'has_liked' => $like && $like->is_like,
            'has_disliked' => $like && !$like->is_like,
            'has_scraped' => $scrap !== null,
            'scrap_memo' => $scrap?->memo,
        ];
    }
    
    /**
     * 사용자의 스크랩 목록 조회
     * 
     * @param User $user 사용자
     * @param string|null $category 카테고리 필터
     * @param int $perPage 페이지당 항목 수
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getUserScraps(User $user, ?string $category = null, int $perPage = 15)
    {
        $query = Scrap::byUser($user->id)
            ->with('scrapable')
            ->ordered();
            
        if ($category) {
            $query->byCategory($category);
        }
        
        return $query->paginate($perPage);
    }

    /**
     * 사용자의 좋아요한 게시글 목록 조회
     * 
     * @param User $user 사용자
     * @param int $perPage 페이지당 항목 수
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getUserLikedPosts(User $user, int $perPage = 15)
    {
        return PostLike::byUser($user->id)
            ->likes()
            ->with('likeable')
            ->latest()
            ->paginate($perPage);
    }

    /**
     * 인기 게시글 조회 (좋아요 기준)
     * 
     * @param string $modelClass 모델 클래스
     * @param int $days 기간 (일)
     * @param int $limit 제한 수
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPopularPosts(string $modelClass, int $days = 7, int $limit = 10)
    {
        $startDate = now()->subDays($days);
        
        return PostLike::where('likeable_type', $modelClass)
            ->likes()
            ->where('created_at', '>=', $startDate)
            ->selectRaw('likeable_id, COUNT(*) as like_count')
            ->groupBy('likeable_id')
            ->orderByDesc('like_count')
            ->limit($limit)
            ->with('likeable')
            ->get()
            ->pluck('likeable');
    }

    /**
     * 스크랩 카테고리 목록 조회
     * 
     * @param User $user 사용자
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getScrapCategories(User $user)
    {
        return Scrap::byUser($user->id)
            ->select('category')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('category')
            ->orderBy('category')
            ->get();
    }

    /**
     * 좋아요/싫어요 일괄 처리
     * 
     * @param array $items [['model' => $model, 'user' => $user, 'action' => 'like'|'dislike']]
     * @return array 처리 결과
     */
    public function batchProcessLikes(array $items): array
    {
        $results = [];
        
        foreach ($items as $item) {
            try {
                $result = match($item['action']) {
                    'like' => $this->toggleLike($item['model'], $item['user']),
                    'dislike' => $this->toggleDislike($item['model'], $item['user']),
                    default => null,
                };
                
                $results[] = [
                    'success' => true,
                    'model_id' => $item['model']->id,
                    'action' => $item['action'],
                    'result' => $result,
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'success' => false,
                    'model_id' => $item['model']->id,
                    'action' => $item['action'],
                    'error' => $e->getMessage(),
                ];
            }
        }
        
        return $results;
    }

    /**
     * 특정 기간 동안의 좋아요/싫어요 통계
     * 
     * @param Model $likeable 대상 모델
     * @param int $days 기간 (일)
     * @return array 통계 데이터
     */
    public function getLikeStatsByPeriod(Model $likeable, int $days = 30): array
    {
        $startDate = now()->subDays($days);
        
        $likes = PostLike::forModel(get_class($likeable), $likeable->id)
            ->likes()
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
            
        $dislikes = PostLike::forModel(get_class($likeable), $likeable->id)
            ->dislikes()
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        return [
            'period' => $days,
            'likes_by_date' => $likes->pluck('count', 'date')->toArray(),
            'dislikes_by_date' => $dislikes->pluck('count', 'date')->toArray(),
        ];
    }
    
    /**
     * 기존 좋아요/싫어요 찾기
     * 
     * @param Model $likeable 대상 모델
     * @param User $user 사용자
     * @return PostLike|null
     */
    private function findExistingLike(Model $likeable, User $user): ?PostLike
    {
        return PostLike::forModel(get_class($likeable), $likeable->id)
            ->byUser($user->id)
            ->first();
    }
    
    /**
     * 기존 스크랩 찾기
     * 
     * @param Model $scrapable 대상 모델
     * @param User $user 사용자
     * @return Scrap|null
     */
    private function findExistingScrap(Model $scrapable, User $user): ?Scrap
    {
        return Scrap::forModel(get_class($scrapable), $scrapable->id)
            ->byUser($user->id)
            ->first();
    }
}