-- SAPA LPPM - Full MySQL Schema
-- Import-ready for phpMyAdmin

SET NAMES utf8mb4;
SET time_zone = '+08:00';

CREATE DATABASE IF NOT EXISTS `sapa_lppm`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `sapa_lppm`;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `activity_logs`;
DROP TABLE IF EXISTS `archives`;
DROP TABLE IF EXISTS `approvals`;
DROP TABLE IF EXISTS `document_templates`;
DROP TABLE IF EXISTS `research_contracts`;
DROP TABLE IF EXISTS `research_projects`;
DROP TABLE IF EXISTS `assignment_letters`;
DROP TABLE IF EXISTS `surat_tugas_penelitian`;
DROP TABLE IF EXISTS `research_permit_letters`;
DROP TABLE IF EXISTS `letters`;
DROP TABLE IF EXISTS `letter_types`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `nidn` VARCHAR(30) DEFAULT NULL,
  `username` VARCHAR(50) NOT NULL,
  `email` VARCHAR(120) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('dosen', 'kepala_lppm', 'admin') NOT NULL DEFAULT 'dosen',
  `unit` VARCHAR(120) DEFAULT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `signature_path` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_username` (`username`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_nidn` (`nidn`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `letter_types` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(20) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_letter_types_code` (`code`),
  KEY `idx_letter_types_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `letters` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `letter_type_id` INT UNSIGNED NOT NULL,
  `letter_number` VARCHAR(100) NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `applicant_id` BIGINT UNSIGNED DEFAULT NULL,
  `destination` VARCHAR(255) DEFAULT NULL,
  `institution` VARCHAR(255) DEFAULT NULL,
  `letter_date` DATE NOT NULL,
  `status` ENUM('draft', 'diajukan', 'approved', 'rejected', 'archived', 'submitted', 'disetujui', 'ditolak', 'perlu_diperbaiki') NOT NULL DEFAULT 'draft',
  `file_pdf` VARCHAR(255) DEFAULT NULL,
  `verification_token` VARCHAR(64) DEFAULT NULL,
  `created_by` BIGINT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_letters_letter_number` (`letter_number`),
  UNIQUE KEY `uq_letters_verification_token` (`verification_token`),
  KEY `idx_letters_type` (`letter_type_id`),
  KEY `idx_letters_applicant` (`applicant_id`),
  KEY `idx_letters_created_by` (`created_by`),
  KEY `idx_letters_date` (`letter_date`),
  KEY `idx_letters_status` (`status`),
  CONSTRAINT `fk_letters_letter_type`
    FOREIGN KEY (`letter_type_id`) REFERENCES `letter_types` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_letters_applicant`
    FOREIGN KEY (`applicant_id`) REFERENCES `users` (`id`)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_letters_created_by`
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `research_permit_letters` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `letter_id` BIGINT UNSIGNED NOT NULL,
  `research_title` VARCHAR(255) NOT NULL,
  `research_location` VARCHAR(255) NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `researcher_name` VARCHAR(150) NOT NULL,
  `institution` VARCHAR(255) DEFAULT NULL,
  `supervisor` VARCHAR(150) DEFAULT NULL,
  `research_scheme` VARCHAR(120) DEFAULT NULL,
  `funding_source` VARCHAR(150) DEFAULT NULL,
  `research_year` YEAR DEFAULT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `unit` VARCHAR(120) DEFAULT NULL,
  `faculty` VARCHAR(150) DEFAULT NULL,
  `purpose` TEXT DEFAULT NULL,
  `destination_position` VARCHAR(120) DEFAULT NULL,
  `address` VARCHAR(255) DEFAULT NULL,
  `city` VARCHAR(120) DEFAULT NULL,
  `attachment_file` VARCHAR(255) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `applicant_email` VARCHAR(120) DEFAULT NULL,
  `members` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_research_permit_letters_letter_id` (`letter_id`),
  KEY `idx_research_permit_dates` (`start_date`, `end_date`),
  CONSTRAINT `fk_research_permit_letter`
    FOREIGN KEY (`letter_id`) REFERENCES `letters` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `assignment_letters` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `letter_id` BIGINT UNSIGNED NOT NULL,
  `activity_name` VARCHAR(255) NOT NULL,
  `location` VARCHAR(255) NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `description` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_assignment_letters_letter_id` (`letter_id`),
  KEY `idx_assignment_dates` (`start_date`, `end_date`),
  CONSTRAINT `fk_assignment_letter`
    FOREIGN KEY (`letter_id`) REFERENCES `letters` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `surat_tugas_penelitian` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `letter_id` BIGINT UNSIGNED NOT NULL,
  `activity_type` VARCHAR(30) NOT NULL DEFAULT 'penelitian',
  `activity_id` INT NOT NULL DEFAULT 0,
  `penelitian_id` INT NOT NULL,
  `lokasi_penugasan` VARCHAR(255) NOT NULL,
  `instansi_tujuan` VARCHAR(255) DEFAULT NULL,
  `tanggal_mulai` DATE NOT NULL,
  `tanggal_selesai` DATE NOT NULL,
  `dasar_penugasan` TEXT NOT NULL,
  `uraian_tugas` TEXT NOT NULL,
  `keterangan` TEXT DEFAULT NULL,
  `file_proposal` VARCHAR(255) DEFAULT NULL,
  `file_instrumen` VARCHAR(255) DEFAULT NULL,
  `file_pendukung_lain` VARCHAR(255) DEFAULT NULL,
  `file_sk` VARCHAR(255) DEFAULT NULL,
  `nomor_surat` VARCHAR(100) DEFAULT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'diajukan',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_surat_tugas_penelitian_letter` (`letter_id`),
  KEY `idx_surat_tugas_penelitian_activity` (`activity_id`),
  KEY `idx_surat_tugas_penelitian_penelitian` (`penelitian_id`),
  KEY `idx_surat_tugas_penelitian_status` (`status`),
  CONSTRAINT `fk_surat_tugas_penelitian_letter`
    FOREIGN KEY (`letter_id`) REFERENCES `letters` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `research_projects` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(50) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `leader_id` BIGINT UNSIGNED NOT NULL,
  `funding_source` VARCHAR(150) DEFAULT NULL,
  `amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `status` ENUM('planning', 'running', 'completed', 'cancelled') NOT NULL DEFAULT 'planning',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_research_projects_code` (`code`),
  KEY `idx_research_projects_leader` (`leader_id`),
  KEY `idx_research_projects_status` (`status`),
  KEY `idx_research_projects_dates` (`start_date`, `end_date`),
  CONSTRAINT `fk_research_projects_leader`
    FOREIGN KEY (`leader_id`) REFERENCES `users` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `research_contracts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` BIGINT UNSIGNED NOT NULL,
  `contract_number` VARCHAR(100) NOT NULL,
  `contract_date` DATE NOT NULL,
  `amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `file_contract` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('draft', 'active', 'expired', 'terminated') NOT NULL DEFAULT 'draft',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_research_contracts_contract_number` (`contract_number`),
  KEY `idx_research_contracts_project` (`project_id`),
  KEY `idx_research_contracts_status` (`status`),
  KEY `idx_research_contracts_dates` (`start_date`, `end_date`),
  CONSTRAINT `fk_research_contracts_project`
    FOREIGN KEY (`project_id`) REFERENCES `research_projects` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `document_templates` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `letter_type_id` INT UNSIGNED NOT NULL,
  `template_name` VARCHAR(150) NOT NULL,
  `template_content` LONGTEXT NOT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_document_templates_name_per_type` (`letter_type_id`, `template_name`),
  KEY `idx_document_templates_active` (`active`),
  CONSTRAINT `fk_document_templates_letter_type`
    FOREIGN KEY (`letter_type_id`) REFERENCES `letter_types` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `approvals` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `letter_id` BIGINT UNSIGNED NOT NULL,
  `approver_id` BIGINT UNSIGNED NOT NULL,
  `approval_status` ENUM('pending', 'approved', 'rejected', 'revision') NOT NULL DEFAULT 'pending',
  `notes` TEXT DEFAULT NULL,
  `approved_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_approvals_letter_approver` (`letter_id`, `approver_id`),
  KEY `idx_approvals_approver` (`approver_id`),
  KEY `idx_approvals_status` (`approval_status`),
  KEY `idx_approvals_approved_at` (`approved_at`),
  CONSTRAINT `fk_approvals_letter`
    FOREIGN KEY (`letter_id`) REFERENCES `letters` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_approvals_approver`
    FOREIGN KEY (`approver_id`) REFERENCES `users` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `archives` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `letter_id` BIGINT UNSIGNED NOT NULL,
  `archive_code` VARCHAR(50) NOT NULL,
  `year` YEAR NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_archives_letter_id` (`letter_id`),
  UNIQUE KEY `uq_archives_archive_code` (`archive_code`),
  KEY `idx_archives_year` (`year`),
  CONSTRAINT `fk_archives_letter`
    FOREIGN KEY (`letter_id`) REFERENCES `letters` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `activity_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED DEFAULT NULL,
  `action` VARCHAR(150) NOT NULL,
  `module` VARCHAR(50) NOT NULL,
  `data_id` BIGINT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_activity_logs_user` (`user_id`),
  KEY `idx_activity_logs_module` (`module`),
  KEY `idx_activity_logs_data_id` (`data_id`),
  KEY `idx_activity_logs_created_at` (`created_at`),
  CONSTRAINT `fk_activity_logs_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `letter_types` (`code`, `name`, `description`) VALUES
('IZIN', 'Surat Izin Penelitian', 'Jenis surat izin untuk pelaksanaan penelitian.'),
('TUGAS', 'Surat Tugas', 'Jenis surat penugasan kegiatan akademik/penelitian.'),
('PENGANTAR', 'Surat Pengantar', 'Jenis surat pengantar resmi dari LPPM.'),
('KONTRAK', 'Kontrak Penelitian', 'Jenis dokumen kontrak penelitian.');
