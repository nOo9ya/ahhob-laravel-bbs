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
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->morphs('attachable'); // board_post, shop_product 등
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('file_path');
            $table->string('mime_type');
            $table->bigInteger('file_size');
            $table->string('file_hash', 64)->nullable(); // SHA256 hash for deduplication
            $table->json('metadata')->nullable(); // 이미지 크기, 썸네일 정보 등
            $table->integer('download_count')->default(0);
            $table->timestamps();
            
            // 인덱스
            $table->index(['attachable_type', 'attachable_id']);
            $table->index('file_hash');
            $table->index('mime_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};