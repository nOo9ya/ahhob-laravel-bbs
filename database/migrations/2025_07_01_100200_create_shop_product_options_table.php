<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | 쇼핑몰 상품 옵션 테이블 생성 (Shop Product Options)
    |--------------------------------------------------------------------------
    */
    
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shop_product_options', function (Blueprint $table) {
            $table->id();
            
            // 상품 관계
            $table->unsignedBigInteger('product_id')->comment('상품 ID');
            
            // 옵션 정보
            $table->string('name', 100)->comment('옵션명 (예: 색상, 사이즈)');
            $table->string('value', 100)->comment('옵션값 (예: 빨강, XL)');
            $table->string('type', 50)->default('text')->comment('옵션 타입'); // text, color, image
            
            // 가격 조정
            $table->decimal('price_adjustment', 8, 2)->default(0)->comment('가격 조정 (+/-)');
            $table->enum('price_type', ['fixed', 'percentage'])->default('fixed')->comment('가격 타입');
            
            // 재고 관리 (옵션별 재고)
            $table->integer('stock_quantity')->nullable()->comment('옵션별 재고 수량');
            $table->string('sku_suffix', 50)->nullable()->comment('SKU 접미사');
            
            // 표시 설정
            $table->integer('sort_order')->default(0)->comment('정렬 순서');
            $table->boolean('is_active')->default(true)->comment('활성화 상태');
            
            // 추가 정보
            $table->string('image')->nullable()->comment('옵션 이미지');
            $table->text('description')->nullable()->comment('옵션 설명');
            
            $table->timestamps();
            
            // 인덱스
            $table->index(['product_id', 'sort_order']);
            $table->index(['product_id', 'is_active']);
            $table->index(['name', 'value']);
            
            // 외래 키
            $table->foreign('product_id')->references('id')->on('shop_products')->onDelete('cascade');
            
            // 유니크 제약 (같은 상품에서 동일한 옵션명-값 조합 방지)
            $table->unique(['product_id', 'name', 'value']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_product_options');
    }
};