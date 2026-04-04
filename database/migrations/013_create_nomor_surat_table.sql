-- 013_create_nomor_surat_table.sql
-- Menyediakan tabel tracking nomor surat untuk generator otomatis.

CREATE TABLE IF NOT EXISTS nomor_surat (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  jenis_surat CHAR(1) NOT NULL COMMENT 'K/I/T',
  skema VARCHAR(50) NOT NULL COMMENT 'Kode skema singkat, contoh: PDP, PF, PKM',
  nomor_urut INT NOT NULL,
  tahun INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_nomor_surat_tahun_nomor (tahun, nomor_urut),
  KEY idx_nomor_surat_jenis_tahun (jenis_surat, tahun),
  KEY idx_nomor_surat_skema_tahun (skema, tahun)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

