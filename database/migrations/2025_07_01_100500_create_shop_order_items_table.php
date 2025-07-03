<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | 쇼핑몰 주문 상품 테이블 생성 (Shop Order Items)
    |--------------------------------------------------------------------------
    */
    
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shop_order_items', function (Blueprint $table) {
            $table->id();
            
            // 주문 관계
            $table->unsignedBigInteger('order_id')->comment('주문 ID');
            $table->unsignedBigInteger('product_id')->comment('상품 ID');
            
            // 주문 당시 상품 정보 (스냅샷)
            $table->string('product_name', 200)->comment('상품명');
            $table->string('product_sku', 100)->comment('상품 SKU');
            $table->string('product_image')->nullable()->comment('상품 이미지');
            $table->json('product_options')->nullable()->comment('선택된 옵션');
            
            // 가격 및 수량
            $table->integer('quantity')->comment('주문 수량');
            $table->decimal('unit_price', 10, 2)->comment('단가');
            $table->decimal('total_price', 10, 2)->comment('총 가격');
            
            // 상품별 상태 (전체 주문과 별개로 상품별 처리 상태)
            $table->enum('status', [
                'pending',      // 처리 대기
                'processing',   // 처리 중
                'shipped',      // 배송 중
                'delivered',    // 배송 완료
                'cancelled',    // 취소
                'returned',     // 반품
                'exchanged'     // 교환
            ])->default('pending')->comment('상품별 상태');
            
            // 리뷰 관련
            $table->boolean('review_submitted')->default(false)->comment('리뷰 작성 여부');
            $table->timestamp('review_deadline')->nullable()->comment('리뷰 작성 기한');
            
            $table->timestamps();
            
            // 인덱스
            $table->index(['order_id', 'created_at']);
            $table->index('product_id');
            $table->index(['status', 'created_at']);
            $table->index('review_submitted');
            
            // 외래 키
            $table->foreign('order_id')->references('id')->on('shop_orders')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('shop_products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_order_items');
    }
};