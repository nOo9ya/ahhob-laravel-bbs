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
        Schema::create('user_social_accounts', function (Blueprint $table) {
            $table->id()->comment('소셜 계정 고유 ID');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('연동된 사용자 ID (users.id)');
            $table->string('provider', 50)->comment('소셜 로그인 제공자 (google, kakao, naver, apple)');
            $table->string('provider_id')->comment('제공자별 사용자 고유 ID');
            $table->string('profile_url')->nullable()->comment('소셜 프로필 URL');
            $table->string('photo_url')->nullable()->comment('소셜 프로필 사진 URL');
            $table->string('display_name', 150)->nullable()->comment('소셜 프로필 표시 이름');
            $table->text('description')->nullable()->comment('소셜 프로필 설명');
            $table->timestamps();
            
            // 제공자별 고유 ID는 중복 불가
            $table->unique(['provider', 'provider_id']);
        });
        
        DB::statement("ALTER TABLE `user_social_accounts` comment '사용자 소셜 계정 연동 정보'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_social_accounts');
    }
};
