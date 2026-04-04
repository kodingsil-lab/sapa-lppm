-- Migration: tambah kolom biodata dosen agar profil bisa diedit penuh
-- Aman dijalankan berulang (idempotent) pada MySQL 8+

USE `sapa_lppm`;

ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `nidn` VARCHAR(30) NULL AFTER `name`,
    ADD COLUMN IF NOT EXISTS `username` VARCHAR(50) NULL AFTER `nidn`,
    ADD COLUMN IF NOT EXISTS `nuptk` VARCHAR(30) NULL AFTER `nidn`,
    ADD COLUMN IF NOT EXISTS `faculty` VARCHAR(150) NULL AFTER `role`,
    ADD COLUMN IF NOT EXISTS `study_program` VARCHAR(150) NULL AFTER `faculty`,
    ADD COLUMN IF NOT EXISTS `unit` VARCHAR(120) NULL AFTER `study_program`,
    ADD COLUMN IF NOT EXISTS `phone` VARCHAR(30) NULL AFTER `unit`,
    ADD COLUMN IF NOT EXISTS `gender` ENUM('Laki-laki', 'Perempuan') NULL AFTER `phone`,
    ADD COLUMN IF NOT EXISTS `avatar` VARCHAR(255) NULL AFTER `gender`,
    ADD COLUMN IF NOT EXISTS `signature_path` VARCHAR(255) NULL AFTER `avatar`,
    ADD COLUMN IF NOT EXISTS `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active' AFTER `signature_path`,
    ADD COLUMN IF NOT EXISTS `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

-- Isi username kosong dari email (bagian sebelum @), lalu fallback ke user{id}
UPDATE `users`
SET `username` = LOWER(SUBSTRING_INDEX(`email`, '@', 1))
WHERE (`username` IS NULL OR TRIM(`username`) = '')
  AND `email` IS NOT NULL
  AND TRIM(`email`) <> '';

UPDATE `users`
SET `username` = CONCAT('user', `id`)
WHERE `username` IS NULL OR TRIM(`username`) = '';

-- Jika study_program kosong, isi dari unit agar data lama tetap terbaca baik
UPDATE `users`
SET `study_program` = `unit`
WHERE (`study_program` IS NULL OR TRIM(`study_program`) = '')
  AND `unit` IS NOT NULL
  AND TRIM(`unit`) <> '';

-- Ubah username jadi NOT NULL jika sekarang masih nullable
SET @is_username_nullable := (
    SELECT IS_NULLABLE
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'username'
    LIMIT 1
);
SET @sql_username_not_null := IF(
    @is_username_nullable = 'YES',
    'ALTER TABLE `users` MODIFY COLUMN `username` VARCHAR(50) NOT NULL',
    'SELECT 1'
);
PREPARE stmt1 FROM @sql_username_not_null;
EXECUTE stmt1;
DEALLOCATE PREPARE stmt1;

-- Pastikan unique index username tersedia
SET @has_uq_username := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND INDEX_NAME = 'uq_users_username'
);
SET @sql_uq_username := IF(
    @has_uq_username = 0,
    'ALTER TABLE `users` ADD UNIQUE KEY `uq_users_username` (`username`)',
    'SELECT 1'
);
PREPARE stmt2 FROM @sql_uq_username;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

-- Pastikan unique index email tersedia
SET @has_uq_email := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND INDEX_NAME = 'uq_users_email'
);
SET @sql_uq_email := IF(
    @has_uq_email = 0,
    'ALTER TABLE `users` ADD UNIQUE KEY `uq_users_email` (`email`)',
    'SELECT 1'
);
PREPARE stmt3 FROM @sql_uq_email;
EXECUTE stmt3;
DEALLOCATE PREPARE stmt3;

-- Tambah index pendukung biodata
SET @has_idx_nidn := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND INDEX_NAME = 'idx_users_nidn'
);
SET @sql_idx_nidn := IF(
    @has_idx_nidn = 0,
    'ALTER TABLE `users` ADD KEY `idx_users_nidn` (`nidn`)',
    'SELECT 1'
);
PREPARE stmt4 FROM @sql_idx_nidn;
EXECUTE stmt4;
DEALLOCATE PREPARE stmt4;

SET @has_idx_role := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND INDEX_NAME = 'idx_users_role'
);
SET @sql_idx_role := IF(
    @has_idx_role = 0,
    'ALTER TABLE `users` ADD KEY `idx_users_role` (`role`)',
    'SELECT 1'
);
PREPARE stmt5 FROM @sql_idx_role;
EXECUTE stmt5;
DEALLOCATE PREPARE stmt5;

SET @has_idx_status := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND INDEX_NAME = 'idx_users_status'
);
SET @sql_idx_status := IF(
    @has_idx_status = 0,
    'ALTER TABLE `users` ADD KEY `idx_users_status` (`status`)',
    'SELECT 1'
);
PREPARE stmt6 FROM @sql_idx_status;
EXECUTE stmt6;
DEALLOCATE PREPARE stmt6;
