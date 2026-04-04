<?php
$app = require __DIR__ . '/../../../../config.php';
$basePath = rtrim((string) (parse_url((string) ($app['app']['url'] ?? ''), PHP_URL_PATH) ?? ''), '/');
$items = $items ?? [];
$stats = $stats ?? ['total' => 0, 'aktif' => 0, 'selesai' => 0];
$filters = $filters ?? ['year' => '', 'status' => '', 'q' => ''];
$routes = $routes ?? [];
$letterRoutes = $letterRoutes ?? [];
$activityType = (string) ($letterRoutes['activity_type'] ?? 'penelitian');
$showResearchColumns = in_array($activityType, ['penelitian', 'pengabdian', 'hilirisasi'], true);
$routeToPath = static function (string $route, ?int $id = null) use ($basePath): string {
    $route = trim($route);
    if ($route === '') {
        return $basePath !== '' ? $basePath : '/';
    }

    $map = [
        'data-penelitian' => 'data/penelitian',
        'data-penelitian-create' => 'data/penelitian/create',
        'data-penelitian-show' => $id !== null ? 'data/penelitian/' . $id : 'data/penelitian',
        'data-penelitian-edit' => $id !== null ? 'data/penelitian/' . $id . '/edit' : 'data/penelitian',
        'data-pengabdian' => 'data/pengabdian',
        'data-pengabdian-create' => 'data/pengabdian/create',
        'data-pengabdian-show' => $id !== null ? 'data/pengabdian/' . $id : 'data/pengabdian',
        'data-pengabdian-edit' => $id !== null ? 'data/pengabdian/' . $id . '/edit' : 'data/pengabdian',
        'data-hilirisasi' => 'data/hilirisasi',
        'data-hilirisasi-create' => 'data/hilirisasi/create',
        'data-hilirisasi-show' => $id !== null ? 'data/hilirisasi/' . $id : 'data/hilirisasi',
        'data-hilirisasi-edit' => $id !== null ? 'data/hilirisasi/' . $id . '/edit' : 'data/hilirisasi',
        'data-penelitian-delete' => 'data/penelitian/hapus',
        'data-pengabdian-delete' => 'data/pengabdian/hapus',
        'data-hilirisasi-delete' => 'data/hilirisasi/hapus',
    ];

    $path = $map[$route] ?? '';
    if ($path === '') {
        return $basePath . '/?route=' . rawurlencode($route) . ($id !== null ? '&id=' . $id : '');
    }

    return $basePath . '/' . ltrim($path, '/');
};

$iconMap = [
    'penelitian' => [
        'total' => ['icon' => 'bi-journal-text', 'iconClass' => 'stat-icon-blue', 'numberClass' => ''],
        'aktif' => ['icon' => 'bi-lightbulb', 'iconClass' => 'stat-icon-yellow', 'numberClass' => ''],
        'selesai' => ['icon' => 'bi-check2-circle', 'iconClass' => 'stat-icon-green', 'numberClass' => 'stat-number-green'],
    ],
    'pengabdian' => [
        'total' => ['icon' => 'bi-people', 'iconClass' => 'stat-icon-blue', 'numberClass' => ''],
        'aktif' => ['icon' => 'bi-megaphone', 'iconClass' => 'stat-icon-yellow', 'numberClass' => ''],
        'selesai' => ['icon' => 'bi-check2-circle', 'iconClass' => 'stat-icon-green', 'numberClass' => 'stat-number-green'],
    ],
    'hilirisasi' => [
        'total' => ['icon' => 'bi-cpu', 'iconClass' => 'stat-icon-blue', 'numberClass' => ''],
        'aktif' => ['icon' => 'bi-tools', 'iconClass' => 'stat-icon-yellow', 'numberClass' => ''],
        'selesai' => ['icon' => 'bi-check2-circle', 'iconClass' => 'stat-icon-green', 'numberClass' => 'stat-number-green'],
    ],
];
$icons = $iconMap[$activityType] ?? $iconMap['penelitian'];
?>

<div class="page-content activity-page">
    <div class="mb-3">
        <h1 class="page-title mb-1"><?= htmlspecialchars((string) ($pageTitle ?? 'Data Kegiatan'), ENT_QUOTES, 'UTF-8'); ?></h1>
        <p class="page-subtitle mb-0"><?= htmlspecialchars((string) ($pageSubtitle ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
    </div>

    <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success form-alert"><?= htmlspecialchars((string) $successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-warning form-alert"><?= htmlspecialchars((string) $errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="activity-card mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <h3 class="section-title mb-0">Ringkasan <?= htmlspecialchars((string) ($activityLongLabel ?? ''), ENT_QUOTES, 'UTF-8'); ?></h3>
            <a href="<?= htmlspecialchars($routeToPath((string) ($routes['create'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary-main">Tambah Data</a>
        </div>

        <div class="row g-3">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-top">
                        <span class="stat-icon <?= htmlspecialchars((string) $icons['total']['iconClass'], ENT_QUOTES, 'UTF-8'); ?>"><i class="bi <?= htmlspecialchars((string) $icons['total']['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i></span>
                        <span class="stat-number <?= htmlspecialchars((string) $icons['total']['numberClass'], ENT_QUOTES, 'UTF-8'); ?>"><?= (int) ($stats['total'] ?? 0); ?></span>
                    </div>
                    <div class="stat-title">Total Data</div>
                    <div class="stat-meta">Akumulasi data kegiatan</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-top">
                        <span class="stat-icon <?= htmlspecialchars((string) $icons['aktif']['iconClass'], ENT_QUOTES, 'UTF-8'); ?>"><i class="bi <?= htmlspecialchars((string) $icons['aktif']['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i></span>
                        <span class="stat-number <?= htmlspecialchars((string) $icons['aktif']['numberClass'], ENT_QUOTES, 'UTF-8'); ?>"><?= (int) ($stats['aktif'] ?? 0); ?></span>
                    </div>
                    <div class="stat-title">Aktif</div>
                    <div class="stat-meta">Kegiatan berjalan</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-top">
                        <span class="stat-icon <?= htmlspecialchars((string) $icons['selesai']['iconClass'], ENT_QUOTES, 'UTF-8'); ?>"><i class="bi <?= htmlspecialchars((string) $icons['selesai']['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i></span>
                        <span class="stat-number <?= htmlspecialchars((string) $icons['selesai']['numberClass'], ENT_QUOTES, 'UTF-8'); ?>"><?= (int) ($stats['selesai'] ?? 0); ?></span>
                    </div>
                    <div class="stat-title">Selesai</div>
                    <div class="stat-meta">Kegiatan selesai</div>
                </div>
            </div>
        </div>
    </div>

    <div class="activity-card mb-3">
        <form method="get" class="row g-3 align-items-end">
            <input type="hidden" name="route" value="<?= htmlspecialchars((string) ($routes['index'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <div class="col-md-2">
                <label class="form-label">Tahun</label>
                <input type="text" name="year" class="form-control modern-input" value="<?= htmlspecialchars((string) ($filters['year'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="2026">
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select modern-input">
                    <option value="">Semua Status</option>
                    <?php foreach (['aktif' => 'Aktif', 'selesai' => 'Selesai'] as $value => $label): ?>
                        <option value="<?= htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); ?>" <?= strtolower((string) ($filters['status'] ?? '')) === $value ? 'selected' : ''; ?>><?= htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label">Kata Kunci</label>
                <input type="text" name="q" class="form-control modern-input" value="<?= htmlspecialchars((string) ($filters['q'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Cari judul, ketua">
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-light-soft">Terapkan Filter</button>
            </div>
        </form>
    </div>

    <div class="activity-card">
        <div class="d-flex align-items-center mb-2">
            <h3 class="section-title mb-0">
                <i class="bi bi-table me-2"></i>Daftar <?= htmlspecialchars((string) ($activityLongLabel ?? 'Data'), ENT_QUOTES, 'UTF-8'); ?>
            </h3>
        </div>
        <?php if (empty($items)): ?>
            <div class="activity-empty-state text-center py-5">
                <h5 class="mb-2">Belum ada data <?= htmlspecialchars(strtolower((string) ($activityLongLabel ?? 'kegiatan')), ENT_QUOTES, 'UTF-8'); ?>.</h5>
                <p class="text-muted mb-3">Silakan tambahkan data terlebih dahulu untuk kebutuhan pengajuan surat.</p>
                <a href="<?= htmlspecialchars($routeToPath((string) ($routes['create'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary-main">Tambah Data</a>
            </div>
        <?php else: ?>
            <div class="activity-table-wrap table-responsive">
                <table data-custom-pagination="10" class="table table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th>No.</th>
                        <th>Judul</th>
                        <th>Peran</th>
                        <?php if ($showResearchColumns): ?>
                            <th>Skema</th>
                            <th>Ruang Lingkup</th>
                            <th>Sumber Dana</th>
                        <?php endif; ?>
                        <th>Ketua</th>
                        <th>Tahun</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $index => $row): ?>
                        <?php $id = (int) ($row['id'] ?? 0); ?>
                        <tr>
                            <td><?= (int) $index + 1; ?></td>
                            <td><div class="activity-col-title"><?= htmlspecialchars((string) ($row['judul'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div></td>
                            <td>
                                <?php if (!empty($row['_is_member_readonly'])): ?>
                                    Anggota
                                <?php else: ?>
                                    Ketua
                                <?php endif; ?>
                            </td>
                            <?php if ($showResearchColumns): ?>
                                <td><?= htmlspecialchars((string) ($row['skema'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars((string) ($row['ruang_lingkup'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars((string) ($row['sumber_dana'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                            <?php endif; ?>
                            <td><?= htmlspecialchars((string) ($row['ketua'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?= htmlspecialchars((string) ($row['tahun'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                            <?php
                                $statusRaw = strtolower(trim((string) ($row['status'] ?? 'draft')));
                                $statusClass = 'status-secondary';
                                if ($statusRaw === 'aktif') {
                                    $statusClass = 'status-activity-aktif';
                                } elseif ($statusRaw === 'selesai') {
                                    $statusClass = 'status-activity-selesai';
                                }
                            ?>
                            <td><span class="status-pill status-table-pill <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars(ucfirst((string) ($row['status'] ?? 'draft')), ENT_QUOTES, 'UTF-8'); ?></span></td>
                            <td>
                                <div class="activity-action-wrap">
                                    <a href="<?= htmlspecialchars($routeToPath((string) ($routes['show'] ?? $routes['edit'] ?? ''), $id), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-outline-soft activity-btn">Detail</a>
                                    <?php if (empty($row['_is_member_readonly'])): ?>
                                        <a href="<?= htmlspecialchars($routeToPath((string) ($routes['edit'] ?? ''), $id), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-light-soft activity-btn">Edit</a>
                                        <?php $isLinkedSubmission = !empty($row['_is_linked_submission']); ?>
                                        <?php if ($isLinkedSubmission): ?>
                                            <button type="button" class="btn btn-sm btn-light-soft activity-btn" disabled title="Data sudah dipakai dalam pengajuan surat">
                                                Terkunci
                                            </button>
                                        <?php else: ?>
                                            <form method="post" action="<?= htmlspecialchars($routeToPath((string) ($routes['delete'] ?? ''), $id), ENT_QUOTES, 'UTF-8'); ?>" class="d-inline-block js-delete-form">
                                                <input type="hidden" name="id" value="<?= (int) $id; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger-soft activity-btn">Hapus</button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="delete-confirm-backdrop d-none" id="deleteConfirmBackdrop">
    <div class="delete-confirm-modal" role="dialog" aria-modal="true" aria-labelledby="deleteConfirmTitle">
        <div class="delete-confirm-title" id="deleteConfirmTitle">Konfirmasi Hapus Data</div>
        <p class="delete-confirm-text mb-0">Data yang dihapus tidak dapat dikembalikan. Lanjutkan menghapus data ini?</p>
        <div class="delete-confirm-actions">
            <button type="button" class="btn btn-light-soft btn-sm" id="deleteCancelBtn">Batal</button>
            <button type="button" class="btn btn-danger-soft btn-sm" id="deleteProceedBtn">Ya, Hapus</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const forms = document.querySelectorAll('.js-delete-form');
    const backdrop = document.getElementById('deleteConfirmBackdrop');
    const cancelBtn = document.getElementById('deleteCancelBtn');
    const proceedBtn = document.getElementById('deleteProceedBtn');
    let activeForm = null;

    if (!backdrop || !cancelBtn || !proceedBtn || forms.length === 0) {
        return;
    }

    function closeModal() {
        backdrop.classList.add('d-none');
        document.body.classList.remove('modal-delete-open');
        activeForm = null;
    }

    forms.forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            activeForm = form;
            backdrop.classList.remove('d-none');
            document.body.classList.add('modal-delete-open');
        });
    });

    cancelBtn.addEventListener('click', closeModal);
    backdrop.addEventListener('click', function (event) {
        if (event.target === backdrop) {
            closeModal();
        }
    });

    proceedBtn.addEventListener('click', function () {
        if (activeForm) {
            activeForm.submit();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeModal();
        }
    });
});
</script>
