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
        Schema::create('boards', function (Blueprint $table) {
            $table->id()->comment('게시판 고유 ID');
            $table->foreignId('board_group_id')->constrained('board_groups')->onDelete('cascade')->comment('게시판 그룹 ID');
            $table->string('name', 100)->comment('게시판 이름');
            $table->string('slug', 100)->unique()->comment('URL용 슬러그');
            $table->text('description')->nullable()->comment('게시판 설명');
            $table->string('list_template', 50)->default('default')->comment('목록 템플릿');
            $table->string('view_template', 50)->default('default')->comment('상세 템플릿');
            $table->string('write_template', 50)->default('default')->comment('작성 템플릿');
            
            // 권한 설정
            $table->enum('read_permission', ['all', 'member', 'admin'])->default('all')->comment('읽기 권한');
            $table->enum('write_permission', ['all', 'member', 'admin'])->default('member')->comment('쓰기 권한');
            $table->enum('comment_permission', ['all', 'member', 'admin'])->default('member')->comment('댓글 권한');
            
            // 기능 설정
            $table->boolean('use_comment')->default(true)->comment('댓글 사용');
            $table->boolean('use_attachment')->default(true)->comment('첨부파일 사용');
            $table->boolean('use_editor')->default(true)->comment('에디터 사용');
            $table->boolean('use_like')->default(true)->comment('좋아요 사용');
            $table->boolean('use_secret')->default(false)->comment('비밀글 사용');
            $table->boolean('use_notice')->default(true)->comment('공지사항 기능');
            
            // 제한 설정
            $table->integer('posts_per_page')->default(20)->comment('페이지당 게시글 수');
            $table->integer('max_attachment_size')->default(10240)->comment('첨부파일 최대 크기(KB)');
            $table->integer('max_attachment_count')->default(5)->comment('첨부파일 최대 개수');
            
            // 포인트 설정
            $table->integer('write_point')->default(0)->comment('글 작성시 포인트');
            $table->integer('comment_point')->default(0)->comment('댓글 작성시 포인트');
            $table->integer('read_point')->default(0)->comment('글 읽기시 포인트');
            
            // 기타
            $table->integer('sort_order')->default(0)->comment('정렬 순서');
            $table->boolean('is_active')->default(true)->comment('활성화 여부');
            $table->integer('post_count')->default(0)->comment('게시글 수');
            $table->timestamps();
            
            // 인덱스
            $table->index(['board_group_id', 'sort_order']);
            $table->index('is_active');
            $table->index('post_count');
        });
        
        DB::statement("ALTER TABLE `boards` comment '게시판'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boards');
    }
};
