<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';
require_once __DIR__ . '/../Helpers/LetterHelper.php';

class UserModel extends BaseModel
{
    private ?array $usersColumns = null;

    public function findByLogin(string $login): ?array
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            "SELECT id, name, nidn, nuptk, email, username, password, role, unit, phone, avatar, signature_path, status
             FROM users
             WHERE username = :login OR email = :login OR nuptk = :login
             LIMIT 1"
        );
        $stmt->execute([':login' => $login]);
        $data = $stmt->fetch();

        return $data !== false ? $data : null;
    }

    public function findByEmail(string $email): ?array
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            "SELECT id, name, nidn, nuptk, email, username, password, role, unit, phone, avatar, signature_path, status
             FROM users
             WHERE email = :email
             LIMIT 1"
        );
        $stmt->execute([':email' => $email]);
        $data = $stmt->fetch();

        return $data !== false ? $data : null;
    }

    public function findByNuptk(string $nuptk): ?array
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            "SELECT id, name, nidn, nuptk, email, username, password, role, unit, phone, avatar, signature_path, status
             FROM users
             WHERE nuptk = :nuptk
             LIMIT 1"
        );
        $stmt->execute([':nuptk' => $nuptk]);
        $data = $stmt->fetch();

        return $data !== false ? $data : null;
    }

    public function findByUsername(string $username): ?array
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            "SELECT id, name, nidn, nuptk, email, username, password, role, unit, phone, avatar, signature_path, status
             FROM users
             WHERE username = :username
             LIMIT 1"
        );
        $stmt->execute([':username' => $username]);
        $data = $stmt->fetch();

        return $data !== false ? $data : null;
    }

    public function findById(int $id): ?array
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch();

        return $data !== false ? $data : null;
    }

    public function getUsersColumns(): array
    {
        if ($this->usersColumns !== null) {
            return $this->usersColumns;
        }

        $pdo = db_pdo();
        $rows = $pdo->query("SHOW COLUMNS FROM users")->fetchAll() ?: [];
        $this->usersColumns = array_map(static fn (array $row): string => (string) ($row['Field'] ?? ''), $rows);

        return $this->usersColumns;
    }

    public function isEmailUsedByOther(string $email, int $excludeId): bool
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email AND id <> :id");
        $stmt->execute([
            ':email' => $email,
            ':id' => $excludeId,
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function isUsernameUsedByOther(string $username, int $excludeId): bool
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username AND id <> :id");
        $stmt->execute([
            ':username' => $username,
            ':id' => $excludeId,
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function updateDosenProfile(int $id, array $data): void
    {
        $availableColumns = $this->getUsersColumns();
        $updatableColumns = [
            'name',
            'nidn',
            'nuptk',
            'email',
            'username',
            'password',
            'faculty',
            'study_program',
            'unit',
            'phone',
            'gender',
            'google_scholar_id',
            'sinta_id',
            'avatar',
        ];

        $columns = array_values(array_filter(
            $updatableColumns,
            static fn (string $column): bool => in_array($column, $availableColumns, true)
        ));

        $sets = [];
        $params = [':id' => $id];

        foreach ($columns as $column) {
            if (!array_key_exists($column, $data)) {
                continue;
            }
            $sets[] = $column . ' = :' . $column;
            $params[':' . $column] = $this->normalizeUserColumnValue($column, $data[$column]);
        }

        if (in_array('updated_at', $availableColumns, true)) {
            $sets[] = 'updated_at = NOW()';
        }

        if ($sets === []) {
            return;
        }

        $pdo = db_pdo();
        // Dipakai juga saat akun Kepala LPPM berpindah ke mode dosen,
        // sehingga tidak boleh dibatasi hanya role='dosen'.
        $sql = 'UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    public function syncDosenNameReferences(int $userId, string $newName): void
    {
        $newName = trim($newName);
        if ($userId <= 0 || $newName === '') {
            return;
        }

        $pdo = db_pdo();
        $pdo->beginTransaction();

        try {
            $stmtPenelitian = $pdo->prepare(
                "UPDATE data_penelitian
                 SET ketua = :new_name, updated_at = NOW()
                 WHERE created_by = :user_id"
            );
            $stmtPenelitian->execute([
                ':new_name' => $newName,
                ':user_id' => $userId,
            ]);

            $stmtPengabdian = $pdo->prepare(
                "UPDATE data_pengabdian
                 SET ketua = :new_name, updated_at = NOW()
                 WHERE created_by = :user_id"
            );
            $stmtPengabdian->execute([
                ':new_name' => $newName,
                ':user_id' => $userId,
            ]);

            // Sinkronkan nama dosen di detail ajuan surat yang sudah tersimpan.
            $stmtSurat = $pdo->prepare(
                "UPDATE research_permit_letters rp
                 INNER JOIN letters l ON l.id = rp.letter_id
                 SET rp.researcher_name = :new_name,
                     rp.supervisor = :new_name
                 WHERE l.applicant_id = :user_id"
            );
            $stmtSurat->execute([
                ':new_name' => $newName,
                ':user_id' => $userId,
            ]);

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function getDosenUsers(): array
    {
        $pdo = db_pdo();
        $stmt = $pdo->query(
            "SELECT id, name, nidn, email, username, unit, phone
             FROM users
             WHERE role = 'dosen'
             ORDER BY name ASC"
        );

        return $stmt->fetchAll() ?: [];
    }

    public function getDosenUsersForManagement(): array
    {
        return $this->getDosenUsersForManagementFiltered();
    }

    public function getDosenUsersForManagementFiltered(array $filters = []): array
    {
        $pdo = db_pdo();
        $where = ["role = 'dosen'"];
        $params = [];

        $keyword = trim((string) ($filters['keyword'] ?? ''));
        if ($keyword !== '') {
            $where[] = '(name LIKE :keyword OR email LIKE :keyword OR username LIKE :keyword OR nuptk LIKE :keyword)';
            $params[':keyword'] = '%' . $keyword . '%';
        }

        $faculty = trim((string) ($filters['faculty'] ?? ''));
        if ($faculty !== '') {
            $where[] = 'faculty = :faculty';
            $params[':faculty'] = $faculty;
        }

        $studyProgram = trim((string) ($filters['study_program'] ?? ''));
        if ($studyProgram !== '') {
            $where[] = 'COALESCE(NULLIF(study_program, \'\'), unit) = :study_program';
            $params[':study_program'] = $studyProgram;
        }

        $sql = "SELECT
                id,
                name,
                nuptk,
                faculty,
                study_program,
                unit,
                google_scholar_id,
                sinta_id,
                email,
                phone,
                username
             FROM users
             WHERE " . implode(' AND ', $where) . "
             ORDER BY name ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    }

    public function getDosenNameSuggestions(?int $excludeId = null): array
    {
        $pdo = db_pdo();
        $sql =
            "SELECT id, name
             FROM users
             WHERE role = 'dosen'
               AND COALESCE(NULLIF(name, ''), '') <> ''";
        $params = [];

        if ($excludeId !== null && $excludeId > 0) {
            $sql .= ' AND id <> :exclude_id';
            $params[':exclude_id'] = $excludeId;
        }

        $sql .= ' ORDER BY name ASC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    }

    public function getDosenFilterOptions(): array
    {
        $pdo = db_pdo();

        $faculties = $pdo->query(
            "SELECT DISTINCT faculty
             FROM users
             WHERE role = 'dosen'
               AND COALESCE(NULLIF(faculty, ''), '') <> ''
             ORDER BY faculty ASC"
        )->fetchAll(PDO::FETCH_COLUMN) ?: [];

        $studyPrograms = $pdo->query(
            "SELECT DISTINCT COALESCE(NULLIF(study_program, ''), unit) AS study_program_label
             FROM users
             WHERE role = 'dosen'
               AND COALESCE(NULLIF(COALESCE(NULLIF(study_program, ''), unit), ''), '') <> ''
             ORDER BY study_program_label ASC"
        )->fetchAll(PDO::FETCH_COLUMN) ?: [];

        return [
            'faculties' => array_values(array_map('strval', $faculties)),
            'study_programs' => array_values(array_map('strval', $studyPrograms)),
        ];
    }

    public function getDosenSummary(): array
    {
        $pdo = db_pdo();
        $totalDosen = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'dosen'")->fetchColumn();
        $totalProdi = (int) $pdo->query("SELECT COUNT(DISTINCT COALESCE(NULLIF(study_program, ''), NULLIF(unit, ''))) FROM users WHERE role = 'dosen'")->fetchColumn();
        $totalWithNuptk = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'dosen' AND COALESCE(NULLIF(nuptk, ''), '') <> ''")->fetchColumn();

        return [
            'total_dosen' => $totalDosen,
            'total_prodi' => $totalProdi,
            'total_with_nuptk' => $totalWithNuptk,
        ];
    }

    public function getAdminManagementSummary(): array
    {
        $pdo = db_pdo();
        $totalDosen = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'dosen'")->fetchColumn();
        $totalProdi = (int) $pdo->query("SELECT COUNT(DISTINCT COALESCE(NULLIF(study_program, ''), NULLIF(unit, ''))) FROM users WHERE role = 'dosen'")->fetchColumn();
        $totalAdmin = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
        $totalKepala = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'kepala_lppm'")->fetchColumn();
        $dosenLengkap = (int) $pdo->query(
            "SELECT COUNT(*)
             FROM users
             WHERE role = 'dosen'
               AND COALESCE(NULLIF(name, ''), '') <> ''
               AND COALESCE(NULLIF(nuptk, ''), '') <> ''
               AND COALESCE(NULLIF(email, ''), '') <> ''
               AND COALESCE(NULLIF(username, ''), '') <> ''
               AND COALESCE(NULLIF(faculty, ''), '') <> ''
               AND COALESCE(NULLIF(study_program, ''), '') <> ''
               AND COALESCE(NULLIF(phone, ''), '') <> ''
               AND COALESCE(NULLIF(gender, ''), '') <> ''"
        )->fetchColumn();

        return [
            'total_dosen' => $totalDosen,
            'total_prodi' => $totalProdi,
            'total_admin' => $totalAdmin,
            'total_kepala' => $totalKepala,
            'dosen_lengkap' => $dosenLengkap,
        ];
    }

    public function findDosenById(int $id): ?array
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id AND role = 'dosen' LIMIT 1");
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch();

        return $data !== false ? $data : null;
    }

    public function isDosenProfileComplete(array $user): bool
    {
        $requiredFields = [
            'name',
            'nuptk',
            'email',
            'username',
            'faculty',
            'study_program',
            'phone',
            'gender',
        ];

        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $user)) {
                return false;
            }

            if (trim((string) $user[$field]) === '') {
                return false;
            }
        }

        return true;
    }

    public function deleteDosenById(int $id): void
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id AND role = 'dosen'");
        $stmt->execute([':id' => $id]);
    }

    public function getDosenDeletionBlockers(int $id): array
    {
        $pdo = db_pdo();

        return $this->fetchDeletionBlockers($pdo, $id);
    }

    private function fetchDeletionBlockers(PDO $pdo, int $id): array
    {
        $checks = [
            'surat_dibuat' => "SELECT COUNT(*) FROM letters WHERE created_by = :id",
            'surat_pemohon' => "SELECT COUNT(*) FROM letters WHERE applicant_id = :id",
            'proyek_penelitian' => "SELECT COUNT(*) FROM research_projects WHERE leader_id = :id",
            'persetujuan' => "SELECT COUNT(*) FROM approvals WHERE approver_id = :id",
        ];

        $result = [];
        foreach ($checks as $key => $sql) {
            try {
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':id' => $id]);
                $count = (int) $stmt->fetchColumn();
                if ($count > 0) {
                    $result[$key] = $count;
                }
            } catch (Throwable $e) {
                continue;
            }
        }

        return $result;
    }

    public function findRoleManagedUserById(int $id): ?array
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            "SELECT *
             FROM users
             WHERE id = :id
               AND role IN ('dosen', 'kepala_lppm', 'admin_lppm')
             LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch();

        return $data !== false ? $data : null;
    }

    public function promoteDosenToKepalaLppm(int $targetUserId): void
    {
        $pdo = db_pdo();
        $pdo->beginTransaction();

        try {
            $checkStmt = $pdo->prepare("SELECT id, role FROM users WHERE id = :id LIMIT 1");
            $checkStmt->execute([':id' => $targetUserId]);
            $target = $checkStmt->fetch();
            if ($target === false || (string) ($target['role'] ?? '') !== 'dosen') {
                throw new RuntimeException('User target harus ber-role dosen.');
            }

            $availableColumns = $this->getUsersColumns();
            $hasUpdatedAt = in_array('updated_at', $availableColumns, true);

            $demoteSql = "UPDATE users SET role = 'dosen'";
            if ($hasUpdatedAt) {
                $demoteSql .= ", updated_at = NOW()";
            }
            $demoteSql .= " WHERE role IN ('kepala_lppm', 'admin_lppm') AND id <> :id";
            $demoteStmt = $pdo->prepare($demoteSql);
            $demoteStmt->execute([':id' => $targetUserId]);

            $promoteSql = "UPDATE users SET role = 'kepala_lppm'";
            if ($hasUpdatedAt) {
                $promoteSql .= ", updated_at = NOW()";
            }
            $promoteSql .= " WHERE id = :id AND role = 'dosen'";
            $promoteStmt = $pdo->prepare($promoteSql);
            $promoteStmt->execute([':id' => $targetUserId]);

            if ((int) $promoteStmt->rowCount() !== 1) {
                throw new RuntimeException('Gagal mengubah role dosen menjadi Kepala LPPM.');
            }

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function demoteKepalaLppmToDosen(int $targetUserId): void
    {
        $pdo = db_pdo();
        $availableColumns = $this->getUsersColumns();
        $hasUpdatedAt = in_array('updated_at', $availableColumns, true);

        $sql = "UPDATE users SET role = 'dosen'";
        if ($hasUpdatedAt) {
            $sql .= ", updated_at = NOW()";
        }
        $sql .= " WHERE id = :id AND role IN ('kepala_lppm', 'admin_lppm')";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $targetUserId]);
        if ((int) $stmt->rowCount() !== 1) {
            throw new RuntimeException('Target bukan akun Kepala LPPM.');
        }
    }

    public function hasActiveKepalaLppm(?int $excludeUserId = null): bool
    {
        $pdo = db_pdo();
        $sql = "SELECT COUNT(*) FROM users WHERE role = 'kepala_lppm'";
        $params = [];

        if ($excludeUserId !== null && $excludeUserId > 0) {
            $sql .= ' AND id <> :exclude_id';
            $params[':exclude_id'] = $excludeUserId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function getAllChairmen(): array
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            "SELECT *
             FROM users
             WHERE role IN ('kepala_lppm', 'admin', 'admin_lppm')
             ORDER BY CASE
                WHEN role = 'kepala_lppm' THEN 0
                WHEN role = 'admin' THEN 1
                ELSE 2
             END, id ASC"
        );
        $stmt->execute();

        return $stmt->fetchAll() ?: [];
    }

    public function getChairmanById(int $id): ?array
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            "SELECT *
             FROM users
             WHERE id = :id AND role IN ('kepala_lppm', 'admin', 'admin_lppm')
             LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch();

        return $data !== false ? $data : null;
    }

    public function updateAdminProfile(int $id, array $data): void
    {
        $availableColumns = $this->getUsersColumns();
        $updatableColumns = [
            'name',
            'nidn',
            'nuptk',
            'gender',
            'email',
            'phone',
            'unit',
            'username',
            'password',
            'signature_path',
            'avatar',
            'jabatan',
            'position',
        ];

        $columns = array_values(array_filter(
            $updatableColumns,
            static fn (string $column): bool => in_array($column, $availableColumns, true)
        ));

        $sets = [];
        $params = [':id' => $id];

        foreach ($columns as $column) {
            if (!array_key_exists($column, $data)) {
                continue;
            }
            $sets[] = $column . ' = :' . $column;
            $params[':' . $column] = $this->normalizeUserColumnValue($column, $data[$column]);
        }

        if (in_array('updated_at', $availableColumns, true)) {
            $sets[] = 'updated_at = NOW()';
        }

        if ($sets === []) {
            return;
        }

        $pdo = db_pdo();
        $sql = 'UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = :id AND role IN (\'kepala_lppm\', \'admin\', \'admin_lppm\')';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    public function getDefaultChairman(): ?array
    {
        $chairmen = $this->getAllChairmen();

        return $chairmen[0] ?? null;
    }

    public function getDefaultAdmin(): ?array
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            "SELECT *
             FROM users
             WHERE role = 'admin'
             ORDER BY id ASC
             LIMIT 1"
        );
        $stmt->execute();
        $data = $stmt->fetch();

        return $data !== false ? $data : null;
    }

    public function updateSignaturePath(int $id, string $path): void
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            "UPDATE users
             SET signature_path = :signature_path, updated_at = NOW()
             WHERE id = :id AND role IN ('kepala_lppm', 'admin', 'admin_lppm')"
        );
        $stmt->execute([
            ':id' => $id,
            ':signature_path' => $path,
        ]);
    }

    public function updateIdentifier(int $id, string $identifier): void
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            "UPDATE users
             SET phone = :phone, updated_at = NOW()
             WHERE id = :id AND role IN ('kepala_lppm', 'admin', 'admin_lppm')"
        );
        $stmt->execute([
            ':id' => $id,
            ':phone' => $identifier,
        ]);
    }

    public function createPublicDosen(array $data): int
    {
        $availableColumns = $this->getUsersColumns();
        $payload = [
            'name' => $this->normalizeUserColumnValue('name', $data['name'] ?? ''),
            'nidn' => $this->normalizeUserColumnValue('nidn', $data['nidn'] ?? ''),
            'nuptk' => $this->normalizeUserColumnValue('nuptk', $data['nuptk'] ?? ''),
            'email' => $this->normalizeUserColumnValue('email', $data['email'] ?? ''),
            'username' => $this->normalizeUserColumnValue('username', $data['username'] ?? ''),
            'password' => (string) ($data['password'] ?? ''),
            'role' => 'dosen',
            'faculty' => $this->normalizeUserColumnValue('faculty', $data['faculty'] ?? ''),
            'study_program' => $this->normalizeUserColumnValue('study_program', $data['study_program'] ?? ''),
            'unit' => $this->normalizeUserColumnValue('unit', $data['unit'] ?? $data['study_program'] ?? ''),
            'phone' => $this->normalizeUserColumnValue('phone', $data['phone'] ?? ''),
            'gender' => $this->normalizeUserColumnValue('gender', $data['gender'] ?? ''),
            'status' => $this->normalizeUserColumnValue('status', $data['status'] ?? 'aktif'),
        ];

        $insertableColumns = array_values(array_filter(
            array_keys($payload),
            static fn (string $column): bool => in_array($column, $availableColumns, true)
        ));

        if ($insertableColumns === []) {
            throw new RuntimeException('Kolom users untuk registrasi tidak ditemukan.');
        }

        $placeholders = array_map(static fn (string $column): string => ':' . $column, $insertableColumns);
        $params = [];
        foreach ($insertableColumns as $column) {
            $params[':' . $column] = $payload[$column];
        }

        $timestampColumns = [];
        foreach (['created_at', 'updated_at'] as $timestampColumn) {
            if (in_array($timestampColumn, $availableColumns, true)) {
                $timestampColumns[] = $timestampColumn;
            }
        }

        $columnsSql = implode(', ', $insertableColumns);
        $valuesSql = implode(', ', $placeholders);
        if ($timestampColumns !== []) {
            $columnsSql .= ', ' . implode(', ', $timestampColumns);
            $valuesSql .= ', ' . implode(', ', array_fill(0, count($timestampColumns), 'NOW()'));
        }

        $pdo = db_pdo();
        $stmt = $pdo->prepare('INSERT INTO users (' . $columnsSql . ') VALUES (' . $valuesSql . ')');
        $stmt->execute($params);

        return (int) $pdo->lastInsertId();
    }

    public function resetPasswordByEmail(string $email, string $hashedPassword): void
    {
        $availableColumns = $this->getUsersColumns();
        $sets = ['password = :password'];
        if (in_array('updated_at', $availableColumns, true)) {
            $sets[] = 'updated_at = NOW()';
        }

        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'UPDATE users SET ' . implode(', ', $sets) . ' WHERE email = :email LIMIT 1'
        );
        $stmt->execute([
            ':password' => $hashedPassword,
            ':email' => trim($email),
        ]);
    }

    public function updatePasswordById(int $id, string $hashedPassword): void
    {
        $availableColumns = $this->getUsersColumns();
        $sets = ['password = :password'];
        if (in_array('updated_at', $availableColumns, true)) {
            $sets[] = 'updated_at = NOW()';
        }

        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = :id LIMIT 1'
        );
        $stmt->execute([
            ':password' => $hashedPassword,
            ':id' => $id,
        ]);
    }

    public function verifyPasswordById(int $id, string $plainPassword): bool
    {
        if ($id <= 0 || trim($plainPassword) === '') {
            return false;
        }

        $user = $this->findById($id);
        if ($user === null) {
            return false;
        }

        $hash = (string) ($user['password'] ?? '');
        if ($hash === '') {
            return false;
        }

        return password_verify($plainPassword, $hash);
    }

    private function normalizeUserColumnValue(string $column, mixed $value): mixed
    {
        $stringValue = trim((string) $value);

        if ($column === 'email') {
            return strtolower($stringValue);
        }

        if ($column === 'username') {
            return strtolower($stringValue);
        }

        if ($column === 'status') {
            $normalized = strtolower($stringValue);
            return $normalized === '' ? 'aktif' : $normalized;
        }

        return $stringValue;
    }
}

