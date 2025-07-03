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
        Schema::create('boards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_group_id')->constrained()->onDelete('cascade');
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['general', 'notice', 'gallery', 'qna'])->default('general');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            
            // 권한 설정
            $table->integer('read_level')->default(1);
            $table->integer('write_level')->default(1);
            $table->integer('comment_level')->default(1);
            $table->integer('download_level')->default(1);
            
            // 게시판 설정
            $table->boolean('allow_anonymous')->default(false);
            $table->boolean('allow_attachments')->default(true);
            $table->boolean('allow_comments')->default(true);
            $table->boolean('use_category')->default(false);
            $table->json('categories')->nullable();
            
            // 통계
            $table->integer('posts_count')->default(0);
            $table->integer('comments_count')->default(0);
            $table->timestamp('last_post_at')->nullable();
            
            $table->timestamps();
            
            // 인덱스
            $table->index(['board_group_id', 'is_active', 'sort_order']);
            $table->index(['type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boards');
    }
};