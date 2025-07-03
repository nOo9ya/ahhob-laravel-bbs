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
        Schema::create('login_histories', function (Blueprint $table) {
            $table->id()->comment('로그인 기록 고유 ID');
            $table->morphs('authenticatable'); // authenticatable_type, authenticatable_id
            $table->string('ip_address', 45)->comment('로그인 IP 주소');
            $table->text('user_agent')->nullable()->comment('사용자 에이전트 정보');
            $table->string('browser', 100)->nullable()->comment('브라우저 정보');
            $table->string('os', 100)->nullable()->comment('운영체제 정보');
            $table->string('device_type', 50)->nullable()->comment('기기 타입 (desktop, mobile, tablet)');
            $table->text('location')->nullable()->comment('로그인 위치 정보');
            $table->string('login_method', 50)->default('email')->comment('로그인 방법 (email, google, kakao, naver, apple)');
            $table->enum('status', ['success', 'failed'])->default('success')->comment('로그인 시도 결과');
            $table->text('failure_reason')->nullable()->comment('로그인 실패 사유');
            $table->timestamps();
            
            // 인덱스 추가 (morphs는 자동으로 복합 인덱스 생성)
            $table->index('ip_address');
            $table->index('created_at');
        });
        
        DB::statement("ALTER TABLE `login_histories` comment '로그인 기록'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_histories');
    }
};
