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
        // 상품 조회 기록
        Schema::create('product_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained('shop_products')->onDelete('cascade');
            $table->string('session_id')->nullable(); // 비로그인 사용자
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->integer('view_duration')->nullable(); // 페이지 머무른 시간 (초)
            $table->timestamps();

            $table->index(['user_id', 'product_id']);
            $table->index(['session_id', 'product_id']);
            $table->index(['product_id', 'created_at']);
        });

        // 상품 연관 관계 (함께 구매된 상품)
        Schema::create('product_associations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_a_id')->constrained('shop_products')->onDelete('cascade');
            $table->foreignId('product_b_id')->constrained('shop_products')->onDelete('cascade');
            $table->integer('association_count')->default(1); // 함께 구매된 횟수
            $table->decimal('association_score', 5, 4)->default(0); // 연관도 점수 (0~1)
            $table->enum('association_type', ['bought_together', 'viewed_together', 'similar']);
            $table->timestamps();

            $table->unique(['product_a_id', 'product_b_id', 'association_type'], 'prod_assoc_unique');
            $table->index(['product_a_id', 'association_score']);
            $table->index(['association_type', 'association_score']);
        });

        // 사용자별 상품 평점 및 선호도
        Schema::create('user_product_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained('shop_products')->onDelete('cascade');
            $table->decimal('preference_score', 5, 4)->default(0); // 선호도 점수 (0~1)
            $table->integer('view_count')->default(0);
            $table->integer('cart_add_count')->default(0);
            $table->integer('purchase_count')->default(0);
            $table->boolean('is_wishlisted')->default(false);
            $table->boolean('is_reviewed')->default(false);
            $table->decimal('review_rating', 3, 2)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'product_id']);
            $table->index(['user_id', 'preference_score']);
            $table->index(['product_id', 'preference_score']);
        });

        // 추천 규칙 설정
        Schema::create('recommendation_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // 'collaborative', 'content_based', 'hybrid', 'trending', 'seasonal'
            $table->text('description')->nullable();
            $table->json('parameters'); // 알고리즘 파라미터
            $table->decimal('weight', 3, 2)->default(1.0); // 추천 가중치
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0); // 우선순위
            $table->timestamps();

            $table->index(['type', 'is_active']);
            $table->index(['priority', 'is_active']);
        });

        // 개인화 추천 결과 캐시
        Schema::create('user_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained('shop_products')->onDelete('cascade');
            $table->string('recommendation_type'); // 'for_you', 'similar', 'trending', 'category_based'
            $table->decimal('score', 8, 6); // 추천 점수
            $table->text('reason')->nullable(); // 추천 이유
            $table->json('metadata')->nullable(); // 추가 메타데이터
            $table->datetime('expires_at'); // 캐시 만료 시간
            $table->timestamps();

            $table->unique(['user_id', 'product_id', 'recommendation_type'], 'user_rec_unique');
            $table->index(['user_id', 'recommendation_type', 'score']);
            $table->index(['expires_at']);
        });

        // 카테고리별 인기 상품 캐시
        Schema::create('category_trending_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('shop_categories')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('shop_products')->onDelete('cascade');
            $table->integer('rank');
            $table->decimal('trend_score', 8, 4);
            $table->string('period'); // 'daily', 'weekly', 'monthly'
            $table->date('period_date');
            $table->timestamps();

            $table->unique(['category_id', 'product_id', 'period', 'period_date'], 'cat_trend_unique');
            $table->index(['category_id', 'period', 'rank']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_trending_products');
        Schema::dropIfExists('user_recommendations');
        Schema::dropIfExists('recommendation_rules');
        Schema::dropIfExists('user_product_preferences');
        Schema::dropIfExists('product_associations');
        Schema::dropIfExists('product_views');
    }
};