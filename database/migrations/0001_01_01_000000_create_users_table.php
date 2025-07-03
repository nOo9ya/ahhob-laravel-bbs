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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            
            // 기본 필드
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            
            // 확장 필드 (modify_users_table_for_ahhob)
            $table->string('username', 50)->unique()->nullable();
            $table->string('nickname', 50)->nullable();
            $table->string('phone_number', 20)->nullable();
            $table->string('profile_image_path')->nullable();
            $table->text('bio')->nullable();
            $table->integer('level')->default(1);
            $table->enum('status', ['active', 'inactive', 'suspended', 'banned'])->default('active');
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            
            // 포인트 관련 필드 (add_points_to_users_table)
            $table->integer('points')->default(0);
            $table->integer('total_earned_points')->default(0);
            $table->integer('total_used_points')->default(0);
            $table->timestamp('last_point_earned_at')->nullable();
            
            // 프로필 필드 (add_profile_fields_to_users_table)
            $table->string('website')->nullable();
            $table->string('location')->nullable();
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['male', 'female', 'other', 'prefer_not_to_say'])->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // 인덱스
            $table->index(['status', 'level']);
            $table->index('last_login_at');
            $table->index('points');
        });
        
        // Password reset tokens
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // Sessions
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};