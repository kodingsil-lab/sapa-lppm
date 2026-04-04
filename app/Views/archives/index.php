<?php
$app = require __DIR__ . '/../../../config.php';
$basePath = rtrim((string) (parse_url((string) ($app['app']['url'] ?? ''), PHP_URL_PATH) ?? ''), '/');
$rows = $rows ?? [];
$summaryData = $summary ?? ['total' => 0, 'kontrak' => 0, 'izin' => 0, 'tugas' => 0];
$filters = $archiveFilters ?? ['jenis' => '', 'tahun' => '', 'keyword' => ''];
$yearOptions = $yearOptions ?? [];
$statusLabel = [
    'surat_terbit' => 'Surat Terbit',
    'surat terbit' => 'Surat Terbit',
    'terbit' => 'Surat Terbit',
    'selesai' => 'Surat Terbit',
];
$statusPillClass = [
    'surat_terbit' => 'myletters-status-issued',
    'surat terbit' => 'myletters-status-issued',
    'terbit' => 'myletters-status-issued',
    'selesai' => 'myletters-status-issued',
];
$statusIcon = [
    'surat_terbit' => 'bi-patch-check',
    'surat terbit' => 'bi-patch-check',
    'terbit' => 'bi-patch-check',
    'selesai' => 'bi-patch-check',
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
$formatDateTime = static function (?string $raw): string {
    $value = trim((string) $raw);
    if ($value === '') {
        return '-';
    }

    $ts = strtotime($value);
    if ($ts === false) {
        return $value;
    }

    return date('d/m/Y H:i', $ts);
};
?>
<style>
.admin-action-btn-danger {
    border-color: #dc3545;
    color: #dc3545;
}
.admin-action-btn-danger:hover {
    background: #dc3545;
    color: #fff;
}
</style>

<div class="page-content myletters-page archives-page compact-list">
    <div class="mb-4">
        <h2 class="admin-page-title mb-1">Arsip Surat Kepala LPPM</h2>
        <p class="admin-page-subtitle mb-0">Daftar surat dosen yang telah diterbitkan dan diarsipkan.</p>
    </div>

    <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success"><?= htmlspecialchars((string) $successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars((string) $errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="row g-3 mb-4 myletters-stats">
        <div class="col-md-6 col-xl-3"><div class="card dashboard-card stat-card myletters-stat-card archive-stat-card"><div class="card-body"><div class="stat-label">Total Arsip</div><div class="stat-value"><?= (int) $summaryData['total']; ?></div><div class="stat-icon archive-stat-icon archive-stat-icon-total"><i class="bi bi-archive-fill"></i></div></div></div></div>
        <div class="col-md-6 col-xl-3"><div class="card dashboard-card stat-card myletters-stat-card archive-stat-card"><div class="card-body"><div class="stat-label">Surat Kontrak</div><div class="stat-value"><?= (int) $summaryData['kontrak']; ?></div><div class="stat-icon archive-stat-icon archive-stat-icon-kontrak"><i class="bi bi-file-earmark-richtext"></i></div></div></div></div>
        <div class="col-md-6 col-xl-3"><div class="card dashboard-card stat-card myletters-stat-card archive-stat-card"><div class="card-body"><div class="stat-label">Surat Izin</div><div class="stat-value"><?= (int) $summaryData['izin']; ?></div><div class="stat-icon archive-stat-icon archive-stat-icon-izin"><i class="bi bi-file-earmark-text"></i></div></div></div></div>
        <div class="col-md-6 col-xl-3"><div class="card dashboard-card stat-card myletters-stat-card archive-stat-card"><div class="card-body"><div class="stat-label">Surat Tugas</div><div class="stat-value"><?= (int) $summaryData['tugas']; ?></div><div class="stat-icon archive-stat-icon archive-stat-icon-tugas"><i class="bi bi-file-earmark-check"></i></div></div></div></div>
    </div>

    <div class="card dashboard-card mb-3 myletters-filter-card">
        <div class="card-body">
            <form method="get" action="<?= htmlspecialchars($basePath . '/arsip-surat', ENT_QUOTES, 'UTF-8'); ?>" class="myletters-filter-form">
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
                    <a href="<?= htmlspecialchars($basePath . '/arsip-surat', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-light-soft myletters-btn">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card dashboard-card mt-3 letters-table-card myletters-table-card">
        <div class="card-header bg-white border-0 pt-3 px-3">
            <h6 class="mb-0"><i class="bi bi-table me-2"></i>Daftar Arsip Surat Dosen</h6>
        </div>
        <div class="card-body pt-2">
            <div class="activity-table-wrap myletters-table-wrap table-responsive">
                <table id="headArchiveTable" data-custom-pagination="5" class="table table-hover align-middle mb-0 w-100">
                    <thead>
                    <tr>
                        <th>No</th>
                        <th>Jenis Surat</th>
                        <th>Judul</th>
                        <th>Nama Ketua</th>
                        <th>Skema</th>
                        <th>Ruang Lingkup</th>
                        <th>Tahun</th>
                        <th>Waktu Pembuatan Surat</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="10" class="text-center text-muted py-3">Belum ada arsip surat terbit.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $index => $row): ?>
                            <?php
                            $status = strtolower(trim((string) ($row['status'] ?? '')));
                            $badgeClass = $statusPillClass[$status] ?? 'myletters-status-issued';
                            $badgeLabel = $statusLabel[$status] ?? 'Surat Terbit';
                            $badgeIcon = $statusIcon[$status] ?? 'bi-patch-check';
                            ?>
                            <tr>
                                <td><?= (int) $index + 1; ?></td>
                                <td><?= htmlspecialchars($resolveJenisSurat((string) ($row['subject'] ?? ''), (string) ($row['letter_type_slug'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars((string) ($row['judul'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars((string) ($row['nama_ketua'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars((string) ($row['skema'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars((string) ($row['ruang_lingkup'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars((string) ($row['tahun'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($formatDateTime((string) ($row['waktu_pembuatan_surat'] ?? $row['created_at'] ?? $row['letter_date'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <span class="status-pill status-table-pill myletters-status-pill <?= htmlspecialchars($badgeClass, ENT_QUOTES, 'UTF-8'); ?>">
                                        <i class="bi <?= htmlspecialchars($badgeIcon, ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                                        <?= htmlspecialchars($badgeLabel, ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="admin-action-group">
                                        <a href="<?= htmlspecialchars($basePath . '/letters/' . (int) ($row['id'] ?? 0) . '/preview', ENT_QUOTES, 'UTF-8'); ?>" class="admin-action-btn" target="_blank" data-bs-toggle="tooltip" title="Lihat surat">Lihat Surat</a>
                                        <button
                                            type="button"
                                            class="admin-action-btn admin-action-btn-danger js-archive-delete-btn"
                                            data-letter-id="<?= (int) ($row['id'] ?? 0); ?>"
                                            data-letter-title="<?= htmlspecialchars((string) ($row['judul'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?>"
                                            data-bs-toggle="modal"
                                            data-bs-target="#archiveDeleteModal"
                                            title="Hapus surat dari arsip"
                                        >
                                            Hapus
                                        </button>
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

<div class="modal fade" id="archiveDeleteModal" tabindex="-1" aria-labelledby="archiveDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="archiveDeleteModalLabel">Konfirmasi Hapus Surat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Surat terbit ini akan dihapus dari sistem untuk semua role.</p>
                <p class="mb-2">Dosen harus mengajukan ulang jika surat sudah dihapus.</p>
                <p class="mb-0"><strong id="archiveDeleteTargetTitle">-</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light-soft" data-bs-dismiss="modal">Batal</button>
                <form method="post" action="<?= htmlspecialchars($basePath . '/arsip-surat/hapus', ENT_QUOTES, 'UTF-8'); ?>" class="d-inline">
                    <input type="hidden" name="id" id="archiveDeleteLetterId" value="">
                    <button type="submit" class="btn btn-danger">Ya, Hapus Surat</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const deleteButtons = document.querySelectorAll('.js-archive-delete-btn');
    const idInput = document.getElementById('archiveDeleteLetterId');
    const titleEl = document.getElementById('archiveDeleteTargetTitle');

    deleteButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            const letterId = btn.getAttribute('data-letter-id') || '';
            const letterTitle = btn.getAttribute('data-letter-title') || '-';

            if (idInput) {
                idInput.value = letterId;
            }
            if (titleEl) {
                titleEl.textContent = letterTitle;
            }
        });
    });
});
</script>
