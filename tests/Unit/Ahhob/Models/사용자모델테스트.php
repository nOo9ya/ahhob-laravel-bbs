<?php

test('사용자_모델_생성_성공', function () {
    $user = 사용자_생성([
        'nickname' => '테스트 사용자',
        'email' => 'test@example.com',
    ]);

    expect($user->nickname)->toBe('테스트 사용자');
    expect($user->email)->toBe('test@example.com');
    expect($user->email)->toBeValidEmail();
});

test('사용자_비밀번호_해싱_확인', function () {
    $user = 사용자_생성([
        'password' => 'plaintext_password',
    ]);

    expect($user->password)->not->toBe('plaintext_password');
    expect(Hash::check('plaintext_password', $user->password))->toBeTrue();
});

test('사용자_이메일_유니크_제약조건', function () {
    사용자_생성(['email' => 'test@example.com']);

    expect(function () {
        사용자_생성(['email' => 'test@example.com']);
    })->toThrow(\Illuminate\Database\QueryException::class);
});

test('사용자_필수_필드_검증', function () {
    expect(function () {
        \App\Models\User::create([
            'email' => 'test@example.com',
            'password' => 'password',
        ]);
    })->toThrow(\Illuminate\Database\QueryException::class);
});

test('사용자_이메일_검증_확인', function () {
    $user = 사용자_생성(['email' => 'valid@example.com']);
    
    expect($user->email)->toBeValidEmail();
});

test('사용자_생성_시간_자동_설정', function () {
    $user = 사용자_생성();

    expect($user->created_at)->not->toBeNull();
    expect($user->updated_at)->not->toBeNull();
    expect($user->created_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('사용자_삭제_동작', function () {
    $user = 사용자_생성();
    $userId = $user->id;

    $user->delete();

    expect(\App\Models\User::find($userId))->toBeNull();
});