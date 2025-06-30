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
        Schema::create('daily_activity_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->comment('사용자 ID');
            $table->string('activity_type')->comment('활동 유형 (post, comment, like, scrap)');
            $table->unsignedBigInteger('target_id')->nullable()->comment('대상 ID (board_id 등)');
            $table->string('target_type')->nullable()->comment('대상 타입 (board 등)');
            $table->date('activity_date')->comment('활동 날짜');
            $table->unsignedInteger('count')->default(0)->comment('활동 횟수');
            $table->string('ip_address', 45)->nullable()->comment('IP 주소');
            $table->string('user_agent_hash')->nullable()->comment('User Agent 해시 (기기 식별용)');
            $table->json('device_fingerprint')->nullable()->comment('기기 핑거프린트 (다중 계정 탐지용)');
            $table->timestamps();
            
            $table->unique(['user_id', 'activity_type', 'target_id', 'target_type', 'activity_date'], 'unique_daily_activity');
            $table->index(['activity_date', 'activity_type']);
            $table->index(['ip_address', 'activity_date']);
            $table->index(['user_agent_hash', 'activity_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_activity_counts');
    }
};
