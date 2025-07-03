<x-ahhob.layouts.app title="게시판">
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- 페이지 헤더 -->
            <div class="mb-8">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-4">게시판</h1>
                <p class="text-gray-600 text-sm sm:text-base">다양한 주제의 게시판에서 소통하고 정보를 공유하세요.</p>
            </div>

            <!-- 게시판 그룹별 목록 -->
            @forelse($boardGroups as $group)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6 overflow-hidden">
                    <!-- 그룹 헤더 -->
                    <div class="bg-gray-50 px-4 sm:px-6 py-3 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">{{ $group->name }}</h2>
                        @if($group->description)
                            <p class="text-sm text-gray-600 mt-1">{{ $group->description }}</p>
                        @endif
                    </div>

                    <!-- 게시판 목록 -->
                    <div class="divide-y divide-gray-200">
                        @forelse($group->boards as $board)
                            <div class="p-4 sm:p-6 hover:bg-gray-50 transition-colors">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                                    <!-- 게시판 정보 -->
                                    <div class="flex-1">
                                        <div class="flex items-start sm:items-center">
                                            <!-- 게시판 아이콘 -->
                                            <div class="flex-shrink-0 mr-3">
                                                @if($board->icon)
                                                    <img src="{{ $board->icon }}" alt="{{ $board->name }}" class="w-8 h-8 rounded">
                                                @else
                                                    <div class="w-8 h-8 bg-blue-100 rounded flex items-center justify-center">
                                                        <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                                                        </svg>
                                                    </div>
                                                @endif
                                            </div>

                                            <!-- 게시판 제목과 설명 -->
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center">
                                                    <a href="{{ route('boards.posts.index', $board->slug) }}" 
                                                       class="text-lg font-medium text-gray-900 hover:text-blue-600 transition-colors">
                                                        {{ $board->name }}
                                                    </a>
                                                    
                                                    <!-- 게시판 권한 표시 -->
                                                    @if($board->permission_read !== 'all')
                                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                                            <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                                            </svg>
                                                            제한
                                                        </span>
                                                    @endif

                                                    <!-- 새 글 표시 -->
                                                    @if($board->has_new_posts ?? false)
                                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                                            NEW
                                                        </span>
                                                    @endif
                                                </div>
                                                
                                                @if($board->description)
                                                    <p class="text-sm text-gray-600 mt-1 line-clamp-2">{{ $board->description }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 통계 정보 -->
                                    <div class="mt-3 sm:mt-0 sm:ml-6 flex items-center space-x-4 text-sm text-gray-500">
                                        <!-- 게시글 수 -->
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                            <span>{{ number_format($board->posts_count ?? 0) }}</span>
                                        </div>

                                        <!-- 오늘 게시글 수 -->
                                        @if(($board->today_posts_count ?? 0) > 0)
                                            <div class="flex items-center text-blue-600">
                                                <span class="text-xs">+{{ $board->today_posts_count }}</span>
                                            </div>
                                        @endif

                                        <!-- 화살표 아이콘 -->
                                        <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="p-6 text-center text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                                </svg>
                                <p>이 그룹에는 아직 게시판이 없습니다.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            @empty
                <!-- 게시판이 없을 때 -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                    <svg class="mx-auto h-16 w-16 text-gray-300 mb-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">게시판이 없습니다</h3>
                    <p class="text-gray-600 mb-6">아직 생성된 게시판이 없습니다.</p>
                    
                    @auth('admin')
                        <a href="{{ route('admin.dashboard') }}" 
                           class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            게시판 생성하기
                        </a>
                    @endauth
                </div>
            @endforelse

            <!-- 사용자 가이드 (모바일에서는 접기 가능) -->
            <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-4 sm:p-6">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">이용 안내</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <ul class="space-y-1">
                                <li>• 각 게시판을 클릭하여 게시글을 확인하고 참여하실 수 있습니다.</li>
                                <li>• 게시글 작성은 로그인 후 이용 가능합니다.</li>
                                <li>• 일부 게시판은 특정 권한이 필요할 수 있습니다.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-ahhob.layouts.app>