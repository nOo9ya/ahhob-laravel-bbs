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
        Schema::create('board_managers', function (Blueprint $table) {
            $table->id()->comment('게시판 관리자 고유 ID');
            $table->foreignId('board_id')->constrained('boards')->onDelete('cascade')->comment('게시판 ID');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('관리자 사용자 ID');
            
            // 권한 설정
            $table->boolean('can_edit_posts')->default(true)->comment('게시글 수정 권한');
            $table->boolean('can_delete_posts')->default(true)->comment('게시글 삭제 권한 (soft delete)');
            $table->boolean('can_move_posts')->default(true)->comment('게시글 이동 권한');
            $table->boolean('can_manage_comments')->default(true)->comment('댓글 관리 권한');
            $table->boolean('can_manage_attachments')->default(true)->comment('첨부파일 관리 권한');
            $table->boolean('can_set_notice')->default(true)->comment('공지사항 설정 권한');
            $table->boolean('can_manage_secret')->default(true)->comment('비밀글 관리 권한');
            
            // 관리 정보
            $table->text('memo')->nullable()->comment('관리자 메모');
            $table->boolean('is_active')->default(true)->comment('활성화 여부');
            $table->timestamp('assigned_at')->useCurrent()->comment('지정일시');
            $table->timestamps();
            
            // 유니크 제약 조건 (한 게시판에 같은 사용자는 한 번만)
            $table->unique(['board_id', 'user_id']);
            
            // 인덱스
            $table->index(['board_id', 'is_active']);
            $table->index(['user_id', 'is_active']);
        });
        
        DB::statement("ALTER TABLE `board_managers` comment '게시판 관리자 권한'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('board_managers');
    }
};
