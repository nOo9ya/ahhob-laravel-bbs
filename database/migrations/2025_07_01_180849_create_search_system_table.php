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
        // search_logs 테이블은 이미 다른 마이그레이션에서 생성됨

        // 인기 검색어
        Schema::create('popular_keywords', function (Blueprint $table) {
            $table->id();
            $table->string('keyword')->unique();
            $table->string('normalized_keyword');
            $table->integer('search_count')->default(1);
            $table->integer('daily_count')->default(0);
            $table->integer('weekly_count')->default(0);
            $table->integer('monthly_count')->default(0);
            $table->date('last_searched_date');
            $table->timestamps();

            $table->index(['search_count', 'last_searched_date']);
            $table->index(['daily_count', 'last_searched_date']);
        });

        // 자동완성 및 추천 키워드
        Schema::create('search_suggestions', function (Blueprint $table) {
            $table->id();
            $table->string('keyword');
            $table->string('suggestion');
            $table->enum('type', ['autocomplete', 'correction', 'related']); // 자동완성, 오타교정, 연관어
            $table->integer('frequency')->default(1); // 빈도수
            $table->decimal('relevance_score', 5, 4)->default(1.0); // 관련도 점수
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['keyword', 'type']);
            $table->index(['suggestion', 'relevance_score']);
        });

        // 검색 필터 설정
        Schema::create('search_filters', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // 필터명 (price, category, brand, rating 등)
            $table->string('label'); // 표시명
            $table->enum('type', ['range', 'select', 'checkbox', 'radio']); // 필터 타입
            $table->json('options')->nullable(); // 필터 옵션들
            $table->json('default_value')->nullable(); // 기본값
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['sort_order', 'is_active']);
        });

        // 상품 검색 인덱스 (Elasticsearch 대신 DB 기반)
        Schema::create('product_search_index', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('shop_products')->onDelete('cascade');
            $table->text('searchable_content'); // 검색 가능한 모든 텍스트 통합
            $table->string('category_path')->nullable(); // 카테고리 경로
            $table->json('tags')->nullable(); // 검색 태그
            $table->decimal('price', 10, 2);
            $table->integer('stock_quantity');
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->integer('sales_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // 전문검색 인덱스
            $table->fullText(['searchable_content']);
            $table->index(['price', 'is_active']);
            $table->index(['average_rating', 'is_active']);
            $table->index(['sales_count', 'is_active']);
        });

        // 검색 결과 클릭 추적
        Schema::create('search_result_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('search_log_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained('shop_products')->onDelete('cascade');
            $table->integer('position'); // 검색 결과에서의 위치
            $table->string('click_type')->default('view'); // view, cart, purchase
            $table->timestamps();

            $table->index(['search_log_id', 'position']);
            $table->index(['product_id', 'click_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('search_result_clicks');
        Schema::dropIfExists('product_search_index');
        Schema::dropIfExists('search_filters');
        Schema::dropIfExists('search_suggestions');
        Schema::dropIfExists('popular_keywords');
        // search_logs는 다른 마이그레이션에서 관리됨
    }
};