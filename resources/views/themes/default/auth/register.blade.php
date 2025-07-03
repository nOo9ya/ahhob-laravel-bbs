<x-ahhob.layouts.app title="회원가입">
    <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    회원가입
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    이미 계정이 있으신가요?
                    <a href="{{ route('login') }}" class="font-medium text-blue-600 hover:text-blue-500 transition-colors">
                        로그인하기
                    </a>
                </p>
            </div>

            <form class="mt-8 space-y-6" action="{{ route('register') }}" method="POST" id="register-form">
                @csrf
                
                <div class="space-y-4">
                    <!-- Username Field -->
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700">
                            아이디 <span class="text-red-500">*</span>
                        </label>
                        <div class="mt-1">
                            <input id="username" name="username" type="text" required 
                                   class="appearance-none relative block w-full px-3 py-3 border @error('username') border-red-300 @else border-gray-300 @enderror placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-colors"
                                   placeholder="영문, 숫자, 언더스코어 3-50자"
                                   value="{{ old('username') }}"
                                   pattern="^[a-zA-Z0-9_]+$"
                                   minlength="3"
                                   maxlength="50">
                        </div>
                        @error('username')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @else
                            <p class="mt-1 text-xs text-gray-500">영문, 숫자, 언더스코어(_)만 사용 가능하며 3-50자여야 합니다.</p>
                        @enderror
                    </div>

                    <!-- Nickname Field -->
                    <div>
                        <label for="nickname" class="block text-sm font-medium text-gray-700">
                            닉네임 <span class="text-red-500">*</span>
                        </label>
                        <div class="mt-1">
                            <input id="nickname" name="nickname" type="text" required 
                                   class="appearance-none relative block w-full px-3 py-3 border @error('nickname') border-red-300 @else border-gray-300 @enderror placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-colors"
                                   placeholder="커뮤니티에서 사용할 닉네임"
                                   value="{{ old('nickname') }}"
                                   minlength="2"
                                   maxlength="100">
                        </div>
                        @error('nickname')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @else
                            <p class="mt-1 text-xs text-gray-500">2-100자까지 입력 가능합니다.</p>
                        @enderror
                    </div>

                    <!-- Real Name Field -->
                    <div>
                        <label for="real_name" class="block text-sm font-medium text-gray-700">
                            실명 <span class="text-red-500">*</span>
                        </label>
                        <div class="mt-1">
                            <input id="real_name" name="real_name" type="text" required 
                                   class="appearance-none relative block w-full px-3 py-3 border @error('real_name') border-red-300 @else border-gray-300 @enderror placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-colors"
                                   placeholder="실명을 입력하세요"
                                   value="{{ old('real_name') }}"
                                   minlength="2"
                                   maxlength="100">
                        </div>
                        @error('real_name')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email Field -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">
                            이메일 <span class="text-red-500">*</span>
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

                    <!-- Phone Number Field -->
                    <div>
                        <label for="phone_number" class="block text-sm font-medium text-gray-700">
                            휴대폰 번호 <span class="text-gray-400">(선택)</span>
                        </label>
                        <div class="mt-1">
                            <input id="phone_number" name="phone_number" type="tel" 
                                   class="appearance-none relative block w-full px-3 py-3 border @error('phone_number') border-red-300 @else border-gray-300 @enderror placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-colors"
                                   placeholder="010-1234-5678"
                                   value="{{ old('phone_number') }}"
                                   pattern="^[0-9\-\+\(\)\s]+$"
                                   maxlength="20">
                        </div>
                        @error('phone_number')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password Field -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">
                            비밀번호 <span class="text-red-500">*</span>
                        </label>
                        <div class="mt-1 relative">
                            <input id="password" name="password" type="password" required 
                                   class="appearance-none relative block w-full px-3 py-3 pr-10 border @error('password') border-red-300 @else border-gray-300 @enderror placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-colors"
                                   placeholder="안전한 비밀번호를 입력하세요"
                                   minlength="8">
                            <button type="button" id="toggle-password" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @else
                            <p class="mt-1 text-xs text-gray-500">최소 8자, 대소문자, 숫자, 특수문자 포함</p>
                        @enderror
                    </div>

                    <!-- Password Confirmation Field -->
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                            비밀번호 확인 <span class="text-red-500">*</span>
                        </label>
                        <div class="mt-1 relative">
                            <input id="password_confirmation" name="password_confirmation" type="password" required 
                                   class="appearance-none relative block w-full px-3 py-3 pr-10 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-colors"
                                   placeholder="비밀번호를 다시 입력하세요"
                                   minlength="8">
                            <button type="button" id="toggle-password-confirmation" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                        <div id="password-match-indicator" class="mt-1 text-xs hidden">
                            <span id="password-match-text"></span>
                        </div>
                    </div>
                </div>

                <!-- Agreement Checkboxes -->
                <div class="space-y-3">
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input id="terms_agree" name="terms_agree" type="checkbox" required
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded transition-colors">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="terms_agree" class="text-gray-700">
                                <span class="text-red-500">*</span> 
                                <a href="#" class="text-blue-600 hover:text-blue-500 underline">이용약관</a>에 동의합니다
                            </label>
                        </div>
                    </div>
                    @error('terms_agree')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input id="privacy_agree" name="privacy_agree" type="checkbox" required
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded transition-colors">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="privacy_agree" class="text-gray-700">
                                <span class="text-red-500">*</span> 
                                <a href="#" class="text-blue-600 hover:text-blue-500 underline">개인정보 처리방침</a>에 동의합니다
                            </label>
                        </div>
                    </div>
                    @error('privacy_agree')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input id="marketing_agree" name="marketing_agree" type="checkbox"
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded transition-colors">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="marketing_agree" class="text-gray-700">
                                마케팅 정보 수신에 동의합니다 <span class="text-gray-400">(선택)</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div>
                    <button type="submit" id="register-button"
                            class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <svg class="h-5 w-5 text-blue-500 group-hover:text-blue-400 transition-colors" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z" />
                            </svg>
                        </span>
                        <span class="register-text">회원가입</span>
                        <span class="register-loading hidden">가입 중...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Register Form Enhancement Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('register-form');
            const button = document.getElementById('register-button');
            const password = document.getElementById('password');
            const passwordConfirmation = document.getElementById('password_confirmation');
            const matchIndicator = document.getElementById('password-match-indicator');
            const matchText = document.getElementById('password-match-text');
            
            // Password visibility toggles
            function setupPasswordToggle(inputId, toggleId) {
                const input = document.getElementById(inputId);
                const toggle = document.getElementById(toggleId);
                
                if (input && toggle) {
                    toggle.addEventListener('click', function() {
                        const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                        input.setAttribute('type', type);
                    });
                }
            }
            
            setupPasswordToggle('password', 'toggle-password');
            setupPasswordToggle('password_confirmation', 'toggle-password-confirmation');
            
            // Password match validation
            function checkPasswordMatch() {
                if (password.value && passwordConfirmation.value) {
                    matchIndicator.classList.remove('hidden');
                    
                    if (password.value === passwordConfirmation.value) {
                        matchText.textContent = '✓ 비밀번호가 일치합니다';
                        matchText.className = 'text-green-600';
                        passwordConfirmation.classList.remove('border-red-300');
                        passwordConfirmation.classList.add('border-green-300');
                    } else {
                        matchText.textContent = '✗ 비밀번호가 일치하지 않습니다';
                        matchText.className = 'text-red-600';
                        passwordConfirmation.classList.remove('border-green-300');
                        passwordConfirmation.classList.add('border-red-300');
                    }
                } else {
                    matchIndicator.classList.add('hidden');
                    passwordConfirmation.classList.remove('border-red-300', 'border-green-300');
                }
            }
            
            if (password && passwordConfirmation) {
                password.addEventListener('input', checkPasswordMatch);
                passwordConfirmation.addEventListener('input', checkPasswordMatch);
            }
            
            // Form submission
            if (form && button) {
                form.addEventListener('submit', function(e) {
                    // Check password match
                    if (password.value !== passwordConfirmation.value) {
                        e.preventDefault();
                        passwordConfirmation.focus();
                        return;
                    }
                    
                    // Show loading state
                    button.disabled = true;
                    document.querySelector('.register-text').classList.add('hidden');
                    document.querySelector('.register-loading').classList.remove('hidden');
                    
                    // Re-enable button after 10 seconds (fallback)
                    setTimeout(function() {
                        if (button.disabled) {
                            button.disabled = false;
                            document.querySelector('.register-text').classList.remove('hidden');
                            document.querySelector('.register-loading').classList.add('hidden');
                        }
                    }, 10000);
                });
            }

            // Auto-focus first input
            const usernameInput = document.getElementById('username');
            if (usernameInput && !usernameInput.value) {
                usernameInput.focus();
            }
        });
    </script>
</x-ahhob.layouts.app>