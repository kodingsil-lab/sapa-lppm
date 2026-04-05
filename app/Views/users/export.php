<?php
$app = require __DIR__ . '/../../../config.php';
$basePath = rtrim((string) (parse_url((string) ($app['app']['url'] ?? ''), PHP_URL_PATH) ?? ''), '/');
$filters = $userFilters ?? ['keyword' => '', 'faculty' => '', 'study_program' => ''];
$filterOptions = $filterOptions ?? ['faculties' => [], 'study_programs' => []];
$totalUsers = (int) ($totalUsers ?? 0);
$activeFilterCount = (int) ($activeFilterCount ?? 0);
$recentExports = $recentExports ?? [];
$roleLabelMap = [
    'admin' => 'Admin',
    'admin_lppm' => 'Admin',
    'kepala_lppm' => 'Kepala LPPM',
    'dosen' => 'Dosen',
];
?>

<div class="page-content myletters-page compact-list">
    <div class="mb-4">
        <h2 class="admin-page-title mb-1">Ekspor Pengguna</h2>
        <p class="admin-page-subtitle mb-0">Halaman ini disiapkan untuk ekspor data pengguna dosen dari sistem SAPA LPPM.</p>
    </div>

    <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success"><?= htmlspecialchars((string) $successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars((string) $errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="row g-3 mb-4 myletters-stats">
        <div class="col-md-6 col-xl-4">
            <div class="card dashboard-card stat-card myletters-stat-card">
                <div class="card-body">
                    <div class="stat-label">Data Siap Diekspor</div>
                    <div class="stat-value"><?= $totalUsers; ?></div>
                    <div class="stat-icon"><i class="bi bi-download"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-4">
            <div class="card dashboard-card stat-card myletters-stat-card">
                <div class="card-body">
                    <div class="stat-label">Filter Aktif</div>
                    <div class="stat-value"><?= $activeFilterCount; ?></div>
                    <div class="stat-icon"><i class="bi bi-funnel"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-4">
            <div class="card dashboard-card stat-card myletters-stat-card">
                <div class="card-body">
                    <div class="stat-label">Format Ekspor</div>
                    <div class="stat-value">XLSX</div>
                    <div class="stat-icon"><i class="bi bi-file-earmark-excel"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card dashboard-card mb-3 myletters-filter-card">
        <div class="card-body">
            <form method="get" action="<?= htmlspecialchars($basePath . '/pengguna/ekspor', ENT_QUOTES, 'UTF-8'); ?>" class="myletters-filter-form">
                <div class="myletters-filter-item">
                    <label class="form-label">Fakultas</label>
                    <select name="faculty" class="form-select">
                        <option value="">Semua Fakultas</option>
                        <?php foreach (($filterOptions['faculties'] ?? []) as $faculty): ?>
                            <option value="<?= htmlspecialchars((string) $faculty, ENT_QUOTES, 'UTF-8'); ?>" <?= (string) ($filters['faculty'] ?? '') === (string) $faculty ? 'selected' : ''; ?>>
                                <?= htmlspecialchars((string) $faculty, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="myletters-filter-item">
                    <label class="form-label">Program Studi</label>
                    <select name="study_program" class="form-select">
                        <option value="">Semua Program Studi</option>
                        <?php foreach (($filterOptions['study_programs'] ?? []) as $studyProgram): ?>
                            <option value="<?= htmlspecialchars((string) $studyProgram, ENT_QUOTES, 'UTF-8'); ?>" <?= (string) ($filters['study_program'] ?? '') === (string) $studyProgram ? 'selected' : ''; ?>>
                                <?= htmlspecialchars((string) $studyProgram, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="myletters-filter-item">
                    <label class="form-label">Cari</label>
                    <input
                        type="text"
                        name="keyword"
                        class="form-control"
                        placeholder="Nama, email, username, atau NUPTK"
                        value="<?= htmlspecialchars((string) ($filters['keyword'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                    >
                </div>
                <div class="myletters-filter-btn-item">
                    <label class="form-label form-label-ghost">Aksi</label>
                    <button type="submit" class="btn btn-primary-main myletters-btn">Terapkan</button>
                </div>
                <div class="myletters-filter-btn-item">
                    <label class="form-label form-label-ghost">Aksi</label>
                    <a href="<?= htmlspecialchars($basePath . '/pengguna/ekspor', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-light-soft myletters-btn">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card dashboard-card mb-4">
        <div class="card-body">
            <h5 class="mb-3" style="color:#123c6b;">Ekspor Data Pengguna</h5>
            <p class="text-muted mb-3">Unduh data pengguna dosen berdasarkan filter yang sedang dipilih.</p>
            <div class="d-flex gap-2 flex-wrap">
                <form method="post" action="<?= htmlspecialchars($basePath . '/pengguna/ekspor/unduh', ENT_QUOTES, 'UTF-8'); ?>" class="m-0">
                    <?= csrfField(); ?>
                    <input type="hidden" name="keyword" value="<?= htmlspecialchars((string) ($filters['keyword'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="faculty" value="<?= htmlspecialchars((string) ($filters['faculty'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="study_program" value="<?= htmlspecialchars((string) ($filters['study_program'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="submit" class="btn btn-primary-main">Ekspor Excel</button>
                </form>
                <form method="post" action="<?= htmlspecialchars($basePath . '/pengguna/ekspor/pdf', ENT_QUOTES, 'UTF-8'); ?>" class="m-0">
                    <?= csrfField(); ?>
                    <input type="hidden" name="keyword" value="<?= htmlspecialchars((string) ($filters['keyword'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="faculty" value="<?= htmlspecialchars((string) ($filters['faculty'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="study_program" value="<?= htmlspecialchars((string) ($filters['study_program'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="submit" class="btn btn-light-soft">Ekspor PDF</button>
                </form>
            </div>
        </div>
    </div>

    <div class="card dashboard-card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-3">
                <h6 class="mb-0" style="color:#123c6b;">Histori Ekspor Terbaru</h6>
                <span class="text-muted" style="font-size:13px;">Tercatat otomatis saat admin mengunduh file Excel atau PDF.</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width:70px;">No.</th>
                            <th>Waktu</th>
                            <th>Admin</th>
                            <th>Role</th>
                            <th>Aktivitas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recentExports === []): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">Belum ada histori ekspor pengguna.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentExports as $index => $log): ?>
                                <?php $roleKey = strtolower(trim((string) ($log['user_role'] ?? ''))); ?>
                                <tr>
                                    <td><?= $index + 1; ?></td>
                                    <td><?= htmlspecialchars((string) date('d M Y H:i', strtotime((string) ($log['created_at'] ?? 'now'))), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars((string) (($log['user_name'] ?? '') !== '' ? $log['user_name'] : 'Sistem'), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars((string) ($roleLabelMap[$roleKey] ?? strtoupper($roleKey !== '' ? $roleKey : '-')), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars((string) ($log['action'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
