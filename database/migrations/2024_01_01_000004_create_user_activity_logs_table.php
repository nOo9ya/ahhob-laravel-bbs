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
        Schema::create('user_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('activity_type', [
                'login', 'logout', 'register', 'profile_update',
                'post_create', 'post_update', 'post_delete',
                'comment_create', 'comment_update', 'comment_delete',
                'like', 'unlike', 'scrap', 'unscrap',
                'shop_view', 'shop_purchase', 'shop_review'
            ]);
            $table->string('description')->nullable();
            $table->nullableMorphs('related'); // related_type, related_id (nullable)
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // 인덱스
            $table->index(['user_id', 'activity_type']);
            $table->index(['related_type', 'related_id']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_activity_logs');
    }
};