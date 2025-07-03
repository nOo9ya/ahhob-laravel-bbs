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
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('scope', ['global', 'board', 'user_level'])->default('global');
            $table->foreignId('board_id')->nullable()->constrained()->onDelete('cascade');
            $table->integer('user_level')->nullable();
            $table->integer('max_posts_per_day')->default(10);
            $table->integer('max_comments_per_day')->default(50);
            $table->integer('priority')->default(1); // 높을수록 우선순위
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // 인덱스
            $table->index(['scope', 'is_active', 'priority']);
            $table->index(['board_id', 'is_active']);
            $table->index(['user_level', 'is_active']);
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