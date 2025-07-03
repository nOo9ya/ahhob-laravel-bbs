#!/bin/bash

# SSL 인증서 갱신 스크립트
# 이 스크립트는 cron으로 정기적으로 실행되어 SSL 인증서를 자동 갱신합니다.

set -e

echo "Starting SSL certificate renewal process..."

# Certbot 컨테이너로 인증서 갱신 시도
docker-compose -f docker-compose.prod.yml run --rm certbot renew --quiet

# Nginx 설정 테스트
if docker-compose -f docker-compose.prod.yml exec nginx nginx -t; then
    echo "Nginx configuration test passed. Reloading Nginx..."
    # Nginx 재로드 (다운타임 없이)
    docker-compose -f docker-compose.prod.yml exec nginx nginx -s reload
    echo "SSL certificate renewal completed successfully!"
else
    echo "Nginx configuration test failed. Please check the configuration."
    exit 1
fi

# 갱신된 인증서 정보 출력
echo "Current certificate information:"
docker-compose -f docker-compose.prod.yml run --rm certbot certificates