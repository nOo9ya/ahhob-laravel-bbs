<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', '관리자') - {{ config('app.name', 'Laravel') }}</title>

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
                                   class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.dashboard') ? 'bg-gray-900 text-white' : '' }}">
                                    대시보드
                                </a>
                                <a href="{{ route('admin.users.index') }}" 
                                   class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.users.*') ? 'bg-gray-900 text-white' : '' }}">
                                    사용자 관리
                                </a>
                                <a href="{{ route('admin.boards.index') }}" 
                                   class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.boards.*') ? 'bg-gray-900 text-white' : '' }}">
                                    게시판 관리
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Admin Info & Actions -->
                    <div class="flex items-center space-x-4">
                        @auth('admin')
                            <div class="flex items-center space-x-4">
                                <!-- Admin Info -->
                                <div class="text-sm text-gray-300">
                                    <span class="font-medium text-white">{{ auth('admin')->user()->display_name ?? auth('admin')->user()->username }}</span>
                                    <span class="ml-2 px-2 py-1 bg-red-600 text-white text-xs rounded-full">
                                        관리자
                                    </span>
                                </div>

                                <!-- Main Site -->
                                <a href="{{ route('home') }}" 
                                   class="text-gray-300 hover:text-white bg-blue-600 hover:bg-blue-700 px-3 py-2 rounded text-sm transition-colors">
                                    메인 사이트
                                </a>

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
                    
                    <!-- Mobile menu button -->
                    <div class="md:hidden flex items-center">
                        <button type="button" class="mobile-menu-button inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white">
                            <span class="sr-only">메뉴 열기</span>
                            <svg class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile menu -->
            <div class="md:hidden hidden" id="mobile-menu">
                <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                    <a href="{{ route('admin.dashboard') }}" 
                       class="text-gray-300 hover:text-white block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('admin.dashboard') ? 'bg-gray-900 text-white' : '' }}">
                        대시보드
                    </a>
                    <a href="{{ route('admin.users.index') }}" 
                       class="text-gray-300 hover:text-white block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('admin.users.*') ? 'bg-gray-900 text-white' : '' }}">
                        사용자 관리
                    </a>
                    <a href="{{ route('admin.boards.index') }}" 
                       class="text-gray-300 hover:text-white block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('admin.boards.*') ? 'bg-gray-900 text-white' : '' }}">
                        게시판 관리
                    </a>
                </div>
            </div>
        </nav>

        <!-- Flash Messages -->
        @if (session('success'))
            <div class="bg-green-50 border-l-4 border-green-400 p-4 mx-4 mt-4 sm:mx-6 lg:mx-8">
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

        @if (session('error'))
            <div class="bg-red-50 border-l-4 border-red-400 p-4 mx-4 mt-4 sm:mx-6 lg:mx-8">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Main Content -->
        <main class="py-8">
            @yield('content')
        </main>
    </div>

    <!-- Mobile menu toggle script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuButton = document.querySelector('.mobile-menu-button');
            const mobileMenu = document.querySelector('#mobile-menu');
            
            if (mobileMenuButton && mobileMenu) {
                mobileMenuButton.addEventListener('click', function() {
                    mobileMenu.classList.toggle('hidden');
                });
            }
        });
    </script>
</body>
</html>