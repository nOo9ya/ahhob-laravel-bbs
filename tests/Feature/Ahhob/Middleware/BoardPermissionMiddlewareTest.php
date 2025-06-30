<?php

use App\Http\Middleware\Ahhob\BoardPermissionMiddleware;
use App\Models\Ahhob\Admin\Admin;
use App\Models\Ahhob\Board\Board;
use App\Models\Ahhob\Board\BoardManager;
use App\Models\User;
use Database\Factories\AdminFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

/**
 * 헬퍼 함수: Route가 설정된 Request 생성
 * 
 * @param string $url 요청 URL
 * @param string $method HTTP 메서드
 * @param string|null $boardSlug 게시판 슬러그 (null이면 Route 설정 안함)
 * @return Request 설정된 Request 객체
 */
function createRequestWithRoute($url, $method = 'GET', $boardSlug = null) {
    $request = Request::create($url, $method);
    
    if ($boardSlug !== null) {
        // 라라벨 Route 객체를 생성하여 실제 라우팅 환경과 유사하게 테스트
        $route = new \Illuminate\Routing\Route([$method], '/test/{boardSlug}', []);
        
        // Route 파라미터를 직접 설정 (bind 호출 없이)
        $route->parameters = ['boardSlug' => $boardSlug];
        
        $request->setRouteResolver(function () use ($route) {
            return $route;
        });
    }
    
    return $request;
}

beforeEach(function () {
    $this->middleware = new BoardPermissionMiddleware();

    // 테스트용 게시판 생성
    $this->board = Board::factory()->create([
        'slug' => 'test-board',
        'name' => '테스트 게시판',
        'is_active' => true,
    ]);

    // 테스트용 사용자들 생성
    $this->user = User::factory()->create();
    $this->boardManager = User::factory()->create();
    $this->superAdmin = AdminFactory::new()->superAdmin()->create();

    // 게시판 매니저 권한 부여
    BoardManager::create([
        'board_id' => $this->board->id,
        'user_id' => $this->boardManager->id,
    ]);
});

/**
 * 테스트 목적: 게시판 권한 미들웨어의 boardSlug 파라미터 누락 시 404 응답 확인
 * 테스트 시나리오: 라우트에 boardSlug 파라미터가 없는 요청이 들어온 경우
 * 기대 결과: 404 상태 코드와 "게시판을 찾을 수 없습니다" 메시지 반환
 * 관련 비즈니스 규칙: 모든 게시판 관련 요청은 유효한 boardSlug가 필요함
 */
test('게시판_슬러그가_없으면_404_응답', function () {
    // Given: boardSlug 파라미터가 없는 요청 생성
    // 실제 상황에서는 라우트 정의에 {boardSlug}가 있지만 요청에서 누락된 경우
    $request = createRequestWithRoute('/test', 'GET');
    
    // When: 미들웨어를 통해 요청 처리
    // 미들웨어는 boardSlug를 찾으려 하지만 없으므로 404 응답 예상
    $response = $this->middleware->handle($request, function () {
        return response('success');
    });

    // Then: 404 응답 확인
    // 게시판을 찾을 수 없다는 의미의 404 상태 코드 반환
    expect($response->getStatusCode())->toBe(404);
});

/**
 * 테스트 목적: 존재하지 않는 게시판 슬러그로 접근 시 404 응답 확인
 * 테스트 시나리오: 데이터베이스에 존재하지 않는 boardSlug로 요청한 경우
 * 기대 결과: 404 상태 코드와 "게시판을 찾을 수 없습니다" 메시지 반환
 * 관련 비즈니스 규칙: 모든 게시판 요청은 실제 존재하는 게시판에 대해서만 허용
 */
test('존재하지_않는_게시판_슬러그면_404_응답', function () {
    // Given: 데이터베이스에 존재하지 않는 게시판 슬러그로 요청 생성
    // 'non-existent-board'는 의도적으로 존재하지 않는 슬러그로 설정
    $request = createRequestWithRoute('/test/non-existent-board', 'GET', 'non-existent-board');
    
    // When: 미들웨어가 해당 슬러그로 게시판을 조회 시도
    // Board::where('slug', 'non-existent-board')->first() 결과는 null이 됨
    $response = $this->middleware->handle($request, function () {
        return response('success');
    });

    // Then: 게시판을 찾을 수 없어 404 응답 반환
    // 존재하지 않는 게시판에 대한 접근 차단
    expect($response->getStatusCode())->toBe(404);
});

/**
 * 테스트 목적: 비활성화된 게시판 접근 시 403 금지 응답 확인
 * 테스트 시나리오: 관리자가 게시판을 비활성화한 상태에서 사용자가 접근한 경우
 * 기대 결과: 403 상태 코드와 "비활성화된 게시판입니다" 메시지 반환
 * 관련 비즈니스 규칙: 비활성화된 게시판은 어떤 사용자도 접근할 수 없음
 */
test('비활성화된_게시판은_403_응답', function () {
    // Given: 기존 활성 상태의 게시판을 비활성화로 변경
    // 관리자가 게시판을 일시적으로 비활성화한 상황을 시뮤레이션
    $this->board->update(['is_active' => false]);
    
    // Given: 비활성화된 게시판에 대한 요청 생성
    $request = createRequestWithRoute('/test/test-board', 'GET', 'test-board');
    
    // When: 미들웨어가 게시판 상태 확인 후 접근 제한
    // is_active 값이 false이므로 접근 차단 예상
    $response = $this->middleware->handle($request, function () {
        return response('success');
    });

    // Then: 비활성화된 게시판에 대한 403 금지 응답
    // 사용자에게 "비활성화된 게시판입니다" 메시지 전달
    expect($response->getStatusCode())->toBe(403);
});

/**
 * 테스트 목적: 모든 사용자에게 열려있는 게시판의 읽기 권한 확인
 * 테스트 시나리오: read_permission이 'all'로 설정된 게시판에 비로그인 사용자가 접근한 경우
 * 기대 결과: 200 상태 코드와 정상 응답 반환
 * 관련 비즈니스 규칙: 'all' 권한은 누구나 접근 가능한 공개 게시판
 */
test('읽기_권한_확인_성공', function () {
    // Given: 게시판의 읽기 권한을 '모두에게 공개'로 설정
    // 비로그인 사용자도 접근 가능한 공개 게시판 상황
    $this->board->update(['read_permission' => 'all']);
    
    // Given: 해당 게시판에 대한 읽기 요청 생성
    $request = createRequestWithRoute('/test/test-board', 'GET', 'test-board');
    
    // When: 미들웨어가 읽기 권한을 확인하고 요청 처리
    // 'all' 권한이므로 인증 없이도 접근 허용되어야 함
    $response = $this->middleware->handle($request, function () {
        return response('success');
    }, 'read');

    // Then: 정상적인 200 응답과 원하는 콘텐츠 반환
    // 게시판 접근이 성공적으로 이루어짐
    expect($response->getStatusCode())->toBe(200);
    expect($response->getContent())->toBe('success');
});

test('로그인이_필요한_게시판에_비로그인_사용자_접근시_401_응답', function () {
    $this->board->update(['read_permission' => 'member']);
    
    $request = createRequestWithRoute('/test/test-board', 'GET', 'test-board');
    
    $response = $this->middleware->handle($request, function () {
        return response('success');
    }, 'read');

    expect($response->getStatusCode())->toBe(401);
});

test('로그인_사용자_읽기_권한_확인_성공', function () {
    $this->board->update(['read_permission' => 'member']);
    
    $request = createRequestWithRoute('/test/test-board', 'GET', 'test-board');
    
    $this->actingAs($this->user);
    
    $response = $this->middleware->handle($request, function () {
        return response('success');
    }, 'read');

    expect($response->getStatusCode())->toBe(200);
});

test('게시판_매니저_쓰기_권한_확인_성공', function () {
    $this->board->update(['write_permission' => 'admin']);
    
    $request = createRequestWithRoute('/test/test-board', 'POST', 'test-board');
    
    $this->actingAs($this->boardManager);
    
    $response = $this->middleware->handle($request, function () {
        return response('success');
    }, 'write');

    expect($response->getStatusCode())->toBe(200);
});

test('일반_사용자_관리자_전용_게시판_쓰기_권한_없음_401_응답', function () {
    $this->board->update(['write_permission' => 'admin']);
    
    $request = createRequestWithRoute('/test/test-board', 'POST', 'test-board');
    
    $this->actingAs($this->user);
    
    $response = $this->middleware->handle($request, function () {
        return response('success');
    }, 'write');

    expect($response->getStatusCode())->toBe(401);
});

test('슈퍼_관리자_모든_권한_보유_확인', function () {
    $this->board->update([
        'read_permission' => 'admin',
        'write_permission' => 'admin',
    ]);
    
    $request = createRequestWithRoute('/test/test-board', 'POST', 'test-board');
    
    $this->actingAs($this->superAdmin, 'admin');
    
    $response = $this->middleware->handle($request, function () {
        return response('success');
    }, 'write');

    expect($response->getStatusCode())->toBe(200);
});

test('JSON_요청시_JSON_응답_반환', function () {
    $this->board->update(['read_permission' => 'member']);
    
    $request = createRequestWithRoute('/test/test-board', 'GET', 'test-board');
    $request->headers->set('Accept', 'application/json');
    
    $response = $this->middleware->handle($request, function () {
        return response('success');
    }, 'read');

    expect($response->getStatusCode())->toBe(401);
    expect($response->getContent())->toBeJson();
    
    $data = json_decode($response->getContent(), true);
    expect($data['success'])->toBeFalse();
    expect($data['message'])->not()->toBeEmpty();
});

test('게시판_정보가_요청에_추가됨', function () {
    $this->board->update(['read_permission' => 'all']);
    
    $request = createRequestWithRoute('/test/test-board', 'GET', 'test-board');
    
    $boardFromRequest = null;
    
    $response = $this->middleware->handle($request, function ($req) use (&$boardFromRequest) {
        $boardFromRequest = $req->attributes->get('board');
        return response('success');
    }, 'read');

    expect($response->getStatusCode())->toBe(200);
    expect($boardFromRequest)->not()->toBeNull();
    expect($boardFromRequest->id)->toBe($this->board->id);
    expect($boardFromRequest->slug)->toBe('test-board');
});