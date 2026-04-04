<?php
$rows = $myLetters ?? [];
$stat = $stats ?? ['total' => 0, 'draft' => 0, 'pending' => 0, 'revision' => 0, 'done' => 0, 'rejected' => 0];
$flt = $filters ?? [];
$typeOptions = $letterTypes ?? [];
$app = require __DIR__ . '/../../../config.php';
$basePath = rtrim((string) (parse_url((string) ($app['app']['url'] ?? ''), PHP_URL_PATH) ?? ''), '/');
$statusBadge = [
    'draft' => 'secondary',
    'diajukan' => 'primary',
    'submitted' => 'primary',
    'diverifikasi' => 'info',
    'perlu_diperbaiki' => 'warning',
    'perlu diperbaiki' => 'warning',
    'menunggu_finalisasi' => 'success',
    'disetujui' => 'success',
    'approved' => 'success',
    'surat_terbit' => 'success',
    'surat terbit' => 'success',
    'terbit' => 'success',
    'ditolak' => 'warning',
    'rejected' => 'warning',
    'selesai' => 'dark',
];
$statusLabel = [
    'draft' => 'Draft',
    'diajukan' => 'Menunggu Diproses',
    'submitted' => 'Menunggu Diproses',
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

$normalizeStatusKey = static function (string $rawStatus): string {
    $key = strtolower(trim($rawStatus));
    $key = str_replace('-', '_', $key);
    $key = preg_replace('/\s+/', '_', $key) ?? '';
    $key = trim($key, '_');
    return $key;
};
?>

<div class="page-content myletters-page compact-list">

<div class="mb-4 myletters-header">
    <h1 class="page-title mb-1">Surat Saya</h1>
    <p class="page-subtitle mb-0">Daftar seluruh pengajuan surat yang telah Anda buat pada sistem SAPA LPPM.</p>
</div>

<?php if (!empty($successMessage)): ?>
    <div class="alert alert-success"><?= htmlspecialchars((string) $successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>
<?php if (!empty($errorMessage)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars((string) $errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<div class="row g-3 mb-4 myletters-stats">
    <div class="col-md-6 col-xl-3">
        <div class="card dashboard-card stat-card myletters-stat-card myletters-stat-total">
            <div class="card-body">
                <div class="stat-label">Total Surat</div>
                <div class="stat-value"><?= (int) $stat['total']; ?></div>
                <div class="stat-icon"><i class="bi bi-files"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card dashboard-card stat-card myletters-stat-card myletters-stat-waiting">
            <div class="card-body">
                <div class="stat-label">Menunggu Diproses</div>
                <div class="stat-value"><?= (int) $stat['pending']; ?></div>
                <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card dashboard-card stat-card myletters-stat-card myletters-stat-revision">
            <div class="card-body">
                <div class="stat-label">Perlu Diperbaiki</div>
                <div class="stat-value"><?= (int) ($stat['revision'] ?? 0); ?></div>
                <div class="stat-icon"><i class="bi bi-pencil-square"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card dashboard-card stat-card myletters-stat-card myletters-stat-issued">
            <div class="card-body">
                <div class="stat-label">Surat Terbit</div>
                <div class="stat-value"><?= (int) ($stat['done'] ?? 0); ?></div>
                <div class="stat-icon"><i class="bi bi-patch-check"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="card dashboard-card mb-3 myletters-filter-card">
    <div class="card-body">
        <form method="get" action="<?= htmlspecialchars($basePath . '/surat-saya', ENT_QUOTES, 'UTF-8'); ?>" class="myletters-filter-form">
            <div class="myletters-filter-item">
                <label class="form-label">Jenis Surat</label>
                <select name="letter_type_id" class="form-select">
                    <option value="">Semua Jenis</option>
                    <?php foreach ($typeOptions as $item): ?>
                        <option value="<?= (int) $item['id']; ?>" <?= (int) ($flt['letter_type_id'] ?? 0) === (int) $item['id'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars((string) $item['name'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="myletters-filter-item">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Semua Status</option>
                    <?php
                    $statusFilterOptions = [
                        'draft' => 'Draft',
                        'diajukan' => 'Menunggu Diproses',
                        'perlu_diperbaiki' => 'Perlu Diperbaiki',
                        'disetujui' => 'Disetujui',
                        'surat_terbit' => 'Surat Terbit',
                    ];
                    foreach ($statusFilterOptions as $st => $stLabel):
                    ?>
                        <option value="<?= htmlspecialchars($st, ENT_QUOTES, 'UTF-8'); ?>" <?= strtolower((string) ($flt['status'] ?? '')) === $st ? 'selected' : ''; ?>><?= htmlspecialchars($stLabel, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="myletters-filter-item">
                <label class="form-label">Tanggal Dari</label>
                <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars((string) ($flt['date_from'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Pilih tanggal awal">
            </div>
            <div class="myletters-filter-item">
                <label class="form-label">Tanggal Sampai</label>
                <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars((string) ($flt['date_to'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Pilih tanggal akhir">
            </div>
            <div class="myletters-filter-btn-item">
                <label class="form-label form-label-ghost">Aksi</label>
                <button type="submit" class="btn btn-primary-main myletters-btn">Terapkan</button>
            </div>
            <div class="myletters-filter-btn-item">
                <label class="form-label form-label-ghost">Aksi</label>
                <a href="<?= htmlspecialchars($basePath . '/surat-saya', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-light-soft myletters-btn">Reset Filter</a>
            </div>
        </form>
    </div>
</div>

<?php if (empty($rows)): ?>
    <div class="card dashboard-card myletters-empty">
        <div class="card-body text-center py-5">
            <div class="mb-2"><i class="bi bi-file-earmark-text fs-1 text-muted"></i></div>
            <h6 class="mb-1">Belum ada pengajuan surat.</h6>
            <p class="text-muted mb-3">Silakan ajukan surat pertama Anda.</p>
        </div>
    </div>
<?php else: ?>
    <div class="card dashboard-card mt-3 letters-table-card myletters-table-card">
        <div class="card-header bg-white border-0 pt-3 px-3">
            <h6 class="mb-0"><i class="bi bi-table me-2"></i>Daftar Surat Saya</h6>
        </div>
        <div class="card-body pt-2">
            <div class="activity-table-wrap myletters-table-wrap table-responsive">
                <table id="myLettersTable" data-custom-pagination="10" class="table table-hover align-middle mb-0 w-100">
                    <thead>
                    <tr>
                        <th>No</th>
                        <th>Judul</th>
                        <th>Jenis Surat</th>
                        <th>Tanggal Surat</th>
                        <th>Instansi Tujuan</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $index => $row): ?>
                        <?php $statusRaw = (string) ($row['status'] ?? ''); ?>
                        <?php $statusKey = $normalizeStatusKey($statusRaw); ?>
                        <?php if ($statusKey === '') { $statusKey = 'perlu_diperbaiki'; } ?>
                        <?php $statusMapKey = in_array($statusKey, ['perlu_diperbaiki', 'perlu_perbaikan', 'revision', 'revisi', 'ditolak', 'rejected'], true) ? 'perlu_diperbaiki' : $statusKey; ?>
                        <?php $isApprovedForPreview = in_array($statusMapKey, ['approved', 'disetujui', 'menunggu_finalisasi', 'surat_terbit', 'terbit', 'selesai'], true); ?>
                        <?php $canEditSubmission = in_array($statusMapKey, ['draft', 'perlu_diperbaiki', 'ditolak', 'rejected'], true); ?>
                        <tr class="<?= $statusMapKey === 'draft' ? 'row-draft' : ''; ?>">
                            <td><?= $index + 1; ?></td>
                            <td><?= htmlspecialchars((string) ($row['activity_title'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?= htmlspecialchars($resolveJenisSurat($row), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?= htmlspecialchars((string) date('d M Y', strtotime((string) ($row['letter_date'] ?? 'now'))), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?= htmlspecialchars((string) ($row['institution'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <span class="status-pill status-table-pill myletters-status-pill <?= htmlspecialchars((string) ($statusPillClass[$statusMapKey] ?? 'myletters-status-ready'), ENT_QUOTES, 'UTF-8'); ?>">
                                    <i class="bi <?= htmlspecialchars((string) ($statusIcon[$statusMapKey] ?? 'bi-send-fill'), ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                                    <?= htmlspecialchars((string) ($statusLabel[$statusMapKey] ?? ($statusRaw !== '' ? $statusRaw : 'Draft')), ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td>
                                <div class="activity-action-wrap myletters-actions">
                                <?php
                                $detailPath = $basePath . '/surat-saya/' . (int) ($row['id'] ?? 0);
                                $editPath = $detailPath . (str_contains($detailPath, '?') ? '&' : '?') . 'edit=1';
                                ?>
                                <a href="<?= htmlspecialchars($detailPath, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-outline-soft activity-btn" data-bs-toggle="tooltip" title="Lihat detail">Detail</a>
                                <?php if ($isApprovedForPreview): ?>
                                    <a href="<?= htmlspecialchars($basePath . '/letters/' . (int) $row['id'] . '/preview', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-outline-soft activity-btn" target="_blank" data-bs-toggle="tooltip" title="Preview surat">Preview</a>
                                <?php else: ?>
                                    <button type="button" class="btn btn-sm btn-light-soft activity-btn" disabled>Preview</button>
                                <?php endif; ?>
                                <?php if (empty($row['_is_member_readonly']) && $canEditSubmission): ?>
                                <a href="<?= htmlspecialchars($editPath, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-light-soft activity-btn" data-bs-toggle="tooltip" title="Edit pengajuan">Edit</a>
                                <?php endif; ?>
                                <?php $isIssuedForDownload = in_array($statusMapKey, ['surat_terbit', 'surat terbit', 'terbit', 'selesai'], true); ?>
                                <?php if (!empty($row['file_pdf']) && $isIssuedForDownload): ?>
                                    <a href="<?= htmlspecialchars($basePath . '/persuratan/' . (int) $row['id'] . '/download', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-primary-main activity-btn" data-bs-toggle="tooltip" title="Download PDF">Unduh</a>
                                <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.bootstrap) {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
            new bootstrap.Tooltip(el);
        });
    }
});
</script>
