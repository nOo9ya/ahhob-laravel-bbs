<?php

use App\Models\User;
use App\Models\Ahhob\User\UserActivityLog;
use App\Enums\UserStatus;
use App\Enums\ActivityType;

test('사용자_회원가입_페이지_접근_가능', function () {
    $response = $this->get(route('register'));
    
    $response->assertStatus(200);
    $response->assertViewIs('ahhob.auth.register');
    $response->assertSee('회원가입');
});

test('사용자_회원가입_성공', function () {
    $userData = [
        'username' => 'newuser',
        'nickname' => '새사용자',
        'real_name' => '홍길동',
        'email' => 'newuser@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'phone_number' => '010-1234-5678',
        'terms_agree' => true,
        'privacy_agree' => true,
        'marketing_agree' => false,
    ];

    $response = $this->post(route('register'), $userData);

    $response->assertRedirect(route('email.verify'));
    
    // 사용자가 생성되었는지 확인
    $this->assertDatabaseHas('users', [
        'username' => 'newuser',
        'nickname' => '새사용자',
        'real_name' => '홍길동',
        'email' => 'newuser@example.com',
        'phone_number' => '010-1234-5678',
        'status' => UserStatus::ACTIVE->value,
        'points' => 100, // 가입 축하 포인트
        'level' => 1,
    ]);

    // 자동 로그인 확인
    $user = User::where('username', 'newuser')->first();
    $this->assertAuthenticatedAs($user, 'web');
});

test('사용자_회원가입_필수_약관_동의_확인', function () {
    $userData = [
        'username' => 'newuser',
        'nickname' => '새사용자',
        'real_name' => '홍길동',
        'email' => 'newuser@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'terms_agree' => false, // 이용약관 미동의
        'privacy_agree' => true,
    ];

    $response = $this->post(route('register'), $userData);

    $response->assertSessionHasErrors(['terms_agree']);
    $this->assertDatabaseMissing('users', [
        'username' => 'newuser',
    ]);
});

test('사용자_회원가입_개인정보_처리방침_동의_확인', function () {
    $userData = [
        'username' => 'newuser',
        'nickname' => '새사용자',
        'real_name' => '홍길동',
        'email' => 'newuser@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'terms_agree' => true,
        'privacy_agree' => false, // 개인정보 처리방침 미동의
    ];

    $response = $this->post(route('register'), $userData);

    $response->assertSessionHasErrors(['privacy_agree']);
    $this->assertDatabaseMissing('users', [
        'username' => 'newuser',
    ]);
});

test('사용자_회원가입_비밀번호_확인_불일치', function () {
    $userData = [
        'username' => 'newuser',
        'nickname' => '새사용자',
        'real_name' => '홍길동',
        'email' => 'newuser@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'DifferentPassword123!', // 비밀번호 불일치
        'terms_agree' => true,
        'privacy_agree' => true,
    ];

    $response = $this->post(route('register'), $userData);

    $response->assertSessionHasErrors(['password']);
    $this->assertDatabaseMissing('users', [
        'username' => 'newuser',
    ]);
});

test('사용자_회원가입_중복_아이디_검증', function () {
    // 기존 사용자 생성
    User::factory()->create([
        'username' => 'existinguser',
    ]);

    $userData = [
        'username' => 'existinguser', // 중복 아이디
        'nickname' => '새사용자',
        'real_name' => '홍길동',
        'email' => 'newuser@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'terms_agree' => true,
        'privacy_agree' => true,
    ];

    $response = $this->post(route('register'), $userData);

    $response->assertSessionHasErrors(['username']);
});

test('사용자_회원가입_중복_이메일_검증', function () {
    // 기존 사용자 생성
    User::factory()->create([
        'email' => 'existing@example.com',
    ]);

    $userData = [
        'username' => 'newuser',
        'nickname' => '새사용자',
        'real_name' => '홍길동',
        'email' => 'existing@example.com', // 중복 이메일
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'terms_agree' => true,
        'privacy_agree' => true,
    ];

    $response = $this->post(route('register'), $userData);

    $response->assertSessionHasErrors(['email']);
});

test('사용자_회원가입_중복_닉네임_검증', function () {
    // 기존 사용자 생성
    User::factory()->create([
        'nickname' => '기존닉네임',
    ]);

    $userData = [
        'username' => 'newuser',
        'nickname' => '기존닉네임', // 중복 닉네임
        'real_name' => '홍길동',
        'email' => 'newuser@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'terms_agree' => true,
        'privacy_agree' => true,
    ];

    $response = $this->post(route('register'), $userData);

    $response->assertSessionHasErrors(['nickname']);
});

test('사용자_회원가입_아이디_형식_검증', function () {
    $userData = [
        'username' => 'invalid-username!', // 특수문자 포함 (언더스코어 제외)
        'nickname' => '새사용자',
        'real_name' => '홍길동',
        'email' => 'newuser@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'terms_agree' => true,
        'privacy_agree' => true,
    ];

    $response = $this->post(route('register'), $userData);

    $response->assertSessionHasErrors(['username']);
});

test('사용자_회원가입_비밀번호_보안_검증', function () {
    $userData = [
        'username' => 'newuser',
        'nickname' => '새사용자',
        'real_name' => '홍길동',
        'email' => 'newuser@example.com',
        'password' => 'weak', // 약한 비밀번호
        'password_confirmation' => 'weak',
        'terms_agree' => true,
        'privacy_agree' => true,
    ];

    $response = $this->post(route('register'), $userData);

    $response->assertSessionHasErrors(['password']);
});

test('사용자_회원가입_활동_로그_기록', function () {
    $userData = [
        'username' => 'newuser',
        'nickname' => '새사용자',
        'real_name' => '홍길동',
        'email' => 'newuser@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'terms_agree' => true,
        'privacy_agree' => true,
    ];

    $response = $this->post(route('register'), $userData);

    $response->assertRedirect(route('email.verify'));
    
    $user = User::where('username', 'newuser')->first();
    
    // 회원가입 활동 로그 확인
    $this->assertDatabaseHas('user_activity_logs', [
        'user_id' => $user->id,
        'activity_type' => ActivityType::REGISTER->value,
        'ip_address' => '127.0.0.1',
    ]);
});

test('사용자_회원가입_API_요청_JSON_응답', function () {
    $userData = [
        'username' => 'newuser',
        'nickname' => '새사용자',
        'real_name' => '홍길동',
        'email' => 'newuser@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'terms_agree' => true,
        'privacy_agree' => true,
        'marketing_agree' => true,
    ];

    $response = $this->postJson(route('register'), $userData);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '회원가입이 완료되었습니다. 환영합니다!',
    ]);
    $response->assertJsonStructure([
        'success',
        'message',
        'user' => [
            'id',
            'username',
            'nickname',
            'email',
        ],
        'redirect_url',
    ]);
});

test('사용자_회원가입_휴대폰_번호_선택사항', function () {
    $userData = [
        'username' => 'newuser',
        'nickname' => '새사용자',
        'real_name' => '홍길동',
        'email' => 'newuser@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'terms_agree' => true,
        'privacy_agree' => true,
        // phone_number 생략
    ];

    $response = $this->post(route('register'), $userData);

    $response->assertRedirect(route('email.verify'));
    
    $this->assertDatabaseHas('users', [
        'username' => 'newuser',
        'phone_number' => null,
    ]);
});

test('사용자_회원가입_중복된_계정으로_접근시_홈으로_리다이렉트', function () {
    $user = User::factory()->create([
        'status' => UserStatus::ACTIVE,
    ]);

    $this->actingAs($user, 'web');

    $response = $this->get(route('register'));
    $response->assertRedirect(route('home'));
});