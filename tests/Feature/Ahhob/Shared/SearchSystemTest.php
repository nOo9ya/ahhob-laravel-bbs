<?php

use App\Models\Ahhob\Board\Dynamic\BoardNotice;
use App\Models\User;
use App\Services\Ahhob\Shared\SearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->searchService = app(SearchService::class);
    
    // 동적 게시판 테이블 생성
    Artisan::call('board:create', ['slug' => 'notice']);
    
    // 테스트용 게시글 생성
    $this->posts = collect([
        BoardNotice::factory()->create([
            'title' => 'Laravel 프레임워크 소개',
            'content' => 'Laravel은 PHP 웹 프레임워크입니다. Eloquent ORM을 제공합니다.',
            'user_id' => $this->user->id,
            'status' => 'published',
            'published_at' => now(),
        ]),
        BoardNotice::factory()->create([
            'title' => 'Vue.js 시작하기',
            'content' => 'Vue.js는 프론트엔드 JavaScript 프레임워크입니다.',
            'user_id' => $this->user->id,
            'status' => 'published',
            'published_at' => now(),
        ]),
        BoardNotice::factory()->create([
            'title' => 'React 개발 가이드',
            'content' => 'React는 컴포넌트 기반의 UI 라이브러리입니다.',
            'user_id' => $this->user->id,
            'status' => 'published',
            'published_at' => now(),
        ]),
        BoardNotice::factory()->create([
            'title' => '비공개 게시글',
            'content' => '이 게시글은 비공개입니다.',
            'user_id' => $this->user->id,
            'status' => 'draft',
            'published_at' => null,
        ]),
    ]);
});

test('기본_키워드_검색', function () {
    $results = $this->searchService->searchPosts('Laravel');
    
    expect($results->total())->toBe(1);
    expect($results->first()->title)->toBe('Laravel 프레임워크 소개');
});

test('제목_검색', function () {
    $results = $this->searchService->searchPosts('Vue.js', ['title']);
    
    expect($results->total())->toBe(1);
    expect($results->first()->title)->toBe('Vue.js 시작하기');
});

test('내용_검색', function () {
    $results = $this->searchService->searchPosts('컴포넌트', ['content']);
    
    expect($results->total())->toBe(1);
    expect($results->first()->title)->toBe('React 개발 가이드');
});

test('제목과_내용_동시_검색', function () {
    $results = $this->searchService->searchPosts('프레임워크');
    
    expect($results->total())->toBe(2);
    $titles = $results->pluck('title')->toArray();
    expect($titles)->toContain('Laravel 프레임워크 소개');
    expect($titles)->toContain('Vue.js 시작하기');
});

test('대소문자_구분_없는_검색', function () {
    $results = $this->searchService->searchPosts('laravel');
    
    expect($results->total())->toBe(1);
    expect($results->first()->title)->toBe('Laravel 프레임워크 소개');
});

test('부분_단어_검색', function () {
    $results = $this->searchService->searchPosts('React');
    
    expect($results->total())->toBe(1);
    expect($results->first()->title)->toBe('React 개발 가이드');
});

test('빈_검색어_처리', function () {
    $results = $this->searchService->searchPosts('');
    
    expect($results->total())->toBe(0);
});

test('검색_결과_없음', function () {
    $results = $this->searchService->searchPosts('존재하지않는키워드');
    
    expect($results->total())->toBe(0);
});

test('공개된_게시글만_검색', function () {
    $results = $this->searchService->searchPosts('게시글');
    
    expect($results->total())->toBe(0); // 비공개 게시글은 검색되지 않음
});

test('작성자별_검색', function () {
    $anotherUser = User::factory()->create();
    BoardNotice::factory()->create([
        'title' => '다른 사용자 게시글',
        'content' => 'Laravel 관련 내용',
        'user_id' => $anotherUser->id,
        'status' => 'published',
        'published_at' => now(),
    ]);
    
    $results = $this->searchService->searchPostsByUser('Laravel', $this->user);
    
    expect($results->total())->toBe(1);
    expect($results->first()->title)->toBe('Laravel 프레임워크 소개');
});

test('날짜_범위_검색', function () {
    $yesterday = now()->subDay();
    $tomorrow = now()->addDay();
    
    $results = $this->searchService->searchPostsByDateRange('Laravel', $yesterday, $tomorrow);
    
    expect($results->total())->toBe(1);
    expect($results->first()->title)->toBe('Laravel 프레임워크 소개');
});

test('검색_결과_정렬', function () {
    // 최신순 정렬
    $results = $this->searchService->searchPosts('프레임워크', null, 'latest');
    
    expect($results->total())->toBe(2);
    $titles = $results->pluck('title')->toArray();
    // 두 게시글이 모두 포함되어야 함
    expect($titles)->toContain('Laravel 프레임워크 소개');
    expect($titles)->toContain('Vue.js 시작하기');
});

test('페이지네이션_적용', function () {
    $results = $this->searchService->searchPosts('프레임워크', null, 'latest', 1);
    
    expect($results->perPage())->toBe(1);
    expect($results->total())->toBe(2);
    expect($results->currentPage())->toBe(1);
});

test('검색_로그_기록', function () {
    $keyword = 'Laravel';
    $results = $this->searchService->searchPosts($keyword);
    
    // 검색 로그가 기록되었는지 확인
    $this->assertDatabaseHas('search_logs', [
        'keyword' => $keyword,
        'results_count' => 1,
        'user_id' => null, // 비로그인 사용자
    ]);
});

test('로그인_사용자_검색_로그', function () {
    $keyword = 'Vue.js';
    $results = $this->searchService->searchPostsAsUser($keyword, $this->user);
    
    // 로그인 사용자의 검색 로그 확인
    $this->assertDatabaseHas('search_logs', [
        'keyword' => $keyword,
        'results_count' => 1,
        'user_id' => $this->user->id,
    ]);
});

test('인기_검색어_조회', function () {
    // 여러 검색 실행
    $this->searchService->searchPosts('Laravel');
    $this->searchService->searchPosts('Laravel');
    $this->searchService->searchPosts('Vue.js');
    
    $popularKeywords = $this->searchService->getPopularKeywords(5);
    
    expect($popularKeywords)->toHaveCount(2);
    expect($popularKeywords->first()['keyword'])->toBe('Laravel');
    expect($popularKeywords->first()['count'])->toBe(2);
});

test('자동완성_키워드_제안', function () {
    $suggestions = $this->searchService->getSuggestions('Lar');
    
    expect($suggestions)->toContain('Laravel');
});

test('검색어_하이라이팅', function () {
    $results = $this->searchService->searchPostsWithHighlight('Laravel');
    
    $post = $results->first();
    expect($post->highlighted_title)->toContain('<mark>Laravel</mark>');
    expect($post->highlighted_content)->toContain('<mark>Laravel</mark>');
});

test('다중_키워드_AND_검색', function () {
    $results = $this->searchService->searchPosts('Laravel PHP');
    
    expect($results->total())->toBe(1);
    expect($results->first()->title)->toBe('Laravel 프레임워크 소개');
});

test('다중_키워드_OR_검색', function () {
    $results = $this->searchService->searchPostsWithMode('Laravel|React', 'or');
    
    expect($results->total())->toBe(2);
    $titles = $results->pluck('title')->toArray();
    expect($titles)->toContain('Laravel 프레임워크 소개');
    expect($titles)->toContain('React 개발 가이드');
});

test('검색_필터_적용', function () {
    $filters = [
        'date_from' => now()->subDay()->toDateString(),
        'date_to' => now()->addDay()->toDateString(),
        'author' => $this->user->nickname,
    ];
    
    $results = $this->searchService->searchPostsWithFilters('Laravel', $filters);
    
    expect($results->total())->toBe(1);
    expect($results->first()->title)->toBe('Laravel 프레임워크 소개');
});

test('검색_성능_측정', function () {
    $startTime = microtime(true);
    
    $results = $this->searchService->searchPosts('프레임워크');
    
    $endTime = microtime(true);
    $executionTime = $endTime - $startTime;
    
    // 검색이 1초 이내에 완료되어야 함
    expect($executionTime)->toBeLessThan(1.0);
    expect($results->total())->toBe(2);
});