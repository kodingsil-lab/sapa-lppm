<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/LetterModel.php';
require_once __DIR__ . '/../Helpers/ActivityLogHelper.php';

class ArchiveController extends BaseController
{
    private LetterModel $letterModel;

    public function __construct()
    {
        parent::__construct();
        $this->letterModel = new LetterModel();
    }

    public function index(): void
    {
        $rows = [];
        $summary = ['total' => 0, 'kontrak' => 0, 'izin' => 0, 'tugas' => 0];
        $yearOptions = [];
        $dbError = null;
        $archiveFilters = [
            'jenis' => strtolower(trim((string) ($_GET['jenis'] ?? ''))),
            'tahun' => trim((string) ($_GET['tahun'] ?? '')),
            'keyword' => trim((string) ($_GET['keyword'] ?? '')),
        ];
        $filtersValidation = $this->validatePayload(
            $archiveFilters,
            [
                'jenis' => 'permit_empty|in_list[kontrak,izin,tugas]',
                'tahun' => 'permit_empty|exact_length[4]|regex_match[/^\\d{4}$/]',
                'keyword' => 'permit_empty|max_length[160]',
            ],
            [
                'tahun' => [
                    'regex_match' => 'Filter tahun harus 4 digit angka.',
                ],
            ]
        );
        if (!$filtersValidation['valid']) {
            $archiveFilters = [
                'jenis' => '',
                'tahun' => '',
                'keyword' => '',
            ];
        }

        try {
            $rows = $this->letterModel->getHeadArchiveRows(500, $archiveFilters);
            $summary = $this->letterModel->getHeadArchiveSummary($archiveFilters);
            $yearOptions = $this->letterModel->getHeadArchiveYears();
        } catch (Throwable $e) {
            $dbError = $e->getMessage();
        }

        $errorMessage = $_GET['error'] ?? null;
        if ($dbError !== null) {
            $errorMessage = $dbError;
        }

        $this->render('archives/index', [
            'pageTitle' => 'Arsip Surat',
            'rows' => $rows,
            'summary' => $summary,
            'archiveFilters' => $archiveFilters,
            'yearOptions' => $yearOptions,
            'successMessage' => $_GET['success'] ?? null,
            'errorMessage' => $errorMessage,
        ]);
    }

    public function delete(): void
    {
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
            $this->redirectToPath('arsip-surat', ['error' => 'Metode request tidak valid.']);
        }

        $letterId = (int) ($_POST['id'] ?? 0);
        $deleteValidation = $this->validatePayload(
            ['id' => (string) $letterId],
            ['id' => 'required|integer|greater_than[0]']
        );
        if (!$deleteValidation['valid']) {
            $this->redirectToPath('arsip-surat', [
                'error' => $this->firstValidationError($deleteValidation['errors'], 'ID surat tidak valid.'),
            ]);
        }
        if ($letterId <= 0) {
            $this->redirectToPath('arsip-surat', ['error' => 'ID surat tidak valid.']);
        }

        try {
            $letter = $this->letterModel->getByIdWithDetails($letterId);
            if ($letter === null) {
                throw new RuntimeException('Data surat tidak ditemukan.');
            }

            $status = strtolower(str_replace(' ', '_', trim((string) ($letter['status'] ?? ''))));
            if (!in_array($status, ['surat_terbit', 'terbit', 'selesai'], true)) {
                throw new RuntimeException('Hanya surat terbit yang dapat dihapus dari arsip.');
            }

            $this->deleteLetterFiles($letter);

            $deleted = $this->letterModel->deleteIssuedLetterById($letterId);
            if (!$deleted) {
                throw new RuntimeException('Gagal menghapus surat dari arsip.');
            }

            logActivity('arsip', 'Menghapus surat terbit dari arsip', $letterId);

            $this->redirectToPath('arsip-surat', ['success' => 'Surat terbit berhasil dihapus dari arsip.']);
        } catch (Throwable $e) {
            $this->redirectToPath('arsip-surat', ['error' => $e->getMessage()]);
        }
    }

    private function deleteLetterFiles(array $letter): void
    {
        $paths = [];
        $pdfPath = trim((string) ($letter['file_pdf'] ?? ''));
        if ($pdfPath !== '') {
            $paths[] = $pdfPath;
        }

        foreach ($this->extractPermitAttachmentPaths((string) ($letter['attachment_file'] ?? '')) as $attachmentPath) {
            $paths[] = $attachmentPath;
        }

        $taskProposal = trim((string) ($letter['task_file_proposal'] ?? ''));
        if ($taskProposal !== '') {
            $paths[] = $taskProposal;
        }
        $taskSk = trim((string) ($letter['task_file_sk'] ?? ''));
        if ($taskSk !== '') {
            $paths[] = $taskSk;
        }

        $unique = [];
        foreach ($paths as $path) {
            $cleanPath = trim((string) $path);
            if ($cleanPath === '' || isset($unique[$cleanPath])) {
                continue;
            }
            $unique[$cleanPath] = true;
            $this->removeStorageFile($cleanPath);
        }
    }

    private function extractPermitAttachmentPaths(string $rawValue): array
    {
        $rawValue = trim($rawValue);
        if ($rawValue === '') {
            return [];
        }

        $decoded = json_decode($rawValue, true);
        if (is_array($decoded)) {
            $paths = [];
            foreach (['file_proposal', 'file_instrumen', 'file_pendukung_lain'] as $key) {
                $value = trim((string) ($decoded[$key] ?? ''));
                if ($value !== '') {
                    $paths[] = $value;
                }
            }

            return $paths;
        }

        return [$rawValue];
    }

    private function removeStorageFile(string $relativePath): void
    {
        $normalized = ltrim(str_replace('\\', '/', $relativePath), '/');
        if ($normalized === '') {
            return;
        }

        $projectRoot = realpath(__DIR__ . '/../../');
        if ($projectRoot === false) {
            return;
        }

        $fullPath = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalized);
        if (!is_file($fullPath)) {
            return;
        }

        @unlink($fullPath);
    }
}
