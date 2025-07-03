<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | 쇼핑몰 위시리스트 테이블 생성 (Shop Wishlists)
    |--------------------------------------------------------------------------
    */
    
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shop_wishlists', function (Blueprint $table) {
            $table->id();
            
            // 사용자 및 상품 관계
            $table->unsignedBigInteger('user_id')->comment('사용자 ID');
            $table->unsignedBigInteger('product_id')->comment('상품 ID');
            
            // 추가 정보
            $table->text('notes')->nullable()->comment('메모');
            $table->integer('priority')->default(0)->comment('우선순위 (0: 낮음, 1: 보통, 2: 높음)');
            
            // 알림 설정
            $table->boolean('notify_price_drop')->default(false)->comment('가격 하락 알림');
            $table->boolean('notify_back_in_stock')->default(false)->comment('재입고 알림');
            
            $table->timestamps();
            
            // 인덱스
            $table->index(['user_id', 'created_at']);
            $table->index('product_id');
            $table->index(['user_id', 'priority']);
            
            // 외래 키
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('shop_products')->onDelete('cascade');
            
            // 유니크 제약 (같은 사용자가 같은 상품을 중복 위시리스트 추가 방지)
            $table->unique(['user_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_wishlists');
    }
};