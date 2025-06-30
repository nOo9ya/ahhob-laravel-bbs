<?php

namespace App\Models\Ahhob\User;

use App\Enums\SocialProvider;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSocialAccount extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | 모델 속성 (Attributes & Properties)
    |--------------------------------------------------------------------------
    */
    // region --- 모델 속성 (Attributes & Properties) ---

    /**
     * The table associated with the model.
     */
    protected $table = 'user_social_accounts';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'provider',
        'provider_id',
        'profile_url',
        'photo_url',
        'display_name',
        'description',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'provider' => SocialProvider::class,
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
     * 소셜 계정이 속한 사용자
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 접근자 & 변경자 (Accessors & Mutators)
    |--------------------------------------------------------------------------
    */
    // region --- 접근자 & 변경자 (Accessors & Mutators) ---

    /**
     * 제공자 이름 접근자
     */
    public function getProviderLabelAttribute(): string
    {
        return $this->provider->label();
    }

    /**
     * 제공자 아이콘 클래스 접근자
     */
    public function getProviderIconAttribute(): string
    {
        return $this->provider->iconClass();
    }

    /**
     * 제공자 브랜드 색상 접근자
     */
    public function getProviderColorAttribute(): string
    {
        return $this->provider->brandColor();
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 공개 메서드 (Public Methods)
    |--------------------------------------------------------------------------
    */
    // region --- 공개 메서드 (Public Methods) ---

    /**
     * 소셜 계정 정보 업데이트
     */
    public function updateSocialInfo(array $socialUser): void
    {
        $this->update([
            'profile_url' => $socialUser['profile_url'] ?? null,
            'photo_url' => $socialUser['avatar'] ?? $socialUser['photo_url'] ?? null,
            'display_name' => $socialUser['name'] ?? $socialUser['display_name'] ?? null,
            'description' => $socialUser['description'] ?? null,
        ]);
    }

    /**
     * 프로필 사진이 있는지 확인
     */
    public function hasPhoto(): bool
    {
        return !empty($this->photo_url);
    }

    /**
     * 프로필 URL이 있는지 확인
     */
    public function hasProfile(): bool
    {
        return !empty($this->profile_url);
    }

    // endregion
}