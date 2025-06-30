<?php

use App\Models\Ahhob\Board\Dynamic\BoardNotice;
use App\Models\Ahhob\Shared\Attachment;
use App\Models\User;
use App\Services\Ahhob\Shared\AttachmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    // 테스트용 스토리지 디스크 설정
    Storage::fake('public');
    Storage::fake('local');
    
    // 테스트용 동적 게시판 테이블 생성
    \Artisan::call('board:create', ['slug' => 'notice']);
    
    // 테스트용 사용자 및 게시글 생성
    $this->user = User::factory()->create();
    $this->post = BoardNotice::factory()->create(['user_id' => $this->user->id]);
    
    // AttachmentService 인스턴스 생성
    $this->attachmentService = new AttachmentService();
});

/**
 * 테스트 목적: 파일 업로드 시스템의 기본 파일 업로드 기능 검증
 * 테스트 시나리오: 사용자가 게시글에 일반 파일을 업로드하는 경우
 * 기대 결과: 파일이 정상적으로 저장되고 메타데이터가 올바르게 기록됨
 * 관련 비즈니스 규칙: 업로드된 파일은 메타데이터와 함께 저장되어야 함
 */
test('기본_파일_업로드', function () {
    // Given: 업로드할 테스트 파일 생성
    // 실제 파일 업로드 상황을 시뮬레이션
    $file = UploadedFile::fake()->create('test-document.pdf', 1024, 'application/pdf');
    
    // When: AttachmentService를 통해 파일 업로드
    // 파일과 관련 메타데이터가 함께 처리되어야 함
    $attachment = $this->attachmentService->upload(
        $file,
        $this->post,
        $this->user,
        [
            'is_public' => true,
            'description' => '테스트 문서 파일'
        ]
    );
    
    // Then: 파일이 정상적으로 업로드되고 메타데이터가 저장되는지 확인
    // 데이터베이스에 Attachment 레코드가 생성되어야 함
    expect($attachment)->toBeInstanceOf(Attachment::class);
    expect($attachment->stored_name)->toContain('test-document');
    expect($attachment->original_name)->toBe('test-document.pdf');
    expect($attachment->file_size)->toBeGreaterThan(0);
    expect($attachment->mime_type)->toBe('application/pdf');
    expect($attachment->is_public)->toBeTrue();
    expect($attachment->status)->toBe('completed');
    expect($attachment->user_id)->toBe($this->user->id);
    
    // 실제 파일이 스토리지에 저장되었는지 확인
    Storage::disk('public')->assertExists($attachment->file_path);
});

/**
 * 테스트 목적: 이미지 파일 업로드 시 썸네일 자동 생성 기능 검증
 * 테스트 시나리오: 사용자가 이미지 파일을 업로드하는 경우
 * 기대 결과: 원본 이미지와 함께 썸네일이 자동 생성됨
 * 관련 비즈니스 규칙: 이미지 파일은 썸네일을 자동 생성하여 성능 최적화
 */
test('이미지_파일_업로드_및_썸네일_생성', function () {
    // Given: 업로드할 이미지 파일 생성
    // 실제 이미지 업로드 상황을 시뮬레이션
    $imageFile = UploadedFile::fake()->image('test-image.jpg', 800, 600);
    
    // When: AttachmentService를 통해 이미지 파일 업로드
    // 이미지 파일의 경우 썸네일도 함께 생성되어야 함
    $attachment = $this->attachmentService->upload(
        $imageFile,
        $this->post,
        $this->user,
        [
            'is_public' => true,
            'generate_thumbnail' => true
        ]
    );
    
    // Then: 이미지 파일과 썸네일이 모두 정상적으로 생성되는지 확인
    // 이미지 메타데이터가 올바르게 저장되어야 함
    expect($attachment)->toBeInstanceOf(Attachment::class);
    expect($attachment->stored_name)->toEndWith('.jpg');
    expect($attachment->mime_type)->toBe('image/jpeg');
    expect($attachment->is_image)->toBeTrue();
    expect($attachment->image_width)->toBe(800);
    expect($attachment->image_height)->toBe(600);
    
    // 썸네일이 생성되고 경로가 설정되었는지 확인
    expect($attachment->thumbnail_path)->not()->toBeNull();
    expect($attachment->has_thumbnail)->toBeTrue();
    
    // 원본 이미지와 썸네일 파일이 모두 스토리지에 존재하는지 확인
    Storage::disk('public')->assertExists($attachment->file_path);
    Storage::disk('public')->assertExists($attachment->thumbnail_path);
});

/**
 * 테스트 목적: 파일 크기 제한 검증 기능 확인
 * 테스트 시나리오: 허용된 최대 파일 크기를 초과하는 파일을 업로드하려는 경우
 * 기대 결과: 파일 크기 초과 예외가 발생함
 * 관련 비즈니스 규칙: 시스템 안정성을 위해 파일 크기 제한 필요
 */
test('파일_크기_제한_검증', function () {
    // Given: 허용 크기를 초과하는 대용량 파일 생성
    // 파일 크기 제한을 테스트하기 위한 시나리오
    $largeFile = UploadedFile::fake()->create('large-file.pdf', 10240); // 10MB
    
    // When & Then: 대용량 파일 업로드 시 예외 발생 확인
    // 파일 크기 제한을 초과하면 적절한 예외가 발생해야 함
    expect(function () use ($largeFile) {
        $this->attachmentService->upload(
            $largeFile,
            $this->post,
            $this->user,
            ['max_file_size' => 5120] // 5MB 제한
        );
    })->toThrow(\InvalidArgumentException::class, '파일 크기가 허용된 최대 크기를 초과했습니다.');
});

/**
 * 테스트 목적: 허용되지 않은 파일 형식 업로드 차단 기능 검증
 * 테스트 시나리오: 보안상 위험한 파일 형식을 업로드하려는 경우
 * 기대 결과: 파일 형식 제한 예외가 발생함
 * 관련 비즈니스 규칙: 보안을 위해 특정 파일 형식만 허용
 */
test('허용되지_않은_파일_형식_차단', function () {
    // Given: 허용되지 않은 실행 파일 생성
    // 보안상 위험한 파일 형식 업로드 시도
    $executableFile = UploadedFile::fake()->create('malicious.exe', 100, 'application/x-msdownload');
    
    // When & Then: 허용되지 않은 파일 형식 업로드 시 예외 발생 확인
    // 보안을 위해 실행 파일 등은 업로드가 차단되어야 함
    expect(function () use ($executableFile) {
        $this->attachmentService->upload(
            $executableFile,
            $this->post,
            $this->user,
            ['allowed_types' => ['image/*', 'application/pdf', 'text/*']]
        );
    })->toThrow(\InvalidArgumentException::class, '허용되지 않은 파일 형식입니다.');
});

/**
 * 테스트 목적: 중복 파일 업로드 시 해시 기반 중복 감지 기능 검증
 * 테스트 시나리오: 동일한 내용의 파일을 여러 번 업로드하는 경우
 * 기대 결과: 중복 파일이 감지되고 기존 파일을 참조함
 * 관련 비즈니스 규칙: 스토리지 공간 절약을 위한 중복 파일 관리
 */
test('중복_파일_감지_및_처리', function () {
    // Given: 동일한 내용의 파일을 두 번 생성
    // 중복 파일 감지 기능을 테스트하기 위한 시나리오
    $file1 = UploadedFile::fake()->createWithContent('duplicate.txt', 'same content');
    $file2 = UploadedFile::fake()->createWithContent('duplicate-copy.txt', 'same content');
    
    // When: 첫 번째 파일 업로드
    $attachment1 = $this->attachmentService->upload($file1, $this->post, $this->user);
    
    // When: 동일한 내용의 두 번째 파일 업로드
    $attachment2 = $this->attachmentService->upload($file2, $this->post, $this->user);
    
    // Then: 중복 파일이 감지되고 동일한 해시값을 가지는지 확인
    // 중복 파일은 동일한 스토리지 위치를 참조해야 함
    expect($attachment1->hash)->toBe($attachment2->hash);
    expect($attachment1->file_path)->toBe($attachment2->file_path);
    
    // 하지만 Attachment 레코드는 각각 별도로 생성되어야 함
    expect($attachment1->id)->not()->toBe($attachment2->id);
    expect($attachment1->original_name)->toBe('duplicate.txt');
    expect($attachment2->original_name)->toBe('duplicate-copy.txt');
});

/**
 * 테스트 목적: 비공개 파일 업로드 및 접근 권한 검증 기능 확인
 * 테스트 시나리오: 비공개로 설정된 파일에 대한 접근 권한 확인
 * 기대 결과: 권한이 있는 사용자만 파일에 접근 가능
 * 관련 비즈니스 규칙: 비공개 파일은 권한이 있는 사용자만 접근 가능
 */
test('비공개_파일_접근_권한_검증', function () {
    // Given: 비공개 파일 업로드
    // 권한 기반 파일 접근 제어 테스트
    $privateFile = UploadedFile::fake()->create('private-document.pdf', 512);
    $otherUser = User::factory()->create();
    
    // When: 비공개 파일로 업로드
    $attachment = $this->attachmentService->upload(
        $privateFile,
        $this->post,
        $this->user,
        ['is_public' => false]
    );
    
    // Then: 권한 검증이 올바르게 작동하는지 확인
    // 작성자는 접근 가능, 다른 사용자는 접근 불가
    expect($attachment->is_public)->toBeFalse();
    expect($attachment->canAccess($this->user))->toBeTrue(); // 작성자는 접근 가능
    expect($attachment->canAccess($otherUser))->toBeFalse(); // 다른 사용자는 접근 불가
    expect($attachment->canAccess(null))->toBeFalse(); // 익명 사용자는 접근 불가
});

/**
 * 테스트 목적: 파일 업로드 과정에서의 상태 관리 시스템 검증
 * 테스트 시나리오: 파일 업로드 과정에서 상태가 올바르게 변경되는지 확인
 * 기대 결과: uploading → processing → completed 순서로 상태 변경
 * 관련 비즈니스 규칙: 파일 처리 과정의 각 단계별 상태 추적 필요
 */
test('파일_업로드_상태_관리', function () {
    // Given: 이미지 파일 준비 (처리 과정이 필요한 파일)
    // 상태 변경 과정을 추적하기 위한 테스트
    $imageFile = UploadedFile::fake()->image('status-test.jpg', 400, 300);
    
    // When: 파일 업로드 시작
    // 업로드 과정에서 상태 변화를 모니터링
    $attachment = $this->attachmentService->upload(
        $imageFile,
        $this->post,
        $this->user,
        ['track_status' => true]
    );
    
    // Then: 최종 상태가 completed인지 확인
    // 정상적인 업로드 완료 시 completed 상태여야 함
    expect($attachment->status)->toBe('completed');
    expect($attachment->is_processed)->toBeTrue();
    
    // 파일 처리 과정에서 필요한 메타데이터가 올바르게 설정되었는지 확인
    expect($attachment->file_size)->toBeGreaterThan(0);
    expect($attachment->hash)->not()->toBeNull();
    expect($attachment->upload_ip)->toBe(request()->ip());
});

/**
 * 테스트 목적: 이미지 파일이 WebP 형식으로 자동 변환되는지 검증
 * 테스트 시나리오: JPEG/PNG 이미지를 업로드하면 WebP로 변환되어 저장
 * 기대 결과: 원본은 JPEG/PNG이지만 저장된 파일은 WebP 형식
 * 관련 비즈니스 규칙: 성능 최적화를 위해 이미지는 WebP로 변환 저장
 */
test('이미지_WebP_형식_자동_변환', function () {
    // Given: JPEG 이미지 파일 생성
    // WebP 변환 기능을 테스트하기 위한 원본 이미지
    $jpegFile = UploadedFile::fake()->image('original.jpg', 800, 600);
    
    // When: 이미지 파일 업로드 (WebP 변환 활성화)
    // 내부적으로 WebP로 변환되어 저장되어야 함
    $attachment = $this->attachmentService->upload(
        $jpegFile,
        $this->post,
        $this->user,
        [
            'is_public' => true,
            'convert_to_webp' => true,
            'webp_quality' => 90
        ]
    );
    
    // Then: 파일이 WebP 형식으로 변환되어 저장되는지 확인
    // 원본 파일명은 유지하되 확장자와 MIME 타입은 WebP로 변경
    expect($attachment)->toBeInstanceOf(Attachment::class);
    expect($attachment->original_name)->toBe('original.jpg'); // 원본 파일명 유지
    expect($attachment->file_extension)->toBe('webp'); // 확장자는 webp로 변환
    expect($attachment->mime_type)->toBe('image/webp'); // MIME 타입도 webp로 변환
    expect($attachment->stored_name)->toEndWith('.webp'); // 저장된 파일도 webp 확장자
    expect($attachment->is_image)->toBeTrue();
    
    // WebP 파일이 실제로 스토리지에 저장되었는지 확인
    Storage::disk('public')->assertExists($attachment->file_path);
    expect(pathinfo($attachment->file_path, PATHINFO_EXTENSION))->toBe('webp');
});

/**
 * 테스트 목적: WebP 변환 'preserve' 모드 검증
 * 테스트 시나리오: webp_mode를 'preserve'로 설정한 경우
 * 기대 결과: 원본 형식 그대로 저장됨
 * 관련 비즈니스 규칙: preserve 모드에서는 항상 원본 형식을 유지해야 함
 */
test('WebP_변환_preserve_모드', function () {
    // Given: JPEG 이미지 파일 생성
    $jpegFile = UploadedFile::fake()->image('preserve-test.jpg', 400, 300);
    
    // When: preserve 모드로 업로드
    $attachment = $this->attachmentService->upload(
        $jpegFile,
        $this->post,
        $this->user,
        [
            'is_public' => true,
            'webp_mode' => 'preserve'
        ]
    );
    
    // Then: 원본 형식 그대로 저장되는지 확인
    expect($attachment)->toBeInstanceOf(Attachment::class);
    expect($attachment->original_name)->toBe('preserve-test.jpg');
    expect($attachment->file_extension)->toBe('jpg'); // 원본 확장자 유지
    expect($attachment->mime_type)->toBe('image/jpeg'); // 원본 MIME 타입 유지
    expect($attachment->stored_name)->toEndWith('.jpg'); // JPEG 확장자 유지
    expect($attachment->is_image)->toBeTrue();
    
    // JPEG 파일이 스토리지에 저장되었는지 확인
    Storage::disk('public')->assertExists($attachment->file_path);
    expect(pathinfo($attachment->file_path, PATHINFO_EXTENSION))->toBe('jpg');
});

/**
 * 테스트 목적: WebP 변환 'force' 모드 검증
 * 테스트 시나리오: webp_mode를 'force'로 설정한 경우
 * 기대 결과: 무조건 WebP로 변환됨
 * 관련 비즈니스 규칙: force 모드에서는 항상 WebP로 변환해야 함
 */
test('WebP_변환_force_모드', function () {
    // Given: PNG 이미지 파일 생성
    $pngFile = UploadedFile::fake()->image('force-test.png', 400, 300);
    
    // When: force 모드로 업로드
    $attachment = $this->attachmentService->upload(
        $pngFile,
        $this->post,
        $this->user,
        [
            'is_public' => true,
            'webp_mode' => 'force'
        ]
    );
    
    // Then: WebP로 변환되어 저장되는지 확인
    expect($attachment)->toBeInstanceOf(Attachment::class);
    expect($attachment->original_name)->toBe('force-test.png'); // 원본 파일명 유지
    expect($attachment->file_extension)->toBe('webp'); // WebP로 변환
    expect($attachment->mime_type)->toBe('image/webp'); // WebP MIME 타입
    expect($attachment->stored_name)->toEndWith('.webp'); // WebP 확장자
    expect($attachment->is_image)->toBeTrue();
    
    // WebP 파일이 스토리지에 저장되었는지 확인
    Storage::disk('public')->assertExists($attachment->file_path);
    expect(pathinfo($attachment->file_path, PATHINFO_EXTENSION))->toBe('webp');
});

/**
 * 테스트 목적: WebP 변환 'auto' 모드 검증 (대용량 파일)
 * 테스트 시나리오: webp_mode를 'auto'로 설정하고 큰 파일을 업로드
 * 기대 결과: 파일 크기가 임계값 이상이면 WebP로 변환됨
 * 관련 비즈니스 규칙: auto 모드에서는 파일 크기에 따라 자동 결정
 */
test('WebP_변환_auto_모드_대용량_파일', function () {
    // Given: 큰 JPEG 파일 생성 (1000x1000)
    $largeJpegFile = UploadedFile::fake()->image('large-auto.jpg', 1000, 1000);
    
    // When: auto 모드로 업로드
    $attachment = $this->attachmentService->upload(
        $largeJpegFile,
        $this->post,
        $this->user,
        [
            'is_public' => true,
            'webp_mode' => 'auto'
        ]
    );
    
    // Then: 큰 파일이므로 WebP로 변환되어야 함
    expect($attachment)->toBeInstanceOf(Attachment::class);
    expect($attachment->original_name)->toBe('large-auto.jpg');
    expect($attachment->file_extension)->toBe('webp'); // WebP로 변환
    expect($attachment->mime_type)->toBe('image/webp'); // WebP MIME 타입
    expect($attachment->stored_name)->toEndWith('.webp'); // WebP 확장자
    
    // WebP 파일이 스토리지에 저장되었는지 확인
    Storage::disk('public')->assertExists($attachment->file_path);
    expect(pathinfo($attachment->file_path, PATHINFO_EXTENSION))->toBe('webp');
});

/**
 * 테스트 목적: WebP 변환 'auto' 모드 검증 (소용량 파일)
 * 테스트 시나리오: webp_mode를 'auto'로 설정하고 작은 파일을 업로드
 * 기대 결과: 파일 크기가 임계값 미만이면 원본 형식 유지
 * 관련 비즈니스 규칙: auto 모드에서는 작은 파일은 변환하지 않음
 */
test('WebP_변환_auto_모드_소용량_파일', function () {
    // Given: 작은 PNG 파일 생성 (50x50)
    $smallPngFile = UploadedFile::fake()->image('small-auto.png', 50, 50);
    
    // When: auto 모드로 업로드
    $attachment = $this->attachmentService->upload(
        $smallPngFile,
        $this->post,
        $this->user,
        [
            'is_public' => true,
            'webp_mode' => 'auto'
        ]
    );
    
    // Then: 작은 파일이므로 원본 형식이 유지되어야 함
    expect($attachment)->toBeInstanceOf(Attachment::class);
    expect($attachment->original_name)->toBe('small-auto.png');
    expect($attachment->file_extension)->toBe('png'); // 원본 형식 유지
    expect($attachment->mime_type)->toBe('image/png'); // 원본 MIME 타입 유지
    expect($attachment->stored_name)->toEndWith('.png'); // PNG 확장자 유지
    
    // PNG 파일이 스토리지에 저장되었는지 확인
    Storage::disk('public')->assertExists($attachment->file_path);
    expect(pathinfo($attachment->file_path, PATHINFO_EXTENSION))->toBe('png');
});

/**
 * 테스트 목적: WebP 변환 'optional' 모드 검증 (명시적 활성화)
 * 테스트 시나리오: webp_mode를 'optional'로 설정하고 convert_to_webp를 true로 설정
 * 기대 결과: WebP로 변환됨
 * 관련 비즈니스 규칙: optional 모드에서는 convert_to_webp 옵션에 따라 결정
 */
test('WebP_변환_optional_모드_활성화', function () {
    // Given: JPEG 이미지 파일 생성
    $jpegFile = UploadedFile::fake()->image('optional-on.jpg', 400, 300);
    
    // When: optional 모드에서 WebP 변환 활성화
    $attachment = $this->attachmentService->upload(
        $jpegFile,
        $this->post,
        $this->user,
        [
            'is_public' => true,
            'webp_mode' => 'optional',
            'convert_to_webp' => true
        ]
    );
    
    // Then: WebP로 변환되어야 함
    expect($attachment)->toBeInstanceOf(Attachment::class);
    expect($attachment->original_name)->toBe('optional-on.jpg');
    expect($attachment->file_extension)->toBe('webp'); // WebP로 변환
    expect($attachment->mime_type)->toBe('image/webp'); // WebP MIME 타입
    expect($attachment->stored_name)->toEndWith('.webp'); // WebP 확장자
    
    // WebP 파일이 스토리지에 저장되었는지 확인
    Storage::disk('public')->assertExists($attachment->file_path);
    expect(pathinfo($attachment->file_path, PATHINFO_EXTENSION))->toBe('webp');
});

/**
 * 테스트 목적: WebP 변환 'optional' 모드 검증 (명시적 비활성화)
 * 테스트 시나리오: webp_mode를 'optional'로 설정하고 convert_to_webp를 false로 설정
 * 기대 결과: 원본 형식 유지
 * 관련 비즈니스 규칙: optional 모드에서는 convert_to_webp 옵션에 따라 결정
 */
test('WebP_변환_optional_모드_비활성화', function () {
    // Given: PNG 이미지 파일 생성
    $pngFile = UploadedFile::fake()->image('optional-off.png', 400, 300);
    
    // When: optional 모드에서 WebP 변환 비활성화
    $attachment = $this->attachmentService->upload(
        $pngFile,
        $this->post,
        $this->user,
        [
            'is_public' => true,
            'webp_mode' => 'optional',
            'convert_to_webp' => false
        ]
    );
    
    // Then: 원본 형식이 유지되어야 함
    expect($attachment)->toBeInstanceOf(Attachment::class);
    expect($attachment->original_name)->toBe('optional-off.png');
    expect($attachment->file_extension)->toBe('png'); // 원본 형식 유지
    expect($attachment->mime_type)->toBe('image/png'); // 원본 MIME 타입 유지
    expect($attachment->stored_name)->toEndWith('.png'); // PNG 확장자 유지
    
    // PNG 파일이 스토리지에 저장되었는지 확인
    Storage::disk('public')->assertExists($attachment->file_path);
    expect(pathinfo($attachment->file_path, PATHINFO_EXTENSION))->toBe('png');
});

/**
 * 테스트 목적: 첨부파일 삭제 시 실제 파일과 썸네일도 함께 삭제되는지 검증
 * 테스트 시나리오: 이미지 첨부파일을 삭제하는 경우
 * 기대 결과: 데이터베이스 레코드, 원본 파일, 썸네일이 모두 삭제됨
 * 관련 비즈니스 규칙: 첨부파일 삭제 시 관련된 모든 파일도 함께 정리
 */
test('첨부파일_삭제_시_파일_정리', function () {
    // Given: 썸네일이 있는 이미지 파일 업로드
    // 파일 삭제 시 정리 과정을 테스트하기 위한 준비
    $imageFile = UploadedFile::fake()->image('delete-test.png', 600, 400);
    $attachment = $this->attachmentService->upload(
        $imageFile,
        $this->post,
        $this->user,
        ['generate_thumbnail' => true]
    );
    
    // 파일과 썸네일이 존재하는지 먼저 확인
    Storage::disk('public')->assertExists($attachment->file_path);
    Storage::disk('public')->assertExists($attachment->thumbnail_path);
    
    // When: 첨부파일 삭제 실행
    // 삭제 시 모든 관련 파일이 정리되어야 함
    $this->attachmentService->delete($attachment, $this->user);
    
    // Then: 데이터베이스에서 소프트 삭제되고 실제 파일도 삭제되는지 확인
    // 소프트 삭제로 레코드는 유지하되 파일은 실제로 삭제
    $attachment->refresh();
    expect($attachment->status)->toBe('deleted');
    expect($attachment->deleted_at)->not()->toBeNull();
    
    // 실제 파일과 썸네일이 스토리지에서 삭제되었는지 확인
    Storage::disk('public')->assertMissing($attachment->file_path);
    Storage::disk('public')->assertMissing($attachment->thumbnail_path);
});