-- COOU SIWES Portal MySQL export
-- Source: SQLite database/database.sqlite
-- Generated: 2026-08-23T11:55:53+00:00

SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+00:00';
SET FOREIGN_KEY_CHECKS = 0;


DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `tickets`;
DROP TABLE IF EXISTS `supervisors`;
DROP TABLE IF EXISTS `supervisor_student_assignments`;
DROP TABLE IF EXISTS `students`;
DROP TABLE IF EXISTS `student_placements`;
DROP TABLE IF EXISTS `student_imports`;
DROP TABLE IF EXISTS `sessions`;
DROP TABLE IF EXISTS `roles`;
DROP TABLE IF EXISTS `role_has_permissions`;
DROP TABLE IF EXISTS `personal_access_tokens`;
DROP TABLE IF EXISTS `permissions`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `password_reset_tokens`;
DROP TABLE IF EXISTS `passkeys`;
DROP TABLE IF EXISTS `otp_challenges`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `notices`;
DROP TABLE IF EXISTS `model_has_roles`;
DROP TABLE IF EXISTS `model_has_permissions`;
DROP TABLE IF EXISTS `migrations`;
DROP TABLE IF EXISTS `jobs`;
DROP TABLE IF EXISTS `job_batches`;
DROP TABLE IF EXISTS `failed_jobs`;
DROP TABLE IF EXISTS `faculties`;
DROP TABLE IF EXISTS `departments`;
DROP TABLE IF EXISTS `courses`;
DROP TABLE IF EXISTS `cache_locks`;
DROP TABLE IF EXISTS `cache`;
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `assessments`;
DROP TABLE IF EXISTS `assessment_scores`;
DROP TABLE IF EXISTS `assessment_rubric_items`;
DROP TABLE IF EXISTS `app_settings`;
DROP TABLE IF EXISTS `academic_sessions`;
DROP TABLE IF EXISTS `academic_levels`;

CREATE TABLE `academic_levels` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `level` int NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `description` longtext NULL,
  `created_at` datetime NULL,
  `updated_at` datetime NULL,
  `deleted_at` datetime NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `academic_levels_level_unique` (`level`),
  UNIQUE KEY `academic_levels_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `academic_levels` (`id`, `name`, `level`, `is_active`, `description`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, '300L', 300, 1, NULL, '2026-08-09 08:31:15', '2026-08-22 22:28:10', NULL),
(2, '100 Level', 100, 0, NULL, '2026-08-09 08:31:15', '2026-08-22 22:40:55', NULL),
(3, '200L', 200, 1, NULL, '2026-08-09 08:31:15', '2026-08-22 22:28:10', NULL),
(4, '400L', 400, 1, NULL, '2026-08-09 08:31:16', '2026-08-22 22:28:10', NULL),
(5, '500L', 500, 1, NULL, '2026-08-09 08:31:16', '2026-08-22 22:28:10', NULL),
(6, '600L', 600, 1, NULL, '2026-08-11 14:51:10', '2026-08-22 22:28:50', NULL);

CREATE TABLE `academic_sessions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `starts_on` date NOT NULL,
  `ends_on` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `description` longtext NULL,
  `created_at` datetime NULL,
  `updated_at` datetime NULL,
  `deleted_at` datetime NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `academic_sessions_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `academic_sessions` (`id`, `name`, `starts_on`, `ends_on`, `is_active`, `description`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, '2026/2027', '2026-09-01 00:00:00', '2027-08-31 00:00:00', 1, NULL, '2026-08-09 08:31:15', '2026-08-21 19:18:00', NULL),
(2, '2025/2026', '2025-09-09 00:00:00', '2026-09-09 00:00:00', 0, NULL, '2026-08-11 14:54:47', '2026-08-22 22:40:56', NULL);

CREATE TABLE `app_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `group` varchar(255) NOT NULL,
  `key` varchar(255) NOT NULL,
  `value` longtext NULL,
  `type` varchar(255) NOT NULL DEFAULT 'string',
  `is_public` tinyint(1) NOT NULL DEFAULT '0',
  `description` longtext NULL,
  `created_at` datetime NULL,
  `updated_at` datetime NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `app_settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `app_settings` (`id`, `group`, `key`, `value`, `type`, `is_public`, `description`, `created_at`, `updated_at`) VALUES
(1, 'site', 'site.name', '"COOU SIWES Portal"', 'string', 1, NULL, '2026-08-09 08:31:16', '2026-08-09 08:31:16'),
(2, 'academic', 'academic.active_session', '"2026\/2027"', 'string', 0, NULL, '2026-08-09 08:31:16', '2026-08-09 08:31:16'),
(3, 'payment', 'payment.provider', '"korapay"', 'string', 0, 'Active payment provider. Use korapay.', '2026-08-09 08:31:16', '2026-08-10 12:49:13'),
(4, 'otp', 'otp.ttl_minutes', '10', 'integer', 0, NULL, '2026-08-09 08:31:16', '2026-08-09 08:31:16'),
(5, 'upload', 'upload.max_mb', '5', 'integer', 0, NULL, '2026-08-09 08:31:16', '2026-08-09 08:31:16'),
(6, 'theme', 'theme.default_mode', '"system"', 'string', 1, NULL, '2026-08-09 08:31:16', '2026-08-09 08:31:16'),
(7, 'notifications', 'notifications.push_enabled', 'true', 'boolean', 0, NULL, '2026-08-09 08:31:16', '2026-08-09 08:31:16'),
(8, 'site', 'site.welcome.enabled', 'true', 'boolean', 1, NULL, '2026-08-09 14:18:13', '2026-08-09 14:18:13'),
(9, 'site', 'site.welcome.title', '"Welcome to COOU SIWES"', 'string', 1, NULL, '2026-08-09 14:18:13', '2026-08-09 14:18:13'),
(10, 'site', 'site.welcome.message', '"Access your industrial training portal, follow official notices, and continue your SIWES workflow securely."', 'string', 1, NULL, '2026-08-09 14:18:13', '2026-08-09 14:18:13'),
(11, 'site', 'site.welcome.duration_seconds', '6', 'integer', 1, NULL, '2026-08-09 14:18:13', '2026-08-09 14:18:13'),
(12, 'payment', 'payment.currency', '"NGN"', 'string', 0, 'Currency code used for ticket payments.', '2026-08-10 12:49:13', '2026-08-10 12:49:13'),
(13, 'payment', 'payment.ticket_amount', '500', 'integer', 0, 'SIWES activation ticket fee.', '2026-08-10 12:49:13', '2026-08-10 12:49:13'),
(14, 'payment', 'payment.ticket_valid_days', '30', 'integer', 0, 'Number of days before an unused ticket expires.', '2026-08-10 12:49:13', '2026-08-10 12:49:13'),
(15, 'korapay', 'korapay.base_url', '"https:\/\/api.korapay.com\/merchant\/api\/v1"', 'string', 0, 'Korapay merchant API base URL.', '2026-08-10 12:49:13', '2026-08-10 12:49:13'),
(16, 'korapay', 'korapay.public_key', NULL, 'string', 0, 'Korapay public API key.', '2026-08-10 12:49:13', '2026-08-10 12:49:13'),
(17, 'korapay', 'korapay.secret_key', NULL, 'string', 0, 'Korapay private API key.', '2026-08-10 12:49:13', '2026-08-10 12:49:13'),
(18, 'korapay', 'korapay.webhook_secret', NULL, 'string', 0, 'Secret used to verify Korapay webhook signatures.', '2026-08-10 12:49:13', '2026-08-10 12:49:13'),
(19, 'korapay', 'korapay.redirect_url', NULL, 'string', 0, 'Callback URL after Korapay checkout.', '2026-08-10 12:49:13', '2026-08-10 12:49:13');

CREATE TABLE `assessment_rubric_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` longtext NULL,
  `max_score` int NOT NULL DEFAULT '10',
  `weight` int NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NULL,
  `updated_at` datetime NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `assessment_rubric_items_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `assessment_rubric_items` (`id`, `name`, `description`, `max_score`, `weight`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Punctuality', 'Attendance, timeliness, and reliability during industrial attachment.', 10, 1, 1, 1, '2026-08-09 08:31:16', '2026-08-09 08:31:16'),
(2, 'Technical Skill', 'Ability to apply relevant professional and technical skills.', 10, 2, 2, 1, '2026-08-09 08:31:16', '2026-08-09 08:31:16'),
(3, 'Communication', 'Clarity, documentation quality, and workplace communication.', 10, 1, 3, 1, '2026-08-09 08:31:16', '2026-08-09 08:31:16'),
(4, 'Professional Conduct', 'Ethics, teamwork, initiative, and adherence to workplace standards.', 10, 2, 4, 1, '2026-08-09 08:31:16', '2026-08-09 08:31:16');

CREATE TABLE `assessment_scores` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `assessment_id` bigint unsigned NOT NULL,
  `assessment_rubric_item_id` bigint unsigned NOT NULL,
  `score` int NOT NULL,
  `max_score` int NOT NULL,
  `comment` longtext NULL,
  `created_at` datetime NULL,
  `updated_at` datetime NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `assessment_scores_assessment_id_assessment_rubric_item_id_unique` (`assessment_id`, `assessment_rubric_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `assessments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `supervisor_id` bigint unsigned NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `supervisor_student_assignment_id` bigint unsigned NOT NULL,
  `total_score` int NOT NULL DEFAULT '0',
  `max_score` int NOT NULL DEFAULT '0',
  `status` varchar(255) NOT NULL DEFAULT 'submitted',
  `feedback` longtext NULL,
  `submitted_at` datetime NULL,
  `reviewed_at` datetime NULL,
  `reviewed_by` int NULL,
  `metadata` longtext NULL,
  `created_at` datetime NULL,
  `updated_at` datetime NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `assessments_supervisor_student_assignment_id_unique` (`supervisor_student_assignment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NULL,
  `event` varchar(255) NOT NULL,
  `auditable_type` varchar(255) NULL,
  `auditable_id` bigint unsigned NULL,
  `metadata` longtext NULL,
  `ip_address` varchar(255) NULL,
  `user_agent` longtext NULL,
  `created_at` datetime NULL,
  `updated_at` datetime NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `audit_logs` (`id`, `user_id`, `event`, `auditable_type`, `auditable_id`, `metadata`, `ip_address`, `user_agent`, `created_at`, `updated_at`) VALUES
(1, 1, 'auth.login_success', NULL, NULL, '{"portal":"admin"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-08-09 08:44:23', '2026-08-09 08:44:23'),
(2, 1, 'otp.challenge_created', NULL, NULL, '[]', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-08-09 08:44:24', '2026-08-09 08:44:24'),
(3, 1, 'otp.verify_success', NULL, NULL, '[]', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-08-09 08:45:21', '2026-08-09 08:45:21'),
(4, 1, 'auth.logout', NULL, NULL, '[]', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-08-09 09:43:29', '2026-08-09 09:43:29'),
(5, 1, 'auth.login_success', NULL, NULL, '{"portal":"admin"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-08-09 15:41:04', '2026-08-09 15:41:04'),
(6, 1, 'otp.challenge_created', NULL, NULL, '[]', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-08-09 15:41:07', '2026-08-09 15:41:07'),
(7, 1, 'auth.login_success', NULL, NULL, '{"portal":"admin"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-08-09 16:29:21', '2026-08-09 16:29:21'),
(8, 1, 'otp.challenge_created', NULL, NULL, '[]', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-08-09 16:29:22', '2026-08-09 16:29:22'),
(9, 1, 'auth.login_success', NULL, NULL, '{"portal":"admin"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-08-09 16:43:16', '2026-08-09 16:43:16'),
(10, 1, 'otp.challenge_created', NULL, NULL, '[]', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-08-09 16:43:18', '2026-08-09 16:43:18'),
(11, 1, 'otp.verify_success', NULL, NULL, '[]', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-08-09 16:43:47', '2026-08-09 16:43:47'),
(12, 1, 'auth.logout', NULL, NULL, '[]', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-08-09 16:44:07', '2026-08-09 16:44:07'),
(13, 1, 'auth.login_success', NULL, NULL, '{"portal":"admin"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-08-09 16:51:49', '2026-08-09 16:51:49'),
(14, 1, 'otp.challenge_created', NULL, NULL, '[]', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-08-09 16:51:51', '2026-08-09 16:51:51'),
(15, 1, 'otp.verify_success', NULL, NULL, '[]', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-08-09 16:52:13', '2026-08-09 16:52:13'),
(16, 1, 'auth.logout', NULL, NULL, '[]', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-08-09 16:52:29', '2026-08-09 16:52:29'),
(17, 1, 'auth.login_success', NULL, NULL, '{"portal":"admin"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-08-09 16:58:09', '2026-08-09 16:58:09'),
(18, 1, 'otp.challenge_created', NULL, NULL, '[]', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-08-09 16:58:11', '2026-08-09 16:58:11'),
(19, 1, 'otp.verify_success', NULL, NULL, '[]', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-08-09 16:58:40', '2026-08-09 16:58:40'),
(20, 1, 'auth.login_success', NULL, NULL, '{"portal":"admin"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-08-10 10:51:01', '2026-08-10 10:51:01'),
(21, 1, 'otp.challenge_created', NULL, NULL, '[]', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-08-10 10:51:03', '2026-08-10 10:51:03'),
(22, 1, 'otp.verify_success', NULL, NULL, '[]', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-08-10 10:51:26', '2026-08-10 10:51:26'),
(23, 1, 'settings.updated', 'App\Models\AppSetting', 3, '{"before":{"group":"payment","key":"payment.provider","value":"korapay","type":"string","is_public":false},"after":{"group":"payment","key":"payment.provider","value":"korapay","type":"string","is_public":false}}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-08-10 12:49:13', '2026-08-10 12:49:13'),
(24, 1, 'settings.created', 'App\Models\AppSetting', 12, '{"group":"payment","key":"payment.currency","value":"NGN","type":"string"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-08-10 12:49:13', '2026-08-10 12:49:13'),
(25, 1, 'settings.created', 'App\Models\AppSetting', 13, '{"group":"payment","key":"payment.ticket_amount","value":500,"type":"integer"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-08-10 12:49:13', '2026-08-10 12:49:13'),
(26, 1, 'settings.created', 'App\Models\AppSetting', 14, '{"group":"payment","key":"payment.ticket_valid_days","value":30,"type":"integer"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-08-10 12:49:13', '2026-08-10 12:49:13'),
(27, 1, 'settings.created', 'App\Models\AppSetting', 15, '{"group":"korapay","key":"korapay.base_url","value":"https:\/\/api.korapay.com\/merchant\/api\/v1","type":"string"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-08-10 12:49:13', '2026-08-10 12:49:13'),
(28, 1, 'settings.created', 'App\Models\AppSetting', 16, '{"group":"korapay","key":"korapay.public_key","value":null,"type":"string"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-08-10 12:49:13', '2026-08-10 12:49:13'),
(29, 1, 'settings.created', 'App\Models\AppSetting', 17, '{"group":"korapay","key":"korapay.secret_key","value":null,"type":"string"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-08-10 12:49:13', '2026-08-10 12:49:13'),
(30, 1, 'settings.created', 'App\Models\AppSetting', 18, '{"group":"korapay","key":"korapay.webhook_secret","value":null,"type":"string"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-08-10 12:49:13', '2026-08-10 12:49:13'),
(31, 1, 'settings.created', 'App\Models\AppSetting', 19, '{"group":"korapay","key":"korapay.redirect_url","value":null,"type":"string"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-08-10 12:49:13', '2026-08-10 12:49:13'),
(32, NULL, 'auth.login_failed', NULL, NULL, '{"identifier":"2026\/DEMO\/001","portal":"student"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-10 18:21:37', '2026-08-10 18:21:37'),
(33, NULL, 'auth.login_failed', NULL, NULL, '{"identifier":"2026\/DEMO\/001","portal":"student"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-10 18:22:00', '2026-08-10 18:22:00'),
(34, NULL, 'auth.login_failed', NULL, NULL, '{"identifier":"2026\/DEMO\/001","portal":"student"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-10 18:27:17', '2026-08-10 18:27:17'),
(35, NULL, 'auth.login_failed', NULL, NULL, '{"identifier":"2026\/DEMO\/001","portal":"student"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-10 18:27:38', '2026-08-10 18:27:38'),
(36, NULL, 'auth.login_failed', NULL, NULL, '{"identifier":"2026\/DEMO\/001","portal":"student"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-10 18:33:17', '2026-08-10 18:33:17'),
(37, 4, 'auth.login_success', NULL, NULL, '{"portal":"student"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-GB) WindowsPowerShell/5.1.26100.8875', '2026-08-10 18:34:58', '2026-08-10 18:34:58'),
(38, 4, 'otp.challenge_created', NULL, NULL, '[]', '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-GB) WindowsPowerShell/5.1.26100.8875', '2026-08-10 18:34:59', '2026-08-10 18:34:59'),
(39, 4, 'auth.login_success', NULL, NULL, '{"portal":"student"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-GB) WindowsPowerShell/5.1.26100.8875', '2026-08-10 18:36:47', '2026-08-10 18:36:47'),
(40, 4, 'otp.challenge_created', NULL, NULL, '[]', '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-GB) WindowsPowerShell/5.1.26100.8875', '2026-08-10 18:36:48', '2026-08-10 18:36:48'),
(41, 4, 'auth.login_success', NULL, NULL, '{"portal":"student"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-10 18:40:21', '2026-08-10 18:40:21'),
(42, 4, 'otp.challenge_created', NULL, NULL, '[]', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-10 18:40:23', '2026-08-10 18:40:23'),
(43, 4, 'auth.login_success', NULL, NULL, '{"portal":"student"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-GB) WindowsPowerShell/5.1.26100.8875', '2026-08-10 18:44:50', '2026-08-10 18:44:50'),
(44, 4, 'auth.logout', NULL, NULL, '[]', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-10 18:46:55', '2026-08-10 18:46:55'),
(45, 4, 'auth.login_success', NULL, NULL, '{"portal":"student"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-10 18:47:31', '2026-08-10 18:47:31'),
(46, 4, 'students.profile_step_updated', 'App\Models\Student', 1, '{"step":"basic","completion":54}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-10 21:42:25', '2026-08-10 21:42:25'),
(47, 4, 'students.profile_step_updated', 'App\Models\Student', 1, '{"step":"contact","completion":77}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-10 21:43:04', '2026-08-10 21:43:04'),
(48, 4, 'students.profile_step_updated', 'App\Models\Student', 1, '{"step":"academic","completion":77}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-10 21:43:17', '2026-08-10 21:43:17'),
(49, 4, 'students.profile_step_updated', 'App\Models\Student', 1, '{"step":"bank","completion":100}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-10 21:43:50', '2026-08-10 21:43:50'),
(50, 4, 'auth.login_success', NULL, NULL, '{"portal":"student"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-11 11:40:48', '2026-08-11 11:40:48'),
(51, 1, 'academics.level_created', 'App\Models\AcademicLevel', 6, '{"name":"600 Level","level":600}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-08-11 14:51:10', '2026-08-11 14:51:10'),
(52, 1, 'academics.session_created', 'App\Models\AcademicSession', 2, '{"name":"2025\/2026","starts_on":"2025-09-09T00:00:00.000000Z","ends_on":"2026-09-09T00:00:00.000000Z","is_active":true}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-08-11 14:54:47', '2026-08-11 14:54:47'),
(53, 4, 'placements.ticket_confirmed', 'App\Models\Ticket', 5, '{"student_id":1}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-11 15:00:47', '2026-08-11 15:00:47'),
(54, 4, 'placements.step_saved', 'App\Models\StudentPlacement', 1, '{"step":"siwes","completion":44}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-11 15:15:17', '2026-08-11 15:15:17'),
(55, 4, 'placements.step_saved', 'App\Models\StudentPlacement', 1, '{"step":"company","completion":100}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-11 15:17:16', '2026-08-11 15:17:16'),
(56, NULL, 'auth.login_failed', NULL, NULL, '{"identifier":"SIWES-896051760345","portal":"student"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-12 12:34:22', '2026-08-12 12:34:22'),
(57, NULL, 'auth.login_failed', NULL, NULL, '{"identifier":"SIWES-896051760345","portal":"student"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-12 12:34:33', '2026-08-12 12:34:33'),
(58, NULL, 'auth.login_failed', NULL, NULL, '{"identifier":"SIWES-896051760345","portal":"student"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-12 12:34:54', '2026-08-12 12:34:54'),
(59, 4, 'auth.login_success', NULL, NULL, '{"portal":"student"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-12 12:36:43', '2026-08-12 12:36:43'),
(60, 4, 'auth.logout', NULL, NULL, '[]', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-12 12:37:25', '2026-08-12 12:37:25'),
(61, 3, 'auth.login_success', NULL, NULL, '{"portal":"supervisor"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-12 12:38:42', '2026-08-12 12:38:42'),
(62, 1, 'supervisors.assigned', 'App\Models\SupervisorStudentAssignment', 1, '{"supervisor_id":1,"student_id":1}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-08-12 12:50:26', '2026-08-12 12:50:26'),
(63, 1, 'supervisors.assignment_revoked', 'App\Models\SupervisorStudentAssignment', 1, '{"supervisor_id":1,"student_id":1}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-08-12 13:16:44', '2026-08-12 13:16:44'),
(64, 1, 'supervisors.assigned', 'App\Models\SupervisorStudentAssignment', 2, '{"supervisor_id":1,"student_id":1}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-08-12 13:17:59', '2026-08-12 13:17:59'),
(65, 4, 'auth.login_success', NULL, NULL, '{"portal":"student"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-22 21:45:23', '2026-08-22 21:45:23');

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` longtext NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('laravel-cache-61590fa229bc022a53148b061379d02f:timer', 'i:1786359119;', 1786359119),
('laravel-cache-61590fa229bc022a53148b061379d02f', 'i:1;', 1786359119),
('laravel-cache-76908fc687a9270634311e73563d7f27:timer', 'i:1786359140;', 1786359140),
('laravel-cache-76908fc687a9270634311e73563d7f27', 'i:1;', 1786359140),
('laravel-cache-1b6453892473a467d07372d45eb05abc2031647a:timer', 'i:1786462039;', 1786462039),
('laravel-cache-1b6453892473a467d07372d45eb05abc2031647a', 'i:1;', 1786462039),
('laravel-cache-79f5af1078903977313af5c429dc9b07:timer', 'i:1786538119;', 1786538119),
('laravel-cache-79f5af1078903977313af5c429dc9b07', 'i:3;', 1786538119),
('laravel-cache-d7b17aa5f08408e68b9284cc8fcbb5e0:timer', 'i:1786538377;', 1786538377),
('laravel-cache-d7b17aa5f08408e68b9284cc8fcbb5e0', 'i:1;', 1786538377),
('laravel-cache-a4d5e03b069a4110f2374b4789dcdfce:timer', 'i:1786542012;', 1786542012),
('laravel-cache-a4d5e03b069a4110f2374b4789dcdfce', 'i:1;', 1786542012),
('laravel-cache-spatie.permission.cache', 'a:3:{s:5:"alias";a:4:{s:1:"a";s:2:"id";s:1:"b";s:4:"name";s:1:"c";s:10:"guard_name";s:1:"r";s:5:"roles";}s:11:"permissions";a:26:{i:0;a:4:{s:1:"a";i:1;s:1:"b";s:14:"dashboard.view";s:1:"c";s:3:"web";s:1:"r";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;}}i:1;a:4:{s:1:"a";i:2;s:1:"b";s:13:"students.view";s:1:"c";s:3:"web";s:1:"r";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:2;a:4:{s:1:"a";i:3;s:1:"b";s:15:"students.create";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:1;i:1;i:2;}}i:3;a:4:{s:1:"a";i:4;s:1:"b";s:15:"students.update";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:1;i:1;i:2;}}i:4;a:4:{s:1:"a";i:5;s:1:"b";s:16:"students.suspend";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:1;i:1;i:2;}}i:5;a:4:{s:1:"a";i:6;s:1:"b";s:15:"students.import";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:1;i:1;i:2;}}i:6;a:4:{s:1:"a";i:7;s:1:"b";s:15:"students.export";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:1;i:1;i:2;}}i:7;a:4:{s:1:"a";i:8;s:1:"b";s:12:"tickets.view";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:1;i:1;i:2;}}i:8;a:4:{s:1:"a";i:9;s:1:"b";s:16:"tickets.generate";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:1;i:1;i:2;}}i:9;a:4:{s:1:"a";i:10;s:1:"b";s:14:"tickets.revoke";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:10;a:4:{s:1:"a";i:11;s:1:"b";s:16:"supervisors.view";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:1;i:1;i:2;}}i:11;a:4:{s:1:"a";i:12;s:1:"b";s:18:"supervisors.create";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:1;i:1;i:2;}}i:12;a:4:{s:1:"a";i:13;s:1:"b";s:18:"supervisors.update";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:1;i:1;i:2;}}i:13;a:4:{s:1:"a";i:14;s:1:"b";s:19:"supervisors.suspend";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:14;a:4:{s:1:"a";i:15;s:1:"b";s:18:"supervisors.assign";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:1;i:1;i:2;}}i:15;a:4:{s:1:"a";i:16;s:1:"b";s:13:"feedback.view";s:1:"c";s:3:"web";s:1:"r";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;}}i:16;a:4:{s:1:"a";i:17;s:1:"b";s:15:"feedback.manage";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:1;i:1;i:3;}}i:17;a:4:{s:1:"a";i:18;s:1:"b";s:13:"payments.view";s:1:"c";s:3:"web";s:1:"r";a:3:{i:0;i:1;i:1;i:2;i:2;i:4;}}i:18;a:4:{s:1:"a";i:19;s:1:"b";s:15:"payments.export";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:1;i:1;i:2;}}i:19;a:4:{s:1:"a";i:20;s:1:"b";s:16:"academics.manage";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:1;i:1;i:2;}}i:20;a:4:{s:1:"a";i:21;s:1:"b";s:13:"settings.view";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:1;i:1;i:2;}}i:21;a:4:{s:1:"a";i:22;s:1:"b";s:15:"settings.update";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:22;a:4:{s:1:"a";i:23;s:1:"b";s:13:"admins.manage";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:23;a:4:{s:1:"a";i:24;s:1:"b";s:12:"roles.manage";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:24;a:4:{s:1:"a";i:25;s:1:"b";s:10:"audit.view";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:25;a:4:{s:1:"a";i:26;s:1:"b";s:20:"notifications.manage";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}}s:5:"roles";a:4:{i:0;a:3:{s:1:"a";i:1;s:1:"b";s:11:"super-admin";s:1:"c";s:3:"web";}i:1;a:3:{s:1:"a";i:2;s:1:"b";s:5:"admin";s:1:"c";s:3:"web";}i:2;a:3:{s:1:"a";i:3;s:1:"b";s:10:"supervisor";s:1:"c";s:3:"web";}i:3;a:3:{s:1:"a";i:4;s:1:"b";s:7:"student";s:1:"c";s:3:"web";}}}', 1787521480),
('laravel-cache-fcb215ce0db21dc1987e8284bf89b478:timer', 'i:1787435180;', 1787435180),
('laravel-cache-fcb215ce0db21dc1987e8284bf89b478', 'i:1;', 1787435180),
('laravel-cache-3911fdaa7da36e5262318bf0fff4fc04:timer', 'i:1787435649;', 1787435649),
('laravel-cache-3911fdaa7da36e5262318bf0fff4fc04', 'i:1;', 1787435649),
('laravel-cache-eceee6f7d7239c86d9cc01333840dce7:timer', 'i:1787436217;', 1787436217),
('laravel-cache-eceee6f7d7239c86d9cc01333840dce7', 'i:1;', 1787436217),
('laravel-cache-7939ad1a23e0b58a4aa7b3931c88b56d:timer', 'i:1787486156;', 1787486156),
('laravel-cache-7939ad1a23e0b58a4aa7b3931c88b56d', 'i:1;', 1787486156);

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `courses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `department_id` bigint unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `duration_years` int NOT NULL DEFAULT '4',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `description` longtext NULL,
  `created_at` datetime NULL,
  `updated_at` datetime NULL,
  `deleted_at` datetime NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `courses_department_id_name_unique` (`department_id`, `name`),
  UNIQUE KEY `courses_department_id_code_unique` (`department_id`, `code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `courses` (`id`, `department_id`, `name`, `code`, `duration_years`, `is_active`, `description`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 'Agricultural Economics', 'BSC-AGE', 4, 1, NULL, '2026-08-09 08:31:15', '2026-08-09 08:31:15', NULL);

CREATE TABLE `departments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `faculty_id` bigint unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `description` longtext NULL,
  `created_at` datetime NULL,
  `updated_at` datetime NULL,
  `deleted_at` datetime NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `departments_faculty_id_name_unique` (`faculty_id`, `name`),
  UNIQUE KEY `departments_faculty_id_code_unique` (`faculty_id`, `code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `departments` (`id`, `faculty_id`, `name`, `code`, `is_active`, `description`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 'Agric Economics & Extension', 'AGE', 1, NULL, '2026-08-09 08:31:15', '2026-08-21 19:17:59', NULL),
(2, 1, 'Animal Science', 'ANS', 1, NULL, '2026-08-21 19:17:59', '2026-08-21 19:17:59', NULL),
(3, 1, 'Crop Science', 'CRS', 1, NULL, '2026-08-21 19:17:59', '2026-08-21 19:17:59', NULL),
(4, 1, 'Fishery', 'FSH', 1, NULL, '2026-08-21 19:17:59', '2026-08-21 19:17:59', NULL),
(5, 1, 'Food Science and Tech', 'FST', 1, NULL, '2026-08-21 19:17:59', '2026-08-21 19:17:59', NULL),
(6, 1, 'Soil Science', 'SOS', 1, NULL, '2026-08-21 19:17:59', '2026-08-21 19:17:59', NULL),
(7, 2, 'Music', 'MUS', 1, NULL, '2026-08-21 19:17:59', '2026-08-21 19:17:59', NULL),
(8, 2, 'Theater Art', 'THA', 1, NULL, '2026-08-21 19:17:59', '2026-08-21 19:17:59', NULL),
(9, 3, 'Anatomy', 'ANA', 1, NULL, '2026-08-21 19:17:59', '2026-08-21 19:17:59', NULL),
(10, 3, 'Physiology', 'PHY', 1, NULL, '2026-08-21 19:17:59', '2026-08-21 19:17:59', NULL),
(11, 4, 'Vocational Education', 'VED', 1, NULL, '2026-08-21 19:17:59', '2026-08-21 19:17:59', NULL),
(12, 4, 'Science Education', 'SED', 1, NULL, '2026-08-21 19:17:59', '2026-08-21 19:17:59', NULL),
(13, 5, 'Civil Engineering', 'CVE', 1, NULL, '2026-08-21 19:17:59', '2026-08-21 19:17:59', NULL),
(14, 5, 'Chemical Engineering', 'CHE', 1, NULL, '2026-08-21 19:17:59', '2026-08-21 19:17:59', NULL),
(15, 5, 'Electrical/Electronic Engineering', 'EEE', 1, NULL, '2026-08-21 19:17:59', '2026-08-21 19:17:59', NULL),
(16, 5, 'Mechanical Engineering', 'MEE', 1, NULL, '2026-08-21 19:17:59', '2026-08-21 19:17:59', NULL),
(17, 6, 'Architecture', 'ARC', 1, NULL, '2026-08-21 19:17:59', '2026-08-21 19:17:59', NULL),
(18, 6, 'Environmental Management', 'EMT', 1, NULL, '2026-08-21 19:17:59', '2026-08-21 19:17:59', NULL),
(19, 6, 'Estate Management', 'ESM', 1, NULL, '2026-08-21 19:17:59', '2026-08-21 19:17:59', NULL),
(20, 6, 'Urban and Regional Planning', 'URP', 1, NULL, '2026-08-21 19:17:59', '2026-08-21 19:17:59', NULL),
(21, 7, 'Accountancy', 'ACC', 1, NULL, '2026-08-21 19:18:00', '2026-08-21 19:18:00', NULL),
(22, 8, 'Biochemistry', 'BCH', 1, NULL, '2026-08-21 19:18:00', '2026-08-21 19:18:00', NULL),
(23, 8, 'Biological Science', 'BIS', 1, NULL, '2026-08-21 19:18:00', '2026-08-21 19:18:00', NULL),
(24, 8, 'Microbiology', 'MCB', 1, NULL, '2026-08-21 19:18:00', '2026-08-21 19:18:00', NULL),
(25, 9, 'Clinical Pharmacy and Pharmacy Management', 'CPM', 1, NULL, '2026-08-21 19:18:00', '2026-08-21 19:18:00', NULL),
(26, 9, 'Pharmaceutics and Pharmaceutical Technology', 'PPT', 1, NULL, '2026-08-21 19:18:00', '2026-08-21 19:18:00', NULL),
(27, 9, 'Pharmaceutical Microbiology and Biotechnology', 'PMB', 1, NULL, '2026-08-21 19:18:00', '2026-08-21 19:18:00', NULL),
(28, 9, 'Pharmacognosy and Traditional Medicines', 'PGT', 1, NULL, '2026-08-21 19:18:00', '2026-08-21 19:18:00', NULL),
(29, 9, 'Pharmacology and Toxicology', 'PTO', 1, NULL, '2026-08-21 19:18:00', '2026-08-21 19:18:00', NULL),
(30, 10, 'Computer Science', 'CSC', 1, NULL, '2026-08-21 19:18:00', '2026-08-21 19:18:00', NULL),
(31, 10, 'Geology', 'GEO', 1, NULL, '2026-08-21 19:18:00', '2026-08-21 19:18:00', NULL),
(32, 10, 'Industrial Physics', 'IPH', 1, NULL, '2026-08-21 19:18:00', '2026-08-21 19:18:00', NULL),
(33, 10, 'Mathematics', 'MTH', 1, NULL, '2026-08-21 19:18:00', '2026-08-21 19:18:00', NULL),
(34, 10, 'Pure and Industrial Chemistry', 'PIC', 1, NULL, '2026-08-21 19:18:00', '2026-08-21 19:18:00', NULL),
(35, 10, 'Statistics', 'STA', 1, NULL, '2026-08-21 19:18:00', '2026-08-21 19:18:00', NULL),
(36, 11, 'Library Science and Information Tech.', 'LIS', 1, NULL, '2026-08-21 19:18:00', '2026-08-21 19:18:00', NULL);

CREATE TABLE `faculties` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `description` longtext NULL,
  `created_at` datetime NULL,
  `updated_at` datetime NULL,
  `deleted_at` datetime NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `faculties_name_unique` (`name`),
  UNIQUE KEY `faculties_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `faculties` (`id`, `name`, `code`, `is_active`, `description`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Faculty of Agricultural Science', 'AGRIC', 1, NULL, '2026-08-09 08:31:15', '2026-08-21 19:17:59', NULL),
(2, 'Faculty of Art', 'ART', 1, NULL, '2026-08-21 19:17:59', '2026-08-21 19:17:59', NULL),
(3, 'Faculty of Basic Medical Science', 'BMS', 1, NULL, '2026-08-21 19:17:59', '2026-08-21 19:17:59', NULL),
(4, 'Faculty of Education', 'EDU', 1, NULL, '2026-08-21 19:17:59', '2026-08-21 19:17:59', NULL),
(5, 'Faculty of Engineering', 'ENG', 1, NULL, '2026-08-21 19:17:59', '2026-08-21 19:17:59', NULL),
(6, 'Faculty of Environmental Sciences', 'ENV', 1, NULL, '2026-08-21 19:17:59', '2026-08-21 19:17:59', NULL),
(7, 'Faculty of Management Sciences', 'MGT', 1, NULL, '2026-08-21 19:18:00', '2026-08-21 19:18:00', NULL),
(8, 'Faculty of Natural Science', 'NAT', 1, NULL, '2026-08-21 19:18:00', '2026-08-21 19:18:00', NULL),
(9, 'Faculty of Pharmaceutical Sciences', 'PHR', 1, NULL, '2026-08-21 19:18:00', '2026-08-21 19:18:00', NULL),
(10, 'Faculty of Physical Sciences', 'PHYSCI', 1, NULL, '2026-08-21 19:18:00', '2026-08-21 19:18:00', NULL),
(11, 'Faculty of Social Sciences', 'SOC', 1, NULL, '2026-08-21 19:18:00', '2026-08-21 19:18:00', NULL);

CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` varchar(255) NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` longtext NULL,
  `cancelled_at` int NULL,
  `created_at` int NOT NULL,
  `finished_at` int NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` int NOT NULL,
  `reserved_at` int NULL,
  `available_at` int NOT NULL,
  `created_at` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `migrations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_07_31_122443_create_permission_tables', 1),
(5, '2026_07_31_122446_add_two_factor_columns_to_users_table', 1),
(6, '2026_07_31_122446_create_personal_access_tokens_table', 1),
(7, '2026_07_31_122447_create_passkeys_table', 1),
(8, '2026_07_31_140430_create_otp_challenges_table', 1),
(9, '2026_07_31_140436_create_audit_logs_table', 1),
(10, '2026_07_31_142611_create_faculties_table', 1),
(11, '2026_07_31_142616_create_departments_table', 1),
(12, '2026_07_31_142621_create_courses_table', 1),
(13, '2026_07_31_142626_create_academic_levels_table', 1),
(14, '2026_07_31_142631_create_academic_sessions_table', 1),
(15, '2026_07_31_142637_create_app_settings_table', 1),
(16, '2026_07_31_150036_create_students_table', 1),
(17, '2026_07_31_150042_create_student_imports_table', 1),
(18, '2026_07_31_160301_create_tickets_table', 1),
(19, '2026_07_31_160306_create_payments_table', 1),
(20, '2026_07_31_165126_create_supervisors_table', 1),
(21, '2026_07_31_165131_create_supervisor_student_assignments_table', 1),
(22, '2026_07_31_171906_create_notifications_table', 1),
(23, '2026_08_03_141629_create_assessment_rubric_items_table', 1),
(24, '2026_08_03_141632_create_assessments_table', 1),
(25, '2026_08_03_141634_create_assessment_scores_table', 1),
(26, '2026_08_09_120000_create_notices_table', 2),
(27, '2026_08_10_120000_allow_unassigned_tickets', 3),
(28, '2026_08_10_130000_add_serial_and_pin_to_tickets', 4),
(29, '2026_08_10_140000_rename_generated_tickets_to_unused', 5),
(30, '2026_08_10_150000_collapse_ticket_statuses_to_used_unused', 6),
(31, '2026_08_10_160000_set_student_passwords_to_matric_numbers', 7),
(32, '2026_08_10_170000_disable_otp_for_all_users', 8),
(33, '2026_08_11_120000_create_student_placements_table', 9),
(34, '2026_08_11_130000_unique_ticket_per_student_placement', 10),
(35, '2026_08_23_120000_drop_student_no_from_students_table', 11);

CREATE TABLE `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`, `model_id`, `model_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`, `model_id`, `model_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
(1, 'App\Models\User', 1),
(2, 'App\Models\User', 2),
(3, 'App\Models\User', 3),
(4, 'App\Models\User', 4);

CREATE TABLE `notices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_by` int NULL,
  `title` varchar(255) NOT NULL,
  `body` longtext NOT NULL,
  `audience` varchar(255) NOT NULL DEFAULT 'all',
  `tone` varchar(255) NOT NULL DEFAULT 'info',
  `published_at` datetime NULL,
  `expires_at` datetime NULL,
  `is_pinned` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NULL,
  `updated_at` datetime NULL,
  `deleted_at` datetime NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `notices` (`id`, `created_by`, `title`, `body`, `audience`, `tone`, `published_at`, `expires_at`, `is_pinned`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, NULL, 'SIWES student portal is open', 'Students can sign in to complete profile details, review activation status, and continue SIWES registration requirements.', 'students', 'success', '2026-08-21 22:40:56', NULL, 1, '2026-08-09 14:18:14', '2026-08-22 22:40:56', NULL),
(2, NULL, 'Supervisor assessment workspace', 'Supervisors should monitor assigned students and submit assessments through the supervisor portal when field reports are due.', 'supervisors', 'info', '2026-08-22 14:40:56', NULL, 0, '2026-08-09 14:18:14', '2026-08-22 22:40:56', NULL);

CREATE TABLE `notifications` (
  `id` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `notifiable_type` varchar(255) NOT NULL,
  `notifiable_id` bigint unsigned NOT NULL,
  `data` longtext NOT NULL,
  `read_at` datetime NULL,
  `created_at` datetime NULL,
  `updated_at` datetime NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `otp_challenges` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `code_hash` varchar(255) NOT NULL,
  `purpose` varchar(255) NOT NULL DEFAULT 'login',
  `delivery_channel` varchar(255) NOT NULL DEFAULT 'email',
  `ip_address` varchar(255) NULL,
  `user_agent` longtext NULL,
  `attempts` int NOT NULL DEFAULT '0',
  `expires_at` datetime NOT NULL,
  `verified_at` datetime NULL,
  `created_at` datetime NULL,
  `updated_at` datetime NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `otp_challenges` (`id`, `user_id`, `code_hash`, `purpose`, `delivery_channel`, `ip_address`, `user_agent`, `attempts`, `expires_at`, `verified_at`, `created_at`, `updated_at`) VALUES
(1, 1, '$2y$12$Mn/6yRIH.iGCnc1CZUXEYOSlgnNZC49oZ6ILf.kPEbHjiS7FhmWcC', 'login', 'email', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', 1, '2026-08-09 08:54:24', '2026-08-09 08:45:21', '2026-08-09 08:44:24', '2026-08-09 08:45:21'),
(2, 1, '$2y$12$HGDENiZSrl1sXGbIoFZVTeME4pgug466mlWW12I7PuWqiR7W2Zh4.', 'login', 'email', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', 0, '2026-08-09 15:51:07', NULL, '2026-08-09 15:41:07', '2026-08-09 15:41:07'),
(3, 1, '$2y$12$eSILZIn.LG2pTJ4S6zy8GO9LfXcAb09CMu4IwAkIBWXO.TWCh0peG', 'login', 'email', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', 0, '2026-08-09 16:39:22', NULL, '2026-08-09 16:29:22', '2026-08-09 16:29:22'),
(4, 1, '$2y$12$H5YIrOEVeOl2Bvliq9EYBO0nWg0Bj/A/pzeTcdXpZu9av38RLTPy.', 'login', 'email', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', 1, '2026-08-09 16:53:18', '2026-08-09 16:43:47', '2026-08-09 16:43:18', '2026-08-09 16:43:47'),
(5, 1, '$2y$12$IxoZkDLB5exaiBDjWTkFkOa1LUDWkAA7C5DmCqw62qWcxj5pe1do6', 'login', 'email', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', 1, '2026-08-09 17:01:51', '2026-08-09 16:52:13', '2026-08-09 16:51:51', '2026-08-09 16:52:13'),
(6, 1, '$2y$12$kgoPtCGQ3.kHUPrdJhtSg.svrFJl3.xE9kkJ6qsv6VDLl2Ui8Lb8S', 'login', 'email', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', 1, '2026-08-09 17:08:11', '2026-08-09 16:58:40', '2026-08-09 16:58:11', '2026-08-09 16:58:40'),
(7, 1, '$2y$12$FgOTHdzaISwfpsNyvstV8.SJ6to8cmKfvWl/J5KLuBHpnmP1g8bmW', 'login', 'email', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', 1, '2026-08-10 11:01:03', '2026-08-10 10:51:26', '2026-08-10 10:51:03', '2026-08-10 10:51:26'),
(8, 4, '$2y$12$zqw0fM6tM.tixg1g7.mjneybrXsv8BPOt542P/eHDOgQrX2rUMjp.', 'login', 'email', '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-GB) WindowsPowerShell/5.1.26100.8875', 0, '2026-08-10 18:44:59', NULL, '2026-08-10 18:34:59', '2026-08-10 18:34:59'),
(9, 4, '$2y$12$LNZzI5oXvZDY3ZjDGV3fYOYLOXnAdGrRsDsp0MWFURsw.xQN8s2mG', 'login', 'email', '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-GB) WindowsPowerShell/5.1.26100.8875', 0, '2026-08-10 18:46:48', NULL, '2026-08-10 18:36:48', '2026-08-10 18:36:48'),
(10, 4, '$2y$12$G4nAxzKdmK6MjslCwKkl/ezDDu76.3DyADTK53WQgBGF6JAV6hZdq', 'login', 'email', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 0, '2026-08-10 18:50:23', NULL, '2026-08-10 18:40:23', '2026-08-10 18:40:23');

CREATE TABLE `passkeys` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `credential_id` varchar(255) NOT NULL,
  `credential` longtext NOT NULL,
  `last_used_at` datetime NULL,
  `created_at` datetime NULL,
  `updated_at` datetime NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `passkeys_credential_id_unique` (`credential_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NULL,
  `token` varchar(255) NOT NULL,
  `created_at` datetime NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `ticket_id` bigint unsigned NULL,
  `provider` varchar(255) NOT NULL DEFAULT 'korapay',
  `reference` varchar(255) NOT NULL,
  `amount` int NOT NULL,
  `currency` varchar(255) NOT NULL DEFAULT 'NGN',
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `checkout_url` varchar(255) NULL,
  `provider_status` varchar(255) NULL,
  `webhook_event` varchar(255) NULL,
  `webhook_event_id` varchar(255) NULL,
  `payload` longtext NULL,
  `verified_at` datetime NULL,
  `paid_at` datetime NULL,
  `created_at` datetime NULL,
  `updated_at` datetime NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payments_webhook_event_id_unique` (`webhook_event_id`),
  UNIQUE KEY `payments_reference_unique` (`reference`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` datetime NULL,
  `updated_at` datetime NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`, `guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'dashboard.view', 'web', '2026-08-09 08:31:09', '2026-08-09 08:31:09'),
(2, 'students.view', 'web', '2026-08-09 08:31:09', '2026-08-09 08:31:09'),
(3, 'students.create', 'web', '2026-08-09 08:31:09', '2026-08-09 08:31:09'),
(4, 'students.update', 'web', '2026-08-09 08:31:09', '2026-08-09 08:31:09'),
(5, 'students.suspend', 'web', '2026-08-09 08:31:10', '2026-08-09 08:31:10'),
(6, 'students.import', 'web', '2026-08-09 08:31:10', '2026-08-09 08:31:10'),
(7, 'students.export', 'web', '2026-08-09 08:31:10', '2026-08-09 08:31:10'),
(8, 'tickets.view', 'web', '2026-08-09 08:31:10', '2026-08-09 08:31:10'),
(9, 'tickets.generate', 'web', '2026-08-09 08:31:10', '2026-08-09 08:31:10'),
(10, 'tickets.revoke', 'web', '2026-08-09 08:31:10', '2026-08-09 08:31:10'),
(11, 'supervisors.view', 'web', '2026-08-09 08:31:10', '2026-08-09 08:31:10'),
(12, 'supervisors.create', 'web', '2026-08-09 08:31:10', '2026-08-09 08:31:10'),
(13, 'supervisors.update', 'web', '2026-08-09 08:31:10', '2026-08-09 08:31:10'),
(14, 'supervisors.suspend', 'web', '2026-08-09 08:31:10', '2026-08-09 08:31:10'),
(15, 'supervisors.assign', 'web', '2026-08-09 08:31:10', '2026-08-09 08:31:10'),
(16, 'feedback.view', 'web', '2026-08-09 08:31:10', '2026-08-09 08:31:10'),
(17, 'feedback.manage', 'web', '2026-08-09 08:31:10', '2026-08-09 08:31:10'),
(18, 'payments.view', 'web', '2026-08-09 08:31:10', '2026-08-09 08:31:10'),
(19, 'payments.export', 'web', '2026-08-09 08:31:10', '2026-08-09 08:31:10'),
(20, 'academics.manage', 'web', '2026-08-09 08:31:10', '2026-08-09 08:31:10'),
(21, 'settings.view', 'web', '2026-08-09 08:31:10', '2026-08-09 08:31:10'),
(22, 'settings.update', 'web', '2026-08-09 08:31:10', '2026-08-09 08:31:10'),
(23, 'admins.manage', 'web', '2026-08-09 08:31:10', '2026-08-09 08:31:10'),
(24, 'roles.manage', 'web', '2026-08-09 08:31:10', '2026-08-09 08:31:10'),
(25, 'audit.view', 'web', '2026-08-09 08:31:10', '2026-08-09 08:31:10'),
(26, 'notifications.manage', 'web', '2026-08-09 08:31:10', '2026-08-09 08:31:10');

CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` longtext NOT NULL,
  `token` varchar(255) NOT NULL,
  `abilities` longtext NULL,
  `last_used_at` datetime NULL,
  `expires_at` datetime NULL,
  `created_at` datetime NULL,
  `updated_at` datetime NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`, `role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES
(20, 1),
(23, 1),
(25, 1),
(1, 1),
(17, 1),
(16, 1),
(26, 1),
(19, 1),
(18, 1),
(24, 1),
(22, 1),
(21, 1),
(3, 1),
(7, 1),
(6, 1),
(5, 1),
(4, 1),
(2, 1),
(15, 1),
(12, 1),
(14, 1),
(13, 1),
(11, 1),
(9, 1),
(10, 1),
(8, 1),
(20, 2),
(1, 2),
(16, 2),
(19, 2),
(18, 2),
(21, 2),
(3, 2),
(7, 2),
(6, 2),
(5, 2),
(4, 2),
(2, 2),
(15, 2),
(12, 2),
(13, 2),
(11, 2),
(9, 2),
(8, 2),
(1, 3),
(17, 3),
(16, 3),
(2, 3),
(1, 4),
(16, 4),
(18, 4);

CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` datetime NULL,
  `updated_at` datetime NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`, `guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'super-admin', 'web', '2026-08-09 08:31:10', '2026-08-09 08:31:10'),
(2, 'admin', 'web', '2026-08-09 08:31:10', '2026-08-09 08:31:10'),
(3, 'supervisor', 'web', '2026-08-09 08:31:11', '2026-08-09 08:31:11'),
(4, 'student', 'web', '2026-08-09 08:31:11', '2026-08-09 08:31:11');

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint unsigned NULL,
  `ip_address` varchar(255) NULL,
  `user_agent` longtext NULL,
  `payload` longtext NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('SRgfD5uLJ3GXJYBjfNmfEu2DcCFjJtPqLohHExJI', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0', 'ZXlKcGRpSTZJbGxqTDIxdWNEQTBLMlkwVUN0YWMwSXJSalJ4ZEhjOVBTSXNJblpoYkhWbElqb2lhbkp0WVM5NVJVdFJOMXBaWlhOdmVtWndNSFJQWm5SNVVVWmpNRzFTTUdKT0wyRkdTVFpXV21oNWJYTk9ZWGRYTlV0VGFVdERUbXhvU21kdGVWbHJRUzk0ZVM5WFF6QXhURzFGZEdrMVoyZHFVRGhrYlU0MFJEaDRhRWtyYjNGTE1tVnhaMGc0Y2lzMlVHTTFTVlZHTmtFdlJtZzJPRVUyT0ZaR2RTOUxObUpwWTNaNWFXSTBVVEowY1ZCSVVHdEZNRlZGSzBSU1duQjNhamxTUkZoMkx6ZHFhVVJXTjB4Q2VFaEdSSEE0VlZObWJtaEVia1JOWjB0VmNUSXZibmdyY3k4M1lsbzFWbGt5YVdGV1JXRTFWM2RTWVN0eFRVOU1NekF6YmpKdU4weG9Zbmh3Tm05WE5FaHVkV2QzUVVWaVRHZFlibWhyVFRWcVpXaE5NMjlrZUZodVUzRnRiMDV3V1hOeGJIRTFWV05ZYzJKbGRIcHRjVEZKUlhKbkwzQllaVEJ5UW5GT1ZYUlFLMU0yYzFwMGJrNURhVE5WVFcxcmNEaEROVTA0UWtnaUxDSnRZV01pT2lJME1qYzRaakZqT0RKaFpqZ3lPVEZqTVRVMU1tRTBZVGxrWlRSbE9UTTVaV1poT0RSa00yRmxaREV5WWpjMlpqVXdZVFZrWlRRNU5XRXlZbVZqTkRrMklpd2lkR0ZuSWpvaUluMD0=', 1787486096),
('GgMDdGGq7LK3RJXJLNQM5dUO8bUUbYaloiL4rDkX', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Avast/150.0.0.0', 'ZXlKcGRpSTZJalZJZERkdVkxRnBPV2gyTUdKR1oxTlZUVE5oV21jOVBTSXNJblpoYkhWbElqb2lZM3BTYXpWeWEzQkVVazFTTWpSNVZTdDRUWEJ0V0VsaGJ5dHhiVGxvWTNkMVNGQXlVVkU0YVhGblpYcHljMUpwUmsxaGIwMW1OM2h3TUhWV2F6QTNOQ3N3TVc1WGVYVjNaV3hHUmpSNFVrNUNVV3RDTlhWUlltOXRhamhLTjJzNVREaFRSREk1Y0RWSlNFRjJZM2xuY1N0cFJ5ODNkV2szUzFVMVdtc3JLM000VVhkQ1ZWbFlObHBUVTNoME5tOXNWWE52VjNOTlRHbGxaRXh0VDFkUVFWbE5hVmRYV1VJeU5VSkxORk5yUVhwTVV6WXhZekpQZDA5SWFtRmpNM04zY0RWbk1WVjJPRXBWYm1aT1lWVndRV1poVFZwblZXWmxkRWgyS3pkVGMzTnlOSGRWWTNGNUwzVmljemxvZVdoSGNqUnhNR2R2ZEdSa2NXaFVNVmgwZW5WR1RGZDJRVE5QTVhkaE1WSnJWRXBCT0c5VFJVdG5WREV5UldzdmNFRXlZVE5tTjFOUFdWQlViR1ZpYTNaWlVrNWhSbTlvWlZwMVltVmtlbEpFZUhaT1ZETXlaME5XWlZKVVkzUmFaWHBCYWk5QmMwVkJQVDBpTENKdFlXTWlPaUk0WkRrd05UTmpNemd5TWpNd1pqRmxZbUZrT1Raak1EWmlZMlF4TUdRell6SXhNbUUzWkRjeVlqTTVaV0l4T1dKaE1ETXdZalJsTmpKbE5EZGlZV05tSWl3aWRHRm5Jam9pSW4wPQ==', 1787480210),
('hx018ofXbd9jZE15FAa1pt0Y5fULLxDmTHNqlO7G', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Avast/150.0.0.0', 'ZXlKcGRpSTZJa28zVjBWWVpUSnRSa3RqYWl0MGRtVnhTMFZ0ZVVFOVBTSXNJblpoYkhWbElqb2lWek5ZTkdSdldFRXJOakYyZVdSU1VrZ3ZMMVJQWkdOdWFHeExiekZWYmt3eFdISkJSMjk2WkVsR01XTndWaXRXUW5jMVozUmtablZLTVZoM05scHhTbEZ3WlhCemVXVnJNalp6V21oNk9HOWhiVmRSWWtNNUwxcFRVWFIxVDI0d1VUTnhOM2syTUhSVlRXUmhlVVY1VlZkSFZYTk9SVXBCV1ZWSWNHMHlTUzlYYTFRMFYyOXVZM1IxVUdSeGNuUTRXR3RJV25wNlFtRTJNMjV5Y1VOVFlXMXRhSE5tVmtoSU5URTFlVTh2ZHpsS1lTOVlTMk5QV1dkcVRFMTRhVFozYWpsemNIWXJlV1YxWm5adlQzQnRUV3RZWkZsU09XeG5lVWhTYmxZdlVqVnpRek51Y2l0NE0xUlFRVVZzV25JdmNsWm1kbGR5Y1hvMVJuVldXVFY0ZWlJc0ltMWhZeUk2SWpoak16VTVOR1V4WXpGaVlURTNOV0ZsWm1SaE9XRmhORFV5WVdVeFpURTBZalE1TXpkalpESmtaR0ZpWVRsak5UUXlPVGMwWlRRMk5EazRPR1EyWmpRaUxDSjBZV2NpT2lJaWZRPT0=', 1787480364),
('t0PI4SOX7FViBrNnNb2UQWIVHJFltll3H1Z2tw6M', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Avast/150.0.0.0', 'ZXlKcGRpSTZJbFJ3WTBwNlMxQk9ObFJLYm14RVluSTFlRVJyVjBFOVBTSXNJblpoYkhWbElqb2lUVWMzTVhneGRWaDBSbG80UzJKSVpGZFZhV2xEZW04d2JFUlFPR0p4VG5wTFRtaFlWWGgzWkhkWEwxUnJiVGxsYUdaTGRsaHhZbTB3ZVdaNU1TOU5abFJSUTB0NVltZDFWR1ZRVldsRGFUWnJNVkppYm1OeGJrcEVSbUpoYVdncmMyOTZSRk4wUkc1NVJuUnBNU3RZV1M4eVpDOWlTRmN3VWxoaFFuaERVRzlKZW1acE9UTnhaR1kzYXlzeVVGVTBOV3B5TkVGd1ExZ3JObWcyVlVkMVNtNUxjelY2VUhOcWMyTlpTV05MVVhsMGQyMXBUMUl2VW5kblNtcDBXVkJaV0UxdFUyWkxjMHQzV1c1emFYZzNSRkppY0hGVWFVbHVka042VERaaldrOHZTM05KTkhvMFFqTnNabGh4VW1keU1GTkpUemxMTDJkTmR6ZzJWRkJVZWlJc0ltMWhZeUk2SWpsaU1qTXlNV0ZrTURRNVpUVTRaR1U0TVRCalpEa3daRFJqWVRZNE1HSXlNbVZpWTJKak1USTBObUV6TUdZek9EazFNMk5sT0ROa09EZzVPR1prWVRJaUxDSjBZV2NpT2lJaWZRPT0=', 1787485074),
('VlHFWjTxgRAxa0tW21oLDBnUZEH9iGGusbI2rJR6', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Avast/150.0.0.0', 'ZXlKcGRpSTZJa1JuWlZjNFZVZzJSblZ3YjBoVFN6UlZjR1ZGUkdjOVBTSXNJblpoYkhWbElqb2lTbXR3V2xsdVNWRm1Wbmd2ZVVKVWRIbFJXbWRHTkdSeFNFVlBlSFowY2pocmRUQnlhVTlLZVRSWFptWndOakZRVFU0eVkwdzRlblpIYjBObmFWWTJZM2c1U0U5b05tZHFlRGRuY25oTVMxcDRlRU5MUlZOeGNscE1XVzVDVVRWaE9FTXhNRkE1YmpKMllrWjZlalJ3ZEdWdmVuVnBibVZXUml0VFZXWnBaR0pJYmpKc1VIaDFSRmQ2ZHk5bVNtRkpOWGxyV1dkVWNrb3hXalJrZGpCelJtSlljRWQyT0dWbWVYTnFWV05ZTWxoWlozWnBSVVZ3U1Rkd00yTlJTbWhMV2xSRmVXWnNhbmgxWm5OU1dsQnBjMlJyVUdaM2JtOU9kREZ5ZWxsSVVrVnhaRWhUT1ZoVVRXSXJaWGxwYUdOWFVETnZaazFPZEdwV1MwNWphbkZ2T0NJc0ltMWhZeUk2SWpRMVpHTmlZMlJpWWpObFpURTNOek5rTldFMFpUVTVNV1l3WlROalkyRXhNMkV5T1dGallXTmpOekEzTVRReVlURTFNRGRrTnprMk5tUmlaRFJrTUdZaUxDSjBZV2NpT2lJaWZRPT0=', 1787485549);

CREATE TABLE `student_imports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uploaded_by` int NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `stored_path` varchar(255) NULL,
  `status` varchar(255) NOT NULL DEFAULT 'previewed',
  `total_rows` int NOT NULL DEFAULT '0',
  `processed_rows` int NOT NULL DEFAULT '0',
  `successful_rows` int NOT NULL DEFAULT '0',
  `failed_rows` int NOT NULL DEFAULT '0',
  `preview_rows` longtext NULL,
  `error_report` longtext NULL,
  `started_at` datetime NULL,
  `finished_at` datetime NULL,
  `created_at` datetime NULL,
  `updated_at` datetime NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `student_placements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `ticket_id` bigint unsigned NULL,
  `academic_level_id` bigint unsigned NULL,
  `academic_session_id` bigint unsigned NULL,
  `siwes_year` int NOT NULL,
  `attachment_period` varchar(255) NOT NULL,
  `company_name` varchar(255) NULL,
  `company_address` longtext NULL,
  `company_state` varchar(255) NULL,
  `company_lga` varchar(255) NULL,
  `company_supervisor_phone` varchar(255) NULL,
  `metadata` longtext NULL,
  `created_at` datetime NULL,
  `updated_at` datetime NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_placements_ticket_id_unique` (`ticket_id`),
  UNIQUE KEY `student_placements_student_id_unique` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `student_placements` (`id`, `student_id`, `ticket_id`, `academic_level_id`, `academic_session_id`, `siwes_year`, `attachment_period`, `company_name`, `company_address`, `company_state`, `company_lga`, `company_supervisor_phone`, `metadata`, `created_at`, `updated_at`) VALUES
(1, 1, 5, 6, 2, 2026, 'August to October', 'NextGen Technologies', 'Victoria Island', 'Lagos', 'Lagos Island', '08086506319', NULL, '2026-08-11 15:15:17', '2026-08-11 15:17:15');

CREATE TABLE `students` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `matric_no` varchar(255) NOT NULL,
  `faculty_id` bigint unsigned NULL,
  `department_id` bigint unsigned NULL,
  `course_id` bigint unsigned NULL,
  `academic_level_id` bigint unsigned NULL,
  `academic_session_id` bigint unsigned NULL,
  `activation_status` varchar(255) NOT NULL DEFAULT 'inactive',
  `gender` varchar(255) NULL,
  `date_of_birth` date NULL,
  `address` longtext NULL,
  `metadata` longtext NULL,
  `created_at` datetime NULL,
  `updated_at` datetime NULL,
  `deleted_at` datetime NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `students_matric_no_unique` (`matric_no`),
  UNIQUE KEY `students_user_id_unique` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `students` (`id`, `user_id`, `matric_no`, `faculty_id`, `department_id`, `course_id`, `academic_level_id`, `academic_session_id`, `activation_status`, `gender`, `date_of_birth`, `address`, `metadata`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 4, '2026/DEMO/001', 1, 1, 1, 6, 1, 'active', 'Male', '2000-01-01 00:00:00', 'New Haven', '{"nationality":"Nigerian","state":"Enugu","lga":"Enugu East","bank_name":"Kuda Microfinance Bank","account_number":"0022367856","sort_code":"50211"}', '2026-08-09 08:31:15', '2026-08-11 15:15:17', NULL);

CREATE TABLE `supervisor_student_assignments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `supervisor_id` bigint unsigned NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `assigned_by` int NULL,
  `revoked_by` int NULL,
  `assigned_at` datetime NOT NULL,
  `revoked_at` datetime NULL,
  `revocation_reason` longtext NULL,
  `metadata` longtext NULL,
  `created_at` datetime NULL,
  `updated_at` datetime NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `supervisor_student_assignments` (`id`, `supervisor_id`, `student_id`, `assigned_by`, `revoked_by`, `assigned_at`, `revoked_at`, `revocation_reason`, `metadata`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 1, '2026-08-12 12:50:26', '2026-08-12 13:16:44', 'Revoked from assignment table', NULL, '2026-08-12 12:50:26', '2026-08-12 13:16:44'),
(2, 1, 1, 1, NULL, '2026-08-12 13:17:59', NULL, NULL, NULL, '2026-08-12 13:17:59', '2026-08-12 13:17:59');

CREATE TABLE `supervisors` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `staff_no` varchar(255) NOT NULL,
  `organization` varchar(255) NULL,
  `department` varchar(255) NULL,
  `capacity` int NOT NULL DEFAULT '30',
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `metadata` longtext NULL,
  `created_at` datetime NULL,
  `updated_at` datetime NULL,
  `deleted_at` datetime NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `supervisors_staff_no_unique` (`staff_no`),
  UNIQUE KEY `supervisors_user_id_unique` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `supervisors` (`id`, `user_id`, `staff_no`, `organization`, `department`, `capacity`, `status`, `metadata`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 3, 'SUP-0001', 'COOU SIWES Unit', 'Industrial Training', 30, 'active', NULL, '2026-08-09 08:31:14', '2026-08-09 08:31:14', NULL);

CREATE TABLE `tickets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NULL,
  `generated_by` int NULL,
  `code_hash` varchar(255) NOT NULL,
  `amount` int NOT NULL,
  `currency` varchar(255) NOT NULL DEFAULT 'NGN',
  `status` varchar(255) NOT NULL DEFAULT 'generated',
  `assigned_at` datetime NULL,
  `paid_at` datetime NULL,
  `used_at` datetime NULL,
  `expires_at` datetime NULL,
  `metadata` longtext NULL,
  `created_at` datetime NULL,
  `updated_at` datetime NULL,
  `deleted_at` datetime NULL,
  `serial_number` varchar(255) NULL,
  `pin` longtext NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tickets_serial_number_unique` (`serial_number`),
  UNIQUE KEY `tickets_code_hash_unique` (`code_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `tickets` (`id`, `student_id`, `generated_by`, `code_hash`, `amount`, `currency`, `status`, `assigned_at`, `paid_at`, `used_at`, `expires_at`, `metadata`, `created_at`, `updated_at`, `deleted_at`, `serial_number`, `pin`) VALUES
(2, NULL, 1, '$2y$12$vg54yGB6ylfN6VDpsbOkEOKAurhpqxdvPmY1amaCSMbeljT2re3Vm', 5000, 'NGN', 'unused', NULL, NULL, NULL, '2026-09-08 23:59:47', NULL, '2026-08-09 23:59:47', '2026-08-10 00:08:45', NULL, 'SIWES-732120934846', 'eyJpdiI6IlFjVW1ReUdFeEhqMG5PZmFJdXR3Z3c9PSIsInZhbHVlIjoiN3MrZUcyczQrR0FtUHFWdjVoSkQxQT09IiwibWFjIjoiOGQ3YzE2ZjBjNjZlMjVjNWE3MDI4NDgzY2QzNGZiYzI4Y2ZmYWE2MWRjMmVmNzAzZWRkOTFhOGJiNTA0NzA1MCIsInRhZyI6IiJ9'),
(3, NULL, 1, '$2y$12$5SJ1CLp1v5d3XbYTz08Ol.4kTLenIH4EDxB2ZpPHW8XNgdGUVg9pm', 5000, 'NGN', 'unused', NULL, NULL, NULL, '2026-09-08 23:59:48', NULL, '2026-08-09 23:59:48', '2026-08-10 00:08:45', NULL, 'SIWES-683636203004', 'eyJpdiI6ImJkdHdLYTFJNnpaaEtUOXkvMU5LOGc9PSIsInZhbHVlIjoiS1laZDRkeWtSTjRqUXBIeG9SRkc4QT09IiwibWFjIjoiOGM3ZjViOGYyMThiNGQzNWFkN2M2ODA5OTkxY2I0N2FmMjMwZGM4Mjc4MDI5MzU0ODk0Zjk3NDIzMmYxZmIxNCIsInRhZyI6IiJ9'),
(4, NULL, 1, '$2y$12$RMfMrafG6bB/pFpLEI3VyeHm/9PNDGpGnxaFpKofiHvdUSu2E4koy', 5000, 'NGN', 'unused', NULL, NULL, NULL, '2026-09-08 23:59:48', NULL, '2026-08-09 23:59:48', '2026-08-10 00:08:45', NULL, 'SIWES-559476831618', 'eyJpdiI6IlhqRXJDYVo3dXp0NndDd0JkK0E2RFE9PSIsInZhbHVlIjoiR29zUEVwOVVEdTlHVkJ3bWJibVJaQT09IiwibWFjIjoiYTA3ZGJiMWVkMzAyNDg0OGNlYjM3MzQ0NmM5MjE0MjQzYTJjNmYyNDA4MDY3ZDNhZjJiMGJiODRjY2U3NzkzMCIsInRhZyI6IiJ9'),
(5, 1, 1, '$2y$12$qOSFhgZlytvMZu19EwcVVe3g7FBc1sUYKfTadDIJLLaJ/rCWQ/1u2', 5000, 'NGN', 'used', '2026-08-11 15:00:47', NULL, '2026-08-11 15:17:16', '2026-09-08 23:59:49', '{"placement_used_at":"2026-08-11T15:17:16.018410Z"}', '2026-08-09 23:59:49', '2026-08-11 15:17:16', NULL, 'SIWES-896051760345', 'eyJpdiI6IjNTVWtrS0s3Wk1NSExGSGNlMVdJbHc9PSIsInZhbHVlIjoiOGExSkxncHZzODFLT2hlQkd5Q1Z6dz09IiwibWFjIjoiOWNmN2M5YTQwNTAyOGY0MjljOWQzNGVkMzM3MTMwNmRhNzQyOWM5NmExOTIwZmNiYTc3MjY5Y2Y2MmZkYzQxMSIsInRhZyI6IiJ9'),
(6, NULL, 1, '$2y$12$4wmzTgqvdR/s0k84pwjxUOHQ5WAVZfAwBlsRnETeweauknJITwwXO', 5000, 'NGN', 'unused', NULL, NULL, NULL, '2026-09-08 23:59:49', NULL, '2026-08-09 23:59:49', '2026-08-10 00:08:45', NULL, 'SIWES-242197974414', 'eyJpdiI6InllRkF3WXlhOTZlMzhGaDVGV0hVdHc9PSIsInZhbHVlIjoia0p2Mk4rbXVqbUI1U04yMzlPUThxdz09IiwibWFjIjoiOTFkMjNhYjAzMTYxNDczMDI1ODBiNTAzNmM1MWUzY2U2Y2ZlMzMwY2M2MzNjYjZiMjhmZmUzYjlhYzM4MDgzZiIsInRhZyI6IiJ9');

CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NULL,
  `phone` varchar(255) NULL,
  `email_verified_at` datetime NULL,
  `password` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending_activation',
  `last_login_at` datetime NULL,
  `otp_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `metadata` longtext NULL,
  `remember_token` varchar(255) NULL,
  `created_at` datetime NULL,
  `updated_at` datetime NULL,
  `two_factor_secret` longtext NULL,
  `two_factor_recovery_codes` longtext NULL,
  `two_factor_confirmed_at` datetime NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `email_verified_at`, `password`, `status`, `last_login_at`, `otp_enabled`, `metadata`, `remember_token`, `created_at`, `updated_at`, `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`) VALUES
(1, 'Super Admin', 'superadmin@coousiwes.test', NULL, '2026-08-09 08:39:30', '$2y$12$53WVL3T9LKCL7tyogFglJuwxyeleHL9AjuO0Ku2XWR4VGMUhNEhxC', 'active', '2026-08-10 10:51:01', 0, NULL, 'sYxZrHBFVKBuNkSWDVBCs5PTYPOl0HNyFazNGnKl9vCNU0m7CPjMIpDtHPMT', '2026-08-09 08:31:12', '2026-08-10 10:51:01', NULL, NULL, NULL),
(2, 'SIWES Admin', 'admin@coousiwes.test', NULL, '2026-08-09 08:39:30', '$2y$12$pRHCHLu65kyuTyQwspEbr.CzFcFXu3C81CaXsPdGQVuWdTPXWFs8G', 'active', NULL, 0, NULL, NULL, '2026-08-09 08:31:13', '2026-08-09 08:39:30', NULL, NULL, NULL),
(3, 'Demo Supervisor', 'supervisor@coousiwes.test', NULL, '2026-08-09 08:39:31', '$2y$12$wa6HBD7m2/yDQP3faMYrme1B2eLl5EC.piNFsXlXVxZvT2wq2m11q', 'active', '2026-08-12 12:38:42', 0, NULL, NULL, '2026-08-09 08:31:14', '2026-08-12 12:38:42', NULL, NULL, NULL),
(4, 'Demo Student', 'student@coousiwes.test', '09887678', '2026-08-09 08:39:32', '$2y$12$WcXG5zOTrgDckF8eqq3JcureNEWFJ9VQ7J8z71AE1XjCeqG7Iq2Py', 'active', '2026-08-22 21:45:23', 0, NULL, NULL, '2026-08-09 08:31:15', '2026-08-22 21:45:23', NULL, NULL, NULL);

SET FOREIGN_KEY_CHECKS = 1;
