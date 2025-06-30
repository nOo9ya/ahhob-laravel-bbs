<?php

namespace Tests\Feature\Ahhob\Admin;

use App\Models\Ahhob\Admin\Admin;
use App\Enums\AdminStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 관리자_로그인_페이지_접근_가능()
    {
        $response = $this->get(route('admin.login'));
        
        $response->assertStatus(200);
        $response->assertViewIs('ahhob.admin.auth.login');
        $response->assertSee('관리자 로그인');
    }

    /** @test */
    public function 관리자_로그인_성공()
    {
        $admin = Admin::factory()->create([
            'username' => 'admin',
            'password' => bcrypt('adminpass123'),
            'status' => AdminStatus::ACTIVE,
        ]);

        $response = $this->post(route('admin.login'), [
            'username' => 'admin',
            'password' => 'adminpass123',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    /** @test */
    public function 관리자_로그인_실패_잘못된_비밀번호()
    {
        $admin = Admin::factory()->create([
            'username' => 'admin',
            'password' => bcrypt('adminpass123'),
            'status' => AdminStatus::ACTIVE,
        ]);

        $response = $this->post(route('admin.login'), [
            'username' => 'admin',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors(['username']);
        $this->assertGuest('admin');
    }

    /** @test */
    public function 관리자_로그아웃_성공()
    {
        $admin = Admin::factory()->create([
            'status' => AdminStatus::ACTIVE,
        ]);

        $this->actingAs($admin, 'admin');

        $response = $this->post(route('admin.logout'));

        $response->assertRedirect(route('admin.login'));
        $this->assertGuest('admin');
    }
}