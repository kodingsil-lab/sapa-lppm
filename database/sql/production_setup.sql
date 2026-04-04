CREATE TABLE IF NOT EXISTS activity_member_relations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    activity_type VARCHAR(50) NOT NULL,
    activity_id BIGINT UNSIGNED NOT NULL,
    owner_user_id BIGINT UNSIGNED NOT NULL,
    member_user_id BIGINT UNSIGNED NULL,
    member_name VARCHAR(190) NOT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    INDEX idx_activity_member_lookup (activity_type, activity_id),
    INDEX idx_activity_member_user (member_user_id, activity_type),
    INDEX idx_activity_member_owner (owner_user_id, activity_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS activity_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL,
    name VARCHAR(150) NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_activity_categories_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO activity_categories (code, name, created_at, updated_at)
VALUES
    ('penelitian', 'Penelitian', NOW(), NOW()),
    ('pengabdian', 'Pengabdian Kepada Masyarakat', NOW(), NOW()),
    ('hilirisasi', 'Hilirisasi', NOW(), NOW())
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    updated_at = NOW();

CREATE TABLE IF NOT EXISTS output_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(80) NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_output_types_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE output_types
    ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER description,
    ADD COLUMN IF NOT EXISTS sort_order INT NOT NULL DEFAULT 1 AFTER is_active,
    ADD COLUMN IF NOT EXISTS allow_required TINYINT(1) NOT NULL DEFAULT 1 AFTER sort_order,
    ADD COLUMN IF NOT EXISTS allow_additional TINYINT(1) NOT NULL DEFAULT 1 AFTER allow_required;

INSERT IGNORE INTO output_types
    (code, name, description, is_active, sort_order, allow_required, allow_additional, created_at, updated_at)
VALUES
    ('artikel_sinta', 'Artikel di jurnal bereputasi nasional (Sinta 1-6)', 'Publikasi artikel pada jurnal nasional bereputasi.', 1, 1, 1, 1, NOW(), NOW()),
    ('artikel_internasional', 'Artikel di jurnal bereputasi internasional', 'Publikasi artikel pada jurnal internasional bereputasi.', 1, 2, 1, 1, NOW(), NOW()),
    ('prosiding_nasional', 'Prosiding seminar nasional', 'Luaran prosiding dalam seminar tingkat nasional.', 1, 3, 1, 1, NOW(), NOW()),
    ('prosiding_internasional', 'Prosiding seminar internasional', 'Luaran prosiding dalam seminar tingkat internasional.', 1, 4, 1, 1, NOW(), NOW()),
    ('buku_ajar', 'Buku ajar', 'Buku ajar sebagai luaran akademik kegiatan.', 1, 5, 1, 1, NOW(), NOW()),
    ('hki', 'HKI', 'Hak Kekayaan Intelektual dari hasil kegiatan.', 1, 6, 1, 1, NOW(), NOW()),
    ('hilirisasi', 'Hilirisasi', 'Luaran berbentuk hilirisasi.', 1, 7, 1, 1, NOW(), NOW()),
    ('hlr_uji_tkt', 'Bukti hasil pengujian prototype dalam rangka peningkatan TKT minimal satu level dari TKT sebelumnya', 'Bukti uji validasi/penerapan untuk peningkatan TKT sesuai ketentuan program hilirisasi.', 1, 8, 1, 0, NOW(), NOW()),
    ('hlr_blueprint', 'Dokumen desain (blueprint) pasca pengujian', 'Dokumen blueprint hasil pengembangan setelah proses pengujian.', 1, 9, 1, 0, NOW(), NOW()),
    ('hlr_poster', 'Poster prototype sesuai ketentuan luaran poster', 'Poster prototype sesuai format dan ketentuan luaran poster.', 1, 10, 1, 0, NOW(), NOW()),
    ('hlr_video', 'Video proses pengembangan, fungsi, dan implementasi hasil produk prototype (unggah YouTube Lembaga PT)', 'Video proses dan hasil implementasi prototype pada kanal resmi lembaga.', 1, 11, 1, 0, NOW(), NOW()),
    ('produk_inovasi', 'Produk inovasi', 'Produk inovatif yang dihasilkan dari kegiatan.', 1, 12, 1, 1, NOW(), NOW()),
    ('laporan_akhir', 'Laporan akhir', 'Dokumen laporan akhir kegiatan.', 1, 13, 1, 1, NOW(), NOW());

CREATE TABLE IF NOT EXISTS activity_category_outputs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    activity_category_code VARCHAR(50) NOT NULL,
    output_type_id INT NOT NULL,
    is_required TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 1,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_activity_category_outputs (activity_category_code, output_type_id),
    INDEX idx_aco_category_code (activity_category_code),
    INDEX idx_aco_output_type_id (output_type_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE activity_category_outputs
    ADD COLUMN IF NOT EXISTS is_required TINYINT(1) NOT NULL DEFAULT 1 AFTER output_type_id,
    ADD COLUMN IF NOT EXISTS sort_order INT NOT NULL DEFAULT 1 AFTER is_required,
    ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER sort_order,
    ADD COLUMN IF NOT EXISTS created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER is_active,
    ADD COLUMN IF NOT EXISTS updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER created_at;

INSERT IGNORE INTO activity_category_outputs
    (activity_category_code, output_type_id, is_required, sort_order, is_active, created_at, updated_at)
SELECT 'penelitian', id, 1,
    CASE code
        WHEN 'artikel_sinta' THEN 1
        WHEN 'artikel_internasional' THEN 2
        WHEN 'prosiding_nasional' THEN 3
        WHEN 'prosiding_internasional' THEN 4
        WHEN 'buku_ajar' THEN 5
        WHEN 'hki' THEN 6
        WHEN 'hilirisasi' THEN 7
        WHEN 'produk_inovasi' THEN 8
        WHEN 'laporan_akhir' THEN 9
    END,
    1, NOW(), NOW()
FROM output_types
WHERE code IN ('artikel_sinta','artikel_internasional','prosiding_nasional','prosiding_internasional','buku_ajar','hki','hilirisasi','produk_inovasi','laporan_akhir');

INSERT IGNORE INTO activity_category_outputs
    (activity_category_code, output_type_id, is_required, sort_order, is_active, created_at, updated_at)
SELECT 'pengabdian', id, 1,
    CASE code
        WHEN 'artikel_sinta' THEN 1
        WHEN 'artikel_internasional' THEN 2
        WHEN 'prosiding_nasional' THEN 3
        WHEN 'prosiding_internasional' THEN 4
        WHEN 'buku_ajar' THEN 5
        WHEN 'hki' THEN 6
        WHEN 'produk_inovasi' THEN 7
        WHEN 'laporan_akhir' THEN 8
    END,
    1, NOW(), NOW()
FROM output_types
WHERE code IN ('artikel_sinta','artikel_internasional','prosiding_nasional','prosiding_internasional','buku_ajar','hki','produk_inovasi','laporan_akhir');

INSERT IGNORE INTO activity_category_outputs
    (activity_category_code, output_type_id, is_required, sort_order, is_active, created_at, updated_at)
SELECT 'hilirisasi', id, 1,
    CASE code
        WHEN 'hilirisasi' THEN 1
        WHEN 'produk_inovasi' THEN 2
        WHEN 'hki' THEN 3
        WHEN 'laporan_akhir' THEN 4
    END,
    1, NOW(), NOW()
FROM output_types
WHERE code IN ('hilirisasi','produk_inovasi','hki','laporan_akhir');

CREATE TABLE IF NOT EXISTS activity_schemes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    activity_category_code VARCHAR(50) NOT NULL,
    code VARCHAR(80) NOT NULL,
    name VARCHAR(160) NOT NULL,
    description TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_activity_scheme_code (code),
    INDEX idx_activity_schemes_category (activity_category_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE activity_schemes
    ADD COLUMN IF NOT EXISTS activity_category_code VARCHAR(50) NOT NULL AFTER id,
    ADD COLUMN IF NOT EXISTS code VARCHAR(80) NOT NULL AFTER activity_category_code,
    ADD COLUMN IF NOT EXISTS name VARCHAR(160) NOT NULL AFTER code,
    ADD COLUMN IF NOT EXISTS description TEXT NULL AFTER name,
    ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER description,
    ADD COLUMN IF NOT EXISTS sort_order INT NOT NULL DEFAULT 1 AFTER is_active,
    ADD COLUMN IF NOT EXISTS created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER sort_order,
    ADD COLUMN IF NOT EXISTS updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER created_at;

INSERT IGNORE INTO activity_schemes
    (activity_category_code, code, name, description, is_active, sort_order, created_at, updated_at)
VALUES
    ('penelitian', 'penelitian-dasar', 'Penelitian Dasar', NULL, 1, 1, NOW(), NOW()),
    ('penelitian', 'penelitian-terapan', 'Penelitian Terapan', NULL, 1, 2, NOW(), NOW()),
    ('pengabdian', 'pemberdayaan-berbasis-masyarakat', 'Pemberdayaan Berbasis Masyarakat', NULL, 1, 1, NOW(), NOW()),
    ('pengabdian', 'pemberdayaan-berbasis-kewirausahaan', 'Pemberdayaan Berbasis Kewirausahaan', NULL, 1, 2, NOW(), NOW()),
    ('pengabdian', 'pemberdayaan-berbasis-wilayah', 'Pemberdayaan Berbasis Wilayah', NULL, 1, 3, NOW(), NOW()),
    ('hilirisasi', 'hilirisasi-riset-prioritas', 'Hilirisasi Riset Prioritas', NULL, 1, 1, NOW(), NOW());

CREATE TABLE IF NOT EXISTS activity_scopes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scheme_id INT NOT NULL,
    code VARCHAR(80) NOT NULL,
    name VARCHAR(180) NOT NULL,
    description TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_activity_scope_code (code),
    INDEX idx_activity_scopes_scheme (scheme_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE activity_scopes
    ADD COLUMN IF NOT EXISTS scheme_id INT NOT NULL AFTER id,
    ADD COLUMN IF NOT EXISTS code VARCHAR(80) NOT NULL AFTER scheme_id,
    ADD COLUMN IF NOT EXISTS name VARCHAR(180) NOT NULL AFTER code,
    ADD COLUMN IF NOT EXISTS description TEXT NULL AFTER name,
    ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER description,
    ADD COLUMN IF NOT EXISTS sort_order INT NOT NULL DEFAULT 1 AFTER is_active,
    ADD COLUMN IF NOT EXISTS created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER sort_order,
    ADD COLUMN IF NOT EXISTS updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER created_at;

INSERT IGNORE INTO activity_scopes
    (scheme_id, code, name, description, is_active, sort_order, created_at, updated_at)
SELECT s.id, v.code, v.name, NULL, 1, v.sort_order, NOW(), NOW()
FROM activity_schemes s
JOIN (
    SELECT 'Penelitian Dasar' AS scheme_name, 'penelitian-dasar-penelitian-dosen-pemula-afirmasi-pdp-afirmasi' AS code, 'Penelitian Dosen Pemula Afirmasi (PDP-Afirmasi)' AS name, 1 AS sort_order
    UNION ALL SELECT 'Penelitian Dasar', 'penelitian-dasar-penelitian-dosen-pemula-pdp', 'Penelitian Dosen Pemula (PDP)', 2
    UNION ALL SELECT 'Penelitian Dasar', 'penelitian-dasar-penelitian-fundamental', 'Penelitian Fundamental', 3
    UNION ALL SELECT 'Penelitian Dasar', 'penelitian-dasar-penelitian-kerja-sama-antar-perguruan-tinggi-pkpt', 'Penelitian Kerja Sama antar Perguruan Tinggi (PKPT)', 4
    UNION ALL SELECT 'Penelitian Terapan', 'penelitian-terapan-luaran-prototipe', 'Penelitian Terapan Luaran Prototipe', 1
    UNION ALL SELECT 'Penelitian Terapan', 'penelitian-terapan-luaran-model', 'Penelitian Terapan Luaran Model', 2
    UNION ALL SELECT 'Pemberdayaan Berbasis Masyarakat', 'pemberdayaan-berbasis-masyarakat-pemberdayaan-masyarakat-pemula', 'Pemberdayaan Masyarakat Pemula', 1
    UNION ALL SELECT 'Pemberdayaan Berbasis Masyarakat', 'pemberdayaan-berbasis-masyarakat-pemberdayaan-kemitraan-masyarakat', 'Pemberdayaan Kemitraan Masyarakat', 2
    UNION ALL SELECT 'Pemberdayaan Berbasis Masyarakat', 'pemberdayaan-berbasis-masyarakat-pemberdayaan-masyarakat-oleh-mahasiswa', 'Pemberdayaan Masyarakat oleh Mahasiswa', 3
    UNION ALL SELECT 'Pemberdayaan Berbasis Kewirausahaan', 'pemberdayaan-berbasis-kewirausahaan-pemberdayaan-mitra-usaha-produk-unggulan-daerah', 'Pemberdayaan Mitra Usaha Produk Unggulan Daerah', 1
    UNION ALL SELECT 'Pemberdayaan Berbasis Wilayah', 'pemberdayaan-berbasis-wilayah-pemberdayaan-wilayah', 'Pemberdayaan Wilayah', 1
    UNION ALL SELECT 'Pemberdayaan Berbasis Wilayah', 'pemberdayaan-berbasis-wilayah-pemberdayaan-desa-binaan', 'Pemberdayaan Desa Binaan', 2
    UNION ALL SELECT 'Hilirisasi Riset Prioritas', 'hilirisasi-riset-prioritas-pengujian-model-dan-prototipe', 'Hilirisasi Pengujian Model dan Prototipe', 1
) v ON v.scheme_name = s.name;

CREATE TABLE IF NOT EXISTS activity_funding_sources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    activity_category_code VARCHAR(50) NOT NULL,
    code VARCHAR(80) NOT NULL,
    name VARCHAR(160) NOT NULL,
    description TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_activity_funding_source_code (code),
    INDEX idx_activity_funding_category (activity_category_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE activity_funding_sources
    ADD COLUMN IF NOT EXISTS activity_category_code VARCHAR(50) NOT NULL AFTER id,
    ADD COLUMN IF NOT EXISTS code VARCHAR(80) NOT NULL AFTER activity_category_code,
    ADD COLUMN IF NOT EXISTS name VARCHAR(160) NOT NULL AFTER code,
    ADD COLUMN IF NOT EXISTS description TEXT NULL AFTER name,
    ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER description,
    ADD COLUMN IF NOT EXISTS sort_order INT NOT NULL DEFAULT 1 AFTER is_active,
    ADD COLUMN IF NOT EXISTS created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER sort_order,
    ADD COLUMN IF NOT EXISTS updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER created_at;

INSERT IGNORE INTO activity_funding_sources
    (activity_category_code, code, name, description, is_active, sort_order, created_at, updated_at)
VALUES
    ('penelitian', 'penelitian-hibah-dikti', 'Hibah Dikti', NULL, 1, 1, NOW(), NOW()),
    ('penelitian', 'penelitian-internal-pt', 'Internal PT', NULL, 1, 2, NOW(), NOW()),
    ('penelitian', 'penelitian-mandiri-dosen', 'Mandiri (Dosen)', NULL, 1, 3, NOW(), NOW()),
    ('penelitian', 'penelitian-lainnya', 'Lainnya', NULL, 1, 4, NOW(), NOW()),
    ('pengabdian', 'pengabdian-hibah-dikti', 'Hibah Dikti', NULL, 1, 1, NOW(), NOW()),
    ('pengabdian', 'pengabdian-internal-pt', 'Internal PT', NULL, 1, 2, NOW(), NOW()),
    ('pengabdian', 'pengabdian-mandiri-dosen', 'Mandiri (Dosen)', NULL, 1, 3, NOW(), NOW()),
    ('pengabdian', 'pengabdian-lainnya', 'Lainnya', NULL, 1, 4, NOW(), NOW()),
    ('hilirisasi', 'hilirisasi-hibah-dikti', 'Hibah Dikti', NULL, 1, 1, NOW(), NOW()),
    ('hilirisasi', 'hilirisasi-internal-pt', 'Internal PT', NULL, 1, 2, NOW(), NOW()),
    ('hilirisasi', 'hilirisasi-mandiri-dosen', 'Mandiri (Dosen)', NULL, 1, 3, NOW(), NOW()),
    ('hilirisasi', 'hilirisasi-lainnya', 'Lainnya', NULL, 1, 4, NOW(), NOW());
