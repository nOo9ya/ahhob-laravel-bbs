/*M!999999\- enable the sandbox mode */ 
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `admins` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '관리자 고유 ID',
  `username` varchar(50) NOT NULL COMMENT '관리자 로그인 아이디',
  `password` varchar(255) NOT NULL COMMENT '해시된 비밀번호',
  `name` varchar(100) NOT NULL COMMENT '관리자 실명',
  `email` varchar(100) NOT NULL COMMENT '관리자 이메일 주소',
  `email_verified_at` timestamp NULL DEFAULT NULL COMMENT '이메일 인증 일시',
  `role` enum('super_admin','admin','manager') NOT NULL DEFAULT 'manager' COMMENT '관리자 역할 (super_admin, admin, manager)',
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '세부 권한 설정 (JSON)' CHECK (json_valid(`permissions`)),
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active' COMMENT '관리자 계정 상태',
  `last_login_at` timestamp NULL DEFAULT NULL COMMENT '최종 로그인 일시',
  `last_login_ip` varchar(45) DEFAULT NULL COMMENT '최종 로그인 IP',
  `memo` text DEFAULT NULL COMMENT '관리자 메모',
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admins_username_unique` (`username`),
  UNIQUE KEY `admins_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='CMS 관리자 정보';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `login_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_histories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '로그인 기록 고유 ID',
  `authenticatable_type` varchar(255) NOT NULL,
  `authenticatable_id` bigint(20) unsigned NOT NULL,
  `ip_address` varchar(45) NOT NULL COMMENT '로그인 IP 주소',
  `user_agent` text DEFAULT NULL COMMENT '사용자 에이전트 정보',
  `browser` varchar(100) DEFAULT NULL COMMENT '브라우저 정보',
  `os` varchar(100) DEFAULT NULL COMMENT '운영체제 정보',
  `device_type` varchar(50) DEFAULT NULL COMMENT '기기 타입 (desktop, mobile, tablet)',
  `location` text DEFAULT NULL COMMENT '로그인 위치 정보',
  `login_method` varchar(50) NOT NULL DEFAULT 'email' COMMENT '로그인 방법 (email, google, kakao, naver, apple)',
  `status` enum('success','failed') NOT NULL DEFAULT 'success' COMMENT '로그인 시도 결과',
  `failure_reason` text DEFAULT NULL COMMENT '로그인 실패 사유',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login_histories_authenticatable_type_authenticatable_id_index` (`authenticatable_type`,`authenticatable_id`),
  KEY `login_histories_ip_address_index` (`ip_address`),
  KEY `login_histories_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='로그인 기록';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_activity_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '활동 로그 고유 ID',
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `activity_type` varchar(50) NOT NULL COMMENT '활동 타입 (login, post_create, comment_create, etc.)',
  `related_type` varchar(255) NOT NULL,
  `related_id` bigint(20) unsigned NOT NULL,
  `activity_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '활동 상세 데이터 (JSON)' CHECK (json_valid(`activity_data`)),
  `ip_address` varchar(45) DEFAULT NULL COMMENT '활동 시 IP 주소',
  `user_agent` text DEFAULT NULL COMMENT '사용자 에이전트 정보',
  `referer_url` text DEFAULT NULL COMMENT '이전 방문 URL',
  `session_id` varchar(100) DEFAULT NULL COMMENT '세션 ID',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_activity_logs_related_type_related_id_index` (`related_type`,`related_id`),
  KEY `user_activity_logs_user_id_index` (`user_id`),
  KEY `user_activity_logs_activity_type_index` (`activity_type`),
  KEY `user_activity_logs_ip_address_index` (`ip_address`),
  KEY `user_activity_logs_created_at_index` (`created_at`),
  CONSTRAINT `user_activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='사용자 활동 추적 로그';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_social_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_social_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '소셜 계정 고유 ID',
  `user_id` bigint(20) unsigned NOT NULL,
  `provider` varchar(50) NOT NULL COMMENT '소셜 로그인 제공자 (google, kakao, naver, apple)',
  `provider_id` varchar(255) NOT NULL COMMENT '제공자별 사용자 고유 ID',
  `profile_url` varchar(255) DEFAULT NULL COMMENT '소셜 프로필 URL',
  `photo_url` varchar(255) DEFAULT NULL COMMENT '소셜 프로필 사진 URL',
  `display_name` varchar(150) DEFAULT NULL COMMENT '소셜 프로필 표시 이름',
  `description` text DEFAULT NULL COMMENT '소셜 프로필 설명',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_social_accounts_provider_provider_id_unique` (`provider`,`provider_id`),
  KEY `user_social_accounts_user_id_foreign` (`user_id`),
  CONSTRAINT `user_social_accounts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='사용자 소셜 계정 연동 정보';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL COMMENT '로그인 아이디',
  `nickname` varchar(100) NOT NULL COMMENT '닉네임',
  `real_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL COMMENT '휴대폰 번호',
  `postal_code` varchar(10) DEFAULT NULL COMMENT '우편번호',
  `address_line1` varchar(255) DEFAULT NULL COMMENT '기본 주소',
  `address_line2` varchar(255) DEFAULT NULL COMMENT '상세 주소',
  `profile_image_path` varchar(255) DEFAULT NULL COMMENT '프로필 이미지 파일 경로',
  `bio` text DEFAULT NULL COMMENT '자기소개',
  `points` int(11) NOT NULL DEFAULT 0 COMMENT '보유 포인트',
  `level` tinyint(3) unsigned NOT NULL DEFAULT 1 COMMENT '사용자 레벨',
  `status` enum('active','dormant','suspended','banned') NOT NULL DEFAULT 'active' COMMENT '계정 상태',
  `last_login_at` timestamp NULL DEFAULT NULL COMMENT '최종 로그인 일시',
  `last_login_ip` varchar(45) DEFAULT NULL COMMENT '최종 로그인 IP',
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '소프트 삭제 일시 (탈퇴일)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_username_unique` (`username`),
  UNIQUE KEY `users_nickname_unique` (`nickname`),
  UNIQUE KEY `users_phone_number_unique` (`phone_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='회원(사용자) 정보';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

/*M!999999\- enable the sandbox mode */ 
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2025_06_28_112534_modify_users_table_for_ahhob',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2025_06_28_112700_create_admins_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2025_06_28_112856_create_user_social_accounts_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2025_06_28_112922_create_login_histories_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2025_06_28_133630_create_user_activity_logs_table',5);
