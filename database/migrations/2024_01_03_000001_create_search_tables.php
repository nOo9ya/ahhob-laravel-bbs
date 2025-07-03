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
        // 검색 로그
        Schema::create('search_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('query');
            $table->string('type')->default('general'); // board, shop, general
            $table->json('filters')->nullable();
            $table->integer('results_count')->default(0);
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            
            $table->index(['query', 'type']);
            $table->index('user_id');
            $table->index('created_at');
        });

        // 인기 검색어
        Schema::create('popular_keywords', function (Blueprint $table) {
            $table->id();
            $table->string('keyword');
            $table->string('type')->default('general');
            $table->integer('search_count')->default(1);
            $table->date('date');
            $table->timestamps();
            
            $table->unique(['keyword', 'type', 'date']);
            $table->index(['type', 'search_count']);
        });

        // 검색 자동완성
        Schema::create('search_suggestions', function (Blueprint $table) {
            $table->id();
            $table->string('keyword');
            $table->string('type')->default('general');
            $table->integer('frequency')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['keyword', 'type']);
            $table->index(['type', 'frequency']);
        });

        // 검색 필터
        Schema::create('search_filters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // board, shop
            $table->string('field');
            $table->enum('filter_type', ['select', 'range', 'date', 'boolean']);
            $table->json('options')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['type', 'is_active', 'sort_order']);
        });

        // 상품 검색 인덱스
        Schema::create('product_search_index', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('shop_products')->onDelete('cascade');
            $table->text('search_content'); // 제목, 설명, 태그 등 통합
            $table->json('attributes')->nullable(); // 브랜드, 색상 등
            $table->decimal('price', 10, 2);
            $table->boolean('is_available')->default(true);
            $table->timestamp('last_updated');
            
            $table->unique('product_id');
            $table->fullText('search_content');
            $table->index(['is_available', 'price']);
        });

        // 검색 결과 클릭
        Schema::create('search_result_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('search_log_id')->constrained()->onDelete('cascade');
            $table->morphs('clicked_item'); // board_post, shop_product 등
            $table->integer('position'); // 검색 결과에서의 위치
            $table->timestamps();
            
            $table->index(['clicked_item_type', 'clicked_item_id']);
            $table->index('search_log_id');
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
        Schema::dropIfExists('search_logs');
    }
};