<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/UserModel.php';
require_once __DIR__ . '/../Models/ActivityLogModel.php';
require_once __DIR__ . '/../Helpers/ActivityLogHelper.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class UserController extends BaseController
{
    private UserModel $userModel;
    private ActivityLogModel $activityLogModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new UserModel();
        $this->activityLogModel = new ActivityLogModel();
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
        if (authRole() !== 'admin') {
            $this->redirectToPath('pengguna', ['error' => 'Aksi ini hanya tersedia untuk admin.']);
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

    public function bulkDeleteDosen(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToPath('pengguna');
        }
        if (authRole() !== 'admin') {
            $this->redirectToPath('pengguna', ['error' => 'Aksi ini hanya tersedia untuk admin.']);
        }

        $ids = array_values(array_filter(
            array_map('intval', (array) ($_POST['user_ids'] ?? [])),
            static fn (int $id): bool => $id > 0
        ));
        $ids = array_values(array_unique($ids));

        if ($ids === []) {
            $this->redirectToPath('pengguna', ['error' => 'Pilih minimal satu pengguna dosen untuk dihapus.']);
        }

        $deletedCount = 0;
        $blockedMessages = [];

        foreach ($ids as $id) {
            try {
                $dosen = $this->userModel->findDosenById($id);
                if ($dosen === null) {
                    $blockedMessages[] = 'ID ' . $id . ' tidak ditemukan.';
                    continue;
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

                    $detail = $parts !== [] ? ' (' . implode(', ', $parts) . ')' : '';
                    $blockedMessages[] = (string) ($dosen['name'] ?? ('ID ' . $id)) . $detail;
                    continue;
                }

                $this->userModel->deleteDosenById($id);
                logActivity('pengguna', 'Menghapus pengguna dosen: ' . (string) ($dosen['name'] ?? ('ID ' . $id)), $id);
                $deletedCount++;
            } catch (Throwable $e) {
                $blockedMessages[] = 'ID ' . $id . ': ' . $e->getMessage();
            }
        }

        if ($deletedCount > 0 && $blockedMessages === []) {
            $this->redirectToPath('pengguna', ['success' => $deletedCount . ' pengguna dosen berhasil dihapus.']);
        }

        if ($deletedCount > 0) {
            $message = $deletedCount . ' pengguna berhasil dihapus. Sebagian gagal: ' . implode('; ', array_slice($blockedMessages, 0, 5));
            if (count($blockedMessages) > 5) {
                $message .= '; dan lainnya.';
            }
            $this->redirectToPath('pengguna', ['error' => $message]);
        }

        $message = 'Penghapusan tidak dapat diproses: ' . implode('; ', array_slice($blockedMessages, 0, 5));
        if (count($blockedMessages) > 5) {
            $message .= '; dan lainnya.';
        }
        $this->redirectToPath('pengguna', ['error' => $message]);
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

    public function importUsersPage(): void
    {
        $summary = $this->userModel->getDosenSummary();
        ensureSessionStarted();
        $importPreview = $_SESSION['users_import_preview'] ?? null;

        $this->render('users/import', [
            'pageTitle' => 'Impor Pengguna',
            'totalUsers' => (int) ($summary['total_dosen'] ?? 0),
            'templateFormat' => 'XLSX',
            'recentImports' => $this->activityLogModel->getRecentImports('pengguna', 10),
            'importPreview' => is_array($importPreview) ? $importPreview : null,
            'successMessage' => $_GET['success'] ?? null,
            'errorMessage' => $_GET['error'] ?? null,
        ]);
    }

    public function downloadUsersImportTemplate(): void
    {
        $filename = 'template-impor-pengguna-' . date('Ymd-His') . '.xlsx';
        $faculties = $this->getUsersImportFacultyReferences();
        $studyPrograms = $this->getUsersImportStudyProgramReferences();
        $academicRanks = $this->getUsersImportAcademicRankReferences();

        $spreadsheet = new Spreadsheet();
        $templateSheet = $spreadsheet->getActiveSheet();
        $templateSheet->setTitle('Template');

        $headers = ['nama', 'nidn', 'nuptk', 'email', 'username', 'password', 'fakultas', 'program_studi', 'no_hp', 'jenis_kelamin', 'google_scholar', 'sinta', 'status'];
        $templateSheet->fromArray($headers, null, 'A1');
        $templateSheet->fromArray([
            'Contoh Dosen',
            '1234567890',
            '9458768669131001',
            'contoh.dosen@kampus.ac.id',
            'contohdosen',
            'Password@123',
            'Fakultas Teknik dan Perencanaan',
            'Teknik Informatika',
            '081234567890',
            'Laki-laki',
            'https://scholar.google.com/citations?user=contoh',
            'https://sinta.kemdiktisaintek.go.id/authors/profile/12345',
            'aktif',
        ], null, 'A2');

        foreach (range('A', 'M') as $column) {
            $templateSheet->getColumnDimension($column)->setAutoSize(true);
        }
        $templateSheet->setCellValue('A4', 'Petunjuk: gunakan sheet Referensi untuk memilih nilai fakultas dan program studi yang sesuai.');
        $templateSheet->mergeCells('A4:M4');
        $templateSheet->getStyle('A1:M1')->getFont()->setBold(true);
        $templateSheet->getStyle('A1:M1')->getFill()->setFillType('solid')->getStartColor()->setRGB('D9E2F3');
        $templateSheet->getStyle('A4')->getFont()->setItalic(true);
        $templateSheet->freezePane('A2');

        $referenceSheet = $spreadsheet->createSheet();
        $referenceSheet->setTitle('Referensi');
        $referenceSheet->setCellValue('A1', 'Referensi Fakultas');
        $referenceSheet->setCellValue('C1', 'Referensi Program Studi');
        $referenceSheet->setCellValue('E1', 'Referensi Jabatan Fungsional');
        $referenceSheet->getStyle('A1')->getFont()->setBold(true);
        $referenceSheet->getStyle('C1')->getFont()->setBold(true);
        $referenceSheet->getStyle('E1')->getFont()->setBold(true);
        $referenceSheet->getStyle('A1')->getFill()->setFillType('solid')->getStartColor()->setRGB('E7E6E6');
        $referenceSheet->getStyle('C1')->getFill()->setFillType('solid')->getStartColor()->setRGB('E7E6E6');
        $referenceSheet->getStyle('E1')->getFill()->setFillType('solid')->getStartColor()->setRGB('E7E6E6');

        $facultyRow = 2;
        foreach ($faculties as $faculty) {
            $referenceSheet->setCellValue('A' . $facultyRow, (string) $faculty);
            $facultyRow++;
        }

        $studyProgramRow = 2;
        foreach ($studyPrograms as $studyProgram) {
            $referenceSheet->setCellValue('C' . $studyProgramRow, (string) $studyProgram);
            $studyProgramRow++;
        }

        $academicRankRow = 2;
        foreach ($academicRanks as $academicRank) {
            $referenceSheet->setCellValue('E' . $academicRankRow, (string) $academicRank);
            $academicRankRow++;
        }

        $referenceSheet->getColumnDimension('A')->setAutoSize(true);
        $referenceSheet->getColumnDimension('C')->setAutoSize(true);
        $referenceSheet->getColumnDimension('E')->setAutoSize(true);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function previewUsersImport(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToPath('pengguna/impor');
        }

        ensureSessionStarted();
        unset($_SESSION['users_import_preview']);

        try {
            if (!isset($_FILES['import_file']) || !is_array($_FILES['import_file'])) {
                throw new RuntimeException('File impor belum dipilih.');
            }

            $file = $_FILES['import_file'];
            $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($errorCode !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Upload file impor gagal. Silakan pilih file yang valid.');
            }

            $tmpPath = (string) ($file['tmp_name'] ?? '');
            $originalName = trim((string) ($file['name'] ?? 'import.xlsx'));
            if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
                throw new RuntimeException('File upload tidak valid.');
            }
            $fileSize = (int) ($file['size'] ?? 0);
            if ($fileSize <= 0) {
                throw new RuntimeException('File impor tidak boleh kosong.');
            }
            if ($fileSize > 5 * 1024 * 1024) {
                throw new RuntimeException('Ukuran file impor maksimal 5 MB.');
            }
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            if ($extension !== 'xlsx') {
                throw new RuntimeException('Format file impor harus Excel (.xlsx).');
            }
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = strtolower((string) $finfo->file($tmpPath));
            $allowedMimeTypes = [
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/zip',
                'application/x-zip-compressed',
                'application/octet-stream',
            ];
            if (!in_array($mimeType, $allowedMimeTypes, true)) {
                throw new RuntimeException('Tipe file impor tidak valid. Gunakan file Excel (.xlsx) yang benar.');
            }

            $rows = $this->parseUsersImportXlsx($tmpPath, $originalName);
            if ($rows === []) {
                throw new RuntimeException('File impor kosong atau tidak memiliki data.');
            }

            $preview = $this->buildUsersImportPreview($rows, $originalName);
            $_SESSION['users_import_preview'] = $preview;

            $this->redirectToPath('pengguna/impor', [
                'success' => 'Preview impor berhasil dibuat. Periksa data valid sebelum menyimpan.',
            ]);
        } catch (Throwable $e) {
            $this->redirectToPath('pengguna/impor', ['error' => $e->getMessage()]);
        }
    }

    public function storeUsersImport(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToPath('pengguna/impor');
        }

        ensureSessionStarted();
        $preview = $_SESSION['users_import_preview'] ?? null;
        if (!is_array($preview) || !isset($preview['valid_rows']) || !is_array($preview['valid_rows'])) {
            $this->redirectToPath('pengguna/impor', ['error' => 'Preview impor tidak ditemukan. Upload file terlebih dahulu.']);
        }

        $validRows = $preview['valid_rows'];
        if ($validRows === []) {
            $this->redirectToPath('pengguna/impor', ['error' => 'Tidak ada data valid untuk disimpan.']);
        }

        $createdCount = 0;
        $failedMessages = [];

        foreach ($validRows as $row) {
            try {
                $email = (string) ($row['email'] ?? '');
                $username = (string) ($row['username'] ?? '');

                if ($this->userModel->findByNuptk((string) ($row['nuptk'] ?? '')) !== null) {
                    $failedMessages[] = (string) ($row['name'] ?? 'Tanpa Nama') . ' - NUPTK sudah terdaftar.';
                    continue;
                }
                if ($this->userModel->findByEmail($email) !== null) {
                    $failedMessages[] = (string) ($row['name'] ?? 'Tanpa Nama') . ' - email sudah terdaftar.';
                    continue;
                }
                if ($this->userModel->findByUsername($username) !== null) {
                    $failedMessages[] = (string) ($row['name'] ?? 'Tanpa Nama') . ' - username sudah terdaftar.';
                    continue;
                }

                $this->userModel->createPublicDosen($row);
                $createdCount++;
            } catch (Throwable $e) {
                $failedMessages[] = (string) ($row['name'] ?? 'Tanpa Nama') . ' - ' . $e->getMessage();
            }
        }

        unset($_SESSION['users_import_preview']);

        if ($createdCount > 0) {
            logActivity('pengguna', 'Impor pengguna (' . $createdCount . ' data)');
        }

        if ($createdCount > 0 && $failedMessages === []) {
            $this->redirectToPath('pengguna/impor', ['success' => $createdCount . ' pengguna berhasil diimpor.']);
        }

        if ($createdCount > 0) {
            $message = $createdCount . ' pengguna berhasil diimpor. Sebagian gagal: ' . implode('; ', array_slice($failedMessages, 0, 5));
            if (count($failedMessages) > 5) {
                $message .= '; dan lainnya.';
            }
            $this->redirectToPath('pengguna/impor', ['error' => $message]);
        }

        $message = 'Impor gagal: ' . implode('; ', array_slice($failedMessages, 0, 5));
        if (count($failedMessages) > 5) {
            $message .= '; dan lainnya.';
        }
        $this->redirectToPath('pengguna/impor', ['error' => $message]);
    }

    public function exportUsersPage(): void
    {
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

        $rows = $this->userModel->getDosenUsersForManagementFiltered($filters);

        $this->render('users/export', [
            'pageTitle' => 'Ekspor Pengguna',
            'userFilters' => $filters,
            'filterOptions' => $filterOptions,
            'totalUsers' => count($rows),
            'activeFilterCount' => count(array_filter($filters, static fn (string $value): bool => trim($value) !== '')),
            'recentExports' => $this->activityLogModel->getRecentExports('pengguna', 10),
            'successMessage' => $_GET['success'] ?? null,
            'errorMessage' => $_GET['error'] ?? null,
        ]);
    }

    public function downloadUsersExport(): void
    {
        $filters = [
            'keyword' => trim((string) ($_POST['keyword'] ?? '')),
            'faculty' => trim((string) ($_POST['faculty'] ?? '')),
            'study_program' => trim((string) ($_POST['study_program'] ?? '')),
        ];
        $filterOptions = $this->userModel->getDosenFilterOptions();

        if ($filters['faculty'] !== '' && !in_array($filters['faculty'], $filterOptions['faculties'], true)) {
            $filters['faculty'] = '';
        }
        if ($filters['study_program'] !== '' && !in_array($filters['study_program'], $filterOptions['study_programs'], true)) {
            $filters['study_program'] = '';
        }

        $rows = $this->userModel->getDosenUsersForManagementFiltered($filters);
        $filename = 'pengguna-dosen-' . date('Ymd-His') . '.xlsx';
        $filterLabels = [];
        if ($filters['faculty'] !== '') {
            $filterLabels[] = 'fakultas=' . $filters['faculty'];
        }
        if ($filters['study_program'] !== '') {
            $filterLabels[] = 'prodi=' . $filters['study_program'];
        }
        if ($filters['keyword'] !== '') {
            $filterLabels[] = 'kata_kunci=' . $filters['keyword'];
        }
        $filterSummary = $filterLabels !== [] ? ' | filter: ' . implode(', ', $filterLabels) : '';
        logActivity('pengguna', 'Ekspor pengguna Excel (' . count($rows) . ' data)' . $filterSummary);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Pengguna');

        $headers = ['No', 'Nama Dosen', 'Status Akun', 'NUPTK', 'Fakultas', 'Program Studi', 'Email', 'Username', 'No. HP', 'Google Scholar', 'Sinta'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:K1')->getFont()->setBold(true);
        $sheet->getStyle('A1:K1')->getFill()->setFillType('solid')->getStartColor()->setRGB('D9E2F3');
        $sheet->freezePane('A2');

        $rowNumber = 2;
        foreach ($rows as $index => $row) {
            $statusLabel = $this->resolveUserExportStatusLabel((string) ($row['role'] ?? 'dosen'));
            $sheet->setCellValue('A' . $rowNumber, $index + 1);
            $sheet->setCellValue('B' . $rowNumber, (string) ($row['name'] ?? ''));
            $sheet->setCellValue('C' . $rowNumber, $statusLabel);
            $sheet->setCellValueExplicit('D' . $rowNumber, (string) ($row['nuptk'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('E' . $rowNumber, (string) ($row['faculty'] ?? ''));
            $sheet->setCellValue('F' . $rowNumber, (string) (($row['study_program'] ?? '') !== '' ? $row['study_program'] : ($row['unit'] ?? '')));
            $sheet->setCellValue('G' . $rowNumber, (string) ($row['email'] ?? ''));
            $sheet->setCellValue('H' . $rowNumber, (string) ($row['username'] ?? ''));
            $sheet->setCellValueExplicit('I' . $rowNumber, $this->normalizeExportPhone((string) ($row['phone'] ?? '')), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('J' . $rowNumber, (string) ($row['google_scholar_id'] ?? ''));
            $sheet->setCellValue('K' . $rowNumber, (string) ($row['sinta_id'] ?? ''));
            $rowNumber++;
        }

        foreach (range('A', 'K') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function downloadUsersExportPdf(): void
    {
        $filters = [
            'keyword' => trim((string) ($_POST['keyword'] ?? '')),
            'faculty' => trim((string) ($_POST['faculty'] ?? '')),
            'study_program' => trim((string) ($_POST['study_program'] ?? '')),
        ];
        $filterOptions = $this->userModel->getDosenFilterOptions();

        if ($filters['faculty'] !== '' && !in_array($filters['faculty'], $filterOptions['faculties'], true)) {
            $filters['faculty'] = '';
        }
        if ($filters['study_program'] !== '' && !in_array($filters['study_program'], $filterOptions['study_programs'], true)) {
            $filters['study_program'] = '';
        }

        $rows = $this->userModel->getDosenUsersForManagementFiltered($filters);
        $filename = 'pengguna-dosen-' . date('Ymd-His') . '.pdf';
        $filterLabels = [];
        if ($filters['faculty'] !== '') {
            $filterLabels[] = 'Fakultas: ' . $filters['faculty'];
        }
        if ($filters['study_program'] !== '') {
            $filterLabels[] = 'Program Studi: ' . $filters['study_program'];
        }
        if ($filters['keyword'] !== '') {
            $filterLabels[] = 'Kata Kunci: ' . $filters['keyword'];
        }

        logActivity(
            'pengguna',
            'Ekspor pengguna PDF (' . count($rows) . ' data)' . ($filterLabels !== [] ? ' | filter: ' . implode(', ', $filterLabels) : '')
        );

        $html = $this->buildUsersExportPdfHtml($rows, $filterLabels);

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isFontSubsettingEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $canvas = $dompdf->getCanvas();
        $fontMetrics = $dompdf->getFontMetrics();
        $font = $fontMetrics->getFont('Helvetica', 'normal');
        $canvas->page_text(480, 810, 'Halaman {PAGE_NUM} / {PAGE_COUNT}', $font, 9, [0, 0, 0]);

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        echo $dompdf->output();
        exit;
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

    private function buildUsersExportPdfHtml(array $rows, array $filterLabels): string
    {
        $generatedAt = date('d M Y H:i');
        $generatedBy = (string) ((authUser()['name'] ?? '') !== '' ? authUser()['name'] : 'Admin SAPA LPPM');
        $filterSummary = $filterLabels !== [] ? implode(' | ', $filterLabels) : 'Semua data pengguna dosen';

        $profileBlocks = '';
        foreach ($rows as $index => $row) {
            $profileBlocks .= '<div class="profile-card">';
            $profileBlocks .= '<div class="profile-header">';
            $profileBlocks .= '<div class="profile-number">#' . ($index + 1) . '</div>';
            $profileBlocks .= '<div class="profile-name">' . $this->escapePdfHtml((string) ($row['name'] ?? '-')) . '</div>';
            $profileBlocks .= '<div class="profile-status">' . $this->escapePdfHtml($this->resolveUserExportStatusLabel((string) ($row['role'] ?? 'dosen'))) . '</div>';
            $profileBlocks .= '</div>';
            $profileBlocks .= '<table class="profile-table">';
            $profileBlocks .= $this->buildProfilePdfRow('NIDN', (string) (($row['nidn'] ?? '') !== '' ? $row['nidn'] : '-'));
            $profileBlocks .= $this->buildProfilePdfRow('NUPTK', (string) (($row['nuptk'] ?? '') !== '' ? $row['nuptk'] : '-'));
            $profileBlocks .= $this->buildProfilePdfRow('Jenis Kelamin', (string) (($row['gender'] ?? '') !== '' ? $row['gender'] : '-'));
            $profileBlocks .= $this->buildProfilePdfRow('Fakultas', (string) (($row['faculty'] ?? '') !== '' ? $row['faculty'] : '-'));
            $profileBlocks .= $this->buildProfilePdfRow('Program Studi', (string) (($row['study_program'] ?? '') !== '' ? $row['study_program'] : ($row['unit'] ?? '-')));
            $profileBlocks .= $this->buildProfilePdfRow('Email', (string) (($row['email'] ?? '') !== '' ? $row['email'] : '-'));
            $profileBlocks .= $this->buildProfilePdfRow('Username', (string) (($row['username'] ?? '') !== '' ? $row['username'] : '-'));
            $profileBlocks .= $this->buildProfilePdfRow('No. HP', (string) (($this->normalizeExportPhone((string) ($row['phone'] ?? ''))) !== '' ? $this->normalizeExportPhone((string) ($row['phone'] ?? '')) : '-'));
            $profileBlocks .= $this->buildProfilePdfRow('Google Scholar', (string) (($row['google_scholar_id'] ?? '') !== '' ? $row['google_scholar_id'] : '-'));
            $profileBlocks .= $this->buildProfilePdfRow('Sinta', (string) (($row['sinta_id'] ?? '') !== '' ? $row['sinta_id'] : '-'));
            $profileBlocks .= '</table>';
            $profileBlocks .= '</div>';
            if ($index < count($rows) - 1) {
                $profileBlocks .= '<div class="profile-divider"></div>';
            }
        }

        if ($profileBlocks === '') {
            $profileBlocks = '<div class="empty-state">Tidak ada data pengguna untuk diekspor.</div>';
        }

        return '<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Ekspor Pengguna</title>
    <style>
        @page { margin: 22px 24px; }
        body { font-family: DejaVu Sans, sans-serif; color: #000000; font-size: 10px; margin: 0; }
        .header { margin-bottom: 14px; }
        .title { font-size: 20px; font-weight: bold; margin-bottom: 4px; }
        .subtitle { font-size: 10px; color: #000000; }
        .meta { margin-top: 5px; font-size: 9px; color: #000000; }
        .profile-card { border: 1px solid #666666; margin-bottom: 0; page-break-inside: avoid; }
        .profile-header { background: #d9d9d9; padding: 9px 11px; border-bottom: 1px solid #666666; }
        .profile-number { font-size: 10px; margin-bottom: 4px; }
        .profile-name { font-size: 15px; font-weight: bold; margin-bottom: 4px; }
        .profile-status { font-size: 10px; }
        .profile-table { width: 100%; border-collapse: collapse; }
        .profile-table td { border: 1px solid #8f8f8f; padding: 7px 8px; vertical-align: top; word-wrap: break-word; overflow-wrap: anywhere; background: #ffffff; }
        .label-cell { width: 28%; background: #f2f2f2; font-weight: bold; }
        .value-cell { width: 72%; }
        .empty-state { border: 1px solid #8f8f8f; padding: 14px; text-align: center; }
        .profile-divider { border-top: 1px dashed #8f8f8f; margin: 10px 0 14px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Ekspor Pengguna Dosen</div>
        <div class="subtitle">SAPA LPPM - Sistem Administrasi Persuratan dan Arsip LPPM</div>
        <div class="meta">Dicetak: ' . $this->escapePdfHtml($generatedAt) . ' | Oleh: ' . $this->escapePdfHtml($generatedBy) . '</div>
        <div class="meta">Filter: ' . $this->escapePdfHtml($filterSummary) . ' | Total Data: ' . count($rows) . '</div>
    </div>
    ' . $profileBlocks . '
</body>
</html>';
    }

    private function buildProfilePdfRow(string $label, string $value): string
    {
        return '<tr>'
            . '<td class="label-cell">' . $this->escapePdfHtml($label) . '</td>'
            . '<td class="value-cell">' . $this->escapePdfHtml($value) . '</td>'
            . '</tr>';
    }

    private function parseUsersImportXlsx(string $path, string $originalName): array
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($extension !== 'xlsx') {
            throw new RuntimeException('Format file impor harus Excel (.xlsx).');
        }

        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getSheetByName('Template') ?? $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray('', true, true, false);
        if ($rows === []) {
            return [];
        }

        $header = array_map(static function ($value): string {
            return strtolower(trim((string) $value));
        }, (array) array_shift($rows));

        $expectedHeader = ['nama', 'nidn', 'nuptk', 'email', 'username', 'password', 'fakultas', 'program_studi', 'no_hp', 'jenis_kelamin', 'google_scholar', 'sinta', 'status'];
        if ($header !== $expectedHeader) {
            throw new RuntimeException('Format header file Excel tidak sesuai template impor pengguna.');
        }

        $mappedRows = [];
        foreach ($rows as $data) {
            $mapped = [];
            foreach ($expectedHeader as $index => $column) {
                $mapped[$column] = trim((string) ($data[$index] ?? ''));
            }

            $firstCell = trim((string) ($mapped['nama'] ?? ''));
            if ($firstCell !== '' && str_starts_with($firstCell, '#')) {
                continue;
            }
            if (implode('', $mapped) === '') {
                continue;
            }

            $mappedRows[] = $mapped;
        }

        return $mappedRows;
    }

    private function buildUsersImportPreview(array $rows, string $fileName): array
    {
        $allowedFaculties = array_values(array_map([$this, 'normalizeFacultyLabel'], $this->getUsersImportFacultyReferences()));
        $allowedStudyPrograms = array_values(array_map([$this, 'normalizeStudyProgramLabel'], $this->getUsersImportStudyProgramReferences()));
        $previewRows = [];
        $validRows = [];
        $seenNuptk = [];
        $seenEmail = [];
        $seenUsername = [];

        foreach ($rows as $index => $row) {
            $normalizedRow = $this->normalizeImportRow($row);
            $errors = $this->validateImportRow($normalizedRow, $allowedFaculties, $allowedStudyPrograms);

            $nuptkKey = strtolower((string) ($normalizedRow['nuptk'] ?? ''));
            $emailKey = strtolower((string) ($normalizedRow['email'] ?? ''));
            $usernameKey = strtolower((string) ($normalizedRow['username'] ?? ''));

            if ($nuptkKey !== '') {
                if (isset($seenNuptk[$nuptkKey])) {
                    $errors[] = 'NUPTK duplikat di file impor.';
                }
                $seenNuptk[$nuptkKey] = true;
            }
            if ($emailKey !== '') {
                if (isset($seenEmail[$emailKey])) {
                    $errors[] = 'Email duplikat di file impor.';
                }
                $seenEmail[$emailKey] = true;
            }
            if ($usernameKey !== '') {
                if (isset($seenUsername[$usernameKey])) {
                    $errors[] = 'Username duplikat di file impor.';
                }
                $seenUsername[$usernameKey] = true;
            }

            if ($nuptkKey !== '' && $this->userModel->findByNuptk((string) ($normalizedRow['nuptk'] ?? '')) !== null) {
                $errors[] = 'NUPTK sudah terdaftar.';
            }
            if ($emailKey !== '' && $this->userModel->findByEmail((string) ($normalizedRow['email'] ?? '')) !== null) {
                $errors[] = 'Email sudah terdaftar.';
            }
            if ($usernameKey !== '' && $this->userModel->findByUsername((string) ($normalizedRow['username'] ?? '')) !== null) {
                $errors[] = 'Username sudah terdaftar.';
            }

            $isValid = $errors === [];

            $previewRows[] = [
                'row_number' => $index + 2,
                'name' => (string) ($normalizedRow['name'] ?? ''),
                'nuptk' => (string) ($normalizedRow['nuptk'] ?? ''),
                'email' => (string) ($normalizedRow['email'] ?? ''),
                'username' => (string) ($normalizedRow['username'] ?? ''),
                'faculty' => (string) ($normalizedRow['faculty'] ?? ''),
                'study_program' => (string) ($normalizedRow['study_program'] ?? ''),
                'status' => $isValid ? 'Valid' : 'Perlu Perbaikan',
                'errors' => $errors,
            ];

            if ($isValid) {
                $validRows[] = $normalizedRow;
            }
        }

        return [
            'file_name' => $fileName,
            'rows' => $previewRows,
            'valid_rows' => $validRows,
            'total_rows' => count($previewRows),
            'valid_count' => count($validRows),
            'invalid_count' => count($previewRows) - count($validRows),
        ];
    }

    private function normalizeImportRow(array $row): array
    {
        $gender = strtolower(trim((string) ($row['jenis_kelamin'] ?? '')));
        if (in_array($gender, ['l', 'laki', 'laki-laki', 'male'], true)) {
            $gender = 'Laki-laki';
        } elseif (in_array($gender, ['p', 'perempuan', 'female'], true)) {
            $gender = 'Perempuan';
        } else {
            $gender = trim((string) ($row['jenis_kelamin'] ?? ''));
        }

        $phone = trim((string) ($row['no_hp'] ?? ''));
        if (str_starts_with($phone, '+62')) {
            $phone = '0' . substr($phone, 3);
        } elseif (str_starts_with($phone, '62')) {
            $phone = '0' . substr($phone, 2);
        }

        return [
            'name' => trim((string) ($row['nama'] ?? '')),
            'nidn' => trim((string) ($row['nidn'] ?? '')),
            'nuptk' => trim((string) ($row['nuptk'] ?? '')),
            'email' => strtolower(trim((string) ($row['email'] ?? ''))),
            'username' => strtolower(trim((string) ($row['username'] ?? ''))),
            'password' => password_hash((string) ($row['password'] ?? ''), PASSWORD_DEFAULT),
            'plain_password' => (string) ($row['password'] ?? ''),
            'faculty' => $this->normalizeFacultyLabel((string) ($row['fakultas'] ?? '')),
            'study_program' => $this->normalizeStudyProgramLabel((string) ($row['program_studi'] ?? '')),
            'unit' => $this->normalizeStudyProgramLabel((string) ($row['program_studi'] ?? '')),
            'phone' => $phone,
            'gender' => $gender,
            'google_scholar_id' => trim((string) ($row['google_scholar'] ?? '')),
            'sinta_id' => trim((string) ($row['sinta'] ?? '')),
            'status' => trim((string) ($row['status'] ?? 'aktif')) !== '' ? trim((string) ($row['status'] ?? 'aktif')) : 'aktif',
        ];
    }

    private function validateImportRow(array $row, array $allowedFaculties = [], array $allowedStudyPrograms = []): array
    {
        $errors = [];

        if ((string) ($row['name'] ?? '') === '') {
            $errors[] = 'Nama wajib diisi.';
        }
        if ((string) ($row['nuptk'] ?? '') === '' || !preg_match('/^\d{6,30}$/', (string) ($row['nuptk'] ?? ''))) {
            $errors[] = 'NUPTK wajib angka 6-30 digit.';
        }
        if ((string) ($row['email'] ?? '') === '' || !filter_var((string) ($row['email'] ?? ''), FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email tidak valid.';
        }
        if ((string) ($row['username'] ?? '') === '' || !preg_match('/^[A-Za-z0-9_.-]{3,50}$/', (string) ($row['username'] ?? ''))) {
            $errors[] = 'Username hanya boleh huruf, angka, titik, garis bawah, atau tanda minus.';
        }
        if ((string) ($row['faculty'] ?? '') === '') {
            $errors[] = 'Fakultas wajib diisi.';
        } elseif ($allowedFaculties !== [] && !in_array((string) ($row['faculty'] ?? ''), $allowedFaculties, true)) {
            $errors[] = 'Fakultas harus mengikuti daftar yang ada di template sistem.';
        }
        if ((string) ($row['study_program'] ?? '') === '') {
            $errors[] = 'Program studi wajib diisi.';
        } elseif ($allowedStudyPrograms !== [] && !in_array((string) ($row['study_program'] ?? ''), $allowedStudyPrograms, true)) {
            $errors[] = 'Program studi harus mengikuti daftar yang ada di template sistem.';
        }
        if ((string) ($row['phone'] ?? '') === '' || !preg_match('/^[0-9+\-\s]{8,25}$/', (string) ($row['phone'] ?? ''))) {
            $errors[] = 'No. HP tidak valid.';
        }
        if (!in_array((string) ($row['gender'] ?? ''), ['Laki-laki', 'Perempuan'], true)) {
            $errors[] = 'Jenis kelamin harus Laki-laki atau Perempuan.';
        }
        if (!$this->isStrongPassword((string) ($row['plain_password'] ?? ''))) {
            $errors[] = 'Password minimal 8 karakter dan wajib mengandung huruf besar, huruf kecil, angka, serta simbol.';
        }
        if ((string) ($row['google_scholar_id'] ?? '') !== '' && !filter_var((string) ($row['google_scholar_id'] ?? ''), FILTER_VALIDATE_URL)) {
            $errors[] = 'Link Google Scholar tidak valid.';
        }
        if ((string) ($row['sinta_id'] ?? '') !== '' && !filter_var((string) ($row['sinta_id'] ?? ''), FILTER_VALIDATE_URL)) {
            $errors[] = 'Link Sinta tidak valid.';
        }

        return $errors;
    }

    private function normalizeFacultyLabel(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $map = [
            'FTP' => 'Fakultas Teknik dan Perencanaan',
            'FAKULTAS TEKNIK DAN PERENCANAAN (FTP)' => 'Fakultas Teknik dan Perencanaan',
            'FAKULTAS TEKNIK DAN PERENCANAAN' => 'Fakultas Teknik dan Perencanaan',
            'FMIPA' => 'Fakultas Matematika dan Ilmu Pengetahuan Alam',
            'FAKULTAS MATEMATIKA DAN ILMU PENGETAHUAN ALAM (FMIPA)' => 'Fakultas Matematika dan Ilmu Pengetahuan Alam',
            'FAKULTAS MATEMATIKA DAN ILMU PENGETAHUAN ALAM' => 'Fakultas Matematika dan Ilmu Pengetahuan Alam',
            'FKIP' => 'Fakultas Keguruan dan Ilmu Pendidikan',
            'FAKULTAS KEGURUAN DAN ILMU PENDIDIKAN (FKIP)' => 'Fakultas Keguruan dan Ilmu Pendidikan',
            'FAKULTAS KEGURUAN DAN ILMU PENDIDIKAN' => 'Fakultas Keguruan dan Ilmu Pendidikan',
        ];

        $upper = strtoupper($value);
        return $map[$upper] ?? $value;
    }

    private function normalizeStudyProgramLabel(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $map = [
            'TEKNIK LINGKUNGAN (TL)' => 'Teknik Lingkungan',
            'TEKNIK LINGKUNGAN' => 'Teknik Lingkungan',
            'TL' => 'Teknik Lingkungan',
            'TEKNIK INFORMATIKA (TI)' => 'Teknik Informatika',
            'TEKNIK INFORMATIKA' => 'Teknik Informatika',
            'TI' => 'Teknik Informatika',
            'STATISTIKA (STAT)' => 'Statistika',
            'STATISTIKA' => 'Statistika',
            'STAT' => 'Statistika',
            'MATEMATIKA (MAT)' => 'Matematika',
            'MATEMATIKA' => 'Matematika',
            'MAT' => 'Matematika',
            'FISIKA (FIS)' => 'Fisika',
            'FISIKA' => 'Fisika',
            'FIS' => 'Fisika',
            'BIOLOGI (BO)' => 'Biologi',
            'BIOLOGI' => 'Biologi',
            'BO' => 'Biologi',
            'PENDIDIKAN LUAR BIASA (PLB)' => 'Pendidikan Luar Biasa',
            'PENDIDIKAN LUAR BIASA' => 'Pendidikan Luar Biasa',
            'PLB' => 'Pendidikan Luar Biasa',
            'PENDIDIKAN JASMANI, KESEHATAN, DAN REKREASI (PJKR)' => 'Pendidikan Jasmani, Kesehatan, dan Rekreasi',
            'PENDIDIKAN JASMANI, KESEHATAN, DAN REKREASI' => 'Pendidikan Jasmani, Kesehatan, dan Rekreasi',
            'PJKR' => 'Pendidikan Jasmani, Kesehatan, dan Rekreasi',
            'PENDIDIKAN BAHASA INGGRIS (PBI)' => 'Pendidikan Bahasa Inggris',
            'PENDIDIKAN BAHASA INGGRIS' => 'Pendidikan Bahasa Inggris',
            'PBI' => 'Pendidikan Bahasa Inggris',
            'PENDIDIKAN GURU SEKOLAH DASAR (PGSD)' => 'Pendidikan Guru Sekolah Dasar',
            'PENDIDIKAN GURU SEKOLAH DASAR' => 'Pendidikan Guru Sekolah Dasar',
            'PGSD' => 'Pendidikan Guru Sekolah Dasar',
        ];

        $upper = strtoupper($value);
        return $map[$upper] ?? $value;
    }

    private function getUsersImportFacultyReferences(): array
    {
        return [
            'Fakultas Keguruan dan Ilmu Pendidikan',
            'Fakultas Matematika dan Ilmu Pengetahuan Alam',
            'Fakultas Teknik dan Perencanaan',
        ];
    }

    private function getUsersImportStudyProgramReferences(): array
    {
        return [
            'Pendidikan Bahasa Inggris',
            'Pendidikan Guru Sekolah Dasar',
            'Pendidikan Jasmani, Kesehatan, dan Rekreasi',
            'Pendidikan Luar Biasa',
            'Biologi',
            'Fisika',
            'Matematika',
            'Statistika',
            'Teknik Informatika',
            'Teknik Lingkungan',
        ];
    }

    private function getUsersImportAcademicRankReferences(): array
    {
        return [
            'Asisten Ahli',
            'Lektor',
            'Lektor Kepala',
            'Profesor',
        ];
    }

    private function resolveUserExportStatusLabel(string $role): string
    {
        $role = strtolower(trim($role));

        return in_array($role, ['kepala_lppm', 'admin_lppm'], true)
            ? 'Dosen + Kepala LPPM'
            : 'Dosen';
    }

    private function escapePdfHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private function formatSpreadsheetText(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        return '="' . str_replace('"', '""', $value) . '"';
    }

    private function normalizeExportPhone(string $phone): string
    {
        $phone = trim($phone);
        if ($phone === '') {
            return '';
        }

        if (str_starts_with($phone, '62')) {
            return '0' . substr($phone, 2);
        }

        if (str_starts_with($phone, '+62')) {
            return '0' . substr($phone, 3);
        }

        return $phone;
    }
}
