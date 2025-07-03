<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | 쇼핑몰 상품 카테고리 테이블 생성 (Shop Product Categories)
    |--------------------------------------------------------------------------
    */
    
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shop_categories', function (Blueprint $table) {
            $table->id();
            
            // 카테고리 기본 정보
            $table->string('name', 100)->comment('카테고리명');
            $table->string('slug', 100)->unique()->comment('URL 슬러그');
            $table->text('description')->nullable()->comment('카테고리 설명');
            $table->string('image')->nullable()->comment('카테고리 이미지');
            
            // 계층 구조 (Nested Set Model)
            $table->unsignedBigInteger('parent_id')->nullable()->comment('상위 카테고리 ID');
            $table->integer('left')->default(0)->comment('좌측 노드값');
            $table->integer('right')->default(0)->comment('우측 노드값');
            $table->integer('depth')->default(0)->comment('계층 깊이');
            
            // 표시 설정
            $table->integer('sort_order')->default(0)->comment('정렬 순서');
            $table->boolean('is_active')->default(true)->comment('활성화 상태');
            $table->boolean('is_featured')->default(false)->comment('추천 카테고리');
            
            // SEO
            $table->string('meta_title')->nullable()->comment('SEO 제목');
            $table->text('meta_description')->nullable()->comment('SEO 설명');
            $table->string('meta_keywords')->nullable()->comment('SEO 키워드');
            
            // 통계
            $table->unsignedInteger('products_count')->default(0)->comment('상품 수');
            
            $table->timestamps();
            
            // 인덱스
            $table->index(['parent_id', 'sort_order']);
            $table->index(['left', 'right']);
            $table->index(['is_active', 'sort_order']);
            $table->index('is_featured');
            
            // 외래 키
            $table->foreign('parent_id')->references('id')->on('shop_categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_categories');
    }
};