<?php

namespace App\Services\Ahhob\Board;

use App\Models\Ahhob\Board\BaseBoardPost;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * 계층형 댓글 시스템 서비스
 * 
 * 이 서비스는 동적 게시판 시스템에서 계층형 댓글의 생성, 조회, 수정, 삭제를 담당합니다.
 * 
 * 주요 기능:
 * - 최대 2단계 깊이 제한으로 무한 중첩 방지
 * - path 기반 트리 구조 관리 (예: "1/3/5")
 * - 부모 댓글의 reply_count 자동 업데이트
 * - 권한 기반 접근 제어 (작성자만 삭제 가능)
 * - 대댓글이 있는 댓글 삭제 시 구조 유지
 * 
 * 성능 최적화:
 * - 트랜잭션 처리로 데이터 일관성 보장
 * - 배치 업데이트로 N+1 쿼리 방지
 * - path 기반 정렬로 효율적인 계층 조회
 */
class CommentService
{
    /**
     * 댓글 최대 깊이 제한 (0부터 시작, 최대 1까지 허용 = 2단계)
     */
    private const MAX_DEPTH = 1;
    
    /**
     * 삭제된 댓글 표시 메시지
     */
    private const DELETED_COMMENT_MESSAGE = '삭제된 댓글입니다.';

    /**
     * 최상위 댓글 생성
     * 
     * @param BaseBoardPost $post 게시글
     * @param User $user 작성자
     * @param array $data 댓글 데이터 (content, is_secret, is_html 등)
     * @return Model 생성된 댓글
     * 
     * @throws \Exception 댓글 생성 실패 시
     */
    public function createComment(BaseBoardPost $post, User $user, array $data): Model
    {
        return DB::transaction(function () use ($post, $user, $data) {
            // 댓글 모델 클래스 동적 결정
            $commentClass = $this->getCommentClass($post);
            
            // 댓글 데이터 준비 및 검증
            $commentData = $this->prepareCommentData($data, [
                'post_id' => $post->id,
                'user_id' => $user->id,
                'parent_id' => null,
                'depth' => 0,
            ]);
            
            // 댓글 생성
            $comment = $commentClass::create($commentData);
            
            // path 설정 (최상위 댓글은 자신의 ID)
            $comment->update(['path' => (string)$comment->id]);
            
            // 게시글의 댓글 수 업데이트
            $post->updateCommentCount();
            
            return $comment;
        });
    }

    /**
     * 대댓글 생성
     * 
     * @param Model $parentComment 부모 댓글
     * @param User $user 작성자
     * @param array $data 댓글 데이터 (content, is_secret, is_html 등)
     * @return Model 생성된 대댓글
     * 
     * @throws InvalidArgumentException 깊이 제한 초과 시
     * @throws \Exception 댓글 생성 실패 시
     */
    public function createReply(Model $parentComment, User $user, array $data): Model
    {
        // 깊이 제한 확인
        $this->validateCommentDepth($parentComment);
        
        return DB::transaction(function () use ($parentComment, $user, $data) {
            // 댓글 클래스 확인
            $commentClass = get_class($parentComment);
            
            // 대댓글 데이터 준비 및 검증
            $replyData = $this->prepareCommentData($data, [
                'post_id' => $parentComment->post_id,
                'user_id' => $user->id,
                'parent_id' => $parentComment->id,
                'depth' => $parentComment->depth + 1,
            ]);
            
            // 대댓글 생성
            $reply = $commentClass::create($replyData);
            
            // path 설정 (부모 path + 자신의 ID)
            $path = $parentComment->path . '/' . $reply->id;
            $reply->update(['path' => $path]);
            
            // 부모 댓글의 reply_count 증가
            $parentComment->increment('reply_count');
            
            // 게시글의 댓글 수 업데이트
            $post = $parentComment->post;
            $post->updateCommentCount();
            
            return $reply;
        });
    }

    /**
     * 계층형 댓글 목록 조회
     * 
     * @param BaseBoardPost $post 게시글
     * @return Collection path 순서로 정렬된 댓글 목록
     */
    public function getHierarchicalComments(BaseBoardPost $post): Collection
    {
        return $post->comments()
            ->with(['user'])
            ->whereNull('deleted_at')
            ->orderByRaw('CAST(SUBSTRING_INDEX(path, "/", 1) AS UNSIGNED), path')
            ->get();
    }

    /**
     * 댓글 삭제
     * 
     * @param Model $comment 삭제할 댓글
     * @param User $user 삭제 요청자
     * 
     * @throws AuthorizationException 권한 없음
     * @throws \Exception 삭제 실패 시
     */
    public function deleteComment(Model $comment, User $user): void
    {
        // 권한 확인
        $this->validateCommentOwnership($comment, $user);
        
        DB::transaction(function () use ($comment) {
            // 대댓글이 있는 경우 내용만 변경 (구조 유지)
            if ($comment->reply_count > 0) {
                $this->markCommentAsDeleted($comment);
            } else {
                // 대댓글이 없는 경우 완전 삭제
                $this->performCommentDeletion($comment);
            }
            
            // 게시글의 댓글 수 업데이트
            $comment->post->updateCommentCount();
        });
    }

    /**
     * 게시글의 댓글 모델 클래스 반환
     * 
     * @param BaseBoardPost $post 게시글
     * @return string 댓글 모델 클래스명
     */
    private function getCommentClass(BaseBoardPost $post): string
    {
        // 동적 모델 클래스명 생성
        $postClass = get_class($post);
        $postClassName = class_basename($postClass);
        
        // Board{Slug}Comment 형태로 댓글 클래스명 생성
        $commentClassName = $postClassName . 'Comment';
        $commentClass = str_replace($postClassName, $commentClassName, $postClass);
        
        return $commentClass;
    }

    /**
     * 댓글 데이터 준비 및 검증
     * 
     * @param array $inputData 사용자 입력 데이터
     * @param array $systemData 시스템 자동 설정 데이터
     * @return array 검증된 댓글 데이터
     */
    private function prepareCommentData(array $inputData, array $systemData): array
    {
        // 기본 시스템 데이터 설정
        $defaultSystemData = [
            'author_ip' => request()->ip(),
            'status' => 'published',
        ];
        
        return array_merge($inputData, $systemData, $defaultSystemData);
    }

    /**
     * 댓글 깊이 제한 검증
     * 
     * @param Model $parentComment 부모 댓글
     * @throws InvalidArgumentException 깊이 제한 초과 시
     */
    private function validateCommentDepth(Model $parentComment): void
    {
        if ($parentComment->depth >= self::MAX_DEPTH) {
            $maxLevel = self::MAX_DEPTH + 1;
            throw new InvalidArgumentException(
                "댓글 깊이는 최대 {$maxLevel}단계까지만 허용됩니다."
            );
        }
    }

    /**
     * 댓글 소유권 검증
     * 
     * @param Model $comment 댓글
     * @param User $user 사용자
     * @throws AuthorizationException 권한 없음
     */
    private function validateCommentOwnership(Model $comment, User $user): void
    {
        if ($comment->user_id !== $user->id) {
            throw new AuthorizationException('댓글을 삭제할 권한이 없습니다.');
        }
    }

    /**
     * 댓글을 삭제됨으로 표시 (대댓글이 있어 구조 유지 필요)
     * 
     * @param Model $comment 댓글
     */
    private function markCommentAsDeleted(Model $comment): void
    {
        $comment->update([
            'content' => self::DELETED_COMMENT_MESSAGE,
            'is_secret' => false,
            'is_html' => false,
        ]);
    }

    /**
     * 댓글 완전 삭제 처리 (대댓글이 없는 경우)
     * 
     * @param Model $comment 댓글
     */
    private function performCommentDeletion(Model $comment): void
    {
        // 소프트 삭제 실행
        $comment->delete();
        
        // 부모 댓글의 reply_count 감소
        if ($comment->parent_id && $comment->parent) {
            $comment->parent->decrement('reply_count');
        }
    }
}