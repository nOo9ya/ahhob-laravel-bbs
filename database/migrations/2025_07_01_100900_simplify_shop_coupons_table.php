<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | 쿠폰 테이블 단순화
    |--------------------------------------------------------------------------
    */
    
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shop_coupons', function (Blueprint $table) {
            // 복잡한 배열 필드들 제거
            $table->dropColumn([
                'applicable_categories',
                'applicable_products', 
                'excluded_categories',
                'excluded_products'
            ]);
            
            // 단순한 필드명으로 변경
            $table->renameColumn('minimum_amount', 'minimum_order_amount');
            $table->renameColumn('usage_limit', 'maximum_uses');
            $table->renameColumn('usage_limit_per_user', 'maximum_uses_per_user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shop_coupons', function (Blueprint $table) {
            // 필드명 되돌리기
            $table->renameColumn('minimum_order_amount', 'minimum_amount');
            $table->renameColumn('maximum_uses', 'usage_limit');
            $table->renameColumn('maximum_uses_per_user', 'usage_limit_per_user');
            
            // 배열 필드들 다시 추가
            $table->json('applicable_categories')->nullable()->after('valid_until');
            $table->json('applicable_products')->nullable()->after('applicable_categories');
            $table->json('excluded_categories')->nullable()->after('applicable_products');
            $table->json('excluded_products')->nullable()->after('excluded_categories');
        });
    }
};