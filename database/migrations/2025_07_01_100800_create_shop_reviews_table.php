<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | 쇼핑몰 상품 리뷰 테이블 생성 (Shop Product Reviews)
    |--------------------------------------------------------------------------
    */
    
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shop_reviews', function (Blueprint $table) {
            $table->id();
            
            // 관계
            $table->unsignedBigInteger('product_id')->comment('상품 ID');
            $table->unsignedBigInteger('user_id')->comment('작성자 ID');
            $table->unsignedBigInteger('order_item_id')->nullable()->comment('주문 상품 ID (구매 인증)');
            
            // 리뷰 내용
            $table->string('title', 200)->comment('리뷰 제목');
            $table->text('content')->comment('리뷰 내용');
            $table->tinyInteger('rating')->comment('평점 (1-5)');
            
            // 이미지
            $table->json('images')->nullable()->comment('리뷰 이미지들');
            
            // 구매자 인증
            $table->boolean('is_verified_purchase')->default(false)->comment('구매 인증 여부');
            
            // 상태
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->comment('승인 상태');
            $table->text('admin_notes')->nullable()->comment('관리자 메모');
            
            // 통계
            $table->unsignedInteger('helpful_count')->default(0)->comment('도움됨 카운트');
            $table->unsignedInteger('not_helpful_count')->default(0)->comment('도움안됨 카운트');
            
            // 관리
            $table->unsignedBigInteger('approved_by')->nullable()->comment('승인자');
            $table->timestamp('approved_at')->nullable()->comment('승인 시간');
            
            $table->timestamps();
            
            // 인덱스
            $table->index(['product_id', 'status', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['rating', 'status']);
            $table->index('order_item_id');
            $table->index('is_verified_purchase');
            
            // 외래 키
            $table->foreign('product_id')->references('id')->on('shop_products')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('order_item_id')->references('id')->on('shop_order_items')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            
            // 유니크 제약 (같은 주문 상품에 대해 한 번만 리뷰 작성 가능)
            $table->unique(['order_item_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_reviews');
    }
};