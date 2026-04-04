<?php
$rows = $logs ?? [];
$app = require __DIR__ . '/../../../config.php';
$basePath = rtrim((string) (parse_url((string) ($app['app']['url'] ?? ''), PHP_URL_PATH) ?? ''), '/');
$roleLabelMap = [
    'dosen' => 'Dosen',
    'kepala_lppm' => 'Kepala LPPM',
    'admin' => 'Admin',
    'admin_lppm' => 'Admin',
];
?>

<div class="page-content myletters-page compact-list activity-log-page">
    <div class="mb-3">
        <h2 class="admin-page-title mb-1">Log Aktivitas</h2>
        <p class="admin-page-subtitle mb-0">Riwayat aktivitas dosen, kepala LPPM, dan admin pada sistem SAPA LPPM.</p>
    </div>

    <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success"><?= htmlspecialchars((string) $successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars((string) $errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="mt-2 letters-table-card myletters-table-card">
        <div class="card-header bg-white border-0 pt-3 px-3 d-flex justify-content-between align-items-center gap-2 flex-wrap">
            <h6 class="mb-0"><i class="bi bi-table me-2"></i>Daftar Aktivitas Sistem</h6>
            <button type="button" id="btnBulkDelete" class="btn btn-sm btn-danger" disabled data-bs-toggle="modal" data-bs-target="#bulkDeleteLogModal">
                Hapus Terpilih
            </button>
        </div>
        <div class="card-body pt-2">
            <form id="bulkDeleteForm" method="post" action="<?= htmlspecialchars($basePath . '/log-aktivitas/hapus', ENT_QUOTES, 'UTF-8'); ?>">
                <div class="activity-table-wrap myletters-table-wrap table-responsive">
                    <table id="activityLogTable" data-custom-pagination="25" class="table table-hover align-middle mb-0 w-100">
                        <thead>
                            <tr>
                                <th style="width:42px;"><input type="checkbox" id="checkAllLogs"></th>
                                <th>No.</th>
                                <th>Waktu</th>
                                <th>Pengguna</th>
                                <th>Role</th>
                                <th>Modul</th>
                                <th>Aktivitas</th>
                                <th>Data ID</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rows)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-3">Belum ada data log aktivitas.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($rows as $index => $row): ?>
                                    <?php $roleKey = strtolower(trim((string) ($row['user_role'] ?? ''))); ?>
                                    <tr>
                                        <td><input type="checkbox" name="log_ids[]" value="<?= (int) ($row['id'] ?? 0); ?>" class="log-checkbox"></td>
                                        <td><?= (int) $index + 1; ?></td>
                                        <td><?= htmlspecialchars((string) date('d M Y H:i', strtotime((string) ($row['created_at'] ?? 'now'))), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?= htmlspecialchars((string) (($row['user_name'] ?? '') !== '' ? $row['user_name'] : 'Sistem'), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?= htmlspecialchars((string) ($roleLabelMap[$roleKey] ?? strtoupper($roleKey !== '' ? $roleKey : '-')), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?= htmlspecialchars((string) (($row['module'] ?? '') !== '' ? $row['module'] : '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?= htmlspecialchars((string) (($row['action'] ?? '') !== '' ? $row['action'] : '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?= htmlspecialchars((string) (($row['data_id'] ?? null) !== null ? (string) $row['data_id'] : '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="bulkDeleteLogModal" tabindex="-1" aria-labelledby="bulkDeleteLogModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:14px;">
            <div class="modal-header" style="background:#f6f9ff;border-bottom:1px solid #e5edf8;">
                <h5 class="modal-title" id="bulkDeleteLogModalLabel" style="color:#123c6b;">Konfirmasi Hapus Log</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1">Anda yakin ingin menghapus log aktivitas terpilih?</p>
                <div class="text-muted" style="font-size:13px;">Tindakan ini tidak dapat dibatalkan.</div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light-soft" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="confirmBulkDeleteBtn">Hapus</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var checkAll = document.getElementById('checkAllLogs');
    var checkboxes = document.querySelectorAll('.log-checkbox');
    var bulkDeleteBtn = document.getElementById('btnBulkDelete');
    var confirmDeleteBtn = document.getElementById('confirmBulkDeleteBtn');
    var bulkForm = document.getElementById('bulkDeleteForm');

    var refreshBulkButtonState = function () {
        var selectedCount = 0;
        checkboxes.forEach(function (cb) {
            if (cb.checked) {
                selectedCount += 1;
            }
        });
        if (bulkDeleteBtn) {
            bulkDeleteBtn.disabled = selectedCount === 0;
        }
    };

    if (checkAll) {
        checkAll.addEventListener('change', function () {
            checkboxes.forEach(function (cb) {
                cb.checked = checkAll.checked;
            });
            refreshBulkButtonState();
        });
    }

    checkboxes.forEach(function (cb) {
        cb.addEventListener('change', function () {
            if (!cb.checked && checkAll) {
                checkAll.checked = false;
            }
            if (checkAll) {
                var allChecked = true;
                checkboxes.forEach(function (item) {
                    if (!item.checked) {
                        allChecked = false;
                    }
                });
                checkAll.checked = allChecked && checkboxes.length > 0;
            }
            refreshBulkButtonState();
        });
    });

    if (confirmDeleteBtn && bulkForm) {
        confirmDeleteBtn.addEventListener('click', function () {
            bulkForm.submit();
        });
    }

    refreshBulkButtonState();
});
</script>
