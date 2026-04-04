<?php
$app = require __DIR__ . '/../../../config.php';
$basePath = rtrim((string) (parse_url((string) ($app['app']['url'] ?? ''), PHP_URL_PATH) ?? ''), '/');

$pageTitle = $pageTitle ?? 'Status Luaran';
$pageSubtitle = $pageSubtitle ?? '';
$filters = $filters ?? ['activity_type' => '', 'year' => '', 'status' => '', 'keyword' => ''];
$items = $items ?? [];
$categories = $categories ?? [];
$stats = $stats ?? ['total' => 0, 'belum' => 0, 'proses' => 0, 'selesai' => 0];

$statusLabels = [
    'belum' => 'Belum Diisi',
    'proses' => 'Belum Lengkap',
    'selesai' => 'Lengkap',
];
$statusClasses = [
    'belum' => 'status-pill-belum',
    'proses' => 'status-pill-proses',
    'selesai' => 'status-pill-selesai',
];

$defaultDataRoute = match (strtolower((string) ($filters['activity_type'] ?? ''))) {
    'pengabdian' => 'data-pengabdian',
    'hilirisasi' => 'data-hilirisasi',
    default => 'data-penelitian',
};
?>

<div class="page-content activity-page status-luaran-page">
    <div class="mb-3">
        <h1 class="page-title mb-1"><?= htmlspecialchars((string) $pageTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
        <p class="page-subtitle mb-0"><?= htmlspecialchars((string) $pageSubtitle, ENT_QUOTES, 'UTF-8'); ?></p>
    </div>

    <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success form-alert"><?= htmlspecialchars((string) $successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-warning form-alert"><?= htmlspecialchars((string) $errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="row g-3 mb-3">
        <div class="col-md-6 col-xl-3">
            <div class="status-stat-card">
                <span class="status-stat-label">Total Kegiatan</span>
                <strong class="status-stat-value"><?= (int) ($stats['total'] ?? 0); ?></strong>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="status-stat-card">
                <span class="status-stat-label">Belum Ada Luaran</span>
                <strong class="status-stat-value text-soft-red"><?= (int) ($stats['belum'] ?? 0); ?></strong>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="status-stat-card">
                <span class="status-stat-label">Belum Lengkap</span>
                <strong class="status-stat-value text-soft-warning"><?= (int) ($stats['proses'] ?? 0); ?></strong>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="status-stat-card">
                <span class="status-stat-label">Lengkap</span>
                <strong class="status-stat-value text-soft-green"><?= (int) ($stats['selesai'] ?? 0); ?></strong>
            </div>
        </div>
    </div>

    <div class="activity-card status-card status-filter-card mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <h3 class="section-title mb-0">Filter Data</h3>
        </div>
        <form method="get" action="<?= htmlspecialchars($basePath . '/status-luaran', ENT_QUOTES, 'UTF-8'); ?>" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Jenis Kegiatan</label>
                <select name="activity_type" class="form-select modern-input">
                    <option value="">Semua Kegiatan</option>
                    <?php foreach ($categories as $category): ?>
                        <?php $categoryCode = (string) ($category['code'] ?? ''); ?>
                        <option value="<?= htmlspecialchars($categoryCode, ENT_QUOTES, 'UTF-8'); ?>" <?= $filters['activity_type'] === $categoryCode ? 'selected' : ''; ?>>
                            <?= htmlspecialchars((string) ($category['name'] ?? $categoryCode), ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Tahun</label>
                <input type="text" name="year" class="form-control modern-input" value="<?= htmlspecialchars((string) ($filters['year'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="2026">
            </div>
            <div class="col-md-2">
                <label class="form-label">Status Luaran</label>
                <select name="status" class="form-select modern-input">
                    <option value="">Semua Status</option>
                    <option value="belum" <?= ($filters['status'] ?? '') === 'belum' ? 'selected' : ''; ?>>Belum Diisi</option>
                    <option value="proses" <?= ($filters['status'] ?? '') === 'proses' ? 'selected' : ''; ?>>Belum Lengkap</option>
                    <option value="selesai" <?= ($filters['status'] ?? '') === 'selesai' ? 'selected' : ''; ?>>Lengkap</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Kata Kunci</label>
                <input type="text" name="keyword" class="form-control modern-input" value="<?= htmlspecialchars((string) ($filters['keyword'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Cari judul / ketua">
            </div>
            <div class="col-md-2">
                <div class="status-filter-actions">
                    <button type="submit" class="btn btn-primary-main">Terapkan</button>
                    <a href="<?= htmlspecialchars($basePath . '/status-luaran', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-light-soft">Reset</a>
                </div>
            </div>
        </form>
    </div>

    <div class="activity-card status-card">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <h3 class="section-title mb-0">Daftar Status Luaran</h3>
        </div>

        <?php if (empty($items)): ?>
            <div class="empty-state-card text-center">
                <h5 class="mb-2">Belum ada data status luaran.</h5>
                <p class="text-muted mb-3">Silakan lengkapi data kegiatan terlebih dahulu agar status luaran dapat dimonitor.</p>
                <?php
                $defaultDataPath = match ($defaultDataRoute) {
                    'data-pengabdian' => '/data/pengabdian',
                    'data-hilirisasi' => '/data/hilirisasi',
                    default => '/data/penelitian',
                };
                ?>
                <a href="<?= htmlspecialchars($basePath . $defaultDataPath, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary-main">Ke Data Kegiatan</a>
            </div>
        <?php else: ?>
            <div class="status-table-wrap table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th>No.</th>
                        <th>Jenis Kegiatan</th>
                        <th>Judul Kegiatan</th>
                        <th>Tahun</th>
                        <th>Ketua</th>
                        <th>Progress Luaran</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $index => $row): ?>
                        <?php
                            $statusKey = strtolower((string) ($row['luaran_status'] ?? 'belum'));
                            $statusLabel = $statusLabels[$statusKey] ?? 'Belum Diisi';
                            $statusClass = $statusClasses[$statusKey] ?? 'status-pill-belum';
                            $completed = (int) ($row['completed_count'] ?? 0);
                            $required = (int) ($row['required_count'] ?? 0);
                            $progress = (int) ($row['progress_percent'] ?? 0);
                            $progress = max(0, min(100, $progress));
                        ?>
                        <tr>
                            <td><?= (int) $index + 1; ?></td>
                            <td><?= htmlspecialchars((string) ($row['activity_type_label'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <div class="status-table-title"><?= htmlspecialchars((string) ($row['judul'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div>
                            </td>
                            <td><?= htmlspecialchars((string) ($row['tahun'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?= htmlspecialchars((string) ($row['ketua'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <div class="status-progress-meta"><?= (int) $completed; ?> / <?= (int) $required; ?></div>
                                <div class="status-progress-bar">
                                    <span style="width: <?= (int) $progress; ?>%;"></span>
                                </div>
                            </td>
                            <td>
                                <span class="status-pill status-table-pill <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                            </td>
                            <td>
                                <a href="<?= htmlspecialchars($basePath . '/status-luaran/' . urlencode((string) ($row['activity_type'] ?? '')) . '/' . (int) ($row['activity_id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-outline-soft activity-btn">Detail Luaran</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>


