<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';
require_once __DIR__ . '/../Helpers/LetterHelper.php';

class FundingSourceModel extends BaseModel
{
    private const DEFAULT_SOURCES = [
        'penelitian' => ['Hibah Dikti', 'Internal PT', 'Mandiri (Dosen)', 'Lainnya'],
        'pengabdian' => ['Hibah Dikti', 'Internal PT', 'Mandiri (Dosen)', 'Lainnya'],
        'hilirisasi' => ['Hibah Dikti', 'Internal PT', 'Mandiri (Dosen)', 'Lainnya'],
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
            $conditions[] = 'activity_category_code = :category_code';
            $params[':category_code'] = strtolower(trim($categoryCode));
        }
        if ($onlyActive) {
            $conditions[] = 'is_active = 1';
        }

        $sql = 'SELECT id, activity_category_code, code, name, description, is_active, sort_order, created_at, updated_at
                FROM activity_funding_sources';
        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        $sql .= ' ORDER BY activity_category_code ASC, sort_order ASC, name ASC, id ASC';

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
            'SELECT id, activity_category_code, code, name, description, is_active, sort_order, created_at, updated_at
             FROM activity_funding_sources
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function findActiveByCategoryAndName(string $categoryCode, string $name): ?array
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'SELECT id, activity_category_code, code, name, description, is_active, sort_order, created_at, updated_at
             FROM activity_funding_sources
             WHERE activity_category_code = :category_code
               AND name = :name
               AND is_active = 1
             LIMIT 1'
        );
        $stmt->execute([
            ':category_code' => strtolower(trim($categoryCode)),
            ':name' => trim($name),
        ]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function saveFundingSource(array $payload, ?int $id = null): int
    {
        $categoryCode = strtolower($this->readString($payload, 'activity_category_code'));
        $code = $this->readString($payload, 'code');
        $name = $this->readString($payload, 'name');
        $description = $this->readNullableString($payload, 'description');
        $sortOrder = max(1, $this->readInt($payload, 'sort_order', 1));
        $isActive = $this->readInt($payload, 'is_active', 1) === 1 ? 1 : 0;

        if (!in_array($categoryCode, ['penelitian', 'pengabdian', 'hilirisasi'], true)) {
            throw new InvalidArgumentException('Kategori kegiatan untuk sumber dana tidak valid.');
        }
        if ($code === '' || $name === '') {
            throw new InvalidArgumentException('Kode dan nama sumber dana wajib diisi.');
        }

        $pdo = db_pdo();
        if ($id !== null && $id > 0) {
            $stmt = $pdo->prepare(
                'UPDATE activity_funding_sources
                 SET activity_category_code = :activity_category_code,
                     code = :code,
                     name = :name,
                     description = :description,
                     is_active = :is_active,
                     sort_order = :sort_order,
                     updated_at = NOW()
                 WHERE id = :id'
            );
            $stmt->execute([
                ':activity_category_code' => $categoryCode,
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
            'INSERT INTO activity_funding_sources
                (activity_category_code, code, name, description, is_active, sort_order, created_at, updated_at)
             VALUES
                (:activity_category_code, :code, :name, :description, :is_active, :sort_order, NOW(), NOW())'
        );
        $stmt->execute([
            ':activity_category_code' => $categoryCode,
            ':code' => $code,
            ':name' => $name,
            ':description' => $description,
            ':is_active' => $isActive,
            ':sort_order' => $sortOrder,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public function deleteFundingSource(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        $stmt = db_pdo()->prepare('DELETE FROM activity_funding_sources WHERE id = :id');
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() > 0;
    }

    private function ensureTable(): void
    {
        $pdo = db_pdo();
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS activity_funding_sources (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }

    private function seedDefaults(): void
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'INSERT IGNORE INTO activity_funding_sources
                (activity_category_code, code, name, description, is_active, sort_order, created_at, updated_at)
             VALUES
                (:activity_category_code, :code, :name, :description, 1, :sort_order, NOW(), NOW())'
        );

        foreach (self::DEFAULT_SOURCES as $categoryCode => $items) {
            foreach (array_values($items) as $index => $name) {
                $stmt->execute([
                    ':activity_category_code' => $categoryCode,
                    ':code' => $this->slugify($categoryCode . '-' . $name),
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
        return trim($normalized, '-') !== '' ? trim($normalized, '-') : 'funding-source';
    }
}
