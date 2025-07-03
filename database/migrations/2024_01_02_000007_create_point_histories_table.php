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
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('admin_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('type', ['earned', 'used', 'transferred', 'expired', 'adjusted']);
            $table->integer('points'); // 양수: 적립, 음수: 차감
            $table->integer('balance_after'); // 거래 후 잔액
            $table->string('reason');
            $table->morphs('related')->nullable(); // 관련 객체 (게시글, 주문 등)
            $table->foreignId('transfer_to_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // 인덱스
            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'created_at']);
            $table->index(['related_type', 'related_id']);
            $table->index('expires_at');
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