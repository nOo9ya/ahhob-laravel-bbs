# Ahhob Laravel BBS - 커뮤니티 & 쇼핑몰 통합 솔루션

[![Laravel](https://img.shields.io/badge/Laravel-11.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3+-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Build Status](https://github.com/your-org/ahhob-laravel-bbs/workflows/CI/badge.svg)](https://github.com/your-org/ahhob-laravel-bbs/actions)

**Ahhob**는 Laravel 11 기반의 현대적인 커뮤니티 게시판과 쇼핑몰이 통합된 솔루션입니다. 동적 게시판 시스템, 완전한 전자상거래 기능, 통합 관리자 대시보드를 제공하여 중소규모부터 엔터프라이즈급까지 확장 가능한 플랫폼입니다.

## 🌟 주요 특징

### 🗣️ 커뮤니티 기능
- **동적 게시판 시스템**: 관리자가 실시간으로 게시판 생성/관리
- **계층형 댓글**: 무제한 대댓글 지원
- **사용자 레벨 시스템**: 포인트 기반 등급 관리
- **활동 제한 시스템**: 스팸 방지 및 품질 관리
- **파일 업로드**: 이미지 자동 최적화 및 썸네일 생성
- **좋아요/스크랩**: 사용자 상호작용 기능

### 🛒 쇼핑몰 기능
- **상품 관리**: 카테고리, 옵션, 재고 관리
- **주문 시스템**: 장바구니부터 결제까지 완전한 프로세스
- **결제 연동**: 아임포트(PortOne) 기반 다중 PG 지원
- **쿠폰 시스템**: 할인 쿠폰 및 프로모션
- **리뷰 시스템**: 상품 평점 및 후기 관리
- **위시리스트**: 관심상품 저장 기능

### 👨‍💼 관리자 기능
- **통합 대시보드**: 실시간 통계 및 분석
- **사용자 관리**: 회원 정보, 권한, 활동 관리
- **콘텐츠 관리**: 게시판, 게시글, 댓글 관리
- **주문 관리**: 주문 처리, 배송 관리, 환불 처리
- **시스템 모니터링**: 성능, 로그, 백업 관리

### ⚡ 성능 최적화
- **다층 캐시 시스템**: Redis + 파일 캐시
- **데이터베이스 최적화**: 인덱스 및 쿼리 최적화
- **이미지 최적화**: WebP 변환 및 반응형 이미지
- **실시간 모니터링**: 성능 메트릭 및 알림 시스템

## 🚀 빠른 시작

### 시스템 요구사항

- **PHP**: 8.3 이상
- **Composer**: 2.x
- **Node.js**: 18.x 이상
- **Docker & Docker Compose**: 최신 버전 (권장)

### 설치 방법

#### 1. Docker를 사용한 설치 (권장)

```bash
# 리포지토리 클론
git clone https://github.com/your-org/ahhob-laravel-bbs.git
cd ahhob-laravel-bbs

# 환경 파일 복사 및 설정
cp .env.example .env
# .env 파일에서 필요한 설정 수정

# Docker 컨테이너 시작
./vendor/bin/sail up -d

# 애플리케이션 키 생성
./vendor/bin/sail artisan key:generate

# 데이터베이스 마이그레이션
./vendor/bin/sail artisan migrate

# 시드 데이터 생성
./vendor/bin/sail artisan db:seed

# 프론트엔드 빌드
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

#### 2. 프로덕션 배포

```bash
# 프로덕션 환경 설정
cp .env.production .env
# 환경 변수 수정

# 프로덕션 배포 실행
./deploy.sh production
```

### 초기 설정

1. **관리자 계정 생성**:
   ```bash
   ./vendor/bin/sail artisan make:admin
   ```

2. **게시판 생성**:
   ```bash
   ./vendor/bin/sail artisan make:board
   ```

3. **성능 최적화**:
   ```bash
   ./vendor/bin/sail artisan ahhob:optimize --all
   ```

## 📚 문서

- [🔧 설치 가이드](docs/guides/installation.md)
- [⚙️ 설정 가이드](docs/guides/configuration.md)
- [👨‍💼 관리자 매뉴얼](docs/guides/admin-manual.md)
- [👤 사용자 매뉴얼](docs/guides/user-manual.md)
- [🚀 배포 가이드](docs/guides/deployment.md)
- [🔍 API 문서](docs/api/README.md)
- [🧪 테스트 가이드](docs/guides/testing.md)

## 🏗️ 아키텍처

### 시스템 구조

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   프론트엔드     │    │   백엔드 API     │    │   관리자 패널    │
│   (Blade/JS)    │◄──►│   (Laravel)     │◄──►│   (Dashboard)   │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │                       │
         ▼                       ▼                       ▼
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│      Nginx      │    │   MariaDB       │    │     Redis       │
│   (웹서버)       │    │  (데이터베이스)   │    │    (캐시)       │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

### 주요 기술 스택

- **백엔드**: Laravel 11, PHP 8.3+
- **프론트엔드**: Blade Templates, Tailwind CSS, Alpine.js
- **데이터베이스**: MariaDB 11
- **캐시**: Redis 7
- **웹서버**: Nginx 1.25
- **컨테이너**: Docker & Docker Compose
- **모니터링**: Prometheus, Grafana
- **테스트**: Pest PHP

## 🔧 주요 명령어

### 개발 도구

```bash
# 개발 서버 시작
./vendor/bin/sail up -d

# 테스트 실행
./vendor/bin/sail artisan test

# 코드 스타일 검사
./vendor/bin/sail composer pint

# 성능 최적화
./vendor/bin/sail artisan ahhob:optimize --all
```

### 운영 관리

```bash
# 프로덕션 배포
./deploy.sh production

# 백업 생성
./scripts/monitoring/backup.sh

# 모니터링 설정
./scripts/monitoring/setup-monitoring.sh

# 로그 확인
docker-compose logs -f app
```

## 🎯 핵심 기능 상세

### 동적 게시판 시스템

Ahhob의 핵심 기능인 동적 게시판은 관리자가 실시간으로 새로운 게시판을 생성할 수 있으며, 각 게시판마다 독립적인 테이블과 설정을 가집니다.

**특징**:
- 게시판별 독립적인 데이터베이스 테이블
- 유연한 권한 관리 (슈퍼 관리자, 게시판 관리자, 일반 사용자)
- 카테고리별 분류 및 정렬
- 실시간 통계 및 인기글 관리

### 통합 쇼핑몰

완전한 전자상거래 기능을 제공하는 쇼핑몰 시스템으로, 중소규모부터 대규모 온라인 쇼핑몰까지 운영 가능합니다.

**특징**:
- 계층형 상품 카테고리
- 상품 옵션 및 재고 관리
- 다양한 결제 수단 지원
- 주문 상태 추적 시스템
- 고객 리뷰 및 평점 시스템

### 성능 최적화

엔터프라이즈급 성능을 위한 다양한 최적화 기법을 적용했습니다.

**최적화 요소**:
- Redis 기반 다층 캐시 시스템
- 데이터베이스 인덱스 최적화
- 이미지 자동 압축 및 WebP 변환
- CDN 연동 지원
- 실시간 성능 모니터링

## 🛡️ 보안

### 인증 및 권한

- **이중 인증 시스템**: 사용자와 관리자 별도 인증
- **소셜 로그인**: Google, Naver, Kakao, Apple 지원
- **2FA**: 관리자 2단계 인증 지원
- **세션 보안**: Redis 기반 안전한 세션 관리

### 데이터 보호

- **입력 검증**: Laravel Validation 기반 엄격한 데이터 검증
- **XSS 방지**: 자동 HTML 이스케이프 처리
- **CSRF 보호**: Laravel CSRF 토큰 적용
- **SQL 인젝션 방지**: Eloquent ORM 및 Query Builder 사용

## 📊 모니터링 및 분석

### 실시간 모니터링

- **시스템 메트릭**: CPU, 메모리, 디스크 사용률
- **애플리케이션 성능**: 응답 시간, 처리량, 오류율
- **데이터베이스 성능**: 쿼리 성능, 연결 상태
- **사용자 활동**: 접속자 수, 페이지뷰, 전환율

### 알림 시스템

- **임계치 알림**: 성능 저하 시 자동 알림
- **장애 감지**: 서비스 중단 시 즉시 알림
- **보안 이벤트**: 의심스러운 활동 감지
- **백업 상태**: 백업 성공/실패 알림

## 🤝 기여하기

Ahhob 프로젝트에 기여해주시는 모든 분들을 환영합니다!

### 기여 방법

1. **이슈 리포트**: 버그나 개선사항을 이슈로 등록
2. **코드 기여**: Pull Request를 통한 코드 기여
3. **문서 개선**: 문서 오류 수정이나 내용 보완
4. **번역**: 다국어 지원을 위한 번역 작업

### 개발 가이드라인

- [코딩 스타일 가이드](docs/guides/coding-standards.md)
- [Git 커밋 컨벤션](docs/guides/commit-convention.md)
- [PR 가이드라인](docs/guides/pull-request.md)
- [테스트 작성 가이드](docs/guides/testing.md)

## 📄 라이선스

이 프로젝트는 [MIT License](LICENSE) 하에 배포됩니다.

## 📞 지원 및 문의

- **이메일**: support@ahhob.com
- **이슈 트래커**: [GitHub Issues](https://github.com/your-org/ahhob-laravel-bbs/issues)
- **토론**: [GitHub Discussions](https://github.com/your-org/ahhob-laravel-bbs/discussions)
- **위키**: [GitHub Wiki](https://github.com/your-org/ahhob-laravel-bbs/wiki)

## 🙏 감사의 말

이 프로젝트는 다음 오픈소스 프로젝트들의 도움으로 만들어졌습니다:

- [Laravel](https://laravel.com) - PHP 웹 애플리케이션 프레임워크
- [Tailwind CSS](https://tailwindcss.com) - 유틸리티 기반 CSS 프레임워크
- [Vite](https://vitejs.dev) - 빠른 프론트엔드 빌드 도구
- [Pest](https://pestphp.com) - 우아한 PHP 테스트 프레임워크
- [Redis](https://redis.io) - 인메모리 데이터 구조 저장소

---

**Ahhob**과 함께 더 나은 온라인 커뮤니티를 만들어보세요! 🚀