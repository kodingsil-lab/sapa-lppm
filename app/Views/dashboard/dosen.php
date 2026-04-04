<?php
$app = require __DIR__ . '/../../../config.php';
$basePath = rtrim((string) (parse_url((string) ($app['app']['url'] ?? ''), PHP_URL_PATH) ?? ''), '/');
$statusLabel = [
    'draft' => 'Draft',
    'diajukan' => 'Menunggu Diproses',
    'submitted' => 'Menunggu Diproses',
    'diverifikasi' => 'Menunggu Diproses',
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
$statusPillClass = [
    'draft' => 'myletters-status-ready',
    'diajukan' => 'myletters-status-waiting',
    'submitted' => 'myletters-status-waiting',
    'diverifikasi' => 'myletters-status-waiting',
    'perlu_diperbaiki' => 'myletters-status-revision',
    'perlu diperbaiki' => 'myletters-status-revision',
    'menunggu_finalisasi' => 'myletters-status-approved',
    'disetujui' => 'myletters-status-approved',
    'approved' => 'myletters-status-approved',
    'surat_terbit' => 'myletters-status-issued',
    'surat terbit' => 'myletters-status-issued',
    'terbit' => 'myletters-status-issued',
    'ditolak' => 'myletters-status-revision',
    'rejected' => 'myletters-status-revision',
    'selesai' => 'myletters-status-approved',
];
$statusIcon = [
    'draft' => 'bi-send-fill',
    'diajukan' => 'bi-clock',
    'submitted' => 'bi-clock',
    'diverifikasi' => 'bi-clock',
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
    'selesai' => 'bi-check2-circle',
];
$resolveJenisSurat = static function (array $row): string {
    $subject = strtolower((string) ($row['subject'] ?? ''));
    if (strpos($subject, 'kontrak') !== false) {
        if (strpos($subject, 'pengabdian') !== false) {
            return 'Surat Kontrak Pengabdian';
        }
        if (strpos($subject, 'hilirisasi') !== false) {
            return 'Surat Kontrak Hilirisasi';
        }
        return 'Surat Kontrak Penelitian';
    }
    if (strpos($subject, 'tugas') !== false) {
        if (strpos($subject, 'pengabdian') !== false) {
            return 'Surat Tugas Pengabdian';
        }
        if (strpos($subject, 'hilirisasi') !== false) {
            return 'Surat Tugas Hilirisasi';
        }
        return 'Surat Tugas Penelitian';
    }
    if (strpos($subject, 'izin') !== false) {
        if (strpos($subject, 'pengabdian') !== false) {
            return 'Surat Izin Pengabdian';
        }
        if (strpos($subject, 'hilirisasi') !== false) {
            return 'Surat Izin Hilirisasi';
        }
        return 'Surat Izin Penelitian';
    }

    return (string) ($row['letter_type_name'] ?? '-');
};

$dayNames = [
    'Sunday' => 'Minggu',
    'Monday' => 'Senin',
    'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday' => 'Kamis',
    'Friday' => 'Jumat',
    'Saturday' => 'Sabtu',
];
$monthNames = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
];
$today = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
$dayName = $dayNames[$today->format('l')] ?? $today->format('l');
$monthName = $monthNames[(int) $today->format('n')] ?? $today->format('F');
$formattedToday = $dayName . ', ' . $today->format('d') . ' ' . $monthName . ' ' . $today->format('Y');
?>
<div class="page-content dashboard-dosen-page">
        <!-- Page Header -->
        <div class="page-head d-flex justify-content-between align-items-center mb-3">
            <h1 class="page-title mb-0">Dashboard</h1>

            <div class="date-chip" title="Tanggal hari ini">
                <span class="date-chip-icon"><i class="bi bi-calendar-event"></i></span>
                <span class="date-chip-content">
                    <small>Hari ini</small>
                    <strong><?= htmlspecialchars($formattedToday, ENT_QUOTES, 'UTF-8'); ?></strong>
                </span>
            </div>
        </div>

        <!-- Hero Row -->
        <div class="row g-3 mb-3">
            <div class="col-lg-7">
                <div class="hero-card h-100">
                    <div class="hero-content">
                        <h2 class="hero-title">
                            Selamat Datang, <span><?= htmlspecialchars($userName ?? 'Dosen Pengusul', ENT_QUOTES, 'UTF-8'); ?></span>
                        </h2>

                        <p class="hero-subtitle">
                            Kelola pengajuan surat Anda dengan lebih
                            mudah, rapi, dan terstruktur melalui SAPA LPPM
                        </p>

                        <div class="hero-actions">
                            <a href="<?= htmlspecialchars($basePath . '/ajukan-surat/penelitian', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary-main">
                                Ajukan Surat
                            </a>
                            <a href="<?= htmlspecialchars($basePath . '/surat-saya', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-light-soft">
                                Surat Saya
                            </a>
                        </div>
                    </div>

                    <div class="hero-illustration">
                        <img src="<?= htmlspecialchars(appAssetUrl('assets/img/dashboard-hero.png'), ENT_QUOTES, 'UTF-8'); ?>" alt="Dashboard Hero">
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="latest-card h-100">
                    <h3 class="latest-title">Pengajuan Surat Terakhir</h3>

                    <div class="line-divider"></div>

                        <div class="latest-body">
                        <?php if (!empty($latestLetter)): ?>
                            <h4 class="latest-letter-title"><?= htmlspecialchars($resolveJenisSurat($latestLetter), ENT_QUOTES, 'UTF-8'); ?></h4>
                            <p class="latest-date">Tanggal: <span><?= htmlspecialchars(date('d M Y', strtotime((string) ($latestLetter['letter_date'] ?? 'now'))), ENT_QUOTES, 'UTF-8'); ?></span></p>

                            <?php $status = strtolower((string) ($latestLetter['status'] ?? '')); ?>
                            <span class="status-pill status-table-pill myletters-status-pill <?= htmlspecialchars((string) ($statusPillClass[$status] ?? 'myletters-status-ready'), ENT_QUOTES, 'UTF-8'); ?>">
                                <i class="bi <?= htmlspecialchars((string) ($statusIcon[$status] ?? 'bi-send-fill'), ENT_QUOTES, 'UTF-8'); ?>"></i>
                                <?= htmlspecialchars((string) ($statusLabel[$status] ?? ucfirst((string) ($latestLetter['status'] ?? 'Diverifikasi'))), ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        <?php else: ?>
                            <h4 class="latest-letter-title">Belum Ada Pengajuan Surat</h4>
                            <p class="latest-date">Tanggal: <span>10 Maret 2026</span></p>

                            <span class="status-pill status-blue">
                                <i class="bi bi-check-lg"></i> Diverifikasi
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="line-divider"></div>

                    <p class="latest-note mb-0"><?= htmlspecialchars(empty($latestLetter) ? 'Belum ada pengajuan terakhir.' : 'Pengajuan terakhir Anda.', ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
            </div>
        </div>

        <!-- Statistic Cards -->
        <div class="row g-3 mb-3">
            <div class="col-lg col-md-4">
                <div class="stat-card stat-card-total">
                    <div class="stat-top">
                        <div class="stat-icon stat-icon-blue">
                            <i class="bi bi-collection"></i>
                        </div>
                        <div class="stat-number stat-number-blue"><?= (int) ($stats['total'] ?? 0); ?></div>
                    </div>
                    <div class="stat-title">Total Surat Saya</div>
                    <div class="stat-meta">Akumulasi semua pengajuan</div>
                </div>
            </div>

            <div class="col-lg col-md-4">
                <div class="stat-card stat-card-waiting">
                    <div class="stat-top">
                        <div class="stat-icon stat-icon-waiting">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <div class="stat-number stat-number-waiting"><?= (int) ($stats['pending'] ?? 0); ?></div>
                    </div>
                    <div class="stat-title">Menunggu Diproses</div>
                    <div class="stat-meta">Perlu verifikasi kepala LPPM</div>
                </div>
            </div>

            <div class="col-lg col-md-4">
                <div class="stat-card stat-card-revision">
                    <div class="stat-top">
                        <div class="stat-icon stat-icon-revision">
                            <i class="bi bi-arrow-repeat"></i>
                        </div>
                        <div class="stat-number stat-number-revision"><?= (int) ($stats['revision'] ?? 0); ?></div>
                    </div>
                    <div class="stat-title">Perlu Diperbaiki</div>
                    <div class="stat-meta">Pengajuan dikembalikan untuk revisi</div>
                </div>
            </div>

            <div class="col-lg col-md-4">
                <div class="stat-card stat-card-approved">
                    <div class="stat-top">
                        <div class="stat-icon stat-icon-approved">
                            <i class="bi bi-check2-circle"></i>
                        </div>
                        <div class="stat-number stat-number-green"><?= (int) ($stats['approved'] ?? 0); ?></div>
                    </div>
                    <div class="stat-title">Disetujui</div>
                    <div class="stat-meta">Surat yang telah disetujui</div>
                </div>
            </div>

            <div class="col-lg col-md-4">
                <div class="stat-card stat-card-issued">
                    <div class="stat-top">
                        <div class="stat-icon stat-icon-issued">
                            <i class="bi bi-file-earmark-check"></i>
                        </div>
                        <div class="stat-number stat-number-issued"><?= (int) ($stats['issued'] ?? 0); ?></div>
                    </div>
                    <div class="stat-title">Surat Terbit</div>
                    <div class="stat-meta">Surat final sudah diterbitkan</div>
                </div>
            </div>

        </div>

        <!-- Chart + Table -->
        <div class="row g-3 dosen-dashboard-main-row">
            <div class="col-lg-5 dosen-dashboard-chart-col">
                <div class="section-card h-100">
                    <div class="section-head">
                        <h3 class="section-title mb-0">Tren Pengajuan Surat</h3>
                        <button class="dots-btn" type="button">
                            <i class="bi bi-three-dots"></i>
                        </button>
                    </div>

                    <div class="chart-wrap">
                        <canvas id="trendChart"></canvas>
                    </div>

                    <div class="chart-legend-box">
                        <span class="legend-dot"></span>
                        <span>Jumlah Pengajuan</span>
                    </div>
                </div>
            </div>

            <div class="col-lg-7 dosen-dashboard-table-col">
                <div class="section-card h-100">
                    <div class="section-head">
                        <h3 class="section-title mb-0">Surat Terbaru Saya</h3>
                        <button class="dots-btn" type="button">
                            <i class="bi bi-three-dots"></i>
                        </button>
                    </div>

                    <div class="table-responsive custom-table-wrap">
                        <table id="dosenTable" data-custom-pagination="5" class="table custom-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nomor Surat</th>
                                    <th>Jenis Surat</th>
                                    <th>Tanggal</th>
                                    <th>Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($myLetters)): ?>
                                    <?php foreach ($myLetters as $index => $letter): ?>
                                        <?php $status = strtolower((string) ($letter['status'] ?? '')); ?>
                                        <?php $isDownloadAllowed = in_array($status, ['surat_terbit', 'surat terbit', 'terbit', 'selesai'], true); ?>
                                        <?php
                                        $detailPath = $basePath . '/surat-saya/' . (int) ($letter['id'] ?? 0);
                                        ?>
                                        <tr>
                                            <td><?= (int) $index + 1; ?></td>
                                            <td><?= htmlspecialchars($letter['letter_number'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?= htmlspecialchars($resolveJenisSurat($letter), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?= htmlspecialchars(date('d M Y', strtotime((string) ($letter['letter_date'] ?? 'now'))), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>
                                                <span class="status-pill status-table-pill myletters-status-pill <?= htmlspecialchars((string) ($statusPillClass[$status] ?? 'myletters-status-ready'), ENT_QUOTES, 'UTF-8'); ?>">
                                                    <i class="bi <?= htmlspecialchars((string) ($statusIcon[$status] ?? 'bi-send-fill'), ENT_QUOTES, 'UTF-8'); ?>"></i>
                                                    <?= htmlspecialchars((string) ($statusLabel[$status] ?? ucfirst((string) ($letter['status'] ?? '-'))), ENT_QUOTES, 'UTF-8'); ?>
                                                </span>
                                            </td>
                                            <td class="text-center action-cell">
                                                <div class="dashboard-action-group">
                                                    <a href="<?= htmlspecialchars($detailPath, ENT_QUOTES, 'UTF-8'); ?>" class="dashboard-action-btn" title="Detail">Detail</a>
                                                    <?php if ($isDownloadAllowed): ?>
                                                        <a href="<?= htmlspecialchars($basePath . '/persuratan/' . (int) ($letter['id'] ?? 0) . '/download', ENT_QUOTES, 'UTF-8'); ?>" class="dashboard-action-btn dashboard-action-btn-primary" title="Unduh Surat">Unduh Surat</a>
                                                    <?php else: ?>
                                                        <button type="button" class="dashboard-action-btn dashboard-action-btn-disabled" disabled title="Aktif setelah surat terbit">Unduh Surat</button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">Belum ada surat</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
    const ctx = document.getElementById('trendChart');

    <?php if (!empty($lettersPerMonth)): ?>
        const monthLabels = <?= json_encode(array_keys($lettersPerMonth), JSON_UNESCAPED_UNICODE); ?>;
        const monthValues = <?= json_encode(array_values($lettersPerMonth), JSON_UNESCAPED_UNICODE); ?>;
    <?php else: ?>
        const monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        const monthValues = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    <?php endif; ?>

    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: monthLabels,
                datasets: [
                    {
                        label: 'Jumlah Pengajuan',
                        data: monthValues,
                        backgroundColor: 'rgba(59, 125, 221, 0.85)',
                        borderRadius: 6,
                        barThickness: 22
                    }
                ]
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
                        ticks: { color: '#6b7280', font: { size: 12 } },
                        border: { display: false }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            color: '#6b7280',
                            font: { size: 12 }
                        },
                        grid: {
                            color: '#edf1f5'
                        },
                        border: { display: false }
                    }
                }
            }
        });
    }
</script>
