<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>관리자 로그인 - {{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;500;700&display=swap" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-900">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <!-- Header -->
            <div>
                <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-red-100">
                    <svg class="h-8 w-8 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-white">
                    관리자 로그인
                </h2>
                <p class="mt-2 text-center text-sm text-gray-400">
                    관리자 계정으로만 로그인 가능합니다
                </p>
            </div>

            <!-- Flash Messages -->
            @if (session('success'))
                <div class="bg-green-50 border-l-4 border-green-400 p-4 rounded">
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
                <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded">
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

            <!-- Login Form -->
            <div class="bg-white shadow-xl rounded-lg">
                <div class="px-6 py-8">
                    <form class="space-y-6" action="{{ route('admin.login') }}" method="POST" id="admin-login-form">
                        @csrf
                        
                        <!-- Username Field -->
                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-700">
                                관리자 아이디
                            </label>
                            <div class="mt-1">
                                <input id="username" name="username" type="text" required 
                                       class="appearance-none relative block w-full px-3 py-3 border @error('username') border-red-300 @else border-gray-300 @enderror placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 sm:text-sm transition-colors"
                                       placeholder="관리자 아이디를 입력하세요"
                                       value="{{ old('username') }}"
                                       maxlength="50">
                            </div>
                            @error('username')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Password Field -->
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700">
                                비밀번호
                            </label>
                            <div class="mt-1 relative">
                                <input id="password" name="password" type="password" required 
                                       class="appearance-none relative block w-full px-3 py-3 pr-10 border @error('password') border-red-300 @else border-gray-300 @enderror placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 sm:text-sm transition-colors"
                                       placeholder="비밀번호를 입력하세요">
                                <button type="button" id="toggle-password" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                            </div>
                            @error('password')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Remember Me -->
                        <div class="flex items-center">
                            <input id="remember" name="remember" type="checkbox" 
                                   class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded transition-colors">
                            <label for="remember" class="ml-2 block text-sm text-gray-700">
                                로그인 상태 유지 (보안상 권장하지 않음)
                            </label>
                        </div>

                        <!-- Submit Button -->
                        <div>
                            <button type="submit" id="login-button"
                                    class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                                    <svg class="h-5 w-5 text-red-500 group-hover:text-red-400 transition-colors" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                                <span class="login-text">관리자 로그인</span>
                                <span class="login-loading hidden">로그인 중...</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Footer Links -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 rounded-b-lg">
                    <div class="text-center">
                        <a href="{{ route('home') }}" 
                           class="text-sm text-gray-600 hover:text-gray-900 transition-colors">
                            ← 메인 사이트로 돌아가기
                        </a>
                    </div>
                </div>
            </div>

            <!-- Security Warning -->
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">보안 주의사항</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <ul class="list-disc pl-5 space-y-1">
                                <li>관리자 계정은 절대 타인과 공유하지 마세요.</li>
                                <li>공용 컴퓨터에서는 로그인하지 마세요.</li>
                                <li>로그인 실패 3회 시 15분간 차단됩니다.</li>
                                <li>비정상적인 접근 시도는 모두 기록됩니다.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Connection Info -->
            <div class="text-center text-xs text-gray-500">
                접속 IP: {{ request()->ip() }} | 
                시간: {{ now()->format('Y-m-d H:i:s') }}
            </div>
        </div>
    </div>

    <!-- Admin Login Enhancement Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('admin-login-form');
            const button = document.getElementById('login-button');
            const togglePassword = document.getElementById('toggle-password');
            const passwordInput = document.getElementById('password');
            const loginText = document.querySelector('.login-text');
            const loginLoading = document.querySelector('.login-loading');

            // Password visibility toggle
            if (togglePassword && passwordInput) {
                togglePassword.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                });
            }

            // Form submission with loading state and security logging
            if (form && button) {
                form.addEventListener('submit', function(e) {
                    // Show loading state
                    button.disabled = true;
                    loginText.classList.add('hidden');
                    loginLoading.classList.remove('hidden');
                    
                    // Log admin login attempt (for security)
                    console.log('Admin login attempt at: ' + new Date().toISOString());
                    
                    // Re-enable button after 10 seconds (fallback)
                    setTimeout(function() {
                        if (button.disabled) {
                            button.disabled = false;
                            loginText.classList.remove('hidden');
                            loginLoading.classList.add('hidden');
                        }
                    }, 10000);
                });
            }

            // Auto-focus username input
            const usernameInput = document.getElementById('username');
            if (usernameInput && !usernameInput.value) {
                usernameInput.focus();
            }

            // Prevent right-click context menu for additional security
            document.addEventListener('contextmenu', function(e) {
                e.preventDefault();
            });

            // Disable F12 and common developer shortcuts
            document.addEventListener('keydown', function(e) {
                if (e.key === 'F12' || 
                    (e.ctrlKey && e.shiftKey && e.key === 'I') ||
                    (e.ctrlKey && e.shiftKey && e.key === 'C') ||
                    (e.ctrlKey && e.key === 'u')) {
                    e.preventDefault();
                }
            });

            // Simple session timeout warning (30 minutes)
            let sessionTimeout = setTimeout(function() {
                alert('보안을 위해 30분 후 자동으로 로그아웃됩니다.');
            }, 30 * 60 * 1000);

            // Clear timeout if user leaves page
            window.addEventListener('beforeunload', function() {
                clearTimeout(sessionTimeout);
            });
        });
    </script>
</body>
</html>