<?php

test('사용자_회원가입_성공', function () {
    $userData = [
        'name' => '테스트 사용자',
        'email' => 'test@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'username' => 'testuser123',
        'nickname' => '테스트닉네임',
        'real_name' => '테스트 실명',
        'terms_agreement' => true,
        'privacy_agreement' => true,
    ];

    $response = $this->post('/register', $userData);

    $response->assertStatus(302);
    expect(\App\Models\User::where('email', 'test@example.com')->exists())->toBeTrue();
});

test('사용자_로그인_성공', function () {
    $user = 사용자_생성([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(302);
    $this->assertAuthenticatedAs($user);
});

test('사용자_로그인_실패_잘못된_비밀번호', function () {
    사용자_생성([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(302);
    $response->assertSessionHasErrors(['email']);
    $this->assertGuest();
});

test('사용자_로그아웃_성공', function () {
    $user = 사용자_생성();
    
    $response = $this->actingAs($user)->post('/logout');

    $response->assertStatus(302);
    $response->assertRedirect('/');
    $this->assertGuest();
});

test('이메일_중복_회원가입_실패', function () {
    사용자_생성(['email' => 'test@example.com']);

    $response = $this->post('/register', [
        'name' => '테스트 사용자2',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(302);
    $response->assertSessionHasErrors(['email']);
});

test('비밀번호_확인_불일치_회원가입_실패', function () {
    $response = $this->post('/register', [
        'name' => '테스트 사용자',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'different_password',
    ]);

    $response->assertStatus(302);
    $response->assertSessionHasErrors(['password']);
});

test('필수_필드_누락_회원가입_실패', function () {
    $response = $this->post('/register', [
        'email' => 'test@example.com',
        'password' => 'Password123!',
    ]);

    $response->assertStatus(302);
    $response->assertSessionHasErrors();
});

test('이메일_형식_오류_회원가입_실패', function () {
    $response = $this->post('/register', [
        'name' => '테스트 사용자',
        'email' => 'invalid-email',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(302);
    $response->assertSessionHasErrors(['email']);
});