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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            
            // 할인 유형 및 값
            $table->enum('type', ['fixed', 'percentage']); // 고정액, 비율
            $table->decimal('value', 10, 2); // 할인 값
            $table->decimal('min_order_amount', 10, 2)->nullable(); // 최소 주문 금액
            $table->decimal('max_discount_amount', 10, 2)->nullable(); // 최대 할인 금액
            
            // 사용 제한
            $table->integer('usage_limit')->nullable(); // 전체 사용 제한
            $table->integer('usage_limit_per_user')->nullable(); // 사용자당 사용 제한
            $table->integer('used_count')->default(0); // 사용된 횟수
            
            // 적용 대상
            $table->json('applicable_products')->nullable(); // 적용 가능 상품 ID 배열
            $table->json('applicable_categories')->nullable(); // 적용 가능 카테고리 ID 배열
            $table->json('excluded_products')->nullable(); // 제외 상품 ID 배열
            $table->json('excluded_categories')->nullable(); // 제외 카테고리 ID 배열
            
            // 고급 조건
            $table->enum('user_type', ['all', 'new', 'existing'])->default('all'); // 사용자 유형
            $table->integer('user_level_min')->nullable(); // 최소 사용자 레벨
            $table->boolean('first_order_only')->default(false); // 첫 주문만
            $table->json('user_tags')->nullable(); // 사용자 태그 조건
            
            // 기간 설정
            $table->datetime('starts_at')->nullable();
            $table->datetime('expires_at')->nullable();
            
            // 상태
            $table->boolean('is_active')->default(true);
            $table->boolean('is_public')->default(true); // 공개 여부
            
            // 생성자 정보
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
            
            // 인덱스
            $table->index(['code', 'is_active']);
            $table->index(['starts_at', 'expires_at']);
            $table->index(['is_active', 'is_public']);
        });

        // 쿠폰 사용 내역 테이블
        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->constrained('shop_orders')->onDelete('cascade');
            $table->decimal('discount_amount', 10, 2);
            $table->decimal('order_amount', 10, 2);
            $table->timestamps();
            
            $table->index(['coupon_id', 'user_id']);
            $table->index(['user_id', 'created_at']);
        });

        // 자동 쿠폰 발급 규칙 테이블
        Schema::create('coupon_auto_issue_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->onDelete('cascade');
            $table->enum('trigger_type', [
                'user_register', 'first_order', 'order_amount', 
                'birthday', 'review_written', 'product_purchase'
            ]);
            $table->json('trigger_conditions')->nullable(); // 트리거 조건
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 사용자별 쿠폰 보유 테이블
        Schema::create('user_coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('coupon_id')->constrained()->onDelete('cascade');
            $table->datetime('issued_at');
            $table->datetime('expires_at')->nullable();
            $table->boolean('is_used')->default(false);
            $table->datetime('used_at')->nullable();
            $table->foreignId('used_order_id')->nullable()->constrained('shop_orders')->onDelete('set null');
            $table->timestamps();
            
            $table->index(['user_id', 'is_used']);
            $table->index(['coupon_id', 'is_used']);
            $table->index(['expires_at', 'is_used']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_coupons');
        Schema::dropIfExists('coupon_auto_issue_rules');
        Schema::dropIfExists('coupon_usages');
        Schema::dropIfExists('coupons');
    }
};