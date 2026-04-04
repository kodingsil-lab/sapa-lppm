<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';
require_once __DIR__ . '/../Helpers/LetterHelper.php';

class OutputTypeModel extends BaseModel
{
    private const DEFAULT_OUTPUT_TYPES = [
        [
            'code' => 'artikel_sinta',
            'name' => 'Artikel di jurnal bereputasi nasional (Sinta 1-6)',
            'description' => 'Publikasi artikel pada jurnal nasional bereputasi.',
        ],
        [
            'code' => 'artikel_internasional',
            'name' => 'Artikel di jurnal bereputasi internasional',
            'description' => 'Publikasi artikel pada jurnal internasional bereputasi.',
        ],
        [
            'code' => 'prosiding_nasional',
            'name' => 'Prosiding seminar nasional',
            'description' => 'Luaran prosiding dalam seminar tingkat nasional.',
        ],
        [
            'code' => 'prosiding_internasional',
            'name' => 'Prosiding seminar internasional',
            'description' => 'Luaran prosiding dalam seminar tingkat internasional.',
        ],
        [
            'code' => 'buku_ajar',
            'name' => 'Buku ajar',
            'description' => 'Buku ajar sebagai luaran akademik kegiatan.',
        ],
        [
            'code' => 'hki',
            'name' => 'HKI',
            'description' => 'Hak Kekayaan Intelektual dari hasil kegiatan.',
        ],
        [
            'code' => 'hilirisasi',
            'name' => 'Hilirisasi',
            'description' => 'Luaran berbentuk hilirisasi.',
        ],
        [
            'code' => 'hlr_uji_tkt',
            'name' => 'Bukti hasil pengujian prototype dalam rangka peningkatan TKT minimal satu level dari TKT sebelumnya',
            'description' => 'Bukti uji validasi/penerapan untuk peningkatan TKT sesuai ketentuan program hilirisasi.',
        ],
        [
            'code' => 'hlr_blueprint',
            'name' => 'Dokumen desain (blueprint) pasca pengujian',
            'description' => 'Dokumen blueprint hasil pengembangan setelah proses pengujian.',
        ],
        [
            'code' => 'hlr_poster',
            'name' => 'Poster prototype sesuai ketentuan luaran poster',
            'description' => 'Poster prototype sesuai format dan ketentuan luaran poster.',
        ],
        [
            'code' => 'hlr_video',
            'name' => 'Video proses pengembangan, fungsi, dan implementasi hasil produk prototype (unggah YouTube Lembaga PT)',
            'description' => 'Video proses dan hasil implementasi prototype pada kanal resmi lembaga.',
        ],
        [
            'code' => 'produk_inovasi',
            'name' => 'Produk inovasi',
            'description' => 'Produk inovatif yang dihasilkan dari kegiatan.',
        ],
        [
            'code' => 'laporan_akhir',
            'name' => 'Laporan akhir',
            'description' => 'Dokumen laporan akhir kegiatan.',
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

    public function getAll(): array
    {
        $pdo = db_pdo();
        $stmt = $pdo->query(
            'SELECT id, code, name, description, is_active, sort_order, allow_required, allow_additional, created_at, updated_at
             FROM output_types
             ORDER BY sort_order ASC, name ASC, id ASC'
        );

        return $stmt->fetchAll() ?: [];
    }

    public function getActiveByCategoryCode(string $categoryCode, string $usage = 'all'): array
    {
        $pdo = db_pdo();
        $conditions = [
            'aco.activity_category_code = :category_code',
            'aco.is_active = 1',
            'ot.is_active = 1',
        ];

        if ($usage === 'required') {
            $conditions[] = 'ot.allow_required = 1';
        } elseif ($usage === 'additional') {
            $conditions[] = 'ot.allow_additional = 1';
        }

        $stmt = $pdo->prepare(
            'SELECT
                ot.id,
                ot.code,
                ot.name,
                ot.description,
                ot.is_active,
                ot.sort_order,
                ot.allow_required,
                ot.allow_additional
             FROM output_types ot
             INNER JOIN activity_category_outputs aco ON aco.output_type_id = ot.id
             WHERE ' . implode(' AND ', $conditions) . '
             ORDER BY aco.sort_order ASC, ot.sort_order ASC, ot.name ASC, ot.id ASC'
        );
        $stmt->execute([':category_code' => strtolower(trim($categoryCode))]);

        return $stmt->fetchAll() ?: [];
    }

    public function findByCode(string $code): ?array
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'SELECT id, code, name, description, is_active, sort_order, allow_required, allow_additional, created_at, updated_at
             FROM output_types
             WHERE code = :code
             LIMIT 1'
        );
        $stmt->execute([':code' => trim($code)]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function findById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'SELECT id, code, name, description, is_active, sort_order, allow_required, allow_additional, created_at, updated_at
             FROM output_types
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function saveOutputType(array $payload, ?int $id = null): int
    {
        $code = $this->readString($payload, 'code');
        $name = $this->readString($payload, 'name');
        $description = $this->readNullableString($payload, 'description');
        $sortOrder = max(1, $this->readInt($payload, 'sort_order', 1));
        $isActive = $this->readInt($payload, 'is_active', 1) === 1 ? 1 : 0;
        $allowRequired = $this->readInt($payload, 'allow_required', 1) === 1 ? 1 : 0;
        $allowAdditional = $this->readInt($payload, 'allow_additional', 1) === 1 ? 1 : 0;

        if ($code === '' || $name === '') {
            throw new InvalidArgumentException('Kode dan nama jenis luaran wajib diisi.');
        }

        $pdo = db_pdo();
        if ($id !== null && $id > 0) {
            $stmt = $pdo->prepare(
                'UPDATE output_types
                 SET code = :code,
                     name = :name,
                     description = :description,
                     is_active = :is_active,
                     sort_order = :sort_order,
                     allow_required = :allow_required,
                     allow_additional = :allow_additional,
                     updated_at = NOW()
                 WHERE id = :id'
            );
            $stmt->execute([
                ':code' => $code,
                ':name' => $name,
                ':description' => $description,
                ':is_active' => $isActive,
                ':sort_order' => $sortOrder,
                ':allow_required' => $allowRequired,
                ':allow_additional' => $allowAdditional,
                ':id' => $id,
            ]);

            return $id;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO output_types
                (code, name, description, is_active, sort_order, allow_required, allow_additional, created_at, updated_at)
             VALUES
                (:code, :name, :description, :is_active, :sort_order, :allow_required, :allow_additional, NOW(), NOW())'
        );
        $stmt->execute([
            ':code' => $code,
            ':name' => $name,
            ':description' => $description,
            ':is_active' => $isActive,
            ':sort_order' => $sortOrder,
            ':allow_required' => $allowRequired,
            ':allow_additional' => $allowAdditional,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public function deleteOutputType(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        $pdo = db_pdo();
        $usedStmt = $pdo->prepare('SELECT COUNT(*) FROM activity_outputs WHERE output_type_id = :id');
        $usedStmt->execute([':id' => $id]);
        if ((int) $usedStmt->fetchColumn() > 0) {
            throw new RuntimeException('Jenis luaran tidak dapat dihapus karena sudah dipakai pada data luaran.');
        }

        $pdo->prepare('DELETE FROM activity_category_outputs WHERE output_type_id = :id')->execute([':id' => $id]);
        $stmt = $pdo->prepare('DELETE FROM output_types WHERE id = :id');
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() > 0;
    }

    private function ensureTable(): void
    {
        $pdo = db_pdo();
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS output_types (
                id INT AUTO_INCREMENT PRIMARY KEY,
                code VARCHAR(80) NOT NULL,
                name VARCHAR(255) NOT NULL,
                description TEXT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                sort_order INT NOT NULL DEFAULT 1,
                allow_required TINYINT(1) NOT NULL DEFAULT 1,
                allow_additional TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                UNIQUE KEY uq_output_types_code (code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );

        $columns = [
            'is_active' => "ALTER TABLE output_types ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER description",
            'sort_order' => "ALTER TABLE output_types ADD COLUMN sort_order INT NOT NULL DEFAULT 1 AFTER is_active",
            'allow_required' => "ALTER TABLE output_types ADD COLUMN allow_required TINYINT(1) NOT NULL DEFAULT 1 AFTER sort_order",
            'allow_additional' => "ALTER TABLE output_types ADD COLUMN allow_additional TINYINT(1) NOT NULL DEFAULT 1 AFTER allow_required",
        ];
        foreach ($columns as $column => $sql) {
            $check = $pdo->query("SHOW COLUMNS FROM output_types LIKE " . $pdo->quote($column))->fetch();
            if ($check === false) {
                $pdo->exec($sql);
            }
        }
    }

    private function seedDefaults(): void
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'INSERT IGNORE INTO output_types
                (code, name, description, is_active, sort_order, allow_required, allow_additional, created_at, updated_at)
             VALUES
                (:code, :name, :description, :is_active, :sort_order, :allow_required, :allow_additional, NOW(), NOW())'
        );

        foreach (array_values(self::DEFAULT_OUTPUT_TYPES) as $index => $item) {
            $code = (string) ($item['code'] ?? '');
            $allowRequired = !in_array($code, ['hlr_uji_tkt', 'hlr_blueprint', 'hlr_poster', 'hlr_video'], true) ? 1 : 1;
            $allowAdditional = !in_array($code, ['hlr_uji_tkt', 'hlr_blueprint', 'hlr_poster', 'hlr_video'], true) ? 1 : 0;
            $stmt->execute([
                ':code' => $code,
                ':name' => $item['name'],
                ':description' => $item['description'],
                ':is_active' => 1,
                ':sort_order' => $index + 1,
                ':allow_required' => $allowRequired,
                ':allow_additional' => $allowAdditional,
            ]);
        }
    }
}
