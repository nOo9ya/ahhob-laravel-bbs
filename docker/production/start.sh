#!/bin/sh

set -e

# 환경 변수 기본값 설정
CONTAINER_ROLE=${CONTAINER_ROLE:-app}

echo "Container role: $CONTAINER_ROLE"

# Laravel 캐시 및 설정 최적화
if [ "$CONTAINER_ROLE" = "app" ] || [ "$CONTAINER_ROLE" = "queue" ] || [ "$CONTAINER_ROLE" = "scheduler" ]; then
    echo "Optimizing Laravel application..."
    
    # 설정 캐시
    php artisan config:cache
    
    # 라우트 캐시
    php artisan route:cache
    
    # 뷰 캐시
    php artisan view:cache
    
    # 이벤트 캐시
    php artisan event:cache
    
    # 마이그레이션 실행 (app 컨테이너에서만)
    if [ "$CONTAINER_ROLE" = "app" ]; then
        echo "Running database migrations..."
        php artisan migrate --force
        
        # 성능 최적화 인덱스 생성
        echo "Creating performance indexes..."
        php artisan ahhob:optimize --indexes --database
        
        # 캐시 워밍업
        echo "Warming up caches..."
        php artisan ahhob:optimize --warmup
    fi
fi

# 컨테이너 역할에 따른 실행
case "$CONTAINER_ROLE" in
    "app")
        echo "Starting PHP-FPM application server..."
        exec php-fpm
        ;;
    "queue")
        echo "Starting queue worker..."
        exec php artisan queue:work redis \
            --sleep=3 \
            --tries=3 \
            --max-time=3600 \
            --memory=512 \
            --timeout=300
        ;;
    "scheduler")
        echo "Starting scheduler..."
        # 스케줄러를 위한 cron 시작
        exec crond -f -l 2
        ;;
    *)
        echo "Unknown container role: $CONTAINER_ROLE"
        echo "Available roles: app, queue, scheduler"
        exit 1
        ;;
esac