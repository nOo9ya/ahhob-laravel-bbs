<!-- 테마 선택기 -->
<div class="relative" x-data="{ open: false }">
    <button @click="open = !open" 
            class="flex items-center text-sm text-gray-600 hover:text-gray-900 transition-colors">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zM21 5a2 2 0 00-2-2h-4a2 2 0 00-2 2v12a4 4 0 004 4h4a2 2 0 002-2V5z"/>
        </svg>
        테마
        <svg class="w-3 h-3 ml-1" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
        </svg>
    </button>

    <div x-show="open" 
         @click.away="open = false"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-50">
        
        <div class="p-2">
            <div class="text-xs font-medium text-gray-500 px-2 py-1 mb-1">테마 선택</div>
            
            @foreach($themeService->getAvailableThemes() as $theme)
                @php $themeInfo = $themeService->getThemeInfo($theme); @endphp
                <button onclick="changeTheme('{{ $theme }}')"
                        class="flex items-center w-full px-2 py-2 text-sm text-left hover:bg-gray-100 rounded-md transition-colors {{ $currentTheme === $theme ? 'bg-blue-50 text-blue-700' : 'text-gray-700' }}">
                    
                    @if($currentTheme === $theme)
                        <svg class="w-4 h-4 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                    @else
                        <div class="w-4 h-4 mr-2"></div>
                    @endif
                    
                    <div>
                        <div class="font-medium">{{ $themeInfo['name'] ?? ucfirst($theme) }}</div>
                        @if(isset($themeInfo['description']))
                            <div class="text-xs text-gray-500 truncate">{{ $themeInfo['description'] }}</div>
                        @endif
                    </div>
                </button>
            @endforeach
        </div>
    </div>
</div>

<script>
function changeTheme(theme) {
    fetch('{{ route("theme.change") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ theme: theme })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert(data.message || '테마 변경에 실패했습니다.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('테마 변경 중 오류가 발생했습니다.');
    });
}
</script>