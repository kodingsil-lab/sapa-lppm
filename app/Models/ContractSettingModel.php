<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

class ContractSettingModel extends BaseModel
{
    public const SCOPE_PENELITIAN = 'penelitian';
    public const SCOPE_PENGABDIAN = 'pengabdian';
    public const SCOPE_HILIRISASI = 'hilirisasi';
    public const SOURCE_DIKTI = 'hibah_dikti';

    public function __construct()
    {
        parent::__construct();
        if ($this->shouldAutoManageSchema()) {
            $this->ensureTable();
            $this->backfillMissingRequiredFields();
        }
    }

    public static function resolveSourceKeyFromFunding(string $fundingSource): string
    {
        return self::SOURCE_DIKTI;
    }

    public static function normalizeScope(string $scope): string
    {
        $scope = strtolower(trim($scope));
        if ($scope === self::SCOPE_PENGABDIAN) {
            return self::SCOPE_PENGABDIAN;
        }
        if ($scope === self::SCOPE_HILIRISASI) {
            return self::SCOPE_HILIRISASI;
        }

        return self::SCOPE_PENELITIAN;
    }

    public static function resolveScopeFromActivityType(string $activityType): string
    {
        return self::normalizeScope($activityType);
    }

    private function ensureTable(): void
    {
        $pdo = db_pdo();
        $currentYear = (int) date('Y');
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS contract_settings (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                setting_year SMALLINT UNSIGNED NOT NULL DEFAULT ' . $currentYear . ',
                activity_scope VARCHAR(30) NOT NULL DEFAULT "penelitian",
                source_key VARCHAR(40) NOT NULL,
                source_label VARCHAR(100) NOT NULL,
                nomor_kontrak_dikti VARCHAR(255) NOT NULL DEFAULT "",
                nomor_kontrak_lldikti_xv VARCHAR(255) NOT NULL DEFAULT "",
                hari_penandatanganan VARCHAR(40) NOT NULL DEFAULT "",
                tanggal_penandatanganan DATE NULL,
                batas_tanggal_tahap_1 DATE NULL,
                batas_tanggal_tahap_2 DATE NULL,
                batas_upload_tahap_2 DATE NULL,
                batas_waktu_upload_setelah_dana VARCHAR(120) NOT NULL DEFAULT "",
                batas_laporan_akhir DATE NULL,
                persentase_tahap_1 DECIMAL(5,2) NOT NULL DEFAULT 80.00,
                persentase_tahap_2 DECIMAL(5,2) NOT NULL DEFAULT 20.00,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_scope_source_year (activity_scope, source_key, setting_year)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );

        $this->ensureColumn($pdo, 'setting_year', 'ALTER TABLE contract_settings ADD COLUMN setting_year SMALLINT UNSIGNED NOT NULL DEFAULT ' . $currentYear . ' AFTER id');
        $this->ensureColumn($pdo, 'activity_scope', 'ALTER TABLE contract_settings ADD COLUMN activity_scope VARCHAR(30) NOT NULL DEFAULT "penelitian" AFTER setting_year');
        $this->ensureColumn($pdo, 'source_key', 'ALTER TABLE contract_settings ADD COLUMN source_key VARCHAR(40) NOT NULL DEFAULT "hibah_dikti" AFTER id');
        $this->ensureColumn($pdo, 'source_label', 'ALTER TABLE contract_settings ADD COLUMN source_label VARCHAR(100) NOT NULL DEFAULT "Hibah Dikti" AFTER source_key');
        $this->ensureColumn($pdo, 'nomor_kontrak_lldikti_xv', 'ALTER TABLE contract_settings ADD COLUMN nomor_kontrak_lldikti_xv VARCHAR(255) NOT NULL DEFAULT "" AFTER nomor_kontrak_dikti');
        $this->ensureColumn($pdo, 'hari_penandatanganan', 'ALTER TABLE contract_settings ADD COLUMN hari_penandatanganan VARCHAR(40) NOT NULL DEFAULT "" AFTER nomor_kontrak_lldikti_xv');
        $this->ensureColumn($pdo, 'tanggal_penandatanganan', 'ALTER TABLE contract_settings ADD COLUMN tanggal_penandatanganan DATE NULL AFTER hari_penandatanganan');
        $this->ensureColumn($pdo, 'tanggal_mulai_global', 'ALTER TABLE contract_settings ADD COLUMN tanggal_mulai_global DATE NULL AFTER tanggal_penandatanganan');
        $this->ensureColumn($pdo, 'tanggal_selesai_global', 'ALTER TABLE contract_settings ADD COLUMN tanggal_selesai_global DATE NULL AFTER tanggal_mulai_global');

        $idColumn = $pdo->query("SHOW COLUMNS FROM contract_settings LIKE 'id'")->fetch();
        $idType = strtolower((string) ($idColumn['Type'] ?? ''));
        $idExtra = strtolower((string) ($idColumn['Extra'] ?? ''));
        if ($idType !== 'int(10) unsigned' || !str_contains($idExtra, 'auto_increment')) {
            $zeroIdRows = $pdo->query('SELECT COUNT(*) FROM contract_settings WHERE id = 0')->fetchColumn();
            if ((int) $zeroIdRows > 0) {
                $maxId = (int) ($pdo->query('SELECT COALESCE(MAX(id), 0) FROM contract_settings WHERE id <> 0')->fetchColumn() ?: 0);
                $newId = $maxId + 1;
                $updateZeroStmt = $pdo->prepare('UPDATE contract_settings SET id = :new_id WHERE id = 0');
                $updateZeroStmt->execute([':new_id' => $newId]);
            }

            $pdo->exec('ALTER TABLE contract_settings MODIFY COLUMN id INT UNSIGNED NOT NULL AUTO_INCREMENT');
        }

        $oldIdx = $pdo->query("SHOW INDEX FROM contract_settings WHERE Key_name = 'uq_source_key'")->fetch();
        if ($oldIdx !== false) {
            $pdo->exec('ALTER TABLE contract_settings DROP INDEX uq_source_key');
        }

        $oldCompositeIdx = $pdo->query("SHOW INDEX FROM contract_settings WHERE Key_name = 'uq_source_year'")->fetch();
        if ($oldCompositeIdx !== false) {
            $pdo->exec('ALTER TABLE contract_settings DROP INDEX uq_source_year');
        }

        $newIdx = $pdo->query("SHOW INDEX FROM contract_settings WHERE Key_name = 'uq_scope_source_year'")->fetch();
        if ($newIdx === false) {
            $pdo->exec('ALTER TABLE contract_settings ADD UNIQUE KEY uq_scope_source_year (activity_scope, source_key, setting_year)');
        }

        $pdo->exec("UPDATE contract_settings SET activity_scope = 'penelitian' WHERE TRIM(COALESCE(activity_scope, '')) = ''");
        $this->seedDefault($currentYear, self::SCOPE_PENELITIAN, self::SOURCE_DIKTI, 'Hibah Dikti');
    }

    private function ensureColumn(PDO $pdo, string $column, string $ddl): void
    {
        $check = $pdo->query("SHOW COLUMNS FROM contract_settings LIKE " . $pdo->quote($column))->fetch();
        if ($check === false) {
            $pdo->exec($ddl);
        }
    }

    private function seedDefault(int $year, string $scope, string $sourceKey, string $label): void
    {
        $scope = self::normalizeScope($scope);
        $pdo = db_pdo();
        $stmt = $pdo->prepare('SELECT id FROM contract_settings WHERE activity_scope = :activity_scope AND source_key = :source_key AND setting_year = :setting_year LIMIT 1');
        $stmt->execute([
            ':activity_scope' => $scope,
            ':source_key' => $sourceKey,
            ':setting_year' => $year,
        ]);
        if ($stmt->fetch() !== false) {
            return;
        }

        $insert = $pdo->prepare(
            'INSERT INTO contract_settings
            (setting_year, activity_scope, source_key, source_label, nomor_kontrak_dikti, nomor_kontrak_lldikti_xv, hari_penandatanganan, tanggal_penandatanganan, tanggal_mulai_global, tanggal_selesai_global, batas_tanggal_tahap_1, batas_tanggal_tahap_2, batas_upload_tahap_2, batas_waktu_upload_setelah_dana, batas_laporan_akhir, persentase_tahap_1, persentase_tahap_2)
            VALUES
            (:setting_year, :activity_scope, :source_key, :source_label, "", "", "", NULL, NULL, NULL, NULL, NULL, NULL, "30 hari setelah dana diterima", NULL, 80.00, 20.00)'
        );
        $insert->execute([
            ':setting_year' => $year,
            ':activity_scope' => $scope,
            ':source_key' => $sourceKey,
            ':source_label' => $label,
        ]);
    }

    public function getAllBySourceForYear(int $year, string $scope = self::SCOPE_PENELITIAN): array
    {
        $scope = self::normalizeScope($scope);
        $pdo = db_pdo();
        $stmt = $pdo->prepare('SELECT * FROM contract_settings WHERE setting_year = :setting_year AND activity_scope = :activity_scope ORDER BY id ASC');
        $stmt->execute([
            ':setting_year' => $year,
            ':activity_scope' => $scope,
        ]);
        $rows = $stmt->fetchAll() ?: [];

        $result = [
            self::SOURCE_DIKTI => $this->defaultRow($year, $scope, self::SOURCE_DIKTI, 'Hibah Dikti'),
        ];

        foreach ($rows as $row) {
            $key = strtolower(trim((string) ($row['source_key'] ?? '')));
            if ($key === '') {
                continue;
            }
            $result[$key] = array_merge($this->defaultRow($year, $scope, $key, (string) ($row['source_label'] ?? '')), $row);
        }

        return $result;
    }

    private function defaultRow(int $year, string $scope, string $sourceKey, string $label): array
    {
        return [
            'setting_year' => $year,
            'activity_scope' => self::normalizeScope($scope),
            'source_key' => $sourceKey,
            'source_label' => $label,
            'nomor_kontrak_dikti' => '',
            'nomor_kontrak_lldikti_xv' => '',
            'hari_penandatanganan' => '',
            'tanggal_penandatanganan' => null,
            'tanggal_mulai_global' => null,
            'tanggal_selesai_global' => null,
            'batas_tanggal_tahap_1' => null,
            'batas_tanggal_tahap_2' => null,
            'batas_upload_tahap_2' => null,
            'batas_waktu_upload_setelah_dana' => '30 hari setelah dana diterima',
            'batas_laporan_akhir' => null,
            'persentase_tahap_1' => '80.00',
            'persentase_tahap_2' => '20.00',
            'updated_at' => null,
        ];
    }

    private function buildSystemDefaults(int $year, string $scope, string $sourceKey, string $label): array
    {
        $scope = self::normalizeScope($scope);
        $prefix = match ($scope) {
            self::SCOPE_PENGABDIAN => 'PENGABDIAN',
            self::SCOPE_HILIRISASI => 'HILIRISASI',
            default => 'DIKTI',
        };
        return [
            'source_label' => $label,
            'nomor_kontrak_dikti' => 'DUMMY/' . $prefix . '/LPPM/' . $year . '/001',
            'nomor_kontrak_lldikti_xv' => 'DUMMY/LLDIKTI-XV/' . $prefix . '/' . $year . '/001',
            'hari_penandatanganan' => 'Jumat',
            'tanggal_penandatanganan' => $year . '-06-20',
            'tanggal_mulai_global' => $year . '-01-01',
            'tanggal_selesai_global' => $year . '-12-31',
            'batas_tanggal_tahap_1' => $year . '-03-31',
            'batas_tanggal_tahap_2' => $year . '-09-30',
            'batas_upload_tahap_2' => $year . '-08-31',
            'batas_waktu_upload_setelah_dana' => '30 hari setelah dana diterima',
            'batas_laporan_akhir' => $year . '-11-30',
            'persentase_tahap_1' => '80.00',
            'persentase_tahap_2' => '20.00',
        ];
    }

    private function backfillMissingRequiredFields(): void
    {
        $pdo = db_pdo();
        $rows = $pdo->query('SELECT setting_year, activity_scope, source_key, source_label, nomor_kontrak_dikti, nomor_kontrak_lldikti_xv, hari_penandatanganan, tanggal_penandatanganan, tanggal_mulai_global, tanggal_selesai_global, batas_tanggal_tahap_1, batas_tanggal_tahap_2, batas_upload_tahap_2, batas_waktu_upload_setelah_dana, batas_laporan_akhir, persentase_tahap_1, persentase_tahap_2 FROM contract_settings')->fetchAll() ?: [];
        $stmt = $pdo->prepare(
            'UPDATE contract_settings SET
                activity_scope = :activity_scope,
                source_label = :source_label,
                nomor_kontrak_dikti = :nomor_kontrak_dikti,
                nomor_kontrak_lldikti_xv = :nomor_kontrak_lldikti_xv,
                hari_penandatanganan = :hari_penandatanganan,
                tanggal_penandatanganan = :tanggal_penandatanganan,
                tanggal_mulai_global = :tanggal_mulai_global,
                tanggal_selesai_global = :tanggal_selesai_global,
                batas_tanggal_tahap_1 = :batas_tanggal_tahap_1,
                batas_tanggal_tahap_2 = :batas_tanggal_tahap_2,
                batas_upload_tahap_2 = :batas_upload_tahap_2,
                batas_waktu_upload_setelah_dana = :batas_waktu_upload_setelah_dana,
                batas_laporan_akhir = :batas_laporan_akhir,
                persentase_tahap_1 = :persentase_tahap_1,
                persentase_tahap_2 = :persentase_tahap_2
             WHERE setting_year = :setting_year AND activity_scope = :activity_scope AND source_key = :source_key'
        );

        foreach ($rows as $row) {
            $year = (int) ($row['setting_year'] ?? date('Y'));
            $scope = self::normalizeScope((string) ($row['activity_scope'] ?? self::SCOPE_PENELITIAN));
            $sourceKey = strtolower(trim((string) ($row['source_key'] ?? self::SOURCE_DIKTI)));
            $label = trim((string) ($row['source_label'] ?? ''));
            if ($label === '') {
                $label = 'Hibah Dikti';
            }

            $defaults = $this->buildSystemDefaults($year, $scope, $sourceKey, $label);
            $payload = [
                'nomor_kontrak_dikti' => trim((string) ($row['nomor_kontrak_dikti'] ?? '')) !== '' ? (string) $row['nomor_kontrak_dikti'] : $defaults['nomor_kontrak_dikti'],
                'nomor_kontrak_lldikti_xv' => trim((string) ($row['nomor_kontrak_lldikti_xv'] ?? '')) !== '' ? (string) $row['nomor_kontrak_lldikti_xv'] : $defaults['nomor_kontrak_lldikti_xv'],
                'hari_penandatanganan' => trim((string) ($row['hari_penandatanganan'] ?? '')) !== '' ? (string) $row['hari_penandatanganan'] : $defaults['hari_penandatanganan'],
                'tanggal_penandatanganan' => trim((string) ($row['tanggal_penandatanganan'] ?? '')) !== '' ? (string) $row['tanggal_penandatanganan'] : $defaults['tanggal_penandatanganan'],
                'tanggal_mulai_global' => trim((string) ($row['tanggal_mulai_global'] ?? '')) !== '' ? (string) $row['tanggal_mulai_global'] : $defaults['tanggal_mulai_global'],
                'tanggal_selesai_global' => trim((string) ($row['tanggal_selesai_global'] ?? '')) !== '' ? (string) $row['tanggal_selesai_global'] : $defaults['tanggal_selesai_global'],
                'batas_tanggal_tahap_1' => trim((string) ($row['batas_tanggal_tahap_1'] ?? '')) !== '' ? (string) $row['batas_tanggal_tahap_1'] : $defaults['batas_tanggal_tahap_1'],
                'batas_tanggal_tahap_2' => trim((string) ($row['batas_tanggal_tahap_2'] ?? '')) !== '' ? (string) $row['batas_tanggal_tahap_2'] : $defaults['batas_tanggal_tahap_2'],
                'batas_upload_tahap_2' => trim((string) ($row['batas_upload_tahap_2'] ?? '')) !== '' ? (string) $row['batas_upload_tahap_2'] : $defaults['batas_upload_tahap_2'],
                'batas_waktu_upload_setelah_dana' => trim((string) ($row['batas_waktu_upload_setelah_dana'] ?? '')) !== '' ? (string) $row['batas_waktu_upload_setelah_dana'] : $defaults['batas_waktu_upload_setelah_dana'],
                'batas_laporan_akhir' => trim((string) ($row['batas_laporan_akhir'] ?? '')) !== '' ? (string) $row['batas_laporan_akhir'] : $defaults['batas_laporan_akhir'],
                'persentase_tahap_1' => trim((string) ($row['persentase_tahap_1'] ?? '')) !== '' ? (string) $row['persentase_tahap_1'] : $defaults['persentase_tahap_1'],
                'persentase_tahap_2' => trim((string) ($row['persentase_tahap_2'] ?? '')) !== '' ? (string) $row['persentase_tahap_2'] : $defaults['persentase_tahap_2'],
            ];
            $stmt->execute([
                ':setting_year' => $year,
                ':activity_scope' => $scope,
                ':source_key' => $sourceKey,
                ':source_label' => $label,
                ':nomor_kontrak_dikti' => $payload['nomor_kontrak_dikti'],
                ':nomor_kontrak_lldikti_xv' => $payload['nomor_kontrak_lldikti_xv'],
                ':hari_penandatanganan' => $payload['hari_penandatanganan'],
                ':tanggal_penandatanganan' => $payload['tanggal_penandatanganan'],
                ':tanggal_mulai_global' => $payload['tanggal_mulai_global'],
                ':tanggal_selesai_global' => $payload['tanggal_selesai_global'],
                ':batas_tanggal_tahap_1' => $payload['batas_tanggal_tahap_1'],
                ':batas_tanggal_tahap_2' => $payload['batas_tanggal_tahap_2'],
                ':batas_upload_tahap_2' => $payload['batas_upload_tahap_2'],
                ':batas_waktu_upload_setelah_dana' => $payload['batas_waktu_upload_setelah_dana'],
                ':batas_laporan_akhir' => $payload['batas_laporan_akhir'],
                ':persentase_tahap_1' => (float) $payload['persentase_tahap_1'],
                ':persentase_tahap_2' => (float) $payload['persentase_tahap_2'],
            ]);
        }
    }

    public function getYearListSummaryBySource(string $sourceKey, string $scope = self::SCOPE_PENELITIAN): array
    {
        $sourceKey = strtolower(trim($sourceKey));
        $scope = self::normalizeScope($scope);
        if ($sourceKey !== self::SOURCE_DIKTI) {
            return [];
        }

        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'SELECT
                setting_year,
                source_key,
                source_label,
                nomor_kontrak_dikti,
                nomor_kontrak_lldikti_xv,
                hari_penandatanganan,
                tanggal_penandatanganan,
                tanggal_mulai_global,
                tanggal_selesai_global,
                persentase_tahap_1,
                persentase_tahap_2,
                batas_tanggal_tahap_1,
                batas_upload_tahap_2,
                batas_tanggal_tahap_2,
                batas_laporan_akhir,
                batas_waktu_upload_setelah_dana,
                updated_at
            FROM contract_settings
            WHERE source_key = :source_key AND activity_scope = :activity_scope
            ORDER BY setting_year DESC'
        );
        $stmt->execute([
            ':source_key' => $sourceKey,
            ':activity_scope' => $scope,
        ]);

        return $stmt->fetchAll() ?: [];
    }

    public function deleteBySourceAndYear(string $sourceKey, int $year, string $scope = self::SCOPE_PENELITIAN): void
    {
        $sourceKey = strtolower(trim($sourceKey));
        $scope = self::normalizeScope($scope);
        $pdo = db_pdo();
        $stmt = $pdo->prepare('DELETE FROM contract_settings WHERE setting_year = :setting_year AND activity_scope = :activity_scope AND source_key = :source_key');
        $stmt->execute([
            ':setting_year' => $year,
            ':activity_scope' => $scope,
            ':source_key' => $sourceKey,
        ]);
    }

    public function saveBySourceForYear(int $year, string $scope, string $sourceKey, string $sourceLabel, array $data): void
    {
        $scope = self::normalizeScope($scope);
        $sourceKey = strtolower(trim($sourceKey));
        if ($sourceKey === '') {
            throw new InvalidArgumentException('Source key tidak valid.');
        }

        $pdo = db_pdo();
        $payload = [
            ':setting_year' => $year,
            ':activity_scope' => $scope,
            ':source_key' => $sourceKey,
            ':source_label' => $sourceLabel,
            ':nomor_kontrak_dikti' => (string) ($data['nomor_kontrak_dikti'] ?? ''),
            ':nomor_kontrak_lldikti_xv' => (string) ($data['nomor_kontrak_lldikti_xv'] ?? ''),
            ':hari_penandatanganan' => (string) ($data['hari_penandatanganan'] ?? ''),
            ':tanggal_penandatanganan' => (string) ($data['tanggal_penandatanganan'] ?? '') !== '' ? (string) $data['tanggal_penandatanganan'] : null,
            ':tanggal_mulai_global' => (string) ($data['tanggal_mulai_global'] ?? '') !== '' ? (string) $data['tanggal_mulai_global'] : null,
            ':tanggal_selesai_global' => (string) ($data['tanggal_selesai_global'] ?? '') !== '' ? (string) $data['tanggal_selesai_global'] : null,
            ':batas_tanggal_tahap_1' => (string) ($data['batas_tanggal_tahap_1'] ?? '') !== '' ? (string) $data['batas_tanggal_tahap_1'] : null,
            ':batas_tanggal_tahap_2' => (string) ($data['batas_tanggal_tahap_2'] ?? '') !== '' ? (string) $data['batas_tanggal_tahap_2'] : null,
            ':batas_upload_tahap_2' => (string) ($data['batas_upload_tahap_2'] ?? '') !== '' ? (string) $data['batas_upload_tahap_2'] : null,
            ':batas_waktu_upload_setelah_dana' => (string) ($data['batas_waktu_upload_setelah_dana'] ?? ''),
            ':batas_laporan_akhir' => (string) ($data['batas_laporan_akhir'] ?? '') !== '' ? (string) $data['batas_laporan_akhir'] : null,
            ':persentase_tahap_1' => (float) ($data['persentase_tahap_1'] ?? 80),
            ':persentase_tahap_2' => (float) ($data['persentase_tahap_2'] ?? 20),
        ];

        $existingStmt = $pdo->prepare('SELECT id FROM contract_settings WHERE setting_year = :setting_year AND activity_scope = :activity_scope AND source_key = :source_key LIMIT 1');
        $existingStmt->execute([
            ':setting_year' => $year,
            ':activity_scope' => $scope,
            ':source_key' => $sourceKey,
        ]);
        $existingId = $existingStmt->fetchColumn();

        if ($existingId !== false) {
            $updateStmt = $pdo->prepare(
                'UPDATE contract_settings SET
                    activity_scope = :activity_scope,
                    source_label = :source_label,
                    nomor_kontrak_dikti = :nomor_kontrak_dikti,
                    nomor_kontrak_lldikti_xv = :nomor_kontrak_lldikti_xv,
                    hari_penandatanganan = :hari_penandatanganan,
                    tanggal_penandatanganan = :tanggal_penandatanganan,
                    tanggal_mulai_global = :tanggal_mulai_global,
                    tanggal_selesai_global = :tanggal_selesai_global,
                    batas_tanggal_tahap_1 = :batas_tanggal_tahap_1,
                    batas_tanggal_tahap_2 = :batas_tanggal_tahap_2,
                    batas_upload_tahap_2 = :batas_upload_tahap_2,
                    batas_waktu_upload_setelah_dana = :batas_waktu_upload_setelah_dana,
                    batas_laporan_akhir = :batas_laporan_akhir,
                    persentase_tahap_1 = :persentase_tahap_1,
                    persentase_tahap_2 = :persentase_tahap_2
                 WHERE setting_year = :setting_year AND activity_scope = :activity_scope AND source_key = :source_key'
            );
            $updateStmt->execute($payload);
            return;
        }

        $insertStmt = $pdo->prepare(
            'INSERT INTO contract_settings
            (setting_year, activity_scope, source_key, source_label, nomor_kontrak_dikti, nomor_kontrak_lldikti_xv, hari_penandatanganan, tanggal_penandatanganan, tanggal_mulai_global, tanggal_selesai_global, batas_tanggal_tahap_1, batas_tanggal_tahap_2, batas_upload_tahap_2, batas_waktu_upload_setelah_dana, batas_laporan_akhir, persentase_tahap_1, persentase_tahap_2)
            VALUES
            (:setting_year, :activity_scope, :source_key, :source_label, :nomor_kontrak_dikti, :nomor_kontrak_lldikti_xv, :hari_penandatanganan, :tanggal_penandatanganan, :tanggal_mulai_global, :tanggal_selesai_global, :batas_tanggal_tahap_1, :batas_tanggal_tahap_2, :batas_upload_tahap_2, :batas_waktu_upload_setelah_dana, :batas_laporan_akhir, :persentase_tahap_1, :persentase_tahap_2)'
        );
        $insertStmt->execute($payload);
    }

    public function getBySourceAndYear(string $sourceKey, int $year, string $scope = self::SCOPE_PENELITIAN): array
    {
        $scope = self::normalizeScope($scope);
        $all = $this->getAllBySourceForYear($year, $scope);
        $key = strtolower(trim($sourceKey));

        return (array) ($all[$key] ?? $this->defaultRow($year, $scope, $key, ''));
    }

    public function getAvailableYears(): array
    {
        $pdo = db_pdo();
        $stmt = $pdo->query('SELECT DISTINCT setting_year FROM contract_settings ORDER BY setting_year DESC');
        $rows = $stmt->fetchAll() ?: [];
        $years = [];
        foreach ($rows as $row) {
            $year = (int) ($row['setting_year'] ?? 0);
            if ($year > 0) {
                $years[] = $year;
            }
        }

        return $years;
    }
}
