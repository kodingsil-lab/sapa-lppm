-- 014_create_nomor_surat_setting_table.sql
-- Tabel konfigurasi format nomor surat per jenis surat (K/I/T).

CREATE TABLE IF NOT EXISTS nomor_surat_setting (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  jenis_surat CHAR(1) NOT NULL COMMENT 'K/I/T',
  nama_jenis VARCHAR(30) NOT NULL,
  format_template VARCHAR(255) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_nomor_surat_setting_jenis (jenis_surat)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO nomor_surat_setting (jenis_surat, nama_jenis, format_template, is_active)
VALUES
  ('K', 'Kontrak', '{nomor_urut}/{jenis_surat}/{skema}/LPPM-UNISAP/{bulan_romawi}/{tahun}', 1),
  ('I', 'Izin', '{nomor_urut}/{jenis_surat}/{skema}/LPPM-UNISAP/{bulan_romawi}/{tahun}', 1),
  ('T', 'Tugas', '{nomor_urut}/{jenis_surat}/{skema}/LPPM-UNISAP/{bulan_romawi}/{tahun}', 1)
ON DUPLICATE KEY UPDATE
  nama_jenis = VALUES(nama_jenis),
  format_template = VALUES(format_template),
  is_active = VALUES(is_active);

