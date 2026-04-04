<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';
require_once __DIR__ . '/ActivityMemberModel.php';
require_once __DIR__ . '/../Helpers/LetterHelper.php';

class LetterModel extends BaseModel
{
    private ?bool $hasLetterTypeSlugColumn = null;
    private ActivityMemberModel $activityMemberModel;
    private const CREATE_REQUIRED_FIELDS = [
        'letter_type_id',
        'subject',
        'applicant_id',
        'destination',
        'institution',
        'letter_date',
        'created_by',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->activityMemberModel = new ActivityMemberModel();
    }

    public function getById(int $id): ?array
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare('SELECT * FROM letters WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch();

        return $data !== false ? $data : null;
    }

    public function getFirstUserId(): ?int
    {
        $pdo = db_pdo();
        $stmt = $pdo->query('SELECT id FROM users ORDER BY id ASC LIMIT 1');
        $id = $stmt->fetchColumn();
        if ($id === false) {
            return null;
        }

        return (int) $id;
    }

    public function userExists(int $userId): bool
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE id = :id');
        $stmt->execute([':id' => $userId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function create(array $data): int
    {
        $this->requireKeys($data, self::CREATE_REQUIRED_FIELDS, 'Create letter');
        $pdo = db_pdo();

        $letterTypeSlug = $this->resolveLetterTypeSlug(
            isset($data['letter_type_slug']) ? (string) $data['letter_type_slug'] : null,
            $this->readInt($data, 'letter_type_id'),
            $this->readString($data, 'subject')
        );
        $params = [
            ':letter_type_id' => $this->readInt($data, 'letter_type_id'),
            ':letter_number' => $this->readNullableString($data, 'letter_number'),
            ':subject' => $this->readString($data, 'subject'),
            ':applicant_id' => $this->readInt($data, 'applicant_id'),
            ':destination' => $this->readString($data, 'destination'),
            ':institution' => $this->readString($data, 'institution'),
            ':letter_date' => $this->readString($data, 'letter_date'),
            ':status' => $this->normalizeEnum(
                $this->readString($data, 'status', 'draft'),
                ['draft', 'diajukan', 'submitted', 'diverifikasi', 'approved', 'disetujui', 'rejected', 'ditolak', 'perlu_diperbaiki', 'selesai', 'surat_terbit', 'terbit'],
                'draft'
            ),
            ':file_pdf' => $this->readNullableString($data, 'file_pdf'),
            ':created_by' => $this->readInt($data, 'created_by'),
        ];

        if ($this->hasLetterTypeSlugColumn()) {
            $stmt = $pdo->prepare(
                'INSERT INTO letters
                (letter_type_id, letter_type_slug, letter_number, subject, applicant_id, destination, institution, letter_date, status, file_pdf, created_by, created_at, updated_at)
                VALUES
                (:letter_type_id, :letter_type_slug, :letter_number, :subject, :applicant_id, :destination, :institution, :letter_date, :status, :file_pdf, :created_by, NOW(), NOW())'
            );
            $params[':letter_type_slug'] = $letterTypeSlug;
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO letters
                (letter_type_id, letter_number, subject, applicant_id, destination, institution, letter_date, status, file_pdf, created_by, created_at, updated_at)
                VALUES
                (:letter_type_id, :letter_number, :subject, :applicant_id, :destination, :institution, :letter_date, :status, :file_pdf, :created_by, NOW(), NOW())'
            );
        }

        $stmt->execute($params);

        return (int) $pdo->lastInsertId();
    }

    public function updateById(int $id, array $data): void
    {
        $this->requireKeys($data, ['subject', 'applicant_id', 'destination', 'institution', 'letter_date', 'status', 'created_by'], 'Update letter');
        $pdo = db_pdo();
        $params = [
            ':id' => $id,
            ':subject' => $this->readString($data, 'subject'),
            ':applicant_id' => $this->readInt($data, 'applicant_id'),
            ':destination' => $this->readString($data, 'destination'),
            ':institution' => $this->readString($data, 'institution'),
            ':letter_date' => $this->readString($data, 'letter_date'),
            ':status' => $this->normalizeEnum(
                $this->readString($data, 'status', 'draft'),
                ['draft', 'diajukan', 'submitted', 'diverifikasi', 'approved', 'disetujui', 'rejected', 'ditolak', 'perlu_diperbaiki', 'selesai', 'surat_terbit', 'terbit'],
                'draft'
            ),
            ':created_by' => $this->readInt($data, 'created_by'),
        ];

        if ($this->hasLetterTypeSlugColumn()) {
            $stmt = $pdo->prepare(
                'UPDATE letters
                 SET subject = :subject,
                     applicant_id = :applicant_id,
                     destination = :destination,
                     institution = :institution,
                     letter_date = :letter_date,
                     letter_type_slug = COALESCE(:letter_type_slug, letter_type_slug),
                     status = :status,
                     created_by = :created_by,
                     updated_at = NOW()
                 WHERE id = :id'
            );
            $params[':letter_type_slug'] = isset($data['letter_type_slug']) ? (string) $data['letter_type_slug'] : null;
        } else {
            $stmt = $pdo->prepare(
                'UPDATE letters
                 SET subject = :subject,
                     applicant_id = :applicant_id,
                     destination = :destination,
                     institution = :institution,
                     letter_date = :letter_date,
                     status = :status,
                     created_by = :created_by,
                     updated_at = NOW()
                 WHERE id = :id'
            );
        }

        $stmt->execute($params);
    }

    public function getLetterTypeIdByCode(string $code): ?int
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare('SELECT id FROM letter_types WHERE code = :code LIMIT 1');
        $stmt->execute([':code' => strtoupper($code)]);
        $id = $stmt->fetchColumn();

        return $id !== false ? (int) $id : null;
    }

    public function getByIdWithDetails(int $id): ?array
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'SELECT
                l.*,
                lt.code AS letter_type_code,
                lt.name AS letter_type_name,
                u.name AS applicant_name,
                u.nuptk AS applicant_nuptk,
                u.nidn AS applicant_nidn,
                rp.research_title,
                rp.research_location,
                rp.start_date AS research_start_date,
                rp.end_date AS research_end_date,
                rp.researcher_name,
                rp.institution AS research_institution,
                rp.supervisor,
                rp.research_scheme,
                rp.research_year,
                rp.faculty AS research_faculty,
                rp.unit AS research_unit,
                rp.purpose AS research_purpose,
                rp.destination_position,
                rp.address AS research_address,
                rp.city AS research_city,
                rp.members AS research_members,
                rp.notes AS research_notes,
                stp.activity_type AS task_activity_type,
                COALESCE(NULLIF(stp.activity_id, 0), stp.penelitian_id) AS task_activity_id,
                stp.lokasi_penugasan AS task_location,
                stp.instansi_tujuan AS task_institution,
                stp.tanggal_mulai AS task_start_date,
                stp.tanggal_selesai AS task_end_date,
                stp.dasar_penugasan AS task_assignment_basis,
                stp.uraian_tugas AS task_description,
                stp.keterangan AS task_notes,
                stp.file_proposal AS task_file_proposal,
                stp.file_instrumen AS task_file_instrumen,
                stp.file_pendukung_lain AS task_file_pendukung_lain,
                stp.file_sk AS task_file_sk,
                COALESCE(
                    NULLIF(dp.judul, ""),
                    NULLIF(dpg.judul, ""),
                    NULLIF(dh.judul, ""),
                    NULLIF(rp.research_title, ""),
                    "-"
                ) AS task_title,
                COALESCE(
                    NULLIF(dp.ruang_lingkup, ""),
                    NULLIF(dpg.ruang_lingkup, ""),
                    NULLIF(dh.ruang_lingkup, ""),
                    NULLIF(dp.skema, ""),
                    NULLIF(dpg.skema, ""),
                    NULLIF(dh.skema, ""),
                    NULLIF(rp.research_scheme, ""),
                    "-"
                ) AS task_scheme,
                COALESCE(
                    NULLIF(dp.sumber_dana, ""),
                    NULLIF(dpg.sumber_dana, ""),
                    NULLIF(dh.sumber_dana, ""),
                    NULLIF(rp.funding_source, ""),
                    "-"
                ) AS task_funding_source,
                COALESCE(
                    NULLIF(CAST(dp.tahun AS CHAR), ""),
                    NULLIF(CAST(dpg.tahun AS CHAR), ""),
                    NULLIF(CAST(dh.tahun AS CHAR), ""),
                    NULLIF(CAST(rp.research_year AS CHAR), ""),
                    "-"
                ) AS task_year,
                COALESCE(
                    NULLIF(dp.lokasi, ""),
                    NULLIF(dpg.lokasi, ""),
                    NULLIF(dh.lokasi, ""),
                    NULLIF(stp.lokasi_penugasan, ""),
                    NULLIF(rp.research_location, ""),
                    "-"
                ) AS task_research_location,
                COALESCE(
                    NULLIF(dp.ketua, ""),
                    NULLIF(dpg.ketua, ""),
                    NULLIF(dh.ketua, ""),
                    NULLIF(rp.researcher_name, ""),
                    NULLIF(u.name, ""),
                    "-"
                ) AS task_leader_name,
                COALESCE(
                    NULLIF(dp.anggota, ""),
                    NULLIF(dpg.anggota, ""),
                    NULLIF(dh.anggota, ""),
                    NULLIF(rp.members, ""),
                    ""
                ) AS task_members,
                al.activity_name,
                al.location AS assignment_location,
                al.start_date AS assignment_start_date,
                al.end_date AS assignment_end_date,
                al.description AS assignment_description
            FROM letters l
            INNER JOIN letter_types lt ON lt.id = l.letter_type_id
            LEFT JOIN users u ON u.id = l.applicant_id
            LEFT JOIN research_permit_letters rp ON rp.letter_id = l.id
            LEFT JOIN surat_tugas_penelitian stp ON stp.letter_id = l.id
            LEFT JOIN data_penelitian dp ON dp.id = COALESCE(NULLIF(stp.activity_id, 0), stp.penelitian_id) AND LOWER(TRIM(stp.activity_type)) = "penelitian"
            LEFT JOIN data_pengabdian dpg ON dpg.id = COALESCE(NULLIF(stp.activity_id, 0), stp.penelitian_id) AND LOWER(TRIM(stp.activity_type)) = "pengabdian"
            LEFT JOIN data_hilirisasi dh ON dh.id = COALESCE(NULLIF(stp.activity_id, 0), stp.penelitian_id) AND LOWER(TRIM(stp.activity_type)) = "hilirisasi"
            LEFT JOIN assignment_letters al ON al.letter_id = l.id
            WHERE l.id = :id
            LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch();

        return $data !== false ? $data : null;
    }

    public function getByIdForDetail(int $id): ?array
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'SELECT
                l.*,
                lt.code AS letter_type_code,
                lt.name AS letter_type_name,
                u.name AS applicant_name,
                u.nidn AS applicant_nidn,
                u.email AS applicant_email,
                u.phone AS applicant_phone,
                u.unit AS applicant_unit
            FROM letters l
            INNER JOIN letter_types lt ON lt.id = l.letter_type_id
            LEFT JOIN users u ON u.id = l.applicant_id
            WHERE l.id = :id
            LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function updateFilePdf(int $id, string $filePath): void
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare('UPDATE letters SET file_pdf = :file_pdf, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            ':id' => $id,
            ':file_pdf' => $filePath,
        ]);
    }

    public function updateLetterNumber(int $id, ?string $letterNumber): void
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare('UPDATE letters SET letter_number = :letter_number, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            ':id' => $id,
            ':letter_number' => $letterNumber,
        ]);
    }

    private function resolveLetterTypeSlug(?string $providedSlug, int $letterTypeId, string $subject): string
    {
        $provided = strtolower(trim((string) $providedSlug));
        if ($provided !== '') {
            return $provided;
        }

        $typeCode = strtoupper(trim((string) $this->getLetterTypeCodeById($letterTypeId)));
        $subjectLower = strtolower($subject);
        $scope = str_contains($subjectLower, 'pengabdian')
            ? 'pengabdian'
            : (str_contains($subjectLower, 'hilirisasi') ? 'hilirisasi' : 'penelitian');

        $kind = match ($typeCode) {
            'IZIN' => 'izin',
            'TUGAS' => 'tugas',
            'KONTRAK' => 'kontrak',
            'PENGANTAR' => str_contains($subjectLower, 'tugas')
                ? 'tugas'
                : (str_contains($subjectLower, 'kontrak') ? 'kontrak' : 'izin'),
            default => str_contains($subjectLower, 'tugas')
                ? 'tugas'
                : (str_contains($subjectLower, 'kontrak') ? 'kontrak' : 'izin'),
        };

        return 'surat_' . $kind . '_' . $scope;
    }

    private function getLetterTypeCodeById(int $letterTypeId): ?string
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare('SELECT code FROM letter_types WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $letterTypeId]);
        $code = $stmt->fetchColumn();

        return $code !== false ? (string) $code : null;
    }

    private function hasLetterTypeSlugColumn(): bool
    {
        if ($this->hasLetterTypeSlugColumn !== null) {
            return $this->hasLetterTypeSlugColumn;
        }

        $pdo = db_pdo();
        $stmt = $pdo->query("SHOW COLUMNS FROM letters LIKE 'letter_type_slug'");
        $this->hasLetterTypeSlugColumn = $stmt !== false && $stmt->fetch() !== false;

        return $this->hasLetterTypeSlugColumn;
    }

    public function setVerificationToken(int $id, string $token): void
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare('UPDATE letters SET verification_token = :token, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            ':id' => $id,
            ':token' => $token,
        ]);
    }

    public function ensureVerificationTokenForApproved(int $id): ?string
    {
        $letter = $this->getById($id);
        if ($letter === null) {
            return null;
        }

        $status = strtolower((string) ($letter['status'] ?? ''));
        if (!in_array($status, ['approved', 'disetujui'], true)) {
            return null;
        }

        $currentToken = (string) ($letter['verification_token'] ?? '');
        if ($currentToken !== '') {
            return $currentToken;
        }

        $pdo = db_pdo();

        for ($i = 0; $i < 5; $i++) {
            $token = generateVerificationToken(32);
            $stmt = $pdo->prepare(
                'UPDATE letters
                 SET verification_token = :token, updated_at = NOW()
                 WHERE id = :id AND (verification_token IS NULL OR verification_token = "")'
            );
            $stmt->execute([
                ':id' => $id,
                ':token' => $token,
            ]);

            if ($stmt->rowCount() > 0) {
                return $token;
            }

            $check = $this->getById($id);
            if (!empty($check['verification_token'])) {
                return (string) $check['verification_token'];
            }
        }

        throw new RuntimeException('Gagal membuat token verifikasi unik.');
    }

    public function getByVerificationToken(string $token): ?array
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'SELECT
                l.id,
                l.letter_number,
                l.letter_date,
                l.status,
                l.verification_token,
                lt.name AS letter_type_name,
                u.name AS applicant_name,
                rp.researcher_name
            FROM letters l
            INNER JOIN letter_types lt ON lt.id = l.letter_type_id
            LEFT JOIN users u ON u.id = l.applicant_id
            LEFT JOIN research_permit_letters rp ON rp.letter_id = l.id
            WHERE l.verification_token = :token
            LIMIT 1'
        );
        $stmt->execute([':token' => $token]);
        $data = $stmt->fetch();

        return $data !== false ? $data : null;
    }

    public function getRecentLetters(int $limit = 20, ?string $typeCode = null): array
    {
        $pdo = db_pdo();

        $sql = 'SELECT DISTINCT
                    l.id,
                    l.letter_number,
                    l.subject,
                    l.letter_date,
                    l.status,
                    l.file_pdf,
                    l.verification_token,
                    lt.name AS letter_type_name,
                    lt.code AS letter_type_code
                FROM letters l
                INNER JOIN letter_types lt ON lt.id = l.letter_type_id';

        if ($typeCode !== null && $typeCode !== '') {
            $sql .= ' WHERE UPPER(lt.code) = :type_code';
        }

        $sql .= ' ORDER BY l.id DESC LIMIT :limit_rows';

        $stmt = $pdo->prepare($sql);
        if ($typeCode !== null && $typeCode !== '') {
            $stmt->bindValue(':type_code', strtoupper($typeCode), PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit_rows', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll() ?: [];
    }

    public function getLettersForUser(int $userId, int $limit = 30, ?string $typeCode = null): array
    {
        $pdo = db_pdo();

        $sql = 'SELECT
                    l.id,
                    l.letter_number,
                    l.subject,
                    l.letter_date,
                    l.status,
                    l.file_pdf,
                    l.verification_token,
                    lt.name AS letter_type_name,
                    lt.code AS letter_type_code
                FROM letters l
                INNER JOIN letter_types lt ON lt.id = l.letter_type_id
                WHERE (l.applicant_id = :user_id OR l.created_by = :user_id)';

        if ($typeCode !== null && $typeCode !== '') {
            $sql .= ' AND UPPER(lt.code) = :type_code';
        }

        $sql .= ' ORDER BY l.id DESC LIMIT :limit_rows';

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        if ($typeCode !== null && $typeCode !== '') {
            $stmt->bindValue(':type_code', strtoupper($typeCode), PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit_rows', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll() ?: [];
    }

    public function getMyLetters(int $userId, array $filters = []): array
    {
        $pdo = db_pdo();

        $where = ['(' . $this->buildOwnedOrMemberLetterWhere('l', 'rp', 'stp') . ')'];
        $params = [
            ':user_id' => $userId,
            ':member_user_id' => $userId,
        ];

        if (!empty($filters['letter_type_id'])) {
            $where[] = 'l.letter_type_id = :letter_type_id';
            $params[':letter_type_id'] = (int) $filters['letter_type_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'LOWER(REPLACE(TRIM(l.status), " ", "_")) = :status';
            $params[':status'] = strtolower(str_replace(' ', '_', trim((string) $filters['status'])));
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'DATE(l.letter_date) >= :date_from';
            $params[':date_from'] = (string) $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'DATE(l.letter_date) <= :date_to';
            $params[':date_to'] = (string) $filters['date_to'];
        }

        $sql = 'SELECT
                    l.id,
                    l.letter_number,
                    l.subject,
                    l.letter_date,
                      l.institution,
                      l.status,
                      l.file_pdf,
                      CASE WHEN (l.applicant_id = :user_id OR l.created_by = :user_id) THEN 1 ELSE 0 END AS _is_owner_letter,
                      CASE WHEN (l.applicant_id = :user_id OR l.created_by = :user_id) THEN 0 ELSE 1 END AS _is_member_readonly,
                      COALESCE(
                        NULLIF(rp.research_title, ""),
                        NULLIF(dp.judul, ""),
                        NULLIF(dpg.judul, ""),
                        NULLIF(dh.judul, ""),
                        "-"
                    ) AS activity_title,
                    lt.code AS letter_type_code,
                    lt.name AS letter_type_name
                FROM letters l
                INNER JOIN letter_types lt ON lt.id = l.letter_type_id
                LEFT JOIN research_permit_letters rp ON rp.letter_id = l.id
                LEFT JOIN surat_tugas_penelitian stp ON stp.letter_id = l.id
                LEFT JOIN data_penelitian dp ON dp.id = COALESCE(NULLIF(stp.activity_id, 0), stp.penelitian_id) AND LOWER(TRIM(stp.activity_type)) = "penelitian"
                LEFT JOIN data_pengabdian dpg ON dpg.id = COALESCE(NULLIF(stp.activity_id, 0), stp.penelitian_id) AND LOWER(TRIM(stp.activity_type)) = "pengabdian"
                LEFT JOIN data_hilirisasi dh ON (
                    (dh.id = COALESCE(NULLIF(stp.activity_id, 0), stp.penelitian_id) AND LOWER(TRIM(stp.activity_type)) = "hilirisasi")
                    OR (
                        rp.notes COLLATE utf8mb4_general_ci LIKE CONCAT("%__ACTIVITY_REF__[hilirisasi:", CAST(dh.id AS CHAR), "]%")
                        AND dh.created_by = l.applicant_id
                    )
                )
                  WHERE ' . implode(' AND ', $where) . '
                  ORDER BY l.id DESC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    }

    public function countMyLettersByStatus(int $userId): array
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'SELECT LOWER(l.status) AS status_key, COUNT(DISTINCT l.id) AS total
             FROM letters l
             LEFT JOIN research_permit_letters rp ON rp.letter_id = l.id
             LEFT JOIN surat_tugas_penelitian stp ON stp.letter_id = l.id
             WHERE ' . $this->buildOwnedOrMemberLetterWhere('l', 'rp', 'stp') . '
             GROUP BY LOWER(l.status)'
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':member_user_id' => $userId,
        ]);
        $rows = $stmt->fetchAll() ?: [];

        $counts = [
            'total' => 0,
            'draft' => 0,
            'pending' => 0,
            'revision' => 0,
            'approved' => 0,
            'issued' => 0,
            'done' => 0,
            'rejected' => 0,
        ];

        foreach ($rows as $row) {
            $status = str_replace(' ', '_', (string) $row['status_key']);
            $total = (int) $row['total'];
            $counts['total'] += $total;

            if ($status === 'draft') {
                $counts['draft'] += $total;
            }
            if (in_array($status, ['diajukan', 'submitted', 'diverifikasi'], true)) {
                $counts['pending'] += $total;
            }
            if (in_array($status, ['perlu_diperbaiki', 'ditolak', 'rejected'], true)) {
                $counts['revision'] += $total;
            }
            if (in_array($status, ['disetujui', 'approved', 'menunggu_finalisasi'], true)) {
                $counts['approved'] += $total;
            }
            if (in_array($status, ['surat_terbit', 'terbit', 'selesai'], true)) {
                $counts['issued'] += $total;
            }
            if (in_array($status, ['ditolak', 'rejected'], true)) {
                $counts['rejected'] += $total;
            }
        }

        $counts['done'] = $counts['approved'] + $counts['issued'];

        return $counts;
    }

    public function getMyLetterDetail(int $letterId, int $userId): ?array
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'SELECT
                l.*,
                lt.code AS letter_type_code,
                lt.name AS letter_type_name,
                u.name AS applicant_name,
                u.nidn AS applicant_nidn,
                u.email AS applicant_email,
                u.phone AS applicant_phone,
                u.unit AS applicant_unit,
                rp.research_title,
                rp.research_scheme,
                rp.funding_source,
                rp.research_year,
                rp.researcher_name,
                rp.members,
                rp.purpose,
                rp.research_location,
                rp.address,
                rp.city,
                rp.unit AS research_unit,
                rp.faculty,
                rp.start_date,
                rp.end_date,
                rp.destination_position,
                rp.notes,
                rp.attachment_file
            FROM letters l
            INNER JOIN letter_types lt ON lt.id = l.letter_type_id
            LEFT JOIN users u ON u.id = l.applicant_id
            LEFT JOIN research_permit_letters rp ON rp.letter_id = l.id
            WHERE l.id = :letter_id
              AND (l.applicant_id = :user_id OR l.created_by = :user_id)
            LIMIT 1'
        );
        $stmt->execute([
            ':letter_id' => $letterId,
            ':user_id' => $userId,
        ]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function getMyLetterByNumber(string $letterNumber, int $userId): ?array
    {
        $normalized = trim($letterNumber);
        if ($normalized === '') {
            return null;
        }

        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'SELECT id, letter_number
             FROM letters
             WHERE letter_number = :letter_number
               AND (applicant_id = :user_id OR created_by = :user_id)
             LIMIT 1'
        );
        $stmt->execute([
            ':letter_number' => $normalized,
            ':user_id' => $userId,
        ]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function isOwnedByUser(int $letterId, int $userId): bool
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*)
             FROM letters
             WHERE id = :id
               AND (applicant_id = :user_id OR created_by = :user_id)'
        );
        $stmt->execute([
            ':id' => $letterId,
            ':user_id' => $userId,
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function canViewByUser(int $letterId, int $userId): bool
    {
        if ($letterId <= 0 || $userId <= 0) {
            return false;
        }

        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'SELECT COUNT(DISTINCT l.id)
             FROM letters l
             LEFT JOIN research_permit_letters rp ON rp.letter_id = l.id
             LEFT JOIN surat_tugas_penelitian stp ON stp.letter_id = l.id
             WHERE l.id = :letter_id
               AND (' . $this->buildOwnedOrMemberLetterWhere('l', 'rp', 'stp') . ')'
        );
        $stmt->execute([
            ':letter_id' => $letterId,
            ':user_id' => $userId,
            ':member_user_id' => $userId,
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function getDashboardStats(): array
    {
        $pdo = db_pdo();

        $totalLetters = (int) $pdo->query('SELECT COUNT(*) FROM letters')->fetchColumn();
        $pending = (int) $pdo->query("SELECT COUNT(*) FROM letters WHERE LOWER(status) IN ('diajukan','submitted','diverifikasi','menunggu diproses')")->fetchColumn();
        $revision = (int) $pdo->query("SELECT COUNT(*) FROM letters WHERE LOWER(status) IN ('perlu_diperbaiki','perlu diperbaiki','ditolak','rejected')")->fetchColumn();
        $approved = (int) $pdo->query("SELECT COUNT(*) FROM letters WHERE LOWER(status) IN ('disetujui','approved')")->fetchColumn();
        $issued = (int) $pdo->query("SELECT COUNT(*) FROM letters WHERE LOWER(status) IN ('surat_terbit','surat terbit','terbit','selesai')")->fetchColumn();
        $activeContracts = (int) $pdo->query("SELECT COUNT(*) FROM research_contracts WHERE LOWER(status) = 'active'")->fetchColumn();
        $year = (int) date('Y');
        $stmtArchives = $pdo->prepare('SELECT COUNT(*) FROM archives WHERE year = :year');
        $stmtArchives->execute([':year' => $year]);
        $archives = (int) $stmtArchives->fetchColumn();

        return [
            'total' => $totalLetters,
            'pending' => $pending,
            'approved' => $approved,
            'issued' => $issued,
            'revision' => $revision,
            'rejected' => $revision,
            'total_letters' => $totalLetters,
            'pending_approval' => $pending,
            'approved_count' => $approved,
            'rejected_count' => $revision,
            'active_contracts' => $activeContracts,
            'archive_count' => $archives,
        ];
    }

    public function getLettersPerMonth(int $year): array
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'SELECT MONTH(letter_date) AS month_num, COUNT(*) AS total
             FROM letters
             WHERE YEAR(letter_date) = :year
             GROUP BY MONTH(letter_date)'
        );
        $stmt->execute([':year' => $year]);
        $rows = $stmt->fetchAll() ?: [];

        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $result = [];
        foreach ($monthNames as $number => $name) {
            $result[$name] = 0;
        }

        foreach ($rows as $row) {
            $monthNum = (int) $row['month_num'];
            if (isset($monthNames[$monthNum])) {
                $result[$monthNames[$monthNum]] = (int) $row['total'];
            }
        }

        return $result;
    }

    public function getLettersPerMonthForUser(int $userId, int $year): array
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'SELECT MONTH(letter_date) AS month_num, COUNT(*) AS total
             FROM letters
             WHERE YEAR(letter_date) = :year
               AND applicant_id = :user_id
             GROUP BY MONTH(letter_date)'
        );
        $stmt->execute([
            ':year' => $year,
            ':user_id' => $userId,
        ]);
        $rows = $stmt->fetchAll() ?: [];

        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $result = [];
        foreach ($monthNames as $number => $name) {
            $result[$name] = 0;
        }

        foreach ($rows as $row) {
            $monthNum = (int) $row['month_num'];
            if (isset($monthNames[$monthNum])) {
                $result[$monthNames[$monthNum]] = (int) $row['total'];
            }
        }

        return $result;
    }

    public function getLetterTypeDistribution(): array
    {
        $pdo = db_pdo();
        $stmt = $pdo->query(
            'SELECT lt.name, COUNT(*) AS total
             FROM letters l
             INNER JOIN letter_types lt ON lt.id = l.letter_type_id
             GROUP BY lt.id, lt.name
             ORDER BY lt.name ASC'
        );
        $rows = $stmt->fetchAll() ?: [];

        $distribution = [];
        foreach ($rows as $row) {
            $distribution[(string) $row['name']] = (int) $row['total'];
        }

        return $distribution;
    }

    public function getTodayStats(): array
    {
        $pdo = db_pdo();
        $stmt = $pdo->query(
            "SELECT
                SUM(CASE WHEN DATE(letter_date) = CURDATE() THEN 1 ELSE 0 END) AS incoming,
                SUM(CASE WHEN DATE(letter_date) = CURDATE() AND LOWER(status) IN ('diajukan','submitted') THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN DATE(letter_date) = CURDATE() AND LOWER(status) IN ('disetujui','approved','selesai') THEN 1 ELSE 0 END) AS done
             FROM letters"
        );
        $row = $stmt->fetch() ?: [];

        return [
            'incoming' => (int) ($row['incoming'] ?? 0),
            'pending' => (int) ($row['pending'] ?? 0),
            'done' => (int) ($row['done'] ?? 0),
        ];
    }

    public function getRecentLettersDetailed(int $limit = 5): array
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'SELECT
                l.id,
                l.letter_number,
                l.letter_date AS date,
                l.status,
                lt.name AS type,
                COALESCE(u.name, rp.researcher_name, "-") AS applicant
            FROM letters l
            INNER JOIN letter_types lt ON lt.id = l.letter_type_id
            LEFT JOIN users u ON u.id = l.applicant_id
            LEFT JOIN research_permit_letters rp ON rp.letter_id = l.id
            ORDER BY
                CASE
                    WHEN l.letter_number IS NULL OR TRIM(l.letter_number) = "" THEN 1
                    ELSE 0
                END ASC,
                l.id DESC
            LIMIT :limit_rows'
        );
        $stmt->bindValue(':limit_rows', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll() ?: [];
    }

    public function markAsApproved(int $id): void
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare('UPDATE letters SET status = :status, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            ':status' => 'approved',
            ':id' => $id,
        ]);
    }

    public function markAsRejected(int $id): void
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare('UPDATE letters SET status = :status, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            ':status' => 'rejected',
            ':id' => $id,
        ]);
    }

    public function markAsNeedsRevision(int $id): void
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare('UPDATE letters SET status = :status, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            ':status' => 'perlu_diperbaiki',
            ':id' => $id,
        ]);
    }

    public function markAsIssued(int $id): void
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare('UPDATE letters SET status = :status, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            ':status' => 'surat_terbit',
            ':id' => $id,
        ]);
    }

    public function deleteIssuedLetterById(int $id): bool
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'DELETE FROM letters
             WHERE id = :id
               AND LOWER(REPLACE(TRIM(status), " ", "_")) IN ("surat_terbit", "terbit", "selesai")'
        );
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() > 0;
    }

    public function getLatestContractSubmissionByActivity(int $userId, string $activityType, int $activityId): ?array
    {
        $pdo = db_pdo();
        $activityRef = '__ACTIVITY_REF__[' . strtolower($activityType) . ':' . $activityId . ']';

        $stmt = $pdo->prepare(
            'SELECT
                l.id,
                l.status,
                l.subject,
                l.letter_date,
                l.updated_at
            FROM letters l
            INNER JOIN research_permit_letters rp ON rp.letter_id = l.id
            WHERE l.applicant_id = :user_id
              AND rp.notes LIKE :activity_ref
              AND LOWER(l.subject) LIKE :subject_key
            ORDER BY l.id DESC
            LIMIT 1'
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':activity_ref' => '%' . $activityRef . '%',
            ':subject_key' => '%kontrak%',
        ]);

        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    private function buildOwnedOrMemberLetterWhere(string $letterAlias, string $permitAlias, string $taskAlias): string
    {
        if (!$this->activityMemberModel->relationTableAvailable()) {
            return '(' . $letterAlias . '.applicant_id = :user_id OR ' . $letterAlias . '.created_by = :user_id)';
        }

        return '(' . $letterAlias . '.applicant_id = :user_id OR ' . $letterAlias . '.created_by = :user_id)
            OR EXISTS (
                SELECT 1
                FROM activity_member_relations amr
                WHERE amr.member_user_id = :member_user_id
                  AND (
                    (
                        LOWER(COALESCE(NULLIF(' . $taskAlias . '.activity_type, ""), "penelitian")) COLLATE utf8mb4_unicode_ci = amr.activity_type COLLATE utf8mb4_unicode_ci
                        AND COALESCE(NULLIF(' . $taskAlias . '.activity_id, 0), ' . $taskAlias . '.penelitian_id) = amr.activity_id
                    )
                    OR ' . $permitAlias . '.notes COLLATE utf8mb4_unicode_ci LIKE CONCAT("%__ACTIVITY_REF__[", amr.activity_type COLLATE utf8mb4_unicode_ci, ":", CAST(amr.activity_id AS CHAR), "]%")
                  )
            )';
    }

    public function getHeadSubmissionRows(int $limit = 100, array $filters = []): array
    {
        $pdo = db_pdo();
        $where = [
            'LOWER(u.role) = "dosen"',
            'LOWER(REPLACE(TRIM(l.status), " ", "_")) NOT IN ("surat_terbit", "terbit", "selesai")',
            '('
            . 'LOWER(l.subject) LIKE "%kontrak%"'
            . ' OR LOWER(l.subject) LIKE "%izin%"'
            . ' OR LOWER(l.subject) LIKE "%tugas%"'
            . ')',
        ];
        $params = [];

        $jenis = strtolower(trim((string) ($filters['jenis'] ?? '')));
        if (in_array($jenis, ['kontrak', 'izin', 'tugas'], true)) {
            $where[] = 'LOWER(l.subject) LIKE :jenis_filter';
            $params[':jenis_filter'] = '%' . $jenis . '%';
        }

        $tahun = trim((string) ($filters['tahun'] ?? ''));
        if ($tahun !== '' && ctype_digit($tahun)) {
            $where[] = 'COALESCE(CAST(rp.research_year AS CHAR), CAST(dp.tahun AS CHAR), CAST(dpg.tahun AS CHAR), CAST(dh.tahun AS CHAR), "") = :tahun_filter';
            $params[':tahun_filter'] = $tahun;
        }

        $keyword = trim((string) ($filters['keyword'] ?? ''));
        if ($keyword !== '') {
            $where[] = '('
                . 'COALESCE(rp.research_title, dp.judul, dpg.judul, dh.judul, "") LIKE :keyword'
                . ' OR COALESCE(rp.researcher_name, dp.ketua, dpg.ketua, dh.ketua, u.name, "") LIKE :keyword'
                . ' OR COALESCE(rp.research_scheme, dp.ruang_lingkup, dp.skema, dpg.ruang_lingkup, dpg.skema, dh.ruang_lingkup, dh.skema, "") LIKE :keyword'
                . ')';
            $params[':keyword'] = '%' . $keyword . '%';
        }

        $stmt = $pdo->prepare(
            'SELECT
                l.id,
                l.subject,
                COALESCE(NULLIF(l.letter_type_slug, ""), "") AS letter_type_slug,
                l.status,
                l.letter_date,
                l.created_at AS waktu_pembuatan_surat,
                COALESCE(
                    NULLIF(rp.research_title, ""),
                    NULLIF(dp.judul, ""),
                    NULLIF(dpg.judul, ""),
                    NULLIF(dh.judul, ""),
                    "-"
                ) AS judul,
                COALESCE(
                    NULLIF(rp.researcher_name, ""),
                    NULLIF(dp.ketua, ""),
                    NULLIF(dpg.ketua, ""),
                    NULLIF(dh.ketua, ""),
                    NULLIF(u.name, ""),
                    "-"
                ) AS nama_ketua,
                COALESCE(
                    NULLIF(dp.skema, ""),
                    NULLIF(dpg.skema, ""),
                    NULLIF(dh.skema, ""),
                    NULLIF(rp.research_scheme, ""),
                    "-"
                ) AS skema,
                COALESCE(
                    NULLIF(dp.ruang_lingkup, ""),
                    NULLIF(dpg.ruang_lingkup, ""),
                    NULLIF(dh.ruang_lingkup, ""),
                    NULLIF(rp.research_scheme, ""),
                    "-"
                ) AS ruang_lingkup,
                COALESCE(
                    NULLIF(CAST(rp.research_year AS CHAR), ""),
                    NULLIF(CAST(dp.tahun AS CHAR), ""),
                    NULLIF(CAST(dpg.tahun AS CHAR), ""),
                    NULLIF(CAST(dh.tahun AS CHAR), ""),
                    "-"
                ) AS tahun
            FROM letters l
            INNER JOIN users u ON u.id = l.applicant_id
            LEFT JOIN research_permit_letters rp ON rp.letter_id = l.id
            LEFT JOIN surat_tugas_penelitian stp ON stp.letter_id = l.id
            LEFT JOIN data_penelitian dp ON dp.id = COALESCE(NULLIF(stp.activity_id, 0), stp.penelitian_id) AND LOWER(TRIM(stp.activity_type)) = "penelitian"
            LEFT JOIN data_pengabdian dpg ON dpg.id = COALESCE(NULLIF(stp.activity_id, 0), stp.penelitian_id) AND LOWER(TRIM(stp.activity_type)) = "pengabdian"
            LEFT JOIN data_hilirisasi dh ON (
                (dh.id = COALESCE(NULLIF(stp.activity_id, 0), stp.penelitian_id) AND LOWER(TRIM(stp.activity_type)) = "hilirisasi")
                OR (
                    rp.notes COLLATE utf8mb4_general_ci LIKE CONCAT("%__ACTIVITY_REF__[hilirisasi:", CAST(dh.id AS CHAR), "]%")
                    AND dh.created_by = l.applicant_id
                )
            )
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY l.created_at DESC, l.id DESC
            LIMIT :limit_rows'
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit_rows', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll() ?: [];
    }

    public function getHeadSubmissionSummary(array $filters = []): array
    {
        $pdo = db_pdo();
        $where = [
            'LOWER(u.role) = "dosen"',
            'LOWER(REPLACE(TRIM(l.status), " ", "_")) NOT IN ("surat_terbit", "terbit", "selesai")',
            '('
            . 'LOWER(l.subject) LIKE "%kontrak%"'
            . ' OR LOWER(l.subject) LIKE "%izin%"'
            . ' OR LOWER(l.subject) LIKE "%tugas%"'
            . ')',
        ];
        $params = [];

        $jenis = strtolower(trim((string) ($filters['jenis'] ?? '')));
        if (in_array($jenis, ['kontrak', 'izin', 'tugas'], true)) {
            $where[] = 'LOWER(l.subject) LIKE :jenis_filter';
            $params[':jenis_filter'] = '%' . $jenis . '%';
        }

        $tahun = trim((string) ($filters['tahun'] ?? ''));
        if ($tahun !== '' && ctype_digit($tahun)) {
            $where[] = 'COALESCE(CAST(rp.research_year AS CHAR), CAST(dp.tahun AS CHAR), CAST(dpg.tahun AS CHAR), CAST(dh.tahun AS CHAR), "") = :tahun_filter';
            $params[':tahun_filter'] = $tahun;
        }

        $keyword = trim((string) ($filters['keyword'] ?? ''));
        if ($keyword !== '') {
            $where[] = '('
                . 'COALESCE(rp.research_title, dp.judul, dpg.judul, dh.judul, "") LIKE :keyword'
                . ' OR COALESCE(rp.researcher_name, dp.ketua, dpg.ketua, dh.ketua, u.name, "") LIKE :keyword'
                . ' OR COALESCE(rp.research_scheme, dp.ruang_lingkup, dp.skema, dpg.ruang_lingkup, dpg.skema, dh.ruang_lingkup, dh.skema, "") LIKE :keyword'
                . ')';
            $params[':keyword'] = '%' . $keyword . '%';
        }

        $stmt = $pdo->prepare(
            'SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN LOWER(l.subject) LIKE "%kontrak%" THEN 1 ELSE 0 END) AS kontrak,
                SUM(CASE WHEN LOWER(l.subject) LIKE "%izin%" THEN 1 ELSE 0 END) AS izin,
                SUM(CASE WHEN LOWER(l.subject) LIKE "%tugas%" THEN 1 ELSE 0 END) AS tugas
            FROM letters l
            INNER JOIN users u ON u.id = l.applicant_id
            LEFT JOIN research_permit_letters rp ON rp.letter_id = l.id
            LEFT JOIN surat_tugas_penelitian stp ON stp.letter_id = l.id
            LEFT JOIN data_penelitian dp ON dp.id = COALESCE(NULLIF(stp.activity_id, 0), stp.penelitian_id) AND LOWER(TRIM(stp.activity_type)) = "penelitian"
            LEFT JOIN data_pengabdian dpg ON dpg.id = COALESCE(NULLIF(stp.activity_id, 0), stp.penelitian_id) AND LOWER(TRIM(stp.activity_type)) = "pengabdian"
            LEFT JOIN data_hilirisasi dh ON (
                (dh.id = COALESCE(NULLIF(stp.activity_id, 0), stp.penelitian_id) AND LOWER(TRIM(stp.activity_type)) = "hilirisasi")
                OR (
                    rp.notes COLLATE utf8mb4_general_ci LIKE CONCAT("%__ACTIVITY_REF__[hilirisasi:", CAST(dh.id AS CHAR), "]%")
                    AND dh.created_by = l.applicant_id
                )
            )
            WHERE ' . implode(' AND ', $where)
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->execute();
        $row = $stmt->fetch() ?: [];

        return [
            'total' => (int) ($row['total'] ?? 0),
            'kontrak' => (int) ($row['kontrak'] ?? 0),
            'izin' => (int) ($row['izin'] ?? 0),
            'tugas' => (int) ($row['tugas'] ?? 0),
        ];
    }

    public function getHeadSubmissionYears(): array
    {
        $pdo = db_pdo();
        $stmt = $pdo->query(
            'SELECT DISTINCT year_value
             FROM (
                SELECT CAST(rp.research_year AS CHAR) AS year_value
                FROM letters l
                INNER JOIN users u ON u.id = l.applicant_id
                LEFT JOIN research_permit_letters rp ON rp.letter_id = l.id
                WHERE LOWER(u.role) = "dosen"
                  AND LOWER(REPLACE(TRIM(l.status), " ", "_")) NOT IN ("surat_terbit", "terbit", "selesai")
                  AND rp.research_year IS NOT NULL
                  AND CAST(rp.research_year AS CHAR) <> ""
                  AND (
                    LOWER(l.subject) LIKE "%kontrak%"
                    OR LOWER(l.subject) LIKE "%izin%"
                    OR LOWER(l.subject) LIKE "%tugas%"
                  )
                UNION
                SELECT CAST(dp.tahun AS CHAR) AS year_value
                FROM letters l
                INNER JOIN users u ON u.id = l.applicant_id
                LEFT JOIN surat_tugas_penelitian stp ON stp.letter_id = l.id
                LEFT JOIN data_penelitian dp ON dp.id = COALESCE(NULLIF(stp.activity_id, 0), stp.penelitian_id) AND LOWER(TRIM(stp.activity_type)) = "penelitian"
                WHERE LOWER(u.role) = "dosen"
                  AND LOWER(REPLACE(TRIM(l.status), " ", "_")) NOT IN ("surat_terbit", "terbit", "selesai")
                  AND dp.tahun IS NOT NULL
                  AND CAST(dp.tahun AS CHAR) <> ""
                  AND (
                    LOWER(l.subject) LIKE "%kontrak%"
                    OR LOWER(l.subject) LIKE "%izin%"
                    OR LOWER(l.subject) LIKE "%tugas%"
                  )
                UNION
                SELECT CAST(dpg.tahun AS CHAR) AS year_value
                FROM letters l
                INNER JOIN users u ON u.id = l.applicant_id
                LEFT JOIN surat_tugas_penelitian stp ON stp.letter_id = l.id
                LEFT JOIN data_pengabdian dpg ON dpg.id = COALESCE(NULLIF(stp.activity_id, 0), stp.penelitian_id) AND LOWER(TRIM(stp.activity_type)) = "pengabdian"
                WHERE LOWER(u.role) = "dosen"
                  AND LOWER(REPLACE(TRIM(l.status), " ", "_")) NOT IN ("surat_terbit", "terbit", "selesai")
                  AND dpg.tahun IS NOT NULL
                  AND CAST(dpg.tahun AS CHAR) <> ""
                  AND (
                    LOWER(l.subject) LIKE "%kontrak%"
                    OR LOWER(l.subject) LIKE "%izin%"
                    OR LOWER(l.subject) LIKE "%tugas%"
                  )
                UNION
                SELECT CAST(dh.tahun AS CHAR) AS year_value
                FROM letters l
                INNER JOIN users u ON u.id = l.applicant_id
                LEFT JOIN research_permit_letters rp ON rp.letter_id = l.id
                LEFT JOIN surat_tugas_penelitian stp ON stp.letter_id = l.id
                LEFT JOIN data_hilirisasi dh ON (
                    (dh.id = COALESCE(NULLIF(stp.activity_id, 0), stp.penelitian_id) AND LOWER(TRIM(stp.activity_type)) = "hilirisasi")
                    OR (
                        rp.notes COLLATE utf8mb4_general_ci LIKE CONCAT("%__ACTIVITY_REF__[hilirisasi:", CAST(dh.id AS CHAR), "]%")
                        AND dh.created_by = l.applicant_id
                    )
                )
                WHERE LOWER(u.role) = "dosen"
                  AND LOWER(REPLACE(TRIM(l.status), " ", "_")) NOT IN ("surat_terbit", "terbit", "selesai")
                  AND dh.tahun IS NOT NULL
                  AND CAST(dh.tahun AS CHAR) <> ""
                  AND (
                    LOWER(l.subject) LIKE "%kontrak%"
                    OR LOWER(l.subject) LIKE "%izin%"
                    OR LOWER(l.subject) LIKE "%tugas%"
                  )
                UNION
                SELECT CAST(dh.tahun AS CHAR) AS year_value
                FROM letters l
                INNER JOIN users u ON u.id = l.applicant_id
                LEFT JOIN research_permit_letters rp ON rp.letter_id = l.id
                LEFT JOIN surat_tugas_penelitian stp ON stp.letter_id = l.id
                LEFT JOIN data_hilirisasi dh ON (
                    (dh.id = COALESCE(NULLIF(stp.activity_id, 0), stp.penelitian_id) AND LOWER(TRIM(stp.activity_type)) = "hilirisasi")
                    OR (
                        rp.notes COLLATE utf8mb4_general_ci LIKE CONCAT("%__ACTIVITY_REF__[hilirisasi:", CAST(dh.id AS CHAR), "]%")
                        AND dh.created_by = l.applicant_id
                    )
                )
                WHERE LOWER(u.role) = "dosen"
                  AND LOWER(REPLACE(TRIM(l.status), " ", "_")) IN ("surat_terbit", "terbit", "selesai")
                  AND dh.tahun IS NOT NULL
                  AND CAST(dh.tahun AS CHAR) <> ""
                  AND (
                    LOWER(l.subject) LIKE "%kontrak%"
                    OR LOWER(l.subject) LIKE "%izin%"
                    OR LOWER(l.subject) LIKE "%tugas%"
                  )
             ) years
             WHERE year_value <> ""
             ORDER BY year_value DESC'
        );
        $rows = $stmt->fetchAll() ?: [];

        return array_values(array_map(static fn(array $row): string => (string) ($row['year_value'] ?? ''), $rows));
    }

    public function getHeadArchiveRows(int $limit = 100, array $filters = []): array
    {
        $pdo = db_pdo();
        $where = [
            'LOWER(u.role) = "dosen"',
            'LOWER(REPLACE(TRIM(l.status), " ", "_")) IN ("surat_terbit", "terbit", "selesai")',
            '('
            . 'LOWER(l.subject) LIKE "%kontrak%"'
            . ' OR LOWER(l.subject) LIKE "%izin%"'
            . ' OR LOWER(l.subject) LIKE "%tugas%"'
            . ')',
        ];
        $params = [];

        $jenis = strtolower(trim((string) ($filters['jenis'] ?? '')));
        if (in_array($jenis, ['kontrak', 'izin', 'tugas'], true)) {
            $where[] = 'LOWER(l.subject) LIKE :jenis_filter';
            $params[':jenis_filter'] = '%' . $jenis . '%';
        }

        $tahun = trim((string) ($filters['tahun'] ?? ''));
        if ($tahun !== '' && ctype_digit($tahun)) {
            $where[] = 'COALESCE(CAST(rp.research_year AS CHAR), CAST(dp.tahun AS CHAR), CAST(dpg.tahun AS CHAR), CAST(dh.tahun AS CHAR), "") = :tahun_filter';
            $params[':tahun_filter'] = $tahun;
        }

        $keyword = trim((string) ($filters['keyword'] ?? ''));
        if ($keyword !== '') {
            $where[] = '('
                . 'COALESCE(rp.research_title, dp.judul, dpg.judul, dh.judul, "") LIKE :keyword'
                . ' OR COALESCE(rp.researcher_name, dp.ketua, dpg.ketua, dh.ketua, u.name, "") LIKE :keyword'
                . ' OR COALESCE(rp.research_scheme, dp.ruang_lingkup, dp.skema, dpg.ruang_lingkup, dpg.skema, dh.ruang_lingkup, dh.skema, "") LIKE :keyword'
                . ')';
            $params[':keyword'] = '%' . $keyword . '%';
        }

        $stmt = $pdo->prepare(
            'SELECT
                l.id,
                l.subject,
                COALESCE(NULLIF(l.letter_type_slug, ""), "") AS letter_type_slug,
                l.status,
                l.letter_date,
                COALESCE(
                    NULLIF(rp.research_title, ""),
                    NULLIF(dp.judul, ""),
                    NULLIF(dpg.judul, ""),
                    NULLIF(dh.judul, ""),
                    "-"
                ) AS judul,
                COALESCE(
                    NULLIF(rp.researcher_name, ""),
                    NULLIF(dp.ketua, ""),
                    NULLIF(dpg.ketua, ""),
                    NULLIF(dh.ketua, ""),
                    NULLIF(u.name, ""),
                    "-"
                ) AS nama_ketua,
                COALESCE(
                    NULLIF(dp.skema, ""),
                    NULLIF(dpg.skema, ""),
                    NULLIF(dh.skema, ""),
                    NULLIF(rp.research_scheme, ""),
                    "-"
                ) AS skema,
                COALESCE(
                    NULLIF(dp.ruang_lingkup, ""),
                    NULLIF(dpg.ruang_lingkup, ""),
                    NULLIF(dh.ruang_lingkup, ""),
                    NULLIF(rp.research_scheme, ""),
                    "-"
                ) AS ruang_lingkup,
                COALESCE(
                    NULLIF(CAST(rp.research_year AS CHAR), ""),
                    NULLIF(CAST(dp.tahun AS CHAR), ""),
                    NULLIF(CAST(dpg.tahun AS CHAR), ""),
                    NULLIF(CAST(dh.tahun AS CHAR), ""),
                    "-"
                ) AS tahun
            FROM letters l
            INNER JOIN users u ON u.id = l.applicant_id
            LEFT JOIN research_permit_letters rp ON rp.letter_id = l.id
            LEFT JOIN surat_tugas_penelitian stp ON stp.letter_id = l.id
            LEFT JOIN data_penelitian dp ON dp.id = COALESCE(NULLIF(stp.activity_id, 0), stp.penelitian_id) AND LOWER(TRIM(stp.activity_type)) = "penelitian"
            LEFT JOIN data_pengabdian dpg ON dpg.id = COALESCE(NULLIF(stp.activity_id, 0), stp.penelitian_id) AND LOWER(TRIM(stp.activity_type)) = "pengabdian"
            LEFT JOIN data_hilirisasi dh ON (
                (dh.id = COALESCE(NULLIF(stp.activity_id, 0), stp.penelitian_id) AND LOWER(TRIM(stp.activity_type)) = "hilirisasi")
                OR (
                    rp.notes COLLATE utf8mb4_general_ci LIKE CONCAT("%__ACTIVITY_REF__[hilirisasi:", CAST(dh.id AS CHAR), "]%")
                    AND dh.created_by = l.applicant_id
                )
            )
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY l.id DESC
            LIMIT :limit_rows'
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit_rows', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll() ?: [];
    }

    public function getHeadArchiveSummary(array $filters = []): array
    {
        $pdo = db_pdo();
        $where = [
            'LOWER(u.role) = "dosen"',
            'LOWER(REPLACE(TRIM(l.status), " ", "_")) IN ("surat_terbit", "terbit", "selesai")',
            '('
            . 'LOWER(l.subject) LIKE "%kontrak%"'
            . ' OR LOWER(l.subject) LIKE "%izin%"'
            . ' OR LOWER(l.subject) LIKE "%tugas%"'
            . ')',
        ];
        $params = [];

        $jenis = strtolower(trim((string) ($filters['jenis'] ?? '')));
        if (in_array($jenis, ['kontrak', 'izin', 'tugas'], true)) {
            $where[] = 'LOWER(l.subject) LIKE :jenis_filter';
            $params[':jenis_filter'] = '%' . $jenis . '%';
        }

        $tahun = trim((string) ($filters['tahun'] ?? ''));
        if ($tahun !== '' && ctype_digit($tahun)) {
            $where[] = 'COALESCE(CAST(rp.research_year AS CHAR), CAST(dp.tahun AS CHAR), CAST(dpg.tahun AS CHAR), CAST(dh.tahun AS CHAR), "") = :tahun_filter';
            $params[':tahun_filter'] = $tahun;
        }

        $keyword = trim((string) ($filters['keyword'] ?? ''));
        if ($keyword !== '') {
            $where[] = '('
                . 'COALESCE(rp.research_title, dp.judul, dpg.judul, dh.judul, "") LIKE :keyword'
                . ' OR COALESCE(rp.researcher_name, dp.ketua, dpg.ketua, dh.ketua, u.name, "") LIKE :keyword'
                . ' OR COALESCE(rp.research_scheme, dp.ruang_lingkup, dp.skema, dpg.ruang_lingkup, dpg.skema, dh.ruang_lingkup, dh.skema, "") LIKE :keyword'
                . ')';
            $params[':keyword'] = '%' . $keyword . '%';
        }

        $stmt = $pdo->prepare(
            'SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN LOWER(l.subject) LIKE "%kontrak%" THEN 1 ELSE 0 END) AS kontrak,
                SUM(CASE WHEN LOWER(l.subject) LIKE "%izin%" THEN 1 ELSE 0 END) AS izin,
                SUM(CASE WHEN LOWER(l.subject) LIKE "%tugas%" THEN 1 ELSE 0 END) AS tugas
            FROM letters l
            INNER JOIN users u ON u.id = l.applicant_id
            LEFT JOIN research_permit_letters rp ON rp.letter_id = l.id
            LEFT JOIN surat_tugas_penelitian stp ON stp.letter_id = l.id
            LEFT JOIN data_penelitian dp ON dp.id = COALESCE(NULLIF(stp.activity_id, 0), stp.penelitian_id) AND LOWER(TRIM(stp.activity_type)) = "penelitian"
            LEFT JOIN data_pengabdian dpg ON dpg.id = COALESCE(NULLIF(stp.activity_id, 0), stp.penelitian_id) AND LOWER(TRIM(stp.activity_type)) = "pengabdian"
            LEFT JOIN data_hilirisasi dh ON (
                (dh.id = COALESCE(NULLIF(stp.activity_id, 0), stp.penelitian_id) AND LOWER(TRIM(stp.activity_type)) = "hilirisasi")
                OR (
                    rp.notes COLLATE utf8mb4_general_ci LIKE CONCAT("%__ACTIVITY_REF__[hilirisasi:", CAST(dh.id AS CHAR), "]%")
                    AND dh.created_by = l.applicant_id
                )
            )
            WHERE ' . implode(' AND ', $where)
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->execute();
        $row = $stmt->fetch() ?: [];

        return [
            'total' => (int) ($row['total'] ?? 0),
            'kontrak' => (int) ($row['kontrak'] ?? 0),
            'izin' => (int) ($row['izin'] ?? 0),
            'tugas' => (int) ($row['tugas'] ?? 0),
        ];
    }

    public function getHeadArchiveYears(): array
    {
        $pdo = db_pdo();
        $stmt = $pdo->query(
            'SELECT DISTINCT year_value
             FROM (
                SELECT CAST(rp.research_year AS CHAR) AS year_value
                FROM letters l
                INNER JOIN users u ON u.id = l.applicant_id
                LEFT JOIN research_permit_letters rp ON rp.letter_id = l.id
                WHERE LOWER(u.role) = "dosen"
                  AND LOWER(REPLACE(TRIM(l.status), " ", "_")) IN ("surat_terbit", "terbit", "selesai")
                  AND rp.research_year IS NOT NULL
                  AND CAST(rp.research_year AS CHAR) <> ""
                  AND (
                    LOWER(l.subject) LIKE "%kontrak%"
                    OR LOWER(l.subject) LIKE "%izin%"
                    OR LOWER(l.subject) LIKE "%tugas%"
                  )
                UNION
                SELECT CAST(dp.tahun AS CHAR) AS year_value
                FROM letters l
                INNER JOIN users u ON u.id = l.applicant_id
                LEFT JOIN surat_tugas_penelitian stp ON stp.letter_id = l.id
                LEFT JOIN data_penelitian dp ON dp.id = COALESCE(NULLIF(stp.activity_id, 0), stp.penelitian_id) AND LOWER(TRIM(stp.activity_type)) = "penelitian"
                WHERE LOWER(u.role) = "dosen"
                  AND LOWER(REPLACE(TRIM(l.status), " ", "_")) IN ("surat_terbit", "terbit", "selesai")
                  AND dp.tahun IS NOT NULL
                  AND CAST(dp.tahun AS CHAR) <> ""
                  AND (
                    LOWER(l.subject) LIKE "%kontrak%"
                    OR LOWER(l.subject) LIKE "%izin%"
                    OR LOWER(l.subject) LIKE "%tugas%"
                  )
                UNION
                SELECT CAST(dpg.tahun AS CHAR) AS year_value
                FROM letters l
                INNER JOIN users u ON u.id = l.applicant_id
                LEFT JOIN surat_tugas_penelitian stp ON stp.letter_id = l.id
                LEFT JOIN data_pengabdian dpg ON dpg.id = COALESCE(NULLIF(stp.activity_id, 0), stp.penelitian_id) AND LOWER(TRIM(stp.activity_type)) = "pengabdian"
                WHERE LOWER(u.role) = "dosen"
                  AND LOWER(REPLACE(TRIM(l.status), " ", "_")) IN ("surat_terbit", "terbit", "selesai")
                  AND dpg.tahun IS NOT NULL
                  AND CAST(dpg.tahun AS CHAR) <> ""
                  AND (
                    LOWER(l.subject) LIKE "%kontrak%"
                    OR LOWER(l.subject) LIKE "%izin%"
                    OR LOWER(l.subject) LIKE "%tugas%"
                  )
             ) years
             WHERE year_value <> ""
             ORDER BY year_value DESC'
        );
        $rows = $stmt->fetchAll() ?: [];

        return array_values(array_map(static fn(array $row): string => (string) ($row['year_value'] ?? ''), $rows));
    }
}

