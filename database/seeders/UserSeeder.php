<?php

namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 테스트 사용자 생성
        User::create([
            'username' => 'testuser',
            'nickname' => '테스트유저',
            'real_name' => '김테스트',
            'email' => 'test@ahhob.test',
            'password' => Hash::make('password123!'),
            'phone_number' => '010-1234-5678',
            'postal_code' => '12345',
            'address_line1' => '서울시 강남구 테헤란로 123',
            'address_line2' => '456호',
            'bio' => '안녕하세요! 테스트 사용자입니다.',
            'points' => 1000,
            'level' => 5,
            'status' => UserStatus::ACTIVE,
            'email_verified_at' => now(),
        ]);

        // 추가 테스트 사용자들 생성
        for ($i = 1; $i <= 5; $i++) {
            User::create([
                'username' => "user{$i}",
                'nickname' => "사용자{$i}",
                'real_name' => "김사용자{$i}",
                'email' => "user{$i}@ahhob.test",
                'password' => Hash::make('password123!'),
                'phone_number' => sprintf('010-%04d-%04d', rand(1000, 9999), rand(1000, 9999)),
                'points' => rand(100, 5000),
                'level' => rand(1, 10),
                'status' => UserStatus::ACTIVE,
                'email_verified_at' => now(),
            ]);
        }

        $this->command->info('테스트 사용자 6명이 생성되었습니다.');
        $this->command->info('테스트 사용자: testuser / password123!');
        $this->command->info('추가 사용자: user1~user5 / password123!');
    }
}
