<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>사용자 관리 - {{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;500;700&display=swap" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-100">
    <div class="min-h-screen">
        <!-- Admin Navigation -->
        <nav class="bg-gray-800 shadow-lg">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <!-- Logo -->
                    <div class="flex items-center">
                        <div class="flex-shrink-0 flex items-center">
                            <a href="{{ route('admin.dashboard') }}" class="text-xl font-bold text-white">Ahhob Admin</a>
                        </div>
                        
                        <!-- Admin Menu -->
                        <div class="hidden md:block ml-10">
                            <div class="flex items-baseline space-x-4">
                                <a href="{{ route('admin.dashboard') }}" 
                                   class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                                    대시보드
                                </a>
                                <a href="{{ route('admin.users.index') }}" 
                                   class="bg-gray-900 text-white px-3 py-2 rounded-md text-sm font-medium">
                                    사용자 관리
                                </a>
                                <a href="{{ route('admin.boards.index') }}" 
                                   class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                                    게시판 관리
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center space-x-4">
                        <a href="{{ route('home') }}" 
                           class="text-gray-300 hover:text-white bg-blue-600 hover:bg-blue-700 px-3 py-2 rounded text-sm transition-colors">
                            메인 사이트
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="py-8">

<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <!-- 헤더 -->
    <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                사용자 관리
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                등록된 사용자를 관리하고 모니터링할 수 있습니다.
            </p>
        </div>
    </div>

    <!-- 검색 및 필터 -->
    <div class="bg-white shadow rounded-lg mb-6">
        <div class="p-6">
            <form method="GET" class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <!-- 검색어 -->
                <div class="sm:col-span-2">
                    <label for="search" class="block text-sm font-medium text-gray-700">검색</label>
                    <input type="text" 
                           name="search" 
                           id="search"
                           value="{{ request('search') }}"
                           placeholder="사용자명, 닉네임, 이메일로 검색..."
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <!-- 상태 필터 -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">상태</label>
                    <select name="status" id="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">전체</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>활성</option>
                        <option value="dormant" {{ request('status') === 'dormant' ? 'selected' : '' }}>휴면</option>
                        <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>정지</option>
                        <option value="banned" {{ request('status') === 'banned' ? 'selected' : '' }}>차단</option>
                    </select>
                </div>
                
                <!-- 검색 버튼 -->
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                        검색
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 통계 카드 -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="h-8 w-8 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m3 5.197H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">전체 사용자</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ number_format(\App\Models\User::count()) }}명</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="h-8 w-8 bg-green-100 rounded-lg flex items-center justify-center">
                            <svg class="h-5 w-5 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">활성 사용자</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ number_format(\App\Models\User::where('status', 'active')->count()) }}명</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="h-8 w-8 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <svg class="h-5 w-5 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">정지 사용자</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ number_format(\App\Models\User::where('status', 'suspended')->count()) }}명</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="h-8 w-8 bg-red-100 rounded-lg flex items-center justify-center">
                            <svg class="h-5 w-5 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">차단 사용자</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ number_format(\App\Models\User::where('status', 'banned')->count()) }}명</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 사용자 목록 테이블 -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <h3 class="text-lg leading-6 font-medium text-gray-900">사용자 목록</h3>
        </div>
        
        @if($users->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                사용자 정보
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                상태
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                가입일
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                최근 로그인
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                관리
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($users as $user)
                        <tr class="hover:bg-gray-50">
                            <!-- 사용자 정보 -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-gray-100 flex items-center justify-center">
                                            <span class="text-sm font-medium text-gray-700">
                                                {{ substr($user->nickname, 0, 2) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $user->nickname }}</div>
                                        <div class="text-sm text-gray-500">{{ $user->username }} ({{ $user->email }})</div>
                                    </div>
                                </div>
                            </td>
                            
                            <!-- 상태 -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $statusColors = [
                                        'active' => 'bg-green-100 text-green-800',
                                        'dormant' => 'bg-gray-100 text-gray-800', 
                                        'suspended' => 'bg-yellow-100 text-yellow-800',
                                        'banned' => 'bg-red-100 text-red-800'
                                    ];
                                    $statusLabels = [
                                        'active' => '활성',
                                        'dormant' => '휴면',
                                        'suspended' => '정지', 
                                        'banned' => '차단'
                                    ];
                                @endphp
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $statusColors[$user->status] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ $statusLabels[$user->status] ?? $user->status }}
                                </span>
                            </td>
                            
                            <!-- 가입일 -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $user->created_at->format('Y-m-d') }}
                            </td>
                            
                            <!-- 최근 로그인 -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $user->last_login_at ? $user->last_login_at->format('Y-m-d H:i') : '-' }}
                            </td>
                            
                            <!-- 관리 -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <a href="{{ route('admin.users.show', $user) }}" 
                                       class="text-blue-600 hover:text-blue-900">상세</a>
                                    
                                    @if($user->status !== 'suspended')
                                        <form method="POST" action="{{ route('admin.users.updateStatus', $user) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="suspended">
                                            <button type="submit" class="text-yellow-600 hover:text-yellow-900"
                                                    onclick="return confirm('이 사용자를 정지하시겠습니까?')">정지</button>
                                        </form>
                                    @endif
                                    
                                    @if($user->status !== 'active')
                                        <form method="POST" action="{{ route('admin.users.updateStatus', $user) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="active">
                                            <button type="submit" class="text-green-600 hover:text-green-900">활성화</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <!-- 페이지네이션 -->
            <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                {{ $users->appends(request()->query())->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m3 5.197H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">사용자가 없습니다</h3>
                <p class="mt-1 text-sm text-gray-500">검색 조건을 변경해보세요.</p>
            </div>
        @endif
    </div>
</div>

        </main>
    </div>
</body>
</html>