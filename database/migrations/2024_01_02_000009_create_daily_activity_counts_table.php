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
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('board_id')->nullable()->constrained()->onDelete('cascade');
            $table->date('activity_date');
            $table->integer('posts_count')->default(0);
            $table->integer('comments_count')->default(0);
            $table->string('ip_address', 45)->nullable();
            $table->string('device_fingerprint', 64)->nullable(); // 기기 핑거프린팅
            $table->json('violation_flags')->nullable(); // 스팸 탐지 플래그
            $table->timestamps();
            
            // 인덱스
            $table->unique(['user_id', 'board_id', 'activity_date']);
            $table->index(['activity_date', 'ip_address']);
            $table->index(['device_fingerprint', 'activity_date']);
            $table->index('violation_flags');
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