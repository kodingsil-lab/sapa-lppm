<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/LetterModel.php';
require_once __DIR__ . '/../Models/UserModel.php';
require_once __DIR__ . '/../Models/ResearchPermitLetterModel.php';
require_once __DIR__ . '/../Models/SuratTugasPenelitianModel.php';
require_once __DIR__ . '/../Models/PenelitianModel.php';
require_once __DIR__ . '/../Models/PengabdianModel.php';
require_once __DIR__ . '/../Models/HilirisasiModel.php';
require_once __DIR__ . '/../Models/ContractSettingModel.php';
require_once __DIR__ . '/../Helpers/LetterHelper.php';
require_once __DIR__ . '/../Helpers/AuthHelper.php';
require_once __DIR__ . '/../Helpers/ActivityLogHelper.php';
require_once __DIR__ . '/LetterPdfController.php';
require_once __DIR__ . '/../Services/LetterNumberGeneratorService.php';
require_once __DIR__ . '/../Services/EmailNotificationService.php';

class LetterController extends BaseController
{
    private LetterModel $letterModel;
    private UserModel $userModel;
    private ResearchPermitLetterModel $researchPermitModel;
    private SuratTugasPenelitianModel $suratTugasPenelitianModel;
    private EmailNotificationService $emailNotificationService;

    public function __construct()
    {
        parent::__construct();
        $this->letterModel = new LetterModel();
        $this->userModel = new UserModel();
        $this->researchPermitModel = new ResearchPermitLetterModel();
        $this->suratTugasPenelitianModel = new SuratTugasPenelitianModel();
        $this->emailNotificationService = new EmailNotificationService();
    }

    public function index(): void
    {
        $role = normalizeRoleName((string) authRole());
        if ($role === 'kepala_lppm') {
            $letters = [];
            $summary = ['total' => 0, 'kontrak' => 0, 'izin' => 0, 'tugas' => 0];
            $yearOptions = [];
            $dbError = null;
            $headFilters = [
                'jenis' => strtolower(trim((string) ($_GET['jenis'] ?? ''))),
                'tahun' => trim((string) ($_GET['tahun'] ?? '')),
                'keyword' => trim((string) ($_GET['keyword'] ?? '')),
            ];

            try {
                $letters = $this->letterModel->getHeadSubmissionRows(500, $headFilters);
                $summary = $this->letterModel->getHeadSubmissionSummary($headFilters);
                $yearOptions = $this->letterModel->getHeadSubmissionYears();
            } catch (Throwable $e) {
                $dbError = $e->getMessage();
            }

            $this->render('letters/index', [
                'pageTitle' => 'Persuratan',
                'isKepalaView' => true,
                'letters' => $letters,
                'summary' => $summary,
                'headFilters' => $headFilters,
                'yearOptions' => $yearOptions,
                'errorMessage' => $dbError,
            ]);

            return;
        }

        $letters = [];
        $dbError = null;
        $type = strtolower(trim((string) ($_GET['type'] ?? '')));
        $typeMap = [
            'izin' => 'IZIN',
            'tugas' => 'TUGAS',
            'pengantar' => 'PENGANTAR',
        ];
        $selectedTypeCode = $typeMap[$type] ?? null;
        $typeLabelMap = [
            'IZIN' => 'Surat Izin Penelitian',
            'TUGAS' => 'Surat Tugas',
            'PENGANTAR' => 'Surat Pengantar',
        ];
        $selectedTypeLabel = $selectedTypeCode !== null ? ($typeLabelMap[$selectedTypeCode] ?? 'Semua Jenis Surat') : 'Semua Jenis Surat';

        try {
            if (authRole() === 'dosen') {
                $letters = $this->letterModel->getLettersForUser((int) authUserId(), 30, $selectedTypeCode);
            } else {
                $letters = $this->letterModel->getRecentLetters(30, $selectedTypeCode);
            }
        } catch (Throwable $e) {
            $dbError = $e->getMessage();
        }

        $errorMessage = $_GET['error'] ?? null;
        if ($dbError !== null) {
            $errorMessage = $dbError;
        }

        $this->render('letters/index', [
            'pageTitle' => 'Persuratan',
            'createdNumber' => $_GET['number'] ?? null,
            'successMessage' => $_GET['success'] ?? null,
            'errorMessage' => $errorMessage,
            'letters' => $letters,
            'selectedTypeCode' => $selectedTypeCode,
            'selectedTypeLabel' => $selectedTypeLabel,
        ]);
    }

    public function create(): void
    {
        // Seluruh pengajuan surat sekarang dipusatkan ke menu Ajukan Surat.
        $legacyLetterId = isset($_GET['letter_id']) ? (int) $_GET['letter_id'] : 0;
        if ($legacyLetterId > 0) {
            $this->redirectToPath('persuratan/' . $legacyLetterId, ['edit' => '1']);
        }

        $routeKey = strtolower(trim((string) ($_GET['route'] ?? '')));
        $legacyRouteMap = [
            'surat-penelitian-kontrak' => ['penelitian', 'kontrak'],
            'surat-penelitian-izin' => ['penelitian', 'izin'],
            'surat-penelitian-tugas' => ['penelitian', 'tugas'],
            'surat-pengabdian-kontrak' => ['pengabdian', 'kontrak'],
            'surat-pengabdian-izin' => ['pengabdian', 'izin'],
            'surat-pengabdian-tugas' => ['pengabdian', 'tugas'],
            'surat-hilirisasi-kontrak' => ['hilirisasi', 'kontrak'],
            'surat-hilirisasi-izin' => ['hilirisasi', 'izin'],
            'surat-hilirisasi-tugas' => ['hilirisasi', 'tugas'],
        ];

        if (isset($legacyRouteMap[$routeKey])) {
            [$legacyActivityType, $legacySuratKind] = $legacyRouteMap[$routeKey];
        } else {
            $legacyActivityType = strtolower(trim((string) ($_GET['activity_type'] ?? 'penelitian')));
            if (!in_array($legacyActivityType, ['penelitian', 'pengabdian', 'hilirisasi'], true)) {
                $legacyActivityType = 'penelitian';
            }
            $legacySuratKind = strtolower(trim((string) ($_GET['surat_kind'] ?? 'izin')));
            if (!in_array($legacySuratKind, ['kontrak', 'izin', 'tugas'], true)) {
                $legacySuratKind = 'izin';
            }
        }
        $legacyValidation = $this->validatePayload(
            [
                'activity_type' => $legacyActivityType,
                'surat_kind' => $legacySuratKind,
            ],
            [
                'activity_type' => 'required|in_list[penelitian,pengabdian,hilirisasi]',
                'surat_kind' => 'required|in_list[kontrak,izin,tugas]',
            ]
        );
        if (!$legacyValidation['valid']) {
            $legacyActivityType = 'penelitian';
            $legacySuratKind = 'izin';
        }

        $this->redirectToPath('ajukan-surat/' . $legacyActivityType, [
            'surat_kind' => $legacySuratKind,
        ]);
    }

    public function submissionMenu(): void
    {
        if (authRole() !== 'dosen') {
            $this->redirectToPath($this->adminDashboardPath());
        }

        $selectedCategory = strtolower(trim((string) ($_GET['activity_type'] ?? 'penelitian')));
        if (!in_array($selectedCategory, ['penelitian', 'pengabdian', 'hilirisasi'], true)) {
            $selectedCategory = 'penelitian';
        }

        $selectedType = strtolower(trim((string) ($_GET['surat_kind'] ?? 'kontrak')));
        if (!in_array($selectedType, ['kontrak', 'izin', 'tugas'], true)) {
            $selectedType = 'kontrak';
        }
        $menuValidation = $this->validatePayload(
            [
                'activity_type' => $selectedCategory,
                'surat_kind' => $selectedType,
                'letter_id' => (string) ((int) ($_GET['letter_id'] ?? 0)),
                'activity_id' => (string) ((int) ($_GET['activity_id'] ?? 0)),
            ],
            [
                'activity_type' => 'required|in_list[penelitian,pengabdian,hilirisasi]',
                'surat_kind' => 'required|in_list[kontrak,izin,tugas]',
                'letter_id' => 'permit_empty|integer|greater_than_equal_to[0]',
                'activity_id' => 'permit_empty|integer|greater_than_equal_to[0]',
            ]
        );
        if (!$menuValidation['valid']) {
            $this->redirectToPath('ajukan-surat/penelitian', [
                'surat_kind' => 'kontrak',
                'error' => $this->firstValidationError($menuValidation['errors'], 'Parameter halaman pengajuan tidak valid.'),
            ]);
        }

        $userId = (int) (authUserId() ?? 0);
        $applicant = $this->userModel->findById($userId);
        $letterId = (int) ($_GET['letter_id'] ?? 0);
        $activityId = (int) ($_GET['activity_id'] ?? 0);
        $activityRow = null;
        if ($activityId > 0) {
            $activityRow = $this->findActivityForUser($selectedCategory, $activityId, $userId);
        }
        $activeActivityOptions = [];
        if (
            ($selectedType === 'izin' && in_array($selectedCategory, ['penelitian', 'pengabdian', 'hilirisasi'], true))
            || ($selectedType === 'tugas' && in_array($selectedCategory, ['penelitian', 'pengabdian', 'hilirisasi'], true))
        ) {
            $activeActivityOptions = $this->getActiveActivitiesForUser($selectedCategory, $userId);
        }

        $formData = [
            'applicant_id' => $userId,
            'research_title' => '',
            'research_scheme' => '',
            'funding_source' => '',
            'funding_source_other' => '',
            'faculty' => '',
            'research_year' => (string) date('Y'),
            'researcher_name' => '',
            'members' => '',
            'purpose' => '',
            'institution' => '',
            'address' => '',
            'city' => '',
            'start_date' => '',
            'end_date' => '',
            'subject' => '',
            'destination' => '',
            'destination_position' => '',
            'notes' => '',
            'phone' => '',
            'unit' => '',
            'applicant_email' => '',
            'nidn' => '',
            'name' => '',
            'penelitian_id' => 0,
            'lokasi_penugasan' => '',
            'instansi_tujuan' => '',
            'tanggal_mulai' => '',
            'tanggal_selesai' => '',
            'deskripsi_kegiatan' => '',
            'uraian_tugas' => '',
            'file_proposal' => '',
            'file_instrumen' => '',
            'file_pendukung_lain' => '',
        ];

        if ($applicant !== null) {
            $formData['name'] = (string) ($applicant['name'] ?? '');
            $formData['nidn'] = (string) ($applicant['nidn'] ?? '');
            $formData['applicant_email'] = (string) ($applicant['email'] ?? '');
            $formData['phone'] = (string) ($applicant['phone'] ?? '');
            $formData['faculty'] = (string) ($applicant['faculty'] ?? $applicant['fakultas'] ?? '');
            $formData['unit'] = (string) ($applicant['study_program'] ?? $applicant['unit'] ?? '');
        }

        if ($activityRow !== null) {
            $formData['research_title'] = (string) ($activityRow['judul'] ?? '');
            $formData['research_scheme'] = (string) (($activityRow['ruang_lingkup'] ?? '') !== '' ? $activityRow['ruang_lingkup'] : ($activityRow['skema'] ?? ''));
            $formData['funding_source'] = (string) ($activityRow['sumber_dana'] ?? '');
            $formData['research_year'] = (string) ($activityRow['tahun'] ?? $formData['research_year']);
            $formData['researcher_name'] = (string) ($activityRow['ketua'] ?? '');
            $formData['members'] = (string) ($activityRow['anggota'] ?? '');
            $formData['institution'] = (string) ($activityRow['mitra'] ?? '');
            $formData['address'] = (string) ($activityRow['lokasi'] ?? '');
            $formData['city'] = trim((string) ($formData['city'] ?? '')) !== ''
                ? (string) $formData['city']
                : (string) ($activityRow['lokasi'] ?? '');
            $formData['start_date'] = (string) ($activityRow['tanggal_mulai'] ?? '');
            $formData['end_date'] = (string) ($activityRow['tanggal_selesai'] ?? '');
            $formData['purpose'] = (string) ($activityRow['deskripsi'] ?? '');
            $formData['activity_id'] = (int) ($activityRow['id'] ?? 0);
            $formData['penelitian_id'] = (int) ($activityRow['id'] ?? 0);
        }

        if ($selectedType === 'tugas' && in_array($selectedCategory, ['penelitian', 'pengabdian', 'hilirisasi'], true) && $letterId > 0) {
            $draftLetter = $this->letterModel->getByIdForDetail($letterId);
            if (
                $draftLetter !== null
                && $this->isEditableResubmissionStatus((string) ($draftLetter['status'] ?? ''))
                && $this->letterModel->isOwnedByUser($letterId, $userId)
            ) {
                $taskDetail = $this->suratTugasPenelitianModel->findByLetterId($letterId);
                if ($taskDetail !== null) {
                    $activityId = $this->resolveTaskActivityIdFromArray($taskDetail);
                    $detailActivityType = strtolower((string) ($taskDetail['activity_type'] ?? $selectedCategory));
                    $activityRow = $activityId > 0 ? $this->findActivityForUser($detailActivityType, $activityId, $userId) : null;
                    $formData = array_merge($formData, [
                        'activity_id' => $activityId,
                        'penelitian_id' => (int) ($taskDetail['penelitian_id'] ?? 0),
                        'activity_type' => $detailActivityType,
                        'lokasi_penugasan' => (string) ($taskDetail['lokasi_penugasan'] ?? ''),
                        'instansi_tujuan' => (string) ($taskDetail['instansi_tujuan'] ?? ''),
                        'tanggal_mulai' => (string) ($taskDetail['tanggal_mulai'] ?? ''),
                        'tanggal_selesai' => (string) ($taskDetail['tanggal_selesai'] ?? ''),
                        'deskripsi_kegiatan' => (string) ($taskDetail['uraian_tugas'] ?? ''),
                        'uraian_tugas' => (string) ($taskDetail['uraian_tugas'] ?? ''),
                        'file_proposal' => (string) ($taskDetail['file_proposal'] ?? ''),
                        'file_instrumen' => (string) ($taskDetail['file_instrumen'] ?? ''),
                        'file_pendukung_lain' => (string) ($taskDetail['file_pendukung_lain'] ?? ''),
                    ]);
                    if ($activityRow !== null) {
                        $formData['research_title'] = (string) ($activityRow['judul'] ?? $formData['research_title']);
                        $formData['research_scheme'] = (string) (($activityRow['ruang_lingkup'] ?? '') !== '' ? $activityRow['ruang_lingkup'] : ($activityRow['skema'] ?? $formData['research_scheme']));
                        $formData['research_year'] = (string) ($activityRow['tahun'] ?? $formData['research_year']);
                        $formData['researcher_name'] = (string) ($activityRow['ketua'] ?? $formData['researcher_name']);
                        $formData['members'] = (string) ($activityRow['anggota'] ?? $formData['members']);
                    }
                }
            }
        }

        $subjectMap = [
            'penelitian' => [
                'kontrak' => 'Permohonan Surat Kontrak Penelitian',
                'izin' => 'Permohonan Surat Izin Penelitian',
                'tugas' => 'Permohonan Surat Tugas Penelitian',
            ],
            'pengabdian' => [
                'kontrak' => 'Permohonan Surat Kontrak Pengabdian Kepada Masyarakat',
                'izin' => 'Permohonan Surat Izin Pengabdian Kepada Masyarakat',
                'tugas' => 'Permohonan Surat Tugas Pengabdian Kepada Masyarakat',
            ],
            'hilirisasi' => [
                'kontrak' => 'Permohonan Surat Kontrak Hilirisasi',
                'izin' => 'Permohonan Surat Izin Pelaksanaan Hilirisasi',
                'tugas' => 'Permohonan Surat Tugas Pelaksanaan Hilirisasi',
            ],
        ];
        $formData['subject'] = (string) ($subjectMap[$selectedCategory][$selectedType] ?? 'Permohonan Surat');

        $fundingOptions = ['Hibah Dikti', 'Internal PT', 'Mandiri', 'Lainnya'];
        if ($formData['funding_source'] !== '' && !in_array((string) $formData['funding_source'], $fundingOptions, true)) {
            $formData['funding_source_other'] = (string) $formData['funding_source'];
            $formData['funding_source'] = 'Lainnya';
        }

        $contractRows = [];
        if ($selectedType === 'kontrak') {
            $contractRows = $this->buildContractSubmissionRows($selectedCategory, $userId);
        }

        $this->render('letters/submission_menu', [
            'pageTitle' => 'Ajukan Surat',
            'selectedCategory' => $selectedCategory,
            'selectedType' => $selectedType,
            'activityId' => $activityId,
            'activityType' => $selectedCategory,
            'formData' => $formData,
            'role' => 'dosen',
            'dosenOptions' => [],
            'letterId' => $letterId > 0 ? $letterId : null,
            'validationErrors' => [],
            'autoFillInfo' => $activityRow !== null ? 'Data surat diisi otomatis dari kegiatan yang dipilih.' : null,
            'activityDetailUrl' => $activityRow !== null ? ('?route=data-' . $selectedCategory . '-edit&id=' . $activityId) : null,
            'contractRows' => $contractRows,
            'activeActivityOptions' => $activeActivityOptions,
            'activityRow' => $activityRow,
            'successMessage' => $_GET['success'] ?? null,
            'errorMessage' => $_GET['error'] ?? null,
        ]);
    }

    public function submitContract(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || authRole() !== 'dosen') {
            $this->redirectToPath('ajukan-surat');
        }

        $activityType = strtolower(trim((string) ($_POST['activity_type'] ?? 'penelitian')));
        if (!in_array($activityType, ['penelitian', 'pengabdian', 'hilirisasi'], true)) {
            $activityType = 'penelitian';
        }

        $activityId = (int) ($_POST['activity_id'] ?? 0);
        $submitValidation = $this->validatePayload(
            [
                'activity_type' => $activityType,
                'activity_id' => (string) $activityId,
            ],
            [
                'activity_type' => 'required|in_list[penelitian,pengabdian,hilirisasi]',
                'activity_id' => 'required|integer|greater_than[0]',
            ]
        );
        if (!$submitValidation['valid']) {
            $this->redirectToPath('surat-saya', [
                'error' => $this->firstValidationError($submitValidation['errors'], 'Data kegiatan tidak valid.'),
            ]);
        }
        if ($activityId <= 0) {
            $this->redirectToPath('surat-saya', ['error' => 'Data kegiatan tidak valid.']);
        }

        $userId = (int) (authUserId() ?? 0);
        $activityRow = $this->findActivityForUser($activityType, $activityId, $userId);
        if ($activityRow === null) {
            $this->redirectToPath('surat-saya', ['error' => 'Kegiatan tidak ditemukan.']);
        }

        $latestSubmission = $this->letterModel->getLatestContractSubmissionByActivity($userId, $activityType, $activityId);
        $latestStatus = strtolower((string) ($latestSubmission['status'] ?? ''));
        if (in_array($latestStatus, ['diajukan', 'submitted', 'diverifikasi', 'approved', 'disetujui'], true)) {
            $this->redirectToPath('surat-saya', ['error' => 'Ajuan kontrak untuk kegiatan ini sedang diproses atau sudah disetujui.']);
        }

        $applicant = $this->userModel->findById($userId);
        if ($applicant === null) {
            $this->redirectToPath('surat-saya', ['error' => 'Data dosen tidak ditemukan.']);
        }

        $letterTypeId = $this->letterModel->getLetterTypeIdByCode('KONTRAK');
        if ($letterTypeId === null) {
            $this->redirectToPath('surat-saya', ['error' => 'Tipe surat belum tersedia.']);
        }

        $subjectMap = [
            'penelitian' => 'Permohonan Surat Kontrak Penelitian',
            'pengabdian' => 'Permohonan Surat Kontrak Pengabdian Kepada Masyarakat',
            'hilirisasi' => 'Permohonan Surat Kontrak Hilirisasi',
        ];
        $subject = (string) ($subjectMap[$activityType] ?? 'Permohonan Surat Kontrak');
        $chairman = $this->userModel->getDefaultChairman();
        $destinationName = (string) ($chairman['name'] ?? 'Ketua LPPM');
        $letterId = $this->letterModel->create([
            'letter_type_id' => $letterTypeId,
            'letter_number' => null,
            'subject' => $subject,
            'applicant_id' => $userId,
            'destination' => $destinationName,
            'institution' => (string) ($activityRow['mitra'] ?? 'LPPM'),
            'letter_date' => date('Y-m-d'),
            'status' => 'diajukan',
            'file_pdf' => null,
            'created_by' => $userId,
        ]);

        $activityRef = $this->buildActivityRefToken($activityType, $activityId);
        $fundingSource = (string) ($activityRow['sumber_dana'] ?? '-');
        if ($fundingSource === '') {
            $fundingSource = '-';
        }
        $activityAttachmentPaths = $this->normalizeTaskAttachmentPaths([
            'file_proposal' => (string) ($activityRow['file_proposal'] ?? ''),
            'file_instrumen' => (string) ($activityRow['file_instrumen'] ?? ''),
            'file_pendukung_lain' => (string) ($activityRow['file_pendukung_lain'] ?? ''),
        ]);
        $attachmentPath = $this->encodeAttachmentPaths($activityAttachmentPaths);
        $contractSourceKey = ContractSettingModel::resolveSourceKeyFromFunding($fundingSource);
        $contractSourceRef = $this->buildContractSourceToken($contractSourceKey);
        $notes = 'Ajuan kontrak dari menu Ajukan Surat. ' . $activityRef . ' ' . $contractSourceRef;

        $this->researchPermitModel->create([
            'letter_id' => $letterId,
            'research_title' => (string) ($activityRow['judul'] ?? '-'),
            'research_location' => (string) ($activityRow['lokasi'] ?? '-'),
            'start_date' => (string) ($activityRow['tanggal_mulai'] ?? date('Y-m-d')),
            'end_date' => (string) ($activityRow['tanggal_selesai'] ?? date('Y-m-d')),
            'researcher_name' => (string) ($activityRow['ketua'] ?? (string) ($applicant['name'] ?? '-')),
            'institution' => (string) ($activityRow['mitra'] ?? 'LPPM'),
            'supervisor' => (string) ($activityRow['ketua'] ?? (string) ($applicant['name'] ?? '-')),
            'research_scheme' => (string) (($activityRow['ruang_lingkup'] ?? '') !== '' ? $activityRow['ruang_lingkup'] : ($activityRow['skema'] ?? '-')),
            'funding_source' => $fundingSource,
            'research_year' => (string) ($activityRow['tahun'] ?? date('Y')),
            'phone' => (string) ($applicant['phone'] ?? '-'),
            'unit' => (string) ($applicant['unit'] ?? '-'),
            'faculty' => (string) ($applicant['faculty'] ?? '-'),
            'purpose' => (string) ($activityRow['deskripsi'] ?? '-'),
            'destination_position' => 'Kepala LPPM',
            'address' => (string) ($activityRow['lokasi'] ?? '-'),
            'city' => (string) ($activityRow['lokasi'] ?? '-'),
            'attachment_file' => $attachmentPath,
            'notes' => $notes,
            'applicant_email' => (string) ($applicant['email'] ?? '-'),
            'members' => (string) ($activityRow['anggota'] ?? '-'),
        ]);

        $this->sendLetterNotificationSafely('submitted', $letterId, $applicant);

        $this->redirectToPath('surat-saya', ['success' => 'Ajuan kontrak berhasil dikirim. Status saat ini: Menunggu Diproses.']);
    }

    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToPath('ajukan-surat/penelitian', ['surat_kind' => 'izin']);
        }

        if (trim((string) ($_POST['form_variant'] ?? '')) === 'task_research') {
            $this->storeResearchTaskLetter();
            return;
        }

        $role = (string) authRole();
        $loggedInUserId = (int) (authUserId() ?? 0);
        $letterId = (int) ($_POST['letter_id'] ?? 0);
        $action = (string) ($_POST['submit_action'] ?? 'draft');
        $status = $action === 'submit' ? 'diajukan' : 'draft';
        $storeValidation = $this->validatePayload(
            [
                'letter_id' => (string) $letterId,
                'submit_action' => (string) $action,
            ],
            [
                'letter_id' => 'permit_empty|integer|greater_than_equal_to[0]',
                'submit_action' => 'required|in_list[draft,submit]',
            ]
        );
        if (!$storeValidation['valid']) {
            $this->redirectBackWithError($this->firstValidationError($storeValidation['errors'], 'Parameter simpan surat tidak valid.'), $letterId);
            return;
        }

        $applicantId = isAdminPanelRole($role) ? (int) ($_POST['applicant_id'] ?? 0) : $loggedInUserId;
        $applicant = $this->userModel->findById($applicantId);
        if ($applicant === null || ($applicant['role'] ?? '') !== 'dosen') {
            $this->redirectBackWithError('Pemohon dosen tidak valid.');
            return;
        }

        $fields = [
            'name' => trim((string) ($_POST['name'] ?? '')),
            'nidn' => trim((string) ($_POST['nidn'] ?? '')),
            'activity_type' => strtolower(trim((string) ($_POST['activity_type'] ?? ''))),
            'activity_id' => (int) ($_POST['activity_id'] ?? 0),
            'research_title' => trim((string) ($_POST['research_title'] ?? '')),
            'research_scheme' => trim((string) ($_POST['research_scheme'] ?? '')),
            'funding_source' => trim((string) ($_POST['funding_source'] ?? '')),
            'funding_source_other' => trim((string) ($_POST['funding_source_other'] ?? '')),
            'faculty' => trim((string) ($_POST['faculty'] ?? '')),
            'research_year' => trim((string) ($_POST['research_year'] ?? '')),
            'researcher_name' => trim((string) ($_POST['researcher_name'] ?? '')),
            'members' => trim((string) ($_POST['members'] ?? '')),
            'purpose' => trim((string) ($_POST['purpose'] ?? '')),
            'institution' => trim((string) ($_POST['institution'] ?? '')),
            'address' => trim((string) ($_POST['address'] ?? '')),
            'city' => trim((string) ($_POST['city'] ?? '')),
            'start_date' => trim((string) ($_POST['start_date'] ?? '')),
            'end_date' => trim((string) ($_POST['end_date'] ?? '')),
            'subject' => trim((string) ($_POST['subject'] ?? 'Permohonan Surat Izin Penelitian')),
            'destination' => trim((string) ($_POST['destination'] ?? '')),
            'destination_position' => trim((string) ($_POST['destination_position'] ?? '')),
            'notes' => trim((string) ($_POST['notes'] ?? '')),
            'phone' => trim((string) ($_POST['phone'] ?? (string) ($applicant['phone'] ?? ''))),
            'unit' => trim((string) ($_POST['unit'] ?? (string) ($applicant['unit'] ?? ''))),
            'applicant_email' => trim((string) ($_POST['applicant_email'] ?? (string) ($applicant['email'] ?? ''))),
        ];
        if (!in_array($fields['activity_type'], ['penelitian', 'pengabdian', 'hilirisasi'], true)) {
            $fields['activity_type'] = $this->resolveActivityTypeFromSubject((string) ($fields['subject'] ?? ''));
        }
        if (!in_array($fields['activity_type'], ['penelitian', 'pengabdian', 'hilirisasi'], true)) {
            $fields['activity_type'] = 'penelitian';
        }

        if ($fields['activity_id'] > 0) {
            $activityRef = $this->buildActivityRefToken($fields['activity_type'], (int) $fields['activity_id']);
            $notes = trim((string) ($fields['notes'] ?? ''));
            if ($notes === '') {
                $fields['notes'] = $activityRef;
            } elseif (stripos($notes, $activityRef) === false) {
                $fields['notes'] = $notes . "\n" . $activityRef;
            }
        }

        $permitAttachmentsInput = [
            'file_proposal' => trim((string) ($_POST['file_proposal'] ?? $_POST['attachment_file'] ?? '')) !== ''
                ? trim((string) ($_POST['file_proposal'] ?? $_POST['attachment_file'] ?? ''))
                : ($_FILES['file_proposal'] ?? $_FILES['attachment_file'] ?? null),
            'file_instrumen' => trim((string) ($_POST['file_instrumen'] ?? '')) !== ''
                ? trim((string) ($_POST['file_instrumen'] ?? ''))
                : ($_FILES['file_instrumen'] ?? null),
            'file_pendukung_lain' => trim((string) ($_POST['file_pendukung_lain'] ?? '')) !== ''
                ? trim((string) ($_POST['file_pendukung_lain'] ?? ''))
                : ($_FILES['file_pendukung_lain'] ?? null),
        ];

        // Mode edit dari detail surat hanya menampilkan sebagian field.
        // Lengkapi field wajib yang tidak dikirim form dengan data detail tersimpan.
        if ($letterId > 0) {
            $existingDetail = $this->researchPermitModel->findByLetterId($letterId);
            if ($existingDetail !== null) {
                $existingLetter = $this->letterModel->getByIdForDetail($letterId);
                if ($existingLetter !== null) {
                    $existingDetail = $this->hydrateContractDetailFromActivityRef($existingLetter, $existingDetail);
                }

                $fieldFallbackMap = [
                    'research_title' => 'research_title',
                    'research_scheme' => 'research_scheme',
                    'funding_source' => 'funding_source',
                    'research_year' => 'research_year',
                    'researcher_name' => 'researcher_name',
                    'members' => 'members',
                    'purpose' => 'purpose',
                    'institution' => 'institution',
                    'address' => 'address',
                    'city' => 'city',
                    'start_date' => 'start_date',
                    'end_date' => 'end_date',
                    'destination_position' => 'destination_position',
                    'notes' => 'notes',
                    'phone' => 'phone',
                    'unit' => 'unit',
                    'applicant_email' => 'applicant_email',
                    'faculty' => 'faculty',
                ];

                foreach ($fieldFallbackMap as $fieldKey => $detailKey) {
                    if (trim((string) ($fields[$fieldKey] ?? '')) === '') {
                        $fields[$fieldKey] = trim((string) ($existingDetail[$detailKey] ?? $fields[$fieldKey] ?? ''));
                    }
                }
            }
        }

        $validationErrors = $this->validateResearchPermitInput($fields, $permitAttachmentsInput);
        if (!empty($validationErrors)) {
            if ($letterId > 0) {
                $this->redirectToPath('persuratan/' . $letterId, [
                    'edit' => '1',
                    'error' => 'Mohon periksa kembali data form.',
                ]);
            }
            $activityType = $this->resolveActivityTypeFromSubject((string) ($fields['subject'] ?? ''));
            if (!in_array($activityType, ['penelitian', 'pengabdian', 'hilirisasi'], true)) {
                $activityType = 'penelitian';
            }
            $this->redirectToPath('ajukan-surat/' . $activityType, [
                'surat_kind' => 'izin',
                'error' => 'Mohon periksa kembali data form.',
            ]);
        }

        $fundingSourceValue = $fields['funding_source'];
        if ($fundingSourceValue === 'Lainnya') {
            $fundingSourceValue = $fields['funding_source_other'];
        }

        try {
            $letterTypeId = $this->letterModel->getLetterTypeIdByCode('IZIN');
            if ($letterTypeId === null) {
                throw new RuntimeException('Tipe surat IZIN belum tersedia.');
            }

            $subjectForLetter = $fields['subject'] !== '' ? $fields['subject'] : $fields['research_title'];
            $createdBy = isAdminPanelRole($role) ? $loggedInUserId : $applicantId;

            if ($letterId > 0) {
                $existingLetter = $this->letterModel->getByIdForDetail($letterId);
                if ($existingLetter === null) {
                    throw new RuntimeException('Data surat draft tidak ditemukan.');
                }
                if (!$this->isEditableResubmissionStatus((string) ($existingLetter['status'] ?? ''))) {
                    throw new RuntimeException('Hanya surat draft atau surat yang perlu diperbaiki yang dapat diubah.');
                }
                if (!isAdminPanelRole($role) && !$this->letterModel->isOwnedByUser($letterId, $loggedInUserId)) {
                    throw new RuntimeException('Anda tidak memiliki akses ke surat ini.');
                }

                $currentDetail = $this->researchPermitModel->findByLetterId($letterId);
                $existingAttachmentPaths = $this->parsePermitAttachmentPaths((string) ($currentDetail['attachment_file'] ?? ''));
                $attachmentPaths = $this->uploadPermitAttachments($permitAttachmentsInput, $existingAttachmentPaths);
                $attachmentPath = $this->composePermitAttachmentValue($attachmentPaths);

                $this->letterModel->updateById($letterId, [
                    'subject' => $subjectForLetter,
                    'applicant_id' => $applicantId,
                    'destination' => $fields['destination'],
                    'institution' => $fields['institution'],
                    'letter_date' => date('Y-m-d'),
                    'status' => $status,
                    'created_by' => $createdBy,
                ]);

                $this->researchPermitModel->updateByLetterId($letterId, [
                    'research_title' => $fields['research_title'],
                    'research_location' => $fields['institution'],
                    'start_date' => $fields['start_date'],
                    'end_date' => $fields['end_date'],
                    'researcher_name' => $fields['researcher_name'],
                    'institution' => $fields['institution'],
                    'supervisor' => $fields['researcher_name'],
                    'research_scheme' => $fields['research_scheme'],
                    'funding_source' => $fundingSourceValue,
                    'research_year' => $fields['research_year'],
                    'phone' => $fields['phone'],
                    'unit' => $fields['unit'],
                    'faculty' => $fields['faculty'],
                    'purpose' => $fields['purpose'],
                    'destination_position' => $fields['destination_position'],
                    'address' => $fields['address'],
                    'city' => $fields['city'],
                    'attachment_file' => $attachmentPath,
                    'notes' => $fields['notes'],
                    'applicant_email' => $fields['applicant_email'],
                    'members' => $fields['members'],
                ]);

                if ($role === 'dosen') {
                    $this->syncActivityDataFromPermitSubmission(
                        $existingLetter,
                        $fields,
                        $fundingSourceValue,
                        $attachmentPaths
                    );
                }
            } else {
                $attachmentPaths = $this->uploadPermitAttachments($permitAttachmentsInput, [
                    'file_proposal' => null,
                    'file_instrumen' => null,
                    'file_pendukung_lain' => null,
                ]);
                $attachmentPath = $this->composePermitAttachmentValue($attachmentPaths);

                $letterId = $this->letterModel->create([
                    'letter_type_id' => $letterTypeId,
                    'letter_number' => null,
                    'subject' => $subjectForLetter,
                    'applicant_id' => $applicantId,
                    'destination' => $fields['destination'],
                    'institution' => $fields['institution'],
                    'letter_date' => date('Y-m-d'),
                    'status' => $status,
                    'file_pdf' => null,
                    'created_by' => $createdBy,
                ]);

                $this->researchPermitModel->create([
                    'letter_id' => $letterId,
                    'research_title' => $fields['research_title'],
                    'research_location' => $fields['institution'],
                    'start_date' => $fields['start_date'],
                    'end_date' => $fields['end_date'],
                    'researcher_name' => $fields['researcher_name'],
                    'institution' => $fields['institution'],
                    'supervisor' => $fields['researcher_name'],
                    'research_scheme' => $fields['research_scheme'],
                    'funding_source' => $fundingSourceValue,
                    'research_year' => $fields['research_year'],
                    'phone' => $fields['phone'],
                    'unit' => $fields['unit'],
                    'faculty' => $fields['faculty'],
                    'purpose' => $fields['purpose'],
                    'destination_position' => $fields['destination_position'],
                    'address' => $fields['address'],
                    'city' => $fields['city'],
                    'attachment_file' => $attachmentPath,
                    'notes' => $fields['notes'],
                    'applicant_email' => $fields['applicant_email'],
                    'members' => $fields['members'],
                ]);
            }

            $logAction = $status === 'diajukan'
                ? 'Mengajukan surat: ' . $subjectForLetter
                : 'Menyimpan draft surat: ' . $subjectForLetter;
            logActivity('persuratan', $logAction, $letterId);
            if ($status === 'diajukan') {
                $this->sendLetterNotificationSafely('submitted', $letterId, $applicant);
            }

            if ($role === 'dosen') {
                $this->redirectToPath('surat-saya', ['success' => 'Data surat berhasil disimpan.']);
            }

            $this->redirectToPath('persuratan/' . $letterId, ['success' => 'Data surat berhasil disimpan.']);
        } catch (Throwable $e) {
            $this->redirectBackWithError($e->getMessage(), $letterId);
            return;
        }
    }

    public function show(): void
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $showValidation = $this->validatePayload(
            ['id' => (string) $id],
            ['id' => 'required|integer|greater_than[0]']
        );
        if (!$showValidation['valid']) {
            $this->redirectToPath('persuratan', [
                'error' => $this->firstValidationError($showValidation['errors'], 'ID surat tidak valid.'),
            ]);
        }
        if ($id <= 0) {
            $this->redirectToPath('persuratan', ['error' => 'ID surat tidak valid.']);
        }

        $letter = $this->letterModel->getByIdForDetail($id);
        if ($letter === null) {
            $this->redirectToPath('persuratan', ['error' => 'Data surat tidak ditemukan.']);
        }

        if (authRole() === 'dosen' && !$this->letterModel->isOwnedByUser($id, (int) (authUserId() ?? 0))) {
            $this->redirectToPath('persuratan', ['error' => 'Anda tidak memiliki akses ke surat ini.']);
        }

        if (strtoupper((string) ($letter['letter_type_code'] ?? '')) === 'TUGAS') {
            $detail = $this->suratTugasPenelitianModel->findByLetterId($id);
            if ($detail === null) {
                $this->redirectToPath('persuratan', ['error' => 'Detail surat tugas tidak ditemukan.']);
            }

            $subjectLower = strtolower((string) ($letter['subject'] ?? ''));
            $activityType = strtolower((string) ($detail['activity_type'] ?? (str_contains($subjectLower, 'pengabdian') ? 'pengabdian' : (str_contains($subjectLower, 'hilirisasi') ? 'hilirisasi' : 'penelitian'))));
            $taskActivityId = $this->resolveTaskActivityIdFromArray($detail);
            $penelitian = $this->findActivityForUser($activityType, $taskActivityId, (int) ($letter['applicant_id'] ?? 0));
            if ($penelitian === null) {
                // Fallback aman: detail surat tugas tetap bisa dibuka meski relasi kegiatan berubah/terhapus.
                $penelitian = [
                    'id' => $taskActivityId,
                    'judul' => (string) ($letter['task_title'] ?? $detail['judul'] ?? ''),
                    'skema' => (string) ($letter['task_scheme'] ?? $detail['skema'] ?? ''),
                    'ruang_lingkup' => (string) ($detail['ruang_lingkup'] ?? ''),
                    'sumber_dana' => (string) ($letter['task_funding_source'] ?? $detail['funding_source'] ?? ''),
                    'tahun' => (string) ($letter['task_year'] ?? $detail['research_year'] ?? ''),
                    'lokasi' => (string) ($letter['task_research_location'] ?? $detail['lokasi'] ?? $detail['research_location'] ?? ''),
                    'ketua' => (string) ($letter['task_leader_name'] ?? $detail['researcher_name'] ?? ''),
                    'anggota' => (string) ($letter['task_members'] ?? $detail['members'] ?? ''),
                    'deskripsi' => (string) ($detail['uraian_tugas'] ?? ''),
                    'mitra' => (string) ($detail['instansi_tujuan'] ?? ''),
                ];
            }
            $this->render($this->resolveTaskDetailView($activityType), [
                'pageTitle' => match ($activityType) {
                    'pengabdian' => 'Detail Surat Tugas Pengabdian',
                    'hilirisasi' => 'Detail Surat Tugas Pelaksanaan Hilirisasi',
                    default => 'Detail Surat Tugas Penelitian',
                },
                'letter' => $letter,
                'detail' => $detail,
                'penelitian' => $penelitian ?? [],
                'activityType' => $activityType,
                'applicantProfile' => $this->userModel->findById((int) ($letter['applicant_id'] ?? 0)),
                'chairmanProfile' => $this->userModel->getDefaultChairman(),
                'successMessage' => $_GET['success'] ?? null,
            ]);
            return;
        }

        $detail = $this->researchPermitModel->findByLetterId($id);
        if ($detail === null) {
            $this->redirectToPath('persuratan', ['error' => 'Detail surat tidak ditemukan.']);
        }
        $detail = $this->hydrateContractDetailFromActivityRef($letter, $detail);
        $detail = $this->hydrateTargetLuaranFromActivityRef($letter, $detail);
        $detail = $this->hydrateTotalApprovedFundFromActivityRef($letter, $detail);

        $isContractEditRequest = ((string) ($_GET['edit'] ?? '') === '1')
            && $this->resolveLetterKindFromSubject((string) ($letter['subject'] ?? '')) === 'kontrak'
            && normalizeRoleName((string) authRole()) === 'dosen';
        if ($isContractEditRequest) {
            $activityEditUrl = $this->resolveActivityEditUrlFromLetterDetail($letter, $detail);
            if ($activityEditUrl !== null) {
                header('Location: ' . $activityEditUrl);
                exit;
            }
        }

        $this->render($this->resolvePermitDetailView($this->resolveActivityTypeFromSubject((string) ($letter['subject'] ?? ''))), [
            'pageTitle' => $this->resolvePermitDetailPageTitle($letter),
            'letter' => $letter,
            'detail' => $detail,
            'applicantProfile' => $this->userModel->findById((int) ($letter['applicant_id'] ?? 0)),
            'chairmanProfile' => $this->userModel->getDefaultChairman(),
            'successMessage' => $_GET['success'] ?? null,
        ]);
    }

    public function approve(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToPath('persuratan', ['error' => 'Metode persetujuan surat tidak valid.']);
        }

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $type = trim((string) ($_POST['type'] ?? $_GET['type'] ?? ''));
        $return = strtolower(trim((string) ($_POST['return'] ?? $_GET['return'] ?? '')));
        $approveValidation = $this->validatePayload(
            [
                'id' => (string) $id,
                'type' => $type,
                'return' => $return === '' ? 'list' : $return,
            ],
            [
                'id' => 'required|integer|greater_than[0]',
                'type' => 'permit_empty|in_list[izin,tugas,pengantar,kontrak]',
                'return' => 'required|in_list[list,show]',
            ]
        );
        if (!$approveValidation['valid']) {
            $this->redirectToPath('persuratan', [
                'error' => $this->firstValidationError($approveValidation['errors'], 'Parameter persetujuan surat tidak valid.'),
            ]);
        }
        $redirectQuery = $this->appBasePath() . '/persuratan' . ($type !== '' ? '?type=' . urlencode($type) : '');
        $redirectShow = $this->appBasePath() . '/persuratan/' . $id;
        if ($id <= 0) {
            $separator = str_contains($redirectQuery, '?') ? '&' : '?';
            header('Location: ' . $redirectQuery . $separator . 'error=' . urlencode('ID surat tidak valid.'));
            exit;
        }

        try {
            $letterBeforeApprove = $this->letterModel->getByIdForDetail($id);
            if ($letterBeforeApprove === null) {
                throw new RuntimeException('Data surat tidak ditemukan.');
            }

            if (trim((string) ($letterBeforeApprove['letter_number'] ?? '')) === '') {
                $jenisSuratCode = $this->resolveJenisSuratCodeFromSubject((string) ($letterBeforeApprove['subject'] ?? ''));
                $skemaCode = $this->resolveSkemaCodeForLetterNumber($id, $letterBeforeApprove);
                $numberGenerator = new LetterNumberGeneratorService();
                $generatedNumber = $numberGenerator->generate($jenisSuratCode, $skemaCode);
                $this->letterModel->updateLetterNumber($id, $generatedNumber);
            }

            $this->letterModel->markAsApproved($id);
            $this->letterModel->ensureVerificationTokenForApproved($id);
            logActivity('persuratan', 'Menyetujui surat: ' . (string) ($letterBeforeApprove['subject'] ?? ('ID ' . $id)), $id);
            $this->sendLetterNotificationSafely(
                'approved',
                $id,
                $this->userModel->findById((int) ($letterBeforeApprove['applicant_id'] ?? 0))
            );

            $target = $return === 'show' ? $redirectShow : $redirectQuery;
            $separator = str_contains($target, '?') && !str_ends_with($target, '?') ? '&' : '?';
            header('Location: ' . $target . $separator . 'success=' . urlencode('Surat disetujui. Silakan terbitkan surat untuk menghasilkan PDF resmi.'));
            exit;
        } catch (Throwable $e) {
            $target = $return === 'show' ? $redirectShow : $redirectQuery;
            $separator = str_contains($target, '?') && !str_ends_with($target, '?') ? '&' : '?';
            header('Location: ' . $target . $separator . 'error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    public function reject(): void
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $type = trim((string) ($_GET['type'] ?? ''));
        $return = strtolower(trim((string) ($_GET['return'] ?? ($_POST['return'] ?? ''))));
        $revisionNote = trim((string) ($_POST['revision_note'] ?? ''));
        $rejectValidation = $this->validatePayload(
            [
                'id' => (string) $id,
                'type' => $type,
                'return' => $return === '' ? 'list' : $return,
                'revision_note' => $revisionNote,
            ],
            [
                'id' => 'required|integer|greater_than[0]',
                'type' => 'permit_empty|in_list[izin,tugas,pengantar,kontrak]',
                'return' => 'required|in_list[list,show]',
                'revision_note' => 'permit_empty|max_length[2000]',
            ]
        );
        if (!$rejectValidation['valid']) {
            $this->redirectToPath('persuratan', [
                'error' => $this->firstValidationError($rejectValidation['errors'], 'Parameter perbaikan surat tidak valid.'),
            ]);
        }
        $redirectQuery = $this->appBasePath() . '/persuratan' . ($type !== '' ? '?type=' . urlencode($type) : '');
        $redirectShow = $this->appBasePath() . '/persuratan/' . $id;
        if ($id <= 0) {
            $separator = str_contains($redirectQuery, '?') ? '&' : '?';
            header('Location: ' . $redirectQuery . $separator . 'error=' . urlencode('ID surat tidak valid.'));
            exit;
        }

        try {
            $letterBeforeRevision = $this->letterModel->getByIdForDetail($id);
            if ($letterBeforeRevision === null) {
                throw new RuntimeException('Data surat tidak ditemukan.');
            }

            $currentStatus = strtolower(trim((string) ($letterBeforeRevision['status'] ?? '')));
            $allowedRevisionStatuses = ['diajukan', 'submitted', 'diverifikasi', 'menunggu diproses'];
            if (!in_array($currentStatus, $allowedRevisionStatuses, true)) {
                throw new RuntimeException('Surat yang sudah disetujui/terbit tidak dapat dikembalikan untuk perbaikan.');
            }

            if ($revisionNote !== '') {
                if (strtoupper((string) ($letterBeforeRevision['letter_type_code'] ?? '')) === 'TUGAS') {
                    $taskDetail = $this->suratTugasPenelitianModel->findByLetterId($id);
                    if ($taskDetail !== null) {
                        $this->suratTugasPenelitianModel->updateByLetterId($id, [
                            'activity_id' => $this->resolveTaskActivityIdFromArray($taskDetail),
                            'penelitian_id' => $this->resolveTaskActivityIdFromArray($taskDetail),
                            'activity_type' => (string) ($taskDetail['activity_type'] ?? 'penelitian'),
                            'lokasi_penugasan' => (string) ($taskDetail['lokasi_penugasan'] ?? ''),
                            'instansi_tujuan' => (string) ($taskDetail['instansi_tujuan'] ?? ''),
                            'tanggal_mulai' => (string) ($taskDetail['tanggal_mulai'] ?? ''),
                            'tanggal_selesai' => (string) ($taskDetail['tanggal_selesai'] ?? ''),
                            'dasar_penugasan' => (string) ($taskDetail['dasar_penugasan'] ?? ''),
                            'uraian_tugas' => (string) ($taskDetail['uraian_tugas'] ?? ''),
                            'keterangan' => $revisionNote,
                            'file_proposal' => (string) ($taskDetail['file_proposal'] ?? ''),
                            'file_instrumen' => (string) ($taskDetail['file_instrumen'] ?? ''),
                            'file_pendukung_lain' => (string) ($taskDetail['file_pendukung_lain'] ?? ''),
                            'file_sk' => (string) ($taskDetail['file_sk'] ?? ''),
                            'nomor_surat' => (string) ($taskDetail['nomor_surat'] ?? ''),
                            'status' => (string) ($taskDetail['status'] ?? 'diajukan'),
                        ]);
                    }
                } else {
                    $permitDetail = $this->researchPermitModel->findByLetterId($id);
                    if ($permitDetail !== null) {
                        $activityRef = '';
                        $currentNotes = (string) ($permitDetail['notes'] ?? '');
                        if (preg_match('/__ACTIVITY_REF__\[[^\]]+\]/', $currentNotes, $matches)) {
                            $activityRef = ' ' . trim((string) $matches[0]);
                        }
                        $this->researchPermitModel->updateByLetterId($id, [
                            'research_title' => (string) ($permitDetail['research_title'] ?? ''),
                            'research_location' => (string) ($permitDetail['research_location'] ?? ''),
                            'start_date' => (string) ($permitDetail['start_date'] ?? ''),
                            'end_date' => (string) ($permitDetail['end_date'] ?? ''),
                            'researcher_name' => (string) ($permitDetail['researcher_name'] ?? ''),
                            'institution' => (string) ($permitDetail['institution'] ?? ''),
                            'supervisor' => (string) ($permitDetail['supervisor'] ?? ''),
                            'research_scheme' => (string) ($permitDetail['research_scheme'] ?? ''),
                            'funding_source' => (string) ($permitDetail['funding_source'] ?? ''),
                            'research_year' => (string) ($permitDetail['research_year'] ?? ''),
                            'phone' => (string) ($permitDetail['phone'] ?? ''),
                            'unit' => (string) ($permitDetail['unit'] ?? ''),
                            'faculty' => (string) ($permitDetail['faculty'] ?? ''),
                            'purpose' => (string) ($permitDetail['purpose'] ?? ''),
                            'destination_position' => (string) ($permitDetail['destination_position'] ?? 'Kepala LPPM'),
                            'address' => (string) ($permitDetail['address'] ?? ''),
                            'city' => (string) ($permitDetail['city'] ?? ''),
                            'attachment_file' => (string) ($permitDetail['attachment_file'] ?? ''),
                            'notes' => trim($revisionNote . $activityRef),
                            'applicant_email' => (string) ($permitDetail['applicant_email'] ?? ''),
                            'members' => (string) ($permitDetail['members'] ?? ''),
                        ]);
                    }
                }
            }

            $this->letterModel->markAsNeedsRevision($id);
            logActivity('persuratan', 'Mengembalikan surat untuk perbaikan: ' . (string) ($letterBeforeRevision['subject'] ?? ('ID ' . $id)), $id);
            $target = $return === 'show' ? $redirectShow : $redirectQuery;
            $separator = str_contains($target, '?') && !str_ends_with($target, '?') ? '&' : '?';
            header('Location: ' . $target . $separator . 'success=' . urlencode('Surat dikembalikan ke dosen untuk diperbaiki.'));
            exit;
        } catch (Throwable $e) {
            $target = $return === 'show' ? $redirectShow : $redirectQuery;
            $separator = str_contains($target, '?') && !str_ends_with($target, '?') ? '&' : '?';
            header('Location: ' . $target . $separator . 'error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    public function headDirectUpdate(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToPath('persuratan');
        }

        $role = normalizeRoleName((string) authRole());
        if ($role !== 'kepala_lppm') {
            $this->redirectToPath($this->adminDashboardPath());
        }

        $letterId = (int) ($_POST['letter_id'] ?? 0);
        $headValidation = $this->validatePayload(
            ['letter_id' => (string) $letterId],
            ['letter_id' => 'required|integer|greater_than[0]']
        );
        if (!$headValidation['valid']) {
            $this->redirectToPath('persuratan', [
                'error' => $this->firstValidationError($headValidation['errors'], 'ID surat tidak valid.'),
            ]);
        }
        if ($letterId <= 0) {
            $this->redirectToPath('persuratan', ['error' => 'ID surat tidak valid.']);
        }

        $redirectShow = $this->appBasePath() . '/persuratan/' . $letterId;

        try {
            $letter = $this->letterModel->getByIdForDetail($letterId);
            if ($letter === null) {
                throw new RuntimeException('Data surat tidak ditemukan.');
            }

            if (!$this->canHeadDirectEditStatus((string) ($letter['status'] ?? ''))) {
                throw new RuntimeException('Status surat ini tidak bisa diedit langsung oleh Kepala LPPM.');
            }

            $letterTypeCode = strtoupper((string) ($letter['letter_type_code'] ?? ''));
            $letterSubject = (string) ($letter['subject'] ?? '');
            $letterKind = $this->resolveLetterKindFromSubject($letterSubject);
            if ($letterTypeCode === 'KONTRAK' || $letterKind === 'kontrak') {
                throw new RuntimeException('Edit langsung untuk pengajuan surat kontrak dinonaktifkan. Data kontrak otomatis mengikuti data kegiatan.');
            }

            if ($letterTypeCode === 'TUGAS') {
                $this->updateTaskLetterByHead($letter, $letterId);
            } else {
                $this->updatePermitLetterByHead($letter, $letterId);
            }

            logActivity('persuratan', 'Kepala LPPM mengedit langsung surat: ' . (string) ($letter['subject'] ?? ('ID ' . $letterId)), $letterId);
            header('Location: ' . $redirectShow . '?success=' . urlencode('Perubahan surat berhasil disimpan langsung oleh Kepala LPPM.'));
            exit;
        } catch (Throwable $e) {
            header('Location: ' . $redirectShow . '?error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    public function myLetters(): void
    {
        if (authRole() !== 'dosen') {
            $this->redirectToPath($this->adminDashboardPath());
        }

        $userId = (int) (authUserId() ?? 0);
        if ($userId <= 0) {
            $this->redirectToPath('login');
        }

        $filters = [
            'letter_type_id' => (int) ($_GET['letter_type_id'] ?? 0),
            'status' => trim((string) ($_GET['status'] ?? '')),
            'date_from' => trim((string) ($_GET['date_from'] ?? '')),
            'date_to' => trim((string) ($_GET['date_to'] ?? '')),
        ];
        $myLettersValidation = $this->validatePayload(
            [
                'letter_type_id' => (string) $filters['letter_type_id'],
                'status' => $filters['status'],
                'date_from' => $filters['date_from'],
                'date_to' => $filters['date_to'],
            ],
            [
                'letter_type_id' => 'permit_empty|integer|greater_than_equal_to[0]',
                'status' => 'permit_empty|in_list[draft,diajukan,submitted,diverifikasi,menunggu diproses,perlu diperbaiki,ditolak,rejected,disetujui,approved,surat terbit,terbit]',
                'date_from' => 'permit_empty|valid_date[Y-m-d]',
                'date_to' => 'permit_empty|valid_date[Y-m-d]',
            ]
        );
        if (!$myLettersValidation['valid']) {
            $this->redirectToPath('surat-saya', [
                'error' => $this->firstValidationError($myLettersValidation['errors'], 'Filter surat tidak valid.'),
            ]);
        }

        $myLetters = $this->letterModel->getMyLetters($userId, $filters);
        $stats = $this->letterModel->countMyLettersByStatus($userId);
        $letterTypes = $this->getLetterTypes();

        $this->render('letters/my_letters', [
            'pageTitle' => 'Surat Saya',
            'myLetters' => $myLetters,
            'stats' => $stats,
            'letterTypes' => $letterTypes,
            'filters' => $filters,
            'successMessage' => $_GET['success'] ?? null,
            'errorMessage' => $_GET['error'] ?? null,
        ]);
    }

    public function myLetterDetail(): void
    {
        if (authRole() !== 'dosen') {
            $this->redirectToPath($this->adminDashboardPath());
        }

        $userId = (int) (authUserId() ?? 0);
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $number = trim((string) ($_GET['number'] ?? ''));
        $detailValidation = $this->validatePayload(
            [
                'id' => (string) $id,
                'number' => $number,
            ],
            [
                'id' => 'permit_empty|integer|greater_than_equal_to[0]',
                'number' => 'permit_empty|max_length[120]',
            ]
        );
        if (!$detailValidation['valid']) {
            $this->redirectToPath('surat-saya', [
                'error' => $this->firstValidationError($detailValidation['errors'], 'Parameter tidak valid.'),
            ]);
        }
        if ($id <= 0 && $number !== '') {
            $byNumber = $this->letterModel->getMyLetterByNumber($number, $userId);
            if ($byNumber !== null) {
                $id = (int) ($byNumber['id'] ?? 0);
            }
        }

        if ($id <= 0 || $userId <= 0) {
            $this->redirectToPath('surat-saya', ['error' => 'Parameter tidak valid.']);
        }

        $letter = $this->letterModel->getByIdForDetail($id);
        if ($letter === null || !$this->letterModel->canViewByUser($id, $userId)) {
            $this->redirectToPath('surat-saya', ['error' => 'Data surat tidak ditemukan atau bukan milik Anda.']);
        }
        $isMemberReadOnly = !$this->letterModel->isOwnedByUser($id, $userId);

        if (strtoupper((string) ($letter['letter_type_code'] ?? '')) === 'TUGAS') {
            $detail = $this->suratTugasPenelitianModel->findByLetterId($id);
            if ($detail === null) {
                $this->redirectToPath('surat-saya', ['error' => 'Detail surat tugas tidak ditemukan.']);
            }

            $subjectLower = strtolower((string) ($letter['subject'] ?? ''));
            $activityType = strtolower((string) ($detail['activity_type'] ?? (str_contains($subjectLower, 'pengabdian') ? 'pengabdian' : (str_contains($subjectLower, 'hilirisasi') ? 'hilirisasi' : 'penelitian'))));
            $taskActivityId = $this->resolveTaskActivityIdFromArray($detail);
            $penelitian = $this->findActivityForUser($activityType, $taskActivityId, $userId);
            if ($penelitian === null) {
                // Fallback aman: detail surat tugas tetap bisa dibuka meski relasi kegiatan berubah/terhapus.
                $penelitian = [
                    'id' => $taskActivityId,
                    'judul' => (string) ($letter['task_title'] ?? $detail['judul'] ?? ''),
                    'skema' => (string) ($letter['task_scheme'] ?? $detail['skema'] ?? ''),
                    'ruang_lingkup' => (string) ($detail['ruang_lingkup'] ?? ''),
                    'sumber_dana' => (string) ($letter['task_funding_source'] ?? $detail['funding_source'] ?? ''),
                    'tahun' => (string) ($letter['task_year'] ?? $detail['research_year'] ?? ''),
                    'lokasi' => (string) ($letter['task_research_location'] ?? $detail['lokasi'] ?? $detail['research_location'] ?? ''),
                    'ketua' => (string) ($letter['task_leader_name'] ?? $detail['researcher_name'] ?? ''),
                    'anggota' => (string) ($letter['task_members'] ?? $detail['members'] ?? ''),
                    'deskripsi' => (string) ($detail['uraian_tugas'] ?? ''),
                    'mitra' => (string) ($detail['instansi_tujuan'] ?? ''),
                ];
            }
              $this->render($this->resolveTaskDetailView($activityType), [
                'pageTitle' => match ($activityType) {
                    'pengabdian' => 'Detail Surat Tugas Pengabdian',
                    'hilirisasi' => 'Detail Surat Tugas Pelaksanaan Hilirisasi',
                    default => 'Detail Surat Tugas Penelitian',
                },
                'letter' => $letter,
                'detail' => $detail,
                  'penelitian' => $penelitian ?? [],
                  'activityType' => $activityType,
                  'applicantProfile' => $this->userModel->findById((int) ($letter['applicant_id'] ?? 0)),
                  'chairmanProfile' => $this->userModel->getDefaultChairman(),
                  'isMemberReadOnly' => $isMemberReadOnly,
              ]);
              return;
          }

        $detail = $this->researchPermitModel->findByLetterId($id);
        if ($detail === null) {
            $this->redirectToPath('surat-saya', ['error' => 'Detail surat tidak ditemukan.']);
        }
        $detail = $this->hydrateContractDetailFromActivityRef($letter, $detail);
        $detail = $this->hydrateTargetLuaranFromActivityRef($letter, $detail);
        $detail = $this->hydrateTotalApprovedFundFromActivityRef($letter, $detail);

        $isContractEditRequest = ((string) ($_GET['edit'] ?? '') === '1')
            && $this->resolveLetterKindFromSubject((string) ($letter['subject'] ?? '')) === 'kontrak';
        if ($isContractEditRequest) {
            $activityEditUrl = $this->resolveActivityEditUrlFromLetterDetail($letter, $detail);
            if ($activityEditUrl !== null) {
                header('Location: ' . $activityEditUrl);
                exit;
            }
        }

          $this->render($this->resolvePermitDetailView($this->resolveActivityTypeFromSubject((string) ($letter['subject'] ?? ''))), [
              'pageTitle' => $this->resolvePermitDetailPageTitle($letter),
              'letter' => $letter,
              'detail' => $detail,
              'applicantProfile' => $this->userModel->findById((int) ($letter['applicant_id'] ?? 0)),
              'chairmanProfile' => $this->userModel->getDefaultChairman(),
              'isMemberReadOnly' => $isMemberReadOnly,
          ]);
      }

    private function validateResearchPermitInput(array $fields, array $attachments): array
    {
        $validation = $this->validatePayload(
            [
                'name' => trim((string) ($fields['name'] ?? '')),
                'nidn' => trim((string) ($fields['nidn'] ?? '')),
                'research_title' => trim((string) ($fields['research_title'] ?? '')),
                'research_scheme' => trim((string) ($fields['research_scheme'] ?? '')),
                'funding_source' => trim((string) ($fields['funding_source'] ?? '')),
                'funding_source_other' => trim((string) ($fields['funding_source_other'] ?? '')),
                'faculty' => trim((string) ($fields['faculty'] ?? '')),
                'research_year' => trim((string) ($fields['research_year'] ?? '')),
                'researcher_name' => trim((string) ($fields['researcher_name'] ?? '')),
                'members' => trim((string) ($fields['members'] ?? '')),
                'purpose' => trim((string) ($fields['purpose'] ?? '')),
                'institution' => trim((string) ($fields['institution'] ?? '')),
                'address' => trim((string) ($fields['address'] ?? '')),
                'city' => trim((string) ($fields['city'] ?? '')),
                'start_date' => trim((string) ($fields['start_date'] ?? '')),
                'end_date' => trim((string) ($fields['end_date'] ?? '')),
                'subject' => trim((string) ($fields['subject'] ?? '')),
                'destination' => trim((string) ($fields['destination'] ?? '')),
                'destination_position' => trim((string) ($fields['destination_position'] ?? '')),
                'phone' => trim((string) ($fields['phone'] ?? '')),
                'unit' => trim((string) ($fields['unit'] ?? '')),
                'applicant_email' => trim((string) ($fields['applicant_email'] ?? '')),
            ],
            [
                'name' => 'required|min_length[3]|max_length[160]',
                'nidn' => 'required|numeric|min_length[6]|max_length[30]',
                'research_title' => 'required|min_length[5]|max_length[255]',
                'research_scheme' => 'required|max_length[160]',
                'funding_source' => 'required|max_length[120]',
                'funding_source_other' => 'permit_empty|max_length[120]',
                'faculty' => 'required|max_length[160]',
                'research_year' => 'required|exact_length[4]|regex_match[/^\d{4}$/]',
                'researcher_name' => 'required|max_length[160]',
                'members' => 'required|max_length[2000]',
                'purpose' => 'required|max_length[5000]',
                'institution' => 'required|max_length[255]',
                'address' => 'required|max_length[255]',
                'city' => 'required|max_length[120]',
                'start_date' => 'required|valid_date[Y-m-d]',
                'end_date' => 'required|valid_date[Y-m-d]',
                'subject' => 'required|max_length[255]',
                'destination' => 'required|max_length[160]',
                'destination_position' => 'required|max_length[160]',
                'phone' => 'required|regex_match[/^[0-9+\\-\\s]{8,25}$/]',
                'unit' => 'required|max_length[150]',
                'applicant_email' => 'required|valid_email|max_length[160]',
            ],
            [
                'research_year' => ['regex_match' => 'Tahun penelitian harus 4 digit angka.'],
                'phone' => ['regex_match' => 'Format nomor HP tidak valid.'],
            ]
        );
        $errors = $validation['errors'];

        if (($fields['funding_source'] ?? '') === 'Lainnya' && trim((string) ($fields['funding_source_other'] ?? '')) === '') {
            $errors['funding_source_other'] = 'Sumber dana lainnya wajib diisi.';
        }

        if (!empty($fields['start_date']) && !empty($fields['end_date'])) {
            if (strtotime((string) $fields['end_date']) < strtotime((string) $fields['start_date'])) {
                $errors['end_date'] = 'Tanggal selesai tidak boleh lebih kecil dari tanggal mulai.';
            }
        }

        foreach ($attachments as $attachment) {
            if (is_string($attachment)) {
                $link = trim($attachment);
                if ($link !== '' && !$this->isValidHttpUrl($link)) {
                    $errors['attachment_file'] = 'Lampiran harus berupa link URL yang valid (http/https).';
                    break;
                }
                continue;
            }

            if (!is_array($attachment) || (int) ($attachment['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ((int) ($attachment['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                $errors['attachment_file'] = 'Upload lampiran gagal.';
                break;
            }

            $size = (int) ($attachment['size'] ?? 0);
            if ($size > 2 * 1024 * 1024) {
                $errors['attachment_file'] = 'Ukuran file maksimal 2 MB.';
                break;
            }

            $allowedExt = ['pdf', 'doc', 'docx'];
            $ext = strtolower((string) pathinfo((string) ($attachment['name'] ?? ''), PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExt, true)) {
                $errors['attachment_file'] = 'Lampiran hanya boleh PDF, DOC, atau DOCX.';
                break;
            }
        }

        if (($_POST['statement_true'] ?? '') !== '1') {
            $errors['statement_true'] = 'Pernyataan wajib dicentang.';
        }
        if (($_POST['statement_rules'] ?? '') !== '1') {
            $errors['statement_rules'] = 'Pernyataan ketentuan wajib dicentang.';
        }

        return $errors;
    }

    private function storeResearchTaskLetter(): void
    {
        $userId = (int) (authUserId() ?? 0);
        if ($userId <= 0) {
            $this->redirectToPath('login');
        }

        $letterId = (int) ($_POST['letter_id'] ?? 0);
        $fields = $this->collectResearchTaskInput();
        $fields['activity_id'] = (int) ($fields['activity_id'] ?? 0);
        $fields['penelitian_id'] = (int) ($fields['penelitian_id'] ?? $fields['activity_id'] ?? 0);
        $fields['activity_type'] = in_array(($fields['activity_type'] ?? ''), ['penelitian', 'pengabdian', 'hilirisasi'], true) ? $fields['activity_type'] : 'penelitian';
        $status = 'diajukan';

        $activityRow = $fields['penelitian_id'] > 0 ? $this->findActivityForUser($fields['activity_type'], $fields['penelitian_id'], $userId) : null;
        $validationErrors = $this->validateResearchTaskInput($fields, $activityRow);

        if (!empty($validationErrors)) {
            $this->renderResearchTaskSubmissionMenu($fields, $validationErrors, $letterId, $activityRow, 'Mohon periksa kembali data form.');
            return;
        }

        try {
            $letterTypeId = $this->letterModel->getLetterTypeIdByCode('TUGAS');
            if ($letterTypeId === null) {
                throw new RuntimeException('Tipe surat TUGAS belum tersedia.');
            }

            $existingDetail = $letterId > 0 ? $this->suratTugasPenelitianModel->findByLetterId($letterId) : null;
            $proposalPath = $this->resolveTaskAttachmentValue(
                (string) ($fields['file_proposal'] ?? ''),
                $_FILES['file_proposal'] ?? null,
                (string) ($existingDetail['file_proposal'] ?? ''),
                'proposal'
            );
            $instrumenPath = $this->resolveTaskAttachmentValue(
                (string) ($fields['file_instrumen'] ?? ''),
                $_FILES['file_instrumen'] ?? null,
                (string) ($existingDetail['file_instrumen'] ?? ''),
                'instrumen'
            );
            $pendukungPath = $this->resolveTaskAttachmentValue(
                (string) ($fields['file_pendukung_lain'] ?? ''),
                $_FILES['file_pendukung_lain'] ?? null,
                (string) ($existingDetail['file_pendukung_lain'] ?? ''),
                'pendukung'
            );
            $letterNumber = $letterId > 0
                ? (string) (($this->letterModel->getById($letterId)['letter_number'] ?? '') ?: '')
                : '';

            $subject = match ($fields['activity_type']) {
                'pengabdian' => 'Permohonan Surat Tugas Pengabdian Kepada Masyarakat',
                'hilirisasi' => 'Permohonan Surat Tugas Pelaksanaan Hilirisasi',
                default => 'Permohonan Surat Tugas Penelitian',
            };
            $chairman = $this->userModel->getDefaultChairman();
            $destinationName = (string) ($chairman['name'] ?? 'Ketua LPPM');

            if ($letterId > 0) {
                $existingLetter = $this->letterModel->getByIdForDetail($letterId);
                if ($existingLetter === null) {
                    throw new RuntimeException('Data surat draft tidak ditemukan.');
                }
                if (!$this->isEditableResubmissionStatus((string) ($existingLetter['status'] ?? ''))) {
                    throw new RuntimeException('Hanya surat draft atau surat yang perlu diperbaiki yang dapat diubah.');
                }
                if (!$this->letterModel->isOwnedByUser($letterId, $userId)) {
                    throw new RuntimeException('Anda tidak memiliki akses ke surat ini.');
                }

                $this->letterModel->updateById($letterId, [
                    'subject' => $subject,
                    'applicant_id' => $userId,
                    'destination' => $destinationName,
                    'institution' => $fields['instansi_tujuan'] !== '' ? $fields['instansi_tujuan'] : $fields['lokasi_penugasan'],
                    'letter_date' => date('Y-m-d'),
                    'status' => $status,
                    'created_by' => $userId,
                ]);

                $this->suratTugasPenelitianModel->updateByLetterId($letterId, [
                    'activity_type' => $fields['activity_type'],
                    'activity_id' => $fields['activity_id'],
                    'penelitian_id' => $fields['penelitian_id'],
                    'lokasi_penugasan' => $fields['lokasi_penugasan'],
                    'instansi_tujuan' => $fields['instansi_tujuan'] !== '' ? $fields['instansi_tujuan'] : null,
                    'tanggal_mulai' => $fields['tanggal_mulai'],
                    'tanggal_selesai' => $fields['tanggal_selesai'],
                    'dasar_penugasan' => '-',
                    'uraian_tugas' => $fields['deskripsi_kegiatan'],
                    'keterangan' => null,
                    'file_proposal' => $proposalPath,
                    'file_instrumen' => $instrumenPath,
                    'file_pendukung_lain' => $pendukungPath,
                    'file_sk' => (string) ($existingDetail['file_sk'] ?? null),
                    'nomor_surat' => $letterNumber !== '' ? $letterNumber : null,
                    'status' => $status,
                ]);
            } else {
                $letterId = $this->letterModel->create([
                    'letter_type_id' => $letterTypeId,
                    'letter_number' => null,
                    'subject' => $subject,
                    'applicant_id' => $userId,
                    'destination' => $destinationName,
                    'institution' => $fields['instansi_tujuan'] !== '' ? $fields['instansi_tujuan'] : $fields['lokasi_penugasan'],
                    'letter_date' => date('Y-m-d'),
                    'status' => $status,
                    'file_pdf' => null,
                    'created_by' => $userId,
                ]);

                $this->suratTugasPenelitianModel->create([
                    'letter_id' => $letterId,
                    'activity_type' => $fields['activity_type'],
                    'activity_id' => $fields['activity_id'],
                    'penelitian_id' => $fields['penelitian_id'],
                    'lokasi_penugasan' => $fields['lokasi_penugasan'],
                    'instansi_tujuan' => $fields['instansi_tujuan'] !== '' ? $fields['instansi_tujuan'] : null,
                    'tanggal_mulai' => $fields['tanggal_mulai'],
                    'tanggal_selesai' => $fields['tanggal_selesai'],
                    'dasar_penugasan' => '-',
                    'uraian_tugas' => $fields['deskripsi_kegiatan'],
                    'keterangan' => null,
                    'file_proposal' => $proposalPath,
                    'file_instrumen' => $instrumenPath,
                    'file_pendukung_lain' => $pendukungPath,
                    'file_sk' => null,
                    'nomor_surat' => null,
                    'status' => $status,
                ]);
            }

            $successMessage = match ($fields['activity_type']) {
                'pengabdian' => 'Surat tugas pengabdian berhasil diajukan.',
                'hilirisasi' => 'Surat tugas pelaksanaan hilirisasi berhasil diajukan.',
                default => 'Surat tugas penelitian berhasil diajukan.',
            };
            $this->sendLetterNotificationSafely(
                'submitted',
                $letterId,
                $this->userModel->findById($userId)
            );
            $this->redirectToPath('surat-saya', ['success' => $successMessage]);
        } catch (Throwable $e) {
            $this->renderResearchTaskSubmissionMenu($fields, [], $letterId, $activityRow, $e->getMessage());
        }
    }

    private function collectResearchTaskInput(): array
    {
        $activityType = trim((string) ($_POST['activity_type'] ?? 'penelitian'));
        $subject = match ($activityType) {
            'pengabdian' => 'Permohonan Surat Tugas Pengabdian Kepada Masyarakat',
            'hilirisasi' => 'Permohonan Surat Tugas Pelaksanaan Hilirisasi',
            default => 'Permohonan Surat Tugas Penelitian',
        };

        return [
            'activity_type' => $activityType,
            'activity_id' => (int) ($_POST['activity_id'] ?? $_POST['penelitian_id'] ?? 0),
            'penelitian_id' => (int) ($_POST['penelitian_id'] ?? $_POST['activity_id'] ?? 0),
            'lokasi_penugasan' => trim((string) ($_POST['lokasi_penugasan'] ?? '')),
            'instansi_tujuan' => trim((string) ($_POST['instansi_tujuan'] ?? '')),
            'tanggal_mulai' => trim((string) ($_POST['tanggal_mulai'] ?? '')),
            'tanggal_selesai' => trim((string) ($_POST['tanggal_selesai'] ?? '')),
            'deskripsi_kegiatan' => trim((string) ($_POST['deskripsi_kegiatan'] ?? $_POST['uraian_tugas'] ?? '')),
            'file_proposal' => trim((string) ($_POST['file_proposal'] ?? $_POST['existing_file_proposal'] ?? '')),
            'file_instrumen' => trim((string) ($_POST['file_instrumen'] ?? $_POST['existing_file_instrumen'] ?? '')),
            'file_pendukung_lain' => trim((string) ($_POST['file_pendukung_lain'] ?? $_POST['existing_file_pendukung_lain'] ?? '')),
            'name' => trim((string) ($_POST['name'] ?? '')),
            'nidn' => trim((string) ($_POST['nidn'] ?? '')),
            'faculty' => trim((string) ($_POST['faculty'] ?? '')),
            'unit' => trim((string) ($_POST['unit'] ?? '')),
            'applicant_email' => trim((string) ($_POST['applicant_email'] ?? '')),
            'phone' => trim((string) ($_POST['phone'] ?? '')),
            'subject' => $subject,
        ];
    }

    private function sendLetterNotificationSafely(string $event, int $letterId, ?array $applicant = null): void
    {
        if ($letterId <= 0) {
            return;
        }

        try {
            $letter = $this->letterModel->getByIdForDetail($letterId);
            if ($letter === null) {
                return;
            }

            $resolvedApplicant = $applicant;
            if ($resolvedApplicant === null) {
                $resolvedApplicant = $this->userModel->findById((int) ($letter['applicant_id'] ?? 0));
            }

            if ($event === 'submitted') {
                $this->emailNotificationService->sendLetterSubmittedNotifications($letter, $resolvedApplicant);
                return;
            }

            if ($event === 'approved') {
                $this->emailNotificationService->sendLetterApprovedNotifications($letter, $resolvedApplicant);
                return;
            }

            if ($event === 'issued') {
                $this->emailNotificationService->sendLetterIssuedNotifications($letter, $resolvedApplicant);
            }
        } catch (Throwable $e) {
            error_log('[SAPA LPPM] Email notifikasi surat gagal: ' . $e->getMessage());
        }
    }

    private function validateResearchTaskInput(array $fields, ?array $activityRow): array
    {
        $validation = $this->validatePayload(
            [
                'activity_type' => (string) ($fields['activity_type'] ?? ''),
                'activity_id' => (string) ((int) ($fields['activity_id'] ?? 0)),
                'lokasi_penugasan' => trim((string) ($fields['lokasi_penugasan'] ?? '')),
                'instansi_tujuan' => trim((string) ($fields['instansi_tujuan'] ?? '')),
                'tanggal_mulai' => trim((string) ($fields['tanggal_mulai'] ?? '')),
                'tanggal_selesai' => trim((string) ($fields['tanggal_selesai'] ?? '')),
                'deskripsi_kegiatan' => trim((string) ($fields['deskripsi_kegiatan'] ?? '')),
                'name' => trim((string) ($fields['name'] ?? '')),
                'nidn' => trim((string) ($fields['nidn'] ?? '')),
                'faculty' => trim((string) ($fields['faculty'] ?? '')),
                'unit' => trim((string) ($fields['unit'] ?? '')),
                'applicant_email' => trim((string) ($fields['applicant_email'] ?? '')),
                'phone' => trim((string) ($fields['phone'] ?? '')),
            ],
            [
                'activity_type' => 'required|in_list[penelitian,pengabdian,hilirisasi]',
                'activity_id' => 'required|integer|greater_than[0]',
                'lokasi_penugasan' => 'required|max_length[255]',
                'instansi_tujuan' => 'permit_empty|max_length[255]',
                'tanggal_mulai' => 'required|valid_date[Y-m-d]',
                'tanggal_selesai' => 'required|valid_date[Y-m-d]',
                'deskripsi_kegiatan' => 'required|max_length[5000]',
                'name' => 'required|max_length[160]',
                'nidn' => 'required|numeric|min_length[6]|max_length[30]',
                'faculty' => 'required|max_length[160]',
                'unit' => 'required|max_length[150]',
                'applicant_email' => 'required|valid_email|max_length[160]',
                'phone' => 'required|regex_match[/^[0-9+\\-\\s]{8,25}$/]',
            ],
            [
                'phone' => ['regex_match' => 'Format nomor HP tidak valid.'],
            ]
        );
        $errors = $validation['errors'];

        if ($activityRow === null && (int) ($fields['activity_id'] ?? 0) > 0) {
            $errors['penelitian_id'] = 'Data kegiatan tidak ditemukan.';
        }

        if ($fields['tanggal_mulai'] !== '' && $fields['tanggal_selesai'] !== '' && strtotime($fields['tanggal_selesai']) < strtotime($fields['tanggal_mulai'])) {
            $errors['tanggal_selesai'] = 'Tanggal selesai tidak boleh lebih kecil dari tanggal mulai.';
        }

        foreach (['file_proposal', 'file_instrumen', 'file_pendukung_lain'] as $field) {
            $linkValue = trim((string) ($fields[$field] ?? ''));
            if ($linkValue !== '' && !$this->isValidHttpUrl($linkValue)) {
                $errors[$field] = 'Lampiran harus berupa link URL yang valid (http/https).';
            }

            $file = $_FILES[$field] ?? null;
            if ($file === null || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ((int) ($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                $errors[$field] = 'Upload file gagal.';
                continue;
            }
            $tmpPath = (string) ($file['tmp_name'] ?? '');
            if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
                $errors[$field] = 'File upload tidak valid.';
                continue;
            }
            if ((int) ($file['size'] ?? 0) > 2 * 1024 * 1024) {
                $errors[$field] = 'Ukuran file maksimal 2 MB.';
            }
            $ext = strtolower((string) pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
            $mime = $this->detectMimeType($tmpPath);
            if (!$this->isAllowedLetterAttachment($ext, $mime)) {
                $errors[$field] = 'File hanya boleh PDF, DOC, atau DOCX.';
            }
        }

        return $errors;
    }

    private function handleTaskAttachmentUpload(?array $attachment, ?string $existingPath, string $prefix): ?string
    {
        if ($attachment === null || (int) ($attachment['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return $existingPath;
        }

        if ((int) ($attachment['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload lampiran gagal.');
        }

        $tmpPath = (string) ($attachment['tmp_name'] ?? '');
        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            throw new RuntimeException('File lampiran tidak valid.');
        }
        if ((int) ($attachment['size'] ?? 0) > 2 * 1024 * 1024) {
            throw new RuntimeException('Ukuran lampiran maksimal 2 MB.');
        }
        $ext = strtolower((string) pathinfo((string) ($attachment['name'] ?? ''), PATHINFO_EXTENSION));
        $mime = $this->detectMimeType($tmpPath);
        if (!$this->isAllowedLetterAttachment($ext, $mime)) {
            throw new RuntimeException('Lampiran hanya boleh PDF, DOC, atau DOCX.');
        }

        $storageDir = __DIR__ . '/../../storage/uploads/letters';
        if (!is_dir($storageDir) && !mkdir($storageDir, 0755, true) && !is_dir($storageDir)) {
            throw new RuntimeException('Folder upload lampiran tidak dapat dibuat.');
        }

        $filename = $prefix . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(3)) . '.' . $ext;
        $fullPath = $storageDir . '/' . $filename;
        if (!move_uploaded_file($tmpPath, $fullPath)) {
            throw new RuntimeException('Gagal menyimpan lampiran.');
        }
        @chmod($fullPath, 0640);

        return 'storage/uploads/letters/' . $filename;
    }

    private function renderResearchTaskSubmissionMenu(array $fields, array $validationErrors, int $letterId, ?array $activityRow, ?string $errorMessage = null): void
    {
        $userId = (int) (authUserId() ?? 0);
        $applicant = $this->userModel->findById($userId);
        $activityType = in_array(($fields['activity_type'] ?? ''), ['penelitian', 'pengabdian', 'hilirisasi'], true) ? $fields['activity_type'] : 'penelitian';
        $formData = array_merge([
            'name' => (string) ($applicant['name'] ?? ''),
            'nidn' => (string) ($applicant['nidn'] ?? ''),
            'faculty' => (string) ($applicant['faculty'] ?? $applicant['fakultas'] ?? ''),
            'unit' => (string) ($applicant['study_program'] ?? $applicant['unit'] ?? ''),
            'applicant_email' => (string) ($applicant['email'] ?? ''),
            'phone' => (string) ($applicant['phone'] ?? ''),
            'research_year' => (string) date('Y'),
            'research_title' => '',
            'research_scheme' => '',
            'researcher_name' => '',
            'members' => '',
            'deskripsi_kegiatan' => '',
            'subject' => match ($activityType) {
                'pengabdian' => 'Permohonan Surat Tugas Pengabdian Kepada Masyarakat',
                'hilirisasi' => 'Permohonan Surat Tugas Pelaksanaan Hilirisasi',
                default => 'Permohonan Surat Tugas Penelitian',
            },
        ], $fields);

        if ($activityRow !== null) {
            $formData['research_title'] = (string) ($activityRow['judul'] ?? '');
            $formData['research_scheme'] = (string) (($activityRow['ruang_lingkup'] ?? '') !== '' ? $activityRow['ruang_lingkup'] : ($activityRow['skema'] ?? ''));
            $formData['research_year'] = (string) ($activityRow['tahun'] ?? $formData['research_year']);
            $formData['researcher_name'] = (string) ($activityRow['ketua'] ?? '');
            $formData['members'] = (string) ($activityRow['anggota'] ?? '');
            if (trim((string) ($formData['deskripsi_kegiatan'] ?? '')) === '') {
                $formData['deskripsi_kegiatan'] = (string) ($activityRow['deskripsi'] ?? '');
            }
        }

        $this->render('letters/submission_menu', [
            'pageTitle' => 'Ajukan Surat',
            'selectedCategory' => $activityType,
            'selectedType' => 'tugas',
            'activityId' => (int) ($fields['activity_id'] ?? $fields['penelitian_id'] ?? 0),
            'activityType' => $activityType,
            'formData' => $formData,
            'role' => 'dosen',
            'dosenOptions' => [],
            'letterId' => $letterId > 0 ? $letterId : null,
            'validationErrors' => $validationErrors,
            'autoFillInfo' => $activityRow !== null ? 'Data surat diisi otomatis dari kegiatan yang dipilih.' : null,
            'activityDetailUrl' => $activityRow !== null ? ('?route=data-' . $activityType . '-edit&id=' . (int) ($fields['activity_id'] ?? $fields['penelitian_id'] ?? 0)) : null,
            'contractRows' => [],
            'activeActivityOptions' => $this->getActiveActivitiesForUser($activityType, $userId),
            'activityRow' => $activityRow,
            'successMessage' => null,
            'errorMessage' => $errorMessage,
        ]);
    }

    private function handleAttachmentUpload(?array $attachment, ?string $existingPath): ?string
    {
        if ($attachment === null || (int) ($attachment['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return $existingPath;
        }

        if ((int) ($attachment['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload lampiran gagal.');
        }

        $tmpPath = (string) ($attachment['tmp_name'] ?? '');
        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            throw new RuntimeException('File lampiran tidak valid.');
        }
        if ((int) ($attachment['size'] ?? 0) > 2 * 1024 * 1024) {
            throw new RuntimeException('Ukuran lampiran maksimal 2 MB.');
        }
        $ext = strtolower((string) pathinfo((string) ($attachment['name'] ?? ''), PATHINFO_EXTENSION));
        $mime = $this->detectMimeType($tmpPath);
        if (!$this->isAllowedLetterAttachment($ext, $mime)) {
            throw new RuntimeException('Lampiran hanya boleh PDF, DOC, atau DOCX.');
        }

        $storageDir = __DIR__ . '/../../storage/uploads/letters';
        if (!is_dir($storageDir) && !mkdir($storageDir, 0755, true) && !is_dir($storageDir)) {
            throw new RuntimeException('Folder upload lampiran tidak dapat dibuat.');
        }

        $filename = 'attachment-' . date('YmdHis') . '-' . bin2hex(random_bytes(3)) . '.' . $ext;
        $fullPath = $storageDir . '/' . $filename;
        if (!move_uploaded_file($tmpPath, $fullPath)) {
            throw new RuntimeException('Gagal menyimpan lampiran.');
        }
        @chmod($fullPath, 0640);

        return 'storage/uploads/letters/' . $filename;
    }

    private function parsePermitAttachmentPaths(string $rawValue): array
    {
        $rawValue = trim($rawValue);
        $result = [
            'file_proposal' => null,
            'file_instrumen' => null,
            'file_pendukung_lain' => null,
        ];

        if ($rawValue === '') {
            return $result;
        }

        $decoded = json_decode($rawValue, true);
        if (is_array($decoded)) {
            $result['file_proposal'] = isset($decoded['file_proposal']) ? trim((string) $decoded['file_proposal']) : null;
            $result['file_instrumen'] = isset($decoded['file_instrumen']) ? trim((string) $decoded['file_instrumen']) : null;
            $result['file_pendukung_lain'] = isset($decoded['file_pendukung_lain']) ? trim((string) $decoded['file_pendukung_lain']) : null;

            return $result;
        }

        $result['file_proposal'] = $rawValue;

        return $result;
    }

    private function uploadPermitAttachments(array $attachmentInputs, array $existingPaths): array
    {
        $proposal = $this->resolvePermitAttachmentValue(
            $attachmentInputs['file_proposal'] ?? null,
            $existingPaths['file_proposal'] ?? null
        );
        $instrumen = $this->resolvePermitAttachmentValue(
            $attachmentInputs['file_instrumen'] ?? null,
            $existingPaths['file_instrumen'] ?? null
        );
        $pendukung = $this->resolvePermitAttachmentValue(
            $attachmentInputs['file_pendukung_lain'] ?? null,
            $existingPaths['file_pendukung_lain'] ?? null
        );

        return [
            'file_proposal' => $proposal !== null && $proposal !== '' ? $proposal : null,
            'file_instrumen' => $instrumen !== null && $instrumen !== '' ? $instrumen : null,
            'file_pendukung_lain' => $pendukung !== null && $pendukung !== '' ? $pendukung : null,
        ];
    }

    private function composePermitAttachmentValue(array $paths): ?string
    {
        $proposal = trim((string) ($paths['file_proposal'] ?? ''));
        $instrumen = trim((string) ($paths['file_instrumen'] ?? ''));
        $pendukung = trim((string) ($paths['file_pendukung_lain'] ?? ''));

        if ($proposal === '' && $instrumen === '' && $pendukung === '') {
            return null;
        }

        if ($instrumen === '' && $pendukung === '') {
            return $proposal;
        }

        return json_encode([
            'file_proposal' => $proposal,
            'file_instrumen' => $instrumen,
            'file_pendukung_lain' => $pendukung,
        ], JSON_UNESCAPED_SLASHES);
    }

    private function normalizeTaskAttachmentPaths(array $paths): array
    {
        return [
            'file_proposal' => trim((string) ($paths['file_proposal'] ?? '')),
            'file_instrumen' => trim((string) ($paths['file_instrumen'] ?? '')),
            'file_pendukung_lain' => trim((string) ($paths['file_pendukung_lain'] ?? '')),
        ];
    }

    private function encodeAttachmentPaths(array $paths): ?string
    {
        return $this->composePermitAttachmentValue($this->normalizeTaskAttachmentPaths($paths));
    }

    private function updatePermitLetterByHead(array $letter, int $letterId): void
    {
        $detail = $this->researchPermitModel->findByLetterId($letterId);
        if ($detail === null) {
            throw new RuntimeException('Detail surat izin/kontrak tidak ditemukan.');
        }

        $fields = [
            'purpose' => trim((string) ($_POST['purpose'] ?? (string) ($detail['purpose'] ?? ''))),
            'institution' => trim((string) ($_POST['institution'] ?? (string) ($detail['institution'] ?? $letter['institution'] ?? ''))),
            'address' => trim((string) ($_POST['address'] ?? (string) ($detail['address'] ?? ''))),
            'city' => trim((string) ($_POST['city'] ?? (string) ($detail['city'] ?? ''))),
            'start_date' => trim((string) ($_POST['start_date'] ?? (string) ($detail['start_date'] ?? ''))),
            'end_date' => trim((string) ($_POST['end_date'] ?? (string) ($detail['end_date'] ?? ''))),
            'destination' => trim((string) ($_POST['destination'] ?? (string) ($letter['destination'] ?? ''))),
            'destination_position' => trim((string) ($_POST['destination_position'] ?? (string) ($detail['destination_position'] ?? 'Kepala LPPM'))),
        ];
        $fieldValidation = $this->validatePayload(
            $fields,
            [
                'purpose' => 'required|max_length[5000]',
                'institution' => 'required|max_length[255]',
                'address' => 'required|max_length[255]',
                'city' => 'required|max_length[120]',
                'start_date' => 'required|valid_date[Y-m-d]',
                'end_date' => 'required|valid_date[Y-m-d]',
                'destination' => 'required|max_length[160]',
                'destination_position' => 'required|max_length[160]',
            ]
        );
        if (!$fieldValidation['valid']) {
            throw new RuntimeException($this->firstValidationError($fieldValidation['errors'], 'Data administrasi surat tidak valid.'));
        }

        foreach (['purpose', 'institution', 'address', 'city', 'start_date', 'end_date', 'destination', 'destination_position'] as $required) {
            if ($fields[$required] === '') {
                throw new RuntimeException('Field "' . $required . '" wajib diisi.');
            }
        }

        if (strtotime($fields['end_date']) < strtotime($fields['start_date'])) {
            throw new RuntimeException('Tanggal selesai tidak boleh lebih kecil dari tanggal mulai.');
        }

        $attachmentInputs = [
            'file_proposal' => trim((string) ($_POST['file_proposal'] ?? $_POST['attachment_file'] ?? '')) !== ''
                ? trim((string) ($_POST['file_proposal'] ?? $_POST['attachment_file'] ?? ''))
                : ($_FILES['file_proposal'] ?? $_FILES['attachment_file'] ?? null),
            'file_instrumen' => trim((string) ($_POST['file_instrumen'] ?? '')) !== ''
                ? trim((string) ($_POST['file_instrumen'] ?? ''))
                : ($_FILES['file_instrumen'] ?? null),
            'file_pendukung_lain' => trim((string) ($_POST['file_pendukung_lain'] ?? '')) !== ''
                ? trim((string) ($_POST['file_pendukung_lain'] ?? ''))
                : ($_FILES['file_pendukung_lain'] ?? null),
        ];
        $existingAttachmentPaths = $this->parsePermitAttachmentPaths((string) ($detail['attachment_file'] ?? ''));
        $attachmentPaths = $this->uploadPermitAttachments($attachmentInputs, $existingAttachmentPaths);
        $attachmentPath = $this->composePermitAttachmentValue($attachmentPaths);

        $this->letterModel->updateById($letterId, [
            'subject' => (string) ($letter['subject'] ?? ''),
            'applicant_id' => (int) ($letter['applicant_id'] ?? 0),
            'destination' => $fields['destination'],
            'institution' => $fields['institution'],
            'letter_date' => (string) ($letter['letter_date'] ?? date('Y-m-d')),
            'status' => (string) ($letter['status'] ?? 'diajukan'),
            'created_by' => (int) ($letter['created_by'] ?? $letter['applicant_id'] ?? 0),
        ]);

        $this->researchPermitModel->updateByLetterId($letterId, [
            'research_title' => (string) ($detail['research_title'] ?? ''),
            'research_location' => $fields['institution'],
            'start_date' => $fields['start_date'],
            'end_date' => $fields['end_date'],
            'researcher_name' => (string) ($detail['researcher_name'] ?? ''),
            'institution' => $fields['institution'],
            'supervisor' => (string) ($detail['supervisor'] ?? $detail['researcher_name'] ?? ''),
            'research_scheme' => (string) ($detail['research_scheme'] ?? ''),
            'funding_source' => (string) ($detail['funding_source'] ?? ''),
            'research_year' => (string) ($detail['research_year'] ?? ''),
            'phone' => (string) ($detail['phone'] ?? ''),
            'unit' => (string) ($detail['unit'] ?? ''),
            'faculty' => (string) ($detail['faculty'] ?? ''),
            'purpose' => $fields['purpose'],
            'destination_position' => $fields['destination_position'],
            'address' => $fields['address'],
            'city' => $fields['city'],
            'attachment_file' => $attachmentPath,
            'notes' => (string) ($detail['notes'] ?? ''),
            'applicant_email' => (string) ($detail['applicant_email'] ?? ''),
            'members' => (string) ($detail['members'] ?? ''),
        ]);
    }

    private function resolvePermitDetailView(string $activityType): string
    {
        $type = strtolower(trim($activityType));
        return match ($type) {
            'pengabdian' => 'letters/services_permit_detail',
            'hilirisasi' => 'letters/hilirisasi_permit_detail',
            default => 'letters/research_permit_detail',
        };
    }

    private function resolveTaskFormView(string $activityType): string
    {
        $type = strtolower(trim($activityType));
        return match ($type) {
            'pengabdian' => 'letters/task_services_form',
            'hilirisasi' => 'letters/task_hilirisasi_form',
            default => 'letters/task_research_form',
        };
    }

    private function resolveTaskDetailView(string $activityType): string
    {
        $type = strtolower(trim($activityType));
        return match ($type) {
            'pengabdian' => 'letters/task_services_detail',
            'hilirisasi' => 'letters/task_hilirisasi_detail',
            default => 'letters/task_research_detail',
        };
    }

    private function updateTaskLetterByHead(array $letter, int $letterId): void
    {
        $detail = $this->suratTugasPenelitianModel->findByLetterId($letterId);
        if ($detail === null) {
            throw new RuntimeException('Detail surat tugas tidak ditemukan.');
        }

        $fields = [
            'lokasi_penugasan' => trim((string) ($_POST['lokasi_penugasan'] ?? (string) ($detail['lokasi_penugasan'] ?? ''))),
            'instansi_tujuan' => trim((string) ($_POST['instansi_tujuan'] ?? (string) ($detail['instansi_tujuan'] ?? ''))),
            'tanggal_mulai' => trim((string) ($_POST['tanggal_mulai'] ?? (string) ($detail['tanggal_mulai'] ?? ''))),
            'tanggal_selesai' => trim((string) ($_POST['tanggal_selesai'] ?? (string) ($detail['tanggal_selesai'] ?? ''))),
            'deskripsi_kegiatan' => trim((string) ($_POST['deskripsi_kegiatan'] ?? $_POST['uraian_tugas'] ?? (string) ($detail['uraian_tugas'] ?? ''))),
        ];
        $fieldValidation = $this->validatePayload(
            $fields,
            [
                'lokasi_penugasan' => 'required|max_length[255]',
                'instansi_tujuan' => 'permit_empty|max_length[255]',
                'tanggal_mulai' => 'required|valid_date[Y-m-d]',
                'tanggal_selesai' => 'required|valid_date[Y-m-d]',
                'deskripsi_kegiatan' => 'required|max_length[5000]',
            ]
        );
        if (!$fieldValidation['valid']) {
            throw new RuntimeException($this->firstValidationError($fieldValidation['errors'], 'Data surat tugas tidak valid.'));
        }

        foreach (['lokasi_penugasan', 'tanggal_mulai', 'tanggal_selesai', 'deskripsi_kegiatan'] as $required) {
            if ($fields[$required] === '') {
                throw new RuntimeException('Field "' . $required . '" wajib diisi.');
            }
        }

        if (strtotime($fields['tanggal_selesai']) < strtotime($fields['tanggal_mulai'])) {
            throw new RuntimeException('Tanggal selesai tidak boleh lebih kecil dari tanggal mulai.');
        }

        $proposalPath = $this->resolveTaskAttachmentValue(
            trim((string) ($_POST['file_proposal'] ?? '')),
            $_FILES['file_proposal'] ?? null,
            (string) ($detail['file_proposal'] ?? ''),
            'proposal'
        );
        $instrumenPath = $this->resolveTaskAttachmentValue(
            trim((string) ($_POST['file_instrumen'] ?? '')),
            $_FILES['file_instrumen'] ?? null,
            (string) ($detail['file_instrumen'] ?? ''),
            'instrumen'
        );
        $pendukungPath = $this->resolveTaskAttachmentValue(
            trim((string) ($_POST['file_pendukung_lain'] ?? '')),
            $_FILES['file_pendukung_lain'] ?? null,
            (string) ($detail['file_pendukung_lain'] ?? ''),
            'pendukung'
        );

        $institutionForLetter = $fields['instansi_tujuan'] !== '' ? $fields['instansi_tujuan'] : $fields['lokasi_penugasan'];
        $activityType = strtolower(trim((string) ($detail['activity_type'] ?? 'penelitian')));
        $subject = match ($activityType) {
            'pengabdian' => 'Permohonan Surat Tugas Pengabdian Kepada Masyarakat',
            'hilirisasi' => 'Permohonan Surat Tugas Pelaksanaan Hilirisasi',
            default => 'Permohonan Surat Tugas Penelitian',
        };

        $this->letterModel->updateById($letterId, [
            'subject' => $subject,
            'applicant_id' => (int) ($letter['applicant_id'] ?? 0),
            'destination' => (string) ($letter['destination'] ?? ''),
            'institution' => $institutionForLetter,
            'letter_date' => (string) ($letter['letter_date'] ?? date('Y-m-d')),
            'status' => (string) ($letter['status'] ?? 'diajukan'),
            'created_by' => (int) ($letter['created_by'] ?? $letter['applicant_id'] ?? 0),
        ]);

        $this->suratTugasPenelitianModel->updateByLetterId($letterId, [
            'activity_type' => (string) ($detail['activity_type'] ?? 'penelitian'),
            'activity_id' => $this->resolveTaskActivityIdFromArray($detail),
            'penelitian_id' => $this->resolveTaskActivityIdFromArray($detail),
            'lokasi_penugasan' => $fields['lokasi_penugasan'],
            'instansi_tujuan' => $fields['instansi_tujuan'] !== '' ? $fields['instansi_tujuan'] : null,
            'tanggal_mulai' => $fields['tanggal_mulai'],
            'tanggal_selesai' => $fields['tanggal_selesai'],
            'dasar_penugasan' => '-',
            'uraian_tugas' => $fields['deskripsi_kegiatan'],
            'keterangan' => (string) ($detail['keterangan'] ?? null),
            'file_proposal' => $proposalPath,
            'file_instrumen' => $instrumenPath,
            'file_pendukung_lain' => $pendukungPath,
            'file_sk' => (string) ($detail['file_sk'] ?? null),
            'nomor_surat' => (string) ($detail['nomor_surat'] ?? ''),
            'status' => (string) ($detail['status'] ?? $letter['status'] ?? 'diajukan'),
        ]);
    }

    private function redirectBackWithError(string $message, int $letterId = 0): void
    {
        if ($letterId > 0) {
            $this->redirectToPath('persuratan/' . $letterId, [
                'edit' => '1',
                'error' => $message,
            ]);
        }

        $activityType = strtolower(trim((string) ($_POST['activity_type'] ?? '')));
        if (!in_array($activityType, ['penelitian', 'pengabdian', 'hilirisasi'], true)) {
            $activityType = $this->resolveActivityTypeFromSubject((string) ($_POST['subject'] ?? ''));
        }
        if (!in_array($activityType, ['penelitian', 'pengabdian', 'hilirisasi'], true)) {
            $activityType = 'penelitian';
        }

        $this->redirectToPath('ajukan-surat/' . $activityType, [
            'surat_kind' => 'izin',
            'error' => $message,
        ]);
    }

    private function normalizeStatusKey(string $status): string
    {
        return str_replace(' ', '_', strtolower(trim($status)));
    }

    private function isEditableResubmissionStatus(string $status): bool
    {
        return in_array($this->normalizeStatusKey($status), ['draft', 'perlu_diperbaiki', 'ditolak', 'rejected'], true);
    }

    private function canHeadDirectEditStatus(string $status): bool
    {
        $key = $this->normalizeStatusKey($status);
        return in_array($key, ['draft', 'diajukan', 'submitted', 'diverifikasi', 'menunggu_diproses', 'perlu_diperbaiki', 'ditolak', 'rejected'], true);
    }

    private function syncActivityDataFromPermitSubmission(array $letter, array $fields, string $fundingSourceValue, array $attachmentPaths): void
    {
        $ownerId = (int) ($letter['applicant_id'] ?? 0);
        if ($ownerId <= 0) {
            return;
        }

        $subject = strtolower((string) ($letter['subject'] ?? ''));
        $activityType = strtolower(trim((string) ($fields['activity_type'] ?? '')));
        if (!in_array($activityType, ['penelitian', 'pengabdian', 'hilirisasi'], true)) {
            $activityType = $this->resolveActivityTypeFromSubject($subject);
        }

        $activityId = (int) ($fields['activity_id'] ?? 0);
        if ($activityId <= 0) {
            [$resolvedType, $resolvedId] = $this->resolveContractActivityRef($subject, [
                'notes' => (string) ($fields['notes'] ?? ''),
                'institution' => (string) ($fields['institution'] ?? ''),
                'research_year' => (string) ($fields['research_year'] ?? ''),
                'research_title' => (string) ($fields['research_title'] ?? ''),
            ], $ownerId);
            if ($resolvedId > 0) {
                $activityType = $resolvedType;
                $activityId = $resolvedId;
            }
        }

        if ($activityId <= 0) {
            return;
        }

        $activityRow = $this->findActivityForUser($activityType, $activityId, $ownerId);
        if ($activityRow === null) {
            return;
        }

        $model = match ($activityType) {
            'pengabdian' => new PengabdianModel(),
            'hilirisasi' => new HilirisasiModel(),
            default => new PenelitianModel(),
        };

        $existingLocation = trim((string) ($activityRow['lokasi'] ?? ''));
        $cityFromForm = trim((string) ($fields['city'] ?? ''));
        $addressFromForm = trim((string) ($fields['address'] ?? ''));
        $resolvedLocation = $cityFromForm !== ''
            ? $cityFromForm
            : ($addressFromForm !== '' ? $addressFromForm : $existingLocation);

        $payload = $activityRow;
        $payload['judul'] = trim((string) ($fields['research_title'] ?? $activityRow['judul'] ?? ''));
        $payload['sumber_dana'] = trim($fundingSourceValue !== '' ? $fundingSourceValue : (string) ($activityRow['sumber_dana'] ?? ''));
        $payload['tahun'] = trim((string) ($fields['research_year'] ?? $activityRow['tahun'] ?? ''));
        $payload['ketua'] = trim((string) ($fields['researcher_name'] ?? $activityRow['ketua'] ?? ''));
        $payload['anggota'] = trim((string) ($fields['members'] ?? $activityRow['anggota'] ?? ''));
        $payload['lokasi'] = $resolvedLocation;
        $payload['mitra'] = trim((string) ($fields['institution'] ?? $activityRow['mitra'] ?? ''));
        $payload['tanggal_mulai'] = trim((string) ($fields['start_date'] ?? $activityRow['tanggal_mulai'] ?? ''));
        $payload['tanggal_selesai'] = trim((string) ($fields['end_date'] ?? $activityRow['tanggal_selesai'] ?? ''));
        $payload['deskripsi'] = trim((string) ($fields['purpose'] ?? $activityRow['deskripsi'] ?? ''));
        $payload['file_proposal'] = trim((string) ($attachmentPaths['file_proposal'] ?? $activityRow['file_proposal'] ?? ''));
        $payload['file_instrumen'] = trim((string) ($attachmentPaths['file_instrumen'] ?? $activityRow['file_instrumen'] ?? ''));
        $payload['file_pendukung_lain'] = trim((string) ($attachmentPaths['file_pendukung_lain'] ?? $activityRow['file_pendukung_lain'] ?? ''));

        $model->save($payload, $ownerId, $activityId);
    }

    private function getLetterTypes(): array
    {
        $pdo = db_pdo();
        $rows = $pdo->query('SELECT id, code, name FROM letter_types ORDER BY name ASC')->fetchAll();

        return $rows ?: [];
    }

    private function findActivityForUser(string $activityType, int $activityId, int $userId): ?array
    {
        if ($activityType === 'penelitian') {
            $model = new PenelitianModel();
            return $model->findOwnedById($activityId, $userId);
        }
        if ($activityType === 'pengabdian') {
            $model = new PengabdianModel();
            return $model->findOwnedById($activityId, $userId);
        }
        if ($activityType === 'hilirisasi') {
            $model = new HilirisasiModel();
            return $model->findOwnedById($activityId, $userId);
        }

        return null;
    }

    private function getActiveActivitiesForUser(string $activityType, int $userId): array
    {
        if ($activityType === 'pengabdian') {
            $model = new PengabdianModel();
        } elseif ($activityType === 'hilirisasi') {
            $model = new HilirisasiModel();
        } else {
            $model = new PenelitianModel();
        }
        $items = $model->getOwnedList($userId);

        $result = [];
        foreach ($items as $item) {
            $status = strtolower(trim((string) ($item['status'] ?? '')));
            if (!in_array($status, ['aktif', 'active'], true)) {
                continue;
            }
            $result[] = $item;
        }

        return $result;
    }

    private function buildContractSubmissionRows(string $activityType, int $userId): array
    {
        if ($activityType === 'pengabdian') {
            $model = new PengabdianModel();
        } elseif ($activityType === 'hilirisasi') {
            $model = new HilirisasiModel();
        } else {
            $model = new PenelitianModel();
        }
        $items = $model->getOwnedList($userId);
        $rows = [];

        foreach ($items as $item) {
            $activityStatus = strtolower(trim((string) ($item['status'] ?? '')));
            $isActiveActivity = in_array($activityStatus, ['aktif', 'active'], true);
            $isFinishedActivity = in_array($activityStatus, ['selesai', 'completed', 'done'], true);

            if (!$isActiveActivity && !$isFinishedActivity) {
                continue;
            }

            $activityId = (int) ($item['id'] ?? 0);
            if ($activityId <= 0) {
                continue;
            }

            $latestSubmission = $this->letterModel->getLatestContractSubmissionByActivity($userId, $activityType, $activityId);
            if ($latestSubmission !== null) {
                // Setelah kontrak diajukan, kelola lanjutannya di menu Surat Saya.
                continue;
            }
            $statusMeta = $this->mapContractStatus($latestSubmission !== null ? (string) ($latestSubmission['status'] ?? '') : '');

            // Asumsi tahap pengembangan:
            // kegiatan selesai dianggap sudah pernah berkontrak, jadi tidak bisa ajukan ulang.
            if ($isFinishedActivity) {
                if ($latestSubmission === null) {
                    $statusMeta = [
                        'label' => 'Disetujui',
                        'class' => 'status-kontrak-approved',
                        'can_submit' => false,
                    ];
                } else {
                    $statusMeta['can_submit'] = false;
                }
            } else {
                // Hanya kegiatan aktif yang boleh ajukan kontrak.
                $statusMeta['can_submit'] = (bool) ($statusMeta['can_submit'] ?? false);
            }

            $rows[] = [
                'id' => $activityId,
                'judul' => (string) ($item['judul'] ?? '-'),
                'skema' => (string) ($item['skema'] ?? '-'),
                'ruang_lingkup' => (string) ($item['ruang_lingkup'] ?? '-'),
                'sumber_dana' => (string) ($item['sumber_dana'] ?? '-'),
                'tahun' => (string) ($item['tahun'] ?? '-'),
                'status_label' => $statusMeta['label'],
                'status_class' => $statusMeta['class'],
                'can_submit' => $statusMeta['can_submit'],
                'latest_letter_id' => (int) ($latestSubmission['id'] ?? 0),
            ];
        }

        return $rows;
    }

    private function mapContractStatus(string $statusRaw): array
    {
        $status = strtolower(trim($statusRaw));

        if ($status === '') {
            return [
                'label' => 'Siap Diajukan',
                'class' => 'status-kontrak-ready',
                'can_submit' => true,
            ];
        }

        if (in_array($status, ['diajukan', 'submitted', 'diverifikasi'], true)) {
            return [
                'label' => 'Menunggu Diproses',
                'class' => 'status-kontrak-waiting',
                'can_submit' => false,
            ];
        }

        if (in_array($status, ['approved', 'disetujui', 'selesai'], true)) {
            return [
                'label' => 'Disetujui',
                'class' => 'status-kontrak-approved',
                'can_submit' => false,
            ];
        }

        if (in_array($status, ['rejected', 'ditolak'], true)) {
            return [
                'label' => 'Perlu Diperbaiki',
                'class' => 'status-kontrak-revision',
                'can_submit' => true,
            ];
        }

        return [
            'label' => 'Siap Diajukan',
            'class' => 'status-kontrak-ready',
            'can_submit' => true,
        ];
    }

    private function buildActivityRefToken(string $activityType, int $activityId): string
    {
        return '__ACTIVITY_REF__[' . strtolower(trim($activityType)) . ':' . $activityId . ']';
    }

    private function buildContractSourceToken(string $sourceKey): string
    {
        $normalized = strtolower(trim($sourceKey));
        if ($normalized !== ContractSettingModel::SOURCE_DIKTI) {
            $normalized = ContractSettingModel::SOURCE_DIKTI;
        }

        return '__CONTRACT_SOURCE__[' . $normalized . ']';
    }

    private function resolvePermitDetailPageTitle(array $letter): string
    {
        $subject = strtolower((string) ($letter['subject'] ?? ''));
        $kind = str_contains($subject, 'kontrak') ? 'Kontrak' : 'Izin';

        if (str_contains($subject, 'pengabdian')) {
            return 'Detail Surat ' . $kind . ' Pengabdian';
        }
        if (str_contains($subject, 'hilirisasi')) {
            return 'Detail Surat ' . $kind . ' Hilirisasi';
        }

        return 'Detail Surat ' . $kind . ' Penelitian';
    }

    private function hydrateContractDetailFromActivityRef(array $letter, array $detail): array
    {
        $subject = strtolower((string) ($letter['subject'] ?? ''));

        $ownerId = (int) ($letter['applicant_id'] ?? 0);
        if ($ownerId <= 0) {
            return $detail;
        }

        [$activityType, $activityId] = $this->resolveContractActivityRef($subject, $detail, $ownerId);
        if ($activityId <= 0) {
            return $detail;
        }

        $activityRow = $this->findActivityForUser($activityType, $activityId, $ownerId);
        if ($activityRow === null) {
            return $detail;
        }

        $keepEditedOrFallback = static function ($currentValue, $fallbackValue): string {
            $current = trim((string) ($currentValue ?? ''));
            if ($current !== '') {
                return $current;
            }

            return trim((string) ($fallbackValue ?? ''));
        };

        $activityScheme = (string) (($activityRow['ruang_lingkup'] ?? '') !== '' ? $activityRow['ruang_lingkup'] : ($activityRow['skema'] ?? ''));

        $detail['research_title'] = $keepEditedOrFallback($detail['research_title'] ?? '', $activityRow['judul'] ?? '');
        $detail['research_scheme'] = $keepEditedOrFallback($detail['research_scheme'] ?? '', $activityScheme);
        $detail['skema'] = $keepEditedOrFallback($detail['skema'] ?? '', $activityRow['skema'] ?? '');
        $detail['ruang_lingkup'] = $keepEditedOrFallback($detail['ruang_lingkup'] ?? '', $activityRow['ruang_lingkup'] ?? '');
        $detail['funding_source'] = $keepEditedOrFallback($detail['funding_source'] ?? '', $activityRow['sumber_dana'] ?? '');
        $detail['research_year'] = $keepEditedOrFallback($detail['research_year'] ?? '', $activityRow['tahun'] ?? '');
        $detail['researcher_name'] = $keepEditedOrFallback($detail['researcher_name'] ?? '', $activityRow['ketua'] ?? '');
        $detail['members'] = $keepEditedOrFallback($detail['members'] ?? '', $activityRow['anggota'] ?? '');
        $detail['purpose'] = $keepEditedOrFallback($detail['purpose'] ?? '', $activityRow['deskripsi'] ?? '');
        $detail['institution'] = $keepEditedOrFallback($detail['institution'] ?? ($letter['institution'] ?? ''), $activityRow['mitra'] ?? '');
        $detail['address'] = $keepEditedOrFallback($detail['address'] ?? '', $activityRow['lokasi'] ?? '');
        $detail['city'] = $keepEditedOrFallback($detail['city'] ?? '', $activityRow['lokasi'] ?? '');
        $detail['start_date'] = $keepEditedOrFallback($detail['start_date'] ?? '', $activityRow['tanggal_mulai'] ?? '');
        $detail['end_date'] = $keepEditedOrFallback($detail['end_date'] ?? '', $activityRow['tanggal_selesai'] ?? '');
        $detail['total_dana_disetujui'] = $keepEditedOrFallback($detail['total_dana_disetujui'] ?? '', $activityRow['total_dana_disetujui'] ?? '');

        $currentAttachment = trim((string) ($detail['attachment_file'] ?? ''));
        if ($currentAttachment === '') {
            $detail['attachment_file'] = $this->encodeAttachmentPaths($this->normalizeTaskAttachmentPaths([
                'file_proposal' => (string) ($activityRow['file_proposal'] ?? ''),
                'file_instrumen' => (string) ($activityRow['file_instrumen'] ?? ''),
                'file_pendukung_lain' => (string) ($activityRow['file_pendukung_lain'] ?? ''),
            ]));
        }

        return $detail;
    }

    private function hydrateTotalApprovedFundFromActivityRef(array $letter, array $detail): array
    {
        $ownerId = (int) ($letter['applicant_id'] ?? 0);
        if ($ownerId <= 0) {
            return $detail;
        }

        $subjectLower = strtolower((string) ($letter['subject'] ?? ''));
        [$activityType, $activityId] = $this->resolveContractActivityRef($subjectLower, $detail, $ownerId);
        if ($activityId <= 0) {
            return $detail;
        }

        $activityRow = $this->findActivityForUser($activityType, $activityId, $ownerId);
        if ($activityRow === null) {
            if (str_contains($subjectLower, 'hilirisasi')) {
                $researchScheme = trim((string) ($detail['research_scheme'] ?? ''));
                if (trim((string) ($detail['skema'] ?? '')) === '') {
                    $detail['skema'] = 'Hilirisasi Riset Prioritas';
                }
                if (trim((string) ($detail['ruang_lingkup'] ?? '')) === '') {
                    $detail['ruang_lingkup'] = $researchScheme !== '' ? $researchScheme : 'Hilirisasi Pengujian Model dan Prototipe';
                }
            }
            return $detail;
        }

        $current = trim((string) ($detail['total_dana_disetujui'] ?? ''));
        if ($current === '') {
            $detail['total_dana_disetujui'] = (string) ($activityRow['total_dana_disetujui'] ?? '');
        }

        return $detail;
    }

    private function hydrateTargetLuaranFromActivityRef(array $letter, array $detail): array
    {
        $ownerId = (int) ($letter['applicant_id'] ?? 0);
        if ($ownerId <= 0) {
            return $detail;
        }

        $subjectLower = strtolower((string) ($letter['subject'] ?? ''));
        [$activityType, $activityId] = $this->resolveContractActivityRef($subjectLower, $detail, $ownerId);
        if ($activityId <= 0) {
            return $detail;
        }

        $activityRow = $this->findActivityForUser($activityType, $activityId, $ownerId);
        if ($activityRow === null) {
            return $detail;
        }

        $targetLuaran = trim((string) ($activityRow['deskripsi'] ?? ''));
        if ($targetLuaran !== '') {
            $detail['target_luaran'] = $targetLuaran;
        }

        return $detail;
    }

    private function resolveContractActivityRef(string $subjectLower, array $detail, int $ownerId): array
    {
        $notes = (string) ($detail['notes'] ?? '');
        if ($notes !== '' && preg_match('/__ACTIVITY_REF__\[(penelitian|pengabdian|hilirisasi):(\d+)\]/i', $notes, $matches)) {
            $type = strtolower((string) ($matches[1] ?? 'penelitian'));
            return [
                $type,
                (int) ($matches[2] ?? 0),
            ];
        }

        $activityType = 'penelitian';
        if (str_contains($subjectLower, 'pengabdian')) {
            $activityType = 'pengabdian';
        } elseif (str_contains($subjectLower, 'hilirisasi')) {
            $activityType = 'hilirisasi';
        }

        $model = $activityType === 'pengabdian'
            ? new PengabdianModel()
            : ($activityType === 'hilirisasi' ? new HilirisasiModel() : new PenelitianModel());
        $items = $model->getList($ownerId);
        if (empty($items)) {
            return [$activityType, 0];
        }

        $targetInstitution = $this->normalizeMatchText((string) ($detail['institution'] ?? ''));
        $targetYear = trim((string) ($detail['research_year'] ?? ''));
        $targetTitle = $this->normalizeMatchText((string) ($detail['research_title'] ?? ''));

        $bestId = 0;
        $bestScore = -1;
        foreach ($items as $item) {
            $score = 0;

            $itemInstitution = $this->normalizeMatchText((string) ($item['mitra'] ?? ''));
            $itemYear = trim((string) ($item['tahun'] ?? ''));
            $itemTitle = $this->normalizeMatchText((string) ($item['judul'] ?? ''));

            if ($targetInstitution !== '' && $itemInstitution !== '' && $targetInstitution === $itemInstitution) {
                $score += 3;
            }
            if ($targetYear !== '' && $itemYear !== '' && $targetYear === $itemYear) {
                $score += 2;
            }
            if ($targetTitle !== '' && $itemTitle !== '' && $targetTitle === $itemTitle) {
                $score += 4;
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestId = (int) ($item['id'] ?? 0);
            }
        }

        if ($bestScore <= 0) {
            return [$activityType, 0];
        }

        return [$activityType, $bestId];
    }

    private function resolveActivityEditUrlFromLetterDetail(array $letter, array $detail): ?string
    {
        $ownerId = (int) ($letter['applicant_id'] ?? 0);
        if ($ownerId <= 0) {
            return null;
        }

        $subjectLower = strtolower((string) ($letter['subject'] ?? ''));
        [$activityType, $activityId] = $this->resolveContractActivityRef($subjectLower, $detail, $ownerId);
        if ($activityId <= 0 || !in_array($activityType, ['penelitian', 'pengabdian', 'hilirisasi'], true)) {
            return null;
        }

        return '?route=data-' . $activityType . '-edit&id=' . $activityId . '&from_letter_id=' . (int) ($letter['id'] ?? 0);
    }

    private function normalizeMatchText(string $value): string
    {
        $text = strtolower(trim($value));
        $text = preg_replace('/\s+/', ' ', $text);
        $text = preg_replace('/[^a-z0-9 ]/', '', (string) $text);

        return trim((string) $text);
    }

    private function isValidHttpUrl(string $value): bool
    {
        $value = trim($value);
        if ($value === '') {
            return false;
        }
        if (!preg_match('/^https?:\/\//i', $value)) {
            return false;
        }

        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    private function resolvePermitAttachmentValue(mixed $input, ?string $existingValue): ?string
    {
        if (is_string($input)) {
            $value = trim($input);
            if ($value === '') {
                return $existingValue;
            }
            if (!$this->isValidHttpUrl($value)) {
                throw new RuntimeException('Lampiran harus berupa link URL yang valid (http/https).');
            }

            return $value;
        }

        return $this->handleAttachmentUpload(is_array($input) ? $input : null, $existingValue);
    }

    private function resolveTaskAttachmentValue(string $linkValue, ?array $fileInput, string $existingValue, string $prefix): ?string
    {
        $linkValue = trim($linkValue);
        if ($linkValue !== '') {
            if (!$this->isValidHttpUrl($linkValue)) {
                throw new RuntimeException('Lampiran harus berupa link URL yang valid (http/https).');
            }

            return $linkValue;
        }

        return $this->handleTaskAttachmentUpload($fileInput, $existingValue, $prefix);
    }

    private function resolveTaskActivityIdFromArray(array $payload): int
    {
        $activityId = (int) ($payload['activity_id'] ?? 0);
        if ($activityId > 0) {
            return $activityId;
        }

        return (int) ($payload['penelitian_id'] ?? 0);
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

    private function isAllowedLetterAttachment(string $ext, string $mime): bool
    {
        $ext = strtolower(trim($ext));
        $mime = strtolower(trim($mime));
        $allowedMimeByExt = [
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword', 'application/vnd.ms-word'],
            'docx' => [
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/zip',
            ],
        ];

        return isset($allowedMimeByExt[$ext]) && in_array($mime, $allowedMimeByExt[$ext], true);
    }

    private function resolveActivityTypeFromSubject(string $subject): string
    {
        $value = strtolower(trim($subject));
        if (str_contains($value, 'pengabdian')) {
            return 'pengabdian';
        }
        if (str_contains($value, 'hilirisasi')) {
            return 'hilirisasi';
        }

        return 'penelitian';
    }

    private function resolveLetterKindFromSubject(string $subject): string
    {
        $value = strtolower(trim($subject));
        if (str_contains($value, 'kontrak')) {
            return 'kontrak';
        }
        if (str_contains($value, 'tugas')) {
            return 'tugas';
        }

        return 'izin';
    }

    private function resolveJenisSuratCodeFromSubject(string $subject): string
    {
        $kind = $this->resolveLetterKindFromSubject($subject);

        return match ($kind) {
            'kontrak' => 'K',
            'tugas' => 'T',
            default => 'I',
        };
    }

    private function resolveSkemaCodeForLetterNumber(int $letterId, array $letter): string
    {
        $fallback = 'UMUM';
        $letterType = strtoupper(trim((string) ($letter['letter_type_code'] ?? '')));

        if ($letterType === 'TUGAS') {
            $taskDetail = $this->suratTugasPenelitianModel->findByLetterId($letterId);
            if ($taskDetail !== null) {
                $activityType = strtolower(trim((string) ($taskDetail['activity_type'] ?? 'penelitian')));
                $activityId = $this->resolveTaskActivityIdFromArray($taskDetail);
                $ownerId = (int) ($letter['applicant_id'] ?? 0);
                if ($activityId > 0 && $ownerId > 0) {
                    $activity = $this->findActivityForUser($activityType, $activityId, $ownerId);
                    if ($activity !== null) {
                        $scope = trim((string) (($activity['ruang_lingkup'] ?? '') !== '' ? $activity['ruang_lingkup'] : ($activity['skema'] ?? '')));
                        if ($scope !== '') {
                            return $scope;
                        }
                    }
                }
            }

            return $fallback;
        }

        $permitDetail = $this->researchPermitModel->findByLetterId($letterId);
        if ($permitDetail !== null) {
            $scheme = trim((string) ($permitDetail['research_scheme'] ?? ''));
            if ($scheme !== '') {
                return $scheme;
            }

            $subjectLower = strtolower((string) ($letter['subject'] ?? ''));
            $ownerId = (int) ($letter['applicant_id'] ?? 0);
            if ($ownerId > 0) {
                [$activityType, $activityId] = $this->resolveContractActivityRef($subjectLower, $permitDetail, $ownerId);
                if ($activityId > 0) {
                    $activity = $this->findActivityForUser($activityType, $activityId, $ownerId);
                    if ($activity !== null) {
                        $scope = trim((string) (($activity['ruang_lingkup'] ?? '') !== '' ? ($activity['ruang_lingkup'] ?? '') : ($activity['skema'] ?? '')));
                        if ($scope !== '') {
                            return $scope;
                        }
                    }
                }
            }
        }

        return $fallback;
    }
}




