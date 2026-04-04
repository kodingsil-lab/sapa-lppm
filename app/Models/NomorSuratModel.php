<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';
require_once __DIR__ . '/../Helpers/LetterHelper.php';

class NomorSuratModel extends BaseModel
{
    public function __construct()
    {
        parent::__construct();
        if ($this->shouldAutoManageSchema()) {
            $this->ensureTable();
        }
    }

    private function ensureTable(): void
    {
        $pdo = db_pdo();
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS nomor_surat (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                jenis_surat CHAR(1) NOT NULL,
                skema VARCHAR(50) NOT NULL,
                nomor_urut INT NOT NULL,
                tahun INT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_nomor_surat_tahun_nomor (tahun, nomor_urut),
                KEY idx_nomor_surat_jenis_tahun (jenis_surat, tahun),
                KEY idx_nomor_surat_skema_tahun (skema, tahun)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }

    public function getLastNumberForYearWithLock(int $year): int
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare('SELECT MAX(nomor_urut) AS last_number FROM nomor_surat WHERE tahun = :tahun FOR UPDATE');
        $stmt->execute([':tahun' => $year]);

        return (int) ($stmt->fetchColumn() ?: 0);
    }

    public function insertNumber(string $jenisSurat, string $skema, int $nomorUrut, int $tahun): int
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'INSERT INTO nomor_surat (jenis_surat, skema, nomor_urut, tahun, created_at)
             VALUES (:jenis_surat, :skema, :nomor_urut, :tahun, NOW())'
        );
        $stmt->execute([
            ':jenis_surat' => $jenisSurat,
            ':skema' => $skema,
            ':nomor_urut' => $nomorUrut,
            ':tahun' => $tahun,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public function getOverviewByYear(int $year): array
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'SELECT
                jenis_surat,
                COUNT(*) AS total,
                MAX(nomor_urut) AS last_nomor
             FROM nomor_surat
             WHERE tahun = :tahun
             GROUP BY jenis_surat'
        );
        $stmt->execute([':tahun' => $year]);

        $rows = $stmt->fetchAll() ?: [];
        $result = [
            'K' => ['total' => 0, 'last_nomor' => 0],
            'I' => ['total' => 0, 'last_nomor' => 0],
            'T' => ['total' => 0, 'last_nomor' => 0],
        ];

        foreach ($rows as $row) {
            $jenis = strtoupper((string) ($row['jenis_surat'] ?? ''));
            if (!isset($result[$jenis])) {
                continue;
            }
            $result[$jenis] = [
                'total' => (int) ($row['total'] ?? 0),
                'last_nomor' => (int) ($row['last_nomor'] ?? 0),
            ];
        }

        return $result;
    }

    public function getRecentByJenis(string $jenisSurat, int $year, int $limit = 20): array
    {
        $code = strtoupper(trim($jenisSurat));
        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'SELECT id, jenis_surat, skema, nomor_urut, tahun, created_at
             FROM nomor_surat
             WHERE jenis_surat = :jenis_surat AND tahun = :tahun
             ORDER BY nomor_urut DESC
             LIMIT :limit_rows'
        );
        $stmt->bindValue(':jenis_surat', $code, PDO::PARAM_STR);
        $stmt->bindValue(':tahun', $year, PDO::PARAM_INT);
        $stmt->bindValue(':limit_rows', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll() ?: [];
    }
}
