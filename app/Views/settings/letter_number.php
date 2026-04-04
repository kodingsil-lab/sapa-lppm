<?php
$app = require __DIR__ . '/../../../config.php';
$basePath = rtrim((string) (parse_url((string) ($app['app']['url'] ?? ''), PHP_URL_PATH) ?? ''), '/');
$settings = $settings ?? [];
$overview = $overview ?? ['K' => ['total' => 0, 'last_nomor' => 0], 'I' => ['total' => 0, 'last_nomor' => 0], 'T' => ['total' => 0, 'last_nomor' => 0]];
$recentByJenis = $recentByJenis ?? ['K' => [], 'I' => [], 'T' => []];
$selectedYear = (int) ($selectedYear ?? (int) date('Y'));
$activeTab = strtoupper((string) ($activeTab ?? 'K'));
if (!in_array($activeTab, ['K', 'I', 'T'], true)) {
    $activeTab = 'K';
}
$defaultTemplate = '{nomor_urut}/{jenis_surat}/{skema}/LPPM-UNISAP/{bulan_romawi}/{tahun}';

$settingsByJenis = [
    'K' => ['jenis_surat' => 'K', 'nama_jenis' => 'Kontrak', 'format_template' => $defaultTemplate, 'is_active' => 1],
    'I' => ['jenis_surat' => 'I', 'nama_jenis' => 'Izin', 'format_template' => $defaultTemplate, 'is_active' => 1],
    'T' => ['jenis_surat' => 'T', 'nama_jenis' => 'Tugas', 'format_template' => $defaultTemplate, 'is_active' => 1],
];
foreach ($settings as $item) {
    $code = strtoupper((string) ($item['jenis_surat'] ?? ''));
    if (isset($settingsByJenis[$code])) {
        $settingsByJenis[$code] = $item;
    }
}

$jenisLabel = ['K' => 'Kontrak', 'I' => 'Izin', 'T' => 'Tugas'];
$totalAll = (int) (($overview['K']['total'] ?? 0) + ($overview['I']['total'] ?? 0) + ($overview['T']['total'] ?? 0));
$activeTemplatePreview = (string) (($settingsByJenis[$activeTab]['format_template'] ?? '') !== ''
    ? $settingsByJenis[$activeTab]['format_template']
    : $defaultTemplate);
$buildFullLetterNumber = static function (array $row, array $setting, string $defaultTemplate): string {
    $template = trim((string) ($setting['format_template'] ?? ''));
    if ($template === '') {
        $template = $defaultTemplate;
    }

    $createdAtRaw = trim((string) ($row['created_at'] ?? ''));
    $timestamp = $createdAtRaw !== '' ? strtotime($createdAtRaw) : false;
    $month = $timestamp !== false ? (int) date('n', $timestamp) : (int) date('n');

    try {
        $romanMonth = monthToRoman($month);
    } catch (Throwable $e) {
        $romanMonth = monthToRoman((int) date('n'));
    }

    $jenisSurat = strtoupper(trim((string) ($row['jenis_surat'] ?? ($setting['jenis_surat'] ?? ''))));
    $nomorUrut = sprintf('%03d', (int) ($row['nomor_urut'] ?? 0));
    $skema = trim((string) ($row['skema'] ?? ''));
    $tahun = (string) ((int) ($row['tahun'] ?? date('Y')));

    return strtr($template, [
        '{nomor_urut}' => $nomorUrut,
        '{jenis_surat}' => $jenisSurat,
        '{skema}' => $skema,
        '{bulan_romawi}' => $romanMonth,
        '{tahun}' => $tahun,
    ]);
};
?>

<style>
.letter-number-page .letter-number-tabs {
    margin-bottom: 1.25rem;
}

.letter-number-page .letter-filter-form {
    display: grid;
    grid-template-columns: minmax(180px, 0.8fr) minmax(360px, 1.8fr) minmax(180px, 1fr) minmax(160px, 0.8fr);
    gap: 0.9rem;
    align-items: end;
}

.letter-number-page .letter-filter-form .letter-filter-group {
    min-width: 0;
}

.letter-number-page .letter-filter-form .letter-filter-group-wide {
    min-width: 0;
}

.letter-number-page .letter-filter-form .letter-filter-action {
    min-width: 0;
}

.letter-number-page .letter-filter-form .btn {
    width: 100%;
}

.letter-number-page .letter-number-tabs .nav-link {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
}

.letter-number-page .tab-section-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #163a63;
    margin-bottom: 0.2rem;
}

.letter-number-page .tab-section-subtitle {
    font-size: 0.92rem;
    color: #60758d;
    margin-bottom: 1rem;
}

.letter-number-page .tab-icon-badge {
    width: 28px;
    height: 28px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #eef5ff;
    color: #2f6fd6;
    font-size: 0.95rem;
}

.letter-number-page .nav-link.active .tab-icon-badge {
    background: #dcecff;
    color: #163a63;
}

.letter-number-page .full-number-cell {
    white-space: nowrap;
    font-weight: 600;
    color: #1d3f67;
}

.letter-number-page .tab-content > .tab-pane > .border {
    border-color: #e5edf7 !important;
}

.letter-number-page .myletters-table-wrap table thead th {
    white-space: nowrap;
}

.letter-number-page .myletters-table-wrap table tbody td {
    vertical-align: middle;
}

.letter-number-page .letter-form-actions {
    margin-top: 0.1rem;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.55rem;
}

.letter-number-page .letter-form-actions .btn {
    min-height: 42px;
    border-radius: 12px;
    font-weight: 600;
    width: 100%;
}

@media (max-width: 1199.98px) {
    .letter-number-page .letter-filter-form {
        grid-template-columns: 1fr 1fr;
    }

    .letter-number-page .tab-section-title {
        font-size: 1.02rem;
    }
}

@media (max-width: 767.98px) {
    .letter-number-page .page-content {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }

    .letter-number-page .letter-filter-form {
        grid-template-columns: 1fr;
        gap: 0.7rem;
    }

    .letter-number-page .letter-number-tabs {
        display: flex;
        flex-wrap: nowrap;
        overflow-x: auto;
        overflow-y: hidden;
        white-space: nowrap;
        padding-bottom: 0.35rem;
        margin-bottom: 0.9rem;
    }

    .letter-number-page .letter-number-tabs .nav-item {
        flex: 0 0 auto;
    }

    .letter-number-page .letter-number-tabs .nav-link {
        padding: 0.48rem 0.7rem;
        font-size: 0.9rem;
    }

    .letter-number-page .tab-section-title {
        font-size: 0.98rem;
        margin-bottom: 0.15rem;
    }

    .letter-number-page .tab-section-subtitle {
        font-size: 0.86rem;
        line-height: 1.45;
    }

    .letter-number-page .tab-pane .border {
        padding: 0.8rem !important;
        border-radius: 0.75rem !important;
    }

    .letter-number-page .myletters-table-wrap table {
        min-width: 760px;
    }

    .letter-number-page .myletters-table-wrap table thead th,
    .letter-number-page .myletters-table-wrap table tbody td {
        font-size: 0.8rem;
        padding-top: 0.52rem;
        padding-bottom: 0.52rem;
    }

    .letter-number-page .full-number-cell {
        font-size: 0.79rem;
    }

    .letter-number-page .letter-form-actions {
        margin-top: 0.75rem !important;
        gap: 0.5rem !important;
    }

    .letter-number-page .letter-form-actions .btn {
        flex: 1 1 auto;
        min-width: 0;
        min-height: 40px;
    }
}
</style>

<div class="page-content myletters-page compact-list contract-setting-page letter-number-page">
    <div class="mb-4">
        <h2 class="admin-page-title mb-1">Pengaturan Nomor Surat</h2>
        <p class="admin-page-subtitle mb-0">Atur format nomor surat dan pantau nomor surat yang sudah terbit.</p>
    </div>

    <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success"><?= htmlspecialchars((string) $successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars((string) $errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="card dashboard-card mb-3 myletters-filter-card">
        <div class="card-body">
            <form method="get" action="<?= htmlspecialchars($basePath . '/pengaturan/nomor-surat', ENT_QUOTES, 'UTF-8'); ?>" class="letter-filter-form">
                <input type="hidden" name="tab" value="<?= htmlspecialchars($activeTab, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="letter-filter-group">
                    <label class="form-label">Tahun</label>
                    <input type="number" min="2000" max="2100" name="tahun" class="form-control" value="<?= (int) $selectedYear; ?>">
                </div>
                <div class="letter-filter-group letter-filter-group-wide">
                    <label class="form-label">Contoh Format Aktif</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($activeTemplatePreview, ENT_QUOTES, 'UTF-8'); ?>" readonly>
                </div>
                <div class="letter-filter-action">
                    <label class="form-label form-label-ghost">Aksi</label>
                    <button type="submit" class="btn btn-primary-main myletters-btn">Terapkan</button>
                </div>
                <div class="letter-filter-action">
                    <label class="form-label form-label-ghost">Aksi</label>
                    <a href="<?= htmlspecialchars($basePath . '/pengaturan/nomor-surat?tab=' . $activeTab, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-light-soft myletters-btn">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card dashboard-card mt-3 letters-table-card myletters-table-card">
        <div class="card-header bg-white border-0 pt-3 px-3">
            <h6 class="mb-0"><i class="bi bi-table me-2"></i>Pengaturan Nomor Surat per Jenis Surat</h6>
        </div>
        <div class="card-body pt-2">
            <ul class="nav nav-tabs letter-number-tabs" role="tablist">
                <?php foreach (['K', 'I', 'T'] as $code): ?>
                    <li class="nav-item" role="presentation">
                        <a
                            class="nav-link <?= htmlspecialchars($activeTab === $code ? 'active' : '', ENT_QUOTES, 'UTF-8'); ?>"
                            href="<?= htmlspecialchars($basePath . '/pengaturan/nomor-surat?tahun=' . $selectedYear . '&tab=' . $code, ENT_QUOTES, 'UTF-8'); ?>"
                        >
                            <span class="tab-icon-badge">
                                <i class="bi <?= htmlspecialchars($code === 'K' ? 'bi-file-earmark-richtext' : ($code === 'I' ? 'bi-file-earmark-text' : 'bi-file-earmark-check'), ENT_QUOTES, 'UTF-8'); ?>"></i>
                            </span>
                            <?= htmlspecialchars($jenisLabel[$code], ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="tab-content">
                <?php foreach (['K', 'I', 'T'] as $code): ?>
                    <?php $setting = $settingsByJenis[$code]; ?>
                    <?php $rows = $recentByJenis[$code] ?? []; ?>
                    <div class="tab-pane fade <?= htmlspecialchars($activeTab === $code ? 'show active' : '', ENT_QUOTES, 'UTF-8'); ?>" id="tab-<?= htmlspecialchars(strtolower($code), ENT_QUOTES, 'UTF-8'); ?>" role="tabpanel">
                        <div class="border rounded-3 p-3">
                            <div class="tab-section-title">Pengaturan Nomor Surat <?= htmlspecialchars($jenisLabel[$code], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="tab-section-subtitle">Atur format dan status aktif nomor surat <?= htmlspecialchars(strtolower($jenisLabel[$code]), ENT_QUOTES, 'UTF-8'); ?> untuk tahun <?= (int) $selectedYear; ?>.</div>

                            <form method="post" action="<?= htmlspecialchars($basePath . '/pengaturan/nomor-surat?tahun=' . $selectedYear . '&tab=' . $code, ENT_QUOTES, 'UTF-8'); ?>" class="mb-4">
                                <input type="hidden" name="active_tab" value="<?= htmlspecialchars((string) $code, ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="tahun" value="<?= (int) $selectedYear; ?>">
                                <input type="hidden" name="settings[0][jenis_surat]" value="<?= htmlspecialchars((string) $code, ENT_QUOTES, 'UTF-8'); ?>">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Tahun</label>
                                        <input type="number" class="form-control" value="<?= (int) $selectedYear; ?>" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Nama Jenis</label>
                                        <input type="text" name="settings[0][nama_jenis]" class="form-control" value="<?= htmlspecialchars((string) ($setting['nama_jenis'] ?? $jenisLabel[$code]), ENT_QUOTES, 'UTF-8'); ?>" required>
                                    </div>
                                    <div class="col-md-9">
                                        <label class="form-label">Format Template</label>
                                        <input type="text" name="settings[0][format_template]" class="form-control" value="<?= htmlspecialchars((string) ($setting['format_template'] ?? $defaultTemplate), ENT_QUOTES, 'UTF-8'); ?>" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Status Aktif</label>
                                        <select name="settings[0][is_active]" class="form-select">
                                            <option value="1" <?= (int) ($setting['is_active'] ?? 1) === 1 ? 'selected' : ''; ?>>Ya</option>
                                            <option value="0" <?= (int) ($setting['is_active'] ?? 1) === 0 ? 'selected' : ''; ?>>Tidak</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row g-3 mt-0">
                                    <div class="col-md-3 ms-md-auto">
                                        <div class="letter-form-actions">
                                            <button type="button" class="btn btn-light-soft" onclick="this.form.reset();">Reset</button>
                                            <button type="submit" class="btn btn-primary-main">Simpan Pengaturan</button>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <div class="tab-section-title mt-4 mb-2">Riwayat Nomor Terbit</div>
                            <div class="activity-table-wrap myletters-table-wrap table-responsive">
                                <table data-custom-pagination="10" class="table table-hover align-middle mb-0 w-100">
                                    <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nomor Surat Utuh</th>
                                        <th>Skema</th>
                                        <th>Dibuat</th>
                                        <th>Nomor Urut</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php if (empty($rows)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-3">Belum ada nomor surat terbit untuk jenis ini pada tahun <?= (int) $selectedYear; ?>.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($rows as $i => $row): ?>
                                            <?php $fullLetterNumber = $buildFullLetterNumber($row, $setting, $defaultTemplate); ?>
                                            <tr>
                                                <td><?= (int) $i + 1; ?></td>
                                                <td class="full-number-cell"><?= htmlspecialchars($fullLetterNumber, ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?= htmlspecialchars((string) ($row['skema'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?= htmlspecialchars((string) ($row['created_at'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?= str_pad((string) (int) ($row['nomor_urut'] ?? 0), 3, '0', STR_PAD_LEFT); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
