<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

expect()->extend('toBeValidEmail', function () {
    return $this->toMatch('/^[^\s@]+@[^\s@]+\.[^\s@]+$/');
});

expect()->extend('toBeValidUrl', function () {
    return $this->toMatch('/^https?:\/\/.+/');
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function 관리자_생성()
{
    return \App\Models\Admin::factory()->create();
}

function 사용자_생성(array $attributes = [])
{
    return \App\Models\User::factory()->create($attributes);
}

function 게시글_생성(array $attributes = [])
{
    return \App\Models\Ahhob\Board\Post::factory()->create($attributes);
}

function 인증된_사용자로_접속($user = null)
{
    $user = $user ?: 사용자_생성();
    return test()->actingAs($user);
}

function 관리자로_접속($admin = null)
{
    $admin = $admin ?: 관리자_생성();
    return test()->actingAs($admin, 'admin');
}
