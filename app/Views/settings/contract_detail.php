<?php
$app = require __DIR__ . '/../../../config.php';
$basePath = rtrim((string) (parse_url((string) ($app['app']['url'] ?? ''), PHP_URL_PATH) ?? ''), '/');
$setting = $setting ?? [];
$selectedYear = (int) ($selectedYear ?? 0);
$sourceKey = (string) ($sourceKey ?? 'hibah_dikti');
$sourceLabel = (string) ($sourceLabel ?? 'Hibah Dikti');
$scope = strtolower(trim((string) ($scope ?? 'penelitian')));
$scopeLabel = match ($scope) {
    'pengabdian' => 'Pengabdian',
    'hilirisasi' => 'Hilirisasi',
    default => 'Penelitian',
};
$viewMode = strtolower(trim((string) ($viewMode ?? 'detail')));
$isEditMode = $viewMode === 'edit';

$displayOrDash = static function ($value): string {
    $text = trim((string) ($value ?? ''));
    return $text !== '' ? $text : '-';
};
$formatTanggalIndonesia = static function ($value): string {
    $text = trim((string) ($value ?? ''));
    if ($text === '') {
        return '-';
    }

    $timestamp = strtotime($text);
    if ($timestamp === false) {
        return $text;
    }

    $bulan = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    $tanggal = (int) date('j', $timestamp);
    $nomorBulan = (int) date('n', $timestamp);
    $tahun = date('Y', $timestamp);

    return $tanggal . ' ' . ($bulan[$nomorBulan] ?? date('F', $timestamp)) . ' ' . $tahun;
};
$formatTanggalWaktuIndonesia = static function ($value) use ($formatTanggalIndonesia): string {
    $text = trim((string) ($value ?? ''));
    if ($text === '') {
        return '-';
    }

    $timestamp = strtotime($text);
    if ($timestamp === false) {
        return $text;
    }

    return $formatTanggalIndonesia($text) . ' ' . date('H:i', $timestamp);
};
$formatPersentase = static function ($value): string {
    $text = trim((string) ($value ?? ''));
    if ($text === '') {
        return '-';
    }

    $normalized = str_replace(',', '.', $text);
    if (!is_numeric($normalized)) {
        return $text;
    }

    $number = (float) $normalized;
    if (abs($number - round($number)) < 0.00001) {
        return (string) (int) round($number);
    }

    $rendered = rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
    return str_replace('.', ',', $rendered);
};
?>

<div class="page-content myletters-page compact-list contract-setting-page">
    <div class="mb-4">
        <h2 class="admin-page-title mb-1">Detail Seting Kontrak <?= htmlspecialchars($scopeLabel, ENT_QUOTES, 'UTF-8'); ?></h2>
        <p class="admin-page-subtitle mb-0">Detail seting kontrak <?= htmlspecialchars(strtolower($scopeLabel), ENT_QUOTES, 'UTF-8'); ?> untuk sumber dana <?= htmlspecialchars($sourceLabel, ENT_QUOTES, 'UTF-8'); ?>.</p>
    </div>

    <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success"><?= htmlspecialchars((string) $successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars((string) $errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <?php if ($isEditMode): ?>
        <div class="alert alert-info">Mode edit aktif. Anda bisa memperbarui field kontrak langsung di halaman ini.</div>
    <?php endif; ?>

    <div class="card dashboard-card letters-table-card myletters-table-card">
        <div class="card-header bg-white border-0 pt-3 px-3">
            <h6 class="mb-0"><i class="bi bi-table me-2"></i><?= htmlspecialchars($sourceLabel, ENT_QUOTES, 'UTF-8'); ?> - Tahun Anggaran <?= (int) $selectedYear; ?></h6>
        </div>
        <div class="card-body">
            <form method="post" action="<?= htmlspecialchars($basePath . '/pengaturan/kontrak', ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="tahun_anggaran" value="<?= (int) $selectedYear; ?>">
                <input type="hidden" name="scope" value="<?= htmlspecialchars($scope, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="active_tab" value="<?= htmlspecialchars($sourceKey, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="redirect_target" value="detail">

                <div class="border rounded-3 p-3">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Tahun Anggaran</label>
                        <input type="text" class="form-control" value="<?= (int) $selectedYear; ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nomor Kontrak Dikti</label>
                        <input type="text" name="settings[<?= htmlspecialchars($sourceKey, ENT_QUOTES, 'UTF-8'); ?>][nomor_kontrak_dikti]" class="form-control" value="<?= htmlspecialchars((string) ($setting['nomor_kontrak_dikti'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required <?= $isEditMode ? '' : 'readonly'; ?>>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nomor Kontrak LLDIKTI XV</label>
                        <input type="text" name="settings[<?= htmlspecialchars($sourceKey, ENT_QUOTES, 'UTF-8'); ?>][nomor_kontrak_lldikti_xv]" class="form-control" value="<?= htmlspecialchars((string) ($setting['nomor_kontrak_lldikti_xv'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required <?= $isEditMode ? '' : 'readonly'; ?>>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Hari Penandatanganan Kontrak</label>
                        <input type="text" name="settings[<?= htmlspecialchars($sourceKey, ENT_QUOTES, 'UTF-8'); ?>][hari_penandatanganan]" class="form-control" value="<?= htmlspecialchars((string) ($setting['hari_penandatanganan'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required <?= $isEditMode ? '' : 'readonly'; ?>>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tanggal Penandatanganan Kontrak</label>
                        <input type="<?= $isEditMode ? 'date' : 'text'; ?>" name="settings[<?= htmlspecialchars($sourceKey, ENT_QUOTES, 'UTF-8'); ?>][tanggal_penandatanganan]" class="form-control" value="<?= htmlspecialchars((string) ($isEditMode ? ($setting['tanggal_penandatanganan'] ?? '') : $formatTanggalIndonesia($setting['tanggal_penandatanganan'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>" required <?= $isEditMode ? '' : 'readonly'; ?>>
                    </div>
                    <?php if (in_array($scope, ['hilirisasi', 'penelitian', 'pengabdian'], true)): ?>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Mulai Kegiatan</label>
                            <input type="<?= $isEditMode ? 'date' : 'text'; ?>" name="settings[<?= htmlspecialchars($sourceKey, ENT_QUOTES, 'UTF-8'); ?>][tanggal_mulai_global]" class="form-control" value="<?= htmlspecialchars((string) ($isEditMode ? ($setting['tanggal_mulai_global'] ?? '') : $formatTanggalIndonesia($setting['tanggal_mulai_global'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>" required <?= $isEditMode ? '' : 'readonly'; ?>>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Selesai Kegiatan</label>
                            <input type="<?= $isEditMode ? 'date' : 'text'; ?>" name="settings[<?= htmlspecialchars($sourceKey, ENT_QUOTES, 'UTF-8'); ?>][tanggal_selesai_global]" class="form-control" value="<?= htmlspecialchars((string) ($isEditMode ? ($setting['tanggal_selesai_global'] ?? '') : $formatTanggalIndonesia($setting['tanggal_selesai_global'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>" required <?= $isEditMode ? '' : 'readonly'; ?>>
                        </div>
                    <?php endif; ?>
                    <div class="col-md-6">
                        <label class="form-label">Persentase Pencairan Tahap 1 (%)</label>
                        <input type="number" name="settings[<?= htmlspecialchars($sourceKey, ENT_QUOTES, 'UTF-8'); ?>][persentase_tahap_1]" class="form-control" min="1" max="99" step="0.01" value="<?= htmlspecialchars((string) ($isEditMode ? ($setting['persentase_tahap_1'] ?? '') : $formatPersentase($setting['persentase_tahap_1'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>" required <?= $isEditMode ? '' : 'readonly'; ?>>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Persentase Pencairan Tahap 2 (%)</label>
                        <input type="number" name="settings[<?= htmlspecialchars($sourceKey, ENT_QUOTES, 'UTF-8'); ?>][persentase_tahap_2]" class="form-control" min="1" max="99" step="0.01" value="<?= htmlspecialchars((string) ($isEditMode ? ($setting['persentase_tahap_2'] ?? '') : $formatPersentase($setting['persentase_tahap_2'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>" required <?= $isEditMode ? '' : 'readonly'; ?>>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Terakhir Diperbarui</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars((string) ($isEditMode ? $displayOrDash($setting['updated_at'] ?? '') : $formatTanggalWaktuIndonesia($setting['updated_at'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>" readonly>
                    </div>
                </div>
                </div>

                <div class="d-flex gap-2 justify-content-end mt-3">
                    <a href="<?= htmlspecialchars($basePath . '/pengaturan/kontrak?scope=' . $scope . '&tab=' . $sourceKey, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-light-soft">Kembali</a>
                    <?php if ($isEditMode): ?>
                        <a href="<?= htmlspecialchars($basePath . '/pengaturan/kontrak/detail?scope=' . $scope . '&tahun_anggaran=' . $selectedYear . '&tab=' . $sourceKey, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-light-soft">Batal</a>
                        <button type="submit" class="btn btn-primary-main">Simpan Perubahan</button>
                    <?php else: ?>
                        <a href="<?= htmlspecialchars($basePath . '/pengaturan/kontrak/detail?scope=' . $scope . '&tahun_anggaran=' . $selectedYear . '&tab=' . $sourceKey . '&mode=edit', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary-main">Edit</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>
