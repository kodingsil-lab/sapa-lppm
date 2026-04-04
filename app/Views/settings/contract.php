<?php
$app = require __DIR__ . '/../../../config.php';
$basePath = rtrim((string) (parse_url((string) ($app['app']['url'] ?? ''), PHP_URL_PATH) ?? ''), '/');
$settingsBySource = $settingsBySource ?? [];
$selectedYear = (int) ($selectedYear ?? (int) date('Y'));
$activeTab = strtolower(trim((string) ($activeTab ?? 'hibah_dikti')));
$yearSummaryRowsBySource = $yearSummaryRowsBySource ?? [];
$viewMode = strtolower(trim((string) ($viewMode ?? 'edit')));
$isDetailMode = $viewMode === 'detail';
$scope = strtolower(trim((string) ($scope ?? 'penelitian')));
$scopeLabel = match ($scope) {
    'pengabdian' => 'Pengabdian',
    'hilirisasi' => 'Hilirisasi',
    default => 'Penelitian',
};

$sourceLabels = [
    'hibah_dikti' => 'Hibah Dikti',
    'internal_pt' => 'Internal PT',
];

$defaultRow = [
    'nomor_kontrak_dikti' => '',
    'nomor_kontrak_lldikti_xv' => '',
    'hari_penandatanganan' => '',
    'tanggal_penandatanganan' => '',
    'tanggal_mulai_global' => '',
    'tanggal_selesai_global' => '',
    'batas_tanggal_tahap_1' => '',
    'batas_tanggal_tahap_2' => '',
    'batas_upload_tahap_2' => '',
    'batas_laporan_akhir' => '',
    'persentase_tahap_1' => '',
    'persentase_tahap_2' => '',
    'updated_at' => '',
];

$displayOrDash = static function ($value): string {
    $text = trim((string) ($value ?? ''));
    return $text !== '' ? $text : '-';
};
?>

<style>
.contract-tab-icon-badge {
    width: 28px;
    height: 28px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #eef5ff;
    color: #2f6fd6;
    font-size: 0.95rem;
    margin-right: 0.45rem;
}

.contract-setting-page .nav-tabs .nav-link {
    display: inline-flex;
    align-items: center;
}

.contract-setting-page .nav-tabs .nav-link.active .contract-tab-icon-badge {
    background: #dcecff;
    color: #163a63;
}

.contract-action-group {
    display: inline-flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.5rem;
    flex-wrap: nowrap;
}

.contract-action-btn {
    min-width: 78px;
    height: 34px;
    padding: 0.4rem 0.85rem;
    border-radius: 10px;
    font-size: 0.875rem;
    font-weight: 600;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: all 0.2s ease;
}

.contract-action-btn:hover,
.contract-action-btn:focus {
    transform: translateY(-1px);
}

.contract-action-btn-detail {
    color: #4f6f8f;
    background: #f6f9fc;
    border: 1px solid #d7e4f2;
}

.contract-action-btn-detail:hover,
.contract-action-btn-detail:focus {
    color: #224b7a;
    background: #edf4fb;
    border-color: #bfd5eb;
}

.contract-action-btn-edit {
    color: #387adb;
    background: #eef5ff;
    border: 1px solid #b9d0f4;
}

.contract-action-btn-edit:hover,
.contract-action-btn-edit:focus {
    color: #245fb8;
    background: #e3efff;
    border-color: #93b8ee;
}

.contract-action-btn-delete {
    color: #d45b5b;
    background: #fff4f4;
    border: 1px solid #f2c3c3;
}

.contract-action-btn-delete:hover,
.contract-action-btn-delete:focus {
    color: #b94343;
    background: #ffeaea;
    border-color: #eba7a7;
}

.contract-action-form {
    margin: 0;
}
</style>

<div class="page-content myletters-page compact-list contract-setting-page">
    <div class="mb-4">
        <h2 class="admin-page-title mb-1">Seting Kontrak <?= htmlspecialchars($scopeLabel, ENT_QUOTES, 'UTF-8'); ?></h2>
        <p class="admin-page-subtitle mb-0">Pengaturan master kontrak <?= htmlspecialchars(strtolower($scopeLabel), ENT_QUOTES, 'UTF-8'); ?> berdasarkan sumber dana untuk otomatisasi penerbitan surat kontrak <?= htmlspecialchars(strtolower($scopeLabel), ENT_QUOTES, 'UTF-8'); ?>.</p>
    </div>

    <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success"><?= htmlspecialchars((string) $successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars((string) $errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($isDetailMode): ?>
        <div class="alert alert-info">Detail seting kontrak tahun anggaran <?= (int) $selectedYear; ?> sedang ditampilkan.</div>
    <?php endif; ?>

    <div class="card dashboard-card letters-table-card myletters-table-card">
        <div class="card-header bg-white border-0 pt-3 px-3">
            <h6 class="mb-0"><i class="bi bi-table me-2"></i>Seting Kontrak <?= htmlspecialchars($scopeLabel, ENT_QUOTES, 'UTF-8'); ?> Per Sumber Dana</h6>
        </div>
        <div class="card-body pt-2">
            <form method="post" action="<?= htmlspecialchars($basePath . '/pengaturan/kontrak', ENT_QUOTES, 'UTF-8'); ?>" id="contract-setting-form">
                <input type="hidden" name="scope" value="<?= htmlspecialchars($scope, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="active_tab" value="hibah_dikti">
                <ul class="nav nav-tabs mb-3" id="contractSettingTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-hibah-btn" data-bs-toggle="tab" data-bs-target="#tab-hibah" type="button" role="tab" aria-controls="tab-hibah" aria-selected="true"><span class="contract-tab-icon-badge"><i class="bi bi-bank"></i></span>Hibah Dikti</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-internal-btn" data-bs-toggle="tab" data-bs-target="#tab-internal" type="button" role="tab" aria-controls="tab-internal" aria-selected="false"><span class="contract-tab-icon-badge"><i class="bi bi-building"></i></span>Internal PT</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-mandiri-btn" data-bs-toggle="tab" data-bs-target="#tab-mandiri" type="button" role="tab" aria-controls="tab-mandiri" aria-selected="false"><span class="contract-tab-icon-badge"><i class="bi bi-person-badge"></i></span>Mandiri (Dosen)</button>
                    </li>
                </ul>

                <div class="tab-content" id="contractSettingTabContent">
                    <div class="tab-pane fade show active" id="tab-hibah" role="tabpanel" aria-labelledby="tab-hibah-btn">
                <?php
                $sourceKey = 'hibah_dikti';
                $sourceLabel = 'Hibah Dikti';
                $row = $defaultRow;
                ?>
                <div class="border rounded-3 p-3 mb-3">
                    <h6 class="mb-3"><?= htmlspecialchars($sourceLabel, ENT_QUOTES, 'UTF-8'); ?></h6>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Tahun Anggaran</label>
                            <input type="number" name="tahun_anggaran" class="form-control" min="2000" max="2100" step="1" value="<?= (int) $selectedYear; ?>" required <?= $isDetailMode ? 'readonly' : ''; ?>>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nomor Kontrak Dikti</label>
                            <input type="text" name="settings[<?= htmlspecialchars($sourceKey, ENT_QUOTES, 'UTF-8'); ?>][nomor_kontrak_dikti]" class="form-control" value="<?= htmlspecialchars((string) $row['nomor_kontrak_dikti'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="Contoh: 123/E5/PG.02.00/2026" required <?= $isDetailMode ? 'readonly' : ''; ?>>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nomor Kontrak LLDIKTI XV</label>
                            <input type="text" name="settings[<?= htmlspecialchars($sourceKey, ENT_QUOTES, 'UTF-8'); ?>][nomor_kontrak_lldikti_xv]" class="form-control" value="<?= htmlspecialchars((string) $row['nomor_kontrak_lldikti_xv'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="Contoh: 045/LL15/PG.02.00/2026" required <?= $isDetailMode ? 'readonly' : ''; ?>>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Hari Penandatanganan Kontrak</label>
                            <input type="text" name="settings[<?= htmlspecialchars($sourceKey, ENT_QUOTES, 'UTF-8'); ?>][hari_penandatanganan]" class="form-control" value="<?= htmlspecialchars((string) $row['hari_penandatanganan'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="Contoh: Jumat" required <?= $isDetailMode ? 'readonly' : ''; ?>>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Penandatanganan Kontrak</label>
                            <input type="date" name="settings[<?= htmlspecialchars($sourceKey, ENT_QUOTES, 'UTF-8'); ?>][tanggal_penandatanganan]" class="form-control" value="<?= htmlspecialchars((string) $row['tanggal_penandatanganan'], ENT_QUOTES, 'UTF-8'); ?>" required <?= $isDetailMode ? 'readonly' : ''; ?>>
                        </div>
                        <?php if (in_array($scope, ['hilirisasi', 'penelitian', 'pengabdian'], true)): ?>
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Mulai Kegiatan</label>
                                <input type="date" name="settings[<?= htmlspecialchars($sourceKey, ENT_QUOTES, 'UTF-8'); ?>][tanggal_mulai_global]" class="form-control" value="<?= htmlspecialchars((string) $row['tanggal_mulai_global'], ENT_QUOTES, 'UTF-8'); ?>" required <?= $isDetailMode ? 'readonly' : ''; ?>>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Selesai Kegiatan</label>
                                <input type="date" name="settings[<?= htmlspecialchars($sourceKey, ENT_QUOTES, 'UTF-8'); ?>][tanggal_selesai_global]" class="form-control" value="<?= htmlspecialchars((string) $row['tanggal_selesai_global'], ENT_QUOTES, 'UTF-8'); ?>" required <?= $isDetailMode ? 'readonly' : ''; ?>>
                            </div>
                        <?php endif; ?>
                        <div class="col-md-6">
                            <label class="form-label">Persentase Pencairan Tahap 1 (%)</label>
                            <input type="number" name="settings[<?= htmlspecialchars($sourceKey, ENT_QUOTES, 'UTF-8'); ?>][persentase_tahap_1]" class="form-control" min="1" max="99" step="0.01" required value="<?= htmlspecialchars((string) $row['persentase_tahap_1'], ENT_QUOTES, 'UTF-8'); ?>" <?= $isDetailMode ? 'readonly' : ''; ?>>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Persentase Pencairan Tahap 2 (%)</label>
                            <input type="number" name="settings[<?= htmlspecialchars($sourceKey, ENT_QUOTES, 'UTF-8'); ?>][persentase_tahap_2]" class="form-control" min="1" max="99" step="0.01" required value="<?= htmlspecialchars((string) $row['persentase_tahap_2'], ENT_QUOTES, 'UTF-8'); ?>" <?= $isDetailMode ? 'readonly' : ''; ?>>
                        </div>
                    </div>
                </div>
                    </div>
                    <div class="tab-pane fade" id="tab-internal" role="tabpanel" aria-labelledby="tab-internal-btn">
                        <div class="border rounded-3 p-4 mb-3 bg-light-subtle">
                            <h6 class="mb-2">Internal PT</h6>
                            <p class="text-muted mb-0">Fitur ini belum tersedia.</p>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="tab-mandiri" role="tabpanel" aria-labelledby="tab-mandiri-btn">
                        <div class="border rounded-3 p-4 mb-3 bg-light-subtle">
                            <h6 class="mb-2">Mandiri (Dosen)</h6>
                            <p class="text-muted mb-0">Fitur ini belum tersedia.</p>
                        </div>
                    </div>
                </div>

                <?php if (!$isDetailMode): ?>
                    <div class="col-12 d-flex gap-2 justify-content-end mt-3" id="hibah-action-bar">
                        <button type="button" class="btn btn-light-soft" onclick="this.form.reset();">Reset</button>
                        <button type="submit" class="btn btn-primary-main">Simpan Seting Kontrak</button>
                    </div>
                <?php endif; ?>
            </form>

            <?php
            $summaryRows = (array) ($yearSummaryRowsBySource['hibah_dikti'] ?? []);
            ?>
            <div class="border rounded-3 p-3 mt-4" id="hibah-summary-section">
                <h6 class="mb-3">Ringkasan Hibah Dikti <?= htmlspecialchars($scopeLabel, ENT_QUOTES, 'UTF-8'); ?> per Tahun</h6>
                <div class="activity-table-wrap myletters-table-wrap table-responsive">
                    <table class="table table-hover align-middle mb-0 w-100">
                        <thead>
                        <tr>
                            <th>Tahun</th>
                            <th>Nomor Kontrak Dikti</th>
                            <th>Nomor Kontrak LLDIKTI XV</th>
                            <th>Tahap 1</th>
                            <th>Tahap 2</th>
                            <th>Diperbarui</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($summaryRows)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Belum ada seting kontrak yang tersimpan untuk Hibah Dikti <?= htmlspecialchars($scopeLabel, ENT_QUOTES, 'UTF-8'); ?>.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($summaryRows as $summary): ?>
                                <?php $year = (int) ($summary['setting_year'] ?? 0); ?>
                                <tr>
                                    <td><strong><?= (int) $year; ?></strong></td>
                                    <td><?= htmlspecialchars((string) $displayOrDash($summary['nomor_kontrak_dikti'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars((string) $displayOrDash($summary['nomor_kontrak_lldikti_xv'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars((string) $displayOrDash((string) ((string) ($summary['persentase_tahap_1'] ?? '') !== '' ? ($summary['persentase_tahap_1'] . '%') : '')), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars((string) $displayOrDash((string) ((string) ($summary['persentase_tahap_2'] ?? '') !== '' ? ($summary['persentase_tahap_2'] . '%') : '')), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars((string) $displayOrDash($summary['updated_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="text-end">
                                        <div class="contract-action-group">
                                            <a href="<?= htmlspecialchars($basePath . '/pengaturan/kontrak/detail?scope=' . $scope . '&tahun_anggaran=' . $year . '&tab=hibah_dikti', ENT_QUOTES, 'UTF-8'); ?>" class="contract-action-btn contract-action-btn-detail">Detail</a>
                                            <a href="<?= htmlspecialchars($basePath . '/pengaturan/kontrak/detail?scope=' . $scope . '&tahun_anggaran=' . $year . '&tab=hibah_dikti&mode=edit', ENT_QUOTES, 'UTF-8'); ?>" class="contract-action-btn contract-action-btn-edit">Edit</a>
                                            <form method="post" action="<?= htmlspecialchars($basePath . '/pengaturan/kontrak/hapus', ENT_QUOTES, 'UTF-8'); ?>" class="contract-action-form" onsubmit="return confirm('Hapus seting kontrak tahun anggaran <?= (int) $year; ?> untuk Hibah Dikti <?= htmlspecialchars($scopeLabel, ENT_QUOTES, 'UTF-8'); ?>?');">
                                                <input type="hidden" name="tahun_anggaran" value="<?= (int) $year; ?>">
                                                <input type="hidden" name="scope" value="<?= htmlspecialchars($scope, ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="source_key" value="hibah_dikti">
                                                <button type="submit" class="contract-action-btn contract-action-btn-delete">Hapus</button>
                                            </form>
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
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var internalTabButton = document.getElementById('tab-internal-btn');
    var mandiriTabButton = document.getElementById('tab-mandiri-btn');
    var hibahTabButton = document.getElementById('tab-hibah-btn');
    var actionBar = document.getElementById('hibah-action-bar');
    var summarySection = document.getElementById('hibah-summary-section');

    function toggleHibahSections(show) {
        if (actionBar) {
            actionBar.style.display = show ? 'flex' : 'none';
        }
        if (summarySection) {
            summarySection.style.display = show ? 'block' : 'none';
        }
    }

    if (internalTabButton) {
        internalTabButton.addEventListener('shown.bs.tab', function () {
            toggleHibahSections(false);
        });
    }

    if (mandiriTabButton) {
        mandiriTabButton.addEventListener('shown.bs.tab', function () {
            toggleHibahSections(false);
        });
    }

    if (hibahTabButton) {
        hibahTabButton.addEventListener('shown.bs.tab', function () {
            toggleHibahSections(true);
        });
    }

    toggleHibahSections(true);
});
</script>
