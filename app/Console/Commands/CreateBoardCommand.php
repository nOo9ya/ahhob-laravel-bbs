<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CreateBoardCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'board:create {slug : 게시판 슬러그}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '게시판별 동적 테이블과 모델 생성';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $slug = $this->argument('slug');
        
        if (!$this->validateSlug($slug)) {
            $this->error('유효하지 않은 슬러그입니다. 영문, 숫자, 언더스코어만 사용 가능합니다.');
            return 1;
        }

        $postTableName = "board_{$slug}";
        $commentTableName = "board_{$slug}_comments";

        try {
            // 게시글 테이블 생성
            $this->createPostTable($postTableName);
            $this->info("게시글 테이블 '{$postTableName}' 생성 완료");

            // 댓글 테이블 생성
            $this->createCommentTable($commentTableName, $postTableName);
            $this->info("댓글 테이블 '{$commentTableName}' 생성 완료");

            // 동적 모델 파일 생성
            $this->createPostModel($slug);
            $this->createCommentModel($slug);
            $this->info("모델 파일 생성 완료");

            $this->info("게시판 '{$slug}' 생성이 완료되었습니다!");
            return 0;

        } catch (\Exception $e) {
            $this->error("게시판 생성 중 오류 발생: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * 슬러그 유효성 검사
     */
    private function validateSlug(string $slug): bool
    {
        return preg_match('/^[a-z0-9_]+$/', $slug) && strlen($slug) <= 50;
    }

    /**
     * 게시글 테이블 생성
     */
    private function createPostTable(string $tableName): void
    {
        Schema::create($tableName, function (Blueprint $table) {
            $table->id()->comment('게시글 고유 ID');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null')->comment('작성자 ID');
            
            // 게시글 기본 정보
            $table->string('title', 255)->comment('제목');
            $table->longText('content')->comment('내용');
            $table->string('password', 100)->nullable()->comment('비밀글 비밀번호');
            $table->boolean('is_notice')->default(false)->comment('공지사항 여부');
            $table->boolean('is_secret')->default(false)->comment('비밀글 여부');
            $table->boolean('is_html')->default(true)->comment('HTML 사용 여부');
            
            // 통계 및 상태
            $table->integer('view_count')->default(0)->comment('조회수');
            $table->integer('like_count')->default(0)->comment('좋아요 수');
            $table->integer('comment_count')->default(0)->comment('댓글 수');
            $table->integer('attachment_count')->default(0)->comment('첨부파일 수');
            
            // 작성자 정보 (익명 지원)
            $table->string('author_name', 100)->nullable()->comment('작성자명 (익명일 경우)');
            $table->string('author_email', 100)->nullable()->comment('작성자 이메일 (익명일 경우)');
            $table->string('author_ip', 45)->nullable()->comment('작성자 IP');
            
            // SEO 및 메타데이터
            $table->string('slug', 255)->nullable()->unique()->comment('SEO용 슬러그');
            $table->text('excerpt')->nullable()->comment('요약');
            $table->json('meta_data')->nullable()->comment('추가 메타데이터');
            
            // 관리 정보
            $table->enum('status', ['published', 'draft', 'private', 'deleted'])->default('published')->comment('게시 상태');
            $table->timestamp('published_at')->nullable()->comment('게시일시');
            $table->softDeletes()->comment('소프트 삭제');
            $table->timestamps();
            
            // 인덱스
            $table->index(['status', 'is_notice', 'published_at']);
            $table->index(['user_id', 'status']);
            $table->index(['is_notice', 'published_at']);
            $table->index('view_count');
            $table->index('like_count');
            $table->fullText(['title', 'content']);
        });
    }

    /**
     * 댓글 테이블 생성
     */
    private function createCommentTable(string $commentTableName, string $postTableName): void
    {
        Schema::create($commentTableName, function (Blueprint $table) use ($postTableName, $commentTableName) {
            $table->id()->comment('댓글 고유 ID');
            
            // 게시글 관계
            $table->unsignedBigInteger('post_id')->comment('게시글 ID');
            $table->foreign('post_id')->references('id')->on($postTableName)->onDelete('cascade');
            
            // 작성자 정보
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null')->comment('작성자 ID');
            $table->string('author_name', 100)->nullable()->comment('작성자명 (익명일 경우)');
            $table->string('author_email', 100)->nullable()->comment('작성자 이메일 (익명일 경우)');
            $table->string('author_ip', 45)->nullable()->comment('작성자 IP');
            
            // 댓글 내용
            $table->text('content')->comment('댓글 내용');
            $table->string('password', 100)->nullable()->comment('비밀댓글 비밀번호');
            $table->boolean('is_secret')->default(false)->comment('비밀댓글 여부');
            $table->boolean('is_html')->default(false)->comment('HTML 사용 여부');
            
            // 계층형 댓글 구조
            $table->foreignId('parent_id')->nullable()->comment('부모 댓글 ID');
            $table->integer('depth')->default(0)->comment('댓글 깊이');
            $table->string('path', 500)->nullable()->comment('댓글 경로 (1/2/3)');
            
            // 통계
            $table->integer('like_count')->default(0)->comment('좋아요 수');
            $table->integer('reply_count')->default(0)->comment('답글 수');
            
            // 관리 정보
            $table->enum('status', ['published', 'hidden', 'deleted'])->default('published')->comment('댓글 상태');
            $table->text('admin_memo')->nullable()->comment('관리자 메모');
            $table->softDeletes()->comment('소프트 삭제');
            $table->timestamps();
            
            // 외래키 설정
            $table->foreign('parent_id')->references('id')->on($commentTableName)->onDelete('cascade');
            
            // 인덱스
            $table->index(['post_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['parent_id', 'depth']);
            $table->index('path');
            $table->index('created_at');
        });
    }

    /**
     * 게시글 모델 파일 생성
     */
    private function createPostModel(string $slug): void
    {
        $className = 'Board' . Str::studly($slug);
        $tableName = "board_{$slug}";
        $commentModelClass = 'Board' . Str::studly($slug) . 'Comment';
        
        $modelContent = $this->getPostModelTemplate($className, $tableName, $commentModelClass, $slug);
        $filePath = app_path("Models/Ahhob/Board/Dynamic/{$className}.php");
        
        File::ensureDirectoryExists(dirname($filePath));
        File::put($filePath, $modelContent);
    }

    /**
     * 댓글 모델 파일 생성
     */
    private function createCommentModel(string $slug): void
    {
        $className = 'Board' . Str::studly($slug) . 'Comment';
        $tableName = "board_{$slug}_comments";
        $postModelClass = 'Board' . Str::studly($slug);
        
        $modelContent = $this->getCommentModelTemplate($className, $tableName, $postModelClass, $slug);
        $filePath = app_path("Models/Ahhob/Board/Dynamic/{$className}.php");
        
        File::ensureDirectoryExists(dirname($filePath));
        File::put($filePath, $modelContent);
    }

    /**
     * 게시글 모델 템플릿
     */
    private function getPostModelTemplate(string $className, string $tableName, string $commentModelClass, string $slug): string
    {
        return "<?php

namespace App\\Models\\Ahhob\\Board\\Dynamic;

use App\\Models\\Ahhob\\Board\\BaseBoardPost;
use App\\Models\\User;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;
use Illuminate\\Database\\Eloquent\\Relations\\HasMany;

class {$className} extends BaseBoardPost
{
    /**
     * The table associated with the model.
     */
    protected \$table = '{$tableName}';

    /**
     * 게시글 댓글들
     */
    public function comments(): HasMany
    {
        return \$this->hasMany({$commentModelClass}::class, 'post_id');
    }

    /**
     * URL 접근자
     */
    public function getUrlAttribute(): string
    {
        return route('board.view', ['{$slug}', \$this->id]);
    }

    /**
     * 게시판 설정 정보
     */
    public function getBoardConfig()
    {
        return \\App\\Models\\Ahhob\\Board\\Board::where('slug', '{$slug}')->first();
    }
}";
    }

    /**
     * 댓글 모델 템플릿
     */
    private function getCommentModelTemplate(string $className, string $tableName, string $postModelClass, string $slug): string
    {
        return "<?php

namespace App\\Models\\Ahhob\\Board\\Dynamic;

use App\\Models\\User;
use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;
use Illuminate\\Database\\Eloquent\\Relations\\HasMany;
use Illuminate\\Database\\Eloquent\\SoftDeletes;

class {$className} extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected \$table = '{$tableName}';

    /**
     * The attributes that are mass assignable.
     */
    protected \$fillable = [
        'post_id',
        'user_id',
        'content',
        'password',
        'is_secret',
        'is_html',
        'parent_id',
        'depth',
        'path',
        'author_name',
        'author_email',
        'author_ip',
        'status',
        'admin_memo',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'is_secret' => 'boolean',
            'is_html' => 'boolean',
            'like_count' => 'integer',
            'reply_count' => 'integer',
            'depth' => 'integer',
        ];
    }

    /**
     * 댓글이 속한 게시글
     */
    public function post(): BelongsTo
    {
        return \$this->belongsTo({$postModelClass}::class, 'post_id');
    }

    /**
     * 댓글 작성자
     */
    public function user(): BelongsTo
    {
        return \$this->belongsTo(User::class);
    }

    /**
     * 부모 댓글
     */
    public function parent(): BelongsTo
    {
        return \$this->belongsTo(self::class, 'parent_id');
    }

    /**
     * 자식 댓글들
     */
    public function children(): HasMany
    {
        return \$this->hasMany(self::class, 'parent_id');
    }

    /**
     * 작성자명 표시
     */
    public function getAuthorNameDisplayAttribute(): string
    {
        return \$this->user ? \$this->user->nickname : (\$this->author_name ?? '익명');
    }
}";
    }
}
