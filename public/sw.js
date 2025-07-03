/**
 * Service Worker for Ahhob Shop
 * 캐싱 및 오프라인 지원
 */

const CACHE_NAME = 'ahhob-shop-v1';
const STATIC_CACHE_URLS = [
    '/',
    '/css/shop-mobile.css',
    '/js/shop.js',
    '/js/shop-mobile.js',
    '/images/logo.png',
    '/images/no-image.png'
];

const DYNAMIC_CACHE_URLS = [
    '/shop/products',
    '/shop/cart',
    '/shop/categories'
];

// 설치 이벤트
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('캐시 생성됨');
                return cache.addAll(STATIC_CACHE_URLS);
            })
    );
});

// 활성화 이벤트
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('오래된 캐시 삭제:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});

// 네트워크 요청 가로채기
self.addEventListener('fetch', event => {
    const request = event.request;
    const url = new URL(request.url);

    // 같은 도메인의 요청만 처리
    if (url.origin !== location.origin) {
        return;
    }

    // GET 요청만 캐싱
    if (request.method !== 'GET') {
        return;
    }

    // 이미지 파일 캐싱 전략
    if (request.destination === 'image') {
        event.respondWith(
            caches.open(CACHE_NAME).then(cache => {
                return cache.match(request).then(response => {
                    if (response) {
                        return response;
                    }
                    return fetch(request).then(fetchResponse => {
                        cache.put(request, fetchResponse.clone());
                        return fetchResponse;
                    });
                });
            })
        );
        return;
    }

    // API 요청 네트워크 우선 전략
    if (url.pathname.startsWith('/api/') || url.pathname.startsWith('/shop/api/')) {
        event.respondWith(
            fetch(request)
                .then(response => {
                    if (response.ok) {
                        const responseClone = response.clone();
                        caches.open(CACHE_NAME).then(cache => {
                            cache.put(request, responseClone);
                        });
                    }
                    return response;
                })
                .catch(() => {
                    return caches.match(request);
                })
        );
        return;
    }

    // 페이지 요청 캐시 우선 전략
    if (request.mode === 'navigate' || 
        (request.method === 'GET' && request.headers.get('accept').includes('text/html'))) {
        event.respondWith(
            caches.match(request)
                .then(response => {
                    if (response) {
                        // 백그라운드에서 업데이트
                        fetch(request).then(fetchResponse => {
                            if (fetchResponse.ok) {
                                caches.open(CACHE_NAME).then(cache => {
                                    cache.put(request, fetchResponse);
                                });
                            }
                        });
                        return response;
                    }
                    return fetch(request).then(fetchResponse => {
                        if (fetchResponse.ok) {
                            const responseClone = fetchResponse.clone();
                            caches.open(CACHE_NAME).then(cache => {
                                cache.put(request, responseClone);
                            });
                        }
                        return fetchResponse;
                    });
                })
                .catch(() => {
                    // 오프라인 시 기본 페이지 반환
                    return caches.match('/offline.html');
                })
        );
        return;
    }

    // 기타 요청 기본 처리
    event.respondWith(
        caches.match(request).then(response => {
            return response || fetch(request);
        })
    );
});

// 백그라운드 동기화
self.addEventListener('sync', event => {
    if (event.tag === 'background-sync') {
        event.waitUntil(doBackgroundSync());
    }
});

function doBackgroundSync() {
    // 오프라인 중 저장된 요청들 처리
    return new Promise((resolve) => {
        // 실제 구현에서는 IndexedDB에서 저장된 요청들을 가져와서 처리
        resolve();
    });
}

// 푸시 알림
self.addEventListener('push', event => {
    const options = {
        body: event.data ? event.data.text() : '새로운 알림이 있습니다.',
        icon: '/images/icon-192x192.png',
        badge: '/images/badge-72x72.png',
        vibrate: [100, 50, 100],
        data: {
            dateOfArrival: Date.now(),
            primaryKey: '1'
        },
        actions: [
            {
                action: 'explore', 
                title: '확인',
                icon: '/images/checkmark.png'
            },
            {
                action: 'close', 
                title: '닫기',
                icon: '/images/xmark.png'
            }
        ]
    };

    event.waitUntil(
        self.registration.showNotification('Ahhob Shop', options)
    );
});

// 알림 클릭 처리
self.addEventListener('notificationclick', event => {
    event.notification.close();

    if (event.action === 'explore') {
        event.waitUntil(
            clients.openWindow('/shop')
        );
    } else if (event.action === 'close') {
        // 알림만 닫기
    } else {
        event.waitUntil(
            clients.openWindow('/')
        );
    }
});