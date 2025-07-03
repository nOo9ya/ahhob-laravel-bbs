/**
 * Ahhob Shop Mobile JavaScript Functions
 * 모바일 특화 JavaScript 기능들
 */

// 모바일 관련 전역 변수
window.ShopMobile = {
    isTouch: 'ontouchstart' in window,
    screenWidth: window.innerWidth,
    scrollPosition: 0,
    navOpen: false,
    filterOpen: false
};

/**
 * 모바일 네비게이션 관리
 */
const MobileNav = {
    init: function() {
        this.setupMenuToggle();
        this.setupOverlay();
        this.setupGestures();
    },

    setupMenuToggle: function() {
        const menuButton = document.querySelector('.mobile-menu-button');
        const nav = document.querySelector('.nav-mobile');
        
        if (menuButton && nav) {
            menuButton.addEventListener('click', () => {
                this.toggle();
            });
        }
    },

    setupOverlay: function() {
        const overlay = document.querySelector('.nav-mobile-overlay');
        if (overlay) {
            overlay.addEventListener('click', () => {
                this.close();
            });
        }
    },

    setupGestures: function() {
        if (!ShopMobile.isTouch) return;

        let startX = 0;
        let currentX = 0;
        let isDragging = false;

        document.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
            if (startX < 20) { // 화면 왼쪽 가장자리에서 시작
                isDragging = true;
            }
        });

        document.addEventListener('touchmove', (e) => {
            if (!isDragging) return;
            currentX = e.touches[0].clientX;
            const diffX = currentX - startX;

            if (diffX > 50 && !ShopMobile.navOpen) {
                this.open();
                isDragging = false;
            } else if (diffX < -50 && ShopMobile.navOpen) {
                this.close();
                isDragging = false;
            }
        });

        document.addEventListener('touchend', () => {
            isDragging = false;
        });
    },

    toggle: function() {
        if (ShopMobile.navOpen) {
            this.close();
        } else {
            this.open();
        }
    },

    open: function() {
        const nav = document.querySelector('.nav-mobile');
        const overlay = document.querySelector('.nav-mobile-overlay');
        
        if (nav && overlay) {
            nav.classList.add('open');
            overlay.classList.add('show');
            document.body.style.overflow = 'hidden';
            ShopMobile.navOpen = true;
        }
    },

    close: function() {
        const nav = document.querySelector('.nav-mobile');
        const overlay = document.querySelector('.nav-mobile-overlay');
        
        if (nav && overlay) {
            nav.classList.remove('open');
            overlay.classList.remove('show');
            document.body.style.overflow = '';
            ShopMobile.navOpen = false;
        }
    }
};

/**
 * 모바일 필터 모달
 */
const MobileFilter = {
    init: function() {
        this.setupFilterButton();
        this.setupFilterModal();
    },

    setupFilterButton: function() {
        const filterButton = document.querySelector('.mobile-filter-button');
        if (filterButton) {
            filterButton.addEventListener('click', () => {
                this.open();
            });
        }
    },

    setupFilterModal: function() {
        const modal = document.querySelector('.filter-modal-mobile');
        const closeButton = document.querySelector('.filter-close-button');
        const applyButton = document.querySelector('.filter-apply-button');

        if (closeButton) {
            closeButton.addEventListener('click', () => {
                this.close();
            });
        }

        if (applyButton) {
            applyButton.addEventListener('click', () => {
                this.apply();
            });
        }

        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.close();
                }
            });
        }
    },

    open: function() {
        const modal = document.querySelector('.filter-modal-mobile');
        if (modal) {
            modal.style.display = 'flex';
            setTimeout(() => {
                modal.classList.add('show');
            }, 10);
            document.body.style.overflow = 'hidden';
            ShopMobile.filterOpen = true;
        }
    },

    close: function() {
        const modal = document.querySelector('.filter-modal-mobile');
        if (modal) {
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
                document.body.style.overflow = '';
            }, 300);
            ShopMobile.filterOpen = false;
        }
    },

    apply: function() {
        // 필터 적용 로직
        const form = document.querySelector('.filter-form-mobile');
        if (form) {
            form.submit();
        }
        this.close();
    }
};

/**
 * 터치 제스처 지원
 */
const TouchGestures = {
    init: function() {
        this.setupSwipeGestures();
        this.setupPinchZoom();
    },

    setupSwipeGestures: function() {
        const swipeableElements = document.querySelectorAll('.swipeable');
        
        swipeableElements.forEach(element => {
            let startX = 0;
            let startY = 0;
            let endX = 0;
            let endY = 0;

            element.addEventListener('touchstart', (e) => {
                startX = e.touches[0].clientX;
                startY = e.touches[0].clientY;
            });

            element.addEventListener('touchend', (e) => {
                endX = e.changedTouches[0].clientX;
                endY = e.changedTouches[0].clientY;
                this.handleSwipe(element, startX, startY, endX, endY);
            });
        });
    },

    handleSwipe: function(element, startX, startY, endX, endY) {
        const diffX = endX - startX;
        const diffY = endY - startY;
        const threshold = 50;

        if (Math.abs(diffX) > Math.abs(diffY)) {
            if (Math.abs(diffX) > threshold) {
                if (diffX > 0) {
                    this.onSwipeRight(element);
                } else {
                    this.onSwipeLeft(element);
                }
            }
        } else {
            if (Math.abs(diffY) > threshold) {
                if (diffY > 0) {
                    this.onSwipeDown(element);
                } else {
                    this.onSwipeUp(element);
                }
            }
        }
    },

    onSwipeLeft: function(element) {
        const event = new CustomEvent('swipeLeft', { detail: { element } });
        element.dispatchEvent(event);
    },

    onSwipeRight: function(element) {
        const event = new CustomEvent('swipeRight', { detail: { element } });
        element.dispatchEvent(event);
    },

    onSwipeUp: function(element) {
        const event = new CustomEvent('swipeUp', { detail: { element } });
        element.dispatchEvent(event);
    },

    onSwipeDown: function(element) {
        const event = new CustomEvent('swipeDown', { detail: { element } });
        element.dispatchEvent(event);
    },

    setupPinchZoom: function() {
        const zoomableImages = document.querySelectorAll('.zoomable-image');
        
        zoomableImages.forEach(img => {
            let scale = 1;
            let lastTouchDistance = 0;

            img.addEventListener('touchstart', (e) => {
                if (e.touches.length === 2) {
                    lastTouchDistance = this.getTouchDistance(e.touches[0], e.touches[1]);
                }
            });

            img.addEventListener('touchmove', (e) => {
                if (e.touches.length === 2) {
                    e.preventDefault();
                    const currentDistance = this.getTouchDistance(e.touches[0], e.touches[1]);
                    const scaleChange = currentDistance / lastTouchDistance;
                    scale *= scaleChange;
                    scale = Math.min(Math.max(scale, 1), 3); // 1x ~ 3x 줌
                    
                    img.style.transform = `scale(${scale})`;
                    lastTouchDistance = currentDistance;
                }
            });

            img.addEventListener('touchend', (e) => {
                if (e.touches.length === 0) {
                    // 더블탭으로 줌 리셋
                    setTimeout(() => {
                        if (scale > 1) {
                            scale = 1;
                            img.style.transform = 'scale(1)';
                        }
                    }, 300);
                }
            });
        });
    },

    getTouchDistance: function(touch1, touch2) {
        const dx = touch1.clientX - touch2.clientX;
        const dy = touch1.clientY - touch2.clientY;
        return Math.sqrt(dx * dx + dy * dy);
    }
};

/**
 * 모바일 토스트 메시지
 */
const MobileToast = {
    show: function(message, type = 'info', duration = 3000) {
        const toast = document.createElement('div');
        toast.className = `toast-mobile ${type}`;
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('show');
        }, 10);

        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 300);
        }, duration);
    },

    success: function(message, duration) {
        this.show(message, 'success', duration);
    },

    error: function(message, duration) {
        this.show(message, 'error', duration);
    }
};

/**
 * 모바일 이미지 갤러리
 */
const MobileImageGallery = {
    init: function() {
        this.setupGallery();
        this.setupLightbox();
    },

    setupGallery: function() {
        const galleries = document.querySelectorAll('.image-gallery-mobile');
        
        galleries.forEach(gallery => {
            const mainImage = gallery.querySelector('.main-image');
            const thumbnails = gallery.querySelectorAll('.thumbnail');
            
            thumbnails.forEach(thumbnail => {
                thumbnail.addEventListener('click', () => {
                    const newSrc = thumbnail.getAttribute('data-full-image') || thumbnail.src;
                    if (mainImage) {
                        mainImage.src = newSrc;
                        
                        // 활성 썸네일 표시
                        thumbnails.forEach(t => t.classList.remove('active'));
                        thumbnail.classList.add('active');
                    }
                });
            });

            // 스와이프로 이미지 변경
            if (mainImage) {
                mainImage.addEventListener('swipeLeft', () => {
                    this.nextImage(gallery);
                });

                mainImage.addEventListener('swipeRight', () => {
                    this.prevImage(gallery);
                });
            }
        });
    },

    nextImage: function(gallery) {
        const thumbnails = gallery.querySelectorAll('.thumbnail');
        const activeThumbnail = gallery.querySelector('.thumbnail.active');
        const currentIndex = Array.from(thumbnails).indexOf(activeThumbnail);
        const nextIndex = (currentIndex + 1) % thumbnails.length;
        
        thumbnails[nextIndex].click();
    },

    prevImage: function(gallery) {
        const thumbnails = gallery.querySelectorAll('.thumbnail');
        const activeThumbnail = gallery.querySelector('.thumbnail.active');
        const currentIndex = Array.from(thumbnails).indexOf(activeThumbnail);
        const prevIndex = currentIndex === 0 ? thumbnails.length - 1 : currentIndex - 1;
        
        thumbnails[prevIndex].click();
    },

    setupLightbox: function() {
        const images = document.querySelectorAll('.lightbox-trigger');
        
        images.forEach(img => {
            img.addEventListener('click', () => {
                this.openLightbox(img.src);
            });
        });
    },

    openLightbox: function(imageSrc) {
        const lightbox = document.createElement('div');
        lightbox.className = 'lightbox-overlay';
        lightbox.innerHTML = `
            <div class="lightbox-content">
                <img src="${imageSrc}" alt="" class="lightbox-image">
                <button class="lightbox-close">&times;</button>
            </div>
        `;
        
        document.body.appendChild(lightbox);
        document.body.style.overflow = 'hidden';
        
        // 닫기 버튼
        lightbox.querySelector('.lightbox-close').addEventListener('click', () => {
            this.closeLightbox(lightbox);
        });
        
        // 배경 클릭으로 닫기
        lightbox.addEventListener('click', (e) => {
            if (e.target === lightbox) {
                this.closeLightbox(lightbox);
            }
        });
    },

    closeLightbox: function(lightbox) {
        document.body.style.overflow = '';
        document.body.removeChild(lightbox);
    }
};

/**
 * 무한 스크롤
 */
const InfiniteScroll = {
    init: function() {
        this.setupInfiniteScroll();
    },

    setupInfiniteScroll: function() {
        const container = document.querySelector('.infinite-scroll-container');
        const loader = document.querySelector('.infinite-scroll-loader');
        
        if (!container) return;

        let loading = false;
        let page = 1;
        let hasMore = true;

        const loadMore = async () => {
            if (loading || !hasMore) return;
            
            loading = true;
            if (loader) loader.style.display = 'block';

            try {
                const response = await fetch(`${window.location.pathname}?page=${page + 1}&ajax=1`);
                const data = await response.json();
                
                if (data.html) {
                    container.insertAdjacentHTML('beforeend', data.html);
                    page++;
                    hasMore = data.hasMore;
                } else {
                    hasMore = false;
                }
            } catch (error) {
                console.error('Infinite scroll error:', error);
            } finally {
                loading = false;
                if (loader) loader.style.display = 'none';
            }
        };

        // 스크롤 이벤트 리스너
        let throttleTimer = null;
        window.addEventListener('scroll', () => {
            if (throttleTimer) return;
            
            throttleTimer = setTimeout(() => {
                const scrollTop = window.pageYOffset;
                const windowHeight = window.innerHeight;
                const documentHeight = document.documentElement.scrollHeight;
                
                if (scrollTop + windowHeight >= documentHeight - 1000) {
                    loadMore();
                }
                
                throttleTimer = null;
            }, 100);
        });
    }
};

/**
 * 모바일 장바구니 플로팅 버튼
 */
const FloatingCart = {
    init: function() {
        this.setupFloatingButton();
        this.updateCartCount();
    },

    setupFloatingButton: function() {
        const button = document.querySelector('.floating-cart-mobile');
        if (button) {
            button.addEventListener('click', () => {
                window.location.href = '/shop/cart';
            });

            // 스크롤 시 버튼 숨기기/보이기
            let lastScrollTop = 0;
            window.addEventListener('scroll', () => {
                const scrollTop = window.pageYOffset;
                
                if (scrollTop > lastScrollTop && scrollTop > 100) {
                    // 아래로 스크롤
                    button.style.transform = 'translateY(100px)';
                } else {
                    // 위로 스크롤
                    button.style.transform = 'translateY(0)';
                }
                
                lastScrollTop = scrollTop;
            });
        }
    },

    updateCartCount: function() {
        const badge = document.querySelector('.floating-cart-mobile .cart-badge');
        if (badge && window.ShopApp && window.ShopApp.cart) {
            badge.textContent = window.ShopApp.cart.count;
            badge.style.display = window.ShopApp.cart.count > 0 ? 'flex' : 'none';
        }
    }
};

/**
 * 성능 최적화
 */
const MobileOptimization = {
    init: function() {
        this.setupLazyLoading();
        this.setupImageOptimization();
        this.setupCacheStrategy();
    },

    setupLazyLoading: function() {
        if ('IntersectionObserver' in window) {
            const images = document.querySelectorAll('img[data-src]');
            const imageObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        imageObserver.unobserve(img);
                    }
                });
            });

            images.forEach(img => imageObserver.observe(img));
        }
    },

    setupImageOptimization: function() {
        const images = document.querySelectorAll('img');
        images.forEach(img => {
            // WebP 지원 감지
            if (this.supportsWebP()) {
                const src = img.src;
                if (src && !src.includes('.webp')) {
                    const webpSrc = src.replace(/\.(jpg|jpeg|png)$/i, '.webp');
                    img.src = webpSrc;
                }
            }
        });
    },

    supportsWebP: function() {
        const canvas = document.createElement('canvas');
        canvas.width = 1;
        canvas.height = 1;
        return canvas.toDataURL('image/webp').indexOf('data:image/webp') === 0;
    },

    setupCacheStrategy: function() {
        // Service Worker 등록 (별도 파일 필요)
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js')
                .then(registration => {
                    console.log('SW registered:', registration);
                })
                .catch(error => {
                    console.log('SW registration failed:', error);
                });
        }
    }
};

/**
 * 초기화
 */
document.addEventListener('DOMContentLoaded', function() {
    // 모바일 환경 감지
    if (window.innerWidth <= 768) {
        MobileNav.init();
        MobileFilter.init();
        TouchGestures.init();
        MobileImageGallery.init();
        InfiniteScroll.init();
        FloatingCart.init();
        MobileOptimization.init();

        // 뷰포트 높이 조정 (모바일 브라우저 주소창 대응)
        const setVH = () => {
            document.documentElement.style.setProperty('--vh', `${window.innerHeight * 0.01}px`);
        };
        setVH();
        window.addEventListener('resize', setVH);

        // 전역 함수로 노출
        window.MobileToast = MobileToast;
        window.MobileNav = MobileNav;
        window.MobileFilter = MobileFilter;
    }
});

// 화면 크기 변경 시 재초기화
window.addEventListener('resize', function() {
    ShopMobile.screenWidth = window.innerWidth;
    
    // 데스크톱으로 전환 시 모바일 UI 초기화
    if (window.innerWidth > 768) {
        MobileNav.close();
        MobileFilter.close();
        document.body.style.overflow = '';
    }
});

// PWA 관련 기능
window.addEventListener('beforeinstallprompt', (e) => {
    // 기본 설치 프롬프트 방지
    e.preventDefault();
    
    // 나중에 사용할 수 있도록 저장
    window.deferredPrompt = e;
    
    // 사용자 정의 설치 버튼 표시
    const installButton = document.querySelector('.pwa-install-button');
    if (installButton) {
        installButton.style.display = 'block';
        installButton.addEventListener('click', () => {
            e.prompt();
            e.userChoice.then((choiceResult) => {
                if (choiceResult.outcome === 'accepted') {
                    console.log('PWA 설치됨');
                }
                window.deferredPrompt = null;
            });
        });
    }
});