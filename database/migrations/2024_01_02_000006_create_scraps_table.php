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
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->morphs('scrapable'); // board_post, shop_product 등
            $table->string('category', 50)->nullable(); // 사용자 정의 카테고리
            $table->string('memo')->nullable();
            $table->timestamps();
            
            // 인덱스
            $table->unique(['user_id', 'scrapable_type', 'scrapable_id']);
            $table->index(['user_id', 'category']);
            $table->index(['scrapable_type', 'scrapable_id']);
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