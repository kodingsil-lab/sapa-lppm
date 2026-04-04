<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';
require_once __DIR__ . '/../Helpers/LetterHelper.php';
require_once __DIR__ . '/ActivityCategoryModel.php';
require_once __DIR__ . '/OutputTypeModel.php';

class ActivityCategoryOutputModel extends BaseModel
{
    private const DEFAULT_MAPPINGS = [
        'penelitian' => [
            'artikel_sinta',
            'artikel_internasional',
            'prosiding_nasional',
            'prosiding_internasional',
            'buku_ajar',
            'hki',
            'hilirisasi',
            'produk_inovasi',
            'laporan_akhir',
        ],
        'pengabdian' => [
            'artikel_sinta',
            'artikel_internasional',
            'prosiding_nasional',
            'prosiding_internasional',
            'buku_ajar',
            'hki',
            'produk_inovasi',
            'laporan_akhir',
        ],
        'hilirisasi' => [
            'hilirisasi',
            'produk_inovasi',
            'hki',
            'laporan_akhir',
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

    public function getOutputsByCategoryCode(string $categoryCode): array
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'SELECT
                aco.id,
                aco.activity_category_code,
                aco.output_type_id,
                aco.is_required,
                aco.sort_order,
                aco.is_active,
                ot.code AS output_code,
                ot.name AS output_name,
                ot.description AS output_description,
                ot.allow_required,
                ot.allow_additional
             FROM activity_category_outputs aco
             INNER JOIN output_types ot ON ot.id = aco.output_type_id
             WHERE aco.activity_category_code = :category_code
               AND aco.is_active = 1
               AND ot.is_active = 1
             ORDER BY aco.sort_order ASC, aco.id ASC'
        );
        $stmt->execute([':category_code' => strtolower(trim($categoryCode))]);

        return $stmt->fetchAll() ?: [];
    }

    public function getCategoryCodesForOutput(int $outputTypeId): array
    {
        if ($outputTypeId <= 0) {
            return [];
        }

        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'SELECT activity_category_code
             FROM activity_category_outputs
             WHERE output_type_id = :output_type_id
             ORDER BY activity_category_code ASC'
        );
        $stmt->execute([':output_type_id' => $outputTypeId]);

        return array_values(array_filter(array_map(
            static fn ($value): string => strtolower(trim((string) $value)),
            $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []
        ), static fn (string $value): bool => $value !== ''));
    }

    public function syncCategoriesForOutput(int $outputTypeId, array $categoryCodes): void
    {
        if ($outputTypeId <= 0) {
            throw new InvalidArgumentException('Jenis luaran tidak valid.');
        }

        $normalizedCodes = [];
        foreach ($categoryCodes as $categoryCode) {
            $normalizedCode = strtolower(trim((string) $categoryCode));
            if ($normalizedCode !== '') {
                $normalizedCodes[] = $normalizedCode;
            }
        }
        $normalizedCodes = array_values(array_unique($normalizedCodes));

        $pdo = db_pdo();
        $pdo->prepare('DELETE FROM activity_category_outputs WHERE output_type_id = :output_type_id')
            ->execute([':output_type_id' => $outputTypeId]);

        if ($normalizedCodes === []) {
            return;
        }

        $insert = $pdo->prepare(
            'INSERT INTO activity_category_outputs
                (activity_category_code, output_type_id, is_required, sort_order, is_active, created_at, updated_at)
             VALUES
                (:activity_category_code, :output_type_id, 1, :sort_order, 1, NOW(), NOW())'
        );

        foreach (array_values($normalizedCodes) as $index => $categoryCode) {
            $insert->execute([
                ':activity_category_code' => $categoryCode,
                ':output_type_id' => $outputTypeId,
                ':sort_order' => $index + 1,
            ]);
        }
    }

    private function ensureTable(): void
    {
        $pdo = db_pdo();
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS activity_category_outputs (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }

    private function seedDefaults(): void
    {
        $activityCategoryModel = new ActivityCategoryModel();
        $outputTypeModel = new OutputTypeModel();
        $categories = $activityCategoryModel->getAll();
        if (empty($categories)) {
            return;
        }

        $outputTypeByCode = [];
        foreach ($outputTypeModel->getAll() as $outputType) {
            $outputTypeByCode[(string) ($outputType['code'] ?? '')] = (int) ($outputType['id'] ?? 0);
        }

        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'INSERT IGNORE INTO activity_category_outputs
                (activity_category_code, output_type_id, is_required, sort_order, is_active, created_at, updated_at)
             VALUES
                (:activity_category_code, :output_type_id, :is_required, :sort_order, :is_active, NOW(), NOW())'
        );

        foreach (self::DEFAULT_MAPPINGS as $categoryCode => $outputCodes) {
            $sortOrder = 1;
            foreach ($outputCodes as $outputCode) {
                $outputTypeId = $outputTypeByCode[$outputCode] ?? 0;
                if ($outputTypeId <= 0) {
                    continue;
                }

                $stmt->execute([
                    ':activity_category_code' => $categoryCode,
                    ':output_type_id' => $outputTypeId,
                    ':is_required' => 1,
                    ':sort_order' => $sortOrder,
                    ':is_active' => 1,
                ]);
                $sortOrder++;
            }
        }
    }
}
