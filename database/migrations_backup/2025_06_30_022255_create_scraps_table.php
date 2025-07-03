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
        Schema::create('scraps', function (Blueprint $table) {
            $table->id()->comment('스크랩 고유 ID');
            
            // 다형적 관계 (게시글, 댓글 등 스크랩 가능)
            $table->morphs('scrapable', 'scraps_scrapable_index');
            
            // 스크랩한 사용자
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('스크랩한 사용자 ID');
            
            // 스크랩 분류 (개인 북마크 등)
            $table->string('category', 50)->nullable()->comment('스크랩 카테고리');
            $table->text('memo')->nullable()->comment('개인 메모');
            
            // 기록 정보  
            $table->string('user_ip', 45)->nullable()->comment('스크랩한 IP 주소');
            
            $table->timestamps();
            
            // 중복 방지를 위한 유니크 제약조건
            $table->unique(['scrapable_type', 'scrapable_id', 'user_id'], 'unique_user_scrap');
            
            // 인덱스 설정
            $table->index(['scrapable_type', 'scrapable_id']);
            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scraps');
    }
};
