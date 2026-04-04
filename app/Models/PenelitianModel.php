<?php

declare(strict_types=1);

require_once __DIR__ . '/ActivityBaseModel.php';

class PenelitianModel extends ActivityBaseModel
{
    protected string $table = 'data_penelitian';

    protected function ensureTable(): void
    {
        $pdo = db_pdo();
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS data_penelitian (
                id INT AUTO_INCREMENT PRIMARY KEY,
                kode_kegiatan VARCHAR(40) NOT NULL,
                judul VARCHAR(255) NOT NULL,
                skema VARCHAR(150) NOT NULL,
                ruang_lingkup VARCHAR(200) NOT NULL DEFAULT "",
                sumber_dana VARCHAR(150) NOT NULL,
                tahun VARCHAR(4) NOT NULL,
                lama_kegiatan VARCHAR(1) NOT NULL DEFAULT "1",
                ketua VARCHAR(150) NOT NULL,
                anggota TEXT NOT NULL,
                lokasi VARCHAR(255) NOT NULL,
                mitra VARCHAR(255) NOT NULL,
                total_dana_disetujui VARCHAR(100) NOT NULL DEFAULT "",
                tanggal_mulai DATE NOT NULL,
                tanggal_selesai DATE NOT NULL,
                status VARCHAR(50) NOT NULL DEFAULT "draft",
                deskripsi TEXT NOT NULL,
                file_proposal VARCHAR(255) NOT NULL DEFAULT "",
                file_instrumen VARCHAR(255) NOT NULL DEFAULT "",
                file_pendukung_lain VARCHAR(255) NOT NULL DEFAULT "",
                created_by INT NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX idx_created_by (created_by),
                INDEX idx_tahun (tahun),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );

        $check = $pdo->query("SHOW COLUMNS FROM data_penelitian LIKE 'ruang_lingkup'")->fetch();
        if ($check === false) {
            $pdo->exec("ALTER TABLE data_penelitian ADD COLUMN ruang_lingkup VARCHAR(200) NOT NULL DEFAULT '' AFTER skema");
        }

        $checkDana = $pdo->query("SHOW COLUMNS FROM data_penelitian LIKE 'total_dana_disetujui'")->fetch();
        if ($checkDana === false) {
            $pdo->exec("ALTER TABLE data_penelitian ADD COLUMN total_dana_disetujui VARCHAR(100) NOT NULL DEFAULT '' AFTER mitra");
        }

        $checkLama = $pdo->query("SHOW COLUMNS FROM data_penelitian LIKE 'lama_kegiatan'")->fetch();
        if ($checkLama === false) {
            $pdo->exec("ALTER TABLE data_penelitian ADD COLUMN lama_kegiatan VARCHAR(1) NOT NULL DEFAULT '1' AFTER tahun");
            $pdo->exec("UPDATE data_penelitian SET lama_kegiatan = CAST(GREATEST(1, TIMESTAMPDIFF(YEAR, tanggal_mulai, tanggal_selesai) + 1) AS CHAR) WHERE COALESCE(tanggal_mulai, '') <> '' AND COALESCE(tanggal_selesai, '') <> ''");
        }

        $checkFileProposal = $pdo->query("SHOW COLUMNS FROM data_penelitian LIKE 'file_proposal'")->fetch();
        if ($checkFileProposal === false) {
            $pdo->exec("ALTER TABLE data_penelitian ADD COLUMN file_proposal VARCHAR(255) NOT NULL DEFAULT '' AFTER deskripsi");
        }
        $checkFileInstrumen = $pdo->query("SHOW COLUMNS FROM data_penelitian LIKE 'file_instrumen'")->fetch();
        if ($checkFileInstrumen === false) {
            $pdo->exec("ALTER TABLE data_penelitian ADD COLUMN file_instrumen VARCHAR(255) NOT NULL DEFAULT '' AFTER file_proposal");
        }
        $checkFilePendukung = $pdo->query("SHOW COLUMNS FROM data_penelitian LIKE 'file_pendukung_lain'")->fetch();
        if ($checkFilePendukung === false) {
            $pdo->exec("ALTER TABLE data_penelitian ADD COLUMN file_pendukung_lain VARCHAR(255) NOT NULL DEFAULT '' AFTER file_instrumen");
        }
    }
}
