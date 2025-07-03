@extends('themes.default.layouts.app')

@section('title', '내 프로필')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- 프로필 헤더 -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
            <div class="p-6">
                <div class="flex flex-col sm:flex-row items-start sm:items-center space-y-4 sm:space-y-0 sm:space-x-6">
                    <!-- 아바타 -->
                    <div class="flex-shrink-0">
                        <img class="w-24 h-24 rounded-full object-cover border-4 border-white shadow-lg" 
                             src="{{ $user->profile_image_url }}" 
                             alt="{{ $user->nickname }}">
                    </div>
                    
                    <!-- 사용자 정보 -->
                    <div class="flex-1 min-w-0">
                        <h1 class="text-2xl font-bold text-gray-900">{{ $user->nickname }}</h1>
                        @if($user->name)
                            <p class="text-gray-600">{{ $user->name }}</p>
                        @endif
                        @if($user->bio)
                            <p class="mt-2 text-gray-700">{{ $user->bio }}</p>
                        @endif
                        
                        <!-- 추가 정보 -->
                        <div class="mt-3 flex flex-wrap gap-4 text-sm text-gray-500">
                            @if($user->location)
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ $user->location }}
                                </span>
                            @endif
                            @if($user->website)
                                <a href="{{ $user->website }}" target="_blank" class="flex items-center hover:text-blue-600">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4.083 9h1.946c.089-1.546.383-2.97.837-4.118A6.004 6.004 0 004.083 9zM10 2a8 8 0 100 16 8 8 0 000-16zm0 2c-.076 0-.232.032-.465.262-.238.234-.497.623-.737 1.182-.389.907-.673 2.142-.766 3.556h3.936c-.093-1.414-.377-2.649-.766-3.556-.24-.56-.5-.948-.737-1.182C10.232 4.032 10.076 4 10 4zm3.971 5c-.089-1.546-.383-2.97-.837-4.118A6.004 6.004 0 0115.917 9h-1.946zm-2.003 2H8.032c.093 1.414.377 2.649.766 3.556.24.56.5.948.737 1.182.233.23.389.262.465.262.076 0 .232-.032.465-.262.238-.234.498-.623.737-1.182.389-.907.673-2.142.766-3.556zm1.166 4.118c.454-1.147.748-2.572.837-4.118h1.946a6.004 6.004 0 01-2.783 4.118zm-6.268 0C6.412 13.97 6.118 12.546 6.03 11H4.083a6.004 6.004 0 002.783 4.118z" clip-rule="evenodd"/>
                                    </svg>
                                    웹사이트
                                </a>
                            @endif
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                                </svg>
                                {{ $stats['join_days'] }}일 전 가입
                            </span>
                        </div>
                    </div>
                    
                    <!-- 수정 버튼 -->
                    <div class="flex space-x-3">
                        <a href="{{ route('profile.edit') }}" 
                           class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            프로필 수정
                        </a>
                        <a href="{{ route('profile.password') }}" 
                           class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-lg transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            비밀번호 변경
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- 통계 정보 -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">활동 통계</h2>
                <div class="space-y-4">
                    <div class="flex items-center justify-between py-2 border-b border-gray-100">
                        <span class="text-gray-600">레벨</span>
                        <div class="text-right">
                            <span class="font-semibold text-gray-900">{{ $user->level }}</span>
                            <span class="text-sm text-blue-600 ml-1">({{ $user->level_name }})</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-gray-100">
                        <span class="text-gray-600">포인트</span>
                        <span class="font-semibold text-gray-900">{{ number_format($user->points) }}P</span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-gray-100">
                        <span class="text-gray-600">작성한 글</span>
                        <span class="font-semibold text-gray-900">{{ number_format($stats['total_posts']) }}개</span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-gray-100">
                        <span class="text-gray-600">작성한 댓글</span>
                        <span class="font-semibold text-gray-900">{{ number_format($stats['total_comments']) }}개</span>
                    </div>
                    <div class="flex items-center justify-between py-2">
                        <span class="text-gray-600">가입일</span>
                        <span class="font-semibold text-gray-900">{{ $user->created_at->format('Y.m.d') }}</span>
                    </div>
                </div>
            </div>

            <!-- 최근 활동 -->
            <div class="lg:col-span-2 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">최근 작성한 글</h2>
                @if(count($recentPosts) > 0)
                    <div class="space-y-3">
                        @foreach($recentPosts as $post)
                            <div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-0">
                                <div class="flex-1 min-w-0">
                                    <a href="{{ route('boards.posts.show', [$post['board_slug'], $post['id']]) }}" 
                                       class="block hover:text-blue-600 transition-colors">
                                        <h3 class="font-medium text-gray-900 truncate">{{ $post['title'] }}</h3>
                                        <p class="text-sm text-gray-500 mt-1">{{ $post['board_name'] }}</p>
                                    </a>
                                </div>
                                <div class="text-sm text-gray-500 ml-4">
                                    {{ $post['created_at']->diffForHumans() }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p class="text-gray-500">아직 작성한 글이 없습니다.</p>
                        <a href="{{ route('boards.index') }}" class="text-blue-600 hover:text-blue-700 text-sm mt-2 inline-block">
                            게시판 둘러보기
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection