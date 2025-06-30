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
        Schema::create('post_likes', function (Blueprint $table) {
            $table->id()->comment('좋아요 고유 ID');
            
            // 다형적 관계 (게시글, 댓글 모두 좋아요 가능)
            $table->morphs('likeable', 'post_likes_likeable_index');
            
            // 좋아요한 사용자
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('좋아요한 사용자 ID');
            
            // 좋아요 유형 (like, dislike 등 향후 확장 가능)
            $table->enum('type', ['like', 'dislike'])->default('like')->comment('좋아요 유형');
            
            // 기록 정보
            $table->string('user_ip', 45)->nullable()->comment('좋아요한 IP 주소');
            $table->string('user_agent', 500)->nullable()->comment('사용자 에이전트');
            
            $table->timestamps();
            
            // 중복 방지를 위한 유니크 제약조건
            $table->unique(['likeable_type', 'likeable_id', 'user_id', 'type'], 'unique_user_likeable');
            
            // 인덱스 설정
            $table->index(['likeable_type', 'likeable_id', 'type']);
            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_likes');
    }
};
