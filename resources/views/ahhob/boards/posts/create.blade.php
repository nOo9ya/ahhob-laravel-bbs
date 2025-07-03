<x-ahhob.layouts.app title="게시글 작성">
    <div class="min-h-screen bg-gray-50">
        <!-- 페이지 헤더 -->
        <div class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
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
                                <span class="text-gray-700 text-sm font-medium">글쓰기</span>
                            </div>
                        </li>
                    </ol>
                </nav>

                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">게시글 작성</h1>
                    <a href="{{ route('boards.posts.index', $board->slug) }}" 
                       class="mt-4 sm:mt-0 inline-flex items-center text-gray-600 hover:text-gray-900 text-sm">
                        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        목록으로
                    </a>
                </div>
            </div>
        </div>

        <!-- 작성 폼 -->
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <form method="POST" action="{{ route('boards.posts.store', $board->slug) }}" enctype="multipart/form-data" id="post-form">
                @csrf
                
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <div class="p-6 space-y-6">
                        <!-- 제목 -->
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                                제목 <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   name="title" 
                                   id="title"
                                   value="{{ old('title') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('title') border-red-300 @enderror"
                                   placeholder="제목을 입력하세요"
                                   required>
                            @error('title')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- 카테고리 (있는 경우만) -->
                        @if($board->categories)
                            <div>
                                <label for="category" class="block text-sm font-medium text-gray-700 mb-2">카테고리</label>
                                <select name="category" 
                                        id="category"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <option value="">카테고리 선택</option>
                                    @foreach($board->categories as $category)
                                        <option value="{{ $category }}" {{ old('category') === $category ? 'selected' : '' }}>
                                            {{ $category }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <!-- 공지사항 설정 (관리자만) -->
                        @if(auth('web')->user()->can('manage', $board))
                            <div>
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           name="is_notice" 
                                           value="1" 
                                           {{ old('is_notice') ? 'checked' : '' }}
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <span class="ml-2 text-sm font-medium text-gray-700">공지사항으로 등록</span>
                                </label>
                            </div>
                        @endif

                        <!-- 내용 (ToastUI Editor) -->
                        <div>
                            <label for="content" class="block text-sm font-medium text-gray-700 mb-2">
                                내용 <span class="text-red-500">*</span>
                            </label>
                            <div id="editor"></div>
                            <input type="hidden" name="content" id="content" value="{{ old('content') }}">
                            @error('content')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- 첨부파일 -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">첨부파일</label>
                            <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-gray-400 transition-colors">
                                <div class="space-y-1 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <div class="flex text-sm text-gray-600">
                                        <label for="attachments" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                            <span>파일 선택</span>
                                            <input id="attachments" 
                                                   name="attachments[]" 
                                                   type="file" 
                                                   class="sr-only" 
                                                   multiple
                                                   accept="image/*,.pdf,.doc,.docx,.txt,.zip,.rar">
                                        </label>
                                        <p class="pl-1">또는 드래그 앤 드롭</p>
                                    </div>
                                    <p class="text-xs text-gray-500">PNG, JPG, PDF, DOC, TXT 파일 (최대 10MB)</p>
                                </div>
                            </div>
                            <div id="file-list" class="mt-4 space-y-2"></div>
                        </div>
                    </div>

                    <!-- 버튼 -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex flex-col sm:flex-row sm:justify-end space-y-2 sm:space-y-0 sm:space-x-3">
                        <a href="{{ route('boards.posts.index', $board->slug) }}" 
                           class="w-full sm:w-auto inline-flex justify-center items-center px-6 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                            취소
                        </a>
                        <button type="button"
                                onclick="saveAsDraft()" 
                                class="w-full sm:w-auto inline-flex justify-center items-center px-6 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors">
                            임시저장
                        </button>
                        <button type="submit" 
                                class="w-full sm:w-auto inline-flex justify-center items-center px-6 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                            게시글 등록
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- ToastUI Editor CSS -->
    <link rel="stylesheet" href="https://uicdn.toast.com/editor/latest/toastui-editor.min.css" />
    <link rel="stylesheet" href="https://uicdn.toast.com/editor/latest/theme/toastui-editor-dark.min.css" />

    <!-- ToastUI Editor JavaScript -->
    <script src="https://uicdn.toast.com/editor/latest/toastui-editor-all.min.js"></script>

    <script>
        // ToastUI Editor 초기화
        const { Editor } = toastui;

        const editor = new Editor({
            el: document.querySelector('#editor'),
            height: '400px',
            initialEditType: 'markdown',
            previewStyle: 'vertical',
            initialValue: document.querySelector('#content').value || '',
            language: 'ko-KR',
            usageStatistics: false,
            toolbarItems: [
                ['heading', 'bold', 'italic', 'strike'],
                ['hr', 'quote'],
                ['ul', 'ol', 'task', 'indent', 'outdent'],
                ['table', 'image', 'link'],
                ['code', 'codeblock'],
                ['scrollSync']
            ],
            hooks: {
                addImageBlobHook: (blob, callback) => {
                    // 이미지 업로드 처리
                    const formData = new FormData();
                    formData.append('image', blob);
                    formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

                    fetch('/api/upload/image', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            callback(data.url, blob.name || 'image');
                        } else {
                            alert('이미지 업로드에 실패했습니다.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('이미지 업로드 중 오류가 발생했습니다.');
                    });
                }
            }
        });

        // 폼 제출 시 에디터 내용을 hidden input에 저장
        document.getElementById('post-form').addEventListener('submit', function() {
            document.getElementById('content').value = editor.getMarkdown();
        });

        // 파일 업로드 처리
        const fileInput = document.getElementById('attachments');
        const fileList = document.getElementById('file-list');

        fileInput.addEventListener('change', function(e) {
            handleFiles(e.target.files);
        });

        function handleFiles(files) {
            fileList.innerHTML = '';
            Array.from(files).forEach((file, index) => {
                const fileItem = document.createElement('div');
                fileItem.className = 'flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200';
                fileItem.innerHTML = `
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                        </svg>
                        <div>
                            <div class="text-sm font-medium text-gray-900">${file.name}</div>
                            <div class="text-xs text-gray-500">${formatFileSize(file.size)}</div>
                        </div>
                    </div>
                    <button type="button" onclick="removeFile(${index})" class="text-red-600 hover:text-red-800">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                `;
                fileList.appendChild(fileItem);
            });
        }

        function removeFile(index) {
            const dt = new DataTransfer();
            const files = fileInput.files;
            
            for (let i = 0; i < files.length; i++) {
                if (i !== index) {
                    dt.items.add(files[i]);
                }
            }
            
            fileInput.files = dt.files;
            handleFiles(fileInput.files);
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // 임시저장 기능
        function saveAsDraft() {
            const title = document.getElementById('title').value;
            const content = editor.getMarkdown();
            
            if (!title && !content) {
                alert('제목이나 내용을 입력해주세요.');
                return;
            }

            localStorage.setItem('draft_title', title);
            localStorage.setItem('draft_content', content);
            localStorage.setItem('draft_board', '{{ $board->slug }}');
            localStorage.setItem('draft_timestamp', new Date().toISOString());
            
            alert('임시저장되었습니다.');
        }

        // 페이지 로드 시 임시저장된 내용 복원
        window.addEventListener('load', function() {
            const draftBoard = localStorage.getItem('draft_board');
            if (draftBoard === '{{ $board->slug }}') {
                const draftTitle = localStorage.getItem('draft_title');
                const draftContent = localStorage.getItem('draft_content');
                const draftTimestamp = localStorage.getItem('draft_timestamp');
                
                if (draftTitle || draftContent) {
                    if (confirm('임시저장된 내용이 있습니다. 복원하시겠습니까?')) {
                        if (draftTitle) document.getElementById('title').value = draftTitle;
                        if (draftContent) editor.setMarkdown(draftContent);
                    }
                }
            }
        });

        // 폼 제출 성공 시 임시저장 데이터 삭제
        document.getElementById('post-form').addEventListener('submit', function() {
            localStorage.removeItem('draft_title');
            localStorage.removeItem('draft_content');
            localStorage.removeItem('draft_board');
            localStorage.removeItem('draft_timestamp');
        });

        // 드래그 앤 드롭 처리
        const dropZone = document.querySelector('.border-dashed');

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, unhighlight, false);
        });

        function highlight(e) {
            dropZone.classList.add('border-blue-400', 'bg-blue-50');
        }

        function unhighlight(e) {
            dropZone.classList.remove('border-blue-400', 'bg-blue-50');
        }

        dropZone.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            fileInput.files = files;
            handleFiles(files);
        }
    </script>
</x-ahhob.layouts.app>