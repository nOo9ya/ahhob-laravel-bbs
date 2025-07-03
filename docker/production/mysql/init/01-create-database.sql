-- 프로덕션 데이터베이스 초기화 스크립트

-- 데이터베이스 생성 (이미 존재할 수 있으므로 IF NOT EXISTS 사용)
CREATE DATABASE IF NOT EXISTS `ahhob_production` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- 사용자 생성 및 권한 부여
CREATE USER IF NOT EXISTS 'ahhob_user'@'%' IDENTIFIED BY 'CHANGE_THIS_STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON `ahhob_production`.* TO 'ahhob_user'@'%';

-- 백업용 읽기 전용 사용자 생성
CREATE USER IF NOT EXISTS 'ahhob_backup'@'%' IDENTIFIED BY 'CHANGE_THIS_BACKUP_PASSWORD';
GRANT SELECT, LOCK TABLES, RELOAD, REPLICATION CLIENT ON *.* TO 'ahhob_backup'@'%';

-- 모니터링용 사용자 생성
CREATE USER IF NOT EXISTS 'ahhob_monitor'@'%' IDENTIFIED BY 'CHANGE_THIS_MONITOR_PASSWORD';
GRANT PROCESS, REPLICATION CLIENT, SELECT ON *.* TO 'ahhob_monitor'@'%';
GRANT SELECT ON performance_schema.* TO 'ahhob_monitor'@'%';

-- 권한 적용
FLUSH PRIVILEGES;

-- 프로덕션 데이터베이스 선택
USE `ahhob_production`;

-- 풀텍스트 검색을 위한 추가 설정
SET GLOBAL innodb_ft_min_token_size = 2;
SET GLOBAL innodb_ft_enable_stopword = 0;

-- 한글 지원을 위한 추가 설정
SET GLOBAL innodb_large_prefix = 1;
SET GLOBAL innodb_file_format = Barracuda;
SET GLOBAL innodb_file_per_table = 1;