<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | 쇼핑몰 장바구니 테이블 생성 (Shop Carts)
    |--------------------------------------------------------------------------
    */
    
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shop_carts', function (Blueprint $table) {
            $table->id();
            
            // 사용자 정보
            $table->unsignedBigInteger('user_id')->nullable()->comment('사용자 ID (비로그인시 null)');
            $table->string('session_id', 100)->nullable()->comment('세션 ID (비로그인 사용자용)');
            
            // 상품 정보
            $table->unsignedBigInteger('product_id')->comment('상품 ID');
            $table->json('product_options')->nullable()->comment('선택된 상품 옵션');
            
            // 수량 및 가격
            $table->integer('quantity')->default(1)->comment('수량');
            $table->decimal('unit_price', 10, 2)->comment('단가 (옵션 포함)');
            $table->decimal('total_price', 10, 2)->comment('총 가격');
            
            // 상품 스냅샷 (상품 정보가 변경되어도 장바구니는 유지)
            $table->string('product_name', 200)->comment('상품명 스냅샷');
            $table->string('product_image')->nullable()->comment('상품 이미지 스냅샷');
            $table->string('product_sku', 100)->comment('상품 SKU 스냅샷');
            
            $table->timestamps();
            
            // 인덱스
            $table->index(['user_id', 'created_at']);
            $table->index(['session_id', 'created_at']);
            $table->index('product_id');
            
            // 외래 키
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('shop_products')->onDelete('cascade');
            
            // 유니크 제약 (같은 사용자가 같은 상품+옵션 조합을 중복 추가 방지)
            $table->unique(['user_id', 'product_id', 'product_options'], 'unique_user_cart_item');
            $table->unique(['session_id', 'product_id', 'product_options'], 'unique_session_cart_item');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_carts');
    }
};