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
        Schema::create('search_logs', function (Blueprint $table) {
            $table->id()->comment('검색 로그 고유 ID');
            
            // 검색 정보
            $table->string('keyword')->comment('검색 키워드');
            $table->integer('results_count')->default(0)->comment('검색 결과 수');
            
            // 사용자 정보
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null')->comment('검색한 사용자 ID');
            $table->string('ip_address', 45)->nullable()->comment('IP 주소');
            $table->text('user_agent')->nullable()->comment('사용자 에이전트');
            
            // 성능 정보
            $table->float('execution_time', 8, 4)->nullable()->comment('검색 실행 시간 (초)');
            
            // 필터 정보
            $table->json('filters_used')->nullable()->comment('사용된 필터 정보');
            
            $table->timestamps();
            
            // 인덱스 설정
            $table->index(['keyword', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['created_at']);
            $table->index(['keyword', 'results_count']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('search_logs');
    }
};