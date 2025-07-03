<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | 쇼핑몰 쿠폰 테이블 생성 (Shop Coupons)
    |--------------------------------------------------------------------------
    */
    
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shop_coupons', function (Blueprint $table) {
            $table->id();
            
            // 쿠폰 기본 정보
            $table->string('code', 50)->unique()->comment('쿠폰 코드');
            $table->string('name', 200)->comment('쿠폰명');
            $table->text('description')->nullable()->comment('쿠폰 설명');
            
            // 할인 정보
            $table->enum('discount_type', ['fixed', 'percentage'])->comment('할인 타입');
            $table->decimal('discount_value', 10, 2)->comment('할인값');
            $table->decimal('minimum_amount', 10, 2)->nullable()->comment('최소 주문 금액');
            $table->decimal('maximum_discount', 10, 2)->nullable()->comment('최대 할인 금액');
            
            // 사용 제한
            $table->integer('usage_limit')->nullable()->comment('전체 사용 제한');
            $table->integer('usage_limit_per_user')->nullable()->comment('사용자당 사용 제한');
            $table->integer('used_count')->default(0)->comment('사용된 횟수');
            
            // 유효 기간
            $table->timestamp('valid_from')->comment('유효 시작일');
            $table->timestamp('valid_until')->comment('유효 종료일');
            
            // 적용 대상
            $table->json('applicable_categories')->nullable()->comment('적용 가능한 카테고리');
            $table->json('applicable_products')->nullable()->comment('적용 가능한 상품');
            $table->json('excluded_categories')->nullable()->comment('제외할 카테고리');
            $table->json('excluded_products')->nullable()->comment('제외할 상품');
            
            // 상태
            $table->boolean('is_active')->default(true)->comment('활성화 상태');
            $table->boolean('is_public')->default(true)->comment('공개 여부');
            
            // 관리자 정보
            $table->unsignedBigInteger('created_by')->nullable()->comment('생성자');
            
            $table->timestamps();
            
            // 인덱스
            $table->index(['is_active', 'valid_from', 'valid_until']);
            $table->index(['is_public', 'is_active']);
            $table->index('created_by');
            
            // 외래 키
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_coupons');
    }
};