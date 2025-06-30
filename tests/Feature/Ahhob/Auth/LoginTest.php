<?php

namespace Tests\Feature\Ahhob\Auth;

use App\Models\User;
use App\Enums\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 사용자_로그인_페이지_접근_가능()
    {
        $response = $this->get(route('login'));
        
        $response->assertStatus(200);
        $response->assertViewIs('ahhob.auth.login');
        $response->assertSee('로그인');
    }

    /** @test */
    public function 사용자_로그인_성공()
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'status' => UserStatus::ACTIVE,
        ]);

        $response = $this->post(route('login'), [
            'username' => 'testuser',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user, 'web');
    }

    /** @test */
    public function 사용자_로그인_실패_잘못된_비밀번호()
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'status' => UserStatus::ACTIVE,
        ]);

        $response = $this->post(route('login'), [
            'username' => 'testuser',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors(['username']);
        $this->assertGuest('web');
    }

    /** @test */
    public function 사용자_로그인_실패_비활성_계정()
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'status' => UserStatus::SUSPENDED,
        ]);

        $response = $this->post(route('login'), [
            'username' => 'testuser',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['username']);
        $this->assertGuest('web');
    }

    /** @test */
    public function 사용자_로그인_이메일로도_가능()
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'status' => UserStatus::ACTIVE,
        ]);

        $response = $this->post(route('login'), [
            'username' => 'test@example.com', // 이메일로 로그인
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user, 'web');
    }

    /** @test */
    public function 사용자_로그인_기억하기_옵션_동작()
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'status' => UserStatus::ACTIVE,
        ]);

        $response = $this->post(route('login'), [
            'username' => 'testuser',
            'password' => 'password123',
            'remember' => true,
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user, 'web');
        
        // Remember token이 설정되었는지 확인
        $user->refresh();
        $this->assertNotNull($user->remember_token);
    }

    /** @test */
    public function 사용자_로그인_API_요청_JSON_응답()
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'status' => UserStatus::ACTIVE,
        ]);

        $response = $this->postJson(route('login'), [
            'username' => 'testuser',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => '로그인이 완료되었습니다.',
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
    }

    /** @test */
    public function 사용자_로그아웃_성공()
    {
        $user = User::factory()->create([
            'status' => UserStatus::ACTIVE,
        ]);

        $this->actingAs($user, 'web');

        $response = $this->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertGuest('web');
    }

    /** @test */
    public function 사용자_현재_정보_조회_API()
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'nickname' => '테스트유저',
            'email' => 'test@example.com',
            'status' => UserStatus::ACTIVE,
            'points' => 1000,
            'level' => 5,
        ]);

        $this->actingAs($user, 'web');

        $response = $this->getJson(route('api.user.me'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'username' => 'testuser',
                'nickname' => '테스트유저',
                'email' => 'test@example.com',
                'points' => 1000,
                'level' => 5,
            ],
        ]);
    }
}