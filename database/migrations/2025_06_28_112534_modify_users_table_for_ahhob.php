<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Laravel 기본 name 컬럼은 유지하고 추가 컬럼만 생성
            $table->string('username', 50)->unique()->after('name')->comment('로그인 아이디');
            $table->string('nickname', 100)->unique()->after('username')->comment('닉네임');
            
            // 추가 사용자 정보 (필수 정보만)
            $table->string('phone_number', 20)->nullable()->unique()->after('email_verified_at')->comment('휴대폰 번호');
            $table->string('profile_image_path')->nullable()->after('phone_number')->comment('프로필 이미지 파일 경로');
            $table->text('bio')->nullable()->after('profile_image_path')->comment('자기소개');
            
            // 포인트 및 레벨 시스템
            $table->integer('points')->default(0)->after('bio')->comment('보유 포인트');
            $table->tinyInteger('level')->unsigned()->default(1)->after('points')->comment('사용자 레벨');
            
            // 계정 상태 및 활동 추적
            $table->enum('status', ['active', 'dormant', 'suspended', 'banned'])
                  ->default('active')->after('level')->comment('계정 상태');
            $table->timestamp('last_login_at')->nullable()->after('status')->comment('최종 로그인 일시');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at')->comment('최종 로그인 IP');
            
            // 소프트 삭제
            $table->softDeletes()->after('updated_at')->comment('소프트 삭제 일시 (탈퇴일)');
        });
        
        // 테이블 코멘트 추가
        DB::statement("ALTER TABLE `users` comment '회원(사용자) 정보'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 추가된 컬럼들 삭제
            $table->dropColumn([
                'username', 'nickname', 'phone_number', 
                'profile_image_path', 'bio', 'points', 'level', 
                'status', 'last_login_at', 'last_login_ip', 'deleted_at'
            ]);
        });
    }
};
