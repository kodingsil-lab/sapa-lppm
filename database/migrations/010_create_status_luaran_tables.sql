-- Status Luaran module schema + default seeds

CREATE TABLE IF NOT EXISTS activity_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL,
    name VARCHAR(150) NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_activity_categories_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS output_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(80) NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_output_types_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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

CREATE TABLE IF NOT EXISTS activity_outputs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    activity_type VARCHAR(50) NOT NULL,
    activity_id INT NOT NULL,
    output_type_id INT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'belum',
    evidence_link VARCHAR(500) NULL,
    evidence_notes TEXT NULL,
    evidence_file VARCHAR(255) NULL,
    validated_by INT NULL,
    validated_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_activity_output_unique (activity_type, activity_id, output_type_id),
    INDEX idx_activity_outputs_activity_type (activity_type),
    INDEX idx_activity_outputs_activity_id (activity_id),
    INDEX idx_activity_outputs_output_type_id (output_type_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO activity_categories (code, name, created_at, updated_at)
VALUES
    ('penelitian', 'Penelitian', NOW(), NOW()),
    ('pengabdian', 'Pengabdian Kepada Masyarakat', NOW(), NOW()),
    ('hilirisasi', 'Hilirisasi', NOW(), NOW())
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    updated_at = NOW();

INSERT INTO output_types (code, name, description, created_at, updated_at)
VALUES
    ('artikel_sinta', 'Artikel di jurnal bereputasi nasional (Sinta 1-6)', 'Publikasi artikel pada jurnal nasional bereputasi.', NOW(), NOW()),
    ('artikel_internasional', 'Artikel di jurnal bereputasi internasional', 'Publikasi artikel pada jurnal internasional bereputasi.', NOW(), NOW()),
    ('prosiding_nasional', 'Prosiding seminar nasional', 'Luaran prosiding dalam seminar tingkat nasional.', NOW(), NOW()),
    ('prosiding_internasional', 'Prosiding seminar internasional', 'Luaran prosiding dalam seminar tingkat internasional.', NOW(), NOW()),
    ('buku_ajar', 'Buku ajar', 'Buku ajar sebagai luaran akademik kegiatan.', NOW(), NOW()),
    ('hki', 'HKI', 'Hak Kekayaan Intelektual dari hasil kegiatan.', NOW(), NOW()),
    ('hilirisasi', 'Hilirisasi', 'Luaran berbentuk hilirisasi.', NOW(), NOW()),
    ('produk_inovasi', 'Produk inovasi', 'Produk inovatif yang dihasilkan dari kegiatan.', NOW(), NOW()),
    ('laporan_akhir', 'Laporan akhir', 'Dokumen laporan akhir kegiatan.', NOW(), NOW())
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description),
    updated_at = NOW();

INSERT INTO activity_category_outputs (activity_category_code, output_type_id, is_required, sort_order, is_active, created_at, updated_at)
SELECT 'penelitian', ot.id, 1, map.sort_order, 1, NOW(), NOW()
FROM output_types ot
INNER JOIN (
    SELECT 'artikel_sinta' AS output_code, 1 AS sort_order
    UNION ALL SELECT 'artikel_internasional', 2
    UNION ALL SELECT 'prosiding_nasional', 3
    UNION ALL SELECT 'prosiding_internasional', 4
    UNION ALL SELECT 'buku_ajar', 5
    UNION ALL SELECT 'hki', 6
    UNION ALL SELECT 'hilirisasi', 7
    UNION ALL SELECT 'produk_inovasi', 8
    UNION ALL SELECT 'laporan_akhir', 9
) map ON map.output_code = ot.code
ON DUPLICATE KEY UPDATE
    is_required = VALUES(is_required),
    sort_order = VALUES(sort_order),
    is_active = VALUES(is_active),
    updated_at = NOW();

INSERT INTO activity_category_outputs (activity_category_code, output_type_id, is_required, sort_order, is_active, created_at, updated_at)
SELECT 'pengabdian', ot.id, 1, map.sort_order, 1, NOW(), NOW()
FROM output_types ot
INNER JOIN (
    SELECT 'artikel_sinta' AS output_code, 1 AS sort_order
    UNION ALL SELECT 'artikel_internasional', 2
    UNION ALL SELECT 'prosiding_nasional', 3
    UNION ALL SELECT 'prosiding_internasional', 4
    UNION ALL SELECT 'buku_ajar', 5
    UNION ALL SELECT 'hki', 6
    UNION ALL SELECT 'produk_inovasi', 7
    UNION ALL SELECT 'laporan_akhir', 8
) map ON map.output_code = ot.code
ON DUPLICATE KEY UPDATE
    is_required = VALUES(is_required),
    sort_order = VALUES(sort_order),
    is_active = VALUES(is_active),
    updated_at = NOW();

INSERT INTO activity_category_outputs (activity_category_code, output_type_id, is_required, sort_order, is_active, created_at, updated_at)
SELECT 'hilirisasi', ot.id, 1, map.sort_order, 1, NOW(), NOW()
FROM output_types ot
INNER JOIN (
    SELECT 'hilirisasi' AS output_code, 1 AS sort_order
    UNION ALL SELECT 'produk_inovasi', 2
    UNION ALL SELECT 'hki', 3
    UNION ALL SELECT 'laporan_akhir', 4
) map ON map.output_code = ot.code
ON DUPLICATE KEY UPDATE
    is_required = VALUES(is_required),
    sort_order = VALUES(sort_order),
    is_active = VALUES(is_active),
    updated_at = NOW();
