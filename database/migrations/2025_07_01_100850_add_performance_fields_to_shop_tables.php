<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | 쇼핑몰 성능 향상을 위한 필드 추가
    |--------------------------------------------------------------------------
    */
    
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 상품 테이블에 인덱스만 추가 (필드는 이미 존재)
        Schema::table('shop_products', function (Blueprint $table) {
            // 인덱스 추가 (컬럼은 이미 존재하므로 인덱스만 추가)
            $table->index('average_rating');
            $table->index('sales_count');
        });
        
        // 카테고리 테이블에 인덱스만 추가 (필드는 이미 존재)
        Schema::table('shop_categories', function (Blueprint $table) {
            // 인덱스 추가 (컬럼은 이미 존재하므로 인덱스만 추가)
            $table->index('products_count');
        });
        
        // 리뷰 테이블에서 not_helpful_count 필드 제거
        Schema::table('shop_reviews', function (Blueprint $table) {
            $table->dropColumn('not_helpful_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shop_products', function (Blueprint $table) {
            $table->dropIndex(['average_rating']);
            $table->dropIndex(['sales_count']);
            // 컬럼은 원래부터 있던 것이므로 삭제하지 않음
        });
        
        Schema::table('shop_categories', function (Blueprint $table) {
            $table->dropIndex(['products_count']);  
            // 컬럼은 원래부터 있던 것이므로 삭제하지 않음
        });
        
        Schema::table('shop_reviews', function (Blueprint $table) {
            $table->integer('not_helpful_count')->default(0)->after('helpful_count');
        });
    }
};