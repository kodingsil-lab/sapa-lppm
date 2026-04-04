<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';
require_once __DIR__ . '/../Helpers/LetterHelper.php';
require_once __DIR__ . '/ActivitySchemeModel.php';

class ActivityScopeModel extends BaseModel
{
    private const DEFAULT_SCOPES = [
        'Penelitian Dasar' => [
            'Penelitian Dosen Pemula Afirmasi (PDP-Afirmasi)',
            'Penelitian Dosen Pemula (PDP)',
            'Penelitian Fundamental',
            'Penelitian Kerja Sama antar Perguruan Tinggi (PKPT)',
        ],
        'Penelitian Terapan' => [
            'Penelitian Terapan Luaran Prototipe',
            'Penelitian Terapan Luaran Model',
        ],
        'Pemberdayaan Berbasis Masyarakat' => [
            'Pemberdayaan Masyarakat Pemula',
            'Pemberdayaan Kemitraan Masyarakat',
            'Pemberdayaan Masyarakat oleh Mahasiswa',
        ],
        'Pemberdayaan Berbasis Kewirausahaan' => [
            'Pemberdayaan Mitra Usaha Produk Unggulan Daerah',
        ],
        'Pemberdayaan Berbasis Wilayah' => [
            'Pemberdayaan Wilayah',
            'Pemberdayaan Desa Binaan',
        ],
        'Hilirisasi Riset Prioritas' => [
            'Hilirisasi Pengujian Model dan Prototipe',
        ],
    ];

    public function __construct()
    {
        parent::__construct();
        if ($this->shouldAutoManageSchema()) {
            $this->ensureTable();
            $this->seedDefaults();
        }
    }

    public function getAll(?string $categoryCode = null, bool $onlyActive = false): array
    {
        $pdo = db_pdo();
        $conditions = [];
        $params = [];

        if ($categoryCode !== null && trim($categoryCode) !== '') {
            $conditions[] = 's.activity_category_code = :category_code';
            $params[':category_code'] = strtolower(trim($categoryCode));
        }
        if ($onlyActive) {
            $conditions[] = 'sc.is_active = 1';
            $conditions[] = 's.is_active = 1';
        }

        $sql = 'SELECT
                    sc.id,
                    sc.scheme_id,
                    sc.code,
                    sc.name,
                    sc.description,
                    sc.is_active,
                    sc.sort_order,
                    sc.created_at,
                    sc.updated_at,
                    s.activity_category_code,
                    s.name AS scheme_name
                FROM activity_scopes sc
                INNER JOIN activity_schemes s ON s.id = sc.scheme_id';
        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        $sql .= ' ORDER BY s.activity_category_code ASC, s.sort_order ASC, sc.sort_order ASC, sc.name ASC, sc.id ASC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    }

    public function findById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'SELECT
                sc.id,
                sc.scheme_id,
                sc.code,
                sc.name,
                sc.description,
                sc.is_active,
                sc.sort_order,
                sc.created_at,
                sc.updated_at,
                s.activity_category_code,
                s.name AS scheme_name
             FROM activity_scopes sc
             INNER JOIN activity_schemes s ON s.id = sc.scheme_id
             WHERE sc.id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function getActiveGroupedBySchemeName(string $categoryCode): array
    {
        $rows = $this->getAll($categoryCode, true);
        $result = [];
        foreach ($rows as $row) {
            $schemeName = trim((string) ($row['scheme_name'] ?? ''));
            $scopeName = trim((string) ($row['name'] ?? ''));
            if ($schemeName === '' || $scopeName === '') {
                continue;
            }
            $result[$schemeName][] = $scopeName;
        }

        foreach ($result as $schemeName => $items) {
            $result[$schemeName] = array_values(array_unique($items));
        }

        return $result;
    }

    public function findActiveByCategoryAndSchemeAndName(string $categoryCode, string $schemeName, string $scopeName): ?array
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'SELECT
                sc.id,
                sc.scheme_id,
                sc.code,
                sc.name,
                sc.description,
                sc.is_active,
                sc.sort_order,
                s.activity_category_code,
                s.name AS scheme_name
             FROM activity_scopes sc
             INNER JOIN activity_schemes s ON s.id = sc.scheme_id
             WHERE s.activity_category_code = :category_code
               AND s.name = :scheme_name
               AND sc.name = :scope_name
               AND s.is_active = 1
               AND sc.is_active = 1
             LIMIT 1'
        );
        $stmt->execute([
            ':category_code' => strtolower(trim($categoryCode)),
            ':scheme_name' => trim($schemeName),
            ':scope_name' => trim($scopeName),
        ]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function saveScope(array $payload, ?int $id = null): int
    {
        $schemeId = $this->readInt($payload, 'scheme_id');
        $code = $this->readString($payload, 'code');
        $name = $this->readString($payload, 'name');
        $description = $this->readNullableString($payload, 'description');
        $sortOrder = max(1, $this->readInt($payload, 'sort_order', 1));
        $isActive = $this->readInt($payload, 'is_active', 1) === 1 ? 1 : 0;

        if ($schemeId <= 0 || $code === '' || $name === '') {
            throw new InvalidArgumentException('Skema, kode, dan nama ruang lingkup wajib diisi.');
        }

        $pdo = db_pdo();
        if ($id !== null && $id > 0) {
            $stmt = $pdo->prepare(
                'UPDATE activity_scopes
                 SET scheme_id = :scheme_id,
                     code = :code,
                     name = :name,
                     description = :description,
                     is_active = :is_active,
                     sort_order = :sort_order,
                     updated_at = NOW()
                 WHERE id = :id'
            );
            $stmt->execute([
                ':scheme_id' => $schemeId,
                ':code' => $code,
                ':name' => $name,
                ':description' => $description,
                ':is_active' => $isActive,
                ':sort_order' => $sortOrder,
                ':id' => $id,
            ]);

            return $id;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO activity_scopes
                (scheme_id, code, name, description, is_active, sort_order, created_at, updated_at)
             VALUES
                (:scheme_id, :code, :name, :description, :is_active, :sort_order, NOW(), NOW())'
        );
        $stmt->execute([
            ':scheme_id' => $schemeId,
            ':code' => $code,
            ':name' => $name,
            ':description' => $description,
            ':is_active' => $isActive,
            ':sort_order' => $sortOrder,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public function deleteScope(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        $pdo = db_pdo();
        $stmt = $pdo->prepare('DELETE FROM activity_scopes WHERE id = :id');
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() > 0;
    }

    private function ensureTable(): void
    {
        $pdo = db_pdo();
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS activity_scopes (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }

    private function seedDefaults(): void
    {
        $schemeModel = new ActivitySchemeModel();
        $schemesByName = [];
        foreach ($schemeModel->getAll() as $scheme) {
            $schemeName = trim((string) ($scheme['name'] ?? ''));
            if ($schemeName !== '') {
                $schemesByName[$schemeName] = (int) ($scheme['id'] ?? 0);
            }
        }

        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'INSERT IGNORE INTO activity_scopes
                (scheme_id, code, name, description, is_active, sort_order, created_at, updated_at)
             VALUES
                (:scheme_id, :code, :name, :description, 1, :sort_order, NOW(), NOW())'
        );

        foreach (self::DEFAULT_SCOPES as $schemeName => $items) {
            $schemeId = (int) ($schemesByName[$schemeName] ?? 0);
            if ($schemeId <= 0) {
                continue;
            }

            foreach (array_values($items) as $index => $name) {
                $stmt->execute([
                    ':scheme_id' => $schemeId,
                    ':code' => $this->slugify($schemeName . '-' . $name),
                    ':name' => $name,
                    ':description' => null,
                    ':sort_order' => $index + 1,
                ]);
            }
        }
    }

    private function slugify(string $value): string
    {
        $normalized = strtolower(trim($value));
        $normalized = preg_replace('/[^a-z0-9]+/i', '-', $normalized) ?? $normalized;
        return trim($normalized, '-') !== '' ? trim($normalized, '-') : 'scope';
    }
}
