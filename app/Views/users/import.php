<?php
$app = require __DIR__ . '/../../../config.php';
$basePath = rtrim((string) (parse_url((string) ($app['app']['url'] ?? ''), PHP_URL_PATH) ?? ''), '/');
$totalUsers = (int) ($totalUsers ?? 0);
$templateFormat = (string) ($templateFormat ?? 'XLSX');
$recentImports = $recentImports ?? [];
$importPreview = is_array($importPreview ?? null) ? $importPreview : null;
$roleLabelMap = [
    'admin' => 'Admin',
    'admin_lppm' => 'Admin',
    'kepala_lppm' => 'Kepala LPPM',
    'dosen' => 'Dosen',
];
?>

<div class="page-content myletters-page compact-list">
    <div class="mb-4">
        <h2 class="admin-page-title mb-1">Impor Pengguna</h2>
        <p class="admin-page-subtitle mb-0">Halaman ini disiapkan untuk impor data pengguna dosen secara massal ke sistem SAPA LPPM.</p>
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
                    <div class="stat-label">Data Pengguna Saat Ini</div>
                    <div class="stat-value"><?= $totalUsers; ?></div>
                    <div class="stat-icon"><i class="bi bi-people"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-4">
            <div class="card dashboard-card stat-card myletters-stat-card">
                <div class="card-body">
                    <div class="stat-label">Format Template</div>
                    <div class="stat-value"><?= htmlspecialchars($templateFormat, ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="stat-icon"><i class="bi bi-file-earmark-excel"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-4">
            <div class="card dashboard-card stat-card myletters-stat-card">
                <div class="card-body">
                    <div class="stat-label">Tahap Saat Ini</div>
                    <div class="stat-value" style="font-size:28px;"><?= $importPreview !== null ? 'Review Preview' : 'Unggah File'; ?></div>
                    <div class="stat-icon"><i class="bi <?= $importPreview !== null ? 'bi-list-check' : 'bi-cloud-upload'; ?>"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card dashboard-card mb-4">
        <div class="card-body">
            <h5 class="mb-3" style="color:#123c6b;">Impor Data Pengguna</h5>
            <p class="text-muted mb-3">Unduh template Excel, unggah file Excel pengguna, lalu periksa preview validasi sebelum data disimpan.</p>
            <div class="d-flex gap-2 flex-wrap mb-3">
                <a href="<?= htmlspecialchars($basePath . '/pengguna/impor/template', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary-main">Unduh Template Excel</a>
            </div>
            <form method="post" action="<?= htmlspecialchars($basePath . '/pengguna/impor/preview', ENT_QUOTES, 'UTF-8'); ?>" enctype="multipart/form-data" class="row g-3 align-items-end">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="col-lg-8">
                    <label class="form-label">File Excel Pengguna</label>
                    <input type="file" name="import_file" class="form-control" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
                    <div class="form-text">Gunakan template Excel resmi agar format data sesuai dan referensi fakultas serta program studi tetap tersedia.</div>
                </div>
                <div class="col-lg-4">
                    <button type="submit" class="btn btn-light-soft w-100">Preview Import</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($importPreview !== null): ?>
        <div class="row g-3 mb-4 myletters-stats">
            <div class="col-md-6 col-xl-4">
                <div class="card dashboard-card stat-card myletters-stat-card">
                    <div class="card-body">
                        <div class="stat-label">Baris Dibaca</div>
                        <div class="stat-value"><?= (int) ($importPreview['total_rows'] ?? 0); ?></div>
                        <div class="stat-icon"><i class="bi bi-list-check"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="card dashboard-card stat-card myletters-stat-card">
                    <div class="card-body">
                        <div class="stat-label">Data Valid</div>
                        <div class="stat-value"><?= (int) ($importPreview['valid_count'] ?? 0); ?></div>
                        <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="card dashboard-card stat-card myletters-stat-card">
                    <div class="card-body">
                        <div class="stat-label">Perlu Perbaikan</div>
                        <div class="stat-value"><?= (int) ($importPreview['invalid_count'] ?? 0); ?></div>
                        <div class="stat-icon"><i class="bi bi-exclamation-circle"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card dashboard-card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-3">
                    <div>
                        <h5 class="mb-1" style="color:#123c6b;">Preview Validasi Impor</h5>
                        <div class="text-muted" style="font-size:13px;">
                            File: <?= htmlspecialchars((string) ($importPreview['file_name'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    </div>
                    <form method="post" action="<?= htmlspecialchars($basePath . '/pengguna/impor/simpan', ENT_QUOTES, 'UTF-8'); ?>" class="m-0">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="submit" class="btn btn-primary-main" <?= (int) ($importPreview['valid_count'] ?? 0) <= 0 ? 'disabled' : ''; ?>>Simpan Pengguna Valid</button>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width:70px;">Baris</th>
                                <th>Nama</th>
                                <th>NUPTK</th>
                                <th>Email</th>
                                <th>Username</th>
                                <th>Fakultas</th>
                                <th>Program Studi</th>
                                <th style="width:140px;">Status</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (($importPreview['rows'] ?? []) as $row): ?>
                                <?php $isValid = (string) ($row['status'] ?? '') === 'Valid'; ?>
                                <tr>
                                    <td><?= (int) ($row['row_number'] ?? 0); ?></td>
                                    <td><?= htmlspecialchars((string) ($row['name'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars((string) ($row['nuptk'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars((string) ($row['email'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars((string) ($row['username'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars((string) ($row['faculty'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars((string) ($row['study_program'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <span class="badge rounded-pill <?= $isValid ? 'bg-success-subtle text-success-emphasis' : 'bg-danger-subtle text-danger-emphasis'; ?>">
                                            <?= htmlspecialchars((string) ($row['status'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['errors']) && is_array($row['errors'])): ?>
                                            <?= htmlspecialchars(implode('; ', array_map('strval', $row['errors'])), ENT_QUOTES, 'UTF-8'); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Siap diimpor</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="card dashboard-card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-3">
                <h6 class="mb-0" style="color:#123c6b;">Histori Impor Terbaru</h6>
                <span class="text-muted" style="font-size:13px;">Histori akan tercatat otomatis setelah proses impor diaktifkan.</span>
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
                        <?php if ($recentImports === []): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">Belum ada histori impor pengguna.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentImports as $index => $log): ?>
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
