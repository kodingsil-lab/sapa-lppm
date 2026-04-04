CREATE TABLE IF NOT EXISTS surat_tugas_penelitian (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    letter_id BIGINT UNSIGNED NOT NULL,
    activity_type VARCHAR(30) NOT NULL DEFAULT 'penelitian',
    activity_id INT NOT NULL DEFAULT 0,
    penelitian_id INT NOT NULL,
    lokasi_penugasan VARCHAR(255) NOT NULL,
    instansi_tujuan VARCHAR(255) DEFAULT NULL,
    tanggal_mulai DATE NOT NULL,
    tanggal_selesai DATE NOT NULL,
    dasar_penugasan TEXT NOT NULL,
    uraian_tugas TEXT NOT NULL,
    keterangan TEXT DEFAULT NULL,
    file_proposal VARCHAR(255) DEFAULT NULL,
    file_instrumen VARCHAR(255) DEFAULT NULL,
    file_pendukung_lain VARCHAR(255) DEFAULT NULL,
    file_sk VARCHAR(255) DEFAULT NULL,
    nomor_surat VARCHAR(100) DEFAULT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'diajukan',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_surat_tugas_penelitian_letter (letter_id),
    KEY idx_surat_tugas_penelitian_activity (activity_id),
    KEY idx_surat_tugas_penelitian_penelitian (penelitian_id),
    KEY idx_surat_tugas_penelitian_status (status),
    CONSTRAINT fk_surat_tugas_penelitian_letter
        FOREIGN KEY (letter_id) REFERENCES letters (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
