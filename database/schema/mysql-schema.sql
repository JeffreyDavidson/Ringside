/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `log_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_id` bigint unsigned DEFAULT NULL,
  `event` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `causer_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `causer_id` bigint unsigned DEFAULT NULL,
  `attribute_changes` json DEFAULT NULL,
  `properties` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subject` (`subject_type`,`subject_id`),
  KEY `causer` (`causer_type`,`causer_id`),
  KEY `activity_log_log_name_index` (`log_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `activity_periods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_periods` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `activeable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `activeable_id` bigint unsigned NOT NULL,
  `started_at` datetime NOT NULL,
  `ended_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `current_activeable_id` bigint unsigned GENERATED ALWAYS AS ((case when (`ended_at` is null) then `activeable_id` else NULL end)) STORED,
  `current_activeable_type` varchar(255) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS ((case when (`ended_at` is null) then `activeable_type` else NULL end)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `activity_periods_one_open_period_unique` (`current_activeable_type`,`current_activeable_id`),
  KEY `activity_periods_activeable_type_activeable_id_index` (`activeable_type`,`activeable_id`),
  CONSTRAINT `activity_periods_dates_ordered` CHECK (((`ended_at` is null) or (`started_at` is null) or (`ended_at` >= `started_at`)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `authentication_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `authentication_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `authenticatable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `authenticatable_id` bigint unsigned NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `login_at` timestamp NULL DEFAULT NULL,
  `login_successful` tinyint(1) NOT NULL DEFAULT '0',
  `logout_at` timestamp NULL DEFAULT NULL,
  `cleared_by_user` tinyint(1) NOT NULL DEFAULT '0',
  `location` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `authentication_log_authenticatable_type_authenticatable_id_index` (`authenticatable_type`,`authenticatable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `crew_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crew_contacts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `promotion_id` bigint unsigned NOT NULL,
  `client_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `version` int unsigned NOT NULL DEFAULT '1',
  `archived_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crew_contacts_promotion_id_client_id_unique` (`promotion_id`,`client_id`),
  KEY `crew_contacts_promotion_id_index` (`promotion_id`),
  CONSTRAINT `crew_contacts_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `employments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `employable_id` bigint unsigned NOT NULL,
  `started_at` datetime NOT NULL,
  `ended_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `current_employable_id` bigint unsigned GENERATED ALWAYS AS ((case when (`ended_at` is null) then `employable_id` else NULL end)) STORED,
  `current_employable_type` varchar(255) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS ((case when (`ended_at` is null) then `employable_type` else NULL end)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employments_one_open_period_unique` (`current_employable_type`,`current_employable_id`),
  KEY `employments_employable_type_employable_id_index` (`employable_type`,`employable_id`),
  CONSTRAINT `employments_dates_ordered` CHECK (((`ended_at` is null) or (`started_at` is null) or (`ended_at` >= `started_at`)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `event_budget_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_budget_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `event_budget_id` bigint unsigned NOT NULL,
  `direction` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `counterparty` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `planned_cents` bigint DEFAULT NULL,
  `confirmed_cents` bigint DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `archived_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `event_budget_lines_event_budget_id_index` (`event_budget_id`),
  CONSTRAINT `event_budget_lines_event_budget_id_foreign` FOREIGN KEY (`event_budget_id`) REFERENCES `event_budgets` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `event_budget_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_budget_movements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `event_budget_line_id` bigint unsigned NOT NULL,
  `kind` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount_cents` bigint NOT NULL,
  `effective_date` date NOT NULL,
  `method` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `source_id` bigint unsigned DEFAULT NULL,
  `replaces_id` bigint unsigned DEFAULT NULL,
  `actor_id` bigint unsigned NOT NULL,
  `actor_label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `voided_at` datetime DEFAULT NULL,
  `voided_by` bigint unsigned DEFAULT NULL,
  `void_reason` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_budget_movements_replaces_id_unique` (`replaces_id`),
  KEY `event_budget_movements_event_budget_line_id_index` (`event_budget_line_id`),
  KEY `event_budget_movements_source_id_index` (`source_id`),
  CONSTRAINT `event_budget_movements_event_budget_line_id_foreign` FOREIGN KEY (`event_budget_line_id`) REFERENCES `event_budget_lines` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `event_budget_movements_replaces_id_foreign` FOREIGN KEY (`replaces_id`) REFERENCES `event_budget_movements` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `event_budget_movements_source_id_foreign` FOREIGN KEY (`source_id`) REFERENCES `event_budget_movements` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `event_budget_revisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_budget_revisions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `event_budget_id` bigint unsigned NOT NULL,
  `version` int NOT NULL,
  `request_key` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `request_hash` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `operation` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_id` bigint unsigned NOT NULL,
  `actor_id` bigint unsigned NOT NULL,
  `actor_label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `before` json NOT NULL,
  `after` json NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `budget_revision_version_unique` (`event_budget_id`,`version`),
  UNIQUE KEY `budget_revision_request_unique` (`event_budget_id`,`request_key`),
  CONSTRAINT `event_budget_revisions_event_budget_id_foreign` FOREIGN KEY (`event_budget_id`) REFERENCES `event_budgets` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `event_budgets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_budgets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `event_id` bigint unsigned NOT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `timezone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `version` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_budgets_event_id_unique` (`event_id`),
  CONSTRAINT `event_budgets_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `event_live_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_live_runs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `event_id` bigint unsigned NOT NULL,
  `version` int unsigned NOT NULL,
  `pack_version` int unsigned NOT NULL,
  `card_fingerprint` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'prepared',
  `content` json NOT NULL,
  `prepared_at` datetime NOT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_live_runs_event_id_unique` (`event_id`),
  CONSTRAINT `event_live_runs_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `event_packs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_packs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `event_id` bigint unsigned NOT NULL,
  `version` int unsigned NOT NULL,
  `card_fingerprint` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` json NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_packs_event_id_unique` (`event_id`),
  CONSTRAINT `event_packs_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `event_publications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_publications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `event_id` bigint unsigned NOT NULL,
  `promotion_id` bigint unsigned NOT NULL,
  `public_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `version` int unsigned NOT NULL,
  `draft` json NOT NULL,
  `card` json DEFAULT NULL,
  `results` json DEFAULT NULL,
  `card_source` json DEFAULT NULL,
  `results_source` json DEFAULT NULL,
  `schedule_fingerprint` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `card_fingerprint` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `results_fingerprint` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `results_published_at` datetime DEFAULT NULL,
  `unpublished_at` datetime DEFAULT NULL,
  `published_by` bigint unsigned DEFAULT NULL,
  `results_published_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_publications_event_id_unique` (`event_id`),
  UNIQUE KEY `event_publications_public_id_unique` (`public_id`),
  KEY `event_publications_published_by_foreign` (`published_by`),
  KEY `event_publications_results_published_by_foreign` (`results_published_by`),
  KEY `event_publications_promotion_id_index` (`promotion_id`),
  CONSTRAINT `event_publications_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `event_publications_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `event_publications_published_by_foreign` FOREIGN KEY (`published_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `event_publications_results_published_by_foreign` FOREIGN KEY (`results_published_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `event_sponsor_commitment_revisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_sponsor_commitment_revisions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `promotion_id` bigint unsigned NOT NULL,
  `commitment_id` bigint unsigned NOT NULL,
  `actor_id` bigint unsigned DEFAULT NULL,
  `actor_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `version` int unsigned NOT NULL,
  `operation` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `snapshot` json NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sponsor_commitment_revision_unique` (`commitment_id`,`version`),
  KEY `event_sponsor_commitment_revisions_promotion_id_index` (`promotion_id`),
  KEY `event_sponsor_commitment_revisions_commitment_id_index` (`commitment_id`),
  KEY `event_sponsor_commitment_revisions_actor_id_index` (`actor_id`),
  CONSTRAINT `event_sponsor_commitment_revisions_actor_id_foreign` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `event_sponsor_commitment_revisions_commitment_id_foreign` FOREIGN KEY (`commitment_id`) REFERENCES `event_sponsor_commitments` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `event_sponsor_commitment_revisions_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `event_sponsor_commitments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_sponsor_commitments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `promotion_id` bigint unsigned NOT NULL,
  `event_id` bigint unsigned NOT NULL,
  `sponsor_id` bigint unsigned NOT NULL,
  `submission_key` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `version` int unsigned NOT NULL,
  `sponsor_name` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `description` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `responsible` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deadline_at` datetime DEFAULT NULL,
  `timezone` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `agreement_reference` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `evidence_note` text COLLATE utf8mb4_unicode_ci,
  `evidence_url` text COLLATE utf8mb4_unicode_ci,
  `completed_at` datetime DEFAULT NULL,
  `instruction_label` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `instruction` text COLLATE utf8mb4_unicode_ci,
  `instruction_owner` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pack_key` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pack_instruction_hash` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event_context` json NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sponsor_commitment_submission_unique` (`event_id`,`submission_key`),
  UNIQUE KEY `sponsor_commitment_pack_key_unique` (`event_id`,`pack_key`),
  KEY `event_sponsor_commitments_promotion_id_status_deadline_at_index` (`promotion_id`,`status`,`deadline_at`),
  KEY `event_sponsor_commitments_promotion_id_index` (`promotion_id`),
  KEY `event_sponsor_commitments_event_id_index` (`event_id`),
  KEY `event_sponsor_commitments_sponsor_id_index` (`sponsor_id`),
  CONSTRAINT `event_sponsor_commitments_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `event_sponsor_commitments_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `event_sponsor_commitments_sponsor_id_foreign` FOREIGN KEY (`sponsor_id`) REFERENCES `sponsors` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `event_talent_booking_revisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_talent_booking_revisions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` bigint unsigned NOT NULL,
  `version` int unsigned NOT NULL,
  `operation` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `snapshot` json NOT NULL,
  `actor_id` bigint unsigned DEFAULT NULL,
  `actor_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `talent_booking_revision_unique` (`booking_id`,`version`),
  KEY `event_talent_booking_revisions_actor_id_index` (`actor_id`),
  CONSTRAINT `event_talent_booking_revisions_actor_id_foreign` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `event_talent_booking_revisions_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `event_talent_bookings` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `event_talent_bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_talent_bookings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `event_id` bigint unsigned NOT NULL,
  `person_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `person_id` bigint unsigned NOT NULL,
  `person_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `person_role` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'proposed',
  `version` int unsigned NOT NULL,
  `fee_minor` bigint unsigned DEFAULT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `arrival_at` datetime DEFAULT NULL,
  `arrival_timezone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `travel_notes` text COLLATE utf8mb4_unicode_ci,
  `terms_need_review` tinyint(1) NOT NULL DEFAULT '0',
  `confirmed_snapshot` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `talent_booking_person_unique` (`event_id`,`person_type`,`person_id`),
  CONSTRAINT `event_talent_bookings_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` datetime DEFAULT NULL,
  `venue_id` bigint unsigned DEFAULT NULL,
  `preview` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `promotion_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `events_promotion_id_date_index` (`promotion_id`,`date`),
  KEY `events_venue_id_index` (`venue_id`),
  CONSTRAINT `events_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `events_matches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `events_matches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `event_id` bigint unsigned NOT NULL,
  `match_number` smallint unsigned NOT NULL,
  `match_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `preview` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `match_finish` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `winning_side_id` bigint unsigned DEFAULT NULL,
  `match_stipulation_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `events_matches_event_id_match_number_unique` (`event_id`,`match_number`),
  KEY `events_matches_winning_side_id_foreign` (`winning_side_id`),
  KEY `events_matches_match_stipulation_id_foreign` (`match_stipulation_id`),
  CONSTRAINT `events_matches_match_stipulation_id_foreign` FOREIGN KEY (`match_stipulation_id`) REFERENCES `matches_stipulations` (`id`),
  CONSTRAINT `events_matches_winning_side_id_foreign` FOREIGN KEY (`winning_side_id`) REFERENCES `events_matches_sides` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `events_matches_competitors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `events_matches_competitors` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `match_id` bigint unsigned NOT NULL,
  `match_side_id` bigint unsigned NOT NULL,
  `entry_order` int unsigned DEFAULT NULL,
  `elimination_order` int unsigned DEFAULT NULL,
  `eliminated_by_match_competitor_id` bigint unsigned DEFAULT NULL,
  `competitor_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `competitor_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `match_competitor_unique` (`match_id`,`competitor_type`,`competitor_id`),
  UNIQUE KEY `events_matches_competitors_match_id_entry_order_unique` (`match_id`,`entry_order`),
  UNIQUE KEY `events_matches_competitors_match_id_elimination_order_unique` (`match_id`,`elimination_order`),
  KEY `events_matches_competitors_competitor_type_competitor_id_index` (`competitor_type`,`competitor_id`),
  KEY `events_matches_competitors_match_side_id_match_id_foreign` (`match_side_id`,`match_id`),
  KEY `emc_eliminated_by_fk` (`eliminated_by_match_competitor_id`),
  KEY `events_matches_competitors_match_side_id_index` (`match_side_id`),
  CONSTRAINT `emc_eliminated_by_fk` FOREIGN KEY (`eliminated_by_match_competitor_id`) REFERENCES `events_matches_competitors` (`id`) ON DELETE SET NULL,
  CONSTRAINT `events_matches_competitors_match_id_foreign` FOREIGN KEY (`match_id`) REFERENCES `events_matches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `events_matches_competitors_match_side_id_match_id_foreign` FOREIGN KEY (`match_side_id`, `match_id`) REFERENCES `events_matches_sides` (`id`, `match_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `events_matches_referees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `events_matches_referees` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `match_id` bigint unsigned NOT NULL,
  `referee_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `events_matches_referees_match_referee_unique` (`match_id`,`referee_id`),
  KEY `events_matches_referees_referee_id_index` (`referee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `events_matches_sides`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `events_matches_sides` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `match_id` bigint unsigned NOT NULL,
  `position` tinyint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `events_matches_sides_match_id_position_unique` (`match_id`,`position`),
  UNIQUE KEY `events_matches_sides_id_match_id_unique` (`id`,`match_id`),
  CONSTRAINT `events_matches_sides_match_id_foreign` FOREIGN KEY (`match_id`) REFERENCES `events_matches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `events_matches_titles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `events_matches_titles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `match_id` bigint unsigned NOT NULL,
  `title_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `events_matches_titles_match_title_unique` (`match_id`,`title_id`),
  KEY `events_matches_titles_title_id_index` (`title_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `injuries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `injuries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `injurable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `injurable_id` bigint unsigned NOT NULL,
  `started_at` datetime NOT NULL,
  `ended_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `current_injurable_id` bigint unsigned GENERATED ALWAYS AS ((case when (`ended_at` is null) then `injurable_id` else NULL end)) STORED,
  `current_injurable_type` varchar(255) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS ((case when (`ended_at` is null) then `injurable_type` else NULL end)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `injuries_one_open_period_unique` (`current_injurable_type`,`current_injurable_id`),
  KEY `injuries_injurable_type_injurable_id_index` (`injurable_type`,`injurable_id`),
  CONSTRAINT `injuries_dates_ordered` CHECK (((`ended_at` is null) or (`started_at` is null) or (`ended_at` >= `started_at`)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lifecycle_transitions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lifecycle_transitions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `subject_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_id` bigint unsigned NOT NULL,
  `dimension` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `transition` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `effective_at` datetime NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `context` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lifecycle_transitions_user_id_foreign` (`user_id`),
  KEY `lifecycle_transitions_history_index` (`subject_type`,`subject_id`,`dimension`,`effective_at`),
  CONSTRAINT `lifecycle_transitions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `managers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `managers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS (concat(`first_name`,_utf8mb4' ',`last_name`)) VIRTUAL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `promotion_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `managers_promotion_id_index` (`promotion_id`),
  KEY `managers_promotion_id_last_name_id_index` (`promotion_id`,`last_name`,`id`),
  CONSTRAINT `managers_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `matches_stipulations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `matches_stipulations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `promotion_invitations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `promotion_invitations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `promotion_id` bigint unsigned NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `role` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'member',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `promotion_invitations_promotion_id_email_unique` (`promotion_id`,`email`),
  KEY `promotion_invitations_email_index` (`email`),
  KEY `promotion_invitations_expires_at_index` (`expires_at`),
  CONSTRAINT `promotion_invitations_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `promotion_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `promotion_user` (
  `promotion_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `role` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'member',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  UNIQUE KEY `promotion_user_promotion_id_user_id_unique` (`promotion_id`,`user_id`),
  KEY `promotion_user_user_id_index` (`user_id`),
  CONSTRAINT `promotion_user_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `promotion_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `promotions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `promotions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `timezone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'UTC',
  PRIMARY KEY (`id`),
  UNIQUE KEY `promotions_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `record_name_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `record_name_locks` (
  `name_key` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`name_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `referees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `referees` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS (concat(`first_name`,_utf8mb4' ',`last_name`)) VIRTUAL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `promotion_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `referees_promotion_id_index` (`promotion_id`),
  KEY `referees_promotion_id_last_name_id_index` (`promotion_id`,`last_name`,`id`),
  KEY `referees_promotion_id_full_name_id_index` (`promotion_id`,`full_name`,`id`),
  CONSTRAINT `referees_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `retirements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `retirements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `retirable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `retirable_id` bigint unsigned NOT NULL,
  `started_at` datetime NOT NULL,
  `ended_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `current_retirable_id` bigint unsigned GENERATED ALWAYS AS ((case when (`ended_at` is null) then `retirable_id` else NULL end)) STORED,
  `current_retirable_type` varchar(255) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS ((case when (`ended_at` is null) then `retirable_type` else NULL end)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `retirements_one_open_period_unique` (`current_retirable_type`,`current_retirable_id`),
  KEY `retirements_retirable_type_retirable_id_index` (`retirable_type`,`retirable_id`),
  CONSTRAINT `retirements_dates_ordered` CHECK (((`ended_at` is null) or (`started_at` is null) or (`ended_at` >= `started_at`)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roster_unavailabilities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roster_unavailabilities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `promotion_id` bigint unsigned NOT NULL,
  `person_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `person_id` bigint unsigned NOT NULL,
  `starts_on` date NOT NULL,
  `ends_on` date NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancelled_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `roster_unavailability_person_dates` (`promotion_id`,`person_type`,`person_id`,`cancelled_at`,`starts_on`),
  KEY `roster_unavailabilities_promotion_id_starts_on_ends_on_index` (`promotion_id`,`starts_on`,`ends_on`),
  KEY `roster_unavailabilities_created_by_index` (`created_by`),
  KEY `roster_unavailabilities_cancelled_by_index` (`cancelled_by`),
  CONSTRAINT `roster_unavailabilities_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `roster_unavailabilities_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `roster_unavailabilities_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `scheduling_slot_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `scheduling_slot_locks` (
  `slot` bigint NOT NULL,
  PRIMARY KEY (`slot`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sponsors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sponsors` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `promotion_id` bigint unsigned NOT NULL,
  `submission_key` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `version` int unsigned NOT NULL,
  `name` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_name` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_phone` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `archived_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sponsors_promotion_id_submission_key_unique` (`promotion_id`,`submission_key`),
  KEY `sponsors_promotion_id_index` (`promotion_id`),
  CONSTRAINT `sponsors_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stable_name_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stable_name_locks` (
  `name_key` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`name_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stables` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `active_name` varchar(255) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS ((case when (`deleted_at` is null) then `name` else NULL end)) STORED,
  `promotion_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stables_active_name_unique` (`promotion_id`,`active_name`),
  CONSTRAINT `stables_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stables_tag_teams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stables_tag_teams` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `stable_id` bigint unsigned NOT NULL,
  `tag_team_id` bigint unsigned NOT NULL,
  `joined_at` datetime NOT NULL,
  `left_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `current_tag_team_id` bigint unsigned GENERATED ALWAYS AS ((case when (`left_at` is null) then `tag_team_id` else NULL end)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stables_tag_teams_one_current_membership_unique` (`current_tag_team_id`),
  KEY `stables_tag_teams_stable_id_index` (`stable_id`),
  KEY `stables_tag_teams_tag_team_id_index` (`tag_team_id`),
  CONSTRAINT `stables_tag_teams_dates_ordered` CHECK (((`left_at` is null) or (`joined_at` is null) or (`left_at` >= `joined_at`)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stables_wrestlers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stables_wrestlers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `stable_id` bigint unsigned NOT NULL,
  `wrestler_id` bigint unsigned NOT NULL,
  `joined_at` datetime NOT NULL,
  `left_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `current_wrestler_id` bigint unsigned GENERATED ALWAYS AS ((case when (`left_at` is null) then `wrestler_id` else NULL end)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stables_wrestlers_one_current_membership_unique` (`current_wrestler_id`),
  KEY `stables_wrestlers_stable_id_index` (`stable_id`),
  KEY `stables_wrestlers_wrestler_id_index` (`wrestler_id`),
  CONSTRAINT `stables_wrestlers_dates_ordered` CHECK (((`left_at` is null) or (`joined_at` is null) or (`left_at` >= `joined_at`)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `storyline_developments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `storyline_developments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `promotion_id` bigint unsigned NOT NULL,
  `storyline_id` bigint unsigned NOT NULL,
  `storyline_step_id` bigint unsigned DEFAULT NULL,
  `heading` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `occurred_on` date NOT NULL,
  `timezone` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sequence` int unsigned NOT NULL,
  `event_id` bigint unsigned DEFAULT NULL,
  `match_id` bigint unsigned DEFAULT NULL,
  `segment_id` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_context` json NOT NULL,
  `cast` json NOT NULL,
  `version` int unsigned NOT NULL DEFAULT '1',
  `submission_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `retracted_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `storyline_developments_submission_id_unique` (`submission_id`),
  UNIQUE KEY `storyline_developments_storyline_step_id_unique` (`storyline_step_id`),
  KEY `storyline_developments_promotion_id_index` (`promotion_id`),
  KEY `storyline_developments_storyline_id_index` (`storyline_id`),
  KEY `storyline_developments_event_id_index` (`event_id`),
  KEY `storyline_developments_match_id_index` (`match_id`),
  CONSTRAINT `storyline_developments_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `storyline_developments_match_id_foreign` FOREIGN KEY (`match_id`) REFERENCES `events_matches` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `storyline_developments_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `storyline_developments_storyline_id_foreign` FOREIGN KEY (`storyline_id`) REFERENCES `storylines` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `storyline_developments_storyline_step_id_foreign` FOREIGN KEY (`storyline_step_id`) REFERENCES `storyline_steps` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `storyline_participants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `storyline_participants` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `promotion_id` bigint unsigned NOT NULL,
  `storyline_id` bigint unsigned NOT NULL,
  `participant_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `participant_id` bigint unsigned NOT NULL,
  `label` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `joined_on` date NOT NULL,
  `left_on` date DEFAULT NULL,
  `open_participant_id` bigint unsigned GENERATED ALWAYS AS ((case when (`left_on` is null) then `participant_id` else NULL end)) STORED,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `storyline_one_open_participant` (`storyline_id`,`participant_type`,`open_participant_id`),
  KEY `storyline_participants_participant_type_participant_id_index` (`participant_type`,`participant_id`),
  KEY `storyline_participants_promotion_id_index` (`promotion_id`),
  KEY `storyline_participants_storyline_id_index` (`storyline_id`),
  CONSTRAINT `storyline_participants_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `storyline_participants_storyline_id_foreign` FOREIGN KEY (`storyline_id`) REFERENCES `storylines` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `storyline_revisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `storyline_revisions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `promotion_id` bigint unsigned NOT NULL,
  `storyline_id` bigint unsigned NOT NULL,
  `actor_id` bigint unsigned NOT NULL,
  `operation` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `record_type` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `record_id` bigint unsigned NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `before` json NOT NULL,
  `after` json NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `storyline_revisions_promotion_id_index` (`promotion_id`),
  KEY `storyline_revisions_storyline_id_index` (`storyline_id`),
  KEY `storyline_revisions_actor_id_index` (`actor_id`),
  CONSTRAINT `storyline_revisions_actor_id_foreign` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `storyline_revisions_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `storyline_revisions_storyline_id_foreign` FOREIGN KEY (`storyline_id`) REFERENCES `storylines` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `storyline_steps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `storyline_steps` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `promotion_id` bigint unsigned NOT NULL,
  `storyline_id` bigint unsigned NOT NULL,
  `heading` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'planned',
  `sequence` int unsigned NOT NULL,
  `event_id` bigint unsigned DEFAULT NULL,
  `match_id` bigint unsigned DEFAULT NULL,
  `segment_id` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target_on` date DEFAULT NULL,
  `target_at` datetime DEFAULT NULL,
  `timezone` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_context` json NOT NULL,
  `version` int unsigned NOT NULL DEFAULT '1',
  `submission_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `storyline_steps_submission_id_unique` (`submission_id`),
  KEY `storyline_steps_promotion_id_index` (`promotion_id`),
  KEY `storyline_steps_storyline_id_index` (`storyline_id`),
  KEY `storyline_steps_event_id_index` (`event_id`),
  KEY `storyline_steps_match_id_index` (`match_id`),
  CONSTRAINT `storyline_steps_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `storyline_steps_match_id_foreign` FOREIGN KEY (`match_id`) REFERENCES `events_matches` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `storyline_steps_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `storyline_steps_storyline_id_foreign` FOREIGN KEY (`storyline_id`) REFERENCES `storylines` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `storylines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `storylines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `promotion_id` bigint unsigned NOT NULL,
  `title` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `premise` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payoff` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `previous_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `version` int unsigned NOT NULL DEFAULT '1',
  `submission_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `storylines_submission_id_unique` (`submission_id`),
  KEY `storylines_promotion_id_index` (`promotion_id`),
  CONSTRAINT `storylines_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `suspensions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `suspensions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `suspendable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `suspendable_id` bigint unsigned NOT NULL,
  `started_at` datetime NOT NULL,
  `ended_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `current_suspendable_id` bigint unsigned GENERATED ALWAYS AS ((case when (`ended_at` is null) then `suspendable_id` else NULL end)) STORED,
  `current_suspendable_type` varchar(255) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS ((case when (`ended_at` is null) then `suspendable_type` else NULL end)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `suspensions_one_open_period_unique` (`current_suspendable_type`,`current_suspendable_id`),
  KEY `suspensions_suspendable_type_suspendable_id_index` (`suspendable_type`,`suspendable_id`),
  CONSTRAINT `suspensions_dates_ordered` CHECK (((`ended_at` is null) or (`started_at` is null) or (`ended_at` >= `started_at`)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tag_teams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tag_teams` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `signature_move` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `promotion_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tag_teams_promotion_id_index` (`promotion_id`),
  KEY `tag_teams_promotion_id_name_id_index` (`promotion_id`,`name`,`id`),
  CONSTRAINT `tag_teams_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tag_teams_managers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tag_teams_managers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tag_team_id` bigint unsigned NOT NULL,
  `manager_id` bigint unsigned NOT NULL,
  `hired_at` datetime NOT NULL,
  `fired_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tag_teams_managers_tag_team_id_index` (`tag_team_id`),
  KEY `tag_teams_managers_manager_id_index` (`manager_id`),
  CONSTRAINT `tag_teams_managers_manager_id_foreign` FOREIGN KEY (`manager_id`) REFERENCES `managers` (`id`),
  CONSTRAINT `tag_teams_managers_tag_team_id_foreign` FOREIGN KEY (`tag_team_id`) REFERENCES `tag_teams` (`id`),
  CONSTRAINT `tag_teams_managers_dates_ordered` CHECK (((`fired_at` is null) or (`hired_at` is null) or (`fired_at` >= `hired_at`)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tag_teams_wrestlers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tag_teams_wrestlers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tag_team_id` bigint unsigned NOT NULL,
  `wrestler_id` bigint unsigned NOT NULL,
  `joined_at` datetime DEFAULT NULL,
  `left_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `current_wrestler_id` bigint unsigned GENERATED ALWAYS AS ((case when (`left_at` is null) then `wrestler_id` else NULL end)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tag_teams_wrestlers_one_current_membership_unique` (`current_wrestler_id`),
  KEY `tag_teams_wrestlers_tag_team_id_index` (`tag_team_id`),
  KEY `tag_teams_wrestlers_wrestler_id_index` (`wrestler_id`),
  CONSTRAINT `tag_teams_wrestlers_dates_ordered` CHECK (((`left_at` is null) or (`joined_at` is null) or (`left_at` >= `joined_at`)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `titles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `titles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `promotion_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `titles_promotion_id_index` (`promotion_id`),
  KEY `titles_promotion_id_name_id_index` (`promotion_id`,`name`,`id`),
  CONSTRAINT `titles_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `titles_championships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `titles_championships` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title_id` bigint unsigned NOT NULL,
  `champion_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `champion_id` bigint unsigned NOT NULL,
  `won_match_id` bigint unsigned DEFAULT NULL,
  `lost_match_id` bigint unsigned DEFAULT NULL,
  `won_at` datetime NOT NULL,
  `lost_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `open_reign_title_id` bigint unsigned GENERATED ALWAYS AS ((case when ((`lost_at` is null) and (`deleted_at` is null)) then `title_id` else NULL end)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `titles_championships_one_open_reign_unique` (`open_reign_title_id`),
  KEY `titles_championships_champion_type_champion_id_index` (`champion_type`,`champion_id`),
  KEY `titles_championships_title_id_won_at_index` (`title_id`,`won_at`),
  CONSTRAINT `titles_championships_dates_ordered` CHECK (((`lost_at` is null) or (`won_at` is null) or (`lost_at` >= `won_at`)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tournament_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tournament_entries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `promotion_id` bigint unsigned NOT NULL,
  `tournament_id` bigint unsigned NOT NULL,
  `seed` tinyint unsigned NOT NULL,
  `competitor_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `competitor_id` bigint unsigned NOT NULL,
  `withdrawn` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tournament_entries_tournament_id_seed_unique` (`tournament_id`,`seed`),
  UNIQUE KEY `tournament_entry_identity_unique` (`tournament_id`,`competitor_type`,`competitor_id`),
  KEY `tournament_entries_competitor_type_competitor_id_index` (`competitor_type`,`competitor_id`),
  KEY `tournament_entries_promotion_id_index` (`promotion_id`),
  CONSTRAINT `tournament_entries_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `tournament_entries_tournament_id_foreign` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tournament_match_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tournament_match_links` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `promotion_id` bigint unsigned NOT NULL,
  `tournament_id` bigint unsigned NOT NULL,
  `node_id` bigint unsigned NOT NULL,
  `active_node_id` bigint unsigned DEFAULT NULL,
  `match_id` bigint unsigned NOT NULL,
  `attempt` int unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tournament_match_links_node_id_attempt_unique` (`node_id`,`attempt`),
  UNIQUE KEY `tournament_match_links_match_id_unique` (`match_id`),
  UNIQUE KEY `tournament_match_links_active_node_id_unique` (`active_node_id`),
  KEY `tournament_match_links_promotion_id_index` (`promotion_id`),
  KEY `tournament_match_links_tournament_id_index` (`tournament_id`),
  CONSTRAINT `tournament_match_links_active_node_id_foreign` FOREIGN KEY (`active_node_id`) REFERENCES `tournament_nodes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `tournament_match_links_match_id_foreign` FOREIGN KEY (`match_id`) REFERENCES `events_matches` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `tournament_match_links_node_id_foreign` FOREIGN KEY (`node_id`) REFERENCES `tournament_nodes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `tournament_match_links_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `tournament_match_links_tournament_id_foreign` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tournament_nodes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tournament_nodes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `promotion_id` bigint unsigned NOT NULL,
  `tournament_id` bigint unsigned NOT NULL,
  `round` tinyint unsigned NOT NULL,
  `position` tinyint unsigned NOT NULL,
  `first_seed` tinyint unsigned DEFAULT NULL,
  `second_seed` tinyint unsigned DEFAULT NULL,
  `planned_event_id` bigint unsigned DEFAULT NULL,
  `walkover_entry_id` bigint unsigned DEFAULT NULL,
  `walkover_reason` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tournament_nodes_tournament_id_round_position_unique` (`tournament_id`,`round`,`position`),
  KEY `tournament_nodes_promotion_id_index` (`promotion_id`),
  KEY `tournament_nodes_planned_event_id_index` (`planned_event_id`),
  KEY `tournament_nodes_walkover_entry_id_index` (`walkover_entry_id`),
  CONSTRAINT `tournament_nodes_planned_event_id_foreign` FOREIGN KEY (`planned_event_id`) REFERENCES `events` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `tournament_nodes_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `tournament_nodes_tournament_id_foreign` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `tournament_nodes_walkover_entry_id_foreign` FOREIGN KEY (`walkover_entry_id`) REFERENCES `tournament_entries` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tournaments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tournaments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `promotion_id` bigint unsigned NOT NULL,
  `request_id` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `entrant_kind` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `capacity` tinyint unsigned NOT NULL,
  `repeat_appearances` tinyint(1) NOT NULL DEFAULT '0',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `version` int unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tournaments_promotion_id_request_id_unique` (`promotion_id`,`request_id`),
  KEY `tournaments_promotion_id_index` (`promotion_id`),
  CONSTRAINT `tournaments_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS (concat(`first_name`,_utf8mb4' ',`last_name`)) VIRTUAL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `avatar_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_phone_number_unique` (`phone_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `venues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `venues` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `street_address` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `state` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `zipcode` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `timezone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'UTC',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wrestlers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wrestlers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `height` int NOT NULL,
  `weight` int NOT NULL,
  `hometown` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `signature_move` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `promotion_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wrestlers_promotion_id_index` (`promotion_id`),
  KEY `wrestlers_promotion_id_name_id_index` (`promotion_id`,`name`,`id`),
  CONSTRAINT `wrestlers_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wrestlers_managers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wrestlers_managers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `wrestler_id` bigint unsigned NOT NULL,
  `manager_id` bigint unsigned NOT NULL,
  `hired_at` datetime NOT NULL,
  `fired_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wrestlers_managers_wrestler_id_index` (`wrestler_id`),
  KEY `wrestlers_managers_manager_id_index` (`manager_id`),
  CONSTRAINT `wrestlers_managers_manager_id_foreign` FOREIGN KEY (`manager_id`) REFERENCES `managers` (`id`),
  CONSTRAINT `wrestlers_managers_wrestler_id_foreign` FOREIGN KEY (`wrestler_id`) REFERENCES `wrestlers` (`id`),
  CONSTRAINT `wrestlers_managers_dates_ordered` CHECK (((`fired_at` is null) or (`hired_at` is null) or (`fired_at` >= `hired_at`)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'2014_10_12_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'2014_10_12_100000_create_password_resets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'2019_01_30_152756_create_wrestlers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2019_02_18_211134_create_tag_teams_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2019_02_18_225920_create_tag_teams_wrestlers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2019_02_22_205952_create_managers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2019_02_25_203931_create_referees_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2019_02_26_132953_create_stables_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2019_03_11_134305_create_venues_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2019_03_14_143148_create_titles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2019_03_28_130735_create_events_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2019_03_29_005226_create_match_types_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2019_03_29_023716_create_match_decisions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2021_09_19_212001_create_events_matches_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2021_09_22_230702_create_events_matches_referees_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2021_09_22_231322_create_events_matches_competitors_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2021_09_23_000517_create_events_matches_titles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2023_12_26_000029_create_events_matches_results',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2024_03_14_000000_rename_password_resets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2024_08_08_195719_create_sessions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2024_09_25_031332_create_referees_employments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2024_09_25_031349_create_managers_employments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2024_09_25_031407_create_tag_teams_employments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2024_09_25_031542_create_wrestlers_employments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2024_09_25_172420_create_titles_activations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2024_10_30_003811_create_wrestlers_injuries_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2024_10_30_003950_create_managers_injuries_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2024_10_30_004116_create_referees_injuries_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2024_10_30_015717_create_wrestlers_suspensions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2024_10_30_015737_create_managers_suspensions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2024_10_30_015750_create_tag_teams_suspensions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2024_10_30_020034_create_referees_suspensions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2024_10_30_034037_create_referees_retirements_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2024_10_30_034057_create_managers_retirements_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2024_10_30_034111_create_wrestlers_retirements_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2024_10_30_034119_create_stables_retirements_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2024_10_30_034132_create_tag_teams_retirements_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2024_10_30_035459_create_titles_retirements_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2024_10_30_123147_create_stables_activations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2024_10_31_170955_create_wrestlers_managers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2024_10_31_171013_create_tag_teams_managers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2024_10_31_174821_create_stables_wrestlers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2024_10_31_174828_create_stables_tag_teams_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2024_11_20_021759_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2025_02_08_175848_create_titles_championships_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2025_02_26_033952_create_authentication_log_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2025_07_18_222736_create_titles_status_changes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2025_07_18_222741_create_stables_status_changes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2025_07_18_231455_add_deleted_at_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2025_07_24_130456_create_events_matches_winners_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2025_07_24_130500_create_events_matches_losers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2025_07_24_130600_make_winner_nullable_in_events_matches_results_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2025_07_26_213803_remove_user_id_from_organizational_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2025_09_20_142024_convert_match_type_to_enum_in_events_matches_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2025_09_20_142053_convert_match_decision_to_enum_in_events_matches_results_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2025_09_20_143044_drop_match_types_and_match_decisions_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2026_05_01_000001_add_expiration_index_to_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2026_08_09_230854_enforce_unique_active_stable_names',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2026_08_11_015443_enforce_single_open_activity_period_for_stables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2026_08_11_022704_enforce_single_current_stable_membership_for_wrestlers',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2026_08_11_022715_enforce_single_current_stable_membership_for_tag_teams',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2026_08_11_024027_enforce_single_open_employment_for_wrestlers',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2026_08_11_024028_enforce_single_open_employment_for_managers',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2026_08_11_024029_enforce_single_open_employment_for_referees',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2026_08_11_024030_enforce_single_open_employment_for_tag_teams',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2026_08_11_025917_enforce_single_open_injury_for_wrestlers',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2026_08_11_025918_enforce_single_open_injury_for_managers',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2026_08_11_025919_enforce_single_open_injury_for_referees',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2026_08_11_030517_enforce_single_open_suspension_for_wrestlers',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2026_08_11_030518_enforce_single_open_suspension_for_managers',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2026_08_11_030519_enforce_single_open_suspension_for_referees',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2026_08_11_030520_enforce_single_open_suspension_for_tag_teams',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (73,'2026_08_11_031330_enforce_single_open_retirement_for_wrestlers',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (74,'2026_08_11_031331_enforce_single_open_retirement_for_managers',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (75,'2026_08_11_031332_enforce_single_open_retirement_for_referees',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (76,'2026_08_11_031333_enforce_single_open_retirement_for_tag_teams',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (77,'2026_08_11_031334_enforce_single_open_retirement_for_stables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (78,'2026_08_11_031335_enforce_single_open_retirement_for_titles',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (79,'2026_08_11_032028_enforce_single_open_activity_period_for_titles',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (80,'2026_08_12_000808_consolidate_employment_records',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (81,'2026_08_12_013145_consolidate_injury_records',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (82,'2026_08_12_020456_consolidate_suspension_records',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (83,'2026_08_12_132209_consolidate_retirement_records',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (84,'2026_08_12_133119_migrate_legacy_injury_records',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (85,'2026_08_12_133120_remove_legacy_injury_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (86,'2026_08_12_133830_migrate_legacy_retirement_records',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (87,'2026_08_12_133831_remove_legacy_retirement_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (88,'2026_08_12_134132_migrate_legacy_suspension_records',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (89,'2026_08_12_134133_remove_legacy_suspension_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (90,'2026_08_12_142446_create_activity_periods_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (91,'2026_08_12_142450_migrate_legacy_activity_period_records',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (92,'2026_08_12_142454_remove_legacy_activity_period_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (93,'2026_08_12_160728_create_lifecycle_transitions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (94,'2026_08_12_160736_migrate_legacy_status_changes_to_lifecycle_transitions',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (95,'2026_08_12_160742_remove_legacy_status_change_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (96,'2026_08_13_135841_add_deleted_at_to_events_matches_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (97,'2026_08_13_214744_remove_user_id_from_wrestlers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (98,'2026_08_15_020523_redesign_match_results_around_sides',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (99,'2026_08_15_145235_add_entry_and_elimination_metadata_to_events_matches_competitors_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (100,'2026_08_15_155333_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (101,'2026_08_15_190102_create_matches_stipulations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (102,'2026_08_15_190106_add_match_stipulation_id_to_events_matches_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (103,'2026_08_15_215840_add_deleted_at_to_titles_championships_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (104,'2026_08_15_223934_drop_legacy_champion_columns_from_titles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (105,'2026_08_15_232954_normalize_tag_team_morph_alias',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (106,'2026_08_18_142221_rename_healed_lifecycle_transition',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (107,'2026_09_17_120000_create_activity_log_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (108,'2026_09_22_135205_create_promotions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (109,'2026_09_22_135213_create_promotion_user_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (110,'2026_09_22_141036_add_promotion_id_to_roster_entities_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (111,'2026_09_22_143416_add_promotion_id_to_event_title_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (112,'2026_10_01_162139_enforce_single_current_tag_team_membership_for_wrestlers',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (113,'2026_10_01_190000_scope_unique_active_stable_names_to_promotion',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (114,'2026_10_02_090000_enforce_case_insensitive_unique_user_email',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (115,'2026_10_03_090000_add_missing_foreign_key_and_history_indexes',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (116,'2026_10_03_130704_create_scheduling_slot_locks_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (117,'2026_10_03_131353_widen_lifecycle_transition_effective_at_range',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (118,'2026_10_03_134527_create_failed_jobs_and_job_batches_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (119,'2026_10_03_141520_widen_events_matches_match_number_range',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (120,'2026_10_04_221843_add_timezone_to_promotions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (121,'2026_10_05_005355_add_timezone_to_venues_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (122,'2026_10_05_024420_enforce_single_open_reign_per_title',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (123,'2026_10_05_024424_enforce_unique_match_number_per_event',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (124,'2026_10_05_024426_enforce_unique_referees_and_titles_per_match',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (125,'2026_10_05_154301_enforce_date_order_for_period_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (126,'2026_10_05_160946_enforce_date_order_for_title_reigns',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (127,'2026_10_05_165441_create_stable_name_locks_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (128,'2026_10_05_203355_add_roster_sort_indexes_and_drop_redundant_indexes',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (129,'2026_10_06_024538_create_promotion_invitations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (130,'2026_10_06_024541_move_pending_promotion_invitations_to_promotion_invitations',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (131,'2026_10_06_024544_default_promotion_user_status_to_active',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (132,'2026_10_06_135303_add_expires_at_to_promotion_invitations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (133,'2026_10_06_141721_use_binary_collation_for_promotion_invitation_email',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (134,'2026_10_10_120000_create_record_name_locks_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (135,'2026_10_13_145346_create_event_packs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (136,'2026_10_13_180902_create_event_live_runs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (137,'2026_10_13_211657_create_roster_unavailabilities_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (138,'2026_10_13_211800_create_storyline_planning_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (139,'2026_10_13_212032_create_tournaments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (140,'2026_10_13_212131_create_event_talent_bookings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (141,'2026_10_13_212135_create_event_talent_booking_revisions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (142,'2026_10_13_212140_create_event_budget_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (143,'2026_10_13_212430_create_crew_contacts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (144,'2026_10_13_212912_create_event_publications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (145,'2026_10_13_214906_create_sponsors_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (146,'2026_10_13_215057_create_event_sponsor_commitments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (147,'2026_10_13_215058_create_event_sponsor_commitment_revisions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (148,'2026_10_22_150819_map_storyline_revision_record_types_to_short_names',1);
