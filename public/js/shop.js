/**
 * Ahhob Shop JavaScript Functions
 * 쇼핑몰 공통 JavaScript 기능들
 */

// 전역 변수
window.ShopApp = {
    cart: {
        count: 0,
        total: 0
    },
    wishlist: {
        count: 0
    },
    config: {
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        baseUrl: window.location.origin
    }
};

/**
 * 장바구니 관련 기능
 */
const Cart = {
    // 장바구니에 상품 추가
    add: function(productId, quantity = 1, options = {}) {
        return fetch('/shop/cart/add', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': ShopApp.config.csrfToken
            },
            body: JSON.stringify({
                product_id: productId,
                quantity: quantity,
                options: options
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateCount(data.cart_count);
                this.showMessage('상품이 장바구니에 추가되었습니다.', 'success');
                this.updateMiniCart(data.cart_items);
            } else {
                this.showMessage(data.message || '오류가 발생했습니다.', 'error');
            }
            return data;
        })
        .catch(error => {
            console.error('Cart add error:', error);
            this.showMessage('네트워크 오류가 발생했습니다.', 'error');
        });
    },

    // 장바구니 상품 수량 업데이트
    updateQuantity: function(itemId, quantity) {
        return fetch(`/shop/cart/${itemId}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': ShopApp.config.csrfToken
            },
            body: JSON.stringify({ quantity: quantity })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateCount(data.cart_count);
                this.updateCartTotals(data.totals);
            } else {
                this.showMessage(data.message || '수량 업데이트에 실패했습니다.', 'error');
            }
            return data;
        });
    },

    // 장바구니에서 상품 제거
    remove: function(itemId) {
        if (!confirm('이 상품을 장바구니에서 제거하시겠습니까?')) {
            return Promise.resolve({ cancelled: true });
        }

        return fetch(`/shop/cart/${itemId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': ShopApp.config.csrfToken
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateCount(data.cart_count);
                this.removeCartItem(itemId);
                this.showMessage('상품이 제거되었습니다.', 'success');
            } else {
                this.showMessage(data.message || '제거에 실패했습니다.', 'error');
            }
            return data;
        });
    },

    // 장바구니 비우기
    clear: function() {
        if (!confirm('장바구니를 모두 비우시겠습니까?')) {
            return Promise.resolve({ cancelled: true });
        }

        return fetch('/shop/cart/clear', {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': ShopApp.config.csrfToken
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateCount(0);
                location.reload();
            }
            return data;
        });
    },

    // 장바구니 수량 업데이트
    updateCount: function(count) {
        ShopApp.cart.count = count;
        const countElements = document.querySelectorAll('.cart-count');
        countElements.forEach(element => {
            element.textContent = count;
            element.style.display = count > 0 ? 'inline' : 'none';
        });
    },

    // 장바구니 총액 업데이트
    updateCartTotals: function(totals) {
        const subtotalElements = document.querySelectorAll('.cart-subtotal');
        const shippingElements = document.querySelectorAll('.cart-shipping');
        const totalElements = document.querySelectorAll('.cart-total');

        subtotalElements.forEach(el => el.textContent = `₩${totals.subtotal.toLocaleString()}`);
        shippingElements.forEach(el => el.textContent = totals.shipping_cost > 0 ? `₩${totals.shipping_cost.toLocaleString()}` : '무료');
        totalElements.forEach(el => el.textContent = `₩${totals.total.toLocaleString()}`);
    },

    // 장바구니 아이템 제거 (DOM)
    removeCartItem: function(itemId) {
        const itemElement = document.querySelector(`[data-cart-item="${itemId}"]`);
        if (itemElement) {
            itemElement.remove();
        }
    },

    // 미니 장바구니 업데이트
    updateMiniCart: function(cartItems) {
        const miniCart = document.querySelector('.mini-cart-items');
        if (miniCart && cartItems) {
            // 미니 장바구니 HTML 업데이트
            miniCart.innerHTML = cartItems.map(item => `
                <div class="mini-cart-item flex items-center space-x-3 p-2">
                    <img src="${item.image || '/images/no-image.png'}" alt="${item.name}" class="w-12 h-12 object-cover rounded">
                    <div class="flex-1">
                        <h4 class="text-sm font-medium">${item.name}</h4>
                        <p class="text-xs text-gray-500">${item.quantity}개 × ₩${item.price.toLocaleString()}</p>
                    </div>
                </div>
            `).join('');
        }
    },

    // 메시지 표시
    showMessage: function(message, type = 'info') {
        // 토스트 메시지 또는 알림 표시
        if (typeof Notification !== 'undefined' && Notification.permission === 'granted') {
            new Notification(message);
        } else {
            alert(message);
        }
    }
};

/**
 * 위시리스트 관련 기능
 */
const Wishlist = {
    // 위시리스트 토글
    toggle: function(productId) {
        return fetch('/shop/wishlist/toggle', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': ShopApp.config.csrfToken
            },
            body: JSON.stringify({ product_id: productId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateWishlistButton(productId, data.is_wishlisted);
                this.updateCount(data.wishlist_count);
                Cart.showMessage(data.message, 'success');
            } else {
                Cart.showMessage(data.message || '오류가 발생했습니다.', 'error');
            }
            return data;
        })
        .catch(error => {
            console.error('Wishlist toggle error:', error);
            Cart.showMessage('로그인이 필요합니다.', 'error');
        });
    },

    // 위시리스트 버튼 상태 업데이트
    updateWishlistButton: function(productId, isWishlisted) {
        const buttons = document.querySelectorAll(`[data-wishlist-product="${productId}"]`);
        buttons.forEach(button => {
            const icon = button.querySelector('svg');
            if (isWishlisted) {
                button.classList.add('text-red-500');
                button.classList.remove('text-gray-400');
                if (icon) icon.setAttribute('fill', 'currentColor');
            } else {
                button.classList.add('text-gray-400');
                button.classList.remove('text-red-500');
                if (icon) icon.setAttribute('fill', 'none');
            }
        });
    },

    // 위시리스트 수량 업데이트
    updateCount: function(count) {
        ShopApp.wishlist.count = count;
        const countElements = document.querySelectorAll('.wishlist-count');
        countElements.forEach(element => {
            element.textContent = count;
            element.style.display = count > 0 ? 'inline' : 'none';
        });
    }
};

/**
 * 상품 관련 기능
 */
const Product = {
    // 상품 이미지 갤러리
    initImageGallery: function() {
        const mainImage = document.querySelector('.product-main-image');
        const thumbnails = document.querySelectorAll('.product-thumbnail');

        if (mainImage && thumbnails.length > 0) {
            thumbnails.forEach(thumbnail => {
                thumbnail.addEventListener('click', function() {
                    const newSrc = this.getAttribute('data-image');
                    if (newSrc) {
                        mainImage.src = newSrc;
                        
                        // 활성 썸네일 표시
                        thumbnails.forEach(t => t.classList.remove('ring-2', 'ring-blue-500'));
                        this.classList.add('ring-2', 'ring-blue-500');
                    }
                });
            });
        }
    },

    // 수량 조절 버튼
    initQuantityControls: function() {
        const decreaseButtons = document.querySelectorAll('.quantity-decrease');
        const increaseButtons = document.querySelectorAll('.quantity-increase');
        const quantityInputs = document.querySelectorAll('.quantity-input');

        decreaseButtons.forEach(button => {
            button.addEventListener('click', function() {
                const input = this.parentNode.querySelector('.quantity-input');
                const currentValue = parseInt(input.value);
                const minValue = parseInt(input.getAttribute('min')) || 1;
                
                if (currentValue > minValue) {
                    input.value = currentValue - 1;
                    input.dispatchEvent(new Event('change'));
                }
            });
        });

        increaseButtons.forEach(button => {
            button.addEventListener('click', function() {
                const input = this.parentNode.querySelector('.quantity-input');
                const currentValue = parseInt(input.value);
                const maxValue = parseInt(input.getAttribute('max')) || 99;
                
                if (currentValue < maxValue) {
                    input.value = currentValue + 1;
                    input.dispatchEvent(new Event('change'));
                }
            });
        });

        // 수량 입력 직접 변경 처리
        quantityInputs.forEach(input => {
            input.addEventListener('change', function() {
                const value = parseInt(this.value);
                const min = parseInt(this.getAttribute('min')) || 1;
                const max = parseInt(this.getAttribute('max')) || 99;

                if (value < min) this.value = min;
                if (value > max) this.value = max;

                // 장바구니 페이지에서 수량 변경 시 자동 업데이트
                const cartItemId = this.getAttribute('data-cart-item-id');
                if (cartItemId) {
                    Cart.updateQuantity(cartItemId, this.value);
                }
            });
        });
    },

    // 상품 옵션 선택
    initProductOptions: function() {
        const optionSelects = document.querySelectorAll('.product-option-select');
        
        optionSelects.forEach(select => {
            select.addEventListener('change', function() {
                this.updateProductPrice();
            });
        });
    },

    // 상품 가격 업데이트 (옵션에 따라)
    updateProductPrice: function() {
        const basePrice = parseFloat(document.querySelector('[data-base-price]')?.getAttribute('data-base-price')) || 0;
        const optionSelects = document.querySelectorAll('.product-option-select');
        let additionalPrice = 0;

        optionSelects.forEach(select => {
            const selectedOption = select.options[select.selectedIndex];
            const optionPrice = parseFloat(selectedOption.getAttribute('data-price')) || 0;
            additionalPrice += optionPrice;
        });

        const totalPrice = basePrice + additionalPrice;
        const priceElements = document.querySelectorAll('.product-total-price');
        
        priceElements.forEach(element => {
            element.textContent = `₩${totalPrice.toLocaleString()}`;
        });
    }
};

/**
 * 검색 관련 기능
 */
const Search = {
    // 자동완성 검색
    initAutocomplete: function() {
        const searchInput = document.querySelector('.search-input');
        const resultsContainer = document.querySelector('.search-results');

        if (searchInput) {
            let timeout;
            
            searchInput.addEventListener('input', function() {
                clearTimeout(timeout);
                const query = this.value.trim();

                if (query.length >= 2) {
                    timeout = setTimeout(() => {
                        this.fetchSuggestions(query);
                    }, 300);
                } else {
                    this.hideSuggestions();
                }
            });

            // 외부 클릭 시 결과 숨기기
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !resultsContainer?.contains(e.target)) {
                    Search.hideSuggestions();
                }
            });
        }
    },

    // 검색 제안 가져오기
    fetchSuggestions: function(query) {
        fetch(`/shop/search/suggestions?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                this.displaySuggestions(data.suggestions);
            })
            .catch(error => {
                console.error('Search suggestions error:', error);
            });
    },

    // 검색 제안 표시
    displaySuggestions: function(suggestions) {
        const resultsContainer = document.querySelector('.search-results');
        
        if (resultsContainer && suggestions.length > 0) {
            resultsContainer.innerHTML = suggestions.map(item => `
                <div class="search-suggestion p-2 hover:bg-gray-100 cursor-pointer" data-value="${item.name}">
                    <div class="flex items-center space-x-3">
                        ${item.image ? `<img src="${item.image}" alt="${item.name}" class="w-8 h-8 object-cover rounded">` : ''}
                        <div>
                            <div class="text-sm font-medium">${item.name}</div>
                            ${item.price ? `<div class="text-xs text-gray-500">₩${item.price.toLocaleString()}</div>` : ''}
                        </div>
                    </div>
                </div>
            `).join('');
            
            resultsContainer.style.display = 'block';
            
            // 제안 항목 클릭 처리
            resultsContainer.querySelectorAll('.search-suggestion').forEach(suggestion => {
                suggestion.addEventListener('click', function() {
                    const value = this.getAttribute('data-value');
                    document.querySelector('.search-input').value = value;
                    Search.hideSuggestions();
                });
            });
        }
    },

    // 검색 제안 숨기기
    hideSuggestions: function() {
        const resultsContainer = document.querySelector('.search-results');
        if (resultsContainer) {
            resultsContainer.style.display = 'none';
        }
    }
};

/**
 * 유틸리티 함수들
 */
const Utils = {
    // 숫자 포맷팅
    formatPrice: function(price) {
        return `₩${price.toLocaleString()}`;
    },

    // 로딩 상태 표시/숨기기
    showLoading: function(element) {
        if (element) {
            element.classList.add('loading');
            element.disabled = true;
        }
    },

    hideLoading: function(element) {
        if (element) {
            element.classList.remove('loading');
            element.disabled = false;
        }
    },

    // 모바일 감지
    isMobile: function() {
        return window.innerWidth <= 768;
    },

    // 쿠키 관리
    setCookie: function(name, value, days = 7) {
        const expires = new Date();
        expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
        document.cookie = `${name}=${value};expires=${expires.toUTCString()};path=/`;
    },

    getCookie: function(name) {
        const nameEQ = name + "=";
        const ca = document.cookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }
};

/**
 * 페이지 로드 시 초기화
 */
document.addEventListener('DOMContentLoaded', function() {
    // 상품 관련 기능 초기화
    Product.initImageGallery();
    Product.initQuantityControls();
    Product.initProductOptions();

    // 검색 자동완성 초기화
    Search.initAutocomplete();

    // 장바구니 수량 표시
    const cartCountElement = document.querySelector('.cart-count');
    if (cartCountElement) {
        ShopApp.cart.count = parseInt(cartCountElement.textContent) || 0;
    }

    // 위시리스트 수량 표시
    const wishlistCountElement = document.querySelector('.wishlist-count');
    if (wishlistCountElement) {
        ShopApp.wishlist.count = parseInt(wishlistCountElement.textContent) || 0;
    }

    // 전역 함수로 노출
    window.Cart = Cart;
    window.Wishlist = Wishlist;
    window.Product = Product;
    window.Search = Search;
    window.Utils = Utils;
});

// 빠른 장바구니 담기 (전역 함수)
window.addToCart = function(productId, quantity = 1) {
    return Cart.add(productId, quantity);
};

// 빠른 위시리스트 토글 (전역 함수)
window.toggleWishlist = function(productId) {
    return Wishlist.toggle(productId);
};