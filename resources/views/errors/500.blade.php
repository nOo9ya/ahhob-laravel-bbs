@extends('themes.default.layouts.app')

@section('title', '서버 오류가 발생했습니다')

@section('content')
<div class="min-h-screen bg-gray-50 flex flex-col justify-center items-center px-4">
    <div class="text-center">
        <!-- 500 아이콘 -->
        <div class="mb-8">
            <svg class="mx-auto h-24 w-24 text-yellow-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.084 16.5c-.77.833.192 2.5 1.732 2.5z"/>
            </svg>
        </div>

        <!-- 500 텍스트 -->
        <h1 class="text-6xl font-bold text-gray-900 mb-4">500</h1>
        <h2 class="text-2xl font-semibold text-gray-700 mb-4">서버 오류가 발생했습니다</h2>
        <p class="text-gray-600 mb-8 max-w-md mx-auto">
            일시적인 서버 문제로 인해 요청을 처리할 수 없습니다.<br>
            잠시 후 다시 시도해주세요.
        </p>

        <!-- 액션 버튼들 -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <button onclick="location.reload()" 
                    class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                새로고침
            </button>
            
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

        <!-- 문제 해결 팁 -->
        <div class="mt-12 bg-yellow-50 border border-yellow-200 rounded-lg p-6 max-w-md mx-auto">
            <h3 class="text-sm font-medium text-yellow-800 mb-2">
                <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                문제 해결 방법
            </h3>
            <div class="text-sm text-yellow-700">
                <p class="mb-2">• 페이지를 새로고침해보세요</p>
                <p class="mb-2">• 몇 분 후 다시 시도해보세요</p>
                <p class="mb-2">• 브라우저 캐시를 삭제해보세요</p>
                <p>• 문제가 계속되면 관리자에게 문의해주세요</p>
            </div>
        </div>

        <!-- 기술 지원 -->
        <div class="mt-8 text-center">
            <p class="text-sm text-gray-500 mb-2">문제가 지속될 경우</p>
            <a href="mailto:support@ahhob.com" class="text-blue-600 hover:text-blue-700 text-sm">
                기술지원팀에 문의하기
            </a>
        </div>
    </div>
</div>
@endsection
