<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/UserModel.php';
require_once __DIR__ . '/../Helpers/ActivityLogHelper.php';

class UserController extends BaseController
{
    private UserModel $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new UserModel();
    }

    public function index(): void
    {
        $chairman = $this->userModel->getDefaultChairman();
        $hasActiveKepala = is_array($chairman)
            && strtolower(trim((string) ($chairman['role'] ?? ''))) === 'kepala_lppm';
        $filters = [
            'keyword' => trim((string) ($_GET['keyword'] ?? '')),
            'faculty' => trim((string) ($_GET['faculty'] ?? '')),
            'study_program' => trim((string) ($_GET['study_program'] ?? '')),
        ];
        $filterOptions = $this->userModel->getDosenFilterOptions();

        if ($filters['faculty'] !== '' && !in_array($filters['faculty'], $filterOptions['faculties'], true)) {
            $filters['faculty'] = '';
        }
        if ($filters['study_program'] !== '' && !in_array($filters['study_program'], $filterOptions['study_programs'], true)) {
            $filters['study_program'] = '';
        }

        $this->render('users/index', [
            'pageTitle' => 'Pengguna',
            'chairman' => $chairman,
            'hasActiveKepala' => $hasActiveKepala,
            'dosenUsers' => $this->userModel->getDosenUsersForManagementFiltered($filters),
            'summary' => $this->userModel->getDosenSummary(),
            'userFilters' => $filters,
            'filterOptions' => $filterOptions,
            'successMessage' => $_GET['success'] ?? null,
            'errorMessage' => $_GET['error'] ?? null,
        ]);
    }

    public function showDosen(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->redirectToPath('pengguna', ['error' => 'Data dosen tidak ditemukan.']);
        }

        $dosen = $this->userModel->findDosenById($id);
        if ($dosen === null) {
            $this->redirectToPath('pengguna', ['error' => 'Data dosen tidak ditemukan.']);
        }

        $this->render('users/dosen_profile', [
            'pageTitle' => 'Detail Profil Dosen',
            'dosen' => $dosen,
            'viewMode' => 'detail',
            'availableColumns' => $this->userModel->getUsersColumns(),
            'successMessage' => $_GET['success'] ?? null,
            'errorMessage' => $_GET['error'] ?? null,
        ]);
    }

    public function editDosen(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->redirectToPath('pengguna', ['error' => 'Data dosen tidak ditemukan.']);
        }

        $dosen = $this->userModel->findDosenById($id);
        if ($dosen === null) {
            $this->redirectToPath('pengguna', ['error' => 'Data dosen tidak ditemukan.']);
        }

        $this->render('users/dosen_profile', [
            'pageTitle' => 'Edit Profil Dosen',
            'dosen' => $dosen,
            'viewMode' => 'edit',
            'availableColumns' => $this->userModel->getUsersColumns(),
            'successMessage' => $_GET['success'] ?? null,
            'errorMessage' => $_GET['error'] ?? null,
        ]);
    }

    public function updateDosen(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToPath('pengguna');
        }

        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->redirectToPath('pengguna', ['error' => 'Data dosen tidak valid.']);
        }

        try {
            $existingUser = $this->userModel->findDosenById($id);
            if ($existingUser === null) {
                throw new RuntimeException('Data dosen tidak ditemukan.');
            }

            $name = trim((string) ($_POST['name'] ?? ''));
            $nidn = trim((string) ($_POST['nidn'] ?? ''));
            $nuptk = trim((string) ($_POST['nuptk'] ?? ''));
            $email = trim((string) ($_POST['email'] ?? ''));
            $username = trim((string) ($_POST['username'] ?? ''));
            $faculty = trim((string) ($_POST['faculty'] ?? ''));
            $studyProgram = trim((string) ($_POST['study_program'] ?? ''));
            $unit = trim((string) ($_POST['unit'] ?? $studyProgram));
            $phone = trim((string) ($_POST['phone'] ?? ''));
            $gender = trim((string) ($_POST['gender'] ?? ''));
            $googleScholarId = trim((string) ($_POST['google_scholar_id'] ?? ''));
            $sintaId = trim((string) ($_POST['sinta_id'] ?? ''));
            $newPassword = (string) ($_POST['new_password'] ?? '');
            $currentPassword = (string) ($_POST['current_password'] ?? '');

            $validation = $this->validatePayload(
                [
                    'name' => $name,
                    'nidn' => $nidn,
                    'nuptk' => $nuptk,
                    'email' => $email,
                    'username' => $username,
                    'faculty' => $faculty,
                    'study_program' => $studyProgram,
                    'phone' => $phone,
                    'gender' => $gender,
                    'google_scholar_id' => $googleScholarId,
                    'sinta_id' => $sintaId,
                ],
                [
                    'name' => 'required|min_length[3]|max_length[120]',
                    'nidn' => 'permit_empty|numeric|min_length[6]|max_length[30]',
                    'nuptk' => 'required|numeric|min_length[6]|max_length[30]',
                    'email' => 'required|valid_email|max_length[160]',
                    'username' => 'required|regex_match[/^[A-Za-z0-9_.-]+$/]|min_length[3]|max_length[50]',
                    'faculty' => 'required|max_length[150]',
                    'study_program' => 'required|max_length[150]',
                    'phone' => 'required|regex_match[/^[0-9+\\-\\s]{8,25}$/]',
                    'gender' => 'required|in_list[Laki-laki,Perempuan]',
                    'google_scholar_id' => 'permit_empty|valid_url_strict[https]|max_length[255]',
                    'sinta_id' => 'permit_empty|valid_url_strict[https]|max_length[255]',
                ],
                [
                    'username' => [
                        'regex_match' => 'Username hanya boleh huruf, angka, titik, garis bawah, atau tanda minus.',
                    ],
                    'phone' => [
                        'regex_match' => 'Nomor HP hanya boleh angka dan simbol + - spasi.',
                    ],
                ]
            );
            if (!$validation['valid']) {
                throw new RuntimeException($this->firstValidationError($validation['errors'], 'Data profil dosen tidak valid.'));
            }

            if ($this->userModel->isEmailUsedByOther($email, $id)) {
                throw new RuntimeException('Email sudah dipakai akun lain.');
            }

            if ($this->userModel->isUsernameUsedByOther($username, $id)) {
                throw new RuntimeException('Username sudah dipakai akun lain.');
            }

            if ($googleScholarId !== '' && !filter_var($googleScholarId, FILTER_VALIDATE_URL)) {
                throw new RuntimeException('Link Google Scholar tidak valid.');
            }

            if ($sintaId !== '' && !filter_var($sintaId, FILTER_VALIDATE_URL)) {
                throw new RuntimeException('Link Sinta tidak valid.');
            }

            $updateData = [
                'name' => $name,
                'nidn' => $nidn,
                'nuptk' => $nuptk,
                'email' => $email,
                'username' => $username,
                'faculty' => $faculty,
                'study_program' => $studyProgram,
                'unit' => $unit,
                'phone' => $phone,
                'gender' => $gender,
                'google_scholar_id' => $googleScholarId,
                'sinta_id' => $sintaId,
            ];

            if ($newPassword !== '') {
                if (!$this->isStrongPassword($newPassword)) {
                    throw new RuntimeException('Password baru minimal 8 karakter dan wajib mengandung huruf besar, huruf kecil, angka, serta simbol.');
                }
                if (!$this->userModel->verifyPasswordById($id, $currentPassword)) {
                    throw new RuntimeException('Password saat ini tidak sesuai.');
                }
                $updateData['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
            }

            $this->userModel->updateDosenProfile($id, $updateData);
            $this->userModel->syncDosenNameReferences($id, $name);
            logActivity('pengguna', 'Memperbarui profil dosen: ' . $name, $id);

            $this->redirectToPath('pengguna/dosen/' . $id . '/edit', ['success' => 'Profil dosen berhasil diperbarui.']);
        } catch (Throwable $e) {
            $this->redirectToPath('pengguna/dosen/' . $id . '/edit', ['error' => $e->getMessage()]);
        }
    }

    public function deleteDosen(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToPath('pengguna');
        }

        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->redirectToPath('pengguna', ['error' => 'Data dosen tidak valid.']);
        }

        try {
            $dosen = $this->userModel->findDosenById($id);
            if ($dosen === null) {
                throw new RuntimeException('Data dosen tidak ditemukan.');
            }

            $blockers = $this->userModel->getDosenDeletionBlockers($id);
            if ($blockers !== []) {
                $parts = [];
                if (!empty($blockers['surat_dibuat'])) {
                    $parts[] = $blockers['surat_dibuat'] . ' surat dibuat';
                }
                if (!empty($blockers['surat_pemohon'])) {
                    $parts[] = $blockers['surat_pemohon'] . ' surat diajukan';
                }
                if (!empty($blockers['proyek_penelitian'])) {
                    $parts[] = $blockers['proyek_penelitian'] . ' proyek penelitian';
                }
                if (!empty($blockers['persetujuan'])) {
                    $parts[] = $blockers['persetujuan'] . ' data persetujuan';
                }

                $detail = $parts !== [] ? ' Masih terhubung dengan: ' . implode(', ', $parts) . '.' : '';
                throw new RuntimeException('Pengguna dosen tidak dapat dihapus karena masih memiliki data yang terhubung.' . $detail);
            }

            $this->userModel->deleteDosenById($id);
            logActivity('pengguna', 'Menghapus pengguna dosen: ' . (string) ($dosen['name'] ?? ('ID ' . $id)), $id);
            $this->redirectToPath('pengguna', ['success' => 'Pengguna dosen berhasil dihapus.']);
        } catch (Throwable $e) {
            $this->redirectToPath('pengguna', ['error' => $e->getMessage()]);
        }
    }

    public function changeRole(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToPath('pengguna');
        }

        $targetId = (int) ($_POST['id'] ?? 0);
        $action = trim((string) ($_POST['action'] ?? ''));

        if ($targetId <= 0) {
            $this->redirectToPath('pengguna', ['error' => 'Target pengguna tidak valid.']);
        }

        try {
            $targetUser = $this->userModel->findRoleManagedUserById($targetId);
            if ($targetUser === null) {
                throw new RuntimeException('Pengguna tidak ditemukan atau role tidak bisa diubah.');
            }

            $targetName = (string) ($targetUser['name'] ?? ('ID ' . $targetId));

            if ($action === 'promote_kepala') {
                if ((string) ($targetUser['role'] ?? '') !== 'dosen') {
                    throw new RuntimeException('Hanya akun dosen yang dapat dijadikan Kepala LPPM.');
                }

                if ($this->userModel->hasActiveKepalaLppm($targetId)) {
                    throw new RuntimeException('Sudah ada Kepala LPPM aktif. Lepas jabatan Kepala LPPM saat ini terlebih dahulu.');
                }

                $this->userModel->promoteDosenToKepalaLppm($targetId);
                logActivity('pengguna', 'Mengubah role menjadi Kepala LPPM: ' . $targetName, $targetId);
                $this->redirectToPath('pengguna', ['success' => 'Role ' . $targetName . ' berhasil diubah menjadi Kepala LPPM.']);
            }

            if ($action === 'demote_dosen') {
                if (!in_array((string) ($targetUser['role'] ?? ''), ['kepala_lppm', 'admin_lppm'], true)) {
                    throw new RuntimeException('Hanya akun Kepala LPPM yang dapat dikembalikan menjadi dosen.');
                }

                $this->userModel->demoteKepalaLppmToDosen($targetId);
                logActivity('pengguna', 'Mengubah role Kepala LPPM menjadi dosen: ' . $targetName, $targetId);
                $this->redirectToPath('pengguna', ['success' => 'Role ' . $targetName . ' berhasil dikembalikan menjadi dosen.']);
            }

            throw new RuntimeException('Aksi role tidak dikenali.');
        } catch (Throwable $e) {
            $this->redirectToPath('pengguna', ['error' => $e->getMessage()]);
        }
    }

    public function profile(): void
    {
        $chairman = null;
        $currentUserId = (int) (authUserId() ?? 0);
        if ($currentUserId > 0) {
            $loggedInUser = $this->userModel->findById($currentUserId);
            if ($loggedInUser !== null && isAdminPanelRole((string) ($loggedInUser['role'] ?? ''))) {
                $chairman = $loggedInUser;
            }
        }

        if ($chairman === null) {
            $chairman = $this->userModel->getDefaultChairman();
        }

        $this->render('users/profile', [
            'pageTitle' => 'Profil Ketua LPPM',
            'chairman' => $chairman,
            'availableColumns' => $this->userModel->getUsersColumns(),
            'successMessage' => $_GET['success'] ?? null,
            'errorMessage' => $_GET['error'] ?? null,
        ]);
    }

    public function uploadSignature(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToPath('profil-admin');
        }

        try {
            $authUserId = (int) (authUserId() ?? 0);
            $chairmanId = $authUserId > 0 ? $authUserId : (int) ($_POST['chairman_id'] ?? 0);
            $chairman = $this->userModel->getChairmanById($chairmanId);
            $availableColumns = $this->userModel->getUsersColumns();

            if ($chairman === null) {
                throw new RuntimeException('User kepala LPPM/admin tidak ditemukan.');
            }

            $userRoleRaw = strtolower(trim((string) ($chairman['role'] ?? '')));
            $userRole = $userRoleRaw === 'admin_lppm' ? 'admin' : $userRoleRaw;
            $isAdminProfile = $userRole === 'admin';

            $namaLengkap = trim((string) ($_POST['nama_lengkap'] ?? ''));
            $email = trim((string) ($_POST['email'] ?? ''));
            $username = trim((string) ($_POST['username'] ?? ''));
            $passwordBaru = (string) ($_POST['password'] ?? '');
            $passwordSaatIni = (string) ($_POST['current_password'] ?? '');

            $jenisKelamin = trim((string) ($_POST['jenis_kelamin'] ?? ''));
            $nidn = trim((string) ($_POST['nidn'] ?? ''));
            $nuptk = trim((string) ($_POST['nuptk'] ?? ''));
            $noHp = trim((string) ($_POST['no_hp'] ?? ''));
            $jabatan = trim((string) ($_POST['jabatan'] ?? ''));

            if ($namaLengkap === '' || $email === '' || $username === '') {
                throw new RuntimeException('Nama lengkap, email, dan username wajib diisi.');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Format email tidak valid.');
            }

            if ($this->userModel->isEmailUsedByOther($email, (int) $chairman['id'])) {
                throw new RuntimeException('Email sudah dipakai akun lain.');
            }

            if ($this->userModel->isUsernameUsedByOther($username, (int) $chairman['id'])) {
                throw new RuntimeException('Username sudah dipakai akun lain.');
            }

            $updateData = [
                'name' => $namaLengkap,
                'email' => $email,
                'username' => $username,
            ];

            if (!$isAdminProfile) {
                if ($jenisKelamin === '' || $noHp === '' || $jabatan === '') {
                    throw new RuntimeException('Semua field wajib diisi kecuali NIDN, password, dan tanda tangan digital.');
                }

                if (in_array('nuptk', $availableColumns, true) && $nuptk === '') {
                    throw new RuntimeException('NUPTK wajib diisi.');
                }

                if (!in_array($jenisKelamin, ['Laki-laki', 'Perempuan'], true)) {
                    throw new RuntimeException('Jenis kelamin harus Laki-laki atau Perempuan.');
                }

                $updateData['gender'] = $jenisKelamin;
                $updateData['phone'] = $noHp;

                if (in_array('nidn', $availableColumns, true)) {
                    $updateData['nidn'] = $nidn;
                }
                if (in_array('nuptk', $availableColumns, true)) {
                    $updateData['nuptk'] = $nuptk;
                }
                if (in_array('jabatan', $availableColumns, true)) {
                    $updateData['jabatan'] = $jabatan;
                } elseif (in_array('position', $availableColumns, true)) {
                    $updateData['position'] = $jabatan;
                }
            }

            if ($passwordBaru !== '') {
                if (!$this->isStrongPassword($passwordBaru)) {
                    throw new RuntimeException('Password baru minimal 8 karakter dan wajib mengandung huruf besar, huruf kecil, angka, serta simbol.');
                }
                if (!$this->userModel->verifyPasswordById($chairmanId, $passwordSaatIni)) {
                    throw new RuntimeException('Password saat ini tidak sesuai.');
                }
                $updateData['password'] = password_hash($passwordBaru, PASSWORD_DEFAULT);
            }

            if (isset($_FILES['avatar_file']) && is_array($_FILES['avatar_file'])) {
                $avatarFile = $_FILES['avatar_file'];
                $avatarError = (int) ($avatarFile['error'] ?? UPLOAD_ERR_NO_FILE);
                if ($avatarError !== UPLOAD_ERR_NO_FILE) {
                    if ($avatarError !== UPLOAD_ERR_OK) {
                        throw new RuntimeException('Upload avatar gagal. Silakan coba lagi.');
                    }

                    $tmpPath = (string) ($avatarFile['tmp_name'] ?? '');
                    $size = (int) ($avatarFile['size'] ?? 0);
                    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
                        throw new RuntimeException('File avatar tidak valid.');
                    }
                    if ($size <= 0 || $size > 2 * 1024 * 1024) {
                        throw new RuntimeException('Ukuran avatar maksimal 2 MB.');
                    }

                    $mime = $this->detectMimeType($tmpPath);
                    $allowedMimes = [
                        'image/jpeg' => 'jpg',
                        'image/png' => 'png',
                        'image/webp' => 'webp',
                    ];
                    if (!isset($allowedMimes[$mime])) {
                        throw new RuntimeException('Format avatar harus JPG, PNG, atau WEBP.');
                    }

                    $ext = $allowedMimes[$mime];
                    $avatarDir = __DIR__ . '/../../storage/uploads/avatars';
                    if (!is_dir($avatarDir) && !mkdir($avatarDir, 0755, true) && !is_dir($avatarDir)) {
                        throw new RuntimeException('Folder avatar tidak dapat dibuat.');
                    }

                    $newFileName = 'avatar-admin-' . $chairmanId . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(3)) . '.' . $ext;
                    $targetPath = $avatarDir . '/' . $newFileName;
                    if (!move_uploaded_file($tmpPath, $targetPath)) {
                        throw new RuntimeException('Gagal menyimpan file avatar.');
                    }
                    @chmod($targetPath, 0640);

                    $oldAvatar = (string) ($chairman['avatar'] ?? '');
                    if ($oldAvatar !== '') {
                        $oldPath = $avatarDir . '/' . $oldAvatar;
                        if (is_file($oldPath)) {
                            @unlink($oldPath);
                        }
                    }

                    $updateData['avatar'] = $newFileName;
                }
            }

            if (!$isAdminProfile && isset($_FILES['ttd_digital']) && is_array($_FILES['ttd_digital'])) {
                $file = $_FILES['ttd_digital'];
                $fileError = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
                if ($fileError !== UPLOAD_ERR_NO_FILE) {
                    if ($fileError !== UPLOAD_ERR_OK) {
                        throw new RuntimeException('Upload tanda tangan gagal. Silakan coba lagi.');
                    }

                    $tmpPath = (string) ($file['tmp_name'] ?? '');
                    $size = (int) ($file['size'] ?? 0);
                    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
                        throw new RuntimeException('File tanda tangan digital tidak valid.');
                    }
                    if ($size <= 0 || $size > 2 * 1024 * 1024) {
                        throw new RuntimeException('Ukuran tanda tangan digital maksimal 2 MB.');
                    }
                    $mime = $this->detectMimeType($tmpPath);
                    if ($mime !== 'image/png') {
                        throw new RuntimeException('Format tanda tangan digital harus PNG.');
                    }

                    $ext = strtolower((string) pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
                    if ($ext !== 'png') {
                        throw new RuntimeException('Ekstensi tanda tangan digital harus .png');
                    }

                    $storageDir = __DIR__ . '/../../storage/uploads/signatures';
                    if (!is_dir($storageDir) && !mkdir($storageDir, 0755, true) && !is_dir($storageDir)) {
                        throw new RuntimeException('Folder signature tidak dapat dibuat.');
                    }

                    $filename = 'signature-ketua-' . $chairmanId . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.png';
                    $fullPath = $storageDir . '/' . $filename;
                    $relativePath = 'storage/uploads/signatures/' . $filename;

                    if (!move_uploaded_file($tmpPath, $fullPath)) {
                        throw new RuntimeException('Gagal memindahkan file signature.');
                    }
                    @chmod($fullPath, 0640);

                    $oldSignature = trim((string) ($chairman['signature_path'] ?? ''));
                    if ($oldSignature !== '') {
                        $oldFullPath = __DIR__ . '/../../' . ltrim(str_replace('\\', '/', $oldSignature), '/');
                        if (is_file($oldFullPath)) {
                            @unlink($oldFullPath);
                        }
                    }

                    $updateData['signature_path'] = $relativePath;
                }
            } elseif (!$isAdminProfile && isset($_FILES['signature_file']) && is_array($_FILES['signature_file'])) {
                // Backward compatibility untuk nama field lama.
                $file = $_FILES['signature_file'];
                if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                    $tmpPath = (string) ($file['tmp_name'] ?? '');
                    $size = (int) ($file['size'] ?? 0);
                    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
                        throw new RuntimeException('File tanda tangan digital tidak valid.');
                    }
                    if ($size <= 0 || $size > 2 * 1024 * 1024) {
                        throw new RuntimeException('Ukuran tanda tangan digital maksimal 2 MB.');
                    }
                    $mime = $this->detectMimeType($tmpPath);
                    $ext = strtolower((string) pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
                    if ($mime !== 'image/png' || $ext !== 'png') {
                        throw new RuntimeException('Format tanda tangan digital harus PNG.');
                    }

                    $storageDir = __DIR__ . '/../../storage/uploads/signatures';
                    if (!is_dir($storageDir) && !mkdir($storageDir, 0755, true) && !is_dir($storageDir)) {
                        throw new RuntimeException('Folder signature tidak dapat dibuat.');
                    }

                    $filename = 'signature-ketua-' . $chairmanId . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.png';
                    $fullPath = $storageDir . '/' . $filename;
                    $relativePath = 'storage/uploads/signatures/' . $filename;
                    if (!move_uploaded_file($tmpPath, $fullPath)) {
                        throw new RuntimeException('Gagal memindahkan file signature.');
                    }
                    @chmod($fullPath, 0640);

                    $oldSignature = trim((string) ($chairman['signature_path'] ?? ''));
                    if ($oldSignature !== '') {
                        $oldFullPath = __DIR__ . '/../../' . ltrim(str_replace('\\', '/', $oldSignature), '/');
                        if (is_file($oldFullPath)) {
                            @unlink($oldFullPath);
                        }
                    }

                    $updateData['signature_path'] = $relativePath;
                }
            }

            $this->userModel->updateAdminProfile($chairmanId, $updateData);
            logActivity('profil', 'Memperbarui profil kepala/admin: ' . $namaLengkap, $chairmanId);

            ensureSessionStarted();
            if (isset($_SESSION['auth_user']) && is_array($_SESSION['auth_user']) && (int) ($_SESSION['auth_user']['id'] ?? 0) === $chairmanId) {
                $_SESSION['auth_user']['name'] = $namaLengkap;
                $_SESSION['auth_user']['email'] = $email;
                $_SESSION['auth_user']['username'] = $username;
                if (!$isAdminProfile) {
                    $_SESSION['auth_user']['nidn'] = $nidn;
                    $_SESSION['auth_user']['nuptk'] = $nuptk;
                    $_SESSION['auth_user']['gender'] = $jenisKelamin;
                }
                if (isset($updateData['avatar'])) {
                    $_SESSION['auth_user']['avatar'] = (string) $updateData['avatar'];
                }
            }

            $this->redirectToPath('profil-admin', ['success' => 'Profil berhasil diperbarui.']);
        } catch (Throwable $e) {
            $this->redirectToPath('profil-admin', ['error' => $e->getMessage()]);
        }
    }

    public function myProfile(): void
    {
        $user = $this->userModel->findById((int) (authUserId() ?? 0));
        if ($user === null) {
            $this->redirectToPath('dashboard-dosen');
        }

        $this->render('users/my_profile', [
            'pageTitle' => 'Profil',
            'user' => $user,
            'successMessage' => $_GET['success'] ?? null,
            'infoMessage' => $_GET['info'] ?? null,
            'errorMessage' => $_GET['error'] ?? null,
            'availableColumns' => $this->userModel->getUsersColumns(),
            'isProfileComplete' => $this->userModel->isDosenProfileComplete($user),
        ]);
    }

    public function updateMyProfile(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToPath('profil');
        }

        $userId = (int) (authUserId() ?? 0);
        if ($userId <= 0) {
            $this->redirectToPath('login');
        }

        try {
            $existingUser = $this->userModel->findById($userId);
            if ($existingUser === null) {
                throw new RuntimeException('Data user tidak ditemukan.');
            }

            $name = trim((string) ($_POST['name'] ?? ''));
            $nidn = trim((string) ($_POST['nidn'] ?? ''));
            $nuptk = trim((string) ($_POST['nuptk'] ?? ''));
            $email = trim((string) ($_POST['email'] ?? ''));
            $username = trim((string) ($_POST['username'] ?? ''));
            $faculty = trim((string) ($_POST['faculty'] ?? ''));
            $studyProgram = trim((string) ($_POST['study_program'] ?? ''));
            $unit = trim((string) ($_POST['unit'] ?? $studyProgram));
            $phone = trim((string) ($_POST['phone'] ?? ''));
            $gender = trim((string) ($_POST['gender'] ?? ''));
            $googleScholarId = trim((string) ($_POST['google_scholar_id'] ?? ''));
            $sintaId = trim((string) ($_POST['sinta_id'] ?? ''));
            $newPassword = (string) ($_POST['new_password'] ?? '');
            $currentPassword = (string) ($_POST['current_password'] ?? '');

            $validation = $this->validatePayload(
                [
                    'name' => $name,
                    'nidn' => $nidn,
                    'nuptk' => $nuptk,
                    'email' => $email,
                    'username' => $username,
                    'faculty' => $faculty,
                    'study_program' => $studyProgram,
                    'phone' => $phone,
                    'gender' => $gender,
                    'google_scholar_id' => $googleScholarId,
                    'sinta_id' => $sintaId,
                ],
                [
                    'name' => 'required|min_length[3]|max_length[120]',
                    'nidn' => 'permit_empty|numeric|min_length[6]|max_length[30]',
                    'nuptk' => 'required|numeric|min_length[6]|max_length[30]',
                    'email' => 'required|valid_email|max_length[160]',
                    'username' => 'required|regex_match[/^[A-Za-z0-9_.-]+$/]|min_length[3]|max_length[50]',
                    'faculty' => 'required|max_length[150]',
                    'study_program' => 'required|max_length[150]',
                    'phone' => 'required|regex_match[/^[0-9+\\-\\s]{8,25}$/]',
                    'gender' => 'required|in_list[Laki-laki,Perempuan]',
                    'google_scholar_id' => 'permit_empty|valid_url_strict[https]|max_length[255]',
                    'sinta_id' => 'permit_empty|valid_url_strict[https]|max_length[255]',
                ],
                [
                    'username' => [
                        'regex_match' => 'Username hanya boleh huruf, angka, titik, garis bawah, atau tanda minus.',
                    ],
                    'phone' => [
                        'regex_match' => 'Nomor HP hanya boleh angka dan simbol + - spasi.',
                    ],
                ]
            );
            if (!$validation['valid']) {
                throw new RuntimeException($this->firstValidationError($validation['errors'], 'Data profil dosen tidak valid.'));
            }

            if ($this->userModel->isEmailUsedByOther($email, $userId)) {
                throw new RuntimeException('Email sudah dipakai akun lain.');
            }

            if ($this->userModel->isUsernameUsedByOther($username, $userId)) {
                throw new RuntimeException('Username sudah dipakai akun lain.');
            }

            if ($googleScholarId !== '' && !filter_var($googleScholarId, FILTER_VALIDATE_URL)) {
                throw new RuntimeException('Link Google Scholar tidak valid.');
            }

            if ($sintaId !== '' && !filter_var($sintaId, FILTER_VALIDATE_URL)) {
                throw new RuntimeException('Link Sinta tidak valid.');
            }

            $updateData = [
                'name' => $name,
                'nidn' => $nidn,
                'nuptk' => $nuptk,
                'email' => $email,
                'username' => $username,
                'faculty' => $faculty,
                'study_program' => $studyProgram,
                'unit' => $unit,
                'phone' => $phone,
                'gender' => $gender,
                'google_scholar_id' => $googleScholarId,
                'sinta_id' => $sintaId,
            ];

            if ($newPassword !== '') {
                if (!$this->isStrongPassword($newPassword)) {
                    throw new RuntimeException('Password baru minimal 8 karakter dan wajib mengandung huruf besar, huruf kecil, angka, serta simbol.');
                }
                if (!$this->userModel->verifyPasswordById($userId, $currentPassword)) {
                    throw new RuntimeException('Password saat ini tidak sesuai.');
                }
                $updateData['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
            }

            if (isset($_FILES['avatar_file']) && is_array($_FILES['avatar_file'])) {
                $avatarFile = $_FILES['avatar_file'];
                $avatarError = (int) ($avatarFile['error'] ?? UPLOAD_ERR_NO_FILE);
                if ($avatarError !== UPLOAD_ERR_NO_FILE) {
                    if ($avatarError !== UPLOAD_ERR_OK) {
                        throw new RuntimeException('Upload avatar gagal. Silakan coba lagi.');
                    }

                    $tmpPath = (string) ($avatarFile['tmp_name'] ?? '');
                    $size = (int) ($avatarFile['size'] ?? 0);
                    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
                        throw new RuntimeException('File avatar tidak valid.');
                    }
                    if ($size <= 0 || $size > 2 * 1024 * 1024) {
                        throw new RuntimeException('Ukuran avatar maksimal 2 MB.');
                    }

                    $mime = $this->detectMimeType($tmpPath);
                    $allowedMimes = [
                        'image/jpeg' => 'jpg',
                        'image/png' => 'png',
                        'image/webp' => 'webp',
                    ];
                    if (!isset($allowedMimes[$mime])) {
                        throw new RuntimeException('Format avatar harus JPG, PNG, atau WEBP.');
                    }

                    $ext = $allowedMimes[$mime];
                    $avatarDir = __DIR__ . '/../../storage/uploads/avatars';
                    if (!is_dir($avatarDir) && !mkdir($avatarDir, 0755, true) && !is_dir($avatarDir)) {
                        throw new RuntimeException('Folder avatar tidak dapat dibuat.');
                    }

                    $newFileName = 'avatar-dosen-' . $userId . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(3)) . '.' . $ext;
                    $targetPath = $avatarDir . '/' . $newFileName;
                    if (!move_uploaded_file($tmpPath, $targetPath)) {
                        throw new RuntimeException('Gagal menyimpan file avatar.');
                    }
                    @chmod($targetPath, 0640);

                    $oldAvatar = (string) ($existingUser['avatar'] ?? '');
                    if ($oldAvatar !== '') {
                        $oldPath = $avatarDir . '/' . $oldAvatar;
                        if (is_file($oldPath)) {
                            @unlink($oldPath);
                        }
                    }

                    $updateData['avatar'] = $newFileName;
                }
            }

            $this->userModel->updateDosenProfile($userId, $updateData);
            $this->userModel->syncDosenNameReferences($userId, $name);
            logActivity('profil', 'Dosen memperbarui profil sendiri: ' . $name, $userId);

            ensureSessionStarted();
            if (isset($_SESSION['auth_user']) && is_array($_SESSION['auth_user'])) {
                $_SESSION['auth_user']['name'] = $name;
                $_SESSION['auth_user']['nidn'] = $nidn;
                $_SESSION['auth_user']['email'] = $email;
                $_SESSION['auth_user']['username'] = $username;
                $_SESSION['auth_user']['gender'] = $gender;
                if (isset($updateData['avatar'])) {
                    $_SESSION['auth_user']['avatar'] = (string) $updateData['avatar'];
                }
            }

            $redirectMessage = $this->userModel->isDosenProfileComplete(array_merge($existingUser, $updateData))
                ? 'Profil berhasil diperbarui.'
                : 'Profil disimpan, tetapi masih ada data wajib yang belum lengkap.';

            if ($this->userModel->isDosenProfileComplete(array_merge($existingUser, $updateData))) {
                $this->redirectToPath('dashboard-dosen', [
                    'success' => 'Profil berhasil dilengkapi. Selamat menggunakan SAPA LPPM.',
                ]);
            }

            $this->redirectToPath('profil', ['success' => $redirectMessage]);
        } catch (Throwable $e) {
            $this->redirectToPath('profil', ['error' => $e->getMessage()]);
        }
    }

    private function isStrongPassword(string $password): bool
    {
        if (strlen($password) < 8 || strlen($password) > 72) {
            return false;
        }

        return (bool) preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[^A-Za-z0-9]).+$/', $password);
    }

    private function detectMimeType(string $path): string
    {
        if ($path === '' || !is_file($path)) {
            return '';
        }

        if (class_exists('finfo')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $detected = (string) $finfo->file($path);
            if ($detected !== '') {
                return strtolower(trim($detected));
            }
        }

        $fallback = (string) (mime_content_type($path) ?: '');
        return strtolower(trim($fallback));
    }
}
