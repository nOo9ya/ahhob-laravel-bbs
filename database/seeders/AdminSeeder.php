<?php

namespace Database\Seeders;

use App\Enums\AdminRole;
use App\Enums\AdminStatus;
use App\Models\Ahhob\Admin\Admin;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 슈퍼 관리자 생성
        Admin::create([
            'username' => 'superadmin',
            'password' => Hash::make('password123!'),
            'name' => '슈퍼 관리자',
            'email' => 'superadmin@ahhob.test',
            'role' => AdminRole::SUPER_ADMIN,
            'permissions' => [], // 슈퍼 관리자는 모든 권한 보유
            'status' => AdminStatus::ACTIVE,
            'memo' => '시스템 최고 관리자',
        ]);

        // 일반 관리자 생성
        Admin::create([
            'username' => 'admin',
            'password' => Hash::make('password123!'),
            'name' => '일반 관리자',
            'email' => 'admin@ahhob.test',
            'role' => AdminRole::ADMIN,
            'permissions' => [
                'users.view',
                'users.edit',
                'posts.view',
                'posts.edit',
                'posts.delete',
                'comments.view',
                'comments.edit',
                'comments.delete',
            ],
            'status' => AdminStatus::ACTIVE,
            'memo' => '커뮤니티 관리 담당',
        ]);

        // 매니저 생성
        Admin::create([
            'username' => 'manager',
            'password' => Hash::make('password123!'),
            'name' => '매니저',
            'email' => 'manager@ahhob.test',
            'role' => AdminRole::MANAGER,
            'permissions' => [
                'posts.view',
                'posts.edit',
                'comments.view',
                'comments.edit',
            ],
            'status' => AdminStatus::ACTIVE,
            'memo' => '콘텐츠 관리 담당',
        ]);

        $this->command->info('관리자 계정 3개가 생성되었습니다.');
        $this->command->info('슈퍼 관리자: superadmin / password123!');
        $this->command->info('일반 관리자: admin / password123!');
        $this->command->info('매니저: manager / password123!');
    }
}
