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
            $table->id()->comment('첨부파일 고유 ID');
            
            // 다형적 관계 (게시글, 댓글, 상품 등 어디든 첨부 가능)
            $table->morphs('attachable', 'attachments_attachable_index');
            
            // 업로드한 사용자 정보
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null')->comment('업로드한 사용자 ID');
            
            // 파일 기본 정보
            $table->string('original_name', 255)->comment('원본 파일명');
            $table->string('stored_name', 255)->comment('저장된 파일명 (UUID 기반)');
            $table->string('file_path', 500)->comment('파일 저장 경로');
            $table->string('file_extension', 10)->comment('파일 확장자');
            $table->string('mime_type', 100)->comment('MIME 타입');
            $table->bigInteger('file_size')->comment('파일 크기 (bytes)');
            
            // 이미지 관련 정보 (이미지 파일인 경우)
            $table->integer('image_width')->nullable()->comment('이미지 너비 (px)');
            $table->integer('image_height')->nullable()->comment('이미지 높이 (px)');
            $table->boolean('is_image')->default(false)->comment('이미지 파일 여부');
            
            // 썸네일 정보
            $table->string('thumbnail_path', 500)->nullable()->comment('썸네일 파일 경로');
            $table->boolean('has_thumbnail')->default(false)->comment('썸네일 존재 여부');
            
            // 메타데이터
            $table->json('metadata')->nullable()->comment('추가 메타데이터 (EXIF, 태그 등)');
            
            // 보안 및 관리
            $table->string('hash', 64)->comment('파일 해시 (SHA-256)');
            $table->integer('download_count')->default(0)->comment('다운로드 횟수');
            $table->boolean('is_public')->default(true)->comment('공개 파일 여부');
            $table->boolean('is_processed')->default(false)->comment('후처리 완료 여부');
            
            // 업로드 정보
            $table->string('upload_ip', 45)->nullable()->comment('업로드 IP 주소');
            $table->integer('sort_order')->default(0)->comment('첨부파일 정렬 순서');
            
            // 상태 관리
            $table->enum('status', ['uploading', 'processing', 'completed', 'failed', 'deleted'])
                  ->default('uploading')->comment('파일 상태');
            $table->text('error_message')->nullable()->comment('에러 메시지 (실패 시)');
            
            $table->softDeletes()->comment('소프트 삭제');
            $table->timestamps();
            
            // 인덱스 설정
            $table->index(['attachable_type', 'attachable_id', 'status']);
            $table->index(['user_id', 'created_at']);
            $table->index(['is_image', 'status']);
            $table->index(['hash', 'file_size']); // 중복 파일 검출용
            $table->index('sort_order');
            $table->index('download_count');
            $table->index(['is_public', 'status']);
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
