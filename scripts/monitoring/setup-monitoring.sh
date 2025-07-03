#!/bin/bash

# Ahhob 모니터링 시스템 설정 스크립트

set -e

# 색상 정의
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

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

# 모니터링 디렉토리 생성
setup_directories() {
    log_info "모니터링 디렉토리 설정 중..."
    
    mkdir -p monitoring/{grafana,prometheus,node-exporter,alertmanager}
    mkdir -p monitoring/grafana/{dashboards,provisioning}
    mkdir -p monitoring/prometheus/{rules,data}
    
    log_success "디렉토리 생성 완료"
}

# Prometheus 설정
setup_prometheus() {
    log_info "Prometheus 설정 중..."
    
    cat > monitoring/prometheus/prometheus.yml << 'EOF'
global:
  scrape_interval: 15s
  evaluation_interval: 15s

rule_files:
  - "rules/*.yml"

alerting:
  alertmanagers:
    - static_configs:
        - targets:
          - alertmanager:9093

scrape_configs:
  # Prometheus 자체 모니터링
  - job_name: 'prometheus'
    static_configs:
      - targets: ['localhost:9090']

  # Node Exporter (시스템 메트릭)
  - job_name: 'node-exporter'
    static_configs:
      - targets: ['node-exporter:9100']

  # Nginx 메트릭
  - job_name: 'nginx'
    static_configs:
      - targets: ['nginx:9113']

  # MySQL 메트릭
  - job_name: 'mysql'
    static_configs:
      - targets: ['mysql-exporter:9104']

  # Redis 메트릭
  - job_name: 'redis'
    static_configs:
      - targets: ['redis-exporter:9121']

  # Laravel 애플리케이션 메트릭
  - job_name: 'laravel'
    metrics_path: '/metrics'
    static_configs:
      - targets: ['app:9000']
    scrape_interval: 30s
EOF

    # Prometheus 알림 규칙
    cat > monitoring/prometheus/rules/alerts.yml << 'EOF'
groups:
  - name: system.rules
    rules:
      # 시스템 리소스 알림
      - alert: HighCpuUsage
        expr: 100 - (avg by(instance) (irate(node_cpu_seconds_total{mode="idle"}[5m])) * 100) > 80
        for: 5m
        labels:
          severity: warning
        annotations:
          summary: "CPU 사용률이 높습니다"
          description: "{{ $labels.instance }}의 CPU 사용률이 80%를 초과했습니다."

      - alert: HighMemoryUsage
        expr: (node_memory_MemTotal_bytes - node_memory_MemAvailable_bytes) / node_memory_MemTotal_bytes * 100 > 85
        for: 5m
        labels:
          severity: warning
        annotations:
          summary: "메모리 사용률이 높습니다"
          description: "{{ $labels.instance }}의 메모리 사용률이 85%를 초과했습니다."

      - alert: DiskSpaceLow
        expr: (node_filesystem_avail_bytes{mountpoint="/"} / node_filesystem_size_bytes{mountpoint="/"}) * 100 < 15
        for: 5m
        labels:
          severity: critical
        annotations:
          summary: "디스크 공간 부족"
          description: "{{ $labels.instance }}의 루트 파티션 여유 공간이 15% 미만입니다."

  - name: application.rules
    rules:
      # 애플리케이션 알림
      - alert: HighResponseTime
        expr: nginx_http_request_duration_seconds{quantile="0.95"} > 2
        for: 2m
        labels:
          severity: warning
        annotations:
          summary: "응답 시간이 느립니다"
          description: "95% 응답 시간이 2초를 초과했습니다."

      - alert: HighErrorRate
        expr: rate(nginx_http_requests_total{status=~"5.."}[5m]) / rate(nginx_http_requests_total[5m]) * 100 > 5
        for: 2m
        labels:
          severity: critical
        annotations:
          summary: "에러율이 높습니다"
          description: "5xx 에러율이 5%를 초과했습니다."

      - alert: DatabaseConnectionHigh
        expr: mysql_global_status_threads_connected / mysql_global_variables_max_connections * 100 > 80
        for: 5m
        labels:
          severity: warning
        annotations:
          summary: "데이터베이스 연결 수가 높습니다"
          description: "MySQL 연결 수가 최대 연결 수의 80%를 초과했습니다."
EOF

    log_success "Prometheus 설정 완료"
}

# Grafana 설정
setup_grafana() {
    log_info "Grafana 설정 중..."
    
    # Grafana 데이터소스 설정
    mkdir -p monitoring/grafana/provisioning/datasources
    cat > monitoring/grafana/provisioning/datasources/prometheus.yml << 'EOF'
apiVersion: 1

datasources:
  - name: Prometheus
    type: prometheus
    url: http://prometheus:9090
    access: proxy
    isDefault: true
    editable: true
EOF

    # Grafana 대시보드 설정
    mkdir -p monitoring/grafana/provisioning/dashboards
    cat > monitoring/grafana/provisioning/dashboards/dashboard.yml << 'EOF'
apiVersion: 1

providers:
  - name: 'Ahhob Dashboards'
    orgId: 1
    folder: ''
    type: file
    disableDeletion: false
    editable: true
    options:
      path: /etc/grafana/provisioning/dashboards
EOF

    # 시스템 대시보드
    cat > monitoring/grafana/dashboards/system-overview.json << 'EOF'
{
  "dashboard": {
    "id": null,
    "title": "Ahhob System Overview",
    "tags": ["system"],
    "timezone": "Asia/Seoul",
    "panels": [
      {
        "id": 1,
        "title": "CPU Usage",
        "type": "stat",
        "targets": [
          {
            "expr": "100 - (avg by(instance) (irate(node_cpu_seconds_total{mode=\"idle\"}[5m])) * 100)",
            "refId": "A"
          }
        ],
        "fieldConfig": {
          "defaults": {
            "unit": "percent",
            "thresholds": {
              "steps": [
                {"color": "green", "value": null},
                {"color": "yellow", "value": 70},
                {"color": "red", "value": 90}
              ]
            }
          }
        },
        "gridPos": {"h": 8, "w": 6, "x": 0, "y": 0}
      },
      {
        "id": 2,
        "title": "Memory Usage",
        "type": "stat",
        "targets": [
          {
            "expr": "(node_memory_MemTotal_bytes - node_memory_MemAvailable_bytes) / node_memory_MemTotal_bytes * 100",
            "refId": "A"
          }
        ],
        "fieldConfig": {
          "defaults": {
            "unit": "percent",
            "thresholds": {
              "steps": [
                {"color": "green", "value": null},
                {"color": "yellow", "value": 80},
                {"color": "red", "value": 95}
              ]
            }
          }
        },
        "gridPos": {"h": 8, "w": 6, "x": 6, "y": 0}
      }
    ],
    "time": {"from": "now-1h", "to": "now"},
    "refresh": "30s"
  }
}
EOF

    log_success "Grafana 설정 완료"
}

# Docker Compose 모니터링 설정
setup_monitoring_compose() {
    log_info "모니터링 Docker Compose 설정 중..."
    
    cat > monitoring/docker-compose.monitoring.yml << 'EOF'
version: '3.8'

services:
  # Prometheus
  prometheus:
    image: prom/prometheus:latest
    container_name: ahhob-prometheus
    restart: unless-stopped
    ports:
      - "9090:9090"
    volumes:
      - ./prometheus/prometheus.yml:/etc/prometheus/prometheus.yml:ro
      - ./prometheus/rules:/etc/prometheus/rules:ro
      - prometheus-data:/prometheus
    command:
      - '--config.file=/etc/prometheus/prometheus.yml'
      - '--storage.tsdb.path=/prometheus'
      - '--web.console.libraries=/usr/share/prometheus/console_libraries'
      - '--web.console.templates=/usr/share/prometheus/consoles'
      - '--web.enable-lifecycle'
      - '--web.enable-admin-api'
    networks:
      - ahhob-monitoring

  # Grafana
  grafana:
    image: grafana/grafana:latest
    container_name: ahhob-grafana
    restart: unless-stopped
    ports:
      - "3000:3000"
    environment:
      - GF_SECURITY_ADMIN_PASSWORD=${GRAFANA_PASSWORD:-admin}
      - GF_USERS_ALLOW_SIGN_UP=false
      - GF_INSTALL_PLUGINS=grafana-clock-panel,grafana-simple-json-datasource
    volumes:
      - grafana-data:/var/lib/grafana
      - ./grafana/provisioning:/etc/grafana/provisioning:ro
      - ./grafana/dashboards:/etc/grafana/provisioning/dashboards:ro
    networks:
      - ahhob-monitoring

  # Node Exporter
  node-exporter:
    image: prom/node-exporter:latest
    container_name: ahhob-node-exporter
    restart: unless-stopped
    ports:
      - "9100:9100"
    volumes:
      - /proc:/host/proc:ro
      - /sys:/host/sys:ro
      - /:/rootfs:ro
    command:
      - '--path.procfs=/host/proc'
      - '--path.sysfs=/host/sys'
      - '--collector.filesystem.mount-points-exclude=^/(sys|proc|dev|host|etc)($$|/)'
    networks:
      - ahhob-monitoring

  # MySQL Exporter
  mysql-exporter:
    image: prom/mysqld-exporter:latest
    container_name: ahhob-mysql-exporter
    restart: unless-stopped
    ports:
      - "9104:9104"
    environment:
      - DATA_SOURCE_NAME=${DB_USERNAME}:${DB_PASSWORD}@(database:3306)/
    networks:
      - ahhob-monitoring

  # Redis Exporter
  redis-exporter:
    image: oliver006/redis_exporter:latest
    container_name: ahhob-redis-exporter
    restart: unless-stopped
    ports:
      - "9121:9121"
    environment:
      - REDIS_ADDR=redis:6379
      - REDIS_PASSWORD=${REDIS_PASSWORD}
    networks:
      - ahhob-monitoring

  # Nginx Exporter
  nginx-exporter:
    image: nginx/nginx-prometheus-exporter:latest
    container_name: ahhob-nginx-exporter
    restart: unless-stopped
    ports:
      - "9113:9113"
    command:
      - -nginx.scrape-uri=http://nginx:8080/nginx_status
    networks:
      - ahhob-monitoring

  # AlertManager
  alertmanager:
    image: prom/alertmanager:latest
    container_name: ahhob-alertmanager
    restart: unless-stopped
    ports:
      - "9093:9093"
    volumes:
      - ./alertmanager/alertmanager.yml:/etc/alertmanager/alertmanager.yml:ro
    networks:
      - ahhob-monitoring

volumes:
  prometheus-data:
  grafana-data:

networks:
  ahhob-monitoring:
    external: true
EOF

    # AlertManager 설정
    mkdir -p monitoring/alertmanager
    cat > monitoring/alertmanager/alertmanager.yml << 'EOF'
global:
  smtp_smarthost: 'localhost:587'
  smtp_from: 'alerts@ahhob.com'

route:
  group_by: ['alertname']
  group_wait: 10s
  group_interval: 10s
  repeat_interval: 1h
  receiver: 'web.hook'

receivers:
  - name: 'web.hook'
    email_configs:
      - to: 'admin@ahhob.com'
        subject: '[Ahhob] Alert: {{ .GroupLabels.alertname }}'
        body: |
          {{ range .Alerts }}
          Alert: {{ .Annotations.summary }}
          Description: {{ .Annotations.description }}
          Labels: {{ range .Labels.SortedPairs }}{{ .Name }}={{ .Value }} {{ end }}
          {{ end }}
    
    slack_configs:
      - api_url: '${SLACK_WEBHOOK_URL}'
        channel: '#alerts'
        title: 'Ahhob Alert'
        text: '{{ range .Alerts }}{{ .Annotations.summary }}{{ end }}'

inhibit_rules:
  - source_match:
      severity: 'critical'
    target_match:
      severity: 'warning'
    equal: ['alertname', 'dev', 'instance']
EOF

    log_success "모니터링 Docker Compose 설정 완료"
}

# 로그 관리 설정
setup_log_management() {
    log_info "로그 관리 시스템 설정 중..."
    
    # Logrotate 설정
    cat > monitoring/logrotate.conf << 'EOF'
/var/www/ahhob/storage/logs/*.log {
    daily
    rotate 30
    compress
    delaycompress
    missingok
    notifempty
    create 644 www-data www-data
    postrotate
        docker exec ahhob-app php artisan config:cache
    endscript
}

/var/log/nginx/*.log {
    daily
    rotate 30
    compress
    delaycompress
    missingok
    notifempty
    create 644 nginx nginx
    postrotate
        docker exec ahhob-nginx nginx -s reload
    endscript
}

/var/log/mysql/*.log {
    daily
    rotate 30
    compress
    delaycompress
    missingok
    notifempty
    create 644 mysql mysql
    postrotate
        docker exec ahhob-database mysqladmin flush-logs
    endscript
}
EOF

    log_success "로그 관리 설정 완료"
}

# 백업 스크립트 생성
setup_backup_scripts() {
    log_info "백업 스크립트 설정 중..."
    
    cat > monitoring/backup.sh << 'EOF'
#!/bin/bash

# Ahhob 자동 백업 스크립트

set -e

BACKUP_DIR="/var/backups/ahhob"
DATE=$(date +"%Y%m%d_%H%M%S")
RETENTION_DAYS=30

# 백업 디렉토리 생성
mkdir -p "$BACKUP_DIR"

# 데이터베이스 백업
echo "데이터베이스 백업 중..."
docker exec ahhob-database mysqldump -u root -p"$DB_ROOT_PASSWORD" \
    --single-transaction \
    --routines \
    --triggers \
    ahhob_production > "$BACKUP_DIR/database_$DATE.sql"

# 파일 백업
echo "파일 백업 중..."
tar -czf "$BACKUP_DIR/files_$DATE.tar.gz" \
    -C /var/www/ahhob \
    storage/app \
    public/uploads

# Redis 백업
echo "Redis 백업 중..."
docker exec ahhob-redis redis-cli --rdb "$BACKUP_DIR/redis_$DATE.rdb"

# 오래된 백업 정리
echo "오래된 백업 정리 중..."
find "$BACKUP_DIR" -name "*.sql" -mtime +$RETENTION_DAYS -delete
find "$BACKUP_DIR" -name "*.tar.gz" -mtime +$RETENTION_DAYS -delete
find "$BACKUP_DIR" -name "*.rdb" -mtime +$RETENTION_DAYS -delete

# S3 업로드 (선택적)
if [ -n "$AWS_S3_BACKUP_BUCKET" ]; then
    echo "S3에 백업 업로드 중..."
    aws s3 cp "$BACKUP_DIR/database_$DATE.sql" "s3://$AWS_S3_BACKUP_BUCKET/database/"
    aws s3 cp "$BACKUP_DIR/files_$DATE.tar.gz" "s3://$AWS_S3_BACKUP_BUCKET/files/"
    aws s3 cp "$BACKUP_DIR/redis_$DATE.rdb" "s3://$AWS_S3_BACKUP_BUCKET/redis/"
fi

echo "백업 완료: $DATE"
EOF

    chmod +x monitoring/backup.sh

    # Crontab 설정 예시
    cat > monitoring/crontab.example << 'EOF'
# Ahhob 백업 및 모니터링 Cron 작업

# 매일 새벽 2시에 백업 실행
0 2 * * * /var/www/ahhob/monitoring/backup.sh >> /var/log/ahhob-backup.log 2>&1

# 매주 일요일 새벽 3시에 시스템 최적화
0 3 * * 0 docker exec ahhob-app php artisan ahhob:optimize --all >> /var/log/ahhob-optimize.log 2>&1

# 매시간 헬스체크 실행
0 * * * * curl -f http://localhost/health || echo "Health check failed: $(date)" >> /var/log/ahhob-health.log

# 매일 로그 로테이션
0 1 * * * /usr/sbin/logrotate /var/www/ahhob/monitoring/logrotate.conf
EOF

    log_success "백업 스크립트 설정 완료"
}

# 헬스체크 엔드포인트 생성 안내
setup_health_check() {
    log_info "헬스체크 설정 안내..."
    
    cat > monitoring/health-check-info.md << 'EOF'
# 헬스체크 엔드포인트 설정

## Laravel 헬스체크 라우트 추가

`routes/web.php`에 다음 라우트를 추가하세요:

```php
Route::get('/health', function () {
    $checks = [];
    
    // 데이터베이스 연결 확인
    try {
        DB::connection()->getPdo();
        $checks['database'] = 'ok';
    } catch (Exception $e) {
        $checks['database'] = 'error';
    }
    
    // Redis 연결 확인
    try {
        Redis::ping();
        $checks['redis'] = 'ok';
    } catch (Exception $e) {
        $checks['redis'] = 'error';
    }
    
    // 디스크 공간 확인
    $diskFree = disk_free_space('/');
    $diskTotal = disk_total_space('/');
    $diskUsage = ($diskTotal - $diskFree) / $diskTotal * 100;
    $checks['disk'] = $diskUsage < 90 ? 'ok' : 'warning';
    
    $status = in_array('error', $checks) ? 500 : 200;
    
    return response()->json([
        'status' => $status === 200 ? 'ok' : 'error',
        'timestamp' => now()->toISOString(),
        'checks' => $checks
    ], $status);
});
```

## Nginx 헬스체크 설정

`docker/production/nginx/conf.d/ahhob.conf`에 다음을 추가하세요:

```nginx
# Nginx 상태 페이지 (모니터링용)
location /nginx_status {
    stub_status on;
    access_log off;
    allow 127.0.0.1;
    allow 172.16.0.0/12;  # Docker 네트워크
    deny all;
}
```

## 모니터링 메트릭 엔드포인트

Laravel에서 Prometheus 메트릭을 노출하려면 `prometheus-php` 패키지를 설치하고
`/metrics` 엔드포인트를 추가하세요.
EOF

    log_success "헬스체크 설정 안내 완료"
}

# 메인 실행 함수
main() {
    log_info "Ahhob 모니터링 시스템 설정을 시작합니다..."
    
    setup_directories
    setup_prometheus
    setup_grafana
    setup_monitoring_compose
    setup_log_management
    setup_backup_scripts
    setup_health_check
    
    log_success "모니터링 시스템 설정이 완료되었습니다!"
    echo ""
    echo "다음 단계:"
    echo "1. 환경 변수 설정: GRAFANA_PASSWORD, SLACK_WEBHOOK_URL 등"
    echo "2. 모니터링 시작: cd monitoring && docker-compose -f docker-compose.monitoring.yml up -d"
    echo "3. Grafana 접속: http://localhost:3000 (admin/admin)"
    echo "4. Prometheus 접속: http://localhost:9090"
    echo "5. 헬스체크 설정: monitoring/health-check-info.md 참조"
    echo "6. Cron 작업 설정: crontab -e로 monitoring/crontab.example 내용 추가"
}

# 스크립트 실행
main "$@"