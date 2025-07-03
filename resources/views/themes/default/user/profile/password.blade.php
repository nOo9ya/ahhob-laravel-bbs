@extends('themes.default.layouts.app')

@section('title', '비밀번호 변경')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- 헤더 -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">비밀번호 변경</h1>
            <p class="text-gray-600 mt-1">보안을 위해 정기적으로 비밀번호를 변경해주세요.</p>
        </div>

        <!-- 비밀번호 변경 폼 -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <form method="POST" action="{{ route('profile.password.update') }}" class="space-y-6">
                @csrf
                @method('PUT')

                <!-- 현재 비밀번호 -->
                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">
                        현재 비밀번호
                    </label>
                    <input type="password" 
                           id="current_password" 
                           name="current_password" 
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('current_password') border-red-500 @enderror">
                    @error('current_password')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 새 비밀번호 -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        새 비밀번호
                    </label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('password') border-red-500 @enderror">
                    @error('password')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 새 비밀번호 확인 -->
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                        새 비밀번호 확인
                    </label>
                    <input type="password" 
                           id="password_confirmation" 
                           name="password_confirmation" 
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <!-- 비밀번호 안전 가이드 -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h3 class="text-sm font-medium text-blue-800 mb-2">
                        <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        안전한 비밀번호 만들기
                    </h3>
                    <ul class="text-sm text-blue-700 space-y-1">
                        <li>• 8자 이상의 길이로 설정하세요</li>
                        <li>• 대문자, 소문자, 숫자, 특수문자를 포함하세요</li>
                        <li>• 개인정보(이름, 생일 등)는 사용하지 마세요</li>
                        <li>• 다른 사이트와 동일한 비밀번호는 사용하지 마세요</li>
                    </ul>
                </div>

                <!-- 버튼 -->
                <div class="flex items-center justify-between pt-4">
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        비밀번호 변경
                    </button>
                </div>
            </form>
        </div>

        <!-- 보안 팁 -->
        <div class="mt-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <h3 class="text-sm font-medium text-yellow-800 mb-2">
                <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                보안 팁
            </h3>
            <ul class="text-sm text-yellow-700 space-y-1">
                <li>• 비밀번호는 3-6개월마다 변경하는 것이 좋습니다</li>
                <li>• 공용 컴퓨터에서는 로그아웃을 꼭 하세요</li>
                <li>• 의심스러운 활동이 발견되면 즉시 비밀번호를 변경하세요</li>
            </ul>
        </div>
    </div>
</div>
@endsection