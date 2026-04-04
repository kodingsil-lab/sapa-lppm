<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Helpers/AuthHelper.php';
require_once __DIR__ . '/../Models/LetterModel.php';
require_once __DIR__ . '/../Models/UserModel.php';
require_once __DIR__ . '/../Models/ActivityLogModel.php';

class DashboardController extends BaseController
{
    public function index(): void
    {
        $role = authRole();
        if ($role === 'dosen') {
            $this->redirectToPath('dashboard-dosen');
        }

        if (isAdminPanelRole($role)) {
            $this->redirectToPath($this->adminDashboardPath());
        }

        $_SESSION = [];
        session_destroy();
        $this->redirectToPath('login', ['error' => 'Role akun tidak dikenali.']);
    }

    public function admin(): void
    {
        $userModel = new UserModel();
        $activityLogModel = new ActivityLogModel();
        $role = (string) authRole();
        $isKepala = $role === 'kepala_lppm';
        $currentUser = $userModel->findById((int) (authUserId() ?? 0));
        $welcomeName = trim((string) ($currentUser['name'] ?? ''));
        if ($welcomeName === '') {
            $welcomeName = $isKepala ? 'Kepala LPPM' : 'Admin LPPM';
        }

        try {
            $adminManagementSummary = $userModel->getAdminManagementSummary();
            $recentLogs = $activityLogModel->getRecentWithUser(10);
            $logToday = $activityLogModel->countToday();
            if ($isKepala) {
                $model = new LetterModel();
                $statsRaw = $model->getDashboardStats();
                $lettersPerMonth = $model->getLettersPerMonth((int) date('Y'));
                $recentRows = $model->getRecentLettersDetailed(10);
            } else {
                $statsRaw = ['total' => 0, 'pending' => 0, 'revision' => 0, 'approved' => 0, 'issued' => 0, 'rejected' => 0];
                $lettersPerMonth = [];
                $recentRows = [];
            }
        } catch (Throwable $e) {
            $statsRaw = ['total' => 0, 'pending' => 0, 'revision' => 0, 'approved' => 0, 'issued' => 0, 'rejected' => 0];
            $lettersPerMonth = $isKepala ? [
                'Januari' => 0, 'Februari' => 0, 'Maret' => 0, 'April' => 0,
                'Mei' => 0, 'Juni' => 0, 'Juli' => 0, 'Agustus' => 0,
                'September' => 0, 'Oktober' => 0, 'November' => 0, 'Desember' => 0,
            ] : [];
            $recentRows = [];
            $adminManagementSummary = [
                'total_dosen' => 0,
                'total_prodi' => 0,
                'total_admin' => 0,
                'total_kepala' => 0,
                'dosen_lengkap' => 0,
            ];
            $recentLogs = [];
            $logToday = 0;
        }

        $stats = [
            'total' => (int) ($statsRaw['total'] ?? $statsRaw['total_letters'] ?? 0),
            'pending' => (int) ($statsRaw['pending'] ?? $statsRaw['pending_approval'] ?? 0),
            'revision' => (int) ($statsRaw['revision'] ?? $statsRaw['rejected'] ?? $statsRaw['rejected_count'] ?? 0),
            'approved' => (int) ($statsRaw['approved'] ?? $statsRaw['approved_count'] ?? 0),
            'issued' => (int) ($statsRaw['issued'] ?? 0),
            'rejected' => (int) ($statsRaw['revision'] ?? $statsRaw['rejected'] ?? $statsRaw['rejected_count'] ?? 0),
        ];

        $recentLetters = [];
        if ($isKepala) {
            foreach ($recentRows as $row) {
                $recentLetters[] = [
                    'id' => (int) ($row['id'] ?? 0),
                    'letter_number' => (string) ($row['letter_number'] ?? '-'),
                    'type' => (string) ($row['type'] ?? '-'),
                    'applicant_name' => (string) ($row['applicant'] ?? '-'),
                    'date' => (string) ($row['date'] ?? ''),
                    'status' => (string) ($row['status'] ?? 'draft'),
                ];
            }
        }

        $latestLetter = $isKepala ? ($recentLetters[0] ?? null) : null;

        $this->render('dashboard/admin', [
            'pageTitle' => $isKepala ? 'Dashboard Kepala LPPM' : 'Dashboard Admin',
            'adminDisplayLabel' => $isKepala ? 'Kepala LPPM' : 'Admin LPPM',
            'welcomeName' => $welcomeName,
            'stats' => $stats,
            'adminManagementSummary' => $adminManagementSummary,
            'recentLogs' => $recentLogs,
            'logToday' => $logToday,
            'latestLetter' => $latestLetter,
            'lettersPerMonth' => $lettersPerMonth,
            'recentLetters' => $recentLetters,
        ]);
    }

    public function dosen(): void
    {
        $letterModel = new LetterModel();
        $userModel = new UserModel();
        $userId = (int) authUserId();
        $user = $userModel->findById($userId);

        $myLetters = $letterModel->getLettersForUser($userId, 10);
        $statsRaw = $letterModel->countMyLettersByStatus($userId);
        $lettersPerMonth = $letterModel->getLettersPerMonthForUser($userId, (int) date('Y'));

        $stats = [
            'total' => (int) ($statsRaw['total'] ?? 0),
            'pending' => (int) ($statsRaw['pending'] ?? 0),
            'revision' => (int) ($statsRaw['revision'] ?? 0),
            'approved' => (int) ($statsRaw['approved'] ?? 0),
            'issued' => (int) ($statsRaw['issued'] ?? 0),
        ];

        $this->render('dashboard/dosen', [
            'pageTitle' => 'Dashboard Dosen',
            'userName' => (string) ($user['name'] ?? 'Dosen'),
            'stats' => $stats,
            'latestLetter' => $myLetters[0] ?? null,
            'myLetters' => $myLetters,
            'lettersPerMonth' => $lettersPerMonth,
        ]);
    }
}
