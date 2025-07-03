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
        Schema::create('admins', function (Blueprint $table) {
            $table->id()->comment('관리자 고유 ID');
            $table->string('username', 50)->unique()->comment('관리자 로그인 아이디');
            $table->string('password')->comment('해시된 비밀번호');
            $table->string('name', 100)->comment('관리자 실명');
            $table->string('email', 100)->unique()->comment('관리자 이메일 주소');
            $table->timestamp('email_verified_at')->nullable()->comment('이메일 인증 일시');
            $table->enum('role', ['super_admin', 'admin', 'manager'])
                  ->default('manager')->comment('관리자 역할 (super_admin, admin, manager)');
            $table->json('permissions')->nullable()->comment('세부 권한 설정 (JSON)');
            $table->enum('status', ['active', 'inactive', 'suspended'])
                  ->default('active')->comment('관리자 계정 상태');
            $table->timestamp('last_login_at')->nullable()->comment('최종 로그인 일시');
            $table->string('last_login_ip', 45)->nullable()->comment('최종 로그인 IP');
            $table->text('memo')->nullable()->comment('관리자 메모');
            $table->rememberToken();
            $table->timestamps();
        });
        
        DB::statement("ALTER TABLE `admins` comment 'CMS 관리자 정보'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
