<x-ahhob.layouts.app title="이메일 인증">
    <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-yellow-100">
                    <svg class="h-6 w-6 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    이메일 인증이 필요합니다
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    회원가입을 완료하려면 이메일 인증이 필요합니다.
                </p>
            </div>

            <div class="bg-white shadow rounded-lg p-6">
                <div class="text-center">
                    <p class="text-sm text-gray-700 mb-4">
                        <strong>{{ auth()->user()->email }}</strong>로<br>
                        인증 이메일을 발송했습니다.
                    </p>
                    
                    <p class="text-xs text-gray-500 mb-6">
                        이메일을 받지 못하셨나요? 스팸함도 확인해보세요.
                    </p>

                    <!-- Resend Email Form -->
                    <form method="POST" action="{{ route('verification.send') }}" id="resend-form">
                        @csrf
                        <button type="submit" id="resend-button"
                                class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                            <span class="resend-text">인증 이메일 다시 보내기</span>
                            <span class="resend-loading hidden">전송 중...</span>
                        </button>
                    </form>

                    <!-- Manual Email Check -->
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <p class="text-xs text-gray-500 mb-2">
                            이미 이메일 인증을 완료하셨나요?
                        </p>
                        <a href="{{ route('home') }}" 
                           class="text-sm text-blue-600 hover:text-blue-500 font-medium transition-colors">
                            홈으로 이동
                        </a>
                    </div>

                    <!-- Logout Option -->
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <p class="text-xs text-gray-500 mb-2">
                            다른 계정으로 로그인하시겠습니까?
                        </p>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-sm text-gray-600 hover:text-gray-900 font-medium transition-colors">
                                로그아웃
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Email Client Quick Links -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="text-sm font-medium text-blue-900 mb-2">빠른 이메일 확인</h3>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <a href="https://mail.google.com" target="_blank" rel="noopener" 
                       class="flex items-center justify-center py-2 px-3 bg-white border border-blue-300 rounded text-blue-700 hover:bg-blue-50 transition-colors">
                        <svg class="h-4 w-4 mr-1" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M24 5.457v13.909c0 .904-.732 1.636-1.636 1.636h-3.819V11.73L12 16.64l-6.545-4.91v9.273H1.636A1.636 1.636 0 0 1 0 19.366V5.457c0-2.023 2.309-3.178 3.927-1.964L5.455 4.64 12 9.548l6.545-4.907 1.528-1.148C21.69 2.28 24 3.434 24 5.457z"/>
                        </svg>
                        Gmail
                    </a>
                    <a href="https://outlook.live.com" target="_blank" rel="noopener"
                       class="flex items-center justify-center py-2 px-3 bg-white border border-blue-300 rounded text-blue-700 hover:bg-blue-50 transition-colors">
                        <svg class="h-4 w-4 mr-1" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M7.88 12.04q0 .45-.11.87-.1.41-.33.74-.22.33-.58.52-.37.2-.87.2t-.85-.2q-.35-.21-.57-.55-.22-.33-.33-.75-.1-.42-.1-.83 0-.42.1-.83.11-.42.33-.75.22-.34.57-.55.35-.2.85-.2t.87.2q.36.19.58.52.23.33.33.74.11.42.11.87zm-3.7 0q0 .58.22 1.07.23.5.61.50.39 0 .61-.5.23-.49.23-1.07 0-.57-.23-1.07-.22-.5-.61-.5-.38 0-.61.5-.22.5-.22 1.07z"/>
                            <path d="M24 12v9.38q0 .46-.33.8-.33.32-.8.32H7.13q-.46 0-.8-.32-.32-.34-.32-.8V18H1q-.41 0-.7-.3-.3-.29-.3-.7V7q0-.41.3-.7Q.58 6 1 6h6.1q.36 0 .63.26.26.25.26.6v5.04h16.01z"/>
                        </svg>
                        Outlook
                    </a>
                </div>
            </div>

            <!-- Security Note -->
            <div class="text-center">
                <p class="text-xs text-gray-500">
                    ⚠️ 보안을 위해 이메일 인증은 24시간 내에 완료해주세요.
                </p>
            </div>
        </div>
    </div>

    <!-- Resend Email Enhancement Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('resend-form');
            const button = document.getElementById('resend-button');
            const resendText = document.querySelector('.resend-text');
            const resendLoading = document.querySelector('.resend-loading');
            
            let lastResendTime = 0;
            const cooldownTime = 60000; // 60 seconds
            
            // Load last resend time from localStorage
            const storedTime = localStorage.getItem('lastEmailResend');
            if (storedTime) {
                lastResendTime = parseInt(storedTime);
            }
            
            function updateButtonState() {
                const now = Date.now();
                const timeDiff = now - lastResendTime;
                
                if (timeDiff < cooldownTime) {
                    const remainingSeconds = Math.ceil((cooldownTime - timeDiff) / 1000);
                    button.disabled = true;
                    resendText.textContent = `다시 보내기 (${remainingSeconds}초 후)`;
                    
                    setTimeout(updateButtonState, 1000);
                } else {
                    button.disabled = false;
                    resendText.textContent = '인증 이메일 다시 보내기';
                }
            }
            
            // Initial button state update
            updateButtonState();
            
            if (form && button) {
                form.addEventListener('submit', function(e) {
                    const now = Date.now();
                    const timeDiff = now - lastResendTime;
                    
                    if (timeDiff < cooldownTime) {
                        e.preventDefault();
                        return;
                    }
                    
                    // Update last resend time
                    lastResendTime = now;
                    localStorage.setItem('lastEmailResend', lastResendTime.toString());
                    
                    // Show loading state
                    button.disabled = true;
                    resendText.classList.add('hidden');
                    resendLoading.classList.remove('hidden');
                    
                    // Update button state after submission
                    setTimeout(function() {
                        resendLoading.classList.add('hidden');
                        resendText.classList.remove('hidden');
                        updateButtonState();
                    }, 2000);
                });
            }
            
            // Auto-refresh page every 30 seconds to check verification status
            let refreshInterval;
            function startAutoRefresh() {
                refreshInterval = setInterval(function() {
                    // Silent check if user is verified
                    fetch('{{ route('api.user.me') }}', {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.user.email_verified_at) {
                            clearInterval(refreshInterval);
                            window.location.href = '{{ route('home') }}';
                        }
                    })
                    .catch(error => {
                        // Ignore errors during auto-check
                    });
                }, 30000); // Check every 30 seconds
            }
            
            // Start auto-refresh
            startAutoRefresh();
            
            // Clear interval when page is about to unload
            window.addEventListener('beforeunload', function() {
                if (refreshInterval) {
                    clearInterval(refreshInterval);
                }
            });
        });
    </script>
</x-ahhob.layouts.app>