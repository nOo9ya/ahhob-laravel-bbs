<?php

use App\Models\Ahhob\Board\Dynamic\BoardNotice;
use App\Models\Ahhob\Shared\PointHistory;
use App\Models\User;
use App\Services\Ahhob\Shared\PointService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    $this->user = User::factory()->create(['points' => 100]);
    $this->pointService = app(PointService::class);
    
    // 동적 게시판 테이블 생성
    Artisan::call('board:create', ['slug' => 'notice']);
    $this->post = BoardNotice::factory()->create();
});

test('기본_포인트_지급', function () {
    $initialPoints = $this->user->points;
    $awardAmount = 50;
    
    $history = $this->pointService->awardPoints(
        $this->user, 
        $awardAmount, 
        'event', 
        '테스트 보상'
    );
    
    $this->user->refresh();
    
    expect($history)->toBeInstanceOf(PointHistory::class);
    expect($history->user_id)->toBe($this->user->id);
    expect($history->points)->toBe($awardAmount);
    expect($history->type)->toBe('event');
    expect($history->description)->toBe('테스트 보상');
    expect($history->balance_before)->toBe($initialPoints);
    expect($history->balance_after)->toBe($initialPoints + $awardAmount);
    expect($this->user->points)->toBe($initialPoints + $awardAmount);
});

test('포인트_차감', function () {
    $initialPoints = $this->user->points;
    $deductAmount = 30;
    
    $history = $this->pointService->deductPoints(
        $this->user,
        $deductAmount,
        'penalty',
        '테스트 벌점'
    );
    
    $this->user->refresh();
    
    expect($history)->toBeInstanceOf(PointHistory::class);
    expect($history->points)->toBe(-$deductAmount);
    expect($history->type)->toBe('penalty');
    expect($history->description)->toBe('테스트 벌점');
    expect($history->balance_before)->toBe($initialPoints);
    expect($history->balance_after)->toBe($initialPoints - $deductAmount);
    expect($this->user->points)->toBe($initialPoints - $deductAmount);
});

test('포인트_부족시_차감_실패', function () {
    $user = User::factory()->create(['points' => 10]);
    $deductAmount = 50;
    
    expect(fn() => $this->pointService->deductPoints($user, $deductAmount, 'penalty', '테스트'))
        ->toThrow(InvalidArgumentException::class, '포인트가 부족합니다');
});

test('게시글_작성_포인트_지급', function () {
    $initialPoints = $this->user->points;
    
    $history = $this->pointService->awardPostPoints($this->user, $this->post);
    
    $this->user->refresh();
    
    expect($history)->toBeInstanceOf(PointHistory::class);
    expect($history->pointable_type)->toBe(BoardNotice::class);
    expect($history->pointable_id)->toBe($this->post->id);
    expect($history->type)->toBe('post_write');
    expect($this->user->points)->toBeGreaterThan($initialPoints);
});

test('댓글_작성_포인트_지급', function () {
    $initialPoints = $this->user->points;
    
    $history = $this->pointService->awardCommentPoints($this->user, $this->post);
    
    $this->user->refresh();
    
    expect($history)->toBeInstanceOf(PointHistory::class);
    expect($history->pointable_type)->toBe(BoardNotice::class);
    expect($history->pointable_id)->toBe($this->post->id);
    expect($history->type)->toBe('comment_write');
    expect($this->user->points)->toBeGreaterThan($initialPoints);
});

test('좋아요_받기_포인트_지급', function () {
    $initialPoints = $this->user->points;
    $giver = User::factory()->create();
    
    $history = $this->pointService->awardLikePoints($this->user, $this->post, $giver);
    
    $this->user->refresh();
    
    expect($history)->toBeInstanceOf(PointHistory::class);
    expect($history->pointable_type)->toBe(BoardNotice::class);
    expect($history->pointable_id)->toBe($this->post->id);
    expect($history->type)->toBe('post_like');
    expect($this->user->points)->toBeGreaterThan($initialPoints);
});

test('출석_체크_포인트_지급', function () {
    $initialPoints = $this->user->points;
    
    $history = $this->pointService->awardAttendancePoints($this->user);
    
    $this->user->refresh();
    
    expect($history)->toBeInstanceOf(PointHistory::class);
    expect($history->type)->toBe('daily_login');
    expect($this->user->points)->toBeGreaterThan($initialPoints);
});

test('중복_출석_체크_방지', function () {
    // 첫 번째 출석 체크
    $this->pointService->awardAttendancePoints($this->user);
    $pointsAfterFirst = $this->user->fresh()->points;
    
    // 같은 날 두 번째 출석 체크 시도
    $history = $this->pointService->awardAttendancePoints($this->user);
    
    expect($history)->toBeNull();
    expect($this->user->fresh()->points)->toBe($pointsAfterFirst);
});

test('포인트_전송', function () {
    $sender = $this->user;
    $receiver = User::factory()->create(['points' => 50]);
    $transferAmount = 25;
    
    $result = $this->pointService->transferPoints($sender, $receiver, $transferAmount, '친구에게 선물');
    
    $sender->refresh();
    $receiver->refresh();
    
    expect($result)->toBeTrue();
    expect($sender->points)->toBe(100 - $transferAmount);
    expect($receiver->points)->toBe(50 + $transferAmount);
    
    // 포인트 히스토리 확인
    $senderHistory = PointHistory::where('user_id', $sender->id)
        ->where('points', '<', 0)
        ->where('type', 'other')
        ->first();
    
    $receiverHistory = PointHistory::where('user_id', $receiver->id)
        ->where('points', '>', 0)
        ->where('type', 'other')
        ->first();
    
    expect($senderHistory)->not->toBeNull();
    expect($receiverHistory)->not->toBeNull();
    expect($senderHistory->points)->toBe(-$transferAmount);
    expect($receiverHistory->points)->toBe($transferAmount);
});

test('포인트_전송_실패_부족한_잔액', function () {
    $sender = User::factory()->create(['points' => 10]);
    $receiver = User::factory()->create(['points' => 50]);
    $transferAmount = 25;
    
    expect(fn() => $this->pointService->transferPoints($sender, $receiver, $transferAmount, '테스트'))
        ->toThrow(InvalidArgumentException::class, '포인트가 부족합니다');
});

test('포인트_히스토리_조회', function () {
    // 여러 포인트 활동 생성
    $this->pointService->awardPoints($this->user, 30, 'event', '테스트1');
    $this->pointService->deductPoints($this->user, 20, 'penalty', '테스트2');
    $this->pointService->awardPostPoints($this->user, $this->post);
    
    $histories = $this->pointService->getPointHistory($this->user, 10);
    
    expect($histories)->toHaveCount(3);
    expect($histories->first()->user_id)->toBe($this->user->id);
});

test('포인트_통계_조회', function () {
    // 다양한 포인트 활동 생성
    $this->pointService->awardPoints($this->user, 50, 'event', '보너스');
    $this->pointService->deductPoints($this->user, 30, 'penalty', '벌점');
    $this->pointService->awardPostPoints($this->user, $this->post);
    
    $stats = $this->pointService->getPointStats($this->user);
    
    expect($stats)->toHaveKeys(['total_earned', 'total_spent', 'current_balance', 'transaction_count']);
    expect($stats['current_balance'])->toBe($this->user->fresh()->points);
    expect($stats['transaction_count'])->toBeGreaterThan(0);
});

test('포인트_랭킹_조회', function () {
    // 여러 사용자 생성
    User::factory()->create(['points' => 200]);
    User::factory()->create(['points' => 150]);
    User::factory()->create(['points' => 300]);
    
    $rankings = $this->pointService->getPointRankings(5);
    
    expect($rankings)->toHaveCount(4); // 테스트 사용자 + 3명
    expect($rankings->first()->points)->toBe(300);
    expect($rankings->pluck('points')->toArray())->toBe([300, 200, 150, 100]);
});

test('일일_포인트_제한_확인', function () {
    $user = User::factory()->create(['points' => 1000]);
    $config = ['daily_limit' => 100];
    
    // 오늘 이미 90포인트 획득했다고 가정
    PointHistory::create([
        'user_id' => $user->id,
        'points' => 90,
        'balance_before' => 1000,
        'balance_after' => 1090,
        'type' => 'event',
        'description' => '테스트',
        'created_at' => now(),
    ]);
    
    $canEarn = $this->pointService->canEarnPoints($user, 20, $config);
    $cannotEarn = $this->pointService->canEarnPoints($user, 50, $config);
    
    expect($canEarn)->toBeFalse();
    expect($cannotEarn)->toBeFalse();
});

test('포인트_만료_처리', function () {
    $user = User::factory()->create(['points' => 100]);
    
    // 90일 전 포인트 히스토리 생성 (만료 대상)
    PointHistory::create([
        'user_id' => $user->id,
        'points' => 50,
        'balance_before' => 50,
        'balance_after' => 100,
        'type' => 'event',
        'description' => '만료 테스트',
        'created_at' => now()->subDays(91),
        'expires_at' => now()->subDay()->toDateString(),
        'is_expired' => false,
    ]);
    
    $expiredCount = $this->pointService->expirePoints();
    
    expect($expiredCount)->toBeGreaterThan(0);
});

test('관리자_포인트_조정', function () {
    $admin = User::factory()->create();
    $adjustAmount = 200;
    $reason = '관리자 조정';
    
    $history = $this->pointService->adjustPointsAsAdmin($this->user, $adjustAmount, $reason, $admin);
    
    $this->user->refresh();
    
    expect($history)->toBeInstanceOf(PointHistory::class);
    expect($history->points)->toBe($adjustAmount);
    expect($history->type)->toBe('admin_adjust');
    expect($history->admin_id)->toBe($admin->id);
    expect($this->user->points)->toBe(100 + $adjustAmount);
});