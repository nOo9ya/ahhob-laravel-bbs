<x-ahhob.layouts.app title="비밀번호 찾기">
    <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-blue-100">
                    <svg class="h-6 w-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-6 6c-3 0-6 1-6 4v1h2a2 2 0 002-2v-1a2 2 0 012-2 6 6 0 006-6zM3 5a2 2 0 012-2h1a1 1 0 000 2H5v3a2 2 0 002 2 1 1 0 000 2H5a4 4 0 01-4-4V5z" />
                    </svg>
                </div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    비밀번호 찾기
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    가입할 때 사용한 이메일 주소를 입력하시면<br>
                    비밀번호 재설정 링크를 보내드립니다.
                </p>
            </div>

            <div class="bg-white shadow rounded-lg p-6">
                @if (session('status'))
                    <div class="mb-4 bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-green-700">{{ session('status') }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('password.email') }}" id="forgot-password-form">
                    @csrf

                    <!-- Email Field -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">
                            이메일 주소
                        </label>
                        <div class="mt-1">
                            <input id="email" name="email" type="email" required 
                                   class="appearance-none relative block w-full px-3 py-3 border @error('email') border-red-300 @else border-gray-300 @enderror placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-colors"
                                   placeholder="이메일 주소를 입력하세요"
                                   value="{{ old('email') }}"
                                   maxlength="100">
                        </div>
                        @error('email')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Submit Button -->
                    <div class="mt-6">
                        <button type="submit" id="submit-button"
                                class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                                <svg class="h-5 w-5 text-blue-500 group-hover:text-blue-400 transition-colors" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                    <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                                </svg>
                            </span>
                            <span class="submit-text">비밀번호 재설정 링크 보내기</span>
                            <span class="submit-loading hidden">전송 중...</span>
                        </button>
                    </div>
                </form>

                <!-- Additional Actions -->
                <div class="mt-6 flex flex-col space-y-2">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300" />
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-2 bg-white text-gray-500">또는</span>
                        </div>
                    </div>

                    <div class="flex justify-center space-x-4 text-sm">
                        <a href="{{ route('login') }}" 
                           class="text-blue-600 hover:text-blue-500 font-medium transition-colors">
                            로그인으로 돌아가기
                        </a>
                        <span class="text-gray-300">|</span>
                        <a href="{{ route('register') }}" 
                           class="text-blue-600 hover:text-blue-500 font-medium transition-colors">
                            회원가입
                        </a>
                    </div>
                </div>
            </div>

            <!-- Security Information -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">보안 안내</h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <ul class="list-disc pl-5 space-y-1">
                                <li>비밀번호 재설정 링크는 1시간 동안 유효합니다.</li>
                                <li>등록되지 않은 이메일 주소로는 링크가 발송되지 않습니다.</li>
                                <li>스팸함도 확인해주세요.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rate Limiting Notice -->
            <div class="text-center">
                <p class="text-xs text-gray-500">
                    보안상 이유로 동일한 이메일로는 5분에 1회만 요청 가능합니다.
                </p>
            </div>
        </div>
    </div>

    <!-- Forgot Password Enhancement Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('forgot-password-form');
            const button = document.getElementById('submit-button');
            const submitText = document.querySelector('.submit-text');
            const submitLoading = document.querySelector('.submit-loading');
            const emailInput = document.getElementById('email');
            
            let lastSubmitTime = 0;
            const cooldownTime = 300000; // 5 minutes
            
            // Load last submit time from localStorage
            const storedTime = localStorage.getItem('lastPasswordResetRequest');
            if (storedTime) {
                lastSubmitTime = parseInt(storedTime);
            }
            
            function updateButtonState() {
                const now = Date.now();
                const timeDiff = now - lastSubmitTime;
                
                if (timeDiff < cooldownTime) {
                    const remainingMinutes = Math.ceil((cooldownTime - timeDiff) / 60000);
                    button.disabled = true;
                    submitText.textContent = `다시 요청 (${remainingMinutes}분 후)`;
                    
                    setTimeout(updateButtonState, 60000); // Update every minute
                } else {
                    button.disabled = false;
                    submitText.textContent = '비밀번호 재설정 링크 보내기';
                }
            }
            
            // Initial button state update
            updateButtonState();
            
            if (form && button) {
                form.addEventListener('submit', function(e) {
                    const now = Date.now();
                    const timeDiff = now - lastSubmitTime;
                    
                    if (timeDiff < cooldownTime) {
                        e.preventDefault();
                        return;
                    }
                    
                    // Update last submit time
                    lastSubmitTime = now;
                    localStorage.setItem('lastPasswordResetRequest', lastSubmitTime.toString());
                    
                    // Show loading state
                    button.disabled = true;
                    submitText.classList.add('hidden');
                    submitLoading.classList.remove('hidden');
                    
                    // Update button state after submission
                    setTimeout(function() {
                        submitLoading.classList.add('hidden');
                        submitText.classList.remove('hidden');
                        updateButtonState();
                    }, 3000);
                });
            }

            // Email validation
            if (emailInput) {
                emailInput.addEventListener('input', function() {
                    const email = this.value;
                    if (email && !isValidEmail(email)) {
                        this.classList.add('border-red-300');
                        this.classList.remove('border-gray-300');
                    } else {
                        this.classList.remove('border-red-300');
                        this.classList.add('border-gray-300');
                    }
                });
            }
            
            function isValidEmail(email) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return emailRegex.test(email);
            }

            // Auto-focus email input
            if (emailInput && !emailInput.value) {
                emailInput.focus();
            }
        });
    </script>
</x-ahhob.layouts.app>