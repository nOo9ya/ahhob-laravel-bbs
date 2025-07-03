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
        // 기본 쿠폰 (단순화 버전)
        Schema::create('shop_coupons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('value', 10, 2); // 할인 금액 또는 퍼센트
            $table->decimal('minimum_order_amount', 10, 2)->default(0); // minimum_amount → minimum_order_amount
            $table->decimal('maximum_discount_amount', 10, 2)->nullable(); // 최대 할인 금액 (퍼센트 쿠폰용)
            $table->integer('maximum_uses')->nullable(); // max_uses → maximum_uses
            $table->integer('used_count')->default(0);
            $table->integer('max_uses_per_user')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            $table->index(['code', 'is_active']);
            $table->index(['is_active', 'starts_at', 'expires_at']);
        });

        // 고급 쿠폰
        Schema::create('advanced_coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('base_coupon_id')->constrained('shop_coupons')->onDelete('cascade');
            $table->json('applicable_categories')->nullable(); // 적용 가능한 카테고리
            $table->json('applicable_products')->nullable(); // 적용 가능한 상품
            $table->json('user_conditions')->nullable(); // 사용자 조건 (level, signup_date 등)
            $table->boolean('auto_apply')->default(false); // 자동 적용 여부
            $table->timestamps();
            
            $table->index('base_coupon_id');
        });

        // 위시리스트
        Schema::create('shop_wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained('shop_products')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['user_id', 'product_id']);
            $table->index(['user_id', 'created_at']);
        });

        // 상품 리뷰 (not_helpful_count 제거된 버전)
        Schema::create('shop_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('shop_products')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_item_id')->nullable()->constrained('shop_order_items')->onDelete('set null');
            $table->integer('rating'); // 1-5
            $table->string('title')->nullable();
            $table->text('content')->nullable();
            $table->json('images')->nullable(); // 리뷰 이미지들
            $table->boolean('is_verified_purchase')->default(false);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->integer('helpful_count')->default(0);
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            
            $table->index(['product_id', 'status', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['rating', 'status']);
        });

        // 리뷰 도움됨
        Schema::create('review_helpfuls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained('shop_reviews')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('is_helpful')->default(true); // true: 도움됨, false: 도움안됨
            $table->timestamps();
            
            $table->unique(['review_id', 'user_id']);
            $table->index(['review_id', 'is_helpful']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_helpfuls');
        Schema::dropIfExists('shop_reviews');
        Schema::dropIfExists('shop_wishlists');
        Schema::dropIfExists('advanced_coupons');
        Schema::dropIfExists('shop_coupons');
    }
};