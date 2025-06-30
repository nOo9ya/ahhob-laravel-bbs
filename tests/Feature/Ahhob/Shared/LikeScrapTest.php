<?php

use App\Models\Ahhob\Board\Dynamic\BoardNotice;
use App\Models\Ahhob\Shared\PostLike;
use App\Models\Ahhob\Shared\Scrap;
use App\Models\User;
use App\Services\Ahhob\Shared\LikeScrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // 테스트용 동적 게시판 테이블 생성
    \Artisan::call('board:create', ['slug' => 'notice']);
    
    // 테스트용 사용자 및 게시글 생성
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();
    $this->post = BoardNotice::factory()->create(['user_id' => $this->user->id]);
    
    // LikeScrapService 인스턴스 생성
    $this->likeScrapService = new LikeScrapService();
});

/**
 * 테스트 목적: 게시글에 좋아요를 추가하는 기능 검증
 * 테스트 시나리오: 사용자가 게시글에 좋아요를 누르는 경우
 * 기대 결과: 좋아요 레코드가 생성되고 게시글의 좋아요 수가 증가함
 * 관련 비즈니스 규칙: 사용자는 게시글에 좋아요를 표시할 수 있음
 */
test('게시글_좋아요_추가', function () {
    // Given: 게시글과 사용자 준비
    // 좋아요 기능을 테스트하기 위한 기본 데이터
    
    // When: 좋아요 추가
    $like = $this->likeScrapService->toggleLike($this->post, $this->user);
    
    // Then: 좋아요가 정상적으로 추가되는지 확인
    expect($like)->toBeInstanceOf(PostLike::class);
    expect($like->user_id)->toBe($this->user->id);
    expect($like->likeable_type)->toBe(BoardNotice::class);
    expect($like->likeable_id)->toBe($this->post->id);
    expect($like->is_like)->toBeTrue();
    
    // 데이터베이스에 좋아요 레코드가 생성되었는지 확인
    $this->assertDatabaseHas('post_likes', [
        'likeable_type' => BoardNotice::class,
        'likeable_id' => $this->post->id,
        'user_id' => $this->user->id,
        'is_like' => true,
    ]);
});

/**
 * 테스트 목적: 게시글에 싫어요를 추가하는 기능 검증
 * 테스트 시나리오: 사용자가 게시글에 싫어요를 누르는 경우
 * 기대 결과: 싫어요 레코드가 생성됨
 * 관련 비즈니스 규칙: 사용자는 게시글에 싫어요를 표시할 수 있음
 */
test('게시글_싫어요_추가', function () {
    // Given: 게시글과 사용자 준비
    
    // When: 싫어요 추가
    $dislike = $this->likeScrapService->toggleDislike($this->post, $this->user);
    
    // Then: 싫어요가 정상적으로 추가되는지 확인
    expect($dislike)->toBeInstanceOf(PostLike::class);
    expect($dislike->user_id)->toBe($this->user->id);
    expect($dislike->likeable_type)->toBe(BoardNotice::class);
    expect($dislike->likeable_id)->toBe($this->post->id);
    expect($dislike->is_like)->toBeFalse();
    
    // 데이터베이스에 싫어요 레코드가 생성되었는지 확인
    $this->assertDatabaseHas('post_likes', [
        'likeable_type' => BoardNotice::class,
        'likeable_id' => $this->post->id,
        'user_id' => $this->user->id,
        'is_like' => false,
    ]);
});

/**
 * 테스트 목적: 중복 좋아요 클릭 시 토글 기능 검증
 * 테스트 시나리오: 이미 좋아요를 누른 게시글에 다시 좋아요를 누르는 경우
 * 기대 결과: 좋아요가 취소됨 (토글 동작)
 * 관련 비즈니스 규칙: 같은 사용자의 중복 좋아요는 토글로 처리
 */
test('좋아요_토글_기능', function () {
    // Given: 이미 좋아요를 누른 상태
    $firstLike = $this->likeScrapService->toggleLike($this->post, $this->user);
    expect($firstLike)->toBeInstanceOf(PostLike::class);
    
    // When: 같은 게시글에 다시 좋아요 클릭
    $result = $this->likeScrapService->toggleLike($this->post, $this->user);
    
    // Then: 좋아요가 취소되어야 함 (null 반환)
    expect($result)->toBeNull();
    
    // 데이터베이스에서 좋아요 레코드가 삭제되었는지 확인
    $this->assertDatabaseMissing('post_likes', [
        'likeable_type' => BoardNotice::class,
        'likeable_id' => $this->post->id,
        'user_id' => $this->user->id,
        'is_like' => true,
    ]);
});

/**
 * 테스트 목적: 좋아요와 싫어요 간 상호 배타적 처리 검증
 * 테스트 시나리오: 좋아요를 누른 후 싫어요를 누르는 경우
 * 기대 결과: 기존 좋아요는 제거되고 싫어요가 추가됨
 * 관련 비즈니스 규칙: 한 사용자는 하나의 게시글에 좋아요 또는 싫어요 중 하나만 가능
 */
test('좋아요_싫어요_상호배타적_처리', function () {
    // Given: 먼저 좋아요를 누른 상태
    $like = $this->likeScrapService->toggleLike($this->post, $this->user);
    expect($like)->toBeInstanceOf(PostLike::class);
    expect($like->is_like)->toBeTrue();
    
    // When: 같은 게시글에 싫어요 클릭
    $dislike = $this->likeScrapService->toggleDislike($this->post, $this->user);
    
    // Then: 기존 좋아요는 제거되고 싫어요가 추가되어야 함
    expect($dislike)->toBeInstanceOf(PostLike::class);
    expect($dislike->is_like)->toBeFalse();
    
    // 데이터베이스에 싫어요만 존재하는지 확인
    $this->assertDatabaseHas('post_likes', [
        'likeable_type' => BoardNotice::class,
        'likeable_id' => $this->post->id,
        'user_id' => $this->user->id,
        'is_like' => false,
    ]);
    
    // 좋아요 레코드는 더 이상 존재하지 않아야 함
    $this->assertDatabaseMissing('post_likes', [
        'likeable_type' => BoardNotice::class,
        'likeable_id' => $this->post->id,
        'user_id' => $this->user->id,
        'is_like' => true,
    ]);
});

/**
 * 테스트 목적: 게시글 스크랩 기능 검증
 * 테스트 시나리오: 사용자가 게시글을 스크랩하는 경우
 * 기대 결과: 스크랩 레코드가 생성됨
 * 관련 비즈니스 규칙: 사용자는 유용한 게시글을 스크랩하여 저장할 수 있음
 */
test('게시글_스크랩_기능', function () {
    // Given: 게시글과 사용자 준비
    
    // When: 게시글 스크랩
    $scrap = $this->likeScrapService->toggleScrap($this->post, $this->user, '유용한 정보');
    
    // Then: 스크랩이 정상적으로 생성되는지 확인
    expect($scrap)->toBeInstanceOf(Scrap::class);
    expect($scrap->user_id)->toBe($this->user->id);
    expect($scrap->scrapable_type)->toBe(BoardNotice::class);
    expect($scrap->scrapable_id)->toBe($this->post->id);
    expect($scrap->memo)->toBe('유용한 정보');
    
    // 데이터베이스에 스크랩 레코드가 생성되었는지 확인
    $this->assertDatabaseHas('scraps', [
        'scrapable_type' => BoardNotice::class,
        'scrapable_id' => $this->post->id,
        'user_id' => $this->user->id,
        'memo' => '유용한 정보',
    ]);
});

/**
 * 테스트 목적: 중복 스크랩 시 토글 기능 검증
 * 테스트 시나리오: 이미 스크랩한 게시글을 다시 스크랩하는 경우
 * 기대 결과: 스크랩이 취소됨 (토글 동작)
 * 관련 비즈니스 규칙: 중복 스크랩은 토글로 처리하여 스크랩 취소 가능
 */
test('스크랩_토글_기능', function () {
    // Given: 이미 스크랩한 상태
    $firstScrap = $this->likeScrapService->toggleScrap($this->post, $this->user, '첫번째 스크랩');
    expect($firstScrap)->toBeInstanceOf(Scrap::class);
    
    // When: 같은 게시글을 다시 스크랩
    $result = $this->likeScrapService->toggleScrap($this->post, $this->user);
    
    // Then: 스크랩이 취소되어야 함 (null 반환)
    expect($result)->toBeNull();
    
    // 데이터베이스에서 스크랩 레코드가 삭제되었는지 확인
    $this->assertDatabaseMissing('scraps', [
        'scrapable_type' => BoardNotice::class,
        'scrapable_id' => $this->post->id,
        'user_id' => $this->user->id,
    ]);
});

/**
 * 테스트 목적: 게시글의 좋아요/싫어요 통계 집계 기능 검증
 * 테스트 시나리오: 여러 사용자가 좋아요/싫어요를 누른 후 통계 확인
 * 기대 결과: 좋아요/싫어요 수가 정확하게 집계됨
 * 관련 비즈니스 규칙: 게시글의 인기도를 측정하기 위한 통계 제공
 */
test('좋아요_싫어요_통계_집계', function () {
    // Given: 여러 사용자가 좋아요/싫어요를 누른 상태
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $user3 = User::factory()->create();
    
    // 2명이 좋아요, 1명이 싫어요
    $this->likeScrapService->toggleLike($this->post, $user1);
    $this->likeScrapService->toggleLike($this->post, $user2);
    $this->likeScrapService->toggleDislike($this->post, $user3);
    
    // When: 통계 조회
    $stats = $this->likeScrapService->getLikeStats($this->post);
    
    // Then: 통계가 정확하게 집계되는지 확인
    expect($stats['likes'])->toBe(2);
    expect($stats['dislikes'])->toBe(1);
    expect($stats['total'])->toBe(3);
    expect($stats['like_ratio'])->toBe(2/3); // 좋아요 비율
});

/**
 * 테스트 목적: 사용자별 좋아요/스크랩 상태 확인 기능 검증
 * 테스트 시나리오: 특정 사용자가 게시글에 대한 좋아요/스크랩 여부 확인
 * 기대 결과: 사용자의 현재 상태가 정확하게 반환됨
 * 관련 비즈니스 규칙: UI에서 사용자의 현재 상태를 표시하기 위한 정보 제공
 */
test('사용자_좋아요_스크랩_상태_확인', function () {
    // Given: 사용자가 좋아요와 스크랩을 모두 한 상태
    $this->likeScrapService->toggleLike($this->post, $this->user);
    $this->likeScrapService->toggleScrap($this->post, $this->user, '스크랩 메모');
    
    // When: 사용자 상태 확인
    $status = $this->likeScrapService->getUserStatus($this->post, $this->user);
    
    // Then: 사용자의 현재 상태가 정확하게 반환되는지 확인
    expect($status['has_liked'])->toBeTrue();
    expect($status['has_disliked'])->toBeFalse();
    expect($status['has_scraped'])->toBeTrue();
    expect($status['scrap_memo'])->toBe('스크랩 메모');
    
    // 다른 사용자의 경우 모두 false여야 함
    $otherStatus = $this->likeScrapService->getUserStatus($this->post, $this->otherUser);
    expect($otherStatus['has_liked'])->toBeFalse();
    expect($otherStatus['has_disliked'])->toBeFalse();
    expect($otherStatus['has_scraped'])->toBeFalse();
    expect($otherStatus['scrap_memo'])->toBeNull();
});