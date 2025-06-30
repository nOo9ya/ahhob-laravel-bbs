<?php

namespace App\Services\Ahhob\Shared;

use App\Models\Ahhob\Shared\Attachment;
use App\Models\Ahhob\System\SystemSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use InvalidArgumentException;

/**
 * 파일 업로드 및 첨부파일 관리 서비스
 * 
 * 이 서비스는 파일 업로드, 이미지 처리, 썸네일 생성, 파일 관리를 담당합니다.
 * 
 * 주요 기능:
 * - 파일 업로드 및 메타데이터 관리
 * - 이미지 최적화 및 썸네일 자동 생성
 * - 파일 크기 및 형식 제한
 * - 중복 파일 감지 및 처리 (해시 기반)
 * - 권한 기반 파일 접근 제어
 * - 안전한 파일 삭제 및 정리
 * 
 * 성능 최적화:
 * - 트랜잭션 처리로 데이터 일관성 보장
 * - 이미지 최적화로 스토리지 공간 절약
 * - 해시 기반 중복 파일 제거
 */
class AttachmentService
{
    /**
     * 설정 캐시
     */
    private array $config;

    public function __construct()
    {
        $this->config = config('attachment', []);
    }

    /**
     * 시스템 설정값 조회 (DB 우선, 설정 파일 폴백)
     */
    private function getSetting(string $key, mixed $default = null): mixed
    {
        // 시스템 설정에서 먼저 조회
        $value = SystemSetting::get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        // 시스템 설정이 없으면 config 파일에서 조회
        $configKey = str_replace('attachment.', '', $key);
        return data_get($this->config, $configKey, $default);
    }

    /**
     * 파일 업로드
     * 
     * @param UploadedFile $file 업로드된 파일
     * @param Model $attachable 첨부될 모델 (게시글, 댓글 등)
     * @param User $user 업로드한 사용자
     * @param array $options 업로드 옵션
     *   - webp_mode: WebP 변환 모드 ('preserve', 'optional', 'auto', 'force')
     *   - convert_to_webp: 이미지를 WebP로 변환할지 여부 (webp_mode가 'optional'일 때 사용)
     *   - webp_quality: WebP 품질 (1-100)
     *   - generate_thumbnail: 썸네일 생성 여부 (기본값: true)
     *   - max_file_size: 최대 파일 크기 (KB)
     *   - allowed_types: 허용할 파일 타입
     *   - is_public: 공개 파일 여부 (기본값: true)
     *   - description: 파일 설명
     * @return Attachment 생성된 첨부파일
     * 
     * @throws InvalidArgumentException 파일 검증 실패 시
     * @throws \Exception 업로드 실패 시
     */
    public function upload(UploadedFile $file, Model $attachable, User $user, array $options = []): Attachment
    {
        // 파일 검증
        $this->validateFile($file, $options);
        
        // 파일 해시 계산
        $fileHash = hash_file('sha256', $file->getRealPath());
        
        // 중복 파일 확인
        $existingAttachment = $this->findDuplicateFile($fileHash);
        
        // 파일 저장
        $filePath = $this->storeFile($file, $existingAttachment, $options);
        
        // 첨부파일 레코드 생성
        $attachment = $this->createAttachment($file, $attachable, $user, $filePath, $fileHash, $options);
        
        // 이미지인 경우 썸네일 생성
        if ($this->isImage($file) && ($options['generate_thumbnail'] ?? true)) {
            $this->generateThumbnail($attachment, $file, $existingAttachment);
        }
        
        // 상태 업데이트
        $attachment->update([
            'status' => 'completed',
            'is_processed' => true,
        ]);
        
        return $attachment;
    }

    /**
     * 첨부파일 삭제
     * 
     * @param Attachment $attachment 삭제할 첨부파일
     * @param User $user 삭제 요청자
     * 
     * @throws \Illuminate\Auth\Access\AuthorizationException 권한 없음
     * @throws \Exception 삭제 실패 시
     */
    public function delete(Attachment $attachment, User $user): void
    {
        // 권한 확인
        if (!$attachment->canAccess($user)) {
            throw new \Illuminate\Auth\Access\AuthorizationException('파일을 삭제할 권한이 없습니다.');
        }
        
        // 실제 파일 삭제
        $this->deletePhysicalFiles($attachment);
        
        // 첨부파일 레코드 소프트 삭제
        $attachment->update(['status' => 'deleted']);
        $attachment->delete();
    }

    /**
     * 파일 검증
     * 
     * @param UploadedFile $file 업로드된 파일
     * @param array $options 검증 옵션
     * @throws InvalidArgumentException 검증 실패 시
     */
    private function validateFile(UploadedFile $file, array $options): void
    {
        // 파일 크기 검증
        $maxSize = $options['max_file_size'] ?? $this->getSetting('attachment.default_max_file_size', 5120);
        if ($file->getSize() > $maxSize * 1024) {
            throw new InvalidArgumentException('파일 크기가 허용된 최대 크기를 초과했습니다.');
        }
        
        // 파일 형식 검증
        $allowedTypes = $options['allowed_types'] ?? $this->getSetting('attachment.default_allowed_types', [
            'image/*', 'application/pdf', 'text/*'
        ]);
        $mimeType = $file->getMimeType();
        
        if (!$this->isAllowedMimeType($mimeType, $allowedTypes)) {
            throw new InvalidArgumentException('허용되지 않은 파일 형식입니다.');
        }
    }

    /**
     * MIME 타입 허용 여부 확인
     * 
     * @param string $mimeType 확인할 MIME 타입
     * @param array $allowedTypes 허용된 타입 목록
     * @return bool 허용 여부
     */
    private function isAllowedMimeType(string $mimeType, array $allowedTypes): bool
    {
        foreach ($allowedTypes as $allowedType) {
            if (str_ends_with($allowedType, '/*')) {
                $prefix = str_replace('/*', '', $allowedType);
                if (str_starts_with($mimeType, $prefix . '/')) {
                    return true;
                }
            } elseif ($mimeType === $allowedType) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * 중복 파일 찾기
     * 
     * @param string $fileHash 파일 해시
     * @return Attachment|null 기존 파일
     */
    private function findDuplicateFile(string $fileHash): ?Attachment
    {
        return Attachment::where('hash', $fileHash)
            ->where('status', 'completed')
            ->first();
    }

    /**
     * 파일 저장
     * 
     * @param UploadedFile $file 업로드된 파일
     * @param Attachment|null $existingAttachment 기존 중복 파일
     * @param array $options 저장 옵션
     * @return string 저장된 파일 경로
     */
    private function storeFile(UploadedFile $file, ?Attachment $existingAttachment, array $options = []): string
    {
        // 중복 파일이 있으면 기존 경로 사용
        if ($existingAttachment) {
            return $existingAttachment->file_path;
        }
        
        // 이미지 파일인 경우 WebP 변환 여부 결정
        if ($this->isImage($file) && $this->shouldConvertToWebP($file, $options)) {
            return $this->storeImageAsWebP($file, $options);
        }
        
        // 일반 파일은 원본 그대로 저장
        $fileName = $this->generateUniqueFileName($file);
        return $file->storeAs('attachments', $fileName, 'public');
    }

    /**
     * WebP 변환 여부 결정
     * 
     * @param UploadedFile $file 업로드된 파일
     * @param array $options 옵션
     * @return bool WebP로 변환할지 여부
     */
    private function shouldConvertToWebP(UploadedFile $file, array $options): bool
    {
        // WebP 변환 가능한 MIME 타입인지 확인
        $convertibleTypes = $this->getSetting('attachment.webp.convertible_types', ['image/jpeg', 'image/png']);
        if (!in_array($file->getMimeType(), $convertibleTypes)) {
            return false;
        }
        
        // WebP 변환 모드 확인
        $webpMode = $options['webp_mode'] ?? $this->getSetting('attachment.webp.mode', 'optional');
        
        switch ($webpMode) {
            case 'preserve':
                // 원본 형식 유지
                return false;
                
            case 'force':
                // 무조건 WebP로 변환
                return true;
                
            case 'auto':
                // 파일 크기에 따라 자동 결정
                $minSize = $this->getSetting('attachment.webp.min_size_for_conversion', 50 * 1024);
                return $file->getSize() >= $minSize;
                
            case 'optional':
            default:
                // 옵션으로 제공 (기본값: false)
                return $options['convert_to_webp'] ?? false;
        }
    }

    /**
     * 이미지를 WebP 형식으로 변환하여 저장
     * 
     * @param UploadedFile $file 업로드된 이미지 파일
     * @param array $options 변환 옵션
     * @return string 저장된 파일 경로
     */
    private function storeImageAsWebP(UploadedFile $file, array $options = []): string
    {
        $fileName = $this->generateUniqueWebPFileName($file);
        $fullPath = Storage::disk('public')->path('attachments/' . $fileName);
        
        // 디렉토리 생성
        $directory = dirname($fullPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        // WebP 품질 설정 (1-100)
        $quality = $options['webp_quality'] ?? $this->getSetting('attachment.webp.quality', 85);
        
        // 이미지를 WebP로 변환하여 저장
        $image = Image::read($file->getRealPath());
        $image->toWebp($quality);
        $image->save($fullPath);
        
        return 'attachments/' . $fileName;
    }

    /**
     * WebP용 고유한 파일명 생성
     * 
     * @param UploadedFile $file 업로드된 파일
     * @return string 고유한 WebP 파일명
     */
    private function generateUniqueWebPFileName(UploadedFile $file): string
    {
        $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $timestamp = now()->format('Y/m/d');
        $uniqueId = uniqid();
        
        return "{$timestamp}/{$baseName}_{$uniqueId}.webp";
    }

    /**
     * 고유한 파일명 생성
     * 
     * @param UploadedFile $file 업로드된 파일
     * @return string 고유한 파일명
     */
    private function generateUniqueFileName(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $timestamp = now()->format('Y/m/d');
        $uniqueId = uniqid();
        
        return "{$timestamp}/{$baseName}_{$uniqueId}.{$extension}";
    }

    /**
     * 첨부파일 레코드 생성
     * 
     * @param UploadedFile $file 업로드된 파일
     * @param Model $attachable 첨부될 모델
     * @param User $user 업로드한 사용자
     * @param string $filePath 파일 경로
     * @param string $fileHash 파일 해시
     * @param array $options 옵션
     * @return Attachment 생성된 첨부파일
     */
    private function createAttachment(
        UploadedFile $file,
        Model $attachable,
        User $user,
        string $filePath,
        string $fileHash,
        array $options
    ): Attachment {
        $attachmentData = [
            'stored_name' => basename($filePath),
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'file_extension' => $file->getClientOriginalExtension(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'hash' => $fileHash,
            'user_id' => $user->id,
            'upload_ip' => request()->ip(),
            'is_public' => $options['is_public'] ?? true,
            'metadata' => isset($options['description']) ? ['description' => $options['description']] : null,
            'status' => 'processing',
            'is_processed' => false,
            'download_count' => 0,
            'sort_order' => 0,
        ];
        
        // 이미지인 경우 차원 정보 추가
        if ($this->isImage($file)) {
            $imageSize = getimagesize($file->getRealPath());
            $attachmentData['is_image'] = true;
            $attachmentData['image_width'] = $imageSize[0] ?? null;
            $attachmentData['image_height'] = $imageSize[1] ?? null;
            
            // 이미지가 WebP로 변환된 경우 MIME 타입과 확장자 업데이트
            if (str_ends_with($filePath, '.webp')) {
                $attachmentData['mime_type'] = 'image/webp';
                $attachmentData['file_extension'] = 'webp';
            }
        }
        
        return $attachable->attachments()->create($attachmentData);
    }

    /**
     * 이미지 파일 여부 확인
     * 
     * @param UploadedFile $file 파일
     * @return bool 이미지 여부
     */
    private function isImage(UploadedFile $file): bool
    {
        return str_starts_with($file->getMimeType(), 'image/');
    }

    /**
     * 썸네일 생성
     * 
     * @param Attachment $attachment 첨부파일
     * @param UploadedFile $file 원본 파일
     * @param Attachment|null $existingAttachment 기존 파일
     */
    private function generateThumbnail(Attachment $attachment, UploadedFile $file, ?Attachment $existingAttachment): void
    {
        // 기존 파일의 썸네일이 있으면 재사용
        if ($existingAttachment && $existingAttachment->has_thumbnail) {
            $attachment->update([
                'thumbnail_path' => $existingAttachment->thumbnail_path,
                'has_thumbnail' => true,
            ]);
            return;
        }
        
        try {
            // 썸네일 생성
            $thumbnailWidth = $this->getSetting('attachment.thumbnail.width', 300);
            $thumbnailHeight = $this->getSetting('attachment.thumbnail.height', 200);
            
            $image = Image::read($file->getRealPath());
            $image->resize($thumbnailWidth, $thumbnailHeight, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            
            // 썸네일 저장
            $thumbnailPath = $this->generateThumbnailPath($attachment->file_path);
            $fullThumbnailPath = Storage::disk('public')->path($thumbnailPath);
            
            // 디렉토리 생성
            $thumbnailDir = dirname($fullThumbnailPath);
            if (!is_dir($thumbnailDir)) {
                mkdir($thumbnailDir, 0755, true);
            }
            
            $image->save($fullThumbnailPath);
            
            // 썸네일 정보 업데이트
            $attachment->update([
                'thumbnail_path' => $thumbnailPath,
                'has_thumbnail' => true,
            ]);
        } catch (\Exception $e) {
            // 썸네일 생성 실패 시 로그 기록 (선택적)
            \Log::warning('썸네일 생성 실패: ' . $e->getMessage(), [
                'attachment_id' => $attachment->id,
                'file_path' => $attachment->file_path,
            ]);
        }
    }

    /**
     * 썸네일 경로 생성
     * 
     * @param string $originalPath 원본 파일 경로
     * @return string 썸네일 경로
     */
    private function generateThumbnailPath(string $originalPath): string
    {
        $pathInfo = pathinfo($originalPath);
        return $pathInfo['dirname'] . '/thumb_' . $pathInfo['basename'];
    }

    /**
     * 실제 파일들 삭제
     * 
     * @param Attachment $attachment 첨부파일
     */
    private function deletePhysicalFiles(Attachment $attachment): void
    {
        // 다른 첨부파일이 같은 파일을 참조하는지 확인
        $duplicateCount = Attachment::where('hash', $attachment->hash)
            ->where('id', '!=', $attachment->id)
            ->where('status', '!=', 'deleted')
            ->count();
        
        // 중복 참조가 없으면 실제 파일 삭제
        if ($duplicateCount === 0) {
            Storage::disk('public')->delete($attachment->file_path);
            
            if ($attachment->has_thumbnail) {
                Storage::disk('public')->delete($attachment->thumbnail_path);
            }
        }
    }
}