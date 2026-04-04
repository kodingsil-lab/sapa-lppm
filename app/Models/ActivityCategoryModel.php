<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';
require_once __DIR__ . '/../Helpers/LetterHelper.php';

class ActivityCategoryModel extends BaseModel
{
    private const DEFAULT_CATEGORIES = [
        ['code' => 'penelitian', 'name' => 'Penelitian'],
        ['code' => 'pengabdian', 'name' => 'Pengabdian Kepada Masyarakat'],
        ['code' => 'hilirisasi', 'name' => 'Hilirisasi'],
    ];

    public function __construct()
    {
        parent::__construct();
        if ($this->shouldAutoManageSchema()) {
            $this->ensureTable();
            $this->seedDefaults();
        }
    }

    public function getAll(): array
    {
        $pdo = db_pdo();
        $stmt = $pdo->query('SELECT id, code, name, created_at, updated_at FROM activity_categories ORDER BY id ASC');

        return $stmt->fetchAll() ?: [];
    }

    private function ensureTable(): void
    {
        $pdo = db_pdo();
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS activity_categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                code VARCHAR(50) NOT NULL,
                name VARCHAR(150) NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                UNIQUE KEY uq_activity_categories_code (code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }

    private function seedDefaults(): void
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'INSERT INTO activity_categories (code, name, created_at, updated_at)
             VALUES (:code, :name, NOW(), NOW())
             ON DUPLICATE KEY UPDATE name = VALUES(name), updated_at = NOW()'
        );

        foreach (self::DEFAULT_CATEGORIES as $item) {
            $stmt->execute([
                ':code' => $item['code'],
                ':name' => $item['name'],
            ]);
        }
    }
}
