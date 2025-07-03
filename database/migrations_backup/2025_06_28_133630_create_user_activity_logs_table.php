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
        Schema::create('user_activity_logs', function (Blueprint $table) {
            $table->id()->comment('활동 로그 고유 ID');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null')->comment('사용자 ID (users.id)');
            $table->string('activity_type', 50)->comment('활동 타입 (login, post_create, comment_create, etc.)');
            $table->morphs('related'); // related_type, related_id (다형적 관계)
            $table->json('activity_data')->nullable()->comment('활동 상세 데이터 (JSON)');
            $table->string('ip_address', 45)->nullable()->comment('활동 시 IP 주소');
            $table->text('user_agent')->nullable()->comment('사용자 에이전트 정보');
            $table->text('referer_url')->nullable()->comment('이전 방문 URL');
            $table->string('session_id', 100)->nullable()->comment('세션 ID');
            $table->timestamps();
            
            // 인덱스 추가 (morphs는 자동으로 복합 인덱스 생성)
            $table->index('user_id');
            $table->index('activity_type');
            $table->index('ip_address');
            $table->index('created_at');
        });
        
        DB::statement("ALTER TABLE `user_activity_logs` comment '사용자 활동 추적 로그'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_activity_logs');
    }
};
