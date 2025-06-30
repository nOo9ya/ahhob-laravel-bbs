<?php

namespace App\Models;

use App\Enums\UserStatus;
use App\Models\Ahhob\User\UserSocialAccount;
use App\Models\Ahhob\User\LoginHistory;
use App\Models\Ahhob\User\UserActivityLog;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /*
    |--------------------------------------------------------------------------
    | 모델 속성 (Attributes & Properties)
    |--------------------------------------------------------------------------
    */
    // region --- 모델 속성 (Attributes & Properties) ---

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',           // Laravel 기본 - 실명
        'username',       // 추가 - 로그인 아이디
        'nickname',       // 추가 - 닉네임
        'email',          // Laravel 기본
        'password',       // Laravel 기본
        'phone_number',   // 추가 - 휴대폰
        'profile_image_path', // 추가 - 프로필 이미지
        'bio',            // 추가 - 자기소개
        'points',         // 추가 - 포인트
        'level',          // 추가 - 레벨
        'status',         // 추가 - 계정 상태
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
            'last_login_at' => 'datetime',
            'points' => 'integer',
            'level' => 'integer',
        ];
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 관계 (Relationships)
    |--------------------------------------------------------------------------
    */
    // region --- 관계 (Relationships) ---

    /**
     * 사용자의 소셜 계정 연동 정보
     */
    public function socialAccounts(): HasMany
    {
        return $this->hasMany(UserSocialAccount::class);
    }

    /**
     * 사용자의 로그인 기록 (다형적 관계)
     */
    public function loginHistories(): MorphMany
    {
        return $this->morphMany(LoginHistory::class, 'authenticatable');
    }

    /**
     * 사용자의 활동 로그
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(UserActivityLog::class);
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 접근자 & 변경자 (Accessors & Mutators)
    |--------------------------------------------------------------------------
    */
    // region --- 접근자 & 변경자 (Accessors & Mutators) ---

    /**
     * 풀네임 접근자 (Laravel 기본 name 사용)
     */
    public function getFullNameAttribute(): string
    {
        return $this->name ?: $this->nickname;
    }

    /**
     * 프로필 이미지 URL 접근자
     */
    public function getProfileImageUrlAttribute(): string
    {
        if ($this->profile_image_path) {
            return asset('storage/' . $this->profile_image_path);
        }
        
        // 기본 아바타 이미지
        return "https://ui-avatars.com/api/?name=" . urlencode($this->nickname) . "&color=7F9CF5&background=EBF4FF";
    }

    /**
     * 레벨 이름 접근자
     */
    public function getLevelNameAttribute(): string
    {
        return match(true) {
            $this->level >= 90 => '다이아몬드',
            $this->level >= 80 => '플래티넘',
            $this->level >= 70 => '골드',
            $this->level >= 60 => '실버',
            $this->level >= 50 => '브론즈',
            default => '뉴비',
        };
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 공개 메서드 (Public Methods)
    |--------------------------------------------------------------------------
    */
    // region --- 공개 메서드 (Public Methods) ---

    /**
     * 활성 상태 사용자인지 확인
     */
    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    /**
     * 로그인 가능한 상태인지 확인
     */
    public function canLogin(): bool
    {
        return $this->status->canLogin();
    }

    /**
     * 포인트 추가
     */
    public function addPoints(int $points, ?string $description = null): void
    {
        $this->increment('points', $points);
        
        // 포인트 히스토리 기록 (추후 구현)
        // $this->pointHistories()->create([
        //     'amount' => $points,
        //     'description' => $description,
        // ]);
    }

    /**
     * 포인트 차감
     */
    public function subtractPoints(int $points, ?string $description = null): bool
    {
        if ($this->points < $points) {
            return false;
        }
        
        $this->decrement('points', $points);
        
        // 포인트 히스토리 기록 (추후 구현)
        // $this->pointHistories()->create([
        //     'amount' => -$points,
        //     'description' => $description,
        // ]);
        
        return true;
    }

    /**
     * 레벨업 확인 및 처리
     */
    public function checkLevelUp(): bool
    {
        $newLevel = $this->calculateLevel();
        
        if ($newLevel > $this->level) {
            $this->update(['level' => $newLevel]);
            return true;
        }
        
        return false;
    }

    /**
     * 포인트 기반 레벨 계산
     */
    private function calculateLevel(): int
    {
        return min(100, intval($this->points / 1000) + 1);
    }

    /**
     * 특정 소셜 제공자 계정이 연동되어 있는지 확인
     */
    public function hasSocialAccount(string $provider): bool
    {
        return $this->socialAccounts()->where('provider', $provider)->exists();
    }

    /**
     * 마지막 로그인 정보 업데이트
     */
    public function updateLastLogin(string $ipAddress): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ipAddress,
        ]);
    }

    /**
     * 동적 게시판의 게시글들 가져오기
     */
    public function getBoardPosts(string $boardSlug)
    {
        $className = 'App\\Models\\Ahhob\\Board\\Dynamic\\Board' . \Illuminate\Support\Str::studly($boardSlug);
        
        if (!class_exists($className)) {
            return collect();
        }

        return $this->hasMany($className);
    }

    /**
     * 특정 게시판에서 작성한 게시글들
     */
    public function postsInBoard(string $boardSlug)
    {
        return $this->getBoardPosts($boardSlug)->getResults();
    }

    /**
     * 동적 게시판의 댓글들 가져오기
     */
    public function getBoardComments(string $boardSlug)
    {
        $className = 'App\\Models\\Ahhob\\Board\\Dynamic\\Board' . \Illuminate\Support\Str::studly($boardSlug) . 'Comment';
        
        if (!class_exists($className)) {
            return collect();
        }

        return $this->hasMany($className);
    }

    /**
     * 특정 게시판에서 작성한 댓글들
     */
    public function commentsInBoard(string $boardSlug)
    {
        return $this->getBoardComments($boardSlug)->getResults();
    }

    /**
     * 게시판 관리자 권한이 있는지 확인
     */
    public function isBoardManager(string $boardSlug): bool
    {
        $board = \App\Models\Ahhob\Board\Board::where('slug', $boardSlug)->first();
        if (!$board) {
            return false;
        }

        return \App\Models\Ahhob\Board\BoardManager::where('board_id', $board->id)
            ->where('user_id', $this->id)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * 특정 게시판에서 특정 권한을 가지고 있는지 확인
     */
    public function hasBoardPermission(string $boardSlug, string $permission): bool
    {
        $board = \App\Models\Ahhob\Board\Board::where('slug', $boardSlug)->first();
        if (!$board) {
            return false;
        }

        $manager = \App\Models\Ahhob\Board\BoardManager::where('board_id', $board->id)
            ->where('user_id', $this->id)
            ->where('is_active', true)
            ->first();

        if (!$manager) {
            return false;
        }

        return $manager->{$permission} ?? false;
    }

    // endregion
}
