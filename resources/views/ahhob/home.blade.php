<x-ahhob.layouts.app title="홈">
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h1 class="text-3xl font-bold mb-6">Ahhob 커뮤니티에 오신 것을 환영합니다!</h1>
                    
                    @auth('web')
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                            <h2 class="text-lg font-semibold text-blue-800 mb-2">환영합니다, {{ auth('web')->user()->nickname }}님!</h2>
                            <div class="text-sm text-blue-600 space-y-1">
                                <p><span class="font-medium">레벨:</span> {{ auth('web')->user()->level }} ({{ auth('web')->user()->level_name }})</p>
                                <p><span class="font-medium">포인트:</span> {{ number_format(auth('web')->user()->points) }}P</p>
                                <p><span class="font-medium">상태:</span> {{ auth('web')->user()->status->label() }}</p>
                                @if(auth('web')->user()->last_login_at)
                                    <p><span class="font-medium">최근 로그인:</span> {{ auth('web')->user()->last_login_at->diffForHumans() }}</p>
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 mb-6">
                            <h2 class="text-lg font-semibold text-gray-800 mb-2">로그인이 필요합니다</h2>
                            <p class="text-gray-600 mb-4">커뮤니티의 모든 기능을 이용하려면 로그인해주세요.</p>
                            <div class="flex space-x-4">
                                <a href="{{ route('login') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                                    로그인
                                </a>
                                <a href="{{ route('register') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition-colors">
                                    회원가입
                                </a>
                            </div>
                        </div>
                    @endauth

                    <!-- 기능 소개 -->
                    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-6 rounded-lg">
                            <div class="flex items-center mb-3">
                                <svg class="h-8 w-8 text-blue-600 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a2 2 0 01-2-2v-2M5 8h2m0 0V6a2 2 0 012-2h6a2 2 0 012 2v2m-6 0h4"/>
                                </svg>
                                <h3 class="text-lg font-semibold text-blue-800">커뮤니티</h3>
                            </div>
                            <p class="text-blue-600">다양한 주제의 게시판에서 소통하고 정보를 공유하세요.</p>
                        </div>

                        <div class="bg-gradient-to-br from-green-50 to-green-100 p-6 rounded-lg">
                            <div class="flex items-center mb-3">
                                <svg class="h-8 w-8 text-green-600 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                                <h3 class="text-lg font-semibold text-green-800">쇼핑몰</h3>
                            </div>
                            <p class="text-green-600">다양한 상품을 둘러보고 안전하게 구매하세요.</p>
                        </div>

                        <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-6 rounded-lg">
                            <div class="flex items-center mb-3">
                                <svg class="h-8 w-8 text-purple-600 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                                </svg>
                                <h3 class="text-lg font-semibold text-purple-800">포인트 시스템</h3>
                            </div>
                            <p class="text-purple-600">활동으로 포인트를 얻고 레벨을 올려보세요.</p>
                        </div>
                    </div>

                    <!-- 관리자 링크 (관리자인 경우만) -->
                    @if(auth('admin')->check())
                        <div class="mt-8 bg-red-50 border border-red-200 rounded-lg p-4">
                            <h3 class="text-lg font-semibold text-red-800 mb-2">관리자 메뉴</h3>
                            <p class="text-red-600 mb-3">관리자로 로그인된 상태입니다.</p>
                            <a href="{{ route('admin.dashboard') }}" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-colors">
                                관리자 대시보드
                            </a>
                        </div>
                    @endif

                    <!-- 테스트 계정 정보 -->
                    @if(app()->environment('local'))
                        <div class="mt-8 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <h3 class="text-lg font-semibold text-yellow-800 mb-2">테스트 계정 정보</h3>
                            <div class="text-sm text-yellow-700 space-y-1">
                                <p><strong>일반 사용자:</strong> testuser / password123!</p>
                                <p><strong>관리자:</strong> superadmin / password123!</p>
                                <p class="text-xs text-yellow-600 mt-2">※ 개발 환경에서만 표시됩니다.</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-ahhob.layouts.app>