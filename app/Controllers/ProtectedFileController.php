<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/LetterModel.php';
require_once __DIR__ . '/../Models/ActivityOutputModel.php';
require_once __DIR__ . '/../Models/PenelitianModel.php';
require_once __DIR__ . '/../Models/PengabdianModel.php';
require_once __DIR__ . '/../Models/HilirisasiModel.php';
require_once __DIR__ . '/../Models/UserModel.php';
require_once __DIR__ . '/../Helpers/AuthHelper.php';

class ProtectedFileController extends BaseController
{
    private LetterModel $letterModel;
    private ActivityOutputModel $activityOutputModel;
    private PenelitianModel $penelitianModel;
    private PengabdianModel $pengabdianModel;
    private HilirisasiModel $hilirisasiModel;

    public function __construct()
    {
        parent::__construct();
        $this->letterModel = new LetterModel();
        $this->activityOutputModel = new ActivityOutputModel();
        $this->penelitianModel = new PenelitianModel();
        $this->pengabdianModel = new PengabdianModel();
        $this->hilirisasiModel = new HilirisasiModel();
    }

    public function letterAttachment(): void
    {
        $letterId = (int) ($_GET['id'] ?? 0);
        $slot = strtolower(trim((string) ($_GET['slot'] ?? '')));
        if ($letterId <= 0 || !in_array($slot, ['file_proposal', 'file_instrumen', 'file_pendukung_lain', 'file_sk'], true)) {
            http_response_code(400);
            echo 'Parameter file tidak valid.';
            return;
        }

        $letter = $this->letterModel->getByIdWithDetails($letterId);
        if ($letter === null) {
            http_response_code(404);
            echo 'Data surat tidak ditemukan.';
            return;
        }

        if (!$this->canAccessLetter($letter)) {
            http_response_code(403);
            echo 'Akses ditolak.';
            return;
        }

        $path = $this->resolveLetterAttachmentPath($letter, $slot);
        if ($path === '') {
            http_response_code(404);
            echo 'File lampiran tidak ditemukan.';
            return;
        }

        if (isExternalFileUrl($path)) {
            if (!isAllowedExternalFileUrl($path)) {
                http_response_code(403);
                echo 'Domain file eksternal tidak diizinkan.';
                return;
            }
            header('Location: ' . $path, true, 302);
            exit;
        }

        $this->serveProjectFile($path);
    }

    public function outputEvidence(): void
    {
        if (authRole() !== 'dosen') {
            http_response_code(403);
            echo 'Akses ditolak.';
            return;
        }

        $activityType = strtolower(trim((string) ($_GET['activity_type'] ?? '')));
        $activityId = (int) ($_GET['activity_id'] ?? 0);
        $outputTypeId = (int) ($_GET['output_type_id'] ?? 0);
        if (!in_array($activityType, ['penelitian', 'pengabdian', 'hilirisasi'], true) || $activityId <= 0 || $outputTypeId <= 0) {
            http_response_code(400);
            echo 'Parameter bukti luaran tidak valid.';
            return;
        }

        $activity = $this->findOwnedActivity($activityType, $activityId, (int) (authUserId() ?? 0));
        if ($activity === null) {
            http_response_code(404);
            echo 'Data kegiatan tidak ditemukan.';
            return;
        }

        $output = $this->activityOutputModel->findByActivityAndOutputType($activityType, $activityId, $outputTypeId);
        if ($output === null) {
            http_response_code(404);
            echo 'Bukti luaran tidak ditemukan.';
            return;
        }

        $path = trim((string) ($output['evidence_file'] ?? ''));
        if ($path === '') {
            $path = trim((string) ($output['evidence_link'] ?? ''));
        }
        if ($path === '') {
            http_response_code(404);
            echo 'Bukti luaran tidak ditemukan.';
            return;
        }

        if (isExternalFileUrl($path)) {
            if (!isAllowedExternalFileUrl($path)) {
                http_response_code(403);
                echo 'Domain file eksternal tidak diizinkan.';
                return;
            }
            header('Location: ' . $path, true, 302);
            exit;
        }

        $this->serveProjectFile($path);
    }

    public function userAvatar(): void
    {
        $userId = (int) ($_GET['id'] ?? 0);
        if ($userId <= 0) {
            http_response_code(400);
            echo 'Parameter avatar tidak valid.';
            return;
        }

        $authId = (int) (authUserId() ?? 0);
        if ($authId <= 0) {
            http_response_code(403);
            echo 'Akses ditolak.';
            return;
        }

        if (!isAdminPanelRole(authRole()) && $authId !== $userId) {
            http_response_code(403);
            echo 'Akses avatar ditolak.';
            return;
        }

        $userModel = new UserModel();
        $user = $userModel->findById($userId);
        if ($user === null) {
            http_response_code(404);
            echo 'User tidak ditemukan.';
            return;
        }

        $avatar = trim((string) ($user['avatar'] ?? ''));
        if ($avatar === '') {
            http_response_code(404);
            echo 'Avatar tidak ditemukan.';
            return;
        }

        $avatarFile = basename($avatar);
        $primaryPath = 'storage/uploads/avatars/' . $avatarFile;
        $legacyPath = 'public/uploads/avatars/' . $avatarFile;
        $projectRoot = realpath(__DIR__ . '/../../');
        $hasPrimary = $projectRoot !== false && is_file($projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $primaryPath));
        $targetPath = $hasPrimary ? $primaryPath : $legacyPath;
        $this->serveProjectFile($targetPath);
    }

    private function canAccessLetter(array $letter): bool
    {
        if (isAdminPanelRole(authRole())) {
            return true;
        }

        if (authRole() === 'dosen') {
            $userId = (int) (authUserId() ?? 0);
            return $userId > 0 && $this->letterModel->canViewByUser((int) ($letter['id'] ?? 0), $userId);
        }

        return false;
    }

    private function resolveLetterAttachmentPath(array $letter, string $slot): string
    {
        $permitPaths = $this->parsePermitAttachmentPaths((string) ($letter['attachment_file'] ?? ''));
        if ($slot !== 'file_sk' && trim((string) ($permitPaths[$slot] ?? '')) !== '') {
            return trim((string) $permitPaths[$slot]);
        }

        $taskMap = [
            'file_proposal' => 'task_file_proposal',
            'file_instrumen' => 'task_file_instrumen',
            'file_pendukung_lain' => 'task_file_pendukung_lain',
            'file_sk' => 'task_file_sk',
        ];
        $taskKey = $taskMap[$slot] ?? '';
        if ($taskKey === '') {
            return '';
        }

        return trim((string) ($letter[$taskKey] ?? ''));
    }

    private function parsePermitAttachmentPaths(string $rawValue): array
    {
        $rawValue = trim($rawValue);
        $result = [
            'file_proposal' => '',
            'file_instrumen' => '',
            'file_pendukung_lain' => '',
        ];

        if ($rawValue === '') {
            return $result;
        }

        $decoded = json_decode($rawValue, true);
        if (is_array($decoded)) {
            foreach (array_keys($result) as $key) {
                $result[$key] = trim((string) ($decoded[$key] ?? ''));
            }

            return $result;
        }

        $result['file_proposal'] = $rawValue;
        return $result;
    }

    private function findOwnedActivity(string $activityType, int $activityId, int $userId): ?array
    {
        if ($activityType === 'pengabdian') {
            return $this->pengabdianModel->findById($activityId, $userId);
        }
        if ($activityType === 'hilirisasi') {
            return $this->hilirisasiModel->findById($activityId, $userId);
        }

        return $this->penelitianModel->findById($activityId, $userId);
    }

    private function serveProjectFile(string $relativePath): void
    {
        $normalized = ltrim(str_replace('\\', '/', trim($relativePath)), '/');
        if ($normalized === '' || str_contains($normalized, '../')) {
            http_response_code(400);
            echo 'Path file tidak valid.';
            return;
        }

        $projectRoot = realpath(__DIR__ . '/../../');
        if ($projectRoot === false) {
            http_response_code(500);
            echo 'Akar project tidak ditemukan.';
            return;
        }

        $fullPath = realpath($projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalized));
        if ($fullPath === false || !is_file($fullPath)) {
            http_response_code(404);
            echo 'File tidak ditemukan.';
            return;
        }

        $projectRootPrefix = rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (!str_starts_with($fullPath, $projectRootPrefix)) {
            http_response_code(403);
            echo 'Akses file ditolak.';
            return;
        }

        $mime = 'application/octet-stream';
        if (class_exists('finfo')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $detected = (string) $finfo->file($fullPath);
            if ($detected !== '') {
                $mime = $detected;
            }
        } else {
            $fallbackMime = (string) (mime_content_type($fullPath) ?: '');
            if ($fallbackMime !== '') {
                $mime = $fallbackMime;
            }
        }
        $filename = basename($fullPath);
        $inlineMimes = [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/webp',
        ];
        $disposition = in_array($mime, $inlineMimes, true) ? 'inline' : 'attachment';

        header('Content-Type: ' . $mime);
        header('Content-Disposition: ' . $disposition . '; filename="' . $filename . '"');
        header('Content-Length: ' . (string) filesize($fullPath));
        header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        readfile($fullPath);
        exit;
    }
}
