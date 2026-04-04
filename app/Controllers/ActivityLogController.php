<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/ActivityLogModel.php';

class ActivityLogController extends BaseController
{
    private ActivityLogModel $activityLogModel;

    public function __construct()
    {
        parent::__construct();
        $this->activityLogModel = new ActivityLogModel();
    }

    public function index(): void
    {
        $logs = [];
        $errorMessage = null;

        try {
            $logs = $this->activityLogModel->getAllWithUser(2000);
        } catch (Throwable $e) {
            $errorMessage = $e->getMessage();
        }

        $this->render('activity_logs/index', [
            'pageTitle' => 'Log Aktivitas',
            'logs' => $logs,
            'successMessage' => $_GET['success'] ?? null,
            'errorMessage' => $_GET['error'] ?? $errorMessage,
        ]);
    }

    public function bulkDelete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToPath('log-aktivitas');
        }

        $ids = $_POST['log_ids'] ?? [];
        if (!is_array($ids)) {
            $ids = [];
        }
        $normalizedIds = [];
        foreach ($ids as $id) {
            $idString = trim((string) $id);
            if ($idString === '') {
                continue;
            }
            $rowValidation = $this->validatePayload(
                ['id' => $idString],
                ['id' => 'required|integer|greater_than[0]']
            );
            if (!$rowValidation['valid']) {
                continue;
            }
            $normalizedIds[] = (int) $idString;
        }
        $normalizedIds = array_values(array_unique($normalizedIds));
        if (count($normalizedIds) > 1000) {
            $this->redirectToPath('log-aktivitas', ['error' => 'Maksimal 1000 log dapat dihapus sekaligus.']);
        }

        try {
            $deletedRows = $this->activityLogModel->deleteBulkByIds($normalizedIds);
            if ($deletedRows <= 0) {
                $this->redirectToPath('log-aktivitas', ['error' => 'Tidak ada log yang dipilih untuk dihapus.']);
            }
            $this->redirectToPath('log-aktivitas', ['success' => 'Berhasil menghapus ' . $deletedRows . ' log aktivitas.']);
        } catch (Throwable $e) {
            $this->redirectToPath('log-aktivitas', ['error' => $e->getMessage()]);
        }
    }
}
