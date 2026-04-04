<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/ActivityBaseModel.php';
require_once __DIR__ . '/../Models/ActivityCategoryModel.php';
require_once __DIR__ . '/../Models/OutputTypeModel.php';
require_once __DIR__ . '/../Models/ActivityCategoryOutputModel.php';
require_once __DIR__ . '/../Models/ActivityOutputModel.php';
require_once __DIR__ . '/../Models/PenelitianModel.php';
require_once __DIR__ . '/../Models/PengabdianModel.php';
require_once __DIR__ . '/../Models/HilirisasiModel.php';

class StatusLuaranController extends BaseController
{
    private ActivityCategoryModel $activityCategoryModel;
    private OutputTypeModel $outputTypeModel;
    private ActivityCategoryOutputModel $categoryOutputModel;
    private ActivityOutputModel $activityOutputModel;
    private PenelitianModel $penelitianModel;
    private PengabdianModel $pengabdianModel;
    private HilirisasiModel $hilirisasiModel;

    public function __construct()
    {
        parent::__construct();
        $this->activityCategoryModel = new ActivityCategoryModel();
        $this->outputTypeModel = new OutputTypeModel();
        $this->categoryOutputModel = new ActivityCategoryOutputModel();
        $this->activityOutputModel = new ActivityOutputModel();
        $this->penelitianModel = new PenelitianModel();
        $this->pengabdianModel = new PengabdianModel();
        $this->hilirisasiModel = new HilirisasiModel();
    }

    public function index(): void
    {
        $this->guardDosen();

        $filters = [
            'activity_type' => trim((string) ($_GET['activity_type'] ?? '')),
            'year' => trim((string) ($_GET['year'] ?? '')),
            'status' => strtolower(trim((string) ($_GET['status'] ?? ''))),
            'keyword' => trim((string) ($_GET['keyword'] ?? '')),
        ];
        $filtersValidation = $this->validatePayload(
            $filters,
            [
                'activity_type' => 'permit_empty|in_list[penelitian,pengabdian,hilirisasi]',
                'year' => 'permit_empty|exact_length[4]|regex_match[/^\d{4}$/]',
                'status' => 'permit_empty|in_list[belum,proses,selesai]',
                'keyword' => 'permit_empty|max_length[160]',
            ],
            [
                'year' => [
                    'regex_match' => 'Filter tahun harus 4 digit angka.',
                ],
            ]
        );
        if (!$filtersValidation['valid']) {
            $this->redirectToPath('status-luaran', [
                'error' => $this->firstValidationError($filtersValidation['errors'], 'Filter tidak valid.'),
            ]);
        }

        $items = $this->collectActivitySummaries((int) (authUserId() ?? 0), $filters);
        $stats = $this->buildSummaryStats($items);

        $this->render('status_luaran/index', [
            'pageTitle' => 'Status Luaran',
            'pageSubtitle' => 'Pantau progres dan lengkapi bukti luaran dari kegiatan penelitian, pengabdian, dan hilirisasi.',
            'filters' => $filters,
            'items' => $items,
            'stats' => $stats,
            'categories' => $this->activityCategoryModel->getAll(),
            'successMessage' => $_GET['success'] ?? null,
            'errorMessage' => $_GET['error'] ?? null,
        ]);
    }

    public function detail(): void
    {
        $this->guardDosen();

        $activityType = strtolower(trim((string) ($_GET['activity_type'] ?? '')));
        $activityId = (int) ($_GET['activity_id'] ?? 0);
        $detailValidation = $this->validatePayload(
            [
                'activity_type' => $activityType,
                'activity_id' => (string) $activityId,
            ],
            [
                'activity_type' => 'required|in_list[penelitian,pengabdian,hilirisasi]',
                'activity_id' => 'required|integer|greater_than[0]',
            ]
        );
        if (!$detailValidation['valid']) {
            $this->redirectToPath('status-luaran', ['error' => 'Parameter activity tidak valid.']);
        }

        $activity = $this->getActivityByTypeAndId($activityType, $activityId);
        if ($activity === null) {
            $this->redirectToPath('status-luaran', ['error' => 'Data kegiatan tidak ditemukan.']);
        }

        $selectedOutputPayload = $this->resolveSelectedOutputsForActivity($activityType, $activity);
        $mappedOutputs = $selectedOutputPayload['items'];
        $realizedOutputs = $this->activityOutputModel->getOutputsByActivity($activityType, $activityId);
        $realizedByOutputType = [];
        foreach ($realizedOutputs as $item) {
            $realizedByOutputType[(int) ($item['output_type_id'] ?? 0)] = $item;
        }

        $rows = [];
        foreach ($mappedOutputs as $output) {
            $outputTypeId = (int) ($output['output_type_id'] ?? 0);
            $realized = $realizedByOutputType[$outputTypeId] ?? null;
            $rows[] = [
                'output_type_id' => $outputTypeId,
                'output_code' => (string) ($output['output_code'] ?? ''),
                'output_name' => (string) ($output['output_name'] ?? ''),
                'output_description' => (string) ($output['output_description'] ?? ''),
                'is_required' => (int) ($output['is_required'] ?? 1),
                'status' => (string) ($realized['status'] ?? 'belum'),
                'evidence_link' => (string) ($realized['evidence_link'] ?? ''),
                'evidence_notes' => (string) ($realized['evidence_notes'] ?? ''),
                'evidence_file' => (string) ($realized['evidence_file'] ?? ''),
                'validated_by' => $realized['validated_by'] ?? null,
                'validated_at' => $realized['validated_at'] ?? null,
            ];
        }

        $selectedOutputTypeIds = array_map(static fn (array $item): int => (int) ($item['output_type_id'] ?? 0), $mappedOutputs);
        $completedCount = $this->countCompletedForSelectedOutputs($activityType, $activityId, $selectedOutputTypeIds);

        $this->render('status_luaran/detail', [
            'pageTitle' => 'Detail Status Luaran',
            'pageSubtitle' => 'Lengkapi dan pantau bukti luaran untuk kegiatan yang dipilih.',
            'activityType' => $activityType,
            'activityTypeLabel' => $this->getActivityCategoryLabel($activityType),
            'activityId' => $activityId,
            'activity' => $activity,
            'outputs' => $rows,
            'completedCount' => $completedCount,
            'totalCount' => count($mappedOutputs),
            'successMessage' => $_GET['success'] ?? null,
            'errorMessage' => $_GET['error'] ?? null,
        ]);
    }

    public function save(): void
    {
        $this->guardDosen();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToPath('status-luaran');
        }

        $activityType = strtolower(trim((string) ($_POST['activity_type'] ?? '')));
        $activityId = (int) ($_POST['activity_id'] ?? 0);
        $outputTypeId = (int) ($_POST['output_type_id'] ?? 0);
        $evidenceLink = trim((string) ($_POST['evidence_link'] ?? ''));
        $evidenceNotes = trim((string) ($_POST['evidence_notes'] ?? ''));

        $detailPath = 'status-luaran/' . rawurlencode($activityType) . '/' . $activityId;

        $saveValidation = $this->validatePayload(
            [
                'activity_type' => $activityType,
                'activity_id' => (string) $activityId,
                'output_type_id' => (string) $outputTypeId,
                'evidence_link' => $evidenceLink,
                'evidence_notes' => $evidenceNotes,
            ],
            [
                'activity_type' => 'required|in_list[penelitian,pengabdian,hilirisasi]',
                'activity_id' => 'required|integer|greater_than[0]',
                'output_type_id' => 'required|integer|greater_than[0]',
                'evidence_link' => 'permit_empty|regex_match[/^https?:\/\/\S+$/i]|max_length[1200]',
                'evidence_notes' => 'permit_empty|max_length[2000]',
            ],
            [
                'evidence_link' => [
                    'regex_match' => 'Format evidence link tidak valid.',
                ],
            ]
        );
        if (!$saveValidation['valid']) {
            $this->redirectToPath($detailPath, ['error' => 'Parameter simpan status luaran tidak valid.']);
        }

        $activity = $this->getActivityByTypeAndId($activityType, $activityId);
        if ($activity === null) {
            $this->redirectToPath('status-luaran', ['error' => 'Data kegiatan tidak ditemukan.']);
        }

        $selectedOutputPayload = $this->resolveSelectedOutputsForActivity($activityType, $activity);
        $mappedOutputs = $selectedOutputPayload['items'];
        $allowedOutputTypeIds = array_map(
            static fn (array $item): int => (int) ($item['output_type_id'] ?? 0),
            $mappedOutputs
        );
        if (!in_array($outputTypeId, $allowedOutputTypeIds, true)) {
            $this->redirectToPath($detailPath, ['error' => 'Jenis luaran tidak termasuk kategori kegiatan ini.']);
        }

        try {
            $existing = $this->activityOutputModel->findByActivityAndOutputType($activityType, $activityId, $outputTypeId);
            $existingEvidenceFile = trim((string) ($existing['evidence_file'] ?? ''));
            $resolvedStatus = $evidenceLink !== '' ? 'selesai' : 'belum';

            $payload = [
                'activity_type' => $activityType,
                'activity_id' => $activityId,
                'output_type_id' => $outputTypeId,
                'status' => $resolvedStatus,
                'evidence_link' => $evidenceLink !== '' ? $evidenceLink : null,
                'evidence_notes' => $evidenceNotes !== '' ? $evidenceNotes : null,
                'evidence_file' => $existingEvidenceFile !== '' ? $existingEvidenceFile : null,
                'validated_by' => null,
                'validated_at' => null,
            ];

            $this->activityOutputModel->saveOrUpdate($payload);
            $this->redirectToPath($detailPath, ['success' => 'Data luaran berhasil disimpan.']);
        } catch (Throwable $e) {
            $this->redirectToPath($detailPath, ['error' => $e->getMessage()]);
        }
    }

    private function collectActivitySummaries(int $userId, array $filters): array
    {
        $activityTypeFilter = strtolower((string) ($filters['activity_type'] ?? ''));
        $allowedTypes = ['penelitian', 'pengabdian', 'hilirisasi'];
        $targetTypes = in_array($activityTypeFilter, $allowedTypes, true) ? [$activityTypeFilter] : $allowedTypes;

        $statusFilter = strtolower((string) ($filters['status'] ?? ''));
        $rows = [];

        foreach ($targetTypes as $type) {
            $model = $this->modelByType($type);
            if ($model === null) {
                continue;
            }

            $activities = $model->getList($userId, [
                'year' => (string) ($filters['year'] ?? ''),
                'q' => (string) ($filters['keyword'] ?? ''),
                'status' => '',
            ]);

            foreach ($activities as $activity) {
                $activityId = (int) ($activity['id'] ?? 0);
                $selectedOutputPayload = $this->resolveSelectedOutputsForActivity($type, $activity);
                $requiredCount = count($selectedOutputPayload['items']);
                $selectedOutputTypeIds = array_map(static fn (array $item): int => (int) ($item['output_type_id'] ?? 0), $selectedOutputPayload['items']);
                $completedCount = $this->countCompletedForSelectedOutputs($type, $activityId, $selectedOutputTypeIds);
                $luaranStatus = $this->resolveLuaranStatus($completedCount, $requiredCount);

                if ($statusFilter !== '' && $luaranStatus !== $statusFilter) {
                    continue;
                }

                $rows[] = [
                    'activity_type' => $type,
                    'activity_type_label' => $this->getActivityCategoryLabel($type),
                    'activity_id' => $activityId,
                    'judul' => (string) ($activity['judul'] ?? ''),
                    'skema' => (string) ($activity['skema'] ?? ''),
                    'tahun' => (string) ($activity['tahun'] ?? ''),
                    'ketua' => (string) ($activity['ketua'] ?? ''),
                    'created_at' => (string) ($activity['created_at'] ?? ''),
                    'updated_at' => (string) ($activity['updated_at'] ?? ''),
                    'luaran_status' => $luaranStatus,
                    'completed_count' => $completedCount,
                    'required_count' => $requiredCount,
                    'progress_percent' => $requiredCount > 0 ? (int) round(($completedCount / $requiredCount) * 100) : 0,
                ];
            }
        }

        usort($rows, static function (array $a, array $b): int {
            $priority = [
                'belum' => 1,
                'proses' => 2,
                'selesai' => 3,
            ];

            $statusA = strtolower((string) ($a['luaran_status'] ?? 'belum'));
            $statusB = strtolower((string) ($b['luaran_status'] ?? 'belum'));
            $prioA = $priority[$statusA] ?? 99;
            $prioB = $priority[$statusB] ?? 99;
            if ($prioA !== $prioB) {
                return $prioA <=> $prioB;
            }

            $yearA = (int) ($a['tahun'] ?? 0);
            $yearB = (int) ($b['tahun'] ?? 0);
            if ($yearA !== $yearB) {
                return $yearB <=> $yearA;
            }

            $updatedA = strtotime((string) ($a['updated_at'] ?? '')) ?: strtotime((string) ($a['created_at'] ?? '')) ?: 0;
            $updatedB = strtotime((string) ($b['updated_at'] ?? '')) ?: strtotime((string) ($b['created_at'] ?? '')) ?: 0;
            if ($updatedA !== $updatedB) {
                return $updatedB <=> $updatedA;
            }

            return (int) ($b['activity_id'] ?? 0) <=> (int) ($a['activity_id'] ?? 0);
        });

        return $rows;
    }

    private function resolveLuaranStatus(int $completedCount, int $requiredCount): string
    {
        if ($requiredCount <= 0 || $completedCount <= 0) {
            return 'belum';
        }

        if ($completedCount >= $requiredCount) {
            return 'selesai';
        }

        return 'proses';
    }

    private function buildSummaryStats(array $items): array
    {
        $stats = [
            'total' => count($items),
            'belum' => 0,
            'proses' => 0,
            'selesai' => 0,
        ];

        foreach ($items as $item) {
            $status = strtolower((string) ($item['luaran_status'] ?? 'belum'));
            if (isset($stats[$status])) {
                $stats[$status]++;
            }
        }

        return $stats;
    }

    private function getActivityByTypeAndId(string $activityType, int $activityId): ?array
    {
        $userId = (int) (authUserId() ?? 0);
        if ($activityId <= 0 || $userId <= 0) {
            return null;
        }

        $model = $this->modelByType($activityType);
        if ($model === null) {
            return null;
        }

        return $model->findById($activityId, $userId);
    }

    private function modelByType(string $activityType): ?ActivityBaseModel
    {
        return match (strtolower($activityType)) {
            'penelitian' => $this->penelitianModel,
            'pengabdian' => $this->pengabdianModel,
            'hilirisasi' => $this->hilirisasiModel,
            default => null,
        };
    }

    private function getActivityCategoryLabel(string $activityType): string
    {
        return match (strtolower($activityType)) {
            'penelitian' => 'Penelitian',
            'pengabdian' => 'Pengabdian Kepada Masyarakat',
            'hilirisasi' => 'Hilirisasi',
            default => 'Kegiatan',
        };
    }

    private function guardDosen(): void
    {
        if (authRole() !== 'dosen') {
            $this->redirectToPath($this->adminDashboardPath());
        }
    }

    private function resolveSelectedOutputsForActivity(string $activityType, array $activity): array
    {
        $selected = $this->parseSelectedOutputCodesFromActivity($activity);
        $wajibCodes = $selected['wajib'];
        $tambahanCodes = $selected['tambahan'];
        $orderedCodes = $selected['ordered'];

        $outputTypeByCode = [];
        foreach ($this->outputTypeModel->getAll() as $outputType) {
            $code = (string) ($outputType['code'] ?? '');
            if ($code !== '') {
                $outputTypeByCode[$code] = $outputType;
            }
        }

        $items = [];
        foreach ($orderedCodes as $code) {
            $outputType = $outputTypeByCode[$code] ?? null;
            if ($outputType === null) {
                continue;
            }

            $items[] = [
                'output_type_id' => (int) ($outputType['id'] ?? 0),
                'output_code' => (string) ($outputType['code'] ?? ''),
                'output_name' => (string) ($outputType['name'] ?? ''),
                'output_description' => (string) ($outputType['description'] ?? ''),
                'is_required' => in_array($code, $wajibCodes, true) ? 1 : 0,
            ];
        }

        if (!empty($items)) {
            return ['items' => $items];
        }

        // Fallback lama: jika data target luaran belum diisi, tetap pakai mapping kategori.
        return ['items' => $this->categoryOutputModel->getOutputsByCategoryCode($activityType)];
    }

    private function parseSelectedOutputCodesFromActivity(array $activity): array
    {
        $normalizeCode = static function (string $code): string {
            $normalized = strtolower(trim($code));
            if ($normalized === 'prototipe') {
                // Kompatibilitas data lama sebelum rename ke "hilirisasi".
                return 'hilirisasi';
            }

            return $normalized;
        };

        $normalizeCodeList = static function (array $items) use ($normalizeCode): array {
            $result = [];
            foreach ($items as $item) {
                $code = $normalizeCode((string) $item);
                if ($code === '') {
                    continue;
                }
                $result[] = $code;
            }

            return array_values(array_unique($result));
        };

        $deskripsi = trim((string) ($activity['deskripsi'] ?? ''));
        if ($deskripsi === '') {
            return ['wajib' => [], 'tambahan' => [], 'ordered' => []];
        }

        $wajib = [];
        $tambahan = [];
        $lines = preg_split('/\r\n|\r|\n/', $deskripsi) ?: [];
        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line === '') {
                continue;
            }

            if (stripos($line, 'Luaran Wajib:') === 0) {
                $raw = trim(substr($line, strlen('Luaran Wajib:')));
                $wajib = $raw === '' ? [] : $normalizeCodeList(explode('|', $raw));
            } elseif (stripos($line, 'Luaran Tambahan:') === 0) {
                $raw = trim(substr($line, strlen('Luaran Tambahan:')));
                $tambahan = $raw === '' ? [] : $normalizeCodeList(explode('|', $raw));
            }
        }

        $ordered = array_values(array_unique(array_merge($wajib, $tambahan)));

        return [
            'wajib' => $wajib,
            'tambahan' => $tambahan,
            'ordered' => $ordered,
        ];
    }

    private function handleEvidenceUpload(string $activityType, int $activityId, int $outputTypeId): string
    {
        if (!isset($_FILES['evidence_file']) || !is_array($_FILES['evidence_file'])) {
            return '';
        }

        $file = $_FILES['evidence_file'];
        $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($errorCode === UPLOAD_ERR_NO_FILE) {
            return '';
        }
        if ($errorCode !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload dokumen bukti gagal. Silakan coba ulang.');
        }

        $tmpPath = (string) ($file['tmp_name'] ?? '');
        $size = (int) ($file['size'] ?? 0);
        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            throw new RuntimeException('File dokumen bukti tidak valid.');
        }
        if ($size <= 0 || $size > 5 * 1024 * 1024) {
            throw new RuntimeException('Ukuran dokumen bukti maksimal 5 MB.');
        }

        $mime = $this->detectMimeType($tmpPath);
        $allowedMimes = [
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];
        if (!isset($allowedMimes[$mime])) {
            throw new RuntimeException('Format dokumen bukti harus PDF/JPG/PNG/WEBP.');
        }

        $ext = $allowedMimes[$mime];
        $uploadDir = __DIR__ . '/../../storage/uploads/luaran-evidence';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            throw new RuntimeException('Folder upload dokumen bukti tidak dapat dibuat.');
        }

        $fileName = sprintf(
            'evidence-%s-%d-%d-%s.%s',
            $activityType,
            $activityId,
            $outputTypeId,
            bin2hex(random_bytes(4)),
            $ext
        );
        $targetPath = $uploadDir . '/' . $fileName;
        if (!move_uploaded_file($tmpPath, $targetPath)) {
            throw new RuntimeException('Gagal menyimpan dokumen bukti.');
        }
        @chmod($targetPath, 0640);

        return 'storage/uploads/luaran-evidence/' . $fileName;
    }

    private function countCompletedForSelectedOutputs(string $activityType, int $activityId, array $selectedOutputTypeIds): int
    {
        if (empty($selectedOutputTypeIds)) {
            return 0;
        }

        $selectedOutputTypeIds = array_values(array_unique(array_filter(array_map('intval', $selectedOutputTypeIds), static fn (int $id): bool => $id > 0)));
        if (empty($selectedOutputTypeIds)) {
            return 0;
        }

        $rows = $this->activityOutputModel->getOutputsByActivity($activityType, $activityId);
        $count = 0;
        foreach ($rows as $row) {
            $outputTypeId = (int) ($row['output_type_id'] ?? 0);
            $status = strtolower(trim((string) ($row['status'] ?? '')));
            if (in_array($outputTypeId, $selectedOutputTypeIds, true) && $status === 'selesai') {
                $count++;
            }
        }

        return $count;
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

