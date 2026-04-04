<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';
require_once __DIR__ . '/../Helpers/LetterHelper.php';

class NomorSuratSettingModel extends BaseModel
{
    private const DEFAULT_FORMAT = '{nomor_urut}/{jenis_surat}/{skema}/LPPM-UNISAP/{bulan_romawi}/{tahun}';

    public function __construct()
    {
        parent::__construct();
        if ($this->shouldAutoManageSchema()) {
            $this->ensureTable();
            $this->ensureDefaultRows();
        }
    }

    private function ensureTable(): void
    {
        $pdo = db_pdo();
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS nomor_surat_setting (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                jenis_surat CHAR(1) NOT NULL,
                nama_jenis VARCHAR(30) NOT NULL,
                format_template VARCHAR(255) NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_nomor_surat_setting_jenis (jenis_surat)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }

    private function ensureDefaultRows(): void
    {
        foreach ($this->defaultRows() as $row) {
            $this->upsert($row['jenis_surat'], $row['nama_jenis'], $row['format_template'], $row['is_active']);
        }
    }

    public function getAll(): array
    {
        $pdo = db_pdo();
        $stmt = $pdo->query('SELECT * FROM nomor_surat_setting ORDER BY FIELD(jenis_surat, "K", "I", "T"), id ASC');

        return $stmt->fetchAll() ?: [];
    }

    public function findByJenis(string $jenisSurat): ?array
    {
        $code = strtoupper(trim($jenisSurat));
        $pdo = db_pdo();
        $stmt = $pdo->prepare('SELECT * FROM nomor_surat_setting WHERE jenis_surat = :jenis_surat LIMIT 1');
        $stmt->execute([':jenis_surat' => $code]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function upsert(string $jenisSurat, string $namaJenis, string $formatTemplate, bool $isActive): void
    {
        $code = strtoupper(trim($jenisSurat));
        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'INSERT INTO nomor_surat_setting (jenis_surat, nama_jenis, format_template, is_active, updated_at)
             VALUES (:jenis_surat, :nama_jenis, :format_template, :is_active, NOW())
             ON DUPLICATE KEY UPDATE
                nama_jenis = VALUES(nama_jenis),
                format_template = VALUES(format_template),
                is_active = VALUES(is_active),
                updated_at = NOW()'
        );
        $stmt->execute([
            ':jenis_surat' => $code,
            ':nama_jenis' => trim($namaJenis),
            ':format_template' => trim($formatTemplate) !== '' ? trim($formatTemplate) : self::DEFAULT_FORMAT,
            ':is_active' => $isActive ? 1 : 0,
        ]);
    }

    public function getDefaultFormatTemplate(): string
    {
        return self::DEFAULT_FORMAT;
    }

    private function defaultRows(): array
    {
        return [
            [
                'jenis_surat' => 'K',
                'nama_jenis' => 'Kontrak',
                'format_template' => self::DEFAULT_FORMAT,
                'is_active' => true,
            ],
            [
                'jenis_surat' => 'I',
                'nama_jenis' => 'Izin',
                'format_template' => self::DEFAULT_FORMAT,
                'is_active' => true,
            ],
            [
                'jenis_surat' => 'T',
                'nama_jenis' => 'Tugas',
                'format_template' => self::DEFAULT_FORMAT,
                'is_active' => true,
            ],
        ];
    }
}

