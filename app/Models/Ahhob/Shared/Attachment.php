<?php

namespace App\Models\Ahhob\Shared;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    use HasFactory, SoftDeletes;

    /*
    |--------------------------------------------------------------------------
    | 모델 속성 (Attributes & Properties)
    |--------------------------------------------------------------------------
    */
    // region --- 모델 속성 (Attributes & Properties) ---

    /**
     * The table associated with the model.
     */
    protected $table = 'attachments';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'attachable_type',
        'attachable_id',
        'user_id',
        'original_name',
        'stored_name',
        'file_path',
        'file_extension',
        'mime_type',
        'file_size',
        'image_width',
        'image_height',
        'is_image',
        'thumbnail_path',
        'has_thumbnail',
        'metadata',
        'hash',
        'download_count',
        'is_public',
        'is_processed',
        'upload_ip',
        'sort_order',
        'status',
        'error_message',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_image' => 'boolean',
            'has_thumbnail' => 'boolean',
            'is_public' => 'boolean',
            'is_processed' => 'boolean',
            'metadata' => 'array',
            'file_size' => 'integer',
            'image_width' => 'integer',
            'image_height' => 'integer',
            'download_count' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'file_path',
        'upload_ip',
        'hash',
    ];

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 관계 (Relationships)
    |--------------------------------------------------------------------------
    */
    // region --- 관계 (Relationships) ---

    /**
     * 첨부파일이 속한 모델 (다형적 관계)
     */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * 업로드한 사용자
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 쿼리 스코프 (Query Scopes)
    |--------------------------------------------------------------------------
    */
    // region --- 쿼리 스코프 (Query Scopes) ---

    /**
     * 이미지 파일만 조회
     */
    public function scopeImages($query)
    {
        return $query->where('is_image', true);
    }

    /**
     * 공개 파일만 조회
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * 완료된 파일만 조회
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * 특정 상태의 파일들
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * 정렬 순서대로 조회
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('created_at');
    }

    /**
     * 특정 타입의 첨부파일들
     */
    public function scopeForType($query, string $type)
    {
        return $query->where('attachable_type', $type);
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 접근자 & 변경자 (Accessors & Mutators)
    |--------------------------------------------------------------------------
    */
    // region --- 접근자 & 변경자 (Accessors & Mutators) ---

    /**
     * 파일 URL 접근자
     */
    public function getUrlAttribute(): string
    {
        if ($this->is_public) {
            return Storage::url($this->file_path);
        }
        
        return route('attachment.download', $this->id);
    }

    /**
     * 썸네일 URL 접근자
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        if (!$this->has_thumbnail || !$this->thumbnail_path) {
            return null;
        }

        if ($this->is_public) {
            return Storage::url($this->thumbnail_path);
        }

        return route('attachment.thumbnail', $this->id);
    }

    /**
     * 파일 크기를 사람이 읽기 쉬운 형태로 변환
     */
    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * 파일이 이미지인지 확인
     */
    public function getIsImageFileAttribute(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * 다운로드 가능한지 확인
     */
    public function getIsDownloadableAttribute(): bool
    {
        return $this->status === 'completed' && $this->is_processed;
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 공개 메서드 (Public Methods)
    |--------------------------------------------------------------------------
    */
    // region --- 공개 메서드 (Public Methods) ---

    /**
     * 다운로드 횟수 증가
     */
    public function incrementDownloadCount(): void
    {
        $this->increment('download_count');
    }

    /**
     * 파일 삭제 (Storage에서도 제거)
     */
    public function deleteFile(): bool
    {
        $deleted = true;

        // 원본 파일 삭제
        if (Storage::exists($this->file_path)) {
            $deleted = Storage::delete($this->file_path);
        }

        // 썸네일 파일 삭제
        if ($this->has_thumbnail && $this->thumbnail_path && Storage::exists($this->thumbnail_path)) {
            Storage::delete($this->thumbnail_path);
        }

        // 모델에서도 삭제
        if ($deleted) {
            $this->delete();
        }

        return $deleted;
    }

    /**
     * 파일 존재 여부 확인
     */
    public function fileExists(): bool
    {
        return Storage::exists($this->file_path);
    }

    /**
     * 썸네일 존재 여부 확인
     */
    public function thumbnailExists(): bool
    {
        return $this->has_thumbnail && 
               $this->thumbnail_path && 
               Storage::exists($this->thumbnail_path);
    }

    /**
     * 파일 상태 업데이트
     */
    public function updateStatus(string $status, ?string $errorMessage = null): bool
    {
        $data = ['status' => $status];
        
        if ($errorMessage) {
            $data['error_message'] = $errorMessage;
        }

        if ($status === 'completed') {
            $data['is_processed'] = true;
        }

        return $this->update($data);
    }

    /**
     * 메타데이터 추가/업데이트
     */
    public function addMetadata(string $key, mixed $value): bool
    {
        $metadata = $this->metadata ?? [];
        $metadata[$key] = $value;
        
        return $this->update(['metadata' => $metadata]);
    }

    /**
     * 특정 메타데이터 가져오기
     */
    public function getMetadata(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * 파일이 특정 사용자에게 접근 가능한지 확인
     */
    public function canAccess(?User $user = null): bool
    {
        // 공개 파일은 누구나 접근 가능
        if ($this->is_public) {
            return true;
        }

        // 로그인하지 않은 사용자는 비공개 파일 접근 불가
        if (!$user) {
            return false;
        }

        // 업로드한 사용자는 항상 접근 가능
        if ($this->user_id === $user->id) {
            return true;
        }

        // 관리자는 모든 파일 접근 가능
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return true;
        }

        // 첨부된 모델의 소유자인지 확인
        if ($this->attachable && method_exists($this->attachable, 'user_id')) {
            return $this->attachable->user_id === $user->id;
        }

        return false;
    }

    /**
     * 정렬 순서 업데이트
     */
    public function updateSortOrder(int $order): bool
    {
        return $this->update(['sort_order' => $order]);
    }

    // endregion
}