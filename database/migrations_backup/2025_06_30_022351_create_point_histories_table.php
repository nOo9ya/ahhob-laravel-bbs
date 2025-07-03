<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('point_histories', function (Blueprint $table) {
            $table->id()->comment('포인트 내역 고유 ID');
            
            // 포인트를 받은/차감된 사용자
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('사용자 ID');
            
            // 포인트 관련 객체 (다형적 관계)
            $table->nullableMorphs('pointable', 'point_histories_pointable_index');
            
            // 포인트 변동 정보
            $table->integer('points')->comment('포인트 (양수: 적립, 음수: 차감)');
            $table->integer('balance_before')->comment('변동 전 잔액');
            $table->integer('balance_after')->comment('변동 후 잔액');
            
            // 포인트 유형
            $table->enum('type', [
                'post_write',       // 게시글 작성
                'comment_write',    // 댓글 작성
                'post_like',        // 게시글 좋아요 받음
                'comment_like',     // 댓글 좋아요 받음
                'daily_login',      // 일일 로그인
                'welcome_bonus',    // 가입 축하
                'admin_adjust',     // 관리자 조정
                'purchase',         // 상품 구매
                'refund',          // 환불
                'event',           // 이벤트
                'penalty',         // 제재
                'other'            // 기타
            ])->comment('포인트 유형');
            
            // 설명 및 메모
            $table->string('description', 255)->comment('포인트 변동 설명');
            $table->text('admin_memo')->nullable()->comment('관리자 메모');
            
            // 관리자 정보 (관리자가 직접 조정한 경우)
            $table->foreignId('admin_id')->nullable()->constrained('users')->onDelete('set null')->comment('처리한 관리자 ID');
            
            // 유효기간 (포인트 만료 관련)
            $table->date('expires_at')->nullable()->comment('포인트 만료일');
            $table->boolean('is_expired')->default(false)->comment('만료 여부');
            
            // 기록 정보
            $table->string('user_ip', 45)->nullable()->comment('사용자 IP 주소');
            
            $table->timestamps();
            
            // 인덱스 설정
            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'type']);
            $table->index(['pointable_type', 'pointable_id']);
            $table->index(['type', 'created_at']);
            $table->index(['expires_at', 'is_expired']);
            $table->index('admin_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('point_histories');
    }
};
