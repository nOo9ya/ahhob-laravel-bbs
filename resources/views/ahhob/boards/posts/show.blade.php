<x-ahhob.layouts.app :title="$post->title">
    <div class="min-h-screen bg-gray-50">
        <!-- 게시판 헤더 -->
        <div class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <!-- 브레드크럼 -->
                <nav class="flex mb-4" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1 md:space-x-3">
                        <li class="inline-flex items-center">
                            <a href="{{ route('home') }}" class="text-gray-500 hover:text-gray-700 text-sm">홈</a>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <svg class="w-4 h-4 text-gray-400 mx-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <a href="{{ route('boards.index') }}" class="text-gray-500 hover:text-gray-700 text-sm">게시판</a>
                            </div>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <svg class="w-4 h-4 text-gray-400 mx-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <a href="{{ route('boards.posts.index', $board->slug) }}" class="text-gray-500 hover:text-gray-700 text-sm">{{ $board->name }}</a>
                            </div>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <svg class="w-4 h-4 text-gray-400 mx-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="text-gray-700 text-sm font-medium">{{ Str::limit($post->title, 30) }}</span>
                            </div>
                        </li>
                    </ol>
                </nav>

                <!-- 게시글 네비게이션 -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                    <a href="{{ route('boards.posts.index', $board->slug) }}" 
                       class="inline-flex items-center text-gray-600 hover:text-gray-900 text-sm">
                        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        목록으로
                    </a>

                    <!-- 게시글 관리 버튼 -->
                    @auth('web')
                        @if(auth('web')->user()->id === $post->user_id || auth('web')->user()->can('manage', $board))
                            <div class="flex items-center space-x-2 mt-2 sm:mt-0">
                                <a href="{{ route('boards.posts.edit', [$board->slug, $post->id]) }}" 
                                   class="inline-flex items-center px-3 py-1.5 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                                    <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    수정
                                </a>
                                <form method="POST" action="{{ route('boards.posts.destroy', [$board->slug, $post->id]) }}" 
                                      onsubmit="return confirm('정말 삭제하시겠습니까?')" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="inline-flex items-center px-3 py-1.5 text-sm bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
                                        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                        삭제
                                    </button>
                                </form>
                            </div>
                        @endif
                    @endauth
                </div>
            </div>
        </div>

        <!-- 게시글 내용 -->
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <article class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <!-- 게시글 헤더 -->
                <div class="border-b border-gray-200 p-6">
                    <!-- 제목 -->
                    <div class="flex items-start justify-between mb-4">
                        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 flex-1 pr-4">
                            @if($post->is_notice)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 mr-2">
                                    공지
                                </span>
                            @endif
                            {{ $post->title }}
                        </h1>
                    </div>

                    <!-- 게시글 메타 정보 -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between text-sm text-gray-600">
                        <div class="flex items-center space-x-4 mb-2 sm:mb-0">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                <span>{{ $post->user_nickname ?? $post->user->nickname }}</span>
                            </div>
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>{{ $post->created_at->format('Y.m.d H:i') }}</span>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <span>{{ number_format($post->views) }}</span>
                            </div>
                            @if($post->comments_count > 0)
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                    </svg>
                                    <span>{{ number_format($post->comments_count) }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- 게시글 본문 -->
                <div class="p-6">
                    <div class="prose prose-sm sm:prose max-w-none">
                        {!! nl2br(e($post->content)) !!}
                    </div>

                    <!-- 첨부파일 -->
                    @if($post->attachments && $post->attachments->count() > 0)
                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <h3 class="text-sm font-medium text-gray-900 mb-3">첨부파일</h3>
                            <div class="space-y-2">
                                @foreach($post->attachments as $attachment)
                                    <div class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                        <svg class="w-5 h-5 text-gray-400 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                        </svg>
                                        <div class="flex-1 min-w-0">
                                            <a href="{{ $attachment->url }}" 
                                               class="text-sm font-medium text-blue-600 hover:text-blue-800 truncate block"
                                               download="{{ $attachment->original_name }}">
                                                {{ $attachment->original_name }}
                                            </a>
                                            <p class="text-xs text-gray-500">{{ $attachment->human_file_size }}</p>
                                        </div>
                                        <a href="{{ $attachment->url }}" 
                                           class="ml-3 text-blue-600 hover:text-blue-800"
                                           download="{{ $attachment->original_name }}">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- 좋아요/스크랩 버튼 -->
                    @auth('web')
                        <div class="mt-6 pt-6 border-t border-gray-200 flex items-center justify-center space-x-4">
                            <button type="button" 
                                    onclick="toggleLike({{ $post->id }})"
                                    class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                                    id="like-button">
                                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                </svg>
                                <span id="like-text">좋아요</span>
                                <span id="like-count" class="ml-1">({{ $post->likes_count ?? 0 }})</span>
                            </button>
                            
                            <button type="button" 
                                    onclick="toggleScrap({{ $post->id }})"
                                    class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                                    id="scrap-button">
                                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                                </svg>
                                <span id="scrap-text">스크랩</span>
                                <span id="scrap-count" class="ml-1">({{ $post->scraps_count ?? 0 }})</span>
                            </button>
                        </div>
                    @endauth
                </div>
            </article>

            <!-- 댓글 섹션 -->
            <div class="mt-8">
                <!-- 댓글 작성 폼 -->
                @auth('web')
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">댓글 작성</h3>
                        <form method="POST" action="{{ route('boards.comments.store', [$board->slug, $post->id]) }}">
                            @csrf
                            <div class="mb-4">
                                <textarea name="content" 
                                          rows="4" 
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                                          placeholder="댓글을 입력하세요..."
                                          required></textarea>
                            </div>
                            <div class="flex justify-end">
                                <button type="submit" 
                                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                                    댓글 등록
                                </button>
                            </div>
                        </form>
                    </div>
                @else
                    <div class="bg-gray-50 rounded-lg border border-gray-200 p-6 mb-6 text-center">
                        <p class="text-gray-600 mb-4">댓글을 작성하려면 로그인이 필요합니다.</p>
                        <a href="{{ route('login') }}" 
                           class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                            로그인
                        </a>
                    </div>
                @endauth

                <!-- 댓글 목록 -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">
                            댓글 <span class="text-blue-600">({{ $post->comments_count ?? 0 }})</span>
                        </h3>
                    </div>
                    
                    @if($post->comments && $post->comments->count() > 0)
                        <div class="divide-y divide-gray-200">
                            @foreach($post->comments as $comment)
                                <div class="p-6" style="margin-left: {{ $comment->depth * 20 }}px;">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0">
                                            <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center">
                                                <svg class="w-4 h-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                </svg>
                                            </div>
                                        </div>
                                        <div class="ml-3 flex-1">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-2">
                                                    <span class="text-sm font-medium text-gray-900">{{ $comment->user_nickname ?? $comment->user->nickname }}</span>
                                                    <span class="text-xs text-gray-500">{{ $comment->created_at->diffForHumans() }}</span>
                                                </div>
                                                @auth('web')
                                                    @if(auth('web')->user()->id === $comment->user_id)
                                                        <div class="flex items-center space-x-2">
                                                            <button type="button" 
                                                                    onclick="editComment({{ $comment->id }})"
                                                                    class="text-xs text-blue-600 hover:text-blue-800">
                                                                수정
                                                            </button>
                                                            <form method="POST" 
                                                                  action="{{ route('boards.comments.destroy', [$board->slug, $post->id, $comment->id]) }}" 
                                                                  onsubmit="return confirm('정말 삭제하시겠습니까?')" 
                                                                  class="inline">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="text-xs text-red-600 hover:text-red-800">
                                                                    삭제
                                                                </button>
                                                            </form>
                                                        </div>
                                                    @endif
                                                @endauth
                                            </div>
                                            <div class="mt-2">
                                                <p class="text-sm text-gray-700">{{ $comment->content }}</p>
                                            </div>
                                            @auth('web')
                                                <div class="mt-2">
                                                    <button type="button" 
                                                            onclick="toggleReplyForm({{ $comment->id }})"
                                                            class="text-xs text-gray-500 hover:text-gray-700">
                                                        답글
                                                    </button>
                                                </div>
                                                
                                                <!-- 답글 작성 폼 -->
                                                <div id="reply-form-{{ $comment->id }}" class="hidden mt-3">
                                                    <form method="POST" action="{{ route('boards.comments.store', [$board->slug, $post->id]) }}">
                                                        @csrf
                                                        <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                                                        <div class="mb-2">
                                                            <textarea name="content" 
                                                                      rows="2" 
                                                                      class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                                                                      placeholder="답글을 입력하세요..."
                                                                      required></textarea>
                                                        </div>
                                                        <div class="flex justify-end space-x-2">
                                                            <button type="button" 
                                                                    onclick="toggleReplyForm({{ $comment->id }})"
                                                                    class="px-3 py-1 text-xs text-gray-600 hover:text-gray-800">
                                                                취소
                                                            </button>
                                                            <button type="submit" 
                                                                    class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded transition-colors">
                                                                등록
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            @endauth
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-12 text-center text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                            </svg>
                            <p>아직 댓글이 없습니다.</p>
                            @auth('web')
                                <p class="text-sm mt-2">첫 번째 댓글을 작성해보세요!</p>
                            @endauth
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // 답글 폼 토글
        function toggleReplyForm(commentId) {
            const form = document.getElementById(`reply-form-${commentId}`);
            form.classList.toggle('hidden');
        }

        // 좋아요 토글
        function toggleLike(postId) {
            fetch(`/api/posts/${postId}/like`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                const button = document.getElementById('like-button');
                const text = document.getElementById('like-text');
                const count = document.getElementById('like-count');
                
                if (data.liked) {
                    button.classList.add('bg-red-50', 'border-red-300', 'text-red-700');
                    button.classList.remove('bg-white', 'text-gray-700');
                    text.textContent = '좋아요 취소';
                } else {
                    button.classList.remove('bg-red-50', 'border-red-300', 'text-red-700');
                    button.classList.add('bg-white', 'text-gray-700');
                    text.textContent = '좋아요';
                }
                count.textContent = `(${data.count})`;
            })
            .catch(error => {
                console.error('Error:', error);
                alert('오류가 발생했습니다.');
            });
        }

        // 스크랩 토글
        function toggleScrap(postId) {
            fetch(`/api/posts/${postId}/scrap`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                const button = document.getElementById('scrap-button');
                const text = document.getElementById('scrap-text');
                const count = document.getElementById('scrap-count');
                
                if (data.scraped) {
                    button.classList.add('bg-yellow-50', 'border-yellow-300', 'text-yellow-700');
                    button.classList.remove('bg-white', 'text-gray-700');
                    text.textContent = '스크랩 취소';
                } else {
                    button.classList.remove('bg-yellow-50', 'border-yellow-300', 'text-yellow-700');
                    button.classList.add('bg-white', 'text-gray-700');
                    text.textContent = '스크랩';
                }
                count.textContent = `(${data.count})`;
            })
            .catch(error => {
                console.error('Error:', error);
                alert('오류가 발생했습니다.');
            });
        }
    </script>
</x-ahhob.layouts.app>