@extends('ahhob.admin.layouts.app')

@section('title', '첨부파일 설정 관리')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-paperclip mr-2"></i>
                        첨부파일 설정
                    </h3>
                    <div class="btn-group">
                        <button type="button" class="btn btn-info btn-sm" onclick="location.reload()">
                            <i class="fas fa-sync"></i> 새로고침
                        </button>
                        <button type="button" class="btn btn-warning btn-sm" onclick="clearCache()">
                            <i class="fas fa-broom"></i> 캐시 클리어
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="resetSettings()">
                            <i class="fas fa-undo"></i> 기본값으로 초기화
                        </button>
                    </div>
                </div>

                <form id="settingsForm" action="{{ route('admin.settings.store') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible fade show">
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                                {{ session('success') }}
                            </div>
                        @endif

                        @if($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show">
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                                <ul class="mb-0">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <!-- 기본 파일 설정 -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="max_file_size">
                                        {{ $settings['attachment.default_max_file_size']->label ?? '기본 최대 파일 크기' }}
                                        <small class="text-muted">(KB)</small>
                                    </label>
                                    <input type="number" 
                                           class="form-control @error('settings.attachment.default_max_file_size') is-invalid @enderror" 
                                           id="max_file_size"
                                           name="settings[attachment.default_max_file_size]" 
                                           value="{{ old('settings.attachment.default_max_file_size', $settings['attachment.default_max_file_size']->value ?? 5120) }}"
                                           min="1" max="102400">
                                    @if(isset($settings['attachment.default_max_file_size']) && $settings['attachment.default_max_file_size']->description)
                                        <small class="form-text text-muted">{{ $settings['attachment.default_max_file_size']->description }}</small>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- WebP 변환 설정 -->
                        <h5 class="mt-4 mb-3">
                            <i class="fas fa-image mr-2"></i>
                            WebP 이미지 변환 설정
                        </h5>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="webp_mode">{{ $settings['attachment.webp.mode']->label ?? 'WebP 변환 모드' }}</label>
                                    <select class="form-control @error('settings.attachment.webp.mode') is-invalid @enderror" 
                                            id="webp_mode"
                                            name="settings[attachment.webp.mode]">
                                        @if(isset($settings['attachment.webp.mode']) && $settings['attachment.webp.mode']->options)
                                            @foreach($settings['attachment.webp.mode']->options as $value => $label)
                                                <option value="{{ $value }}" 
                                                        {{ old('settings.attachment.webp.mode', $settings['attachment.webp.mode']->value) === $value ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                    @if(isset($settings['attachment.webp.mode']) && $settings['attachment.webp.mode']->description)
                                        <small class="form-text text-muted">{{ $settings['attachment.webp.mode']->description }}</small>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="webp_quality">
                                        {{ $settings['attachment.webp.quality']->label ?? 'WebP 품질' }}
                                        <small class="text-muted">(1-100)</small>
                                    </label>
                                    <input type="number" 
                                           class="form-control @error('settings.attachment.webp.quality') is-invalid @enderror" 
                                           id="webp_quality"
                                           name="settings[attachment.webp.quality]" 
                                           value="{{ old('settings.attachment.webp.quality', $settings['attachment.webp.quality']->value ?? 85) }}"
                                           min="1" max="100">
                                    @if(isset($settings['attachment.webp.quality']) && $settings['attachment.webp.quality']->description)
                                        <small class="form-text text-muted">{{ $settings['attachment.webp.quality']->description }}</small>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="webp_min_size">
                                        {{ $settings['attachment.webp.min_size_for_conversion']->label ?? 'WebP 자동 변환 최소 크기' }}
                                        <small class="text-muted">(bytes)</small>
                                    </label>
                                    <input type="number" 
                                           class="form-control @error('settings.attachment.webp.min_size_for_conversion') is-invalid @enderror" 
                                           id="webp_min_size"
                                           name="settings[attachment.webp.min_size_for_conversion]" 
                                           value="{{ old('settings.attachment.webp.min_size_for_conversion', $settings['attachment.webp.min_size_for_conversion']->value ?? 51200) }}"
                                           min="1">
                                    @if(isset($settings['attachment.webp.min_size_for_conversion']) && $settings['attachment.webp.min_size_for_conversion']->description)
                                        <small class="form-text text-muted">{{ $settings['attachment.webp.min_size_for_conversion']->description }}</small>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="form-check mt-4">
                                        <input type="hidden" name="settings[attachment.webp.keep_original]" value="0">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               id="webp_keep_original"
                                               name="settings[attachment.webp.keep_original]" 
                                               value="1"
                                               {{ old('settings.attachment.webp.keep_original', $settings['attachment.webp.keep_original']->value ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="webp_keep_original">
                                            {{ $settings['attachment.webp.keep_original']->label ?? '원본 파일 보관' }}
                                        </label>
                                        @if(isset($settings['attachment.webp.keep_original']) && $settings['attachment.webp.keep_original']->description)
                                            <br><small class="form-text text-muted">{{ $settings['attachment.webp.keep_original']->description }}</small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 썸네일 설정 -->
                        <h5 class="mt-4 mb-3">
                            <i class="far fa-images mr-2"></i>
                            썸네일 생성 설정
                        </h5>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="thumbnail_width">
                                        {{ $settings['attachment.thumbnail.width']->label ?? '썸네일 가로 크기' }}
                                        <small class="text-muted">(px)</small>
                                    </label>
                                    <input type="number" 
                                           class="form-control @error('settings.attachment.thumbnail.width') is-invalid @enderror" 
                                           id="thumbnail_width"
                                           name="settings[attachment.thumbnail.width]" 
                                           value="{{ old('settings.attachment.thumbnail.width', $settings['attachment.thumbnail.width']->value ?? 300) }}"
                                           min="50" max="1000">
                                    @if(isset($settings['attachment.thumbnail.width']) && $settings['attachment.thumbnail.width']->description)
                                        <small class="form-text text-muted">{{ $settings['attachment.thumbnail.width']->description }}</small>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="thumbnail_height">
                                        {{ $settings['attachment.thumbnail.height']->label ?? '썸네일 세로 크기' }}
                                        <small class="text-muted">(px)</small>
                                    </label>
                                    <input type="number" 
                                           class="form-control @error('settings.attachment.thumbnail.height') is-invalid @enderror" 
                                           id="thumbnail_height"
                                           name="settings[attachment.thumbnail.height]" 
                                           value="{{ old('settings.attachment.thumbnail.height', $settings['attachment.thumbnail.height']->value ?? 200) }}"
                                           min="50" max="1000">
                                    @if(isset($settings['attachment.thumbnail.height']) && $settings['attachment.thumbnail.height']->description)
                                        <small class="form-text text-muted">{{ $settings['attachment.thumbnail.height']->description }}</small>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="thumbnail_quality">
                                        {{ $settings['attachment.thumbnail.quality']->label ?? '썸네일 품질' }}
                                        <small class="text-muted">(1-100)</small>
                                    </label>
                                    <input type="number" 
                                           class="form-control @error('settings.attachment.thumbnail.quality') is-invalid @enderror" 
                                           id="thumbnail_quality"
                                           name="settings[attachment.thumbnail.quality]" 
                                           value="{{ old('settings.attachment.thumbnail.quality', $settings['attachment.thumbnail.quality']->value ?? 85) }}"
                                           min="1" max="100">
                                    @if(isset($settings['attachment.thumbnail.quality']) && $settings['attachment.thumbnail.quality']->description)
                                        <small class="form-text text-muted">{{ $settings['attachment.thumbnail.quality']->description }}</small>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- WebP 변환 가능 MIME 타입 (고급 설정) -->
                        <div class="mt-4">
                            <h6 class="mb-3">
                                <i class="fas fa-cogs mr-2"></i>
                                고급 설정
                            </h6>
                            
                            <div class="form-group">
                                <label for="webp_convertible_types">{{ $settings['attachment.webp.convertible_types']->label ?? 'WebP 변환 가능한 MIME 타입' }}</label>
                                <textarea class="form-control @error('settings.attachment.webp.convertible_types') is-invalid @enderror" 
                                          id="webp_convertible_types"
                                          name="settings[attachment.webp.convertible_types]" 
                                          rows="3">{{ old('settings.attachment.webp.convertible_types', $settings['attachment.webp.convertible_types']->value ?? '["image/jpeg","image/png"]') }}</textarea>
                                @if(isset($settings['attachment.webp.convertible_types']) && $settings['attachment.webp.convertible_types']->description)
                                    <small class="form-text text-muted">{{ $settings['attachment.webp.convertible_types']->description }}</small>
                                @endif
                                <small class="form-text text-info">JSON 배열 형식으로 입력하세요. 예: ["image/jpeg","image/png"]</small>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-2"></i>설정 저장
                        </button>
                        <button type="button" class="btn btn-secondary ml-2" onclick="location.reload()">
                            <i class="fas fa-times mr-2"></i>취소
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function clearCache() {
    if (confirm('설정 캐시를 클리어하시겠습니까?')) {
        fetch('{{ route("admin.settings.clear-cache") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            location.reload();
        })
        .catch(error => {
            alert('캐시 클리어 중 오류가 발생했습니다.');
        });
    }
}

function resetSettings() {
    if (confirm('모든 설정을 기본값으로 초기화하시겠습니까? 이 작업은 되돌릴 수 없습니다.')) {
        fetch('{{ route("admin.settings.reset") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                group: 'attachment'
            })
        })
        .then(response => response.json())
        .then(data => {
            location.reload();
        })
        .catch(error => {
            alert('설정 초기화 중 오류가 발생했습니다.');
        });
    }
}

// WebP 모드 변경 시 관련 설정 표시/숨김
document.getElementById('webp_mode').addEventListener('change', function() {
    const mode = this.value;
    const autoSettings = document.getElementById('webp_min_size').closest('.col-md-6');
    
    if (mode === 'auto') {
        autoSettings.style.display = 'block';
    } else {
        autoSettings.style.display = 'none';
    }
});

// 페이지 로드 시 초기 상태 설정
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('webp_mode').dispatchEvent(new Event('change'));
});
</script>
@endpush
@endsection