@extends('themes.default.layouts.app')

@section('title', '접근 권한이 없습니다')

@section('content')
<div class="min-h-screen bg-gray-50 flex flex-col justify-center items-center px-4">
    <div class="text-center">
        <!-- 403 아이콘 -->
        <div class="mb-8">
            <svg class="mx-auto h-24 w-24 text-red-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
        </div>

        <!-- 403 텍스트 -->
        <h1 class="text-6xl font-bold text-gray-900 mb-4">403</h1>
        <h2 class="text-2xl font-semibold text-gray-700 mb-4">접근 권한이 없습니다</h2>
        <p class="text-gray-600 mb-8 max-w-md mx-auto">
            이 페이지에 접근할 권한이 없습니다.<br>
            로그인이 필요하거나 관리자 권한이 필요한 페이지일 수 있습니다.
        </p>

        <!-- 액션 버튼들 -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            @guest('web')
                <a href="{{ route('login') }}" 
                   class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                    </svg>
                    로그인
                </a>
            @endguest
            
            <a href="{{ route('home') }}" 
               class="inline-flex items-center px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                홈으로 가기
            </a>
            
            <button onclick="history.back()" 
                    class="inline-flex items-center px-6 py-3 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 font-medium rounded-lg transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                이전 페이지
            </button>
        </div>

        <!-- 안내 메시지 -->
        <div class="mt-12 bg-blue-50 border border-blue-200 rounded-lg p-6 max-w-md mx-auto">
            <h3 class="text-sm font-medium text-blue-800 mb-2">접근 권한에 대한 안내</h3>
            <div class="text-sm text-blue-700">
                <p class="mb-2">• 회원 전용 페이지는 로그인 후 이용하실 수 있습니다</p>
                <p class="mb-2">• 관리자 전용 페이지는 관리자만 접근 가능합니다</p>
                <p>• 권한이 필요한 경우 관리자에게 문의해주세요</p>
            </div>
        </div>
    </div>
</div>
@endsection
