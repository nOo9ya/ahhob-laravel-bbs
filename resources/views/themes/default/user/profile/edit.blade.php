@extends('themes.default.layouts.app')

@section('title', '프로필 수정')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- 헤더 -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">프로필 수정</h1>
            <p class="text-gray-600 mt-1">개인 정보를 수정하고 프로필을 업데이트하세요.</p>
        </div>

        <!-- 프로필 수정 폼 -->
        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- 아바타 섹션 -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">프로필 사진</h2>
                
                <div class="flex items-center space-x-6">
                    <div class="flex-shrink-0">
                        <img class="w-20 h-20 rounded-full object-cover border-4 border-white shadow-lg" 
                             src="{{ $user->profile_image_url }}" 
                             alt="{{ $user->nickname }}"
                             id="avatar-preview">
                    </div>
                    
                    <div class="flex-1">
                        <div class="flex items-center space-x-4">
                            <label for="avatar" class="cursor-pointer inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                사진 선택
                            </label>
                            <input type="file" id="avatar" name="avatar" accept="image/*" class="hidden" onchange="previewAvatar(this)">
                            
                            @if($user->profile_image_path)
                                <a href="{{ route('profile.avatar.delete') }}" 
                                   onclick="return confirm('정말로 아바타를 삭제하시겠습니까?')"
                                   class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">
                                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    삭제
                                </a>
                            @endif
                        </div>
                        <p class="text-sm text-gray-500 mt-2">JPG, PNG, GIF 파일을 업로드하세요. 최대 2MB</p>
                        @error('avatar')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- 기본 정보 -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">기본 정보</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- 실명 -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">실명</label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               value="{{ old('name', $user->name) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('name') border-red-500 @enderror">
                        @error('name')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 닉네임 -->
                    <div>
                        <label for="nickname" class="block text-sm font-medium text-gray-700 mb-2">닉네임</label>
                        <input type="text" 
                               id="nickname" 
                               name="nickname" 
                               value="{{ old('nickname', $user->nickname) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('nickname') border-red-500 @enderror">
                        @error('nickname')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 생년월일 -->
                    <div>
                        <label for="birth_date" class="block text-sm font-medium text-gray-700 mb-2">생년월일</label>
                        <input type="date" 
                               id="birth_date" 
                               name="birth_date" 
                               value="{{ old('birth_date', $user->birth_date?->format('Y-m-d')) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('birth_date') border-red-500 @enderror">
                        @error('birth_date')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 성별 -->
                    <div>
                        <label for="gender" class="block text-sm font-medium text-gray-700 mb-2">성별</label>
                        <select id="gender" 
                                name="gender"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('gender') border-red-500 @enderror">
                            <option value="">선택 안 함</option>
                            <option value="M" {{ old('gender', $user->gender) === 'M' ? 'selected' : '' }}>남성</option>
                            <option value="F" {{ old('gender', $user->gender) === 'F' ? 'selected' : '' }}>여성</option>
                            <option value="O" {{ old('gender', $user->gender) === 'O' ? 'selected' : '' }}>기타</option>
                        </select>
                        @error('gender')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 거주지역 -->
                    <div>
                        <label for="location" class="block text-sm font-medium text-gray-700 mb-2">거주지역</label>
                        <input type="text" 
                               id="location" 
                               name="location" 
                               value="{{ old('location', $user->location) }}"
                               placeholder="예: 서울특별시"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('location') border-red-500 @enderror">
                        @error('location')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 웹사이트 -->
                    <div>
                        <label for="website" class="block text-sm font-medium text-gray-700 mb-2">웹사이트</label>
                        <input type="url" 
                               id="website" 
                               name="website" 
                               value="{{ old('website', $user->website) }}"
                               placeholder="https://example.com"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('website') border-red-500 @enderror">
                        @error('website')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- 자기소개 -->
                <div class="mt-6">
                    <label for="bio" class="block text-sm font-medium text-gray-700 mb-2">자기소개</label>
                    <textarea id="bio" 
                              name="bio" 
                              rows="4" 
                              placeholder="자신에 대해 간단히 소개해주세요..."
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('bio') border-red-500 @enderror">{{ old('bio', $user->bio) }}</textarea>
                    @error('bio')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-sm text-gray-500 mt-1">최대 500자까지 입력 가능합니다.</p>
                </div>
            </div>

            <!-- 버튼 -->
            <div class="flex items-center justify-between">
                <a href="{{ route('profile.show') }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    취소
                </a>
                
                <button type="submit" 
                        class="inline-flex items-center px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    저장하기
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('avatar-preview').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endsection