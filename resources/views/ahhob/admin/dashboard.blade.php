<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>관리자 대시보드 - {{ config('app.name', 'Laravel') }}</title>

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
                            <span class="text-xl font-bold text-white">Ahhob Admin</span>
                        </div>
                    </div>

                    <!-- Admin Menu -->
                    <div class="flex items-center space-x-4">
                        @auth('admin')
                            <div class="flex items-center space-x-4">
                                <!-- Admin Info -->
                                <div class="text-sm text-gray-300">
                                    <span class="font-medium text-white">{{ auth('admin')->user()->display_name }}</span>
                                    <span class="ml-2 px-2 py-1 bg-{{ auth('admin')->user()->role->color() }}-600 text-white text-xs rounded-full">
                                        {{ auth('admin')->user()->role->label() }}
                                    </span>
                                </div>

                                <!-- Logout -->
                                <form method="POST" action="{{ route('admin.logout') }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-gray-300 hover:text-white bg-red-600 hover:bg-red-700 px-3 py-2 rounded text-sm transition-colors">
                                        로그아웃
                                    </button>
                                </form>
                            </div>
                        @endauth
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Flash Messages -->
                @if (session('success'))
                    <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-green-700">{{ session('success') }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Dashboard Header -->
                <div class="bg-white shadow rounded-lg mb-6">
                    <div class="px-6 py-4">
                        <h1 class="text-2xl font-bold text-gray-900">관리자 대시보드</h1>
                        <p class="text-gray-600">시스템 현황과 관리 도구에 접근할 수 있습니다.</p>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Users Count -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-6 w-6 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m3 5.197H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">총 사용자</dt>
                                        <dd class="text-lg font-medium text-gray-900">{{ \App\Models\User::count() }}명</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Active Users -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-6 w-6 text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">활성 사용자</dt>
                                        <dd class="text-lg font-medium text-gray-900">{{ \App\Models\User::where('status', \App\Enums\UserStatus::ACTIVE)->count() }}명</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Admins Count -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-6 w-6 text-red-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">관리자</dt>
                                        <dd class="text-lg font-medium text-gray-900">{{ \App\Models\Ahhob\Admin\Admin::count() }}명</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Today's Logins -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-6 w-6 text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">오늘 로그인</dt>
                                        <dd class="text-lg font-medium text-gray-900">{{ \App\Models\Ahhob\User\LoginHistory::where('status', 'success')->whereDate('created_at', today())->count() }}회</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Admin Menu Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- User Management -->
                    <div class="bg-white overflow-hidden shadow rounded-lg hover:shadow-lg transition-shadow">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="h-8 w-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                        <svg class="h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m3 5.197H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-medium text-gray-900">사용자 관리</h3>
                                    <p class="text-sm text-gray-500">회원 정보 및 권한 관리</p>
                                </div>
                            </div>
                            <div class="mt-4">
                                <a href="#" class="text-blue-600 hover:text-blue-500 text-sm font-medium">
                                    관리하기 →
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Content Management -->
                    <div class="bg-white overflow-hidden shadow rounded-lg hover:shadow-lg transition-shadow">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="h-8 w-8 bg-green-100 rounded-lg flex items-center justify-center">
                                        <svg class="h-5 w-5 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-medium text-gray-900">콘텐츠 관리</h3>
                                    <p class="text-sm text-gray-500">게시글, 댓글 관리</p>
                                </div>
                            </div>
                            <div class="mt-4">
                                <a href="#" class="text-green-600 hover:text-green-500 text-sm font-medium">
                                    관리하기 →
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- System Settings -->
                    <div class="bg-white overflow-hidden shadow rounded-lg hover:shadow-lg transition-shadow">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="h-8 w-8 bg-purple-100 rounded-lg flex items-center justify-center">
                                        <svg class="h-5 w-5 text-purple-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-medium text-gray-900">시스템 설정</h3>
                                    <p class="text-sm text-gray-500">사이트 및 시스템 관리</p>
                                </div>
                            </div>
                            <div class="mt-4">
                                <a href="#" class="text-purple-600 hover:text-purple-500 text-sm font-medium">
                                    설정하기 →
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Security Logs -->
                    <div class="bg-white overflow-hidden shadow rounded-lg hover:shadow-lg transition-shadow">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="h-8 w-8 bg-red-100 rounded-lg flex items-center justify-center">
                                        <svg class="h-5 w-5 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-medium text-gray-900">보안 로그</h3>
                                    <p class="text-sm text-gray-500">로그인 기록 및 보안 감사</p>
                                </div>
                            </div>
                            <div class="mt-4">
                                <a href="#" class="text-red-600 hover:text-red-500 text-sm font-medium">
                                    확인하기 →
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Analytics -->
                    <div class="bg-white overflow-hidden shadow rounded-lg hover:shadow-lg transition-shadow">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="h-8 w-8 bg-yellow-100 rounded-lg flex items-center justify-center">
                                        <svg class="h-5 w-5 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-medium text-gray-900">통계 분석</h3>
                                    <p class="text-sm text-gray-500">사용자 활동 및 사이트 통계</p>
                                </div>
                            </div>
                            <div class="mt-4">
                                <a href="#" class="text-yellow-600 hover:text-yellow-500 text-sm font-medium">
                                    보기 →
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Main Site -->
                    <div class="bg-white overflow-hidden shadow rounded-lg hover:shadow-lg transition-shadow">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="h-8 w-8 bg-gray-100 rounded-lg flex items-center justify-center">
                                        <svg class="h-5 w-5 text-gray-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-medium text-gray-900">메인 사이트</h3>
                                    <p class="text-sm text-gray-500">사용자 페이지로 이동</p>
                                </div>
                            </div>
                            <div class="mt-4">
                                <a href="{{ route('home') }}" class="text-gray-600 hover:text-gray-500 text-sm font-medium">
                                    이동하기 →
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="mt-8 bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">최근 활동</h2>
                    </div>
                    <div class="p-6">
                        <div class="flow-root">
                            <ul class="divide-y divide-gray-200">
                                @foreach(\App\Models\Ahhob\User\LoginHistory::latest()->limit(5)->get() as $login)
                                <li class="py-3">
                                    <div class="flex items-center space-x-4">
                                        <div class="flex-shrink-0">
                                            <div class="h-8 w-8 rounded-full bg-{{ $login->is_success ? 'green' : 'red' }}-100 flex items-center justify-center">
                                                @if($login->is_success)
                                                    <svg class="h-4 w-4 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                @else
                                                    <svg class="h-4 w-4 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 truncate">
                                                {{ $login->is_success ? '로그인 성공' : '로그인 실패' }}
                                            </p>
                                            <p class="text-sm text-gray-500 truncate">
                                                IP: {{ $login->ip_address }} | {{ $login->device_type }} ({{ $login->browser }})
                                            </p>
                                        </div>
                                        <div class="flex-shrink-0 text-sm text-gray-500">
                                            {{ $login->created_at->diffForHumans() }}
                                        </div>
                                    </div>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>