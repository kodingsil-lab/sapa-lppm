<?php
$app = require __DIR__ . '/../../../config.php';
$basePath = rtrim((string) (parse_url((string) ($app['app']['url'] ?? ''), PHP_URL_PATH) ?? ''), '/');
$items = is_array($items ?? null) ? $items : [];
?>
<?php require __DIR__ . '/_styles.php'; ?>
<div class="page-content myletters-page compact-list master-list-page">
    <div class="mb-4">
        <h2 class="admin-page-title mb-1"><?= htmlspecialchars((string) ($pageTitle ?? 'Master Sumber Dana'), ENT_QUOTES, 'UTF-8'); ?></h2>
        <p class="admin-page-subtitle mb-0"><?= htmlspecialchars((string) ($pageSubtitle ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
    <?php if (!empty($successMessage)): ?><div class="alert alert-success"><?= htmlspecialchars((string) $successMessage, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
    <?php if (!empty($errorMessage)): ?><div class="alert alert-danger"><?= htmlspecialchars((string) $errorMessage, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
    <?php require __DIR__ . '/_nav.php'; ?>

    <div class="card dashboard-card mt-2 letters-table-card myletters-table-card">
        <div class="card-header bg-white border-0 pt-3 px-3 d-flex justify-content-between align-items-center gap-2 flex-wrap">
            <h6 class="mb-0"><i class="bi bi-table me-2"></i>Daftar Sumber Dana</h6>
            <a href="<?= htmlspecialchars($basePath . '/master-data/sumber-dana/create', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary-main master-list-add-btn">Tambah Sumber Dana</a>
        </div>
        <div class="card-body pt-2 pb-2">
            <div class="activity-table-wrap myletters-table-wrap table-responsive">
                <table class="table table-hover align-middle mb-0 w-100" data-custom-pagination="10">
                    <thead><tr><th>No.</th><th>Kategori</th><th>Nama Sumber Dana</th><th>Kode</th><th>Status</th><th class="master-action-col">Aksi</th></tr></thead>
                    <tbody>
                    <?php foreach ($items as $index => $item): ?>
                        <tr>
                            <td><?= $index + 1; ?></td>
                            <td><?= htmlspecialchars(ucfirst((string) ($item['activity_category_code'] ?? '-')), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?= htmlspecialchars((string) ($item['name'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?= htmlspecialchars((string) ($item['code'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                            <?php $isActive = (int) ($item['is_active'] ?? 0) === 1; ?>
                            <td><span class="master-status-pill <?= $isActive ? 'master-status-active' : 'master-status-inactive'; ?>"><?= $isActive ? 'Aktif' : 'Nonaktif'; ?></span></td>
                            <td class="master-action-col">
                                <div class="activity-action-wrap myletters-actions">
                                    <a href="<?= htmlspecialchars($basePath . '/master-data/sumber-dana/' . (int) ($item['id'] ?? 0) . '/edit', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm activity-btn user-action-btn user-action-detail">Edit</a>
                                    <form method="post" action="<?= htmlspecialchars($basePath . '/master-data/sumber-dana/hapus', ENT_QUOTES, 'UTF-8'); ?>" class="d-inline">
                                        <input type="hidden" name="id" value="<?= (int) ($item['id'] ?? 0); ?>">
                                        <button type="submit" class="btn btn-sm activity-btn user-action-btn user-action-delete" onclick="return confirm('Hapus sumber dana ini?');">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($items === []): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Belum ada data sumber dana.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
