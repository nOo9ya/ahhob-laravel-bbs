@extends('themes.default.layouts.app')

@section('title', '사이트맵 관리')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- 헤더 -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">사이트맵 관리</h1>
            <p class="text-gray-600 mt-1">검색엔진 최적화를 위한 사이트맵과 robots.txt를 관리합니다.</p>
        </div>

        <!-- 현재 상태 -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">현재 상태</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div>
                        <h3 class="font-medium text-gray-900">사이트맵 파일</h3>
                        <p class="text-sm text-gray-600">sitemap.xml</p>
                    </div>
                    <div class="text-right">
                        @if($sitemapExists)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                생성됨
                            </span>
                            <p class="text-xs text-gray-500 mt-1">{{ $sitemapLastGenerated }}</p>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                없음
                            </span>
                        @endif
                    </div>
                </div>

                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div>
                        <h3 class="font-medium text-gray-900">접근 가능한 URL</h3>
                        <p class="text-sm text-gray-600">
                            @if($sitemapExists)
                                <a href="{{ url('storage/sitemap.xml') }}" target="_blank" class="text-blue-600 hover:text-blue-700">
                                    {{ url('storage/sitemap.xml') }}
                                </a>
                            @else
                                사이트맵을 먼저 생성해주세요
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 액션 버튼들 -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">관리 작업</h2>
            
            <div class="flex flex-wrap gap-4">
                <form method="POST" action="{{ route('admin.sitemap.generate') }}" class="inline">
                    @csrf
                    <button type="submit" 
                            class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        @if($sitemapExists) 사이트맵 재생성 @else 사이트맵 생성 @endif
                    </button>
                </form>

                @if($sitemapExists)
                    <a href="{{ route('admin.sitemap.download') }}" 
                       class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-4-4m4 4l4-4m-6 0H8a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-8a2 2 0 00-2-2h-4z"/>
                        </svg>
                        다운로드
                    </a>

                    <form method="POST" action="{{ route('admin.sitemap.delete') }}" class="inline" 
                          onsubmit="return confirm('정말로 사이트맵을 삭제하시겠습니까?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            삭제
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <!-- 사이트맵 정보 -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">사이트맵 정보</h2>
            
            <div class="space-y-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h3 class="text-sm font-medium text-blue-800 mb-2">포함되는 페이지</h3>
                    <ul class="text-sm text-blue-700 space-y-1">
                        <li>• 홈페이지 (우선순위: 1.0)</li>
                        <li>• 게시판 목록 페이지 (우선순위: 0.9)</li>
                        <li>• 각 게시판 페이지 (우선순위: 0.8)</li>
                        <li>• 최신 게시글 100개 (우선순위: 0.6)</li>
                    </ul>
                </div>

                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <h3 class="text-sm font-medium text-yellow-800 mb-2">자동 제외 페이지</h3>
                    <ul class="text-sm text-yellow-700 space-y-1">
                        <li>• 관리자 페이지 (/admin/*)</li>
                        <li>• 사용자 프로필 페이지 (/profile/*)</li>
                        <li>• API 엔드포인트 (/api/*)</li>
                    </ul>
                </div>

                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <h3 class="text-sm font-medium text-green-800 mb-2">파일 생성</h3>
                    <ul class="text-sm text-green-700 space-y-1">
                        <li>• <strong>sitemap.xml</strong>: 검색엔진을 위한 사이트맵</li>
                        <li>• <strong>robots.txt</strong>: 검색엔진 크롤링 규칙</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection