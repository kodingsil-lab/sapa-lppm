<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';
require_once __DIR__ . '/ActivityMemberModel.php';
require_once __DIR__ . '/../Helpers/LetterHelper.php';

abstract class ActivityBaseModel extends BaseModel
{
    protected string $table = '';
    private ?ActivityMemberModel $activityMemberModel = null;

    public function __construct()
    {
        parent::__construct();
        $this->assertSafeTableName($this->table);
        if ($this->shouldAutoManageSchema()) {
            $this->ensureTable();
        }
    }

    abstract protected function ensureTable(): void;

    public function getList(int $userId, array $filters = []): array
    {
        $pdo = db_pdo();
        $params = [':user_id' => $userId];
        $memberExistsSql = $this->memberExistsSql('a');
        $where = [$memberExistsSql !== null
            ? '(a.created_by = :user_id OR ' . $memberExistsSql . ')'
            : 'a.created_by = :user_id'];

        if (!empty($filters['year'])) {
            $where[] = 'a.tahun = :tahun';
            $params[':tahun'] = (string) $filters['year'];
        }

        if (!empty($filters['status'])) {
            $where[] = 'LOWER(a.status) = :status';
            $params[':status'] = strtolower((string) $filters['status']);
        }

        if (!empty($filters['q'])) {
            $where[] = '(a.judul LIKE :q OR a.ketua LIKE :q)';
            $params[':q'] = '%' . trim((string) $filters['q']) . '%';
        }

        $sql = sprintf(
            'SELECT
                a.*,
                CASE WHEN a.created_by = :user_id THEN 1 ELSE 0 END AS _is_owner,
                CASE
                    WHEN a.created_by = :user_id THEN 0
                    ELSE %s
                END AS _is_member_readonly
             FROM %s a
             WHERE %s
             ORDER BY a.id DESC',
            $memberExistsSql !== null ? 'CASE WHEN ' . $memberExistsSql . ' THEN 1 ELSE 0 END' : '0',
            $this->table,
            implode(' AND ', $where)
        );

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    }

    public function getStats(int $userId): array
    {
        $pdo = db_pdo();
        $memberExistsSql = $this->memberExistsSql('a');
        $visibilityWhere = $memberExistsSql !== null
            ? 'a.created_by = :user_id OR ' . $memberExistsSql
            : 'a.created_by = :user_id';
        $stmt = $pdo->prepare(sprintf(
            'SELECT LOWER(a.status) AS status_key, COUNT(DISTINCT a.id) AS total
             FROM %s a
             WHERE %s
             GROUP BY LOWER(a.status)',
            $this->table,
            $visibilityWhere
        ));
        $stmt->execute([':user_id' => $userId]);
        $rows = $stmt->fetchAll() ?: [];

        $stats = [
            'total' => 0,
            'aktif' => 0,
            'selesai' => 0,
            'draft' => 0,
        ];

        foreach ($rows as $row) {
            $status = (string) ($row['status_key'] ?? '');
            $total = (int) ($row['total'] ?? 0);
            $stats['total'] += $total;

            if (in_array($status, ['aktif', 'active'], true)) {
                $stats['aktif'] += $total;
            } elseif (in_array($status, ['selesai', 'done', 'completed'], true)) {
                $stats['selesai'] += $total;
            } else {
                $stats['draft'] += $total;
            }
        }

        return $stats;
    }

    public function findById(int $id, int $userId): ?array
    {
        $pdo = db_pdo();
        $memberExistsSql = $this->memberExistsSql('a');
        $visibilityWhere = $memberExistsSql !== null
            ? '(a.created_by = :user_id OR ' . $memberExistsSql . ')'
            : 'a.created_by = :user_id';
        $stmt = $pdo->prepare(sprintf(
            'SELECT
                a.*,
                CASE WHEN a.created_by = :user_id THEN 1 ELSE 0 END AS _is_owner,
                CASE
                    WHEN a.created_by = :user_id THEN 0
                    ELSE %s
                END AS _is_member_readonly
             FROM %s a
             WHERE a.id = :id
               AND %s
             LIMIT 1',
            $memberExistsSql !== null ? 'CASE WHEN ' . $memberExistsSql . ' THEN 1 ELSE 0 END' : '0',
            $this->table,
            $visibilityWhere
        ));
        $stmt->execute([
            ':id' => $id,
            ':user_id' => $userId,
        ]);

        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        $row['_member_entries'] = $this->activityMemberModel()->getMembersForActivity($this->resolveActivityType(), $id);

        return $row;
    }

    public function getOwnedList(int $userId, array $filters = []): array
    {
        $pdo = db_pdo();
        $where = ['created_by = :user_id'];
        $params = [':user_id' => $userId];

        if (!empty($filters['year'])) {
            $where[] = 'tahun = :tahun';
            $params[':tahun'] = (string) $filters['year'];
        }

        if (!empty($filters['status'])) {
            $where[] = 'LOWER(status) = :status';
            $params[':status'] = strtolower((string) $filters['status']);
        }

        if (!empty($filters['q'])) {
            $where[] = '(judul LIKE :q OR ketua LIKE :q)';
            $params[':q'] = '%' . trim((string) $filters['q']) . '%';
        }

        $stmt = $pdo->prepare(sprintf('SELECT * FROM %s WHERE %s ORDER BY id DESC', $this->table, implode(' AND ', $where)));
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    }

    public function findOwnedById(int $id, int $userId): ?array
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare(sprintf('SELECT * FROM %s WHERE id = :id AND created_by = :user_id LIMIT 1', $this->table));
        $stmt->execute([
            ':id' => $id,
            ':user_id' => $userId,
        ]);

        $row = $stmt->fetch();
        if ($row === false) {
            return null;
        }

        $row['_is_owner'] = 1;
        $row['_is_member_readonly'] = 0;
        $row['_member_entries'] = $this->activityMemberModel()->getMembersForActivity($this->resolveActivityType(), $id);

        return $row;
    }

    public function isOwnedByUser(int $id, int $userId): bool
    {
        if ($id <= 0 || $userId <= 0) {
            return false;
        }

        $pdo = db_pdo();
        $stmt = $pdo->prepare(sprintf('SELECT COUNT(*) FROM %s WHERE id = :id AND created_by = :user_id', $this->table));
        $stmt->execute([
            ':id' => $id,
            ':user_id' => $userId,
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function save(array $data, int $userId, ?int $id = null): int
    {
        $pdo = db_pdo();
        $this->requireKeys($data, ['judul', 'tahun', 'ketua'], 'Simpan kegiatan');
        $status = $this->normalizeEnum(
            $this->readString($data, 'status', 'draft'),
            ['draft', 'aktif', 'active', 'selesai', 'completed', 'done'],
            'draft'
        );
        $payload = [
            ':kode_kegiatan' => '-',
            ':judul' => $this->readString($data, 'judul'),
            ':skema' => $this->readString($data, 'skema'),
            ':ruang_lingkup' => $this->readString($data, 'ruang_lingkup'),
            ':sumber_dana' => $this->readString($data, 'sumber_dana'),
            ':tahun' => $this->readString($data, 'tahun'),
            ':lama_kegiatan' => $this->readString($data, 'lama_kegiatan', '1'),
            ':ketua' => $this->readString($data, 'ketua'),
            ':anggota' => $this->readString($data, 'anggota'),
            ':lokasi' => $this->readString($data, 'lokasi'),
            ':mitra' => $this->readString($data, 'mitra'),
            ':total_dana_disetujui' => $this->readString($data, 'total_dana_disetujui'),
            ':tanggal_mulai' => $this->readString($data, 'tanggal_mulai'),
            ':tanggal_selesai' => $this->readString($data, 'tanggal_selesai'),
            ':status' => $status,
            ':deskripsi' => $this->readString($data, 'deskripsi'),
            ':file_proposal' => $this->readString($data, 'file_proposal'),
            ':file_instrumen' => $this->readString($data, 'file_instrumen'),
            ':file_pendukung_lain' => $this->readString($data, 'file_pendukung_lain'),
            ':created_by' => $userId,
        ];

        if ($id === null) {
            $sql = sprintf(
                'INSERT INTO %s
                (kode_kegiatan, judul, skema, ruang_lingkup, sumber_dana, tahun, lama_kegiatan, ketua, anggota, lokasi, mitra, total_dana_disetujui, tanggal_mulai, tanggal_selesai, status, deskripsi, file_proposal, file_instrumen, file_pendukung_lain, created_by, created_at, updated_at)
                VALUES
                (:kode_kegiatan, :judul, :skema, :ruang_lingkup, :sumber_dana, :tahun, :lama_kegiatan, :ketua, :anggota, :lokasi, :mitra, :total_dana_disetujui, :tanggal_mulai, :tanggal_selesai, :status, :deskripsi, :file_proposal, :file_instrumen, :file_pendukung_lain, :created_by, NOW(), NOW())',
                $this->table
            );
            $stmt = $pdo->prepare($sql);
            $stmt->execute($payload);

            $savedId = (int) $pdo->lastInsertId();
            $this->activityMemberModel()->syncMembers($this->resolveActivityType(), $savedId, $userId, (array) ($data['anggota_members'] ?? []));

            return $savedId;
        }

        $sql = sprintf(
            'UPDATE %s SET
                kode_kegiatan = :kode_kegiatan,
                judul = :judul,
                skema = :skema,
                ruang_lingkup = :ruang_lingkup,
                sumber_dana = :sumber_dana,
                tahun = :tahun,
                lama_kegiatan = :lama_kegiatan,
                ketua = :ketua,
                anggota = :anggota,
                lokasi = :lokasi,
                mitra = :mitra,
                total_dana_disetujui = :total_dana_disetujui,
                tanggal_mulai = :tanggal_mulai,
                tanggal_selesai = :tanggal_selesai,
                status = :status,
                deskripsi = :deskripsi,
                file_proposal = :file_proposal,
                file_instrumen = :file_instrumen,
                file_pendukung_lain = :file_pendukung_lain,
                updated_at = NOW()
             WHERE id = :id AND created_by = :created_by',
            $this->table
        );
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge($payload, [':id' => $id]));
        $this->activityMemberModel()->syncMembers($this->resolveActivityType(), $id, $userId, (array) ($data['anggota_members'] ?? []));

        return $id;
    }

    public function deleteById(int $id, int $userId): bool
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare(sprintf('DELETE FROM %s WHERE id = :id AND created_by = :user_id', $this->table));
        $stmt->execute([
            ':id' => $id,
            ':user_id' => $userId,
        ]);

        if ($stmt->rowCount() > 0) {
            $this->activityMemberModel()->deleteByActivity($this->resolveActivityType(), $id);
        }

        return $stmt->rowCount() > 0;
    }

    public function hasLinkedLetterSubmission(int $id, int $userId, string $activityType): bool
    {
        $activityType = strtolower(trim($activityType));
        if ($id <= 0 || $userId <= 0 || $activityType === '') {
            return false;
        }

        $activityAliases = [$activityType];

        $pdo = db_pdo();

        // 1) Relasi eksplisit surat tugas penelitian/pengabdian.
        $taskAliasParams = [];
        $taskAliasPlaceholders = [];
        foreach ($activityAliases as $index => $aliasType) {
            $key = ':activity_type_' . $index;
            $taskAliasPlaceholders[] = $key;
            $taskAliasParams[$key] = $aliasType;
        }
        $aliasPlaceholders = implode(',', $taskAliasPlaceholders);
        $taskSql =
            'SELECT stp.id
             FROM surat_tugas_penelitian stp
             INNER JOIN letters l ON l.id = stp.letter_id
             WHERE stp.penelitian_id = :activity_id
               AND LOWER(stp.activity_type) IN (' . $aliasPlaceholders . ')
               AND l.applicant_id = :user_id
             LIMIT 1';
        $taskStmt = $pdo->prepare($taskSql);
        $taskParams = array_merge(
            [':activity_id' => $id],
            $taskAliasParams,
            [':user_id' => $userId]
        );
        $taskStmt->execute($taskParams);
        if ($taskStmt->fetchColumn() !== false) {
            return true;
        }

        // 2) Relasi token untuk surat izin/kontrak.
        $permitSqlParts = [];
        $permitParams = [':user_id' => $userId];
        foreach ($activityAliases as $index => $aliasType) {
            $key = ':token' . $index;
            $permitSqlParts[] = 'rp.notes LIKE ' . $key;
            $permitParams[$key] = '%__ACTIVITY_REF__[' . $aliasType . ':' . $id . ']%';
        }
        $permitStmt = $pdo->prepare('SELECT rp.id
             FROM research_permit_letters rp
             INNER JOIN letters l ON l.id = rp.letter_id
             WHERE l.applicant_id = :user_id
               AND (' . implode(' OR ', $permitSqlParts) . ')
             LIMIT 1');
        $permitStmt->execute($permitParams);
        if ($permitStmt->fetchColumn() !== false) {
            return true;
        }

        // 3) Fallback legacy (khusus surat izin/kontrak lama yang belum menyimpan token __ACTIVITY_REF__).
        // Cocokkan berdasarkan judul + tahun + pemilik surat.
        $activityTable = match ($activityType) {
            'penelitian' => 'data_penelitian',
            'pengabdian' => 'data_pengabdian',
            'hilirisasi' => 'data_hilirisasi',
            default => null,
        };
        if ($activityTable !== null) {
            $activityStmt = $pdo->prepare(
                'SELECT judul, tahun
                 FROM ' . $activityTable . '
                 WHERE id = :activity_id AND created_by = :user_id
                 LIMIT 1'
            );
            $activityStmt->execute([
                ':activity_id' => $id,
                ':user_id' => $userId,
            ]);
            $activityRow = $activityStmt->fetch();
            if ($activityRow !== false) {
                $activityTitle = trim((string) ($activityRow['judul'] ?? ''));
                $activityYear = trim((string) ($activityRow['tahun'] ?? ''));

                if ($activityTitle !== '' && $activityYear !== '') {
                    $legacyPermitStmt = $pdo->prepare(
                        'SELECT rp.id
                         FROM research_permit_letters rp
                         INNER JOIN letters l ON l.id = rp.letter_id
                         WHERE l.applicant_id = :user_id
                           AND TRIM(COALESCE(rp.research_title, "")) = :activity_title
                           AND TRIM(COALESCE(CAST(rp.research_year AS CHAR), "")) = :activity_year
                         LIMIT 1'
                    );
                    $legacyPermitStmt->execute([
                        ':user_id' => $userId,
                        ':activity_title' => $activityTitle,
                        ':activity_year' => $activityYear,
                    ]);
                    if ($legacyPermitStmt->fetchColumn() !== false) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function assertSafeTableName(string $table): void
    {
        $normalized = trim($table);
        if ($normalized === '' || preg_match('/^[a-z_][a-z0-9_]*$/i', $normalized) !== 1) {
            throw new RuntimeException('Nama tabel kegiatan tidak valid.');
        }
    }

    private function activityMemberModel(): ActivityMemberModel
    {
        if ($this->activityMemberModel === null) {
            $this->activityMemberModel = new ActivityMemberModel();
        }

        return $this->activityMemberModel;
    }

    private function memberExistsSql(string $activityAlias): ?string
    {
        if (!$this->activityMemberModel()->relationTableAvailable()) {
            return null;
        }

        return 'EXISTS (
            SELECT 1
            FROM activity_member_relations amr
            WHERE amr.activity_type COLLATE utf8mb4_unicode_ci = ' . $this->quoteActivityTypeForSql() . ' COLLATE utf8mb4_unicode_ci
              AND amr.activity_id = ' . $activityAlias . '.id
              AND amr.member_user_id = :user_id
        )';
    }

    private function quoteActivityTypeForSql(): string
    {
        return db_pdo()->quote($this->resolveActivityType());
    }

    private function resolveActivityType(): string
    {
        return match ($this->table) {
            'data_penelitian' => 'penelitian',
            'data_pengabdian' => 'pengabdian',
            'data_hilirisasi' => 'hilirisasi',
            default => '',
        };
    }
}
