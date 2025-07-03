#!/bin/bash

# Ahhob Laravel BBS 배포 스크립트
# 사용법: ./deploy.sh [환경] [버전]
# 예시: ./deploy.sh production v1.0.0

set -e  # 에러 발생 시 스크립트 종료

# 색상 정의
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 로그 함수
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# 변수 설정
ENVIRONMENT=${1:-production}
VERSION=${2:-latest}
PROJECT_DIR="/var/www/ahhob"
BACKUP_DIR="/var/backups/ahhob"
CURRENT_DATE=$(date +"%Y%m%d_%H%M%S")

log_info "Ahhob Laravel BBS 배포 시작"
log_info "환경: $ENVIRONMENT"
log_info "버전: $VERSION"
log_info "배포 시간: $CURRENT_DATE"

# 사전 요구사항 확인
check_requirements() {
    log_info "사전 요구사항 확인 중..."
    
    # Docker 설치 확인
    if ! command -v docker &> /dev/null; then
        log_error "Docker가 설치되어 있지 않습니다."
        exit 1
    fi
    
    # Docker Compose 설치 확인
    if ! command -v docker-compose &> /dev/null; then
        log_error "Docker Compose가 설치되어 있지 않습니다."
        exit 1
    fi
    
    # 환경 파일 확인
    if [ ! -f ".env.$ENVIRONMENT" ]; then
        log_error ".env.$ENVIRONMENT 파일이 없습니다."
        exit 1
    fi
    
    log_success "모든 요구사항이 충족되었습니다."
}

# 백업 생성
create_backup() {
    log_info "기존 데이터 백업 중..."
    
    # 백업 디렉토리 생성
    mkdir -p "$BACKUP_DIR"
    
    # 데이터베이스 백업
    if docker ps | grep -q ahhob-database; then
        log_info "데이터베이스 백업 중..."
        docker exec ahhob-database mysqldump -u root -p"$DB_ROOT_PASSWORD" ahhob_production > "$BACKUP_DIR/database_$CURRENT_DATE.sql"
        log_success "데이터베이스 백업 완료: $BACKUP_DIR/database_$CURRENT_DATE.sql"
    fi
    
    # 파일 백업
    if [ -d "$PROJECT_DIR/storage" ]; then
        log_info "파일 백업 중..."
        tar -czf "$BACKUP_DIR/files_$CURRENT_DATE.tar.gz" -C "$PROJECT_DIR" storage public/uploads
        log_success "파일 백업 완료: $BACKUP_DIR/files_$CURRENT_DATE.tar.gz"
    fi
    
    # 오래된 백업 정리 (30일 이상)
    find "$BACKUP_DIR" -name "*.sql" -mtime +30 -delete
    find "$BACKUP_DIR" -name "*.tar.gz" -mtime +30 -delete
    
    log_success "백업 완료"
}

# 애플리케이션 중지
stop_application() {
    log_info "기존 애플리케이션 중지 중..."
    
    if [ -f "docker-compose.prod.yml" ]; then
        docker-compose -f docker-compose.prod.yml down --remove-orphans
        log_success "애플리케이션이 중지되었습니다."
    else
        log_warning "docker-compose.prod.yml 파일이 없습니다."
    fi
}

# 환경 설정
setup_environment() {
    log_info "환경 설정 중..."
    
    # 환경 파일 복사
    cp ".env.$ENVIRONMENT" ".env"
    log_success "환경 파일이 설정되었습니다."
    
    # 디렉토리 권한 설정
    sudo chown -R $USER:$USER .
    chmod -R 755 .
    chmod -R 775 storage bootstrap/cache
    
    # 필요한 디렉토리 생성
    mkdir -p storage/logs/nginx
    mkdir -p storage/logs/mysql
    mkdir -p public/uploads
    mkdir -p docker/production/ssl/ahhob.com
    
    log_success "디렉토리 권한이 설정되었습니다."
}

# SSL 인증서 확인/생성
setup_ssl() {
    log_info "SSL 인증서 확인 중..."
    
    SSL_DIR="docker/production/ssl/ahhob.com"
    
    if [ ! -f "$SSL_DIR/fullchain.pem" ] || [ ! -f "$SSL_DIR/privkey.pem" ]; then
        log_warning "SSL 인증서가 없습니다."
        log_info "Let's Encrypt를 사용하여 인증서를 생성하세요:"
        log_info "1. Certbot 설치: sudo apt install certbot"
        log_info "2. 인증서 생성: sudo certbot certonly --standalone -d ahhob.com -d www.ahhob.com"
        log_info "3. 인증서 복사:"
        log_info "   sudo cp /etc/letsencrypt/live/ahhob.com/fullchain.pem $SSL_DIR/"
        log_info "   sudo cp /etc/letsencrypt/live/ahhob.com/privkey.pem $SSL_DIR/"
        log_info "   sudo chown $USER:$USER $SSL_DIR/*"
        
        # 임시 자체 서명 인증서 생성
        log_info "임시 자체 서명 인증서를 생성합니다..."
        openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
            -keyout "$SSL_DIR/privkey.pem" \
            -out "$SSL_DIR/fullchain.pem" \
            -subj "/C=KR/ST=Seoul/L=Seoul/O=Ahhob/CN=ahhob.com"
        
        log_warning "자체 서명 인증서가 생성되었습니다. 프로덕션에서는 Let's Encrypt 인증서를 사용하세요."
    else
        log_success "SSL 인증서가 확인되었습니다."
    fi
}

# 애플리케이션 빌드 및 시작
build_and_start() {
    log_info "애플리케이션 빌드 및 시작 중..."
    
    # Docker 이미지 빌드
    docker-compose -f docker-compose.prod.yml build --no-cache
    log_success "Docker 이미지가 빌드되었습니다."
    
    # 컨테이너 시작
    docker-compose -f docker-compose.prod.yml up -d
    log_success "컨테이너가 시작되었습니다."
    
    # 컨테이너 상태 확인
    sleep 10
    log_info "컨테이너 상태 확인 중..."
    docker-compose -f docker-compose.prod.yml ps
}

# 애플리케이션 초기화
initialize_application() {
    log_info "애플리케이션 초기화 중..."
    
    # 애플리케이션 컨테이너가 준비될 때까지 대기
    log_info "애플리케이션 컨테이너 준비 대기 중..."
    until docker exec ahhob-app php -v > /dev/null 2>&1; do
        echo -n "."
        sleep 2
    done
    echo ""
    
    # Laravel 키 생성 (처음 배포시에만)
    if ! docker exec ahhob-app php artisan key:generate --show | grep -q "base64:"; then
        log_info "Laravel 애플리케이션 키 생성 중..."
        docker exec ahhob-app php artisan key:generate --force
    fi
    
    # 데이터베이스가 준비될 때까지 대기
    log_info "데이터베이스 연결 대기 중..."
    until docker exec ahhob-database mysqladmin ping -h"localhost" --silent; do
        echo -n "."
        sleep 2
    done
    echo ""
    
    # 마이그레이션 실행
    log_info "데이터베이스 마이그레이션 실행 중..."
    docker exec ahhob-app php artisan migrate --force
    
    # 시드 데이터 생성 (필요시)
    if [ "$ENVIRONMENT" = "production" ] && [ ! -f ".seeded" ]; then
        log_info "기본 데이터 생성 중..."
        docker exec ahhob-app php artisan db:seed --class=ProductionSeeder --force
        touch .seeded
    fi
    
    # 캐시 최적화
    log_info "캐시 최적화 중..."
    docker exec ahhob-app php artisan ahhob:optimize --all
    
    log_success "애플리케이션 초기화가 완료되었습니다."
}

# 헬스체크
health_check() {
    log_info "헬스체크 실행 중..."
    
    # 웹 서버 응답 확인
    for i in {1..30}; do
        if curl -f -s http://localhost/health > /dev/null; then
            log_success "웹 서버가 정상적으로 응답합니다."
            break
        fi
        
        if [ $i -eq 30 ]; then
            log_error "웹 서버가 응답하지 않습니다."
            exit 1
        fi
        
        echo -n "."
        sleep 2
    done
    echo ""
    
    # 데이터베이스 연결 확인
    if docker exec ahhob-app php artisan tinker --execute="DB::connection()->getPdo(); echo 'Database OK';" | grep -q "Database OK"; then
        log_success "데이터베이스 연결이 정상입니다."
    else
        log_error "데이터베이스 연결에 문제가 있습니다."
        exit 1
    fi
    
    # Redis 연결 확인
    if docker exec ahhob-app php artisan tinker --execute="Redis::ping(); echo 'Redis OK';" | grep -q "Redis OK"; then
        log_success "Redis 연결이 정상입니다."
    else
        log_error "Redis 연결에 문제가 있습니다."
        exit 1
    fi
}

# 배포 후 정리
cleanup() {
    log_info "배포 후 정리 중..."
    
    # 사용하지 않는 Docker 이미지 정리
    docker image prune -f
    
    # 로그 파일 권한 설정
    sudo chown -R $USER:$USER storage/logs
    
    log_success "정리가 완료되었습니다."
}

# 배포 정보 출력
show_deployment_info() {
    log_success "배포가 완료되었습니다!"
    echo ""
    echo "배포 정보:"
    echo "- 환경: $ENVIRONMENT"
    echo "- 버전: $VERSION"
    echo "- 배포 시간: $CURRENT_DATE"
    echo ""
    echo "접속 정보:"
    echo "- 웹사이트: https://www.ahhob.com"
    echo "- 관리자: https://www.ahhob.com/admin"
    echo "- 모니터링: http://localhost:3000 (Grafana)"
    echo ""
    echo "유용한 명령어:"
    echo "- 로그 확인: docker-compose -f docker-compose.prod.yml logs -f"
    echo "- 컨테이너 상태: docker-compose -f docker-compose.prod.yml ps"
    echo "- 애플리케이션 재시작: docker-compose -f docker-compose.prod.yml restart app"
    echo "- 성능 최적화: docker exec ahhob-app php artisan ahhob:optimize"
}

# 에러 처리
handle_error() {
    log_error "배포 중 오류가 발생했습니다!"
    log_info "롤백을 수행하려면 다음 명령을 실행하세요:"
    echo "docker-compose -f docker-compose.prod.yml down"
    echo "# 백업에서 복원..."
    exit 1
}

# 에러 트랩 설정
trap handle_error ERR

# 메인 실행 흐름
main() {
    check_requirements
    create_backup
    stop_application
    setup_environment
    setup_ssl
    build_and_start
    initialize_application
    health_check
    cleanup
    show_deployment_info
}

# 스크립트 실행
main "$@"