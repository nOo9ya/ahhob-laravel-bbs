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
        Schema::table('users', function (Blueprint $table) {
            // points 컬럼은 이미 존재하므로 추가하지 않음
            $table->integer('total_earned_points')->default(0)->comment('총 획득 포인트')->after('points');
            $table->integer('total_used_points')->default(0)->comment('총 사용 포인트')->after('total_earned_points');
            $table->timestamp('last_point_earned_at')->nullable()->comment('마지막 포인트 획득일시')->after('total_used_points');
            
            // 인덱스 추가
            $table->index('points');
            $table->index('total_earned_points');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['points']);
            $table->dropIndex(['total_earned_points']);
            $table->dropColumn([
                'total_earned_points', 
                'total_used_points',
                'last_point_earned_at'
            ]);
        });
    }
};
