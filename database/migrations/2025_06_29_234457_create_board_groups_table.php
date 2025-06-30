<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('board_groups', function (Blueprint $table) {
            $table->id()->comment('게시판 그룹 고유 ID');
            $table->string('name', 100)->comment('그룹명');
            $table->string('slug', 100)->unique()->comment('URL용 슬러그');
            $table->text('description')->nullable()->comment('그룹 설명');
            $table->integer('sort_order')->default(0)->comment('정렬 순서');
            $table->boolean('is_active')->default(true)->comment('활성화 여부');
            $table->timestamps();
            
            // 인덱스
            $table->index('sort_order');
            $table->index('is_active');
        });
        
        DB::statement("ALTER TABLE `board_groups` comment '게시판 그룹'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('board_groups');
    }
};
