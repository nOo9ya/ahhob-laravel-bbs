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
        Schema::create('login_histories', function (Blueprint $table) {
            $table->id();
            $table->morphs('authenticatable'); // user_id/admin_id + type
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->enum('status', ['success', 'failed', 'blocked'])->default('success');
            $table->string('failure_reason')->nullable();
            $table->string('location')->nullable();
            $table->json('device_info')->nullable();
            $table->timestamp('attempted_at');
            $table->timestamps();
            
            // 인덱스
            $table->index(['authenticatable_type', 'authenticatable_id']);
            $table->index(['ip_address', 'attempted_at']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_histories');
    }
};