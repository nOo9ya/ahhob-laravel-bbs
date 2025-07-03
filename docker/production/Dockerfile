# ====================================
# Stage 1: Base PHP Runtime
# ====================================
FROM php:8.3-fpm-alpine AS base

# 시스템 의존성 설치 (런타임에 필요한 것만)
RUN apk add --no-cache \
    libpng \
    libjpeg-turbo \
    freetype \
    libzip \
    oniguruma \
    postgresql-libs \
    icu \
    imagemagick \
    supervisor \
    nginx \
    && rm -rf /var/cache/apk/*

# PHP 확장 설치
RUN apk add --no-cache --virtual .build-deps \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    oniguruma-dev \
    postgresql-dev \
    icu-dev \
    imagemagick-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        bcmath \
        exif \
        gd \
        intl \
        mbstring \
        opcache \
        pdo \
        pdo_mysql \
        pdo_pgsql \
        zip \
    && pecl install imagick redis \
    && docker-php-ext-enable imagick redis \
    && apk del .build-deps

# ====================================
# Stage 2: Composer Dependencies
# ====================================
FROM composer:2 AS composer

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# ====================================
# Stage 3: Frontend Build
# ====================================
FROM node:20-alpine AS frontend

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --only=production

COPY . .
RUN npm run build

# ====================================
# Stage 4: Production Runtime
# ====================================
FROM base AS production

# 작업 디렉토리 설정
WORKDIR /var/www/html

# PHP 설정 복사
COPY docker/production/php/php.ini /usr/local/etc/php/conf.d/99-custom.ini
COPY docker/production/php/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

# Supervisor 설정
COPY docker/production/supervisor/ /etc/supervisor/conf.d/

# 애플리케이션 파일 복사 (선택적)
COPY artisan ./
COPY app/ ./app/
COPY bootstrap/ ./bootstrap/
COPY config/ ./config/
COPY database/ ./database/
COPY public/ ./public/
COPY resources/ ./resources/
COPY routes/ ./routes/
COPY storage/ ./storage/

# Composer 의존성 복사
COPY --from=composer /app/vendor ./vendor

# 프론트엔드 빌드 결과 복사
COPY --from=frontend /app/public/build ./public/build

# 권한 설정
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# 시작 스크립트 복사
COPY docker/production/start.sh /start.sh
RUN chmod +x /start.sh

# 포트 노출
EXPOSE 9000

# 헬스체크
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD php-fpm-healthcheck || exit 1

# 시작 명령
ENTRYPOINT ["/start.sh"]