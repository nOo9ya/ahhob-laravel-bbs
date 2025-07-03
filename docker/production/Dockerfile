# 멀티 스테이지 빌드를 사용한 프로덕션 Dockerfile
FROM php:8.3-fpm-alpine AS base

# 시스템 의존성 설치
RUN apk add --no-cache \
    curl \
    git \
    zip \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    oniguruma-dev \
    postgresql-dev \
    icu-dev \
    imagemagick-dev \
    supervisor \
    nginx \
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
    && docker-php-ext-enable imagick redis

# Composer 설치
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 작업 디렉토리 설정
WORKDIR /var/www/html

# PHP 설정 복사
COPY docker/production/php/php.ini /usr/local/etc/php/conf.d/99-custom.ini
COPY docker/production/php/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

# Supervisor 설정
COPY docker/production/supervisor/ /etc/supervisor/conf.d/

# 애플리케이션 파일 복사
COPY . .

# Composer 의존성 설치 (프로덕션 모드)
RUN composer install --no-dev --optimize-autoloader --no-scripts

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