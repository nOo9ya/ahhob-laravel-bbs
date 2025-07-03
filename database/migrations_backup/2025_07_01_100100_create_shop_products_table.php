<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | 쇼핑몰 상품 테이블 생성 (Shop Products)
    |--------------------------------------------------------------------------
    */
    
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shop_products', function (Blueprint $table) {
            $table->id();
            
            // 기본 정보
            $table->string('name', 200)->comment('상품명');
            $table->string('slug', 200)->unique()->comment('URL 슬러그');
            $table->text('short_description')->nullable()->comment('짧은 설명');
            $table->longText('description')->nullable()->comment('상품 상세 설명');
            $table->string('sku', 100)->unique()->comment('상품 코드');
            
            // 카테고리 관계
            $table->unsignedBigInteger('category_id')->comment('카테고리 ID');
            
            // 가격 정보
            $table->decimal('price', 10, 2)->comment('판매가');
            $table->decimal('compare_price', 10, 2)->nullable()->comment('정가(할인 전 가격)');
            $table->decimal('cost_price', 10, 2)->nullable()->comment('원가');
            
            // 재고 관리
            $table->integer('stock_quantity')->default(0)->comment('재고 수량');
            $table->integer('min_stock_quantity')->default(0)->comment('최소 재고 수량');
            $table->boolean('track_stock')->default(true)->comment('재고 추적 여부');
            $table->enum('stock_status', ['in_stock', 'out_of_stock', 'on_backorder'])->default('in_stock')->comment('재고 상태');
            
            // 이미지
            $table->string('featured_image')->nullable()->comment('대표 이미지');
            $table->json('gallery_images')->nullable()->comment('갤러리 이미지들');
            
            // 배송 정보
            $table->decimal('weight', 8, 2)->nullable()->comment('무게(kg)');
            $table->json('dimensions')->nullable()->comment('크기 정보 (가로, 세로, 높이)');
            $table->boolean('requires_shipping')->default(true)->comment('배송 필요 여부');
            $table->decimal('shipping_cost', 8, 2)->default(0)->comment('배송비');
            
            // 상태 관리
            $table->enum('status', ['draft', 'active', 'inactive', 'archived'])->default('draft')->comment('상품 상태');
            $table->enum('visibility', ['visible', 'catalog', 'search', 'hidden'])->default('visible')->comment('노출 설정');
            $table->boolean('is_featured')->default(false)->comment('추천 상품');
            $table->boolean('is_digital')->default(false)->comment('디지털 상품');
            
            // 판매 설정
            $table->integer('min_purchase_quantity')->default(1)->comment('최소 구매 수량');
            $table->integer('max_purchase_quantity')->nullable()->comment('최대 구매 수량');
            $table->boolean('allow_backorder')->default(false)->comment('품절 시 주문 허용');
            
            // SEO
            $table->string('meta_title')->nullable()->comment('SEO 제목');
            $table->text('meta_description')->nullable()->comment('SEO 설명');
            $table->string('meta_keywords')->nullable()->comment('SEO 키워드');
            
            // 통계
            $table->unsignedInteger('views_count')->default(0)->comment('조회수');
            $table->unsignedInteger('sales_count')->default(0)->comment('판매량');
            $table->decimal('average_rating', 3, 2)->default(0)->comment('평균 평점');
            $table->unsignedInteger('reviews_count')->default(0)->comment('리뷰 수');
            
            // 관리
            $table->unsignedBigInteger('created_by')->nullable()->comment('등록자');
            $table->unsignedBigInteger('updated_by')->nullable()->comment('수정자');
            
            $table->timestamps();
            $table->timestamp('published_at')->nullable()->comment('출시일');
            
            // 인덱스
            $table->index(['category_id', 'status']);
            $table->index(['status', 'visibility']);
            $table->index(['is_featured', 'status']);
            $table->index(['stock_status', 'status']);
            $table->index('published_at');
            $table->index('created_by');
            
            // 외래 키
            $table->foreign('category_id')->references('id')->on('shop_categories')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_products');
    }
};