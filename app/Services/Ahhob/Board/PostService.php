<?php

namespace App\Services\Ahhob\Board;

use App\Models\User;
use App\Models\Ahhob\Board\Board;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class PostService
{
    /**
     * 동적 게시글 모델 클래스 반환
     */
    public function getPostModel(Board $board): string
    {
        $modelName = 'Board' . Str::studly($board->slug);
        return "App\\Models\\Ahhob\\Board\\Dynamic\\{$modelName}";
    }

    /**
     * 동적 댓글 모델 클래스 반환
     */
    public function getCommentModel(Board $board): string
    {
        $modelName = 'Board' . Str::studly($board->slug) . 'Comment';
        return "App\\Models\\Ahhob\\Board\\Dynamic\\{$modelName}";
    }

    /**
     * 게시글 목록 조회 (페이지네이션)
     */
    public function getPaginatedPosts(Board $board, array $filters = []): LengthAwarePaginator
    {
        $modelClass = $this->getPostModel($board);
        
        if (!class_exists($modelClass)) {
            throw new \Exception("게시판 모델이 존재하지 않습니다: {$modelClass}");
        }
        
        $query = $modelClass::query()
            ->with(['user', 'attachments'])
            ->where('status', 'published')
            ->orderBy('is_notice', 'desc')
            ->orderBy('created_at', 'desc');
        
        // 검색 필터 적용
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }
        
        // 카테고리 필터 (있는 경우)
        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }
        
        // 공지사항 제외 필터
        if (!empty($filters['exclude_notice'])) {
            $query->where('is_notice', false);
        }
        
        $perPage = $filters['per_page'] ?? $board->posts_per_page ?? 20;
        
        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * 특정 게시글 조회
     */
    public function getPost(Board $board, int $postId): Model
    {
        $modelClass = $this->getPostModel($board);
        
        if (!class_exists($modelClass)) {
            throw new \Exception("게시판 모델이 존재하지 않습니다: {$modelClass}");
        }
        
        return $modelClass::with(['user', 'attachments', 'comments.user'])
            ->findOrFail($postId);
    }

    /**
     * 게시글 생성
     */
    public function createPost(Board $board, User $user, array $data): Model
    {
        $modelClass = $this->getPostModel($board);
        
        if (!class_exists($modelClass)) {
            throw new \Exception("게시판 모델이 존재하지 않습니다: {$modelClass}");
        }
        
        return DB::transaction(function () use ($modelClass, $board, $user, $data) {
            // 게시글 생성
            $post = $modelClass::create([
                'board_id' => $board->id,
                'user_id' => $user->id,
                'title' => $data['title'],
                'content' => $data['content'],
                'is_notice' => $data['is_notice'] ?? false,
                'status' => 'published',
                'views' => 0,
                'likes_count' => 0,
                'comments_count' => 0,
            ]);
            
            // 첨부파일 처리 (있는 경우)
            if (!empty($data['attachments'])) {
                $this->handleAttachments($post, $data['attachments']);
            }
            
            // 게시판 통계 업데이트
            $this->updateBoardStats($board);
            
            return $post;
        });
    }

    /**
     * 게시글 수정
     */
    public function updatePost(Model $post, array $data): Model
    {
        return DB::transaction(function () use ($post, $data) {
            $post->update([
                'title' => $data['title'],
                'content' => $data['content'],
                'is_notice' => $data['is_notice'] ?? $post->is_notice,
            ]);
            
            return $post->fresh();
        });
    }

    /**
     * 게시글 삭제
     */
    public function deletePost(Model $post): bool
    {
        return DB::transaction(function () use ($post) {
            // 권한에 따른 삭제 방식 결정
            $user = auth()->user();
            
            if ($user->hasRole('admin') || $user->can('forceDelete', $post)) {
                // 관리자는 완전 삭제 (hard delete)
                $deleted = $post->forceDelete();
            } else {
                // 일반 사용자는 소프트 삭제 (soft delete)
                $deleted = $post->delete();
            }
            
            if ($deleted) {
                // 게시판 통계 업데이트
                $board = Board::find($post->board_id);
                if ($board) {
                    $this->updateBoardStats($board);
                }
            }
            
            return $deleted;
        });
    }

    /**
     * 조회수 증가
     */
    public function incrementViews(Model $post): void
    {
        // 조회수 중복 증가 방지를 위한 세션 체크
        $sessionKey = "post_viewed_{$post->id}";
        
        if (!session()->has($sessionKey)) {
            $post->increment('views');
            session()->put($sessionKey, true);
        }
    }

    /**
     * 첨부파일 처리
     */
    private function handleAttachments(Model $post, array $attachments): void
    {
        foreach ($attachments as $attachment) {
            // 파일 업로드 처리 로직
            // intervention/image 사용한 이미지 리사이징 등
            // 실제 구현 시 AttachmentService 분리 고려
        }
    }

    /**
     * 게시판 통계 업데이트
     */
    private function updateBoardStats(Board $board): void
    {
        $modelClass = $this->getPostModel($board);
        
        if (class_exists($modelClass)) {
            $totalPosts = $modelClass::count();
            $board->update(['total_posts' => $totalPosts]);
        }
    }

    /**
     * 게시글 좋아요 토글
     */
    public function toggleLike(Model $post, User $user): array
    {
        $likeModel = \App\Models\Ahhob\Board\PostLike::class;
        
        $existingLike = $likeModel::where([
            'likeable_type' => get_class($post),
            'likeable_id' => $post->id,
            'user_id' => $user->id,
        ])->first();
        
        if ($existingLike) {
            $existingLike->delete();
            $post->decrement('likes_count');
            $liked = false;
        } else {
            $likeModel::create([
                'likeable_type' => get_class($post),
                'likeable_id' => $post->id,
                'user_id' => $user->id,
            ]);
            $post->increment('likes_count');
            $liked = true;
        }
        
        return [
            'liked' => $liked,
            'likes_count' => $post->fresh()->likes_count,
        ];
    }

    /**
     * 게시글 스크랩 토글
     */
    public function toggleScrap(Model $post, User $user): array
    {
        $scrapModel = \App\Models\Ahhob\Board\Scrap::class;
        
        $existingScrap = $scrapModel::where([
            'scrapable_type' => get_class($post),
            'scrapable_id' => $post->id,
            'user_id' => $user->id,
        ])->first();
        
        if ($existingScrap) {
            $existingScrap->delete();
            $scraped = false;
        } else {
            $scrapModel::create([
                'scrapable_type' => get_class($post),
                'scrapable_id' => $post->id,
                'user_id' => $user->id,
            ]);
            $scraped = true;
        }
        
        return [
            'scraped' => $scraped,
        ];
    }
}