<?php
$app = require __DIR__ . '/../../../config.php';
$basePath = rtrim((string) (parse_url((string) ($app['app']['url'] ?? ''), PHP_URL_PATH) ?? ''), '/');

$stats = $stats ?? ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
$lettersPerMonth = $lettersPerMonth ?? [];
$recentLetters = $recentLetters ?? [];
$latestLetter = $latestLetter ?? null;
$adminManagementSummary = $adminManagementSummary ?? [
    'total_dosen' => 0,
    'total_prodi' => 0,
    'total_admin' => 0,
    'total_kepala' => 0,
    'dosen_lengkap' => 0,
];
$recentLogs = $recentLogs ?? [];
$logToday = (int) ($logToday ?? 0);
$adminDisplayLabel = (string) ($adminDisplayLabel ?? 'Admin LPPM');
$welcomeName = (string) ($welcomeName ?? 'Kepala LPPM');

$statusLabelMap = [
    'draft' => 'Draft',
    'diajukan' => 'Menunggu Diproses',
    'submitted' => 'Menunggu Diproses',
    'menunggu diproses' => 'Menunggu Diproses',
    'diverifikasi' => 'Diverifikasi',
    'perlu_diperbaiki' => 'Perlu Diperbaiki',
    'perlu diperbaiki' => 'Perlu Diperbaiki',
    'menunggu_finalisasi' => 'Disetujui',
    'disetujui' => 'Disetujui',
    'approved' => 'Disetujui',
    'surat_terbit' => 'Surat Terbit',
    'surat terbit' => 'Surat Terbit',
    'terbit' => 'Surat Terbit',
    'ditolak' => 'Perlu Diperbaiki',
    'rejected' => 'Perlu Diperbaiki',
    'selesai' => 'Selesai',
];
$statusClassMap = [
    'draft' => 'admin-badge-draft',
    'diajukan' => 'admin-badge-pending',
    'submitted' => 'admin-badge-pending',
    'menunggu diproses' => 'admin-badge-pending',
    'diverifikasi' => 'admin-badge-verified',
    'perlu_diperbaiki' => 'admin-badge-revision',
    'perlu diperbaiki' => 'admin-badge-revision',
    'menunggu_finalisasi' => 'admin-badge-approved',
    'disetujui' => 'admin-badge-approved',
    'approved' => 'admin-badge-approved',
    'surat_terbit' => 'admin-badge-issued',
    'surat terbit' => 'admin-badge-issued',
    'terbit' => 'admin-badge-issued',
    'ditolak' => 'admin-badge-revision',
    'rejected' => 'admin-badge-revision',
    'selesai' => 'admin-badge-completed',
];
$statusIconMap = [
    'draft' => 'bi-pencil-square',
    'diajukan' => 'bi-clock-history',
    'submitted' => 'bi-clock-history',
    'menunggu diproses' => 'bi-clock-history',
    'diverifikasi' => 'bi-patch-check',
    'perlu_diperbaiki' => 'bi-pencil-square',
    'perlu diperbaiki' => 'bi-pencil-square',
    'menunggu_finalisasi' => 'bi-check2-circle',
    'disetujui' => 'bi-check2-circle',
    'approved' => 'bi-check2-circle',
    'surat_terbit' => 'bi-patch-check',
    'surat terbit' => 'bi-patch-check',
    'terbit' => 'bi-patch-check',
    'ditolak' => 'bi-pencil-square',
    'rejected' => 'bi-pencil-square',
    'selesai' => 'bi-check2-all',
];

$formatTanggalIndonesia = static function (?string $date): string {
    if ($date === null || trim($date) === '') {
        return '-';
    }

    $ts = strtotime($date);
    if ($ts === false) {
        return '-';
    }

    $months = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    return (int) date('j', $ts) . ' ' . ($months[(int) date('n', $ts)] ?? date('F', $ts)) . ' ' . date('Y', $ts);
};
$formatTanggalWaktuIndonesia = static function (?string $date): string {
    if ($date === null || trim($date) === '') {
        return '-';
    }

    $ts = strtotime($date);
    if ($ts === false) {
        return '-';
    }

    $months = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    return (int) date('j', $ts) . ' ' . ($months[(int) date('n', $ts)] ?? date('F', $ts)) . ' ' . date('Y', $ts) . ' - ' . date('H:i', $ts);
};

$monthLabels = !empty($lettersPerMonth) ? array_keys($lettersPerMonth) : ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$monthValues = !empty($lettersPerMonth) ? array_values($lettersPerMonth) : array_fill(0, 12, 0);

$latestType = (string) ($latestLetter['type'] ?? 'Belum ada surat masuk');
$latestDate = $formatTanggalIndonesia((string) ($latestLetter['date'] ?? ''));
$latestStatusRaw = strtolower((string) ($latestLetter['status'] ?? 'draft'));
$latestStatusLabel = $statusLabelMap[$latestStatusRaw] ?? ucwords((string) ($latestLetter['status'] ?? 'Draft'));
$latestStatusClass = $statusClassMap[$latestStatusRaw] ?? 'admin-badge-draft';
$latestStatusIcon = $statusIconMap[$latestStatusRaw] ?? 'bi-pencil-square';
$dashboardTitle = $adminDisplayLabel === 'Kepala LPPM' ? 'Dashboard Kepala LPPM' : 'Dashboard Admin';
$isKepalaPanel = $adminDisplayLabel === 'Kepala LPPM';
$antrianNote = $adminDisplayLabel === 'Kepala LPPM'
    ? 'Ringkasan ini menampilkan surat terakhir yang masuk ke antrian proses kepala LPPM.'
    : 'Ringkasan ini menampilkan surat terakhir yang masuk ke antrian proses admin.';
$verificationNote = $adminDisplayLabel === 'Kepala LPPM' ? 'Perlu verifikasi kepala LPPM' : 'Perlu verifikasi admin';
$dayNames = [
    'Sunday' => 'Minggu',
    'Monday' => 'Senin',
    'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday' => 'Kamis',
    'Friday' => 'Jumat',
    'Saturday' => 'Sabtu',
];
$today = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
$todayDayName = $dayNames[$today->format('l')] ?? $today->format('l');
$todayMonthName = $months[(int) $today->format('n')] ?? $today->format('F');
$formattedToday = $todayDayName . ', ' . $today->format('d') . ' ' . $todayMonthName . ' ' . $today->format('Y');
$roleLabelMap = [
    'admin' => 'Admin',
    'kepala_lppm' => 'Kepala LPPM',
    'dosen' => 'Dosen',
];
$moduleLabelMap = [
    'users' => 'Pengguna',
    'pengguna' => 'Pengguna',
    'letters' => 'Persuratan',
    'persuratan' => 'Persuratan',
    'contracts' => 'Kontrak',
    'contract' => 'Kontrak',
    'profiles' => 'Profil',
    'profil' => 'Profil',
    'settings' => 'Pengaturan',
    'master-data' => 'Master Data',
    'master_data' => 'Master Data',
    'auth' => 'Autentikasi',
    'arsip' => 'Arsip',
];
?>

<div class="page-content admin-dashboard-page">
    <div class="admin-page-head d-flex justify-content-between align-items-center mb-3">
        <h1 class="admin-page-title mb-1"><?= htmlspecialchars($dashboardTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
        <div class="admin-date-chip" title="Tanggal hari ini">
            <span class="admin-date-chip-icon"><i class="bi bi-calendar-event"></i></span>
            <span class="admin-date-chip-content">
                <small>Hari ini</small>
                <strong><?= htmlspecialchars($formattedToday, ENT_QUOTES, 'UTF-8'); ?></strong>
            </span>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-7">
            <div class="admin-hero-card h-100">
                <span class="admin-hero-orb admin-hero-orb-left" aria-hidden="true"></span>
                <span class="admin-hero-orb admin-hero-orb-right" aria-hidden="true"></span>
                <div class="admin-hero-content">
                    <h2 class="admin-hero-title">Selamat Datang, <?= htmlspecialchars($welcomeName, ENT_QUOTES, 'UTF-8'); ?></h2>
                    <?php if ($isKepalaPanel): ?>
                        <p class="admin-hero-subtitle">Kelola pengajuan surat penelitian, pengabdian, dan hilirisasi dengan lebih cepat, rapi, dan terstruktur melalui SAPA LPPM.</p>
                    <?php else: ?>
                        <p class="admin-hero-subtitle">Kelola akun pengguna, pantau aktivitas sistem, dan pastikan operasional SAPA LPPM berjalan konsisten setiap hari.</p>
                    <?php endif; ?>
                    <div class="admin-hero-actions">
                        <?php if ($isKepalaPanel): ?>
                            <a href="<?= htmlspecialchars($basePath . '/persuratan', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary admin-btn-main">Kelola Surat</a>
                            <a href="<?= htmlspecialchars($basePath . '/pengaturan/nomor-surat', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-primary admin-btn-outline">Pengaturan Nomor Surat</a>
                        <?php else: ?>
                            <a href="<?= htmlspecialchars($basePath . '/pengguna', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary admin-btn-main">Kelola Pengguna</a>
                            <a href="<?= htmlspecialchars($basePath . '/log-aktivitas', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-primary admin-btn-outline">Lihat Log Aktivitas</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="admin-hero-image-wrap">
                    <div class="admin-hero-image-frame">
                        <img src="<?= htmlspecialchars(appAssetUrl('assets/img/dashboard-admin.png'), ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars($dashboardTitle, ENT_QUOTES, 'UTF-8'); ?>" class="admin-hero-image">
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="admin-summary-card h-100">
                <div class="admin-summary-head">
                    <h3 class="admin-summary-title mb-0"><?= htmlspecialchars($isKepalaPanel ? 'Aktivitas Proses Terakhir' : 'Ringkasan Kontrol Admin', ENT_QUOTES, 'UTF-8'); ?></h3>
                </div>
                <div class="admin-summary-body">
                    <?php if ($isKepalaPanel): ?>
                        <h4 class="admin-summary-letter"><?= htmlspecialchars($latestType, ENT_QUOTES, 'UTF-8'); ?></h4>
                        <p class="admin-summary-date mb-2">Tanggal: <span><?= htmlspecialchars($latestDate, ENT_QUOTES, 'UTF-8'); ?></span></p>
                        <span class="admin-status-badge <?= htmlspecialchars($latestStatusClass, ENT_QUOTES, 'UTF-8'); ?>">
                            <i class="bi <?= htmlspecialchars($latestStatusIcon, ENT_QUOTES, 'UTF-8'); ?>"></i>
                            <?= htmlspecialchars($latestStatusLabel, ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                        <div class="admin-summary-meta-list">
                            <div class="admin-summary-meta-item">
                                <span class="admin-summary-meta-label">Antrian</span>
                                <span class="admin-summary-meta-value">Proses Administrasi</span>
                            </div>
                            <div class="admin-summary-meta-item">
                                <span class="admin-summary-meta-label">Keterangan</span>
                                <span class="admin-summary-meta-value">Data terbaru sinkron dari pengajuan surat.</span>
                            </div>
                        </div>
                        <p class="admin-summary-note mb-0"><?= htmlspecialchars($antrianNote, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php else: ?>
                        <h4 class="admin-summary-letter">Kontrol Pengguna & Aktivitas</h4>
                        <p class="admin-summary-date mb-2">Hari ini: <span><?= (int) $logToday; ?> aktivitas tercatat</span></p>
                        <div class="admin-summary-meta-list">
                            <div class="admin-summary-meta-item">
                                <span class="admin-summary-meta-label">Akun Admin</span>
                                <span class="admin-summary-meta-value"><?= (int) ($adminManagementSummary['total_admin'] ?? 0); ?> akun</span>
                            </div>
                            <div class="admin-summary-meta-item">
                                <span class="admin-summary-meta-label">Kepala LPPM</span>
                                <span class="admin-summary-meta-value"><?= (int) ($adminManagementSummary['total_kepala'] ?? 0); ?> akun</span>
                            </div>
                            <div class="admin-summary-meta-item">
                                <span class="admin-summary-meta-label">Dosen Profil Lengkap</span>
                                <span class="admin-summary-meta-value"><?= (int) ($adminManagementSummary['dosen_lengkap'] ?? 0); ?> akun</span>
                            </div>
                        </div>
                        <p class="admin-summary-note mb-0">Gunakan dashboard ini untuk monitoring akun dan jejak aktivitas sistem.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if ($isKepalaPanel): ?>
        <div class="admin-quick-actions admin-quick-actions-four mb-3">
            <a href="<?= htmlspecialchars($basePath . '/persuratan', ENT_QUOTES, 'UTF-8'); ?>" class="admin-quick-btn"><i class="bi bi-patch-check"></i>Verifikasi Surat</a>
            <a href="<?= htmlspecialchars($basePath . '/arsip-surat', ENT_QUOTES, 'UTF-8'); ?>" class="admin-quick-btn"><i class="bi bi-archive"></i>Arsip Surat</a>
            <a href="<?= htmlspecialchars($basePath . '/pengaturan/nomor-surat', ENT_QUOTES, 'UTF-8'); ?>" class="admin-quick-btn"><i class="bi bi-123"></i>Nomor Surat</a>
            <a href="<?= htmlspecialchars($basePath . '/log-aktivitas', ENT_QUOTES, 'UTF-8'); ?>" class="admin-quick-btn"><i class="bi bi-clock-history"></i>Log Aktivitas</a>
        </div>
    <?php else: ?>
        <div class="admin-quick-actions admin-quick-actions-admin mb-3">
            <a href="<?= htmlspecialchars($basePath . '/pengguna', ENT_QUOTES, 'UTF-8'); ?>" class="admin-quick-btn"><i class="bi bi-people-fill"></i>Data Pengguna</a>
            <a href="<?= htmlspecialchars($basePath . '/log-aktivitas', ENT_QUOTES, 'UTF-8'); ?>" class="admin-quick-btn"><i class="bi bi-clock-history"></i>Log Aktivitas</a>
            <a href="<?= htmlspecialchars($basePath . '/profil-admin', ENT_QUOTES, 'UTF-8'); ?>" class="admin-quick-btn"><i class="bi bi-person-vcard"></i>Profil Admin</a>
            <a href="<?= htmlspecialchars($basePath . '/master-data/luaran', ENT_QUOTES, 'UTF-8'); ?>" class="admin-quick-btn"><i class="bi bi-database-gear"></i>Master Data</a>
        </div>
    <?php endif; ?>

    <?php if ($isKepalaPanel): ?>
        <div class="row g-3 mb-3">
            <div class="col-xl col-md-6">
                <div class="admin-stat-card">
                    <div class="admin-stat-top">
                        <div class="admin-stat-icon admin-stat-blue"><i class="bi bi-collection"></i></div>
                        <div class="admin-stat-number admin-stat-number-blue"><?= (int) ($stats['total'] ?? 0); ?></div>
                    </div>
                    <div class="admin-stat-title">Total Surat Masuk</div>
                    <div class="admin-stat-meta">Total semua pengajuan surat</div>
                </div>
            </div>
            <div class="col-xl col-md-6">
                <div class="admin-stat-card">
                    <div class="admin-stat-top">
                        <div class="admin-stat-icon admin-stat-blue"><i class="bi bi-hourglass-split"></i></div>
                        <div class="admin-stat-number admin-stat-number-blue"><?= (int) ($stats['pending'] ?? 0); ?></div>
                    </div>
                    <div class="admin-stat-title">Menunggu Diproses</div>
                    <div class="admin-stat-meta"><?= htmlspecialchars($verificationNote, ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            </div>
            <div class="col-xl col-md-6">
                <div class="admin-stat-card">
                    <div class="admin-stat-top">
                        <div class="admin-stat-icon admin-stat-orange"><i class="bi bi-arrow-repeat"></i></div>
                        <div class="admin-stat-number admin-stat-number-orange"><?= (int) ($stats['revision'] ?? 0); ?></div>
                    </div>
                    <div class="admin-stat-title">Perlu Diperbaiki</div>
                    <div class="admin-stat-meta">Surat yang dikembalikan untuk revisi</div>
                </div>
            </div>
            <div class="col-xl col-md-6">
                <div class="admin-stat-card">
                    <div class="admin-stat-top">
                        <div class="admin-stat-icon admin-stat-green"><i class="bi bi-check-circle"></i></div>
                        <div class="admin-stat-number admin-stat-number-green"><?= (int) ($stats['approved'] ?? 0); ?></div>
                    </div>
                    <div class="admin-stat-title">Disetujui</div>
                    <div class="admin-stat-meta">Surat yang telah disetujui</div>
                </div>
            </div>
            <div class="col-xl col-md-6">
                <div class="admin-stat-card">
                    <div class="admin-stat-top">
                        <div class="admin-stat-icon admin-stat-cyan"><i class="bi bi-file-earmark-check"></i></div>
                        <div class="admin-stat-number admin-stat-number-cyan"><?= (int) ($stats['issued'] ?? 0); ?></div>
                    </div>
                    <div class="admin-stat-title">Surat Terbit</div>
                    <div class="admin-stat-meta">Surat final sudah diterbitkan</div>
                </div>
            </div>
        </div>

        <div class="row g-3 admin-dashboard-main-row">
            <div class="col-lg-5 admin-dashboard-chart-col">
                <div class="admin-section-card h-100">
                    <div class="admin-section-head">
                        <h3 class="admin-section-title mb-0">Tren Pengajuan Surat</h3>
                    </div>
                    <div class="admin-chart-wrap">
                        <canvas id="adminTrendChart"></canvas>
                    </div>
                    <div class="admin-chart-legend">
                        <span class="admin-legend-dot"></span>
                        <span>Jumlah Pengajuan</span>
                    </div>
                </div>
            </div>

            <div class="col-lg-7 admin-dashboard-table-col">
                <div class="admin-table-card h-100">
                    <div class="admin-section-head">
                        <h3 class="admin-section-title mb-0"><i class="bi bi-table me-2"></i>Surat Terbaru</h3>
                    </div>
                    <div class="table-responsive admin-table-wrap">
                        <table id="adminLettersTable" data-custom-pagination="5" class="table admin-custom-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nomor Surat</th>
                                    <th>Jenis Surat</th>
                                    <th>Pemohon</th>
                                    <th>Tanggal</th>
                                    <th>Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recentLetters)): ?>
                                    <?php foreach ($recentLetters as $index => $row): ?>
                                        <?php
                                        $statusRaw = strtolower((string) ($row['status'] ?? 'draft'));
                                        $statusLabel = $statusLabelMap[$statusRaw] ?? ucwords((string) ($row['status'] ?? 'Draft'));
                                        $statusClass = $statusClassMap[$statusRaw] ?? 'admin-badge-draft';
                                        $statusIcon = $statusIconMap[$statusRaw] ?? 'bi-pencil-square';
                                        ?>
                                        <tr>
                                            <td><?= (int) $index + 1; ?></td>
                                            <td><?= htmlspecialchars((string) (($row['letter_number'] ?? '') !== '' ? $row['letter_number'] : '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?= htmlspecialchars((string) ($row['type'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?= htmlspecialchars((string) ($row['applicant_name'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?= htmlspecialchars($formatTanggalIndonesia((string) ($row['date'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>
                                                <span class="admin-status-badge <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8'); ?>">
                                                    <i class="bi <?= htmlspecialchars($statusIcon, ENT_QUOTES, 'UTF-8'); ?>"></i>
                                                    <?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <div class="admin-action-group">
                                                    <a href="<?= htmlspecialchars($basePath . '/persuratan/' . (int) ($row['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>" class="admin-action-btn" title="Detail">Detail</a>
                                                    <a href="<?= htmlspecialchars($basePath . '/persuratan/' . (int) ($row['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>" class="admin-action-btn admin-action-btn-primary" title="Proses">Proses</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">Belum ada data surat terbaru.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-3 mb-3">
            <div class="col-xl col-md-6">
                <div class="admin-stat-card">
                    <div class="admin-stat-top">
                        <div class="admin-stat-icon admin-stat-blue"><i class="bi bi-people-fill"></i></div>
                        <div class="admin-stat-number admin-stat-number-blue"><?= (int) ($adminManagementSummary['total_dosen'] ?? 0); ?></div>
                    </div>
                    <div class="admin-stat-title">Total Dosen</div>
                    <div class="admin-stat-meta">Akun dosen terdaftar di sistem</div>
                </div>
            </div>
            <div class="col-xl col-md-6">
                <div class="admin-stat-card">
                    <div class="admin-stat-top">
                        <div class="admin-stat-icon admin-stat-blue"><i class="bi bi-journal-bookmark"></i></div>
                        <div class="admin-stat-number admin-stat-number-blue"><?= (int) ($adminManagementSummary['total_prodi'] ?? 0); ?></div>
                    </div>
                    <div class="admin-stat-title">Program Studi</div>
                    <div class="admin-stat-meta">Jumlah prodi unik pengguna dosen</div>
                </div>
            </div>
            <div class="col-xl col-md-6">
                <div class="admin-stat-card">
                    <div class="admin-stat-top">
                        <div class="admin-stat-icon admin-stat-green"><i class="bi bi-person-badge"></i></div>
                        <div class="admin-stat-number admin-stat-number-green"><?= (int) ($adminManagementSummary['total_admin'] ?? 0); ?></div>
                    </div>
                    <div class="admin-stat-title">Akun Admin</div>
                    <div class="admin-stat-meta">Akun pengelola panel admin</div>
                </div>
            </div>
            <div class="col-xl col-md-6">
                <div class="admin-stat-card">
                    <div class="admin-stat-top">
                        <div class="admin-stat-icon admin-stat-cyan"><i class="bi bi-person-check"></i></div>
                        <div class="admin-stat-number admin-stat-number-cyan"><?= (int) ($adminManagementSummary['total_kepala'] ?? 0); ?></div>
                    </div>
                    <div class="admin-stat-title">Kepala LPPM</div>
                    <div class="admin-stat-meta">Akun pimpinan aktif saat ini</div>
                </div>
            </div>
            <div class="col-xl col-md-6">
                <div class="admin-stat-card">
                    <div class="admin-stat-top">
                        <div class="admin-stat-icon admin-stat-orange"><i class="bi bi-activity"></i></div>
                        <div class="admin-stat-number admin-stat-number-orange"><?= (int) $logToday; ?></div>
                    </div>
                    <div class="admin-stat-title">Aktivitas Hari Ini</div>
                    <div class="admin-stat-meta">Jejak aktivitas tercatat hari ini</div>
                </div>
            </div>
        </div>

        <div class="row g-3 admin-dashboard-main-row">
            <div class="col-lg-4 admin-dashboard-chart-col">
                <div class="admin-section-card h-100">
                    <div class="admin-section-head">
                        <h3 class="admin-section-title mb-0">Distribusi Akun</h3>
                    </div>
                    <div class="admin-chart-wrap admin-chart-wrap-donut">
                        <canvas id="adminRoleChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-lg-8 admin-dashboard-table-col">
                <div class="admin-table-card h-100">
                    <div class="admin-section-head">
                        <h3 class="admin-section-title mb-0"><i class="bi bi-table me-2"></i>Aktivitas Terbaru Sistem</h3>
                    </div>
                    <div class="table-responsive admin-table-wrap">
                        <table id="adminActivityTable" data-custom-pagination="7" class="table admin-custom-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="admin-activity-col-no">No</th>
                                    <th class="admin-activity-col-time">Waktu</th>
                                    <th class="admin-activity-col-user">Pengguna</th>
                                    <th class="admin-activity-col-role">Peran</th>
                                    <th class="admin-activity-col-module">Modul</th>
                                    <th class="admin-activity-col-action">Aktivitas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recentLogs)): ?>
                                    <?php foreach ($recentLogs as $index => $row): ?>
                                        <?php
                                        $roleRaw = strtolower((string) ($row['user_role'] ?? ''));
                                        $moduleRaw = strtolower((string) ($row['module'] ?? ''));
                                        $roleLabel = $roleLabelMap[$roleRaw] ?? ucwords(str_replace('_', ' ', $roleRaw));
                                        $moduleLabel = $moduleLabelMap[$moduleRaw] ?? ucfirst($moduleRaw !== '' ? $moduleRaw : '-');
                                        $createdAtTs = strtotime((string) ($row['created_at'] ?? ''));
                                        $activityDate = $createdAtTs !== false ? $formatTanggalIndonesia((string) ($row['created_at'] ?? '')) : '-';
                                        $activityTime = $createdAtTs !== false ? date('H:i', $createdAtTs) . ' WITA' : '';
                                        ?>
                                        <tr>
                                            <td class="admin-activity-col-no"><?= (int) $index + 1; ?></td>
                                            <td class="admin-activity-col-time">
                                                <div class="admin-activity-time">
                                                    <span class="admin-activity-date"><?= htmlspecialchars($activityDate, ENT_QUOTES, 'UTF-8'); ?></span>
                                                    <?php if ($activityTime !== ''): ?>
                                                        <span class="admin-activity-hour"><?= htmlspecialchars($activityTime, ENT_QUOTES, 'UTF-8'); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="admin-activity-col-user"><?= htmlspecialchars((string) ($row['user_name'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td class="admin-activity-col-role"><span class="admin-activity-role-pill"><?= htmlspecialchars((string) ($roleLabel !== '' ? $roleLabel : '-'), ENT_QUOTES, 'UTF-8'); ?></span></td>
                                            <td class="admin-activity-col-module"><span class="admin-activity-module-pill"><?= htmlspecialchars((string) $moduleLabel, ENT_QUOTES, 'UTF-8'); ?></span></td>
                                            <td class="admin-activity-col-action"><?= htmlspecialchars((string) ($row['action'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">Belum ada log aktivitas terbaru.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
(() => {
    if (!window.Chart) return;

    <?php if ($isKepalaPanel): ?>
    const trendCanvas = document.getElementById('adminTrendChart');
    if (trendCanvas) {
        const labels = <?= json_encode($monthLabels, JSON_UNESCAPED_UNICODE); ?>;
        const values = <?= json_encode($monthValues, JSON_UNESCAPED_UNICODE); ?>;

        new Chart(trendCanvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Jumlah Pengajuan',
                    data: values,
                    backgroundColor: 'rgba(59, 125, 221, 0.9)',
                    borderRadius: 8,
                    barThickness: 22
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#123c6b',
                        titleColor: '#fff',
                        bodyColor: '#fff'
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#64748b', font: { size: 12 } },
                        border: { display: false }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            color: '#64748b',
                            font: { size: 12 }
                        },
                        grid: {
                            color: '#eef2f7'
                        },
                        border: { display: false }
                    }
                }
            }
        });
    }
    <?php else: ?>
    const roleCanvas = document.getElementById('adminRoleChart');
    if (roleCanvas) {
        const roleData = [
            <?= (int) ($adminManagementSummary['total_admin'] ?? 0); ?>,
            <?= (int) ($adminManagementSummary['total_kepala'] ?? 0); ?>,
            <?= (int) ($adminManagementSummary['total_dosen'] ?? 0); ?>
        ];

        new Chart(roleCanvas, {
            type: 'doughnut',
            data: {
                labels: ['Admin', 'Kepala LPPM', 'Dosen'],
                datasets: [{
                    data: roleData,
                    backgroundColor: ['#2f6fd6', '#36a269', '#f59e0b'],
                    borderColor: '#ffffff',
                    borderWidth: 2,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '64%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            boxHeight: 12,
                            color: '#4b5563',
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        backgroundColor: '#123c6b',
                        titleColor: '#fff',
                        bodyColor: '#fff'
                    }
                }
            }
        });
    }
    <?php endif; ?>
})();
</script>
