<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class DeleteBoardCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'board:delete {slug : 게시판 슬러그}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '게시판별 동적 테이블과 모델 삭제';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $slug = $this->argument('slug');
        $postTableName = "board_{$slug}";
        $commentTableName = "board_{$slug}_comments";

        try {
            // 테이블 존재 확인
            if (!Schema::hasTable($postTableName)) {
                $this->error("게시글 테이블 '{$postTableName}'이 존재하지 않습니다.");
                return 1;
            }

            // 확인 메시지
            if (!$this->confirm("게시판 '{$slug}'와 모든 데이터를 삭제하시겠습니까? 이 작업은 되돌릴 수 없습니다.")) {
                $this->info('삭제 작업이 취소되었습니다.');
                return 0;
            }

            // 댓글 테이블 삭제 (외래키 제약조건 때문에 먼저 삭제)
            if (Schema::hasTable($commentTableName)) {
                Schema::dropIfExists($commentTableName);
                $this->info("댓글 테이블 '{$commentTableName}' 삭제 완료");
            }

            // 게시글 테이블 삭제
            Schema::dropIfExists($postTableName);
            $this->info("게시글 테이블 '{$postTableName}' 삭제 완료");

            // 모델 파일 삭제
            $this->deleteModelFiles($slug);
            $this->info("모델 파일 삭제 완료");

            $this->info("게시판 '{$slug}' 삭제가 완료되었습니다!");
            return 0;

        } catch (\Exception $e) {
            $this->error("게시판 삭제 중 오류 발생: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * 모델 파일들 삭제
     */
    private function deleteModelFiles(string $slug): void
    {
        $postClassName = 'Board' . Str::studly($slug);
        $commentClassName = 'Board' . Str::studly($slug) . 'Comment';
        
        $postModelPath = app_path("Models/Ahhob/Board/Dynamic/{$postClassName}.php");
        $commentModelPath = app_path("Models/Ahhob/Board/Dynamic/{$commentClassName}.php");

        // 게시글 모델 파일 삭제
        if (File::exists($postModelPath)) {
            File::delete($postModelPath);
            $this->info("게시글 모델 파일 삭제: {$postClassName}.php");
        }

        // 댓글 모델 파일 삭제
        if (File::exists($commentModelPath)) {
            File::delete($commentModelPath);
            $this->info("댓글 모델 파일 삭제: {$commentClassName}.php");
        }

        // Dynamic 폴더가 비어있으면 삭제하지 않음 (다른 게시판 모델들이 있을 수 있음)
    }
}
