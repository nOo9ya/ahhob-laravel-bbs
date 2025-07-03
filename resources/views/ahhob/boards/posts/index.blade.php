@extends('ahhob.layouts.app')

@section('title', $board->name)

@section('content')
    <div class="min-h-screen bg-gray-50">
        <!-- 게시판 헤더 -->
        <div class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <!-- 브레드크럼 -->
                <nav class="flex mb-4" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1 md:space-x-3">
                        <li class="inline-flex items-center">
                            <a href="{{ route('home') }}" class="text-gray-500 hover:text-gray-700 text-sm">
                                홈
                            </a>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <svg class="w-4 h-4 text-gray-400 mx-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <a href="{{ route('boards.index') }}" class="text-gray-500 hover:text-gray-700 text-sm">
                                    게시판
                                </a>
                            </div>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <svg class="w-4 h-4 text-gray-400 mx-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="text-gray-700 text-sm font-medium">{{ $board->name }}</span>
                            </div>
                        </li>
                    </ol>
                </nav>

                <!-- 게시판 정보 -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center">
                        @if($board->icon)
                            <img src="{{ $board->icon }}" alt="{{ $board->name }}" class="w-10 h-10 rounded mr-3">
                        @else
                            <div class="w-10 h-10 bg-blue-100 rounded flex items-center justify-center mr-3">
                                <svg class="w-6 h-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                                </svg>
                            </div>
                        @endif
                        <div>
                            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">{{ $board->name }}</h1>
                            @if($board->description)
                                <p class="text-gray-600 text-sm mt-1">{{ $board->description }}</p>
                            @endif
                        </div>
                    </div>

                    <!-- 게시글 작성 버튼 -->
                    <div class="mt-4 sm:mt-0">
                        @auth('web')
                            <a href="{{ route('boards.posts.create', $board->slug) }}" 
                               class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                글쓰기
                            </a>
                        @else
                            <a href="{{ route('login') }}" 
                               class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-lg transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                                </svg>
                                로그인
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
        </div>

        <!-- 게시글 목록 -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- 검색 및 필터 -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
                <form method="GET" class="flex flex-col sm:flex-row gap-4">
                    <!-- 검색어 -->
                    <div class="flex-1">
                        <input type="text" 
                               name="search" 
                               value="{{ request('search') }}"
                               placeholder="제목, 내용으로 검색..."
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <!-- 카테고리 필터 -->
                    @if($board->categories)
                        <div class="sm:w-40">
                            <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">전체 카테고리</option>
                                @foreach($board->categories as $category)
                                    <option value="{{ $category }}" {{ request('category') === $category ? 'selected' : '' }}>
                                        {{ $category }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    
                    <!-- 검색 버튼 -->
                    <button type="submit" 
                            class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                        <svg class="w-4 h-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        검색
                    </button>
                </form>
            </div>

            <!-- 게시글 테이블 (데스크톱) -->
            <div class="hidden md:block bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                제목
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                작성자
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                작성일
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                조회
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($posts as $post)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <!-- 제목 -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <!-- 공지사항 아이콘 -->
                                        @if($post->is_notice)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 mr-2">
                                                공지
                                            </span>
                                        @endif
                                        
                                        <!-- 첨부파일 아이콘 -->
                                        @if($post->attachment_count > 0)
                                            <svg class="w-4 h-4 text-gray-400 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                            </svg>
                                        @endif
                                        
                                        <a href="{{ route('boards.posts.show', [$board->slug, $post->id]) }}" 
                                           class="text-gray-900 hover:text-blue-600 font-medium">
                                            {{ $post->title }}
                                        </a>
                                        
                                        <!-- 댓글 수 -->
                                        @if($post->comment_count > 0)
                                            <span class="ml-1 text-blue-600 text-sm">[{{ $post->comment_count }}]</span>
                                        @endif
                                        
                                        <!-- 새 글 표시 -->
                                        @if($post->created_at->diffInHours() < 24)
                                            <span class="ml-1 text-red-500 text-xs">NEW</span>
                                        @endif
                                    </div>
                                </td>
                                
                                <!-- 작성자 -->
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $post->author_name ?? ($post->user ? $post->user->nickname : '익명') }}
                                </td>
                                
                                <!-- 작성일 -->
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $post->created_at->format('Y.m.d') }}
                                </td>
                                
                                <!-- 조회수 -->
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ number_format($post->view_count) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                    <svg class="mx-auto h-12 w-12 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <p>게시글이 없습니다.</p>
                                    @auth('web')
                                        <p class="text-sm mt-2">첫 번째 게시글을 작성해보세요!</p>
                                    @endauth
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- 게시글 카드 (모바일) -->
            <div class="md:hidden space-y-4">
                @forelse($posts as $post)
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                        <div class="flex items-start justify-between mb-2">
                            <div class="flex items-center space-x-2">
                                @if($post->is_notice)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                        공지
                                    </span>
                                @endif
                                @if($post->attachment_count > 0)
                                    <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                    </svg>
                                @endif
                                @if($post->created_at->diffInHours() < 24)
                                    <span class="text-red-500 text-xs font-medium">NEW</span>
                                @endif
                            </div>
                            <span class="text-xs text-gray-500">{{ $post->created_at->format('m.d') }}</span>
                        </div>
                        
                        <a href="{{ route('boards.posts.show', [$board->slug, $post->id]) }}" 
                           class="block">
                            <h3 class="font-medium text-gray-900 mb-2 line-clamp-2">
                                {{ $post->title }}
                                @if($post->comment_count > 0)
                                    <span class="text-blue-600 text-sm">[{{ $post->comment_count }}]</span>
                                @endif
                            </h3>
                        </a>
                        
                        <div class="flex items-center justify-between text-sm text-gray-500">
                            <span>{{ $post->author_name ?? ($post->user ? $post->user->nickname : '익명') }}</span>
                            <span>조회 {{ number_format($post->view_count) }}</span>
                        </div>
                    </div>
                @empty
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p class="text-gray-500">게시글이 없습니다.</p>
                        @auth('web')
                            <p class="text-sm text-gray-400 mt-2">첫 번째 게시글을 작성해보세요!</p>
                        @endauth
                    </div>
                @endforelse
            </div>

            <!-- 페이지네이션 -->
            @if($posts->hasPages())
                <div class="mt-6">
                    {{ $posts->appends(request()->query())->links('pagination::tailwind') }}
                </div>
            @endif
        </div>
    </div>
@endsection