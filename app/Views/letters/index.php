<?php
$currentRole = normalizeRoleName((string) authRole());
$isKepalaView = (bool) ($isKepalaView ?? false);
$app = require __DIR__ . '/../../../config.php';
$basePath = rtrim((string) (parse_url((string) ($app['app']['url'] ?? ''), PHP_URL_PATH) ?? ''), '/');
?>

<?php if ($isKepalaView && $currentRole === 'kepala_lppm'): ?>
<?php
$rows = $letters ?? [];
$summaryData = $summary ?? ['total' => 0, 'kontrak' => 0, 'izin' => 0, 'tugas' => 0];
$filters = $headFilters ?? ['jenis' => '', 'tahun' => '', 'keyword' => ''];
$yearOptions = $yearOptions ?? [];
$statusLabel = [
    'diajukan' => 'Menunggu Diproses',
    'submitted' => 'Menunggu Diproses',
    'diverifikasi' => 'Menunggu Diproses',
    'menunggu diproses' => 'Menunggu Diproses',
    'perlu_diperbaiki' => 'Perlu Diperbaiki',
    'perlu diperbaiki' => 'Perlu Diperbaiki',
    'rejected' => 'Perlu Diperbaiki',
    'ditolak' => 'Perlu Diperbaiki',
    'menunggu_finalisasi' => 'Disetujui',
    'approved' => 'Disetujui',
    'disetujui' => 'Disetujui',
    'surat_terbit' => 'Surat Terbit',
    'surat terbit' => 'Surat Terbit',
    'terbit' => 'Surat Terbit',
];
$statusPillClass = [
    'diajukan' => 'myletters-status-waiting',
    'submitted' => 'myletters-status-waiting',
    'diverifikasi' => 'myletters-status-waiting',
    'menunggu diproses' => 'myletters-status-waiting',
    'perlu_diperbaiki' => 'myletters-status-revision',
    'perlu diperbaiki' => 'myletters-status-revision',
    'rejected' => 'myletters-status-revision',
    'ditolak' => 'myletters-status-revision',
    'menunggu_finalisasi' => 'myletters-status-approved',
    'approved' => 'myletters-status-approved',
    'disetujui' => 'myletters-status-approved',
    'surat_terbit' => 'myletters-status-issued',
    'surat terbit' => 'myletters-status-issued',
    'terbit' => 'myletters-status-issued',
];
$statusIcon = [
    'diajukan' => 'bi-clock',
    'submitted' => 'bi-clock',
    'diverifikasi' => 'bi-clock',
    'menunggu diproses' => 'bi-clock',
    'perlu_diperbaiki' => 'bi-pencil-square',
    'perlu diperbaiki' => 'bi-pencil-square',
    'rejected' => 'bi-pencil-square',
    'ditolak' => 'bi-pencil-square',
    'menunggu_finalisasi' => 'bi-check2-circle',
    'approved' => 'bi-check2-circle',
    'disetujui' => 'bi-check2-circle',
    'surat_terbit' => 'bi-patch-check',
    'surat terbit' => 'bi-patch-check',
    'terbit' => 'bi-patch-check',
];
$resolveJenisSurat = static function (string $subject, string $slug = ''): string {
    $slug = strtolower(trim($slug));
    if ($slug !== '') {
        $kind = str_contains($slug, 'kontrak') ? 'Surat Kontrak' : (str_contains($slug, 'tugas') ? 'Surat Tugas' : 'Surat Izin');
        if (str_ends_with($slug, '_hilirisasi')) {
            return $kind . ' Hilirisasi';
        }
        if (str_ends_with($slug, '_pengabdian')) {
            return $kind . ' Pengabdian';
        }
        if (str_ends_with($slug, '_penelitian')) {
            return $kind . ' Penelitian';
        }
    }

    $value = strtolower(trim($subject));
    $prefix = str_contains($value, 'kontrak') ? 'Surat Kontrak' : (str_contains($value, 'tugas') ? 'Surat Tugas' : 'Surat Izin');
    if (str_contains($value, 'hilirisasi')) {
        return $prefix . ' Hilirisasi';
    }
    if (str_contains($value, 'pengabdian')) {
        return $prefix . ' Pengabdian';
    }

    return $prefix . ' Penelitian';
};
?>
<div class="page-content myletters-page compact-list">
    <div class="mb-4">
        <h2 class="admin-page-title mb-1">Persuratan Kepala LPPM</h2>
        <p class="admin-page-subtitle mb-0">Daftar pengajuan surat dosen untuk kegiatan penelitian, pengabdian, dan hilirisasi.</p>
    </div>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars((string) $errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="row g-3 mb-4 myletters-stats">
        <div class="col-md-6 col-xl-3"><div class="card dashboard-card stat-card myletters-stat-card"><div class="card-body"><div class="stat-label">Total Pengajuan</div><div class="stat-value"><?= (int) $summaryData['total']; ?></div><div class="stat-icon"><i class="bi bi-files"></i></div></div></div></div>
        <div class="col-md-6 col-xl-3"><div class="card dashboard-card stat-card myletters-stat-card"><div class="card-body"><div class="stat-label">Surat Kontrak</div><div class="stat-value"><?= (int) $summaryData['kontrak']; ?></div><div class="stat-icon"><i class="bi bi-file-earmark-richtext"></i></div></div></div></div>
        <div class="col-md-6 col-xl-3"><div class="card dashboard-card stat-card myletters-stat-card"><div class="card-body"><div class="stat-label">Surat Izin</div><div class="stat-value"><?= (int) $summaryData['izin']; ?></div><div class="stat-icon"><i class="bi bi-file-earmark-text"></i></div></div></div></div>
        <div class="col-md-6 col-xl-3"><div class="card dashboard-card stat-card myletters-stat-card"><div class="card-body"><div class="stat-label">Surat Tugas</div><div class="stat-value"><?= (int) $summaryData['tugas']; ?></div><div class="stat-icon"><i class="bi bi-file-earmark-check"></i></div></div></div></div>
    </div>

    <div class="card dashboard-card mb-3 myletters-filter-card">
        <div class="card-body">
            <form method="get" action="<?= htmlspecialchars($basePath . '/index.php', ENT_QUOTES, 'UTF-8'); ?>" class="myletters-filter-form">
                <input type="hidden" name="route" value="letters">
                <div class="myletters-filter-item">
                    <label class="form-label">Jenis Surat</label>
                    <select name="jenis" class="form-select">
                        <option value="">Semua Jenis</option>
                        <option value="kontrak" <?= (string) ($filters['jenis'] ?? '') === 'kontrak' ? 'selected' : ''; ?>>Surat Kontrak</option>
                        <option value="izin" <?= (string) ($filters['jenis'] ?? '') === 'izin' ? 'selected' : ''; ?>>Surat Izin</option>
                        <option value="tugas" <?= (string) ($filters['jenis'] ?? '') === 'tugas' ? 'selected' : ''; ?>>Surat Tugas</option>
                    </select>
                </div>
                <div class="myletters-filter-item">
                    <label class="form-label">Tahun</label>
                    <select name="tahun" class="form-select">
                        <option value="">Semua Tahun</option>
                        <?php foreach ($yearOptions as $year): ?>
                            <option value="<?= htmlspecialchars((string) $year, ENT_QUOTES, 'UTF-8'); ?>" <?= (string) ($filters['tahun'] ?? '') === (string) $year ? 'selected' : ''; ?>>
                                <?= htmlspecialchars((string) $year, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="myletters-filter-item">
                    <label class="form-label">Cari</label>
                    <input type="text" name="keyword" class="form-control" placeholder="Judul, ketua, atau skema" value="<?= htmlspecialchars((string) ($filters['keyword'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="myletters-filter-btn-item">
                    <label class="form-label form-label-ghost">Aksi</label>
                    <button type="submit" class="btn btn-primary-main myletters-btn">Terapkan</button>
                </div>
                <div class="myletters-filter-btn-item">
                    <label class="form-label form-label-ghost">Aksi</label>
                    <a href="<?= htmlspecialchars($basePath . '/?route=letters', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-light-soft myletters-btn">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card dashboard-card mt-3 letters-table-card myletters-table-card">
        <div class="card-header bg-white border-0 pt-3 px-3">
            <h6 class="mb-0"><i class="bi bi-table me-2"></i>Daftar Pengajuan Surat Dosen</h6>
        </div>
        <div class="card-body pt-2">
            <div class="activity-table-wrap myletters-table-wrap table-responsive">
                <table id="headLettersTable" data-custom-pagination="5" class="table table-hover align-middle mb-0 w-100">
                    <thead>
                    <tr>
                        <th>No</th>
                        <th>Jenis Surat</th>
                        <th>Judul</th>
                        <th>Nama Ketua</th>
                        <th>Skema</th>
                        <th>Ruang Lingkup</th>
                        <th>Tahun</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-3">Belum ada pengajuan surat dosen.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $index => $row): ?>
                            <?php
                            $status = strtolower(trim((string) ($row['status'] ?? '')));
                            $canIssue = in_array($status, ['disetujui', 'approved', 'menunggu_finalisasi', 'surat_terbit', 'surat terbit', 'terbit', 'selesai'], true);
                            $badgeClass = $statusPillClass[$status] ?? 'myletters-status-waiting';
                            $badgeLabel = $statusLabel[$status] ?? 'Menunggu Diproses';
                            $badgeIcon = $statusIcon[$status] ?? 'bi-clock';
                            ?>
                            <tr>
                                <td><?= (int) $index + 1; ?></td>
                                <td><?= htmlspecialchars($resolveJenisSurat((string) ($row['subject'] ?? ''), (string) ($row['letter_type_slug'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars((string) ($row['judul'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars((string) ($row['nama_ketua'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars((string) ($row['skema'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars((string) ($row['ruang_lingkup'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars((string) ($row['tahun'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <span class="status-pill status-table-pill myletters-status-pill <?= htmlspecialchars($badgeClass, ENT_QUOTES, 'UTF-8'); ?>">
                                        <i class="bi <?= htmlspecialchars($badgeIcon, ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                                        <?= htmlspecialchars($badgeLabel, ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="admin-action-group">
                                        <a href="<?= htmlspecialchars($basePath . '/persuratan/' . (int) ($row['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>" class="admin-action-btn" data-bs-toggle="tooltip" title="Lihat detail surat">Detail</a>
                                        <?php if ($canIssue): ?>
                                            <form method="post" action="<?= htmlspecialchars($basePath . '/persuratan/' . (int) ($row['id'] ?? 0) . '/generate-pdf', ENT_QUOTES, 'UTF-8'); ?>" class="d-inline m-0">
                                                <input type="hidden" name="id" value="<?= (int) ($row['id'] ?? 0); ?>">
                                                <button type="submit" class="admin-action-btn admin-action-btn-primary" data-bs-toggle="tooltip" title="Terbitkan surat (generate PDF)">Terbitkan Surat</button>
                                            </form>
                                        <?php else: ?>
                                            <button type="button" class="admin-action-btn admin-action-btn-disabled" disabled data-bs-toggle="tooltip" title="Aktif setelah status Disetujui">Terbitkan Surat</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<?php
$currentTypeParam = strtolower(trim((string) ($_GET['type'] ?? '')));
$typeQuerySuffix = $currentTypeParam !== '' ? '&type=' . urlencode($currentTypeParam) : '';
$selectedTypeLabel = (string) ($selectedTypeLabel ?? 'Semua Jenis Surat');
$statusBadge = [
    'draft' => 'secondary',
    'submitted' => 'primary',
    'diajukan' => 'primary',
    'perlu_diperbaiki' => 'warning',
    'menunggu_finalisasi' => 'success',
    'approved' => 'success',
    'disetujui' => 'success',
    'surat_terbit' => 'success',
    'surat terbit' => 'success',
    'terbit' => 'success',
    'rejected' => 'warning',
    'ditolak' => 'warning',
];
$isAdmin = isAdminPanelRole($currentRole);
?>
<div class="letters-list-page compact-list">
    <div class="card dashboard-card letters-list-head">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="card-title mb-1">Modul Persuratan</h5>
                    <p class="mb-0 text-muted">Filter aktif: <?= htmlspecialchars($selectedTypeLabel, ENT_QUOTES, 'UTF-8'); ?>.</p>
                </div>
                <div class="d-flex align-items-end gap-2 flex-wrap">
                    <?php if ($isAdmin): ?>
                        <form method="get" action="<?= htmlspecialchars($basePath . '/index.php', ENT_QUOTES, 'UTF-8'); ?>" class="d-flex align-items-end gap-2">
                            <input type="hidden" name="route" value="letters">
                            <div>
                                <label class="form-label mb-1">Jenis Surat</label>
                                <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="" <?= $currentTypeParam === '' ? 'selected' : ''; ?>>Semua</option>
                                    <option value="izin" <?= $currentTypeParam === 'izin' ? 'selected' : ''; ?>>Surat Izin</option>
                                    <option value="tugas" <?= $currentTypeParam === 'tugas' ? 'selected' : ''; ?>>Surat Tugas</option>
                                    <option value="pengantar" <?= $currentTypeParam === 'pengantar' ? 'selected' : ''; ?>>Surat Pengantar</option>
                                </select>
                            </div>
                        </form>
                    <?php endif; ?>
                    <a href="<?= htmlspecialchars($basePath . '/ajukan-surat/penelitian?surat_kind=izin', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i> Buat Surat Baru
                    </a>
                </div>
            </div>

            <?php if (!empty($createdNumber)): ?>
                <div class="alert alert-success mt-3 mb-0">
                    Surat berhasil dibuat dengan nomor:
                    <strong><?= htmlspecialchars((string) $createdNumber, ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>
            <?php endif; ?>

            <?php if (!empty($successMessage)): ?>
                <div class="alert alert-success mt-3 mb-0">
                    <?= htmlspecialchars((string) $successMessage, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger mt-3 mb-0">
                    Gagal membuat surat: <?= htmlspecialchars((string) $errorMessage, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card dashboard-card mt-3 letters-table-card">
        <div class="card-header bg-white border-0 pt-3 px-3">
            <h6 class="mb-0"><i class="bi bi-table me-2"></i>Daftar Surat</h6>
        </div>
        <div class="card-body pt-2">
            <div class="table-responsive">
                <table id="lettersTable" data-custom-pagination="10" class="table table-hover align-middle mb-0 w-100">
                    <thead>
                    <tr>
                        <th>No</th>
                        <th>Nomor Surat</th>
                        <th>Jenis</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($letters)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">Belum ada data surat.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($letters as $index => $letter): ?>
                            <tr>
                                <td><?= (int) $index + 1; ?></td>
                                <td><?= htmlspecialchars((string) $letter['letter_number'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars((string) $letter['letter_type_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars((string) date('d M Y', strtotime((string) $letter['letter_date'])), ENT_QUOTES, 'UTF-8'); ?></td>
                                <?php $statusKey = strtolower((string) $letter['status']); ?>
                                <?php $badgeClass = $statusBadge[$statusKey] ?? 'secondary'; ?>
                                <td><span class="badge text-bg-<?= htmlspecialchars($badgeClass, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars((string) $letter['status'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td class="d-flex gap-1 flex-wrap letters-actions">
                                    <?php if ($isAdmin): ?>
                                        <?php if (!in_array($statusKey, ['approved', 'disetujui', 'menunggu_finalisasi', 'surat_terbit', 'surat terbit', 'terbit', 'rejected', 'ditolak'], true)): ?>
                                            <form method="post" action="<?= htmlspecialchars($basePath . '/persuratan/' . (int) $letter['id'] . '/approve', ENT_QUOTES, 'UTF-8'); ?>" class="d-inline m-0">
                                                <input type="hidden" name="type" value="<?= htmlspecialchars($currentTypeParam, ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="return" value="list">
                                                <button type="submit" class="btn btn-sm btn-outline-success" data-bs-toggle="tooltip" title="Setujui surat">Setujui</button>
                                            </form>
                                            <form method="post" action="<?= htmlspecialchars($basePath . '/persuratan/' . (int) $letter['id'] . '/reject', ENT_QUOTES, 'UTF-8'); ?>" class="d-inline m-0">
                                                <input type="hidden" name="type" value="<?= htmlspecialchars($currentTypeParam, ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="return" value="list">
                                                <button type="submit" class="btn btn-sm btn-outline-warning" data-bs-toggle="tooltip" title="Kembalikan untuk perbaikan">Perlu Diperbaiki</button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <a href="<?= htmlspecialchars($basePath . '/letters/' . (int) $letter['id'] . '/preview', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-outline-secondary" target="_blank" data-bs-toggle="tooltip" title="Preview surat">Preview</a>
                                    <a href="<?= htmlspecialchars($basePath . '/persuratan/' . (int) $letter['id'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-outline-dark" data-bs-toggle="tooltip" title="Detail surat">Detail</a>
                                    <?php if ($isAdmin): ?>
                                        <form method="post" action="<?= htmlspecialchars($basePath . '/persuratan/' . (int) $letter['id'] . '/generate-pdf', ENT_QUOTES, 'UTF-8'); ?>" class="d-inline m-0">
                                            <input type="hidden" name="id" value="<?= (int) $letter['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Generate PDF">Generate PDF</button>
                                        </form>
                                    <?php endif; ?>
                                    <a href="<?= htmlspecialchars($basePath . '/persuratan/' . (int) $letter['id'] . '/download', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Download PDF">Download PDF</a>
                                    <?php if ($isAdmin && !empty($letter['verification_token'])): ?>
                                        <a href="<?= htmlspecialchars($basePath . '/verify/' . (string) $letter['verification_token'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-outline-dark" target="_blank" data-bs-toggle="tooltip" title="Verifikasi QR">Verifikasi</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.bootstrap) {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
            new bootstrap.Tooltip(el);
        });
    }
});
</script>
