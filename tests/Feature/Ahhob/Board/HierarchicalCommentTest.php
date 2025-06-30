<?php

use App\Models\Ahhob\Board\Board;
use App\Models\Ahhob\Board\Dynamic\BoardNotice;
use App\Models\Ahhob\Board\Dynamic\BoardNoticeComment;
use App\Models\User;
use App\Services\Ahhob\Board\CommentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // 테스트용 사용자 생성
    $this->user = User::factory()->create();
    $this->anotherUser = User::factory()->create();
    
    // 테스트용 게시판 및 게시글 생성
    $this->board = Board::factory()->create(['slug' => 'notice']);
    $this->post = BoardNotice::factory()->create(['user_id' => $this->user->id]);
    
    // CommentService 인스턴스 생성
    $this->commentService = new CommentService();
});

/**
 * 테스트 목적: 계층형 댓글 시스템의 최상위 댓글 생성 기능 검증
 * 테스트 시나리오: 사용자가 게시글에 최상위 댓글을 작성한 경우
 * 기대 결과: parent_id = null, depth = 0, path = "{id}" 형태로 댓글이 생성됨
 * 관련 비즈니스 규칙: 최상위 댓글은 부모가 없으며 깊이 0을 가져야 함
 */
test('최상위_댓글_생성', function () {
    // Given: 댓글 데이터 준비
    // 최상위 댓글은 parent_id가 없어야 함
    $commentData = [
        'content' => '첫 번째 댓글입니다.',
        'is_secret' => false,
        'is_html' => false,
    ];
    
    // When: CommentService를 통해 최상위 댓글 생성
    // 사용자가 게시글에 새로운 댓글을 작성
    $comment = $this->commentService->createComment(
        $this->post,
        $this->user,
        $commentData
    );
    
    // Then: 최상위 댓글의 계층 구조가 올바르게 설정되는지 확인
    // parent_id는 null, depth는 0, path는 자신의 ID
    expect($comment)->toBeInstanceOf(BoardNoticeComment::class);
    expect($comment->parent_id)->toBeNull();
    expect($comment->depth)->toBe(0);
    expect($comment->path)->toBe((string)$comment->id);
    expect($comment->content)->toBe('첫 번째 댓글입니다.');
    expect($comment->user_id)->toBe($this->user->id);
});

/**
 * 테스트 목적: 계층형 댓글 시스템의 대댓글(자식 댓글) 생성 기능 검증
 * 테스트 시나리오: 사용자가 기존 댓글에 대댓글을 작성한 경우
 * 기대 결과: parent_id = 부모_댓글_ID, depth = 부모_depth + 1, path = "부모_path/{id}" 형태로 생성됨
 * 관련 비즈니스 규칙: 대댓글은 부모 댓글의 depth + 1을 가지고 path는 부모 경로를 상속해야 함
 */
test('대댓글_생성', function () {
    // Given: 부모 댓글 먼저 생성
    // 대댓글 작성을 위해서는 부모 댓글이 먼저 존재해야 함
    $parentComment = $this->commentService->createComment(
        $this->post,
        $this->user,
        ['content' => '부모 댓글', 'is_secret' => false, 'is_html' => false]
    );
    
    // Given: 대댓글 데이터 준비
    $replyData = [
        'content' => '대댓글입니다.',
        'is_secret' => false,
        'is_html' => false,
    ];
    
    // When: CommentService를 통해 대댓글 생성
    // 다른 사용자가 기존 댓글에 대댓글을 작성
    $reply = $this->commentService->createReply(
        $parentComment,
        $this->anotherUser,
        $replyData
    );
    
    // Then: 대댓글의 계층 구조가 올바르게 설정되는지 확인
    // 부모 댓글과의 관계가 정확하게 설정되어야 함
    expect($reply)->toBeInstanceOf(BoardNoticeComment::class);
    expect($reply->parent_id)->toBe($parentComment->id);
    expect($reply->depth)->toBe(1);
    expect($reply->path)->toBe($parentComment->id . '/' . $reply->id);
    expect($reply->content)->toBe('대댓글입니다.');
    expect($reply->user_id)->toBe($this->anotherUser->id);
});

/**
 * 테스트 목적: 계층형 댓글 시스템의 다단계 중첩 댓글 생성 및 깊이 제한 검증
 * 테스트 시나리오: 3단계 이상의 중첩 댓글을 작성하려고 시도한 경우
 * 기대 결과: 깊이 제한(최대 2단계)에 걸려 예외가 발생하거나 제한된 깊이로 생성됨
 * 관련 비즈니스 규칙: 무한 중첩을 방지하기 위해 댓글 깊이를 2단계로 제한
 */
test('댓글_깊이_제한_확인', function () {
    // Given: 1단계 댓글 생성 (최상위)
    // 다단계 중첩 구조를 만들기 위한 기본 댓글
    $level1Comment = $this->commentService->createComment(
        $this->post,
        $this->user,
        ['content' => '1단계 댓글', 'is_secret' => false, 'is_html' => false]
    );
    
    // Given: 2단계 댓글 생성 (대댓글)
    // 1단계 댓글에 대한 대댓글
    $level2Comment = $this->commentService->createReply(
        $level1Comment,
        $this->anotherUser,
        ['content' => '2단계 댓글', 'is_secret' => false, 'is_html' => false]
    );
    
    // When & Then: 3단계 댓글 생성 시도 시 예외 발생
    // 깊이 제한을 초과하면 예외가 발생해야 함
    expect(function () use ($level2Comment) {
        $this->commentService->createReply(
            $level2Comment,
            $this->user,
            ['content' => '3단계 댓글 (제한됨)', 'is_secret' => false, 'is_html' => false]
        );
    })->toThrow(\InvalidArgumentException::class, '댓글 깊이는 최대 2단계까지만 허용됩니다.');
});

/**
 * 테스트 목적: 계층형 댓글 시스템의 트리 구조 조회 기능 검증
 * 테스트 시나리오: 게시글에 여러 개의 댓글과 대댓글이 있을 때 올바른 순서로 조회되는지 확인
 * 기대 결과: path 기준으로 정렬되어 계층형 구조가 유지된 채로 조회됨
 * 관련 비즈니스 규칙: 댓글은 path 컬럼을 기준으로 정렬하여 트리 구조를 표현
 */
test('계층형_댓글_트리_구조_조회', function () {
    // Given: 복잡한 댓글 구조 생성
    // 다양한 계층의 댓글들을 생성하여 트리 구조 테스트
    $comment1 = $this->commentService->createComment(
        $this->post,
        $this->user,
        ['content' => '첫 번째 댓글', 'is_secret' => false, 'is_html' => false]
    );
    
    $comment2 = $this->commentService->createComment(
        $this->post,
        $this->anotherUser,
        ['content' => '두 번째 댓글', 'is_secret' => false, 'is_html' => false]
    );
    
    $reply1_1 = $this->commentService->createReply(
        $comment1,
        $this->anotherUser,
        ['content' => '첫 번째 댓글의 대댓글', 'is_secret' => false, 'is_html' => false]
    );
    
    $reply2_1 = $this->commentService->createReply(
        $comment2,
        $this->user,
        ['content' => '두 번째 댓글의 대댓글', 'is_secret' => false, 'is_html' => false]
    );
    
    // When: 게시글의 계층형 댓글 목록 조회
    // path 기준으로 정렬된 트리 구조로 조회되어야 함
    $comments = $this->commentService->getHierarchicalComments($this->post);
    
    // Then: 댓글들이 올바른 계층 구조로 정렬되어 조회되는지 확인
    // path 순서대로 정렬되어 트리 구조를 표현해야 함
    expect($comments)->toHaveCount(4);
    
    // 첫 번째 댓글과 그 대댓글이 연속으로 나와야 함
    expect($comments[0]->id)->toBe($comment1->id);
    expect($comments[1]->id)->toBe($reply1_1->id);
    expect($comments[2]->id)->toBe($comment2->id);
    expect($comments[3]->id)->toBe($reply2_1->id);
});

/**
 * 테스트 목적: 계층형 댓글 시스템의 댓글 삭제 및 대댓글 관리 기능 검증
 * 테스트 시나리오: 대댓글이 있는 부모 댓글을 삭제할 때의 처리 방식 확인
 * 기대 결과: 부모 댓글은 내용만 변경되고 대댓글은 유지됨 (소프트 삭제)
 * 관련 비즈니스 규칙: 대댓글이 있는 댓글 삭제 시 "삭제된 댓글입니다" 표시하고 구조 유지
 */
test('대댓글이_있는_부모_댓글_삭제', function () {
    // Given: 부모 댓글과 대댓글 생성
    // 댓글 삭제 시나리오를 위한 부모-자식 관계 설정
    $parentComment = $this->commentService->createComment(
        $this->post,
        $this->user,
        ['content' => '삭제될 부모 댓글', 'is_secret' => false, 'is_html' => false]
    );
    
    $childComment = $this->commentService->createReply(
        $parentComment,
        $this->anotherUser,
        ['content' => '유지될 대댓글', 'is_secret' => false, 'is_html' => false]
    );
    
    // When: 부모 댓글 삭제 실행
    // 대댓글이 있는 부모 댓글을 삭제하면 내용만 변경되어야 함
    $this->commentService->deleteComment($parentComment, $this->user);
    
    // Then: 부모 댓글이 올바르게 처리되고 대댓글은 유지되는지 확인
    // 댓글 구조는 유지하되 내용만 "삭제된 댓글입니다"로 변경
    $parentComment->refresh();
    $childComment->refresh();
    
    expect($parentComment->content)->toBe('삭제된 댓글입니다.');
    expect($parentComment->deleted_at)->toBeNull(); // 하드 삭제되지 않음
    expect($childComment->content)->toBe('유지될 대댓글'); // 대댓글은 그대로 유지
    expect($childComment->deleted_at)->toBeNull();
});

/**
 * 테스트 목적: 계층형 댓글 시스템의 대댓글 수 자동 업데이트 기능 검증
 * 테스트 시나리오: 대댓글 생성/삭제 시 부모 댓글의 reply_count가 자동으로 업데이트되는지 확인
 * 기대 결과: 대댓글 추가 시 reply_count 증가, 삭제 시 감소
 * 관련 비즈니스 규칙: 부모 댓글의 reply_count는 실제 대댓글 수와 일치해야 함
 */
test('부모_댓글의_대댓글_수_자동_업데이트', function () {
    // Given: 부모 댓글 생성
    // 대댓글 수 카운팅을 위한 기본 댓글
    $parentComment = $this->commentService->createComment(
        $this->post,
        $this->user,
        ['content' => '부모 댓글', 'is_secret' => false, 'is_html' => false]
    );
    
    // When: 첫 번째 대댓글 생성
    // 대댓글 생성 시 부모의 reply_count가 증가해야 함
    $reply1 = $this->commentService->createReply(
        $parentComment,
        $this->anotherUser,
        ['content' => '첫 번째 대댓글', 'is_secret' => false, 'is_html' => false]
    );
    
    // Then: 부모 댓글의 reply_count가 1 증가
    $parentComment->refresh();
    expect($parentComment->reply_count)->toBe(1);
    
    // When: 두 번째 대댓글 생성
    $reply2 = $this->commentService->createReply(
        $parentComment,
        $this->user,
        ['content' => '두 번째 대댓글', 'is_secret' => false, 'is_html' => false]
    );
    
    // Then: 부모 댓글의 reply_count가 2로 증가
    $parentComment->refresh();
    expect($parentComment->reply_count)->toBe(2);
    
    // When: 하나의 대댓글 삭제
    $this->commentService->deleteComment($reply1, $this->anotherUser);
    
    // Then: 부모 댓글의 reply_count가 1로 감소
    $parentComment->refresh();
    expect($parentComment->reply_count)->toBe(1);
});

/**
 * 테스트 목적: 계층형 댓글 시스템의 권한 검증 기능 확인
 * 테스트 시나리오: 다른 사용자의 댓글을 삭제하려고 시도한 경우
 * 기대 결과: 권한 없음 예외가 발생함
 * 관련 비즈니스 규칙: 자신이 작성한 댓글만 삭제할 수 있음 (관리자 제외)
 */
test('댓글_삭제_권한_검증', function () {
    // Given: 사용자가 작성한 댓글
    // 권한 검증을 위한 다른 사용자의 댓글
    $comment = $this->commentService->createComment(
        $this->post,
        $this->user,
        ['content' => '다른 사용자 댓글', 'is_secret' => false, 'is_html' => false]
    );
    
    // When & Then: 다른 사용자가 댓글 삭제 시도 시 예외 발생
    // 자신이 작성하지 않은 댓글은 삭제할 수 없어야 함
    expect(function () use ($comment) {
        $this->commentService->deleteComment($comment, $this->anotherUser);
    })->toThrow(\Illuminate\Auth\Access\AuthorizationException::class);
});