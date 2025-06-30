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
        Schema::create('posting_limits', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('제한 정책명');
            $table->string('target_type')->comment('제한 대상 (user, user_level, board, global)');
            $table->unsignedBigInteger('target_id')->nullable()->comment('대상 ID (target_type에 따라 user_id, board_id 등)');
            $table->string('activity_type')->comment('활동 유형 (post, comment, like, scrap)');
            $table->unsignedInteger('daily_limit')->comment('일일 제한 수');
            $table->unsignedInteger('hourly_limit')->nullable()->comment('시간당 제한 수');
            $table->json('time_restrictions')->nullable()->comment('시간대별 제한 (JSON)');
            $table->boolean('is_active')->default(true)->comment('활성화 여부');
            $table->text('description')->nullable()->comment('제한 정책 설명');
            $table->timestamps();
            
            $table->index(['target_type', 'target_id', 'activity_type']);
            $table->index(['is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posting_limits');
    }
};
